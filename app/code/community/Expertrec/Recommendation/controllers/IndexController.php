<?php
  /*
  Get feed manually from info page
  */
  class Expertrec_Recommendation_IndexController extends Mage_Core_Controller_Front_Action{
    const FEED_PATH = '/Expertrec';
    const IS_UPLOAD_FEED = 'expertrec/general/is_upload';

    public function indexAction (){
      try{
        //Increase memory limit
        ini_set('memory_limit', '1024M');
        //Increase maximum execution time to 5 hours (default in magento)
        set_time_limit(18000);
        // Run main application
        $isUpload = Mage::getStoreConfig(self::IS_UPLOAD_FEED);
        if(isset($isUpload) && $isUpload == 'true'){
          ob_end_clean();
          //avoid apache to kill the php running
          ignore_user_abort(true);
          ob_start();//start buffer output
          echo "Feed generation started in background.";
          //close session file on server side to avoid blocking other requests
          session_write_close();
          //send header to avoid the browser side to take content as gzip format
          header("Content-Encoding: none");
          header("Content-Length: ".ob_get_length());
          header("Connection: close");
          ob_end_flush();
          flush();
          $this->runMain(true);
        }else{
          $this->runMain();
        }
      }catch (Exception $e) {
        Mage::getSingleton('expertrec_recommendation/log')->log( "Exception while preparing the main appilication: ".$e->getMessage());
      }
    }

    public function cleanAction(){
      try{
        //return array of all parameters sent
        $requestParams = Mage::app()->getRequest()->getParams();
        $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';
        // Check password. if invalid password, it will not proceed.
        if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
          die('ERROR: The specified password is invalid.');
        }
        $vFeedDir = "/tmp".self::FEED_PATH;
        //delete all files in Expertrec
        Mage::helper('expertrec_recommendation/filehelper')->cleanDir($vFeedDir);
        die("successfully deleted all files.");
      }catch (Exception $e) {
        Mage::getSingleton('expertrec_recommendation/log')->log( "Exception while cleaning Expertrec feed dir: ".$e->getMessage());
      }
    }

    public function getlogAction(){
      try{
        //return array of all parameters sent
        $requestParams = Mage::app()->getRequest()->getParams();
        $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';
        // Check password. if invalid password, it will not proceed.
        if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
          die('ERROR: The specified password is invalid.');
        }
        //base Directory path
        $logPath = "/tmp".self::FEED_PATH."/expertrec_feed.log";
        $this->downloadFile($logPath);
        return;
      }catch (Exception $e) {
        Mage::getSingleton('expertrec_recommendation/log')->log( "Exception while getting log file: ".$e->getMessage());
        die('ERROR: Unable to download log file.');
      }
    }

    private function runMain($upload = false){
      try{
        //return array of all parameters sent
        $requestParams = Mage::app()->getRequest()->getParams();
        // Get parameters from the requestParams array
        $Command = isset($requestParams['cmd']) ? $requestParams['cmd'] : '';
        $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';
        $imageWidth = isset($requestParams['width']) ? $requestParams['width'] : '';
        $imageHeight = isset($requestParams['height']) ? $requestParams['height'] : '';
        $storeId = isset($requestParams['sid']) ? $requestParams['sid'] : '';
        $websiteId = isset($requestParams['wid']) ? $requestParams['wid'] : '';
        $pageStart = isset($requestParams['ps']) ? $requestParams['ps'] : '';
        $pageEnd = isset($requestParams['pe']) ? $requestParams['pe'] : '';
        $pageSize = isset($requestParams['psize']) ? $requestParams['psize'] : '';
        // Check password. if invalid password, it will not proceed.
        if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
          die('ERROR: The specified password is invalid.');
        }
        $feedConfig = Mage::getSingleton('expertrec_recommendation/feed_feedconfig');
        // if website_id and store_id are not given in the request then retuen with error message 
        if(empty($storeId) && empty($websiteId)){
          die("ERROR: Blank website_id and store_id are not alowed. Edit this request and set website_id and store_id.");
        }else{
          $feedConfig
            ->setWebsiteId($websiteId)
            ->setStoreId($storeId);
        }
        //Limiting feed collection in one request 
        if(!empty($pageStart) && !empty($pageEnd)){
          $feedConfig->setPageStart($pageStart)
            ->setPageEnd($pageEnd);
        }
        //Limiting number of products in a page during feed collection
        if(!empty($pageSize)){
          $feedConfig->setPageSize($pageSize);
        }
        // check whether the image is to generate or not
        if(!empty($imageWidth) && !empty($imageHeight)){
          Mage::getSingleton('expertrec_recommendation/log')->log("setting Image width and height.");
          $feedConfig
            ->setGenerateImage(true)
            ->setImageHeight($imageHeight)
            ->setImageWidth($imageWidth);
        }else{
          $feedConfig->setGenerateImage(false);
        }
        // If the command is export, then run the native export
        if($Command == 'export'){
          if(!Mage::getModel('expertrec_recommendation/feed')->exportFeeds()){
            die("Error: Feed not created. Please try again after some time.");
          }
        }else if($Command == 'getpp'){
          if(!Mage::helper('expertrec_recommendation/suggestionhelper')->exportPopularProducts()){
            die("Error: Popolar products feed not created. Please try again after some time.");
          }
        }else{
          die("The requested URL /expertrec-feed/ was not found on this server.");
        }
        $feedFilePath=$feedConfig->feedFilePath;
        if($upload){
          Mage::helper('expertrec_recommendation')->pushFeed($feedFilePath);
        }else{
          $this->downloadFile($feedFilePath,true);
        }
        return;
      }catch(Exception $e){
        Mage::getSingleton('expertrec_recommendation/log')->log("Error: Entry point error: ".$e->getMessage());
      } 
    }

    private function downloadFile($attachment_location,$delete = false){
      try{
        $fileName = substr($attachment_location, strrpos($attachment_location, '/') + 1);
        if (file_exists($attachment_location)) {
          header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
          header('Expires: 0');
          header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
          header('Content-Description: File Transfer');
          header("Content-Type: application/octet-stream");   
          header('Pragma: public');
          header("Content-Length:".filesize($attachment_location));
          header("Content-Disposition: attachment; filename=".$fileName);
          //header is set, so clear the output buffer before downloading  file
          ob_clean();
          flush();
          //download  file
          readfile($attachment_location);
          if($delete){
            //deleting file
            Mage::helper('expertrec_recommendation/filehelper')->deleteFile($attachment_location);
          }
          die("File downloaded successfully.");
        } else {
          die("Error: File not found.");
        }
      }catch(Exception $e){
        Mage::getSingleton('expertrec_recommendation/log')->log("Error: returning feed error: ".$e->getMessage());
      }        
    }

  }
?>
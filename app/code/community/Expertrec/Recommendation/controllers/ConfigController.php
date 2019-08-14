<?php

class Expertrec_Recommendation_ConfigController extends Mage_Core_Controller_Front_Action {

      const SEARCH_LIST_ENABLE = 'search/enable';
      const SEARCH_LIST_API = 'search/api';
      const SEARCH_FACET_LIST = 'search/facets_list';
      const SEARCH_SINGLE_SELECT_FILTERS = 'search/single_select_filters';
      const SEARCH_IS_AJAX = 'search/is_ajax';
      const SEARCH_ITEMS_PER_PAGE = 'search/items_per_page';
      const SEARCH_DISPLAY_PAGES = 'search/display_pages';
      const SEARCH_FETCH_PRICE = 'search/fetch_price';
      const SEARCH_CONVERT_PRICE = 'search/convert_price';
      const SEARCH_CUSTOM_TEMPLATE = 'search/custom_template';
      const FEED_LOG_ENDPOINT = 'log_endpoint';
      const FEED_UPLOAD_ENDPOINT = 'upload_endpoint';
  
      // return websites and stores information
      public function editAction(){
          try{
              //return array of all parameters sent
              $requestParams = Mage::app()->getRequest()->getParams();
                  
              // Get parameters from the requestParams array
              $mid = isset($requestParams['mid']) ? $requestParams['mid'] : '';
              $secret = isset($requestParams['secret']) ? $requestParams['secret'] : '';
              $pwd = isset($requestParams['pwd']) ? $requestParams['pwd'] : '';
              
              if(empty($mid) || empty($secret) || empty($pwd)){
                  die("Invalid parameters given.");
              }
              
              $storedPwd = Mage::getModel('expertrec_recommendation/validate')->getPassword();
              
              //if passwoed is not valid then it will not proceed    
              if($pwd != $storedPwd){
                  die("Authentication failed");
              }
              
              $encoded_secret = base64_encode($secret);
         
              //store mid and secret then clear cache
              Mage::helper("expertrec_recommendation")
                    ->saveConfig('secret',$encoded_secret)         
                    ->saveConfig('mid',$mid)
                    ->clearCache();

              // need to remove, we now no longer use cache.
              //update config variable
              Mage::getSingleton('expertrec_recommendation/feed_feedconfig')
                    ->setSecret($secret)
                    ->setMerchantId($mid);

              die("Successfully updated mid and secret");
          }catch(Exception $e){
              Mage::getSingleton('expertrec_recommendation/log')->log("Updating mid & secret error: ".$e->getMessage());
              die("Unable to configure mid and secret");
          }    
           
                  
      }

      public function saveheadersAction(){
          try{
              $requestParams = Mage::app()->getRequest()->getParams();
              $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';

              // Check password. if invalid password, it will not proceed.
              if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
                  die('ERROR: The specified password is invalid.');
              }

              $attrArray = array();
              if(isset($requestParams['selected_headers'])){

                  if(!empty($requestParams['check_list'])){
                      
                      foreach($requestParams['check_list'] as $selected){
                          $attrArray[] = $selected;
                          
                      }

                      if(count($attrArray) > 0){

                        // check for field expert_image
                        if(in_array("expert_image", $attrArray)){

                          if(isset($requestParams['imagewidth']) ? $requestParams['imagewidth'] : ''){
                            $imageWidth = $requestParams['imagewidth'];
                            Mage::helper("expertrec_recommendation")
                                ->saveConfig('expertrec_image_width',$imageWidth);
                            echo "Image Width updated</br>";
                          }
                          else{
                            Mage::helper("expertrec_recommendation")
                                ->saveConfig('expertrec_image_width','');
                            echo "Not Updating Image Width</br>";
                          }
                          if(isset($requestParams['imageheight']) ? $requestParams['imageheight'] : ''){
                            $imageHeight = $requestParams['imageheight'];

                            // store image width and height
                            Mage::helper("expertrec_recommendation")
                              ->saveConfig('expertrec_image_height',$imageHeight);
                            echo "Image Height updated</br>";
                          }
                          else{
                            Mage::helper("expertrec_recommendation")
                                ->saveConfig('expertrec_image_height','');
                            echo "Not Updating Image Height</br>";
                          }

                        }

                          //store headers then clear cache
                          Mage::helper("expertrec_recommendation")
                                  ->saveConfig('headers',implode(',', $attrArray))
                                  ->clearCache();
                                  
                          die("Successfully updated selected headers. Please close this tab and reload the info page.");
                      }
                  }        
              }
              die("Invalid request");

          }catch(Exception $e){
              Mage::getSingleton('expertrec_recommendation/log')->log("Updating feed header error: ".$e->getMessage());
              die("Unable to update headers");
          }
      }

      public function savefeedconfAction(){
          try{
              $requestParams = Mage::app()->getRequest()->getParams();
              $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';

              // Check password. if invalid password, it will not proceed.
              if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
                  die('ERROR: The specified password is invalid.');
              }

              $feedConfArray = array();
              if(isset($requestParams['feed_conf'])){
                  if(!empty($requestParams['log_api'])){
                      $feedConfArray[self::FEED_LOG_ENDPOINT] = $requestParams['log_api'];
                  }

                  if(!empty($requestParams['upload_api'])){
                      $feedConfArray[self::FEED_UPLOAD_ENDPOINT] = $requestParams['upload_api'];
                  }

                  if(!empty($requestParams['upload_feed'])){
                    $feedConfArray["is_upload"] = "true";
                  }else{
                    $feedConfArray["is_upload"] = "false";
                  }

                  foreach ($feedConfArray as $sKey => $sValue) {
                      //storing search config
                      Mage::helper("expertrec_recommendation")
                              ->saveConfig($sKey,$sValue);
                  }

                  //clear cache
                  Mage::helper("expertrec_recommendation")
                          ->clearCache();
                                      
                  die("Successfully updated feed config. Please close this tab and reload the info page.");
              }

            die("Invalid request");

          }catch(Exception $e){
              Mage::getSingleton('expertrec_recommendation/log')->log("Updating feed config error: ".$e->getMessage());
              die("Unable to update feed config");
          }
      }

      public function savesearchAction(){
          try{
              $requestParams = Mage::app()->getRequest()->getParams();
              $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';

              // Check password. if invalid password, it will not proceed.
              if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
                  die('ERROR: The specified password is invalid.');
              }
      
              $searchConfArray = array();

              if(isset($requestParams['selected_search'])){

                  if(!empty($requestParams['api'])){
                      $searchConfArray[self::SEARCH_LIST_API] = $requestParams['api'];
                  }
                  
                  $searchConfArray[self::SEARCH_FACET_LIST] = isset($requestParams['facet_list']) ? $requestParams['facet_list'] : '';
                  
                  $searchConfArray[self::SEARCH_SINGLE_SELECT_FILTERS] = isset($requestParams['single_select_filter']) ? $requestParams['single_select_filter'] : '';
                  
                  $searchConfArray[self::SEARCH_ITEMS_PER_PAGE] = isset($requestParams['items_per_page']) ? $requestParams['items_per_page'] : '';
                  
                  $searchConfArray[self::SEARCH_DISPLAY_PAGES] = isset($requestParams['display_pages']) ? $requestParams['display_pages'] : '';
                  
                  $searchCheckedList = isset($requestParams['search_check_list']) ? $requestParams['search_check_list'] : array();

                  $searchConfArray[self::SEARCH_LIST_ENABLE] = in_array("search_enable", $searchCheckedList) ? "true" : "false";
                  $searchConfArray[self::SEARCH_FETCH_PRICE] = in_array("fetch_price", $searchCheckedList) ? "true" : "false";
                  $searchConfArray[self::SEARCH_CONVERT_PRICE] = in_array("convert_price", $searchCheckedList) ? "true" : "false";
                  $searchConfArray[self::SEARCH_IS_AJAX] = in_array("is_ajax", $searchCheckedList) ? "true" : "false";
                  $searchConfArray[self::SEARCH_CUSTOM_TEMPLATE] = in_array("custom_template", $searchCheckedList) ? "true" : "false";
                  

                  foreach ($searchConfArray as $sKey => $sValue) {
                      //storing search config
                      Mage::helper("expertrec_recommendation")
                              ->saveConfig($sKey,$sValue);
                  }

                  //clear cache
                  Mage::helper("expertrec_recommendation")
                          ->clearCache();
                                      
                  die("Successfully updated search config. Please close this tab and reload the info page.");
              }

              die("Invalid request");

          }catch(Exception $e){
              Mage::getSingleton('expertrec_recommendation/log')->log("Updating search config error: ".$e->getMessage());
              die("Unable to update search config");
          }
      }

}
?>

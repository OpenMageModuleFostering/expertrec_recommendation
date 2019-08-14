<?php

class Expertrec_Recommendation_ApiController extends Mage_Core_Controller_Front_Action {

      const CONFIG_HEADERS  = 'expertrec/general/headers';
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
      const IS_UPLOAD_FEED = 'is_upload';
      const IMAGE_WIDTH = 'expertrec/general/expertrec_image_width';
      const IMAGE_HEIGHT = 'expertrec/general/expertrec_image_height';

      const BUILD_NO = "1484913773";
      private $_password;

      // return websites and stores information
      public function infoAction(){

          //return array of all parameters sent
          $requestParams = Mage::app()->getRequest()->getParams(); 
          
          $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';
          
          // Check password. if invalid password, it will not proceed.
          if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
            die('ERROR: The specified password is invalid.');
          }
          
         $this->_password = Mage::getModel('expertrec_recommendation/validate')->getPassword();

          // Set character set to UTF-8
          header("Content-Type: text/html; charset=UTF-8");
?>
          <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
          <html xmlns="http://www.w3.org/1999/xhtml">
          <head>
              <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
              <title>Expertrec Feed Collection Api</title>
              <link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
              <style type="text/css">
                  table {border-spacing: 0;border-collapse: collapse;width: 100%;}
                  .table-hover > tbody > tr:hover {background-color: #f5f5f5;}
                  tbody td, thead th{padding: 8px;line-height: 1.42857143;vertical-align: top;border-top: 1px solid #ddd;text-align:left;}
                  thead:first-child > tr:first-child > th {border-top: 0;}
                  thead th {vertical-align: bottom;border-bottom:2px solid #ddd;background-color:#f5f5f5;text-align:center;}
                  fieldset { display: block;margin:10px 2px;padding: .35em .625em .75em; border: 1px solid silver; }
                  legend{padding: 0px 5px; width: auto; border: 0px none;margin:0;}
                  fieldset p { text-align: left; display: block; }
                  #searchConfSection input[type="checkbox"]{bottom: 2px;}
              </style>
          </head>
          <body>
          <div class="container-fluid">
<?php 
              $version = (string)Mage::getConfig()->getNode('modules/Expertrec_Recommendation/version');
              echo "<h4 style='margin:20px auto 10px;'>Extension Version: <span style='color:red;'>".$version."</span></h4>";
              echo "<h4 style='margin:10px auto;'>Extension Build No.: <span style='color:red;'>".self::BUILD_NO."</span></h4>";
              try{
                  echo "<h4 style='margin:10px auto;'>Magento Version: <span style='color:red;'>".Mage::getVersion()."</span></h4>"; 
              }catch(Exception $em){};
?>            
              <ul class="nav nav-tabs" style="margin-top:20px;">
                  <li class="active"><a data-toggle="tab" href="#apiSection">Api</a></li>
                  <li><a data-toggle="tab" href="#feedHeader">Feed Header</a></li>
                  <li><a data-toggle="tab" href="#feedEndpointSection">Feed Config</a></li>
                  <li><a data-toggle="tab" href="#searchConfSection">Search</a></li>
                  <li><a data-toggle="tab" href="#siteDetailsSection">Details</a></li>
              </ul>
              <div style="clear:both;"></div>
          <div class="tab-content">
            <div id="apiSection" class="tab-pane fade in active">
              <div style="margin-top:20px;">
                <fieldset>
                  <legend>Getting Feeds Api</legend>
                  <table class="table-hover" style="margin: 1em auto;">
                    <thead>
                        <tr>
                          <th>Website ID</th>
                          <th>Website Name</th>
                          <th>Store ID</th>
                          <th>Store Name</th>
                          <th>Total Pages</th>
                          <th>Url</th>
                        </tr>
                    </thead>
                    <tbody>
<?php 
     
                        //site url
                        $baseUrl=Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
                        $feedFilter = Mage::getSingleton('expertrec_recommendation/feed_feedfilter');

                        // List all website-stores
                        $websiteCollection = Mage::getModel('core/website')->getCollection()->load();
                        foreach ($websiteCollection as $website){
                            $wid=$website->getWebsiteId();
                            foreach ($website->getGroups() as $group) {
                                $stores = $group->getStores();
                                foreach ($stores as $oStore) {
                                    $sid=$oStore->getId();
                                    $apiUrl=$baseUrl.'index.php/expertrec-feed?secret='.$this->_password.'&cmd=export&wid='.$wid.'&sid='.$sid;
                                    // Display the store-website details with feed api
                                    echo '<tr>';
                                    echo '<td style="text-align:center;">'.$wid.'</td>';
                                    echo '<td style="text-align:center;">'.$website->getName().'</td>';
                                    echo '<td style="text-align:center;">'.$sid.'</td>';
                                    echo '<td style="text-align:center;">'.$oStore->getName().'</td>';
                                    try{
                                        $collection=$feedFilter->addBasicFilter($website,$oStore)
                                                               ->setPageSize(500);
                                        $pages = $collection->getLastPageNumber();
                                        echo '<td style="text-align:center;">'.(string)$pages.'</td>';

                                    }catch(Exception $e){
                                        echo '<td style="text-align:center;"><b style="color:red;">Error: </b>'.$e->getMessage().'</td>';
                                    }
                                    echo '<td><a href="'.$apiUrl.'">'.$apiUrl.'</a></td>';
                                    echo '</tr>';
                                }
                            }
                        }

?>
                    </tbody>
                  </table>
                </fieldset>

            </div>
            <p>
              <strong>Note:</strong><br />
              <span>
                1. Each page contains 500 products.<br />
                2. For Getting custom image size, kindly add <b>width</b> and <b>height</b> parameter in the api url.
              </span>
            </p>
            <fieldset>
                <legend>Example</legend>
<?php      
              
              $apiUrlWithCustomConf=$baseUrl.'index.php/expertrec-feed?secret='.$this->_password.'&cmd=export&wid=1&sid=1';

              echo '<p><b>With Custom image size</b><br />';
              echo '<p><a href="'.$apiUrlWithCustomConf.'&width=170&height=170">'.$apiUrlWithCustomConf.'&width=170&height=170</a></p>';

              echo '<p><b>With pagination without page size(default page size is 500)</b><br />'; 
              echo '<p><a href="'.$apiUrlWithCustomConf.'&ps=1&pe=2">'.$apiUrlWithCustomConf.'&ps=1&pe=2</a></p>';

              echo '<p><b>With pagination & page size</b><br />'; 
              echo '<p><a href="'.$apiUrlWithCustomConf.'&ps=1&pe=2&psize=50">'.$apiUrlWithCustomConf.'&ps=1&pe=2&psize=50</a></p>';
            echo '</fieldset>';

            echo $this->displaySuggestionApi($baseUrl);
            echo $this->displayLogApi($baseUrl);  
?>
            
          </div> <!-- api section end -->

          <!-- header section start -->
          <div id="feedHeader" class="tab-pane fade">
            <div style="margin-top:20px">
<?php
              $allAttribute = $this->getAllAttributes();
              //display header form
              echo $this->displayHeaderForm($baseUrl,$allAttribute);
?>
            </div>
          </div> <!-- header section end -->

          <!-- Feed api configuration section start -->
          <div id="feedEndpointSection" class="tab-pane fade">
            <div style="margin-top:20px">
              <?php echo $this->displayFeedConf($baseUrl); ?>
            </div>
          </div>
          <!-- Feed api configuration section end -->

          <!-- search section start -->
          <div id="searchConfSection" class="tab-pane fade">
            <div style="margin-top:20px">
              <?php echo $this->displaySearchConf($baseUrl); ?>
            </div>
          </div>

          <!-- site details -->
          <div id="siteDetailsSection" class="tab-pane fade">
            <div style="margin-top:20px">
              <?php echo $this->displaySiteDetails(); ?>
            </div>
          </div>
        </div> <!-- tab-content end-->
        </div> <!-- container-fluid end-->
        <!-- script section-->
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

        </body>
        </html>
<?php 
          
      }

      private function displayLogApi($baseUrl){
          $result = '<div style="margin-top:20px">';
          $result .= '<fieldset>';
          $result .= '<legend>Getting log & Cleaning Expertrec Directory Api\'s</legend>';

          $logUrl = $baseUrl.'index.php/expertrec-feed/index/getlog?secret='.$this->_password;
          $cleanDirUrl = $baseUrl.'index.php/expertrec-feed/index/clean?secret='.$this->_password;

          $result .= '<p><b>Get log url</b><br />';
          $result .= '<p><a href="'.$logUrl.'">'.$logUrl.'</a></p>';
          $result .= '<p><b>Clean directory url</b><br />';
          $result .= '<p><a href="'.$cleanDirUrl.'" target="_blank">'.$cleanDirUrl.'</a></p>';

          $result .= '</fieldset>';
          $result .= '</div>';

          return $result;

      }

      private function displaySuggestionApi($baseUrl){
          $result = '<div style="margin-top:20px">';
          $result .= '<fieldset>';
          $result .= '<legend>Getting Popular products Api</legend>';

          $apiUrlWithCustomConf=$baseUrl.'index.php/expertrec-feed?secret='.$this->_password.'&cmd=getpp&wid=1&sid=1';

          $result .= '<p><b>Without pagination</b><br />'; 
          $result .= '<p><a href="'.$apiUrlWithCustomConf.'">'.$apiUrlWithCustomConf.'</a></p>';

          $result .= '<p><b>With pagination without page size(default page size is 500)</b><br />'; 
          $result .= '<p><a href="'.$apiUrlWithCustomConf.'&ps=1&pe=2">'.$apiUrlWithCustomConf.'&ps=1&pe=2</a></p>';

          $result .= '<p><b>With pagination & page size</b><br />'; 
          $result .= '<p><a href="'.$apiUrlWithCustomConf.'&ps=1&pe=2&psize=50">'.$apiUrlWithCustomConf.'&ps=1&pe=2&psize=50</a></p>';

          $result .= '</fieldset>';
          $result .= '</div>';

          return $result;
      }

      private function getAllAttributes(){
          $attrArray = array('qty','is_in_stock','expert_image','expert_smallImage','expert_thumbnail','expert_category','expert_url','final_price','entity_id','attribute_set_id','type_id','entity_type_id','rating_summary');
          $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
          
          foreach ($attributes as $attribute) {
              $attrCode = $attribute->getAttributeCode();
              if (!in_array($attrCode, $attrArray)) { 
                  $attrArray[] = $attrCode;
              } 
              
          }
          return $attrArray;
      }
      
      private function displayHeaderForm($baseUrl,$attributes){
          $saveHeaderUrl = $baseUrl."index.php/expertrec-feed/config/saveheaders?secret=".$this->_password;

          $result = '<style type="text/css"> .setHeadersForm label{padding:0px 10px 10px 0px;} .setHeadersForm input{vertical-align: middle;} </style>';

          $result .= '<script type="text/javascript">';
          $result .= 'function toggle_select(){var checkboxes = document.getElementsByName("check_list[]"); var button = document.getElementById("toggleSelect"); if(button.value == "Select All"){for (var i in checkboxes){checkboxes[i].checked = "checked"; } button.value = "Deselect All"}else{for (var i in checkboxes){checkboxes[i].checked = ""; } button.value = "Select All";}}'; 
          $result .= '</script>';

          $storedHeaders = Mage::getStoreConfig(self::CONFIG_HEADERS);
          if (isset($storedHeaders)){
              $storedHeadersArray = explode(',', $storedHeaders);
          }
          
          $result .= '<fieldset>';
          $result .= '<legend>Configure Feed Headers</legend>';

          $result .=  '<form class="setHeadersForm" action="'.$saveHeaderUrl.'"  method="post" role="form" target="_blank">';
          
          foreach ($attributes as $attr) { 
              if (isset($storedHeadersArray) && in_array($attr, $storedHeadersArray)){
                  $result .= '<input type="checkbox" id="'.$attr.'" name="check_list[]" value="'.$attr.'" checked>';
              }else{
                  $result .= '<input type="checkbox" id="'.$attr.'" name="check_list[]" value="'.$attr.'">';
              }
              $result .= '<label for="'.$attr.'">'.$attr.'</label>';
          }

          // adding image width and height
          $result .= '<div style="display:block"><label for="imagewidth">Image Width</label><input type="text" id="imagewidth" name="imagewidth" placeholder="Give image width" value="'.Mage::getStoreConfig(self::IMAGE_WIDTH).'"></div>';
          $result .= '<div style="display:block"><label for="imageheight">Image Height</label><input type="text" id="imageheight" name="imageheight" placeholder="Give image height" value="'.Mage::getStoreConfig(self::IMAGE_HEIGHT).'"></div>';

          $result .= '<div style="text-align:center;margin:10px auto;"> ';
          $result .= '<input type="button" class="btn btn-md btn-primary" id="toggleSelect" value="Select All" onClick="toggle_select()" style="padding:5px; margin: 5px;"/>';
          $result .= '<input type="submit" class="btn btn-md btn-primary" name="selected_headers" value="Submit" style= "padding:5px; margin: 5px;"/>';
          $result .= '</div>';
          $result .= '</form>';
          $result .= '</fieldset>';

          $result .= '<fieldset style="margin-top:20px;">';
          $result .= '<legend>Selected Headers</legend>';
          $result .= '<div class="setHeadersForm">';
                 
          if (isset($storedHeadersArray) && count($storedHeadersArray) > 0){
             foreach ($storedHeadersArray as $header) { 
                  $result .= '<input type="checkbox" name="'.$header.'" value="'.$header.'" disabled checked>';
                  $result .= '<label>'.$header.'</label>';
              }
          }

          $result .= '</div>';  
          $result .= '</fieldset>';

          return $result;
      }

      private function displaySearchConf($baseUrl){
          $saveSearchUrl = $baseUrl."index.php/expertrec-feed/config/savesearch?secret=".$this->_password;
          $result = '<fieldset>';
          $result .= '<legend>Configure Search</legend>';
          $result .= '<form class="form-horizontal" action="'.$saveSearchUrl.'"  method="post" role="form" target="_blank">';

          $textArray = array("api"=>"Search endpoint", "facet_list"=>"Facet list comma separated", "single_select_filter"=>"Single select filters comma separated", "items_per_page"=>"No of items per page","display_pages"=>"No. of pages to display");

          $textToStoreKeyMapArray = array("api"=>self::SEARCH_LIST_API,"facet_list"=>self::SEARCH_FACET_LIST,"single_select_filter"=>self::SEARCH_SINGLE_SELECT_FILTERS,"items_per_page"=>self::SEARCH_ITEMS_PER_PAGE,"display_pages"=>self::SEARCH_DISPLAY_PAGES);

          $chekboxArray = array("search_enable"=>self::SEARCH_LIST_ENABLE,"fetch_price"=>self::SEARCH_FETCH_PRICE,"convert_price"=>self::SEARCH_CONVERT_PRICE,"is_ajax"=>self::SEARCH_IS_AJAX,"custom_template"=>self::SEARCH_CUSTOM_TEMPLATE);

          // input
          foreach ($textArray as $tKey => $tValue) {
            $label = ucwords(str_replace("_", " ", $tKey));
            $storeValue = Mage::helper('expertrec_recommendation')->getConfig($textToStoreKeyMapArray[$tKey]);

            $result .= '<div class="form-group">';
            $result .= '<label class="control-label col-sm-2" for="search_'.$tKey.'">'.$label.':</label>';
            $result .= '<div class="col-sm-8">';
            if(isset($storeValue)){
              $result .= '<input type="text" class="form-control" name="'.$tKey.'" value="'.$storeValue.'" id="search_'.$tKey.'" placeholder="'.$tValue.'">';
            }else{
              $result .= '<input type="text" class="form-control" name="'.$tKey.'" id="search_'.$tKey.'" placeholder="'.$tValue.'">';
            }
            $result .= '</div>';
            $result .= '</div>';
          }
          
          //checkbox
          $result .= '<div class="form-group">';
          $result .= '<div class="col-sm-2"></div>';
          $result .= '<div class="col-sm-8">';
          foreach ($chekboxArray as $cKey => $cValue) {
            $label = ucwords(str_replace("_", " ", $cKey));
            $storeValue = Mage::helper('expertrec_recommendation')->getConfig($cValue);
            $checked = isset($storeValue) && $storeValue == "true" ? "checked" :'';

            $result .= '<div class="col-sm-2">';
            $result .= '<div class="checkbox">';
            $result .= '<label><input type="checkbox" name="search_check_list[]" value="'.$cKey.'" '.$checked.'>'.$label.'</label>';
            $result .= '</div>';
            $result .= '</div>';
            
          }
          $result .= '</div>';
          $result .= '</div>';
          $result .= '<div style="text-align:center;margin:10px auto;">'; 
          $result .= '<input type="submit" class="btn btn-md btn-primary" name="selected_search" value="Submit" style="padding:5px; "/>';
          $result .= '</div>';
          $result .= '</form>';
          $result .= '</fieldset>';

          return $result;

      }

      private function displayFeedConf($baseUrl){
          $saveFeedApiUrl = $baseUrl."index.php/expertrec-feed/config/savefeedconf?secret=".$this->_password;
          $result = '<fieldset>';
          $result .= '<legend>Configure Search</legend>';
          $result .= '<form class="form-horizontal" action="'.$saveFeedApiUrl.'"  method="post" role="form" target="_blank">';

          $textArray = array("log_api"=>"Feed log endpoint", "upload_api"=>"Feed upload endpoint");
          $textToStoreKeyMapArray = array("log_api"=>self::FEED_LOG_ENDPOINT,"upload_api"=>self::FEED_UPLOAD_ENDPOINT);

          foreach ($textArray as $tKey => $tValue) {
            $label = ucwords(str_replace("_", " ", $tKey));
            $storeValue = Mage::helper('expertrec_recommendation')->getConfig($textToStoreKeyMapArray[$tKey]);

            $result .= '<div class="form-group">';
            $result .= '<label class="control-label col-sm-2" for="search_'.$tKey.'">'.$label.':</label>';
            $result .= '<div class="col-sm-8">';

            if(isset($storeValue)){
              $result .= '<input type="text" class="form-control" name="'.$tKey.'" value="'.$storeValue.'" placeholder="'.$tValue.'">';
            }else{
              $result .= '<input type="text" class="form-control" name="'.$tKey.'" placeholder="'.$tValue.'">';
            }
            $result .= '</div>';
            $result .= '</div>';
          }

          //enable/disable feed upload
          $isUpload = Mage::helper('expertrec_recommendation')->getConfig(self::IS_UPLOAD_FEED);
          $result .= '<div class="row">';
          $result .='<div class="col-sm-offset-2 col-sm-8" style="clear:both;">';
          $result .= '<label class="checkbox-inline" style="padding-left: 20px;">';
          if(isset($isUpload) && $isUpload == 'true'){
            $result .= '<input type="checkbox" name="upload_feed" value="is_upload" checked>';
          }else{
            $result .= '<input type="checkbox" name="upload_feed" value="is_upload">';
          }
          $result .= 'Upload Feed';
          $result .= '</label>';
          $result .= '</div>';
          $result .= '</div>';

          $result .= '<div style="text-align:center;margin:10px auto;">'; 
          $result .= '<input type="submit" class="btn btn-md btn-primary" name="feed_conf" value="Submit" style="padding:5px; "/>';
          $result .= '</div>';

          $result .= '</form>';
          $result .= '</fieldset>';

          return $result;
      }

      private function displaySiteDetails(){
          $result = '<fieldset>';
          $result .= '<legend>Site Info</legend>';
          $result .= '<table class="table table-hover">';
          $result .= '<tbody>';
          try{
            $adminEmail = Mage::getStoreConfig('trans_email/ident_general/email');
            $adminName = Mage::getStoreConfig('trans_email/ident_general/name');
            $salesEmail = Mage::getStoreConfig('trans_email/ident_sales/email');
            $salesName = Mage::getStoreConfig('trans_email/ident_sales/name');
            $secureUrl = Mage::getStoreConfig('web/secure/base_url');
            $unsecureUrl = Mage::getStoreConfig('web/unsecure/base_url');
            $baseCurrency = Mage::getStoreConfig('currency/options/base');
            $defaultCurrency = Mage::getStoreConfig('currency/options/default');
            $allowCurrency = Mage::getStoreConfig('currency/options/allow');
            $timezone = Mage::getStoreConfig('general/locale/timezone');
            $defaultCountry = Mage::getStoreConfig('general/country/default');
            $installedCurrency = Mage::getStoreConfig('system/currency/installed');
          }catch(Exception $e){}

          if(isset($adminName)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Admin name: </strong></td>';
              $result .= '<td>'.$adminName.'</td>';
              $result .= '</tr>';
          }
          if(isset($adminEmail)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Admin email: </strong></td>';
              $result .= '<td>'.$adminEmail.'</td>';
              $result .= '</tr>';
          }
          if(isset($salesName)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Sales name: </strong></td>';
              $result .= '<td>'.$salesName.'</td>';
              $result .= '</tr>';
          }
          if(isset($salesEmail)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Sales email: </strong></td>';
              $result .= '<td>'.$salesEmail.'</td>';
              $result .= '</tr>';
          }
          if(isset($secureUrl)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Web secure base url: </strong></td>';
              $result .= '<td>'.$secureUrl.'</td>';
              $result .= '</tr>';
          }
          if(isset($unsecureUrl)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Web unsecure base url: </strong></td>';
              $result .= '<td>'.$unsecureUrl.'</td>';
              $result .= '</tr>';
          }
          if(isset($baseCurrency)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Base currency: </strong></td>';
              $result .= '<td>'.$baseCurrency.'</td>';
              $result .= '</tr>';
          }
          if(isset($defaultCurrency)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Default currency: </strong></td>';
              $result .= '<td>'.$defaultCurrency.'</td>';
              $result .= '</tr>';
          }
          if(isset($allowCurrency)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Allow currencies: </strong></td>';
              $result .= '<td>'.str_replace(",", ", ",$allowCurrency).'</td>';
              $result .= '</tr>';
          }
          if(isset($defaultCountry)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Default Country: </strong></td>';
              $result .= '<td>'.$defaultCountry.'</td>';
              $result .= '</tr>';
          }
          if(isset($timezone)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Timezone: </strong></td>';
              $result .= '<td>'.$timezone.'</td>';
              $result .= '</tr>';
          }
          if(isset($installedCurrency)){
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Installed currencies: </strong></td>';
              $result .= '<td>'.str_replace(",", ", ",$installedCurrency).'</td>';
              $result .= '</tr>';
          }

          try{
              $modulesArray = (array)Mage::getConfig()->getNode('modules')->children();
              $modulesNameArray = array_keys($modulesArray);

              //displaying list of installed modules
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Installed Extensions: </strong></td>';
              $result .= '<td>'.implode(", ", $modulesNameArray).'</td>';
              $result .= '</tr>';

              //displaying list of active modules
              $activeModules = array();
              $coreHelper = Mage::helper('core');
              foreach ($modulesNameArray as $module) {
                if($coreHelper->isModuleEnabled($module)){
                    $activeModules[] = $module;
                }
              }
              $result .= '<tr>';
              $result .= '<td style="width: 20%;"><strong>Active Extensions: </strong></td>';
              $result .= '<td>'.implode(", ", $activeModules).'</td>';
              $result .= '</tr>';
          }catch(Exception $e){$result .= '<tr>';
              $result .= '<tr><td>Error</td><td style="color:red;">'.$e->getMessage().'</td></tr>';
          }
          

          $result .= '</tbody>';
          $result .= '</table>';
          $result .= '</fieldset>';
          return $result;
      }

}
?>

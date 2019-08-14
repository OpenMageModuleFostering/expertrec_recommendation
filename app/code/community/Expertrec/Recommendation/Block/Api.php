<?php

  class Expertrec_Recommendation_Block_Api extends Mage_Core_Block_Template{

    const CONFIG_HEADERS  = 'expertrec/general/headers';
    const CONFIG_FILTERS  = 'expertrec/general/filters';
    const MERCHANT_ID  = 'expertrec/general/mid';
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
    const FEED_LOG_ENDPOINT = 'expertrec/general/log_endpoint';
    const FEED_UPLOAD_ENDPOINT = 'expertrec/general/upload_endpoint';
    const IS_UPLOAD_FEED = 'expertrec/general/is_upload';
    const IMAGE_WIDTH = 'expertrec/general/expertrec_image_width';
    const IMAGE_HEIGHT = 'expertrec/general/expertrec_image_height';
    const THUMBNAIL_WIDTH = 'expertrec/general/expertrec_thumbnail_width';
    const THUMBNAIL_HEIGHT = 'expertrec/general/expertrec_thumbnail_height';      

    /**
    * A custom method use to return a simple string.
    * @return string
    */
    public function getText(){
      $version = (string)Mage::getConfig()->getNode('modules/Expertrec_Recommendation/version');
      return "I am inside `" . $version. "` block and it is my first custom block ever !";
    }

    // Fetching website domain URL
    public function getBaseUrl(){
      $baseUrl=Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
      return $baseUrl;
    }

    // Fetching merchant ID
    public function getMerchantID(){
      $mid = "";
      $mid=Mage::getStoreConfig(self::MERCHANT_ID);
      return $mid;
    }

    // Fetching secret passed in url request parameters
    public function getSecret(){
      $customerSecret = Mage::registry('secret');
      return $customerSecret;
    }

    public function getLogUrl(){
      $logUrl['surl'] = $this->getBaseUrl().'index.php/expertrec-feed/index/getlog';
      $logUrl['link'] =  $this->getBaseUrl().'index.php/expertrec-feed/index/getlog?secret='.$this->getSecret();
      $logUrl['secret'] = $this->getSecret();
      return $logUrl;
    }

    public function getCleanDirUrl(){
      $cleanDirUrl['surl'] = $this->getBaseUrl().'index.php/expertrec-feed/index/clean';  	
      $cleanDirUrl['link'] = $this->getBaseUrl().'index.php/expertrec-feed/index/clean?secret='.$this->getSecret();
      $cleanDirUrl['secret'] = $this->getSecret();
      return $cleanDirUrl;
    }

    // PullFeed 
    public function pullFeed(){
      $pullFeed['surl'] = $this->getBaseUrl().'index.php/expertrec-feed/api/pullFeed';
      $pullFeed['secret'] = $this->getSecret();
      $pullFeed['link'] = $this->getBaseUrl().'index.php/expertrec-feed/api/pullFeed?secret='.$this->getSecret();
      return $pullFeed;
    }

    public function getCustomApiUrl(){
      $apiUrlWithCustomConf['surl']=$this->getBaseUrl().'index.php/expertrec-feed';
      $apiUrlWithCustomConf['link']=$this->getBaseUrl().'index.php/expertrec-feed?secret='.$this->getSecret().'&cmd=export&wid=1&sid=1';
      $apiUrlWithCustomConf['secret']=$this->getSecret();
      $apiUrlWithCustomConf['cmd']="export";
      $apiUrlWithCustomConf['wid']=1;
      $apiUrlWithCustomConf['sid']=1;
      return $apiUrlWithCustomConf;
    }

    public function getSuggestionApiUrl(){
      $apiUrlWithCustomConf['surl']=$this->getBaseUrl().'index.php/expertrec-feed';
      $apiUrlWithCustomConf['link']=$this->getBaseUrl().'index.php/expertrec-feed?secret='.$this->getSecret().'&cmd=getpp&wid=1&sid=1';
      $apiUrlWithCustomConf['secret']=$this->getSecret();
      $apiUrlWithCustomConf['cmd']="getpp";
      $apiUrlWithCustomConf['wid']=1;
      $apiUrlWithCustomConf['sid']=1;
      return $apiUrlWithCustomConf;
    }

    // Fetching image width and height 
    public function getImageParams(){
      $keys = array('imgwidth', 'imgheight','thumbwidth', 'thumbwidth');
      $imageParamsArray = array_fill_keys($keys, "");
      $imageWidth = Mage::getStoreConfig(self::IMAGE_WIDTH);
      $imageHeight = Mage::getStoreConfig(self::IMAGE_HEIGHT);
      $thumbWidth = Mage::getStoreConfig(self::THUMBNAIL_WIDTH);
      $thumbHeight = Mage::getStoreConfig(self::THUMBNAIL_HEIGHT);

      // Checking for non-empty and non-null image width and height
      if (isset($imageWidth) && $imageWidth != ""){
        $imageParamsArray["imgwidth"] = $imageWidth;
      }
      if (isset($imageHeight) && $imageHeight != ""){
        $imageParamsArray["imgheight"] = $imageHeight;
      }
      if (isset($thumbWidth) && $thumbWidth != ""){
        $imageParamsArray["thumbwidth"] = $thumbWidth;
      }
      if (isset($thumbHeight) && $thumbHeight != ""){
        $imageParamsArray["thumbheight"] = $thumbHeight;
      }
      return $imageParamsArray;
    }

    // Fetching website store info
    public function getWebsiteStoreInfo(){
      $feedFilter = Mage::getSingleton('expertrec_recommendation/feed_feedfilter');
      $baseUrl=Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
      $websiteStoreData = array();
      // List all website-stores
      $websiteCollection = Mage::getModel('core/website')->getCollection()->load();
      foreach ($websiteCollection as $website){
        $wid=$website->getWebsiteId();
        foreach ($website->getGroups() as $group) {
          $stores = $group->getStores();
          foreach ($stores as $oStore) {
            $sid=$oStore->getId();
            $websiteStoreRow = array();
            $apiUrl=Mage::app()->getStore($sid)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'index.php/expertrec-feed';
            $url = Mage::app()->getStore($sid)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'index.php/expertrec-feed?secret='.$this->getSecret().'&cmd=export&wid='.$wid.'&sid='.$sid;
            // Display the store-website details with feed api
            $websiteStoreRow["wid"] = $wid;
            $websiteStoreRow["wname"] = $website->getName();
            $websiteStoreRow["sid"] = $sid;
            $websiteStoreRow["sname"] = $oStore->getName();
            $websiteStoreRow["surl"] = $apiUrl;
            $websiteStoreRow["secret"] = $this->getSecret();
            $websiteStoreRow["cmd"] = "export";
            $websiteStoreRow['link'] = $url;
            try{
              $websiteStoreRow["pcount"] = Mage::helper('expertrec_recommendation')->getProductCount($wid,$sid);
            }catch(Exception $e){
              $websiteStoreRow["pcounterr"] = $e.getMessage();
            }
            try{
              $filteredCollection = $feedFilter->addBasicFilter($website,$oStore);
              $websiteStoreRow["fcount"] = $filteredCollection->getSize();
            }catch(Exception $e){
              $websiteStoreRow["fcounterr"] = $e.getMessage();
            }
            array_push($websiteStoreData,$websiteStoreRow);                    
          }
        }
      } 
      return $websiteStoreData;
    }

    /*
    * Feed config functions
    */
    public function isFeedUpload(){
      $isUpload = Mage::helper('expertrec_recommendation')->getConfig(self::IS_UPLOAD_FEED);
      return $isUpload;
    }

    public function getFeedConfUrl(){
      $urlArray = array();
      $textArray = array("log_api"=>"Feed log endpoint", "upload_api"=>"Feed upload endpoint");
      $textToStoreKeyMapArray = array("log_api"=>self::FEED_LOG_ENDPOINT,"upload_api"=>self::FEED_UPLOAD_ENDPOINT);   
      foreach ($textArray as $tKey => $tValue) {
        $storeValue = Mage::helper('expertrec_recommendation')->getConfig($textToStoreKeyMapArray[$tKey]);
        $label = ucwords(str_replace("_", " ", $tKey));
        $urlRow = array();      
        $urlRow["label"] = $label;
        $urlRow["tkey"] = $tKey;
        $urlRow["storevalue"] = $storeValue;
        $urlRow["placeholder"] = $tValue; 
        array_push($urlArray,$urlRow);  
      }
      return $urlArray;
    }

    /*
    * Search config functions
    */
    public function getSearchCheckedValues(){
      $searchCheckArray = array();
      $chekboxArray = array(
      "search_enable"=>self::SEARCH_LIST_ENABLE,
      "fetch_price"=>self::SEARCH_FETCH_PRICE,
      "convert_price"=>self::SEARCH_CONVERT_PRICE,
      "is_ajax"=>self::SEARCH_IS_AJAX,
      "custom_template"=>self::SEARCH_CUSTOM_TEMPLATE);
      foreach ($chekboxArray as $cKey => $cValue)
      {      
        $checkRow = array();
        $storeValue = Mage::helper('expertrec_recommendation')->getConfig($cValue);
        $checkRow['label'] = ucwords(str_replace("_", " ", $cKey));
        $checkRow['val'] = $cKey;
        $checkRow['checked'] = isset($storeValue) && $storeValue == "true" ? "checked" :'';
        array_push($searchCheckArray,$checkRow);           
      }
      return $searchCheckArray;
    }

    public function getSearchFields(){
      $searchFieldArray = array();
      $textArray = array(
        "api"=>"Search endpoint", 
        "facet_list"=>"Facet list comma separated",
        "single_select_filter"=>"Single select filters comma separated", 
        "items_per_page"=>"No of items per page",
        "display_pages"=>"No. of pages to display"
      );
      $textToStoreKeyMapArray = array(
        "api"=>self::SEARCH_LIST_API,
        "facet_list"=>self::SEARCH_FACET_LIST,
        "single_select_filter"=>self::SEARCH_SINGLE_SELECT_FILTERS,
        "items_per_page"=>self::SEARCH_ITEMS_PER_PAGE,
        "display_pages"=>self::SEARCH_DISPLAY_PAGES
        );
      // Input
      foreach ($textArray as $tKey => $tValue) { 
        $searchFieldRow = array();
        $label = ucwords(str_replace("_", " ", $tKey));
        $storeValue = Mage::helper('expertrec_recommendation')->getConfig($textToStoreKeyMapArray[$tKey]);
        $searchFieldRow["tkey"] = $tKey;
        $searchFieldRow["label"] = $label;
        $searchFieldRow["storevalue"] = $storeValue;
        $searchFieldRow["placeholder"] = $tValue; 
        array_push($searchFieldArray,$searchFieldRow);
      }
      return $searchFieldArray;
    }

    /*
    * Site Details section
    */
    public function getSiteDetails(){
      $siteDetails = array();
      try{
        $siteDetails["adminEmail"] = Mage::getStoreConfig('trans_email/ident_general/email');
        $siteDetails["adminName"] = Mage::getStoreConfig('trans_email/ident_general/name');
        $siteDetails["salesEmail"] = Mage::getStoreConfig('trans_email/ident_sales/email');
        $siteDetails["salesName"] = Mage::getStoreConfig('trans_email/ident_sales/name');
        $siteDetails["secureUrl"] = Mage::getStoreConfig('web/secure/base_url');
        $siteDetails["unsecureUrl"] = Mage::getStoreConfig('web/unsecure/base_url');
        $siteDetails["baseCurrency"] = Mage::getStoreConfig('currency/options/base');
        $siteDetails["defaultCurrency"] = Mage::getStoreConfig('currency/options/default');
        $siteDetails["allowCurrency"] = Mage::getStoreConfig('currency/options/allow');
        $siteDetails["timezone"] = Mage::getStoreConfig('general/locale/timezone');
        $siteDetails["defaultCountry"] = Mage::getStoreConfig('general/country/default');
        $siteDetails["installedCurrency"] = Mage::getStoreConfig('system/currency/installed');

      }
      catch(Exception $e){}
      return $siteDetails;
    }

    public function getInstalledExtensions(){
      $installedModules = array();
      try{
        $modulesArray = (array)Mage::getConfig()->getNode('modules')->children();
        $modulesNameArray = array_keys($modulesArray);
        $installedModules['modulearr'] = $modulesNameArray;
        $installedModules['modulestr'] = implode(', ',$modulesNameArray);
      }
      catch(Exception $e){
        $installedModules['errorstr'] = $e.getMessage();
      }
      return $installedModules;   
    }

    public function getActiveExtensions($installedModules){
      $activeModules = array();
      // Pass only if the installed modules are retrieved successful, an array of modules is needed
      if(isset($installedModules)){
        try{
          $coreHelper = Mage::helper('core');
          $activeModulesArr = array();
          foreach ($installedModules as $module){
            if($coreHelper->isModuleEnabled($module)){
              $activeModulesArr[] = $module;
            }
          }
          $activeModules['modulestr'] = implode(', ',$activeModulesArr);
        }
        catch(Exception $e){
          $activeModules['errorstr'] = $e.getMessage();
        }
      }
      return $activeModules;    
    }

    /*
    * Feed Headers function
    */
    public function getAllAttributes(){
      $attrArray = array(
        'qty',
        'is_in_stock',
        'expert_image',
        'expert_smallImage',
        'expert_thumbnail',
        'expert_category',
        'expert_category_ids',
        'expert_url',
        'final_price',
        'entity_id',
        'attribute_set_id',
        'type_id',
        'entity_type_id',
        'rating_summary'
      );
      $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();

      foreach ($attributes as $attribute) {
        $attrCode = $attribute->getAttributeCode();
        if (!in_array($attrCode, $attrArray)) { 
          $attrArray[] = $attrCode;
        } 
      }
      return $attrArray;
    }

    public function getStoredHeaders(){
      $storedHeadersArray = array();
      $storedHeaders = Mage::getStoreConfig(self::CONFIG_HEADERS);
      if (isset($storedHeaders)) {
        $storedHeadersArray = explode(',', $storedHeaders);
      }
      return $storedHeadersArray;
    }

    /*
    * Get all filters function
    */
    public function getAllFilters(){
      $filterArray = array(
        'filter_by_stock',
        'filter_by_status',
        // 'filter_by_visiblity'
        'not_visible_individually',
        'visible_catalog',
        'visible_search',
        'visible_catalog_search'
      );
      return $filterArray;
    }

    public function getStoredFilters(){
      $storedFiltersArray = array();
      $storedFilters = Mage::getStoreConfig(self::CONFIG_FILTERS);
      if(isset($storedFilters)){
        $storedFiltersArray = explode(',', $storedFilters);
      }
      return $storedFiltersArray;
    }
  }
?>
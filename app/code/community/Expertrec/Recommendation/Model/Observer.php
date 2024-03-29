<?php

/**
 *
 * @category Expertrec
 * @package Expertrec_Recommendation
 * 
 */
class Expertrec_Recommendation_Model_Observer {

    const CONFIG_HEADERS  = 'expertrec/general/headers';
    const FEED_LOG_ENDPOINT = 'expertrec/general/log_endpoint';
    const SEARCH_LIST_ENABLE = 'search/enable';
    const SEARCH_CUSTOM_TEMPLATE = 'search/custom_template';
    const MERCHANT_ID  = 'expertrec/general/mid';
    /**
     * Method to track the add to cart
     */
    public function trackAddToCart(Varien_Event_Observer $observer) {

        $product = $observer->getEvent()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            Mage::getSingleton('expertrec_recommendation/log')->log('AddToCart_Track: product is not a valid type',Zend_Log::ERR);
            return $this;
        }
        //return unique product id
        $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);
        $ci_id = $_COOKIE['ci_id'];
        
        //sending request
        //return boolean
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'ci_id' => $ci_id,
                    'item' => $uniqueId,
                    'event' => 3,
                    'sku' => $product->getSku(),
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])

                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();

        // Mage::getSingleton('expertrec_recommendation/log')->log("AddToCart_Track: request made with uniqueId ".$uniqueId);
        if(!$response) {
            Mage::getSingleton('expertrec_recommendation/log')->log('AddToCart_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
        }
        return $this;
    }

    /**
     * Method to track orders
     */
    public function trackOrder(Varien_Event_Observer $observer) {

        $payment = $observer->getEvent()->getPayment();

        if(!$payment instanceof Mage_Sales_Model_Order_Payment) {
            Mage::getSingleton('expertrec_recommendation/log')->log('Order_Track: payment is not a valid type',Zend_Log::ERR);
            return $this;
        }
        $items = $payment->getOrder()->getAllVisibleItems();

        if(!is_array($items)) {
            return $this;
        }

        foreach($items as $item) {
            if($item instanceof Mage_Sales_Model_Order) {
                Mage::getSingleton('expertrec_recommendation/log')
                    ->log('Order_Track: request failed because item is of instancetype '.get_class($item), Zend_Log::ERR);
                continue;
            }
            $product =$item->getProduct();
            if(!$product instanceof Mage_Catalog_Model_Product) {
                return $this;
            }

            //return unique product id
            $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product, $item);
            $ci_id = $_COOKIE['ci_id'];

            //sending request
            //return boolean
            $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'ci_id' => $ci_id,
                    'item' => $uniqueId,
                    'event' => 2,
                    'sku' => $item->getSku(),
                    'qty' => $item->getQtyOrdered(),
                    'price' => $item->getPriceInclTax(),
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])  
                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();
            
            // Mage::getSingleton('expertrec_recommendation/log')->log("Order_Track: request made with uniqueId ".$uniqueId);

            if(!$response) {
                Mage::getSingleton('expertrec_recommendation/log')->log('Order_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
            }
        }
        return $this;

    }

    /**
     * Method to track remove item from cart
     */
    public function trackRemoveItem(Varien_Event_Observer $observer){
        
        $product = $observer->getQuoteItem()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            Mage::getSingleton('expertrec_recommendation/log')->log('RemoveItemFromCart_Track: product is not a valid type',Zend_Log::ERR);
            return $this;
        }

        //return unique product id
        $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);

        $sku = $product->getSku();

        //sending request
        //return boolean
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'item' => $uniqueId,
                    'event' => 11,
                    'sku' => $sku,
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])
                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();

        //Mage::getSingleton('expertrec_recommendation/log')->log("RemoveItemFromCart_Track: request with uniqueId #".$uniqueId." and sku: ".$sku);
        if(!$response) {
            Mage::getSingleton('expertrec_recommendation/log')->log('RemoveItemFromCart_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
        }
        return $this;
    }

    /**
     * Method to track deleted item from catalog
     */
    public function trackDelete(Varien_Event_Observer $observer) {
        $logger = Mage::getSingleton('expertrec_recommendation/log');
        $product = $observer->getEvent()->getDataObject();

        if (!isset($product) || 
            !$product instanceof Mage_Catalog_Model_Product || 
            !$product->hasData('entity_id'))
        {

            $logger->log('DeleteCatalogProduct_Track: product is not a valid type');
            return $this;
        }

        $finalUrl = $this->getFeedEndpoint();
        if(empty($finalUrl)){
            return $this;
        }

        //sending request
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setUserId('expertrec')
            ->setUrl($finalUrl)
            ->setMethod(Zend_Http_Client::DELETE)
            ->setData(array('item' => $product->getId()))
            ->setPrepareRequestStatus(true)
            ->sendRequest();

        $mid = Mage::getStoreConfig(self::MERCHANT_ID);
        $feedUrl = "https://feed.expertrec.com/magento/n01eba6261ad7f174cd3a16523e86e65/";
        $finalUrl = $feedUrl.''.$mid.'/';

        //sending request
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setUserId('expertrec')
            ->setUrl($finalUrl)
            ->setMethod(Zend_Http_Client::DELETE)
            ->setData(array('item' => $product->getId()))
            ->setPrepareRequestStatus(true)
            ->sendRequest();

        
        if(!$response) {
            $logger->log('DeleteCatalogProduct_Track: request failed for product with Id #'.$product->getId());
        }

        return $this;
    }

    /**
     * Method to track save product
     */
    public function saveProduct(Varien_Event_Observer $observer){
        $logger = Mage::getSingleton('expertrec_recommendation/log');
        $product = $observer->getEvent()->getDataObject();

        if (!isset($product) || 
            !$product instanceof Mage_Catalog_Model_Product || 
            !$product->hasData('entity_id'))
        {
            $logger->log('SaveCatalogProduct_Track: product is not a valid type');
            return $this;
        }

        try{
            $storedHeaders = Mage::getStoreConfig(self::CONFIG_HEADERS);
            if (isset($storedHeaders)){
                $header = explode(',', $storedHeaders);
            }else{
                $header = array();
            }

            if(!empty($header)){
                // passing product to identify product url
                $feedUrl = $this->getFeedEndpoint();
                $finalUrl = $feedUrl.'/product';
                if(empty($finalUrl)){
                    return $this;
                }
                $resultArray = Mage::getSingleton('expertrec_recommendation/feed_formatter')
                                    ->init()
                                    ->prepareRow($header,$product);
                

                //sending request
                $response = Mage::getModel('expertrec_recommendation/api_request')
                    ->setPrepareRequestStatus(false)
                    ->setUserId('expertrec')
                    ->setUrl($finalUrl)
                    ->setMethod(Zend_Http_Client::POST)
                    ->setData($resultArray)
                    ->setHeader("Content-Type",'application/json')
                    ->setPrepareRequestStatus(true)
                    ->sendRequest();

                $mid = Mage::getStoreConfig(self::MERCHANT_ID);
                $feedUrl = "https://feed.expertrec.com/magento/n01eba6261ad7f174cd3a16523e86e65/";
                $finalUrl = $feedUrl.''.$mid.'/product';

                //sending request
                $response = Mage::getModel('expertrec_recommendation/api_request')
                    ->setPrepareRequestStatus(false)
                    ->setUserId('expertrec')
                    ->setUrl($finalUrl)
                    ->setMethod(Zend_Http_Client::POST)
                    ->setData($resultArray)
                    ->setHeader("Content-Type",'application/json')
                    ->setPrepareRequestStatus(true)
                    ->sendRequest();

                
                if(!$response) {
                    $logger->log('SaveCatalogProduct_Track: request failed for product with Id #'.$product->getId());
                }
            }
        }catch (Exception $e) {
            $logger->log("SaveCatalogProduct_Track error: ".$e->getMessage());
        }
        return $this;
    }

    /**
     * Method to track save Category
     */

     public function saveCategory($observer){
        $logger = Mage::getSingleton('expertrec_recommendation/log');
        $category = $observer->getEvent()->getCategory();

        // $logger->log("Hook on category after save");

        // get category url
        $store = Mage::app()->getStore();
        $category_url = $store->getBaseUrl().$category->getUrlPath();

        // get path with name
        $pathIdArray = explode('/', $category->getPath());
        $storeId = $store->getId();
        $pathNameArray = array();

        for($i=0;$i<count($pathIdArray);$i++){
            $categoryy = Mage::getModel('catalog/category')->setStoreId($storeId)->load($pathIdArray[$i]);
            $pathNameArray[$i] = $categoryy->getName();
        }
        //removing Root catalog which is $pathNameArrray[0]
        array_shift($pathNameArray);
        $category_path = implode('/', $pathNameArray);

        array_shift($pathIdArray);
        $category_id_path = implode('/', $pathIdArray);

        $categoryArray = array('categoryId' => $category->getId(),
                               'categoryName' => $category->getName(),
                               'categoryIdPath' => $category_id_path,
                               'categoryNamePath' => $category_path,
                               'categoryUrl' => $category_url);

        // passing category to identify category url
        $feedUrl = $this->getFeedEndpoint();
        $finalUrl = $feedUrl.'/category';
        if(empty($finalUrl)){
            return $this;
        }

        //sending request
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setUserId('expertrec')
            ->setUrl($finalUrl)
            ->setMethod(Zend_Http_Client::POST)
            ->setData($categoryArray)
            ->setHeader("Content-Type",'application/json')
            ->setPrepareRequestStatus(true)
            ->sendRequest();

        $mid = Mage::getStoreConfig(self::MERCHANT_ID);
        $feedUrl = "https://feed.expertrec.com/magento/n01eba6261ad7f174cd3a16523e86e65/";
        $finalUrl = $feedUrl.''.$mid.'/category';
                
        //sending request
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setUserId('expertrec')
            ->setUrl($finalUrl)
            ->setMethod(Zend_Http_Client::POST)
            ->setData($categoryArray)
            ->setHeader("Content-Type",'application/json')
            ->setPrepareRequestStatus(true)
            ->sendRequest();

        if(!$response) {
            $logger->log('request failed for category with Id #'.$category->getId());
        }
        
        return $this;

    }

    // public function productView(Varien_Event_Observer $observer){
    //     $logger = Mage::getSingleton('expertrec_recommendation/log');
    //     $logger->log("product view");
    //     // $lastUrl = Mage::getSingleton('core/session')->getLastUrl();
    //     // $logger->log("view last url ".$lastUrl);
    //     $currUrl = Mage::helper('core/url')->getCurrentUrl();
    //     // $logger->log("view curr url ".$currUrl);
    //     // $logger->log("check ".print_r($_GET,1));
    //     if($_GET['cifr']=="rtr"){
    //         $logger->log("from rtr");
    //         $logger->log("cookie ".print_r($_COOKIE,1));
    //         // $logger->log("server ".print_r($_SERVER,1));
            

    //         $product = $observer->getEvent()->getProduct();
    //         if(!$product instanceof Mage_Catalog_Model_Product) {
    //             $logger->log('ProductView_Track: product is not a valid type',Zend_Log::ERR);
    //             return $this;
    //         }

    //         //return unique product id
    //         $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);
            
    //         $array = array(
    //                     'item' => $uniqueId,
    //                     'event' => 1,
    //                     'sku' => $product->getSku(),
    //                     'domain' => $_SERVER['HTTP_HOST'],
    //                     'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);

    //         $logger->log("array ".print_r($array,1));
    //         //sending request
    //         //return boolean
    //         // $response = Mage::getModel('expertrec_recommendation/api_request')
    //         //     ->setPrepareRequestStatus(false)
    //         //     ->setData(array(
    //         //             'item' => $uniqueId,
    //         //             'event' => 3,
    //         //             'sku' => $product->getSku(),
    //         //             'domain' => $_SERVER['HTTP_HOST'],
    //         //             'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])
    //         //         )
    //         //     ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
    //         //     ->prepareRequest()
    //         //     ->sendRequest();

    //         // //Mage::getSingleton('expertrec_recommendation/log')->log("ProductView_Track: request with uniqueId ".$uniqueId);
    //         // if(!$response) {
    //         //     $logger->log('ProductView_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
    //         // }
    //         return $this;

    //     }
    //     else{
    //         $logger->log("not from rtr");
    //     }

    // }
    

    public function addWishlist(Varien_Event_Observer $observer) {

        $wishListItemCollection = $observer->getItems();
        if (count($wishListItemCollection)) {
            $arrProductIds = array();
            foreach ($wishListItemCollection as $item) {
                /* @var $product Mage_Catalog_Model_Product */
                $product = $item->getProduct();
                // $arrProductIds[] = $product->getId();
            }
        }else{
            Mage::getSingleton('expertrec_recommendation/log')->log('AddToWishlist_Track: wishListItemCollection count is 0',Zend_Log::ERR);
        return $this;
        }

        if(!$product instanceof Mage_Catalog_Model_Product) {
            Mage::getSingleton('expertrec_recommendation/log')->log('AddToWishlist_Track: product is not a valid type',Zend_Log::ERR);
            return $this;
        }

        //return unique product id
        $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);
        $ci_id = $_COOKIE['ci_id'];
        
        //sending request
        //return boolean
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'ci_id' => $ci_id,
                    'item' => $uniqueId,
                    'event' => 17,
                    'sku' => $product->getSku(),
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])

                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();

        // Mage::getSingleton('expertrec_recommendation/log')->log("AddToWishlist_Track: request made with uniqueId ".$uniqueId);
        if(!$response) {
            Mage::getSingleton('expertrec_recommendation/log')->log('AddToWishlist_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
        }
        return $this;
    }



    protected function getFeedEndpoint(){
        try{
            $endpoint = Mage::getStoreConfig(self::FEED_LOG_ENDPOINT);
            $mid = Mage::getModel('expertrec_recommendation/validate')->getSiteKey();

            // $endpoint = "https://feed.expertrec.com/magento/n01eba6261ad7f174cd3a16523e86e65/";
            // $mid = Mage::getStoreConfig(self::MERCHANT_ID);

            if(empty($endpoint) || empty($mid)){
                Mage::getSingleton('expertrec_recommendation/log')
                        ->log("feed endpoint: ".$endpoint." or merchand id: ".$mid." is not configured Properly");
                return '';
            }
            
            return rtrim($endpoint,'/').'/'.$mid;
        }catch(Exception $e){
            Mage::getSingleton('expertrec_recommendation/log')->log("Error in getting feed endpoint: ".$e->getMessage());
            return '';
        }
    }

    /**
     * Load Expertrec Search.
     */
    public function useExpertrecSearch(Varien_Event_Observer $observer){
        try {
            $app = Mage::app();
            $pageIdentifier = $app->getFrontController()->getAction()->getFullActionName();
            
            if ($pageIdentifier === 'catalogsearch_result_index' || 
                $pageIdentifier === 'expertrec_result_index') 
            {
                $searchEnable = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_LIST_ENABLE);
                $customTemplate = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_CUSTOM_TEMPLATE);

                $requestParams = $app->getRequest()->getParams();
                if( (isset($searchEnable) && $searchEnable == "true") || 
                    (isset($requestParams["expertrec"]) && $requestParams["expertrec"] == "search")
                ){
                    if(isset($customTemplate) && $customTemplate == "true"){
                        $observer->getLayout()
                                ->getUpdate()
                                ->addHandle('expertrec_custom_autocomplete');
                    }else{
                        $resultData = Mage::helper('expertrec_recommendation/autocompletehelper')->prepareLayer($requestParams);

                        if(count($resultData) == 0 ){
                            throw new Exception("Either Search_api or facets_list has not configured.");
                        }
                        
                        Mage::register('expertrec_search_navigation', $resultData["facetHtml"]);
                        Mage::register('expertrec_search_list', $resultData["listHtml"]);

                        $observer->getLayout()
                                ->getUpdate()
                                ->addHandle('expertrec_autocomplete');
                    }
                }
            }
        }catch (Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log('useExpertrecSearch exception: '.$e->getMessage());
        }  
        return $this;
    }

}
?>

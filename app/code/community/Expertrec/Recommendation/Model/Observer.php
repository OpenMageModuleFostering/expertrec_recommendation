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

        $logger = Mage::getSingleton('expertrec_recommendation/log');

        $product = $observer->getEvent()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            $logger->log('AddToCart_Track: product is not a valid type',Zend_Log::ERR);
            return $this;
        }
        //return unique product id
        $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);
        $ci_id = $_COOKIE['ci_id'];

        // Gets the current store's details
        $store = Mage::app()->getStore();
         
        // Gets the current store's id
        $storeId = Mage::app()->getStore()->getStoreId();
         
        // Gets the current website's id
        $websiteId = Mage::app()->getStore()->getWebsiteId();

        $logger->log("Added to cart for product # ".$uniqueId);
        // $logger->log("store_id ".$storeId);
        // $logger->log("website_id ".$websiteId);
        
        //sending request
        //return boolean
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'ci_id' => $ci_id,
                    'item' => $uniqueId,
                    'event' => 3,
                    'sku' => $product->getSku(),
                    'sid' => $storeId,
                    'wid' => $websiteId,
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])

                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();

        // Mage::getSingleton('expertrec_recommendation/log')->log("AddToCart_Track: request made with uniqueId ".$uniqueId);
        if(!$response) {
            $logger->log('AddToCart_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
        }
        return $this;
    }

    /**
     * Method to track orders
     */
    public function trackOrder(Varien_Event_Observer $observer) {

		$logger = Mage::getSingleton('expertrec_recommendation/log');

        $payment = $observer->getEvent()->getPayment();

        if(!$payment instanceof Mage_Sales_Model_Order_Payment) {
            $logger->log('Order_Track: payment is not a valid type',Zend_Log::ERR);
            return $this;
        }
        $items = $payment->getOrder()->getAllVisibleItems();

        if(!is_array($items)) {
            return $this;
        }

        // Gets the current store's details
        $store = Mage::app()->getStore();
         
        // Gets the current store's id
        $storeId = Mage::app()->getStore()->getStoreId();
         
        // Gets the current website's id
        $websiteId = Mage::app()->getStore()->getWebsiteId();

        // $logger->log("store_id ".$storeId);
        // $logger->log("website_id ".$websiteId);

        foreach($items as $item) {
            if($item instanceof Mage_Sales_Model_Order) {
                $logger->log('Order_Track: request failed because item is of instancetype '.get_class($item), Zend_Log::ERR);
                continue;
            }
            $product =$item->getProduct();
            if(!$product instanceof Mage_Catalog_Model_Product) {
                return $this;
            }

            //return unique product id
            $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product, $item);
            $ci_id = $_COOKIE['ci_id'];
			$logger->log("trackOrder for product # ".$uniqueId);

            //sending request
            //return boolean
            $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'ci_id' => $ci_id,
                    'item' => $uniqueId,
                    'event' => 2,
                    'sku' => $item->getSku(),
                    'sid' => $storeId,
                    'wid' => $websiteId,
                    'qty' => $item->getQtyOrdered(),
                    'price' => $item->getPriceInclTax(),
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])  
                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();
            
            // $logger->log("Order_Track: request made with uniqueId ".$uniqueId);

            if(!$response) {
                $logger->log('Order_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
            }
        }
        return $this;

    }

    /**
     * Method to track remove item from cart
     */
    public function trackRemoveItem(Varien_Event_Observer $observer){

        $logger = Mage::getSingleton('expertrec_recommendation/log');
        
        $product = $observer->getQuoteItem()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            $logger->log('RemoveItemFromCart_Track: product is not a valid type',Zend_Log::ERR);
            return $this;
        }

        //return unique product id
        $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);

        $sku = $product->getSku();

        // Gets the current store's details
        $store = Mage::app()->getStore();
         
        // Gets the current store's id
        $storeId = Mage::app()->getStore()->getStoreId();
         
        // Gets the current website's id
        $websiteId = Mage::app()->getStore()->getWebsiteId();

        $logger->log("trackRemoveItem for product # ".$uniqueId);
        // $logger->log("store_id ".$storeId);
        // $logger->log("website_id ".$websiteId);

        //sending request
        //return boolean
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'item' => $uniqueId,
                    'event' => 11,
                    'sku' => $sku,
                    'sid' => $storeId,
                    'wid' => $websiteId,
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])
                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();

        //Mage::getSingleton('expertrec_recommendation/log')->log("RemoveItemFromCart_Track: request with uniqueId #".$uniqueId." and sku: ".$sku);
        if(!$response) {
            $logger->log('RemoveItemFromCart_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
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
		
		$logger->log("Deleted product # ".$product->getId());

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

        //Fetching product
        $product = $observer->getEvent()->getDataObject();

        //Apply rule to get updated price details
        Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($product);

        //sanity check
        if (!isset($product) || 
            !$product instanceof Mage_Catalog_Model_Product || 
            !$product->hasData('entity_id'))
        {
            $logger->log('SaveCatalogProduct_Track: product is not a valid type');
            return $this;
        }

        try{
            //Fetching global out of stock
            $global_display_out_of_stock = Mage::getStoreConfig('cataloginventory/options/show_out_of_stock');

            $storedHeaders = Mage::getStoreConfig(self::CONFIG_HEADERS);
            if (isset($storedHeaders)){
                $header = explode(',', $storedHeaders);
            }else{
                $header = array();
            }

            if(!empty($header)){
                // passing product to identify product url
                $feedUrl = $this->getFeedEndpoint();

                $mid = Mage::getStoreConfig(self::MERCHANT_ID);
                $feedUrl_new = "https://feed.expertrec.com/magento/n01eba6261ad7f174cd3a16523e86e65/".$mid;

                $storeIds = $product->getStoreIds();
                if(!empty($storeIds)){

                    foreach($storeIds as $storeId){
                        //Need to refetch the product based on wid
                        $productId = $product->getId();

                        // $logger->log("for store # ".$storeId);

                        $store = Mage::app()->getStore($storeId);

                        $websiteId = $store->getWebsiteId();
                        $website = Mage::app()->getWebsite($websiteId);

                        //Fetching collection
                        $coreResource = Mage::getSingleton("core/resource");
                        $catalogInventoryTable = method_exists($coreResource, 'getTableName')
                        ? $coreResource->getTableName('cataloginventory_stock_item') : 'cataloginventory_stock_item';
                        $stockfields = array("qty" => "qty", "manage_stock" => "manage_stock",
                        "use_config_manage_stock" => "use_config_manage_stock", "is_in_stock" => "is_in_stock");

                        $collection = Mage::getModel('catalog/product')->getCollection();
                        //Filtering the fetched collection
                        $collection->addFieldToFilter('entity_id',$productId)
                        ->addWebsiteFilter($websiteId)
                        ->setStoreId($storeId)
                        ->addAttributeToSelect('*')
                        ->addCategoryIds()
                        ->joinTable($catalogInventoryTable, 'product_id=entity_id', $stockfields, null, 'left');

                        //Looping to check disable and out of stock
                        foreach($collection as $productt){

                            $producttId = $productt->getId();
                            $status = $productt->getData('status');

                            //If product is disabled, send a BE hit
                            if($status == 2){
                                $logger->log("product # ".$producttId." is disabled for store # ".$storeId);

                                $status_info = '2'.chr(4).'Disabled';
                                $disabled_array = array('status' => $status_info,'entity_id' => $producttId,'sid' => $storeId,'wid' => $websiteId);

                                $finalUrl_disabled = $feedUrl.'/disabled';
                                // $logger->log("final disabled url is  : ".$finalUrl_disabled);

                                $response = Mage::getModel('expertrec_recommendation/api_request')
                                    ->setPrepareRequestStatus(false)
                                    ->setUserId('expertrec')
                                    ->setUrl($finalUrl_disabled)
                                    ->setMethod(Zend_Http_Client::POST)
                                    ->setData($disabled_array)
                                    ->setHeader("Content-Type",'application/json')
                                    ->setPrepareRequestStatus(true)
                                    ->sendRequest();
                                
                                $finalUrl_disabled_new = $feedUrl_new.'/disabled';

                                //sending request to 2 endpoints, for backward compatibility
                                $response = Mage::getModel('expertrec_recommendation/api_request')
                                    ->setPrepareRequestStatus(false)
                                    ->setUserId('expertrec')
                                    ->setUrl($finalUrl_disabled_new)
                                    ->setMethod(Zend_Http_Client::POST)
                                    ->setData($disabled_array)
                                    ->setHeader("Content-Type",'application/json')
                                    ->setPrepareRequestStatus(true)
                                    ->sendRequest();

                                if(!$response) {
                                        $logger->log('SaveCatalogProduct_Track: request failed for product with Id #'.$product->getId());
                                    }
                         }
                         else{
                            //This can only mean that the product is out of stock, and didn't return a value because global out of stock is not set
                            if($productt->getData("is_in_stock") == 0 && $global_display_out_of_stock == 0 ){
                                $logger->log("product # ".$producttId." is out_of_stock for store # ".$storeId);

                                $qty = $productt->getData('qty');
                                $stock_array = array('qty' => $qty,'entity_id' => $producttId,'is_in_stock'=>0,'sid' => $storeId,'wid' => $websiteId);

                                $finalUrl_stock = $feedUrl.'/stock';
                                // $logger->log("final stock url is  : ".$finalUrl_stock);

                                $response = Mage::getModel('expertrec_recommendation/api_request')
                                    ->setPrepareRequestStatus(false)
                                    ->setUserId('expertrec')
                                    ->setUrl($finalUrl_stock)
                                    ->setMethod(Zend_Http_Client::POST)
                                    ->setData($stock_array)
                                    ->setHeader("Content-Type",'application/json')
                                    ->setPrepareRequestStatus(true)
                                    ->sendRequest();
                                
                                $finalUrl_stock_new = $feedUrl_new.'/stock';

                                //sending request to 2 endpoints, for backward compatibility
                                $response = Mage::getModel('expertrec_recommendation/api_request')
                                    ->setPrepareRequestStatus(false)
                                    ->setUserId('expertrec')
                                    ->setUrl($finalUrl_stock_new)
                                    ->setMethod(Zend_Http_Client::POST)
                                    ->setData($stock_array)
                                    ->setHeader("Content-Type",'application/json')
                                    ->setPrepareRequestStatus(true)
                                    ->sendRequest();

                                if(!$response) {
                                        $logger->log('SaveCatalogProduct_Track: request failed for product with Id #'.$product->getId());
                                    }
                            }else{
                                //Fetch price data for product if it is in stock, or if it is out of stock and has global setting to true
                                $collectionWithPrice = Mage::getModel('catalog/product')->getCollection();
                                $collectionWithPrice->addFieldToFilter('entity_id',$producttId)
                                ->addWebsiteFilter($websiteId)
                                ->setStoreId($storeId)
                                ->addAttributeToSelect('*')
                                ->addCategoryIds()
                                ->joinTable($catalogInventoryTable, 'product_id=entity_id', $stockfields, null, 'left')
                                ->addPriceData(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID, $websiteId);
                                    //Looping on price product, to send BE hit
                                foreach($collectionWithPrice as $selectedProduct){

                                    $selectedProduct->setStoreId($storeId);

                                    $resultArray = Mage::getSingleton('expertrec_recommendation/feed_formatter')
                                    ->init()
                                    ->prepareRow($header,$selectedProduct);
                                    $resultArray['sid'] = $storeId;
                                    $resultArray['wid'] = $websiteId;
                                    $resultArray['type'] = $selectedProduct->getTypeId();

                                    if($selectedProduct->getTypeId() == 'grouped'){

                                        $associatedProducts = $selectedProduct->getTypeInstance(true)->getAssociatedProducts($selectedProduct);
                                        $associatedProductsIds = array();
                                        foreach($associatedProducts as $childProduct){
                                            $associatedProductsIds[] = $childProduct->getId();
                                        }
                                        $resultArray['associatedProductsIds'] = $associatedProductsIds;
                                    }


                                    if($selectedProduct->getTypeId() == 'configurable'){
                                        
                                        $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$selectedProduct);
                                        $associatedProductsIds = array();
                                        foreach($childProducts as $childProduct){
                                            $associatedProductsIds[] = $childProduct->getId();
                                        }
                                        $resultArray['associatedProductsIds'] = $associatedProductsIds;
                                    }

                                    if($selectedProduct->getTypeId() == 'bundle'){
                                        
                                        $childProducts = $selectedProduct->getTypeInstance(true)->getSelectionsCollection($selectedProduct->getTypeInstance(true)->getOptionsIds($selectedProduct), $selectedProduct);
                                     
                                        $associatedProductsIds = array();
                                        foreach($childProducts as $childProduct) {
                                            $associatedProductsIds[] = $childProduct->getId();
                                        }
                                        $resultArray['associatedProductsIds'] = $associatedProductsIds;
                                    }

                                    $logger->log("product # ".$productId." has valid data for store # ".$storeId);
                                    // $logger->log(print_r($resultArray,1));

                                    $finalUrl = $feedUrl.'/product';
                                    // $logger->log("final product url is  : ".$finalUrl);


                                    $response = Mage::getModel('expertrec_recommendation/api_request')
                                    ->setPrepareRequestStatus(false)
                                    ->setUserId('expertrec')
                                    ->setUrl($finalUrl)
                                    ->setMethod(Zend_Http_Client::POST)
                                    ->setData($resultArray)
                                    ->setHeader("Content-Type",'application/json')
                                    ->setPrepareRequestStatus(true)
                                    ->sendRequest();

                                    $finalUrl_new = $feedUrl_new.'/product';

                                        //sending request to 2 endpoints, for backward compatibility
                                    $response = Mage::getModel('expertrec_recommendation/api_request')
                                    ->setPrepareRequestStatus(false)
                                    ->setUserId('expertrec')
                                    ->setUrl($finalUrl_new)
                                    ->setMethod(Zend_Http_Client::POST)
                                    ->setData($resultArray)
                                    ->setHeader("Content-Type",'application/json')
                                    ->setPrepareRequestStatus(true)
                                    ->sendRequest();

                                    if(!$response) {
                                        $logger->log('SaveCatalogProduct_Track: request failed for product with Id #'.$product->getId());
                                    }

                                }
                            }
                        }
                    }

                }
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

        $category_data = $category->getData();

        $storeId = $category_data['store_id'];

        $store = Mage::app()->getStore($storeId);

        $websiteId = $store->getWebsiteId();

        $category->setStoreId($storeId);

        // get category url
        $category_url = $store->getBaseUrl().$category->getUrlPath();

        $category_id = $category->getId();
		$logger->log("saved Category for # ".$category_id);
        $category_name = $category->getName();

        // get path with name
        $pathIdArray = explode('/', $category->getPath());
        // $storeId = $store->getId();
        $pathNameArray = array();

        for($i=0;$i<count($pathIdArray);$i++){
            $categoryy = Mage::getModel('catalog/category');
            $categoryy->setStoreId($storeId);
            $categoryy->load($pathIdArray[$i]);
            $pathNameArray[$i] = $categoryy->getName();
        }
        // if($category->getName() == ''){
        //     $categoryy = Mage::getModel('catalog/category')->load($category->getId());
        //     $category_name = $categoryy->getName();
        // }
        //removing Root catalog which is $pathNameArrray[0]
        // array_shift($pathNameArray);
        $category_path = implode('/', $pathNameArray);

        // array_shift($pathIdArray);
        $category_id_path = implode('/', $pathIdArray);

        $categoryArray = array('categoryId' => $category_id,
                               'categoryName' => $category_name,
                               'categoryIdPath' => $category_id_path,
                               'categoryNamePath' => $category_path,
                               'categoryUrl' => $category_url,
                               'sid' => $storeId,
                               'wid' => $websiteId);

        //$logger->log("array ".print_r($categoryArray,1));
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
        $logger=Mage::getSingleton('expertrec_recommendation/log');
        $wishListItemCollection = $observer->getItems();
        if (count($wishListItemCollection)) {
            $arrProductIds = array();
            foreach ($wishListItemCollection as $item) {
                /* @var $product Mage_Catalog_Model_Product */
                $product = $item->getProduct();
                // $arrProductIds[] = $product->getId();
            }
        }else{
            $logger->log('AddToWishlist_Track: wishListItemCollection count is 0',Zend_Log::ERR);
        return $this;
        }

        if(!$product instanceof Mage_Catalog_Model_Product) {
            $logger->log('AddToWishlist_Track: product is not a valid type',Zend_Log::ERR);
            return $this;
        }

        //return unique product id
        $uniqueId = Mage::helper('expertrec_recommendation')->getUniqueId($product);
        $ci_id = $_COOKIE['ci_id'];

        // Gets the current store's details
        $store = Mage::app()->getStore();
         
        // Gets the current store's id
        $storeId = Mage::app()->getStore()->getStoreId();
         
        // Gets the current website's id
        $websiteId = Mage::app()->getStore()->getWebsiteId();

        $logger->log("added to Wishlist for product # ".$uniqueId);
        // $logger->log("store_id ".$storeId);
        // $logger->log("website_id ".$websiteId);
        
        //sending request
        //return boolean
        $response = Mage::getModel('expertrec_recommendation/api_request')
            ->setPrepareRequestStatus(false)
            ->setData(array(
                    'ci_id' => $ci_id,
                    'item' => $uniqueId,
                    'event' => 17,
                    'sku' => $product->getSku(),
                    'sid' => $storeId,
                    'wid' => $websiteId,
                    'domain' => $_SERVER['HTTP_HOST'],
                    'ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])

                )
            ->setHeader("User-Agent",$_SERVER['HTTP_USER_AGENT'])
            ->prepareRequest()
            ->sendRequest();

        // $logger->log("AddToWishlist_Track: request made with uniqueId ".$uniqueId);
        if(!$response) {
            $logger->log('AddToWishlist_Track: request failed for product with uniqueId #'.$uniqueId,Zend_Log::ERR);
        }
        return $this;
    }


    public function outOfStockReport($observer)
    {   
        $logger=Mage::getSingleton('expertrec_recommendation/log');
        $item = $observer->getEvent()->getItem();
        $item_array = $item->getData();


        $product_id = $item_array["product_id"];

        $product_stores = Mage::getModel('catalog/product')->load($product_id);

        //Checking for stock details
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product_id);
        $qty = $stockItem->getData('qty');
        $inStock = $stockItem->getData('is_in_stock');
        $storeIds = $product_stores->getStoreIds();

        foreach($storeIds as $storeId){

            $store = Mage::app()->getStore($storeId);

            $websiteId = $store->getWebsiteId();

            //Magento has a default out of stock check, custom quantity can be set
            if ($inStock == 0){
                $logger->log("Out of stock".$product_id);
                $stockArray = array('entity_id' => $product_id, 'qty' => $qty, 'is_in_stock' =>0, 'sid' => $storeId, 'wid'=> $websiteId);

                $feedUrl = $this->getFeedEndpoint();
                $finalUrl = $feedUrl.'/stock';
                if(empty($finalUrl)){
                    return $this;
                }

                //sending request
                $response = Mage::getModel('expertrec_recommendation/api_request')
                    ->setPrepareRequestStatus(false)
                    ->setUserId('expertrec')
                    ->setUrl($finalUrl)
                    ->setMethod(Zend_Http_Client::POST)
                    ->setData($stockArray)
                    ->setHeader("Content-Type",'application/json')
                    ->setPrepareRequestStatus(true)
                    ->sendRequest();

                $mid = Mage::getStoreConfig(self::MERCHANT_ID);
                $feedUrl = "https://feed.expertrec.com/magento/n01eba6261ad7f174cd3a16523e86e65/";
                $finalUrl = $feedUrl.''.$mid.'/stock';
                        
                //sending request
                $response = Mage::getModel('expertrec_recommendation/api_request')
                    ->setPrepareRequestStatus(false)
                    ->setUserId('expertrec')
                    ->setUrl($finalUrl)
                    ->setMethod(Zend_Http_Client::POST)
                    ->setData($stockArray)
                    ->setHeader("Content-Type",'application/json')
                    ->setPrepareRequestStatus(true)
                    ->sendRequest();

                // $logger->log('SUCCESS : request made for stock status with Id #'.$product_id);
                if(!$response) {
                    $logger->log('request failed for stock status with Id #'.$product_id);
                }
            }
        }

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
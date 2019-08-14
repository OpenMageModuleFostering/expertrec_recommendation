<?php

/**
 *
 * @category    Expertrec
 * @package     Expertrec_Recommendation
 * @copyright   Copyright (c) 2016 CloudInfra LLC.
 */
class Expertrec_Recommendation_Helper_Data extends Mage_Core_Helper_Abstract {
    
    const CONFIG_PREFIX = 'expertrec/general';
    const FEED_UPLOAD_ENDPOINT = 'expertrec/general/upload_endpoint';

    public function getUniqueId($product, $item=null) {
        $type= null;
        if($product->hasData('type_id')) {
            $type = $product->getData('type_id');
        }

        if($type =='simple'){

            if ($item != null && $item instanceof Mage_Sales_Model_Order) {
                if($item->getParentItem() != null)  {
                    $productId = $item->getParentItem()->getProductId();
                    return $productId;
                }
            }
                  
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

            if(!isset($parentIds) || sizeof($parentIds) == 0) {
                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
                if(isset($parentIds) && sizeof($parentIds) > 0) {
                    return $parentIds[0];
                }
            }else {
                return $parentIds[0];
            }
        }

        $productId = $product->getData('entity_id');
        return $productId;
    }
 
    public function saveConfig($key, $value, $scope = 'default', $scopeId = 0){
        if(is_null($value) || is_array($value) || is_object($value)){
            return $this;
        }
        
        $path = self::CONFIG_PREFIX . '/' . $key; 
        Mage::getConfig()->saveConfig(rtrim($path, '/'), (string)$value, $scope, $scopeId);
        
        Mage::getSingleton('expertrec_recommendation/log')->log( "Successfully updated : <".$key."> in core_config_data table.");
        return $this;
    }

    public function getConfig($key){
        $path = self::CONFIG_PREFIX . '/' . $key;

        return Mage::getStoreConfig($path);
    }

    public function clearCache($type='config'){
        Mage::app()->getCacheInstance()->cleanType($type);
    }

    public function sendCurl($command){
        if (isset($ch)) {
            unset($ch);
        }

        if (function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            $str = curl_exec($ch);
        } else {
            $str = 'failed';
        }

        return $str;
    }

    public function getPages($curPage,$count,$displayPages=5,$itemPerPage=20){
        $totalPages = ceil($count / $itemPerPage);
        $pages = array();
        if ($totalPages <= $displayPages) {
            $pages = range(1, $totalPages);
        }
        else{
            $half = ceil($displayPages / 2);
            if ($curPage >= $half && $curPage <= $totalPages - $half){
                $start  = ($curPage - $half) + 1;
                $finish = ($start + $displayPages) - 1;
            }elseif ($curPage < $half) {
                $start  = 1;
                $finish = $displayPages;
            }elseif ($curPage > ($totalPages - $half)) {
                $finish = $totalPages;
                $start  = $finish - $displayPages + 1;
            }
            $pages = range($start, $finish);
        }
        return $pages;
    }

    public function getAddToCartUrl($productId){
        try{
            $product=Mage::getModel('catalog/product')->load($productId);
            return Mage::helper('checkout/cart')->getAddUrl($product);
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting AddToCart Url: ".$e->getMessage());
        }
        return '';
    }

    public function getWishlistUrl($productId){
        try{
            $product=Mage::getModel('catalog/product')->load($productId);
            return Mage::helper('wishlist')->getAddUrl($product);
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting Wishlist Url: ".$e->getMessage());
        }
        return '';
    }
    

    public function getProductCompareUrl($productId){
        try{
            $product=Mage::getModel('catalog/product')->load($productId);
            return Mage::helper('catalog/product_compare')->getAddUrl($product);
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting Product-Compare Url: ".$e->getMessage());
        }
        return '';
        
    }

    public function getFormatCurrency($price=0,$productId=0,$fetchPrice=false){
        try{
            if($fetchPrice){
                $product=Mage::getModel('catalog/product')->load($productId);
                if($product){
                    $price = $product->fetchPrice();
                }
            }
            if(empty($price)){
                return $price;
            }
            $formattedPrice = Mage::helper('core')->currency((int)$price, true, true);
            return $formattedPrice;
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting FormattedPrice: ".$e->getMessage());
        }
        return '';
    }

    public function getFormatPrice($price){
        try{
            if(empty($price)){
                return $price;
            }
            return Mage::helper('core')->formatPrice($price,false);
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting Format Price: ".$e->getMessage());
        }
        return '';
    }

    public function getProductDescription($productId){
        try{
            $product=Mage::getModel('catalog/product')->load($productId);
            return $product->getDescription();
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting Product Description: ".$e->getMessage());
        }
        return '';
    }

    public function pushFeed($filepath){
        try{
            $logger = Mage::getSingleton('expertrec_recommendation/log');
            $url = Mage::getStoreConfig(self::FEED_UPLOAD_ENDPOINT);
            if(empty($url)){
                $logger->log("Upload endpoint is not configured");
                return false;
            }
            
            $filename = end(explode("/", $filepath));
            $file = new CurlFile($filepath,'',$filename);
            $fields = array('file'=>$file);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_POST,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            $logger->log('Pushing the feed to '.$url);
            // push the feed to the server
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $logger->log("Response from the server: ".json_encode($response));
            
            if($status != 200) {
                throw new Exception('Unexpected response from server');
            }
            $logger->log("File Successfully uploaded to the server.");
            Mage::helper('expertrec_recommendation/filehelper')->deleteFile($filepath); 
        }catch(Exception $ex){
            $logger->log("Error while uploading the feed because of " . $ex->getMessage());
            return false;
        }
        curl_close($ch);
        return true;
    }


}  
?>

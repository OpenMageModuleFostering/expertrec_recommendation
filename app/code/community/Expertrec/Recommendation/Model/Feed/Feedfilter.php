<?php
class Expertrec_Recommendation_Model_Feed_Feedfilter {
		public function addBasicFilter(Mage_Core_Model_Website $website,Mage_Core_Model_Store $oStore){

	        $collection = Mage::getModel('catalog/product')->getCollection();
	        $visiblityCondition = array('in' => array(2,3,4));
	        $coreResource = Mage::getSingleton("core/resource");
	        $catalogInventoryTable = method_exists($coreResource, 'getTableName')
	              ? $coreResource->getTableName('cataloginventory_stock_item') : 'cataloginventory_stock_item';
	        $stockfields = array("qty" => "qty", "manage_stock" => "manage_stock",
	              "use_config_manage_stock" => "use_config_manage_stock", "is_in_stock" => "is_in_stock");

	        $collection
	              ->addWebsiteFilter($website->getWebsiteId())
	              ->setStoreId($oStore->getId())
	              ->addAttributeToSelect('*')
	              ->joinTable($catalogInventoryTable, 'product_id=entity_id', $stockfields, null, 'left')
	              ->addCategoryIds()
                  ->addAttributeToFilter('visibility',$visiblityCondition)
	              ->addPriceData(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID, $website->getWebsiteId());

	        //filters out products which are Disabled
	        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
	        
	        return $collection;
	    }
}
?>

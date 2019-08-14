<?php
class Expertrec_Recommendation_Model_Feed_Feedfilter {
	// adding filters selected form info page
	const CONFIG_FILTERS  = 'expertrec/general/filters';
	public function addBasicFilter(Mage_Core_Model_Website $website,Mage_Core_Model_Store $oStore){

		    $storedFilters = Mage::getStoreConfig(self::CONFIG_FILTERS);
		    $storedFiltersArray = array();

		    if(isset($storedFilters))
		    {
		        if(!empty($storedFilters)){
		        	$storedFiltersArray = explode(',', $storedFilters);
		        }
		    }

	    	// $stock = in_array('filter_by_stock',$storedFiltersArray) ? 1 : 0;
    		// $status = in_array('filter_by_status',$storedFiltersArray) ? 1 : 0;
      		$visible_1 = in_array('not_visible_individually',$storedFiltersArray) ? 1 : 0;
      		$visible_2 = in_array('visible_catalog',$storedFiltersArray) ? 2 : 0;
      		$visible_3 = in_array('visible_search',$storedFiltersArray) ? 3 : 0;
      		$visible_4 = in_array('visible_catalog_search',$storedFiltersArray) ? 4 : 0;


        $collection = Mage::getModel('catalog/product')->getCollection();
        $visiblityCondition = array('in' => array("$visible_1","$visible_2","$visible_3","$visible_4"));
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

       
        //  $stockCollection = Mage::getModel('cataloginventory/stock_item')->getCollection()
        // ->addFieldToFilter('is_in_stock', 1); //this can also be used to filter

        //filters out products which are Disabled
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        
        return $collection;
    }
}
?>

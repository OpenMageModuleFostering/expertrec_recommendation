<?php
  class Expertrec_Recommendation_Model_Feed_Feedfilter {
    // adding filters selected form info page
    const CONFIG_FILTERS  = 'expertrec/general/filters';
    
    public function addBasicFilter(Mage_Core_Model_Website $website,Mage_Core_Model_Store $oStore){

      $collection = Mage::getModel('catalog/product')->getCollection();

      $storedFilters = Mage::getStoreConfig(self::CONFIG_FILTERS);
      $storedFiltersArray = array();
      if(isset($storedFilters)){
        if(!empty($storedFilters)){
          $storedFiltersArray = explode(',', $storedFilters);
        }
      }

      $visible_1 = in_array('not_visible_individually',$storedFiltersArray) ? 1 : 0;
      $visible_2 = in_array('visible_catalog',$storedFiltersArray) ? 2 : 0;
      $visible_3 = in_array('visible_search',$storedFiltersArray) ? 3 : 0;
      $visible_4 = in_array('visible_catalog_search',$storedFiltersArray) ? 4 : 0;
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
        // ->addAttributeToFilter('visibility',$visiblityCondition)
        ->addPriceData(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID, $website->getWebsiteId());

      if(in_array('not_visible_individually',$storedFiltersArray) || in_array('visible_catalog',$storedFiltersArray) || in_array('visible_search',$storedFiltersArray) || in_array('visible_catalog_search',$storedFiltersArray) ){
        $collection->addAttributeToFilter('visibility',$visiblityCondition);
      }

      // if(in_array('filter_by_stock',$storedFiltersArray)){
      //   $collection
      //     ->joinField(
      //       'is_in_stock',
      //       'cataloginventory/stock_item',
      //       'is_in_stock',
      //       'product_id=entity_id',
      //       '{{table}}.stock_id=1',
      //       'left'
      //     )
      //     ->addAttributeToFilter('is_in_stock', array('eq' => 1));
      // }

      // if(in_array('filter_by_status',$storedFiltersArray)){
      //   $collection->addAttributeToFilter('status', array('eq' => 1));
      // }

      //filters out products which are Disabled
      Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);

      // echo "collection <pre>";
      // print_r($collection->getSize());
      // print_r($collection->getData());
      // exit();

      return $collection;
    }
  }
?>

<?php
 
class Expertrec_Recommendation_Block_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar {

	/**
     * Set collection to pager, overriding this function
     *To ignore relevance
     *
     * @param Varien_Data_Collection $collection
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function setCollection($collection)
    {
        $searchEnable = Mage::helper('expertrec_recommendation/searchhelper')->getSearchEnable();         
        $pageIdentifier = Mage::app()->getFrontController()->getAction()->getFullActionName();
        $isSearchPage = $pageIdentifier === 'catalogsearch_result_index' || 
                    $pageIdentifier === 'expertrec_result_index';     
                   
        //Don't use the toolbar when it is not the search page or when the search is not enabled
        if(!$isSearchPage || !(isset($searchEnable) && $searchEnable == "true"))
        {
            Mage::getSingleton('expertrec_recommendation/log')->log(" Default Toolbar call made "); 
            return parent::setCollection($collection);
        }

        $this->_collection = $collection;
        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }
        if ($this->getCurrentOrder() != "relevance") {

            Mage::getSingleton('expertrec_recommendation/log')->log(" I have received order from Magento ".print_r($this->getCurrentOrder(),1),null);
            $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());

        }

        return $this;
    }

}

?>

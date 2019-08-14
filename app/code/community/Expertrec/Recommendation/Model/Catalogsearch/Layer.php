<?php

class Expertrec_Recommendation_Model_Catalogsearch_Layer extends Mage_CatalogSearch_Model_Layer{

    var $searchEnable;
    const SEARCH_LIST_ENABLE = 'search/enable';
    const SEARCH_CUSTOM_TEMPLATE = 'search/custom_template';

    private function setSearchEnable()
    {

        $this->searchEnable = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_LIST_ENABLE);
    }

    private function setExpertrecSearchData(){
            try
             {
                $app = Mage::app();
                $pageIdentifier = $app->getFrontController()->getAction()->getFullActionName();
                
                if ($pageIdentifier === 'catalogsearch_result_index' || 
                    $pageIdentifier === 'expertrec_result_index') 
                {                

                    $customTemplate = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_CUSTOM_TEMPLATE);

                    $requestParams = $app->getRequest()->getParams();

                    if( (isset($this->searchEnable) && $this->searchEnable == "true") || 
                        (isset($requestParams["expertrec"]) && $requestParams["expertrec"] == "search"))
                    {
                        if(isset($customTemplate) && $customTemplate == "true")
                        {
                            $observer->getLayout()
                                    ->getUpdate()
                                    ->addHandle('expertrec_custom_autocomplete');
                        }
                        else
                        {
                            //This sets the result IDs
                            Mage::getSingleton('expertrec_recommendation/log')->log(" Fetching search results from expertec ");

                            Mage::helper('expertrec_recommendation/searchhelper')->prepareLayer($requestParams);

                       }
                    }
                }
            }
            catch (Exception $e) 
            {
                Mage::getSingleton('expertrec_recommendation/log')->log('setExpertrecSearchData exception: '.$e->getMessage());
            }  
            
    } 


    /**
     * Prepare product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
        $this->setSearchEnable();        

        if(isset($this->searchEnable) && $this->searchEnable == "true")
        {
            //Setting product IDs
            $this->setExpertrecSearchData();
            $product_ids = Mage::helper('expertrec_recommendation/searchhelper')->getResultIds();    
            Mage::getSingleton('expertrec_recommendation/log')->log(" The expertrec result IDs are ".count($product_ids));        

            $collection
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addFieldToFilter('entity_id',array('in'=>$product_ids))
            ->setStore(Mage::app()->getStore())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite();

            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
        }
        else
        {
            //Call parent function if search is not enabled
            Mage::getSingleton('expertrec_recommendation/log')->log(" Default search returned products "); 
            return parent::prepareProductCollection($collection);
        }  


        return $collection;
    }


}
 
?>

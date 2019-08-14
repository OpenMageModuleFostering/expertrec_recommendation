<?php

/**
 * Translates category ids into a complete path.
 */
 
class Expertrec_Recommendation_Model_Translator_Category {

    protected $_categoryPaths = array();

    /**
     * Translates a category id stored in the supplied field to a full category path.
     *
     * @param $product 
     * @return string
     */
    public function translate($product) {
        $categoryPathArray=array();
        $categoryArray  = array_unique($product->getCategoryIds());
        foreach ($categoryArray as $category_id) {
            $curPath=$this->_getCategoryPath($category_id);
            if(!empty($curPath) && !in_array($curPath, $categoryPathArray)){
              $categoryPathArray[] = $curPath;
            }
        }
        
        return implode(chr(4),$categoryPathArray);
    }

    /**
     * First check given category_id present in the global category_path Array or not. If not found then make a db call to 
     * fetch category path and set it to category_path array.
     *
     * @param category id
     * @return category path
     */
    protected function _getCategoryPath($categoryId) {
        try{
            if (!array_key_exists($categoryId, $this->_categoryPaths)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                if ($category === null || !$category->getIsActive() || $category->getLevel() == 1){
                    $this->_categoryPaths[$categoryId] = '';
                }else {
                    $parentCategoryPath = $this->_getCategoryPath($category->getParentId());
                    if ($parentCategoryPath == '') {
                        $this->_categoryPaths[$categoryId] = $category->getName();
                    }else {
                        $this->_categoryPaths[$categoryId] = $parentCategoryPath . chr(3) . $category->getName();
                    }
                }
            }
            return $this->_categoryPaths[$categoryId];
        }catch (Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log("Error in getCategoryPath: ".$e->getMessage());
            return '';
        }

    }

}

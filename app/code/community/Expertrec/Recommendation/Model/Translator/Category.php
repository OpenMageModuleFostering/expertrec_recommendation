<?php
/**
* Translates category ids into a complete path.
*/
class Expertrec_Recommendation_Model_Translator_Category {
    protected $_categoryPaths = array();
    protected $_categoryIdPaths = array();
    /**
    * Translates a category id stored in the supplied field to a full category path.
    *
    * @param $product 
    * @return string
    */

    public function translate($product,$idStr) {
        $categoryPathArray=array();
        $categoryArray  = array_unique($product->getCategoryIds());
        foreach ($categoryArray as $category_id) {
            if($idStr == 0){
                $curPath=$this->_getCategoryPath($category_id);
            }elseif ($idStr == 1){
                $curPath=$this->_getCategoryIdPath($category_id);
            }
            if(!empty($curPath)){
                $categoryPathArray[] = $curPath;
            }
        }
        //Mage::getSingleton('expertrec_recommendation/log')->log(" The categories are : ".implode(chr(4),$categoryPathArray));
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

    /**
    * First check given category_id present in the global category_path Array or not. If not found then make a db call to 
    * fetch category path and set it to category_path array.
    *
    * @param category id
    * @return category path
    */
    protected function _getCategoryIdPath($categoryId) {
        try{
            if (!array_key_exists($categoryId, $this->_categoryIdPaths)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                if ($category === null || !$category->getIsActive() || $category->getLevel() == 1){
                    $this->_categoryIdPaths[$categoryId] = '';
                }else {
                //Mage::getSingleton('expertrec_recommendation/log')->log("Inside category ID");
                    $parentCategoryPath = $this->_getCategoryIdPath($category->getParentId());
                    if ($parentCategoryPath == '') {
                        $this->_categoryIdPaths[$categoryId] = $categoryId;
                    }else {
                        $this->_categoryIdPaths[$categoryId] = $parentCategoryPath . chr(3) . $categoryId;
                    }
                }
            }
            return $this->_categoryIdPaths[$categoryId];
        }catch (Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log("Error in getCategoryPath: ".$e->getMessage());
            return '';
        }
    }
}
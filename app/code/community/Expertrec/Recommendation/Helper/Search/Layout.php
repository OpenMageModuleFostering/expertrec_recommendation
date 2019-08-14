<?php

class Expertrec_Recommendation_Helper_Search_Layout extends Mage_Core_Helper_Abstract{

    const SEARCH_SINGLE_SELECT_FILTERS = 'search/single_select_filters';
    const SEARCH_IS_AJAX = 'search/is_ajax';
    
    protected $_isAjax = false;
    protected $_confArray;
    
    protected $_singleSelectFilters = array();

    public function init($confArray){
        $this->_confArray = $confArray;
        try{
            $singleFilters = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_SINGLE_SELECT_FILTERS);
            $isAjax = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_IS_AJAX);
            

            $this->_singleSelectFilters = isset($singleFilters) ? explode(',', $singleFilters) : $this->_singleSelectFilters;
            $this->_isAjax = isset($isAjax) && $isAjax == 'true' ? true : false;
            
        }catch (Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log('Search Layout Helper::init exception: '.$e->getMessage());
        }
        return $this;
    }

    public function getIsAjax(){
        return $this->_isAjax;
    }

    protected function removeRequestParam($paramKey,$url=''){
        $url = !empty($url) ? $url : $this->_confArray["url"];
        $finalUrl = rtrim(Mage::helper('core/url')->removeRequestParam($url, $paramKey, false),'&');
        return $finalUrl;
    }

    /**
    * @param string facet-header
    * @param string facet-label
    * @return string url
    */
    protected function getFacetUrl($fhead,$facetLabel){
        $confArray = $this->_confArray;
        $url = $this->removeRequestParam($fhead);

        $filter = isset($confArray["filters"][$fhead]) ? $confArray["filters"][$fhead] : '';
        $label = str_replace(" ","+",$facetLabel);
        if(!in_array($fhead, $this->_singleSelectFilters) && !empty($filter)){
            $att = preg_split('/(%2C|,)/', $filter);
            if(!in_array($label, $att)){
                $att[] = $label;
            }
            $url .= "&".str_replace(" ","_",$fhead).'='.implode('%2C', $att);
        }else{
            $url .= "&".str_replace(" ","_",$fhead).'='.$label;
        }
        return rtrim($url,'&');
    }    

    /*
    * @param filterkey string
    * @param filterlist Array
    * @return url
    */
    protected function getClearLinkUrl($fKey,$flist){
        $baseUrl = $this->removeRequestParam($fKey);
        $linkUrl = $baseUrl.'&'.str_replace(' ', '_', $fKey)."=".implode('%2C', $flist);
        return $linkUrl;
    }
       
}

?>
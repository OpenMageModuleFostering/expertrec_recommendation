<?php

class Expertrec_Recommendation_Helper_Search_Layout extends Mage_Core_Helper_Abstract{

    const SEARCH_SINGLE_SELECT_FILTERS = 'search/single_select_filters';
    const SEARCH_IS_AJAX = 'search/is_ajax';
    const SEARCH_DISPLAY_PAGES = 'search/display_pages';
    const SEARCH_FETCH_PRICE = 'search/fetch_price';
    const SEARCH_CONVERT_PRICE = 'search/convert_price';

    protected $_itemsPerPage;
    protected $_displayPages = 5;
    protected $_isAjax = false;
    protected $_confArray;
    protected $_fetchPrice = false;
    protected $_convertPrice = false;
    protected $_singleSelectFilters = array();

    public function init($confArray){
        $this->_confArray = $confArray;
        try{
            $singleFilters = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_SINGLE_SELECT_FILTERS);
            $isAjax = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_IS_AJAX);
            $displayPages = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_DISPLAY_PAGES);
            $fetchPrice = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_FETCH_PRICE);
            $convertPrice = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_CONVERT_PRICE);

            $this->_singleSelectFilters = isset($singleFilters) ? explode(',', $singleFilters) : $this->_singleSelectFilters;
            $this->_isAjax = isset($isAjax) && $isAjax == 'true' ? true : false;
            $this->_fetchPrice = isset($fetchPrice) && $fetchPrice == 'true' ? true : false;
            $this->_convertPrice = isset($convertPrice) && $convertPrice == 'true' ? true : false;
            $this->_displayPages = !empty($displayPages) ? (int)$displayPages : $this->_displayPages;

            $this->_itemsPerPage = Mage::helper('expertrec_recommendation/autocompletehelper')->getItemsPerPage(); 
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

    protected function getToolbarDiv(){
        
        $result = '<div class="toolbar"><div class="sorter">';
        $result .= '<p class="view-mode"><label>'.$this->__('View as').'</label>';

        if($this->_confArray["mode"] == 'grid'){
            $result .= '<strong title="Grid" class="grid">Grid</strong><a href="'.$this->removeRequestParam("mode").'&mode=list" title="List" class="list">List</a>';
        }else{
            $result .= '<a href= "'.$this->removeRequestParam("mode").'&mode=grid" title="Grid" class="grid">Grid</a><strong title="List" class="list">List</strong>';
        }

        $result .= '</p>';
        $result .= '<div class="sort-by"><label>'.$this->__('Sort By').'</label>';
        $result .= '<select onchange="setLocation(this.value)" title="'.$this->__('Sort By').'">';

        //sort by
        $orderArray = array("relevance"=>"Relevance", 'popularity'=>"Popularity", "price+asc"=>"Price - Low to High", "price+desc"=>"Price - High to Low");
        foreach ($orderArray as $oKey => $oValue) {
            if(urlencode($this->_confArray["order"]) == $oKey){
                $result .= '<option value="'.$this->removeRequestParam("order").'&order='.$oKey.'" selected="selected">'.$this->__($oValue).'</option>';
            }else{
                $result .= '<option value="'.$this->removeRequestParam("order").'&order='.$oKey.'">'.$this->__($oValue).' </option>';
            }
        }

        $result .= '</select>';
        $result .= '</div></div>';//close sorter

        $result .= '<div class="pager">';
        $result .= '<div class="count-container">';

        $totalNum = $this->_confArray["count"];
        $totalPage = ceil($totalNum/$this->_itemsPerPage);
        $previous = $this->_confArray["curPage"]-1;
        $firstNum = $previous * $this->_itemsPerPage + 1;
        $lastNum = min(($this->_confArray["curPage"] * $this->_itemsPerPage),$totalNum);
        
        if($totalPage > 1){
            $result .= '<p class="amount amount--has-pages">';
            $result .= $this->__('Showing %s - %s of %s', $firstNum, $lastNum,  $totalNum);
        }else{
            $result .= '<p class="amount amount--no-pages">';
            $result .= '<strong>'.$this->__('Showing %s Item(s)', $totalNum) .'</strong>';
        }
        $result .= '</p>';
        $result .= '</div>';//close count-container

        $result .= '<div class="pages"><strong>Page:</strong>';
        $result .= '<ol>';

        //pager
        if($previous >= 1){
            $result .= '<li><a class="previous i-previous" href="'.$this->removeRequestParam("p").'&p='.(string)$previous.'" title="Previous">Prev</a></li>';
        }
                        
        $pages = Mage::helper('expertrec_recommendation')
                    ->getPages($this->_confArray["curPage"],$totalNum,$this->_displayPages,$this->_itemsPerPage); 
        if(count($pages) > 1){
            foreach ($pages as $page){
                if($page == $this->_confArray["curPage"]){
                    $result .= '<li class="current">'.(string)($this->_confArray["curPage"]).'</li>';
                }else{
                    $result .= '<li><a href="'.$this->removeRequestParam("p").'&p='.(string)$page.'">'.(string)$page.'</a></li>';
                }
            }
        }

        $next = $this->_confArray["curPage"]+1;
        if($next <= $totalPage){
            $result .= '<li><a class="next i-next" href="'.$this->removeRequestParam("p").'&p='.(string)$next.'" title="Next">Next</a></li>';
        }

        $result .= '</ol>';
        $result .= '</div>';//close pages
        $result .= '</div>';//close pager
        $result .= '</div>';//close toolbar

        return $result;
    }

    protected function getGridProductsDiv($data){
        $confArray = $this->_confArray;

        $result = '<ul class="products-grid products-grid--max-3-col">';
       
        foreach ($data as $item) {
            if(!isset($item["entity_id"]) && !isset($item["title"]) && !isset($item["url"]) && !isset($item["image_url"])){
                continue;
            }
            $msrp = "";
            $price = "";
            $compareUrl = Mage::helper('expertrec_recommendation')->getProductCompareUrl($item['entity_id']);
            $wishlistUrl = Mage::helper('expertrec_recommendation')->getWishlistUrl($item['entity_id']);
            $cartUrl = Mage::helper('expertrec_recommendation')->getAddToCartUrl($item['entity_id']);
            
            if(!empty($item["msrp"]) && $item["msrp"] != "None" && $item["msrp"] != "0"){
                $msrp =$item['msrp'];
            }
            if(!empty($item["price"]) && $item["price"] != "None" && $item["price"] != "0"){
                $price = $item['price'];
            }  

            if($this->_convertPrice){
                $price = Mage::helper('expertrec_recommendation')->getFormatCurrency($price,$item['entity_id'],$this->_fetchPrice);
                $msrp =  Mage::helper('expertrec_recommendation')->getFormatCurrency($msrp,$item['entity_id'],$this->_fetchPrice);
            }else{
                $price = Mage::helper('expertrec_recommendation')->getFormatPrice($price);
                $msrp =  Mage::helper('expertrec_recommendation')->getFormatPrice($msrp);
            }
            
            $result .= '<li class="item last">';
            $result .= '<a href="'.$item["url"].'" title="'.$item['title'].'" class="product-image">';
            $result .= '<img id="product-collection-image-'.$item['entity_id'].'" src="'.$item['image_url'].'" alt="'.$item['title'].'">';
            $result .= '</a>';
            $result .= '<div class="product-info" style="padding-bottom: 75px; min-height: 133px;"><h2 class="product-name">';
            $result .= '<a href="'.$item["url"].'" title="'.$item['title'].'">'.$item['title'].'</a>';
            $result .= '</h2>';
            $result .= '<div class="price-box">';
            if(!empty($msrp)){
                $result .= '<p class="old-price">';
                $result .= '<span class="price-label">Regular Price:</span>';
                $result .= '<span class="price" id="old-price-'.$item['entity_id'].'">'.$msrp.'</span>';
                $result .= '</p>';

                $result .= '<p class="special-price">';
                $result .= '<span class="price-label">Special Price</span>';
                $result .= '<span class="price" id="product-price-'.$item['entity_id'].'">'.$price.'</span>';
                $result .= '</p>';
            }else{
                $result .= '<span class="regular-price" id="product-price-'.$item['entity_id'].'"><span class="price">'.$price.'</span></span>';
            }
            $result .= '</div>';
            $result .= '<div class="actions">';

            if(!empty($cartUrl)){
                $result .= '<button type="button" title="Add to Cart" class="button btn-cart" onclick="setLocation(\''.$cartUrl.'\')">';
                $result .= '<span><span>Add to Cart</span></span>';
                $result .= '</button>';
            }
            $result .= '<ul class="add-to-links">';

            if(!empty($wishlistUrl)){
                $result .= '<li><a href="'.$wishlistUrl.'" class="link-wishlist"> <i class="fa fa-heart" aria-hidden="true"></i> Wishlist</a></li>';
            }
            if(!empty($compareUrl)){
                $result .= '<li><span class="separator">|</span>'; 
                $result .= '<a href="'.$compareUrl.'" class="link-compare"> <i class="fa fa-balance-scale" aria-hidden="true"></i> Compare</a>';
                $result .= '</li>';
            }
            $result .= '</ul>';    //add to links
            $result .= '</div>';  // actions div
            $result .= '</div>'; //product-info div
            $result .= '</li>';
                                           
        }
        $result .= '</ul>';

        return $result;
    }

    protected function getListProductsDiv($data){
        $confArray = $this->_confArray;

        $result = '<ol class="products-list" id="products-list">';
        $index = 1;
        $data_size = count($data);
        foreach ($data as $item) {
            if(!isset($item["entity_id"]) && !isset($item["title"]) && !isset($item["url"]) && !isset($item["image_url"])){
                continue;
            }
            $msrp = "";
            $price = "";
            $compareUrl = Mage::helper('expertrec_recommendation')->getProductCompareUrl($item['entity_id']);
            $wishlistUrl = Mage::helper('expertrec_recommendation')->getWishlistUrl($item['entity_id']);
            $cartUrl = Mage::helper('expertrec_recommendation')->getAddToCartUrl($item['entity_id']);
            $description = isset($item['desc']) ? $item['desc'] : (Mage::helper('expertrec_recommendation')->getProductDescription($item['entity_id']));
            
            if(!empty($item["msrp"]) && $item["msrp"] != "None" && $item["msrp"] != "0"){
                $msrp =$item['msrp'];
            }
            if(!empty($item["price"]) && $item["price"] != "None" && $item["price"] != "0"){
                $price = $item['price'];
            }  

            if($this->_convertPrice){
                $price = Mage::helper('expertrec_recommendation')->getFormatCurrency($price,$item['entity_id'],$this->_fetchPrice);
                $msrp =  Mage::helper('expertrec_recommendation')->getFormatCurrency($msrp,$item['entity_id'],$this->_fetchPrice);
            }else{
                $price = Mage::helper('expertrec_recommendation')->getFormatPrice($price);
                $msrp =  Mage::helper('expertrec_recommendation')->getFormatPrice($msrp);
            }

            $odd_even = $data_size == $index ? 'last ' : '';

            $odd_even .= ($index % 2) == 0 ? 'even' : 'odd';

            $result .= '<li class="item '.$odd_even.'">';
            $result .= '<a href="'.$item["url"].'" title="'.$item['title'].'" class="product-image">';
            $result .= '<img id="product-collection-image-'.$item['entity_id'].'" src="'.$item['image_url'].'" alt="'.$item['title'].'">';
            $result .= '</a>';
            $result .= '<div class="product-shop">';
            $result .= '<div class="f-fix">';
            $result .= '<div class="product-primary">';
            $result .= '<h2 class="product-name">';
            $result .= '<a href="'.$item["url"].'" title="'.$item['title'].'">'.$item['title'].'</a>';
            $result .= '</h2>';
            $result .= '</div>'; //product-primary
            $result .= '<div class="product-secondary">';
            $result .= '<div class="price-box">';
            if(!empty($msrp)){
                $result .= '<p class="old-price">';
                $result .= '<span class="price-label">Regular Price:</span>';
                $result .= '<span class="price" id="old-price-'.$item['entity_id'].'">'.$msrp.'</span>';
                $result .= '</p>';

                $result .= '<p class="special-price">';
                $result .= '<span class="price-label">Special Price</span>';
                $result .= '<span class="price" id="product-price-'.$item['entity_id'].'">'.$price.'</span>';
                $result .= '</p>';
            }else{
                $result .= '<span class="regular-price" id="product-price-'.$item['entity_id'].'"><span class="price">'.$price.'</span></span>';
            }
            $result .= '</div>'; //price-box
            $result .= '</div>'; //product-secondary
            $result .= '<div class="product-secondary">';
            $result .= '<p class="action">';

            if(!empty($cartUrl)){
                $result .= '<button type="button" title="Add to Cart" class="button btn-cart" onclick="setLocation(\''.$cartUrl.'\')">';
                $result .= '<span><span>Add to Cart</span></span>';
                $result .= '</button>';
            }

            $result .= '</p>';
            $result .= '<ul class="add-to-links">';

            if(!empty($wishlistUrl)){
                $result .= '<li><a href="'.$wishlistUrl.'" class="link-wishlist"> <i class="fa fa-heart" aria-hidden="true"></i> Wishlist</a></li>';
            }
            if(!empty($compareUrl)){
                $result .= '<li><span class="separator">|</span>'; 
                $result .= '<a href="'.$compareUrl.'" class="link-compare"> <i class="fa fa-balance-scale" aria-hidden="true"></i> Compare</a>';
                $result .= '</li>';
            }
            $result .= '</ul>';    //add to links
            $result .= '</div>'; //product-secondary
            $result .= '<div class="desc std">'.$description;
            $result .= '<a href="'.$item["url"].'" title="'.$item['title'].'" class="link-learn">Learn More</a>';
            $result .= '</div>'; //desc
            $result .= '</div>'; // f-fix
            $result .= '</div>'; //product-shop
            $result .= '</li>';

            $index = $index + 1;
        }

        $result .= '</ol>';

        return $result;
    }

    /*
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
        return $url;
    }

    /*
    * @param string facet-header
    * @param string facet-label
    * @return string class
    */
    protected function getFacetClass($fhead,$facetLabel){
        $confArray = $this->_confArray;

        $class = $fhead == "category" ? "expertrec-filter-cat" : "expertrec-filter-attr";
        $filter = isset($confArray["filters"][$fhead]) ? $confArray["filters"][$fhead] : '';
        $label = str_replace(" ","+",$facetLabel);

        if(!empty($filter)){
            $att = preg_split('/(%2C|,)/', $filter);
            if(in_array($label, $att)){
                $class .= '-selected';
            }
        }

        return $class;
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

    protected function getNavigationState(){
        $result = '';
        $confArray = $this->_confArray;
        
        try{
            if(!empty($confArray["filters"])){
                $filters = $confArray["filters"];
                $result .= '<div class="currently">';
                $result .= '<p class="block-subtitle">'.$this->__('Currently Shopping by:').'</p>';
                $result .= '<ol>';
                foreach ($filters as $fKey => $fValue){
                    $result .= '<li>';
                    $result .= '<span class="label">'.$this->__(str_replace('_', ' ', $fKey)).':</span>';
                    $listSubFilter = preg_split('/(%2C|,)/', $fValue);

                    if(in_array($fKey, $this->_singleSelectFilters) || count($listSubFilter) == 1){
                        $result .= '<span class="value">'.str_replace('+', ' ', $listSubFilter[0]).'</span>';
                        $result .= '<a  class="btn-remove" href="'.$this->removeRequestParam($fKey).'" title="'.$this->__('Remove This Item').'"><i class="fa fa-times" aria-hidden="true"></i></a>';
                    }else{
                        foreach ($listSubFilter as $sfKey) {
                            $remainSubFilterList = array_diff($listSubFilter, array($sfKey));

                            $result .= '<span class="multivalue">'.str_replace('+', ' ', $sfKey).'</span>';
                            $result .= '<a class="btn-remove-inline" href="'.$this->getClearLinkUrl($fKey,$remainSubFilterList).'" title="'.$this->__('Remove This Item').'">';
                            $result .= '<i class="fa fa-times-circle" aria-hidden="true"></i>';
                            $result .= '</a>';
                            $result .= '</span>';
                        }
                    }

                    $result .= '</li>';
                }
                $result .= '</ol>';
                $result .= '</div>';
                $result .= '<div class="actions">';
                $result .= '<a href="'.strtok($confArray["url"], '?').'?q='.$confArray["query"].'">'.$this->__('Clear All').'</a>';
                $result .= '</div>';
            }
        }catch(Exception $e){
             Mage::getSingleton('expertrec_recommendation/log')->log( "Error in getting navigation state: ".$e->getMessage());
        }

        return $result;
    }

    /*
    * @param Array facetlist
    * @return html string
    */
    public function prepareFilterLayout($facets){
        $result = '<div class="listing-filter">';
        $result .= '<a href="javascript:;" onclick="erOpenFilter()">';
        $result .= '<i class="fa fa-filter" aria-hidden="true"></i>&nbsp; Filter By </a>';
        $result .= '</div>';
        $result .= '<div id="er_Filteroption" class="overlay-filter">';
        $result .= '<div class="block block-layered-nav expertrec-overflow-scroll-enabled">';
        $result .= '<div class="block-title">';
        $result .= '<strong><span>'.$this->__('Shop By').'</span></strong>';
        $result .= '</div>';
        $result .= '<div class="block-content toggle-content">';
        $result .= '<div style="clear:both;"></div>';
        $result .= '<p class="block-subtitle mobile-only"><i class="fa fa-filter" aria-hidden="true"></i> &nbsp; Filter By</p>';
        $result .= '<dl id="narrow-by-list">';
        
        foreach ($facets as $fKey => $fValue) {
            if(count($fValue)){
                
                $result .= '<dt>'.$fKey.'</dt>';
                $result .= '<dd class="filter-search">';
                    
                $result .= '<ol>';
                foreach ($fValue as $facetLabel => $facetCount) {
                    $result .= '<li class="'.$this->getFacetClass($fKey,$facetLabel).'">';
                    if($this->_isAjax){
                        $result .= '<a href="javascript:;">';
                        $result .= '<input class="ajaxUrl" type="hidden" value="'.$this->getFacetUrl($fKey,$facetLabel).'">';
                    }else{
                        $result .= '<a href="'.$this->getFacetUrl($fKey,$facetLabel).'">';
                    }
                    if(!in_array($fKey, $this->_singleSelectFilters)){
                        $result .= '<i class="fa fa-square-o" aria-hidden="true"></i>';
                        $result .= '<i class="fa fa-check-square-o" aria-hidden="true"></i>';
                    }
                    $result .= '&nbsp;'.ucwords(strip_tags($facetLabel));
                            
                    $result .= ' <span class="count">('.$facetCount.')</span>';
                    $result .= '</a>';
                    $result .= '</li>';
                }

                $result .= '</ol>';
                $result .= '</dd>';
            }
            
        }
        
        $result .= '</dl>';
        $result .= '<script type="text/javascript">decorateDataList(\'narrow-by-list\')</script>';
        $result .= '<div class="mobile-only mobile-filter-closer" onclick="erCloseFilter()"> <i class="fa fa-times" aria-hidden="true"></i> &nbsp; Close Filter </div>';

        $result .= '</div>'; // block-content close
        $result .= '</div>'; // block-layered-nav close
        $result .= '</div>'; // er_Filteroption close

        return $result;
    }


    /*
    * @param list of products
    * @return html string
    */

    public function prepareLayout($data){
        $confArray = $this->_confArray;

        $finalResult = $this->getNavigationState();

        if(!empty($confArray["count"])){
            $toolbar = $this->getToolbarDiv();
            $finalResult .= '<div class="category-products">';
             
            $finalResult .= $toolbar;

            if($confArray["mode"] == 'grid'){
                $finalResult .= $this->getGridProductsDiv($data);
                $finalResult .= '<script type="text/javascript">decorateGeneric($$("ul.products-grid"), ["odd","even","first","last"])</script>';
            }else{
                $finalResult .= $this->getListProductsDiv($data);
                $finalResult .= '<script type="text/javascript">decorateList("products-list", "none-recursive")</script>';
            }
            
            $finalResult .= '<div class="toolbar-bottom">';
            $finalResult .= $toolbar;
            $finalResult .= '</div>'; 
            $finalResult .= '</div>';
        }else{
            $noResult = $this->__('Your search returns no results.');
            $finalResult .= '<p class="note-msg">'.$noResult.'</p>';
        }

        return $finalResult;
        
    }
}

?>
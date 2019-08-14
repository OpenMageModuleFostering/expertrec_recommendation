<?php

class Expertrec_Recommendation_Helper_Autocompletehelper extends Mage_Core_Helper_Abstract{

	const SEARCH_LIST_API = 'search/api';
  const SEARCH_FACET_LIST = 'search/facets_list';
  const SEARCH_ITEMS_PER_PAGE = 'search/items_per_page';

  protected $_facetList = array();
  protected $_itemsPerPage = 20;

  protected function getFormatUrl($url){
    $splitedUrl = explode("?", $url);
    $newUrl = preg_replace('/\/ajax/',"",$splitedUrl[0]);
    if(count($splitedUrl) == 2){
      $newUrl .= '?'.$splitedUrl[1];
    }
    return $newUrl;
  }

  protected function getSearchApi($confArray){
      $searchApi = "";
      try{
        $itemsPerPage = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_ITEMS_PER_PAGE);
        $storeSearchApi = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_LIST_API);
      }catch(Exception $e){
          Mage::getSingleton('expertrec_recommendation/log')->log('Autocompletehelper::getting searchApi exception: '.$e->getMessage());
      }
      if(!empty($itemsPerPage)){
        $this->_itemsPerPage = $itemsPerPage;
      }

      if(!empty($storeSearchApi)){
          $searchApi = $storeSearchApi;
          $previous = $confArray["curPage"]-1;
          $searchApi .= "?q=".urlencode($confArray["query"])."&page=".$previous."&size=".$this->_itemsPerPage;
          $searchApi .= "&fq=order:".urlencode($confArray["order"]);
          if(isset($confArray['filters'])){
            $filters = $confArray['filters'];
            foreach ($filters as $fkey => $fvalue) {
               $searchApi .= "&fq=".$fkey.":".urlencode($fvalue);
            }
          }
      }

      return $searchApi;
  }

  public function getItemsPerPage(){
     return (int)$this->_itemsPerPage;
  }

	public function prepareLayer($requestParams){
		  $confArray = array();
      $filters = array();
      $resultData = array();
      try{
        $confArray["url"] = $this->getFormatUrl(Mage::helper('core/url')->getCurrentUrl());
        $confArray["query"] = htmlentities($requestParams['q'], ENT_QUOTES);

        $confArray["curPage"] = isset($requestParams['p']) ? (int)$requestParams['p'] : 1;

        $mode = isset($requestParams['mode']) ? $requestParams['mode'] : 'grid';
        $confArray["mode"] = $mode == 'list' ? $mode : 'grid';
        $confArray["order"] = isset($requestParams["order"]) ? $requestParams["order"] : 'relevance';

        try{
          $facetList = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_FACET_LIST);
          $this->_facetList = !empty($facetList) ? explode(",", $facetList) : $this->_facetList;
        }catch(Exception $ex){
            Mage::getSingleton('expertrec_recommendation/log')->log('Autocompletehelper::getting facetlist from db exception: '.$ex->getMessage());
        }

        if(count($this->_facetList) > 0){
            foreach($this->_facetList as $facet){
                $filter = isset($requestParams[$facet]) ? $requestParams[$facet] : '';
                if(!empty($filter)){
                  $filters[$facet] = str_replace(" ","+",$filter);
                }
            }
            $confArray['filters'] = $filters;
        }
        
        //setup search url
        $searchApi = $this->getSearchApi($confArray);
        //Mage::getSingleton('expertrec_recommendation/log')->log('search url: '.$searchApi);

        if(!empty($searchApi)){
            
            //sending request
            $resp = Mage::helper('expertrec_recommendation')->sendCurl($searchApi);
            $response_json = json_decode($resp,true);
            
            if(isset($response_json["res"]) && isset($response_json["res"]["count"])){
              $confArray["count"] = (int)$response_json["res"]["count"];
            }
            
            //Initiate search layout
            $layoutHelper = Mage::helper('expertrec_recommendation/search_layout')->init($confArray);
            
           	$searchListData = $response_json["results"];
           	$searchListHtml = $layoutHelper->prepareLayout($searchListData);
            
            if(isset($confArray['filters'])){
              $facetData = $response_json["facets"];
              $facetNavHtml = $layoutHelper->prepareFilterLayout($facetData);
            }else{
              $facetNavHtml = '';
            }
            
            $resultData["listHtml"] = $searchListHtml;
            $resultData["facetHtml"] = $facetNavHtml;

            
        }

      }catch (Exception $e) {
          Mage::getSingleton('expertrec_recommendation/log')->log('Autocompletehelper::prepareLayer exception: '.$e->getMessage());
          $resultData["listHtml"] = '<div style="color:red;><span style="font-weight:bold;">Error: </span>Oops. Something went wrong. Please try again later.</div>';
          $resultData["facetHtml"] = '';
      }

      return $resultData;
	}

}

?>
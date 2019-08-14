<?php

class Expertrec_Recommendation_Helper_Searchhelper extends Mage_Core_Helper_Abstract{

	const SEARCH_LIST_API = 'search/api';
  const SEARCH_FACET_LIST = 'search/facets_list';
  const SEARCH_LIST_ENABLE = 'search/enable';  

  protected $_facetList = array();
  protected $_resultIds = array();

  public function getResultIds(){
    return $this->_resultIds;
  }

  protected function getFormatUrl($url){
    $splitedUrl = explode("?", $url);
    $newUrl = preg_replace('/\/ajax/',"",$splitedUrl[0]);
    if(count($splitedUrl) == 2){
      $newUrl .= '?'.$splitedUrl[1];
    }

    return $newUrl;
  }

  public function getSearchEnable(){
     $searchEnable = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_LIST_ENABLE);
     return $searchEnable;
  }

  protected function getSearchApi($confArray){

      $searchApi = "";

      try{
        $storeSearchApi = Mage::helper('expertrec_recommendation')->getConfig(self::SEARCH_LIST_API);
      }catch(Exception $e){
          Mage::getSingleton('expertrec_recommendation/log')->log('Searchhelper::getting searchApi exception: '.$e->getMessage());
      }      

      if(!empty($storeSearchApi)){
          $searchApi = $storeSearchApi;        
          
          // searching for cat and other facets 
          // if(isset($confArray["facetPortion"]))
          // {
          //   // $confArray["cat"] is to add cat and facets in searchapi
          //   $searchApi .= "?q=".urlencode($confArray["query"]).$confArray["facetPortion"]."&page=0&src=magento";
          // }
          
          $searchApi .= "?q=".urlencode($confArray["query"])."&page=0&src=magento";

          $splitedUrl = explode("?",html_entity_decode($confArray["url"]));

          if(count($splitedUrl)==2)
          {
            $searchApi .= "&oq=".urlencode($splitedUrl[1]);        

          }         
      }

      Mage::getSingleton('expertrec_recommendation/log')->log("The search URL sent to Expertrec BE is : ".print_r($searchApi,1),null);

      //print_r($searchApi);

      return $searchApi;
  }

  public function getNonQueryUrlParameters($searchUrl)
  {
    //checking for cat and facets 
    $facetPortion = ""; 
    $limitQuery = "";

    if(!strpos($searchUrl,"?q")){

          $parsedUrl = parse_url($searchUrl);
          $newQuery = array();
          $categoryArr = array();
          $query1 = "";

          $getCategory = explode("&",html_entity_decode($parsedUrl["query"]));          

          // spliting queries
          foreach ($getCategory as $key => $value) {
              list($key1, $val) = explode('=', $value);

              //Excluding query term and cifr from categories
              if(!in_array($key1,array("q","cifr","limit","dir","order")))
              {
                $categoryArr[$key1] = $val;
              }
              
          }

          // getting all attributes
          $attributes = Mage::getModel('eav/entity_attribute_option')->getCollection()->setStoreFilter(0)->join('attribute','attribute.attribute_id=main_table.attribute_id', 'attribute_code');

          // changing id to name for cat and facets
          if(isset($categoryArr))
          {
              foreach ($categoryArr as $key => $value) {

                if($key == 'cat')
                {
                      $id = $value;
                      $cat = Mage::getModel('catalog/category')->load($id);
                      $newQuery['category'] = $cat->getName();
                }
                else
                {
                  //For all attributes not under the sortables, use attribute table
                  if(!in_array($key,array("limit","order","dir")))
                  {
                      $attrMatched = false;

                      foreach ($attributes as $attribute) {
                        if ($attribute->getOptionId()==$value) {
                          $newQuery[$key] = $attribute->getValue();
                          $attrMatched = true;
                          }
                        }

                      //If the attribute is not a category and it is not matched, just pass it to BE.
                      if(!$attrMatched)
                      {
                        $newQuery[$key] = $value;
                      }
                  }
                  else if($key == 'limit')
                  {
                    $limitQuery = "size=".urlencode($value);
                  }
                  else if($key == 'order')
                  {
                    $newQuery[$key] = $value." ".$categoryArr["dir"];
                  }    
                }
            }

           // changing to normal array
            foreach($newQuery as $k=>$v)
            {
              $finalQuery[] = $k.":".urlencode($v);
            }

            // implode with &fq as in url 
            if(isset($finalQuery))
            {
              $query1 = "&fq=".implode("&fq=",$finalQuery);
            }    
            

            if(strlen($limitQuery) > 0)
            {
              $query1 .= "&".$limitQuery;
            }

            $facetPortion = $query1;   

          }
          
        }

      return $facetPortion;      
  }

  public function prepareLayer($requestParams){
      $confArray = array();
      $filters = array();

      try{
        $confArray["url"] = $this->getFormatUrl(Mage::helper('core/url')->getCurrentUrl(),$confArray);
        $confArray["query"] = $requestParams['q'];   
        //$confArray["facetPortion"] = $this->getNonQueryUrlParameters($confArray["url"]);

        //setup search url
        $searchApi = $this->getSearchApi($confArray);      

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
            $itemIds = array();

            if(isset($searchListData))
            {
              Mage::getSingleton('expertrec_recommendation/log')->log("Number of items returned by Expertrec BE ".count($searchListData));

              foreach ($searchListData as $item) {
                $itemIds[] = $item['entity_id'];
              }
            }
           
            $this->_resultIds = $itemIds;
          }

      }catch (Exception $e) {
          Mage::getSingleton('expertrec_recommendation/log')->log('Searchhelper::prepareLayer exception: '.$e->getMessage());
         
      }      
    }
    
}
?>
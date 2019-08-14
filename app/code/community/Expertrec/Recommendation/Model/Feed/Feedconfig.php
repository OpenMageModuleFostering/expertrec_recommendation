<?php

class Expertrec_Recommendation_Model_Feed_Feedconfig {

    var $pageSize=500;
    var $imageWidth = 250;
    var $imageHeight = 250;
    var $generateImage=false;
    var $websiteId=0;
    var $storeId=0;
    var $feedFilePath='';
    var $pageStart=1;
    var $pageEnd =0;

    protected $secret;
    protected $merchant_id;

    public function setImageHeight($height=250) {
        $this->imageHeight = (int)$height;
        return $this;
    }

    public function setImageWidth($wid=250) {
        $this->imageWidth = (int)$wid;
        return $this;
    }

    public function setGenerateImage($img=true){
        $this->generateImage = $img;
        return $this;
    }

    public function setWebsiteId($wid=0){
        $this->websiteId = (int)$wid;
        return $this;
    }

    public function setStoreId($sid=0){
        $this->storeId = (int)$sid;
        return $this;
    }

    public function setFeedFilePath($path=''){
        $this->feedFilePath=$path;
        return $this;
    }

    public function setSecret($pwd=null){
        $this->secret = $pwd;
        return $this;
    }

    public function getSecret(){
        return $this->secret;
    }

    public function setMerchantId($mid=''){
      $this->merchant_id = (string)$mid;  
      return $this;
    }

    public function getMerchantId(){
      return $this->merchant_id;
    }

    public function setPageStart($st=1){
        $this->pageStart = (int)$st;
        return $this;
    }

    public function setPageEnd($en=0){
        $this->pageEnd = (int)$en;
        return $this;
    }
    public function setPageSize($size=500) {
        $this->pageSize = (int)$size;
        return $this;
    }
}

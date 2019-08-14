<?php 
  /**
  **Validate Password class
  **
  ** @category    Expertrec
  ** @package     Expertrec_Recommendation
  ** @copyright   Copyright (c) 2016 CloudInfra LLC.       
  **/
  class Expertrec_Recommendation_Model_Validate {
    const CONFIG_SECRET  = 'expertrec/general/secret';
    const CONFIG_MID = 'expertrec/general/mid';
    const DEFAULT_SECRET = '519545f988a1c619ad9927f704914753';
    /*
    *@param password
    *@return boolean
    */
    
    public function checkPassword($Password){
      if($Password == '' || $Password == self::DEFAULT_SECRET || $Password != $this->getPassword()){
        return false;
      }
      return true;
    }

    public function getPassword(){
      $storedPwd = Mage::getSingleton('expertrec_recommendation/feed_feedconfig')->getSecret();
      if(empty($storedPwd)){
        $storedPwd = base64_decode(Mage::getStoreConfig(self::CONFIG_SECRET));
        Mage::getSingleton('expertrec_recommendation/feed_feedconfig')
          ->setSecret($storedPwd);
      }
      return $storedPwd;
    }

    public function getSiteKey(){
      $storedMid = Mage::getSingleton('expertrec_recommendation/feed_feedconfig')->getMerchantId();
      if(empty($storedMid)){
        $storedMid = Mage::getStoreConfig(self::CONFIG_MID);
        Mage::getSingleton('expertrec_recommendation/feed_feedconfig')
          ->setMerchantId($storedMid);
      }
      return $storedMid;
    }
  }
?>
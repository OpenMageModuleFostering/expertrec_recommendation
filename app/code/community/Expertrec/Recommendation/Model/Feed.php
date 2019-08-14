<?php

    /**
     * Feed generator class
     *
     * @category    Expertrec
     * @package     Expertrec_Recommendation
     * @copyright   Copyright (c) 2016 CloudInfra LLC.
     */
    class Expertrec_Recommendation_Model_Feed {
        protected $_feedName='expertrec_feed';

        public function exportFeeds() {
            try {
                $status = false;
                //load Website by given website id
                $website = Mage::getModel('core/website')->load(Mage::getSingleton('expertrec_recommendation/feed_feedconfig')->websiteId);
                //load Store by given store id
                $oStore =  Mage::getModel('core/store')->load(Mage::getSingleton('expertrec_recommendation/feed_feedconfig')->storeId);
                if(isset($website) && isset($oStore)){
                    //Appending website name to feedName
                    $this->_feedName .= '_'.str_replace(" ", "", (string)$website->getName());
                    //Start feed creation process
                    $status = Mage::getSingleton('expertrec_recommendation/feed_feedcreator')->export($website,$oStore,$this->_feedName);
                    Mage::getSingleton('expertrec_recommendation/log')->log('Expertrec Datafeeder finished creating file');
                }else{
                    Mage::getSingleton('expertrec_recommendation/log')->log("Website id and Store id are not set",Zend_Log::ERR);
                }
                return $status;
            } catch (Exception $e) {
                Mage::getSingleton('expertrec_recommendation/log')->log( "Exception during feed generation: ".$e->getMessage(),Zend_Log::ERR);
                return false;
            }
        }
    }
?>
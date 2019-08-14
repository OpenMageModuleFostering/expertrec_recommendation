<?php
    /*
    external links to magento.expertrec.com and support mail action in admin-panel dashboard
    */
    class Expertrec_Recommendation_Adminhtml_IndexController extends Mage_Core_Controller_Front_Action
    {
    	const MERCHANT_ID  = 'expertrec/general/mid';
        public function indexAction(){
        	$mid = Mage::getStoreConfig(self::MERCHANT_ID);
        	$url = 'https://magento.expertrec.com/home?mid='.$mid;
            return $this->_redirectUrl($url);
        }
        // added support email
        public function mailAction(){
        	return $this->_redirectUrl("mailto:support@expertrec.com?/");
        }
    }
?>

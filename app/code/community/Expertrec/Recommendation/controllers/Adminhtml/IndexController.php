<?php

class Expertrec_Recommendation_Adminhtml_IndexController extends Mage_Core_Controller_Front_Action
{

    public function indexAction(){
        return $this->_redirectUrl("https://magento.expertrec.com/autocomplete/signup");
    }
}
?>

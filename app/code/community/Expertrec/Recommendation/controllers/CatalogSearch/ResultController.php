<?php

require_once 'Mage/CatalogSearch/controllers/ResultController.php';

class Expertrec_Recommendation_CatalogSearch_ResultController extends Mage_CatalogSearch_ResultController
{

    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    public function ajaxAction(){
        $requestParams = Mage::app()->getRequest()->getParams();
        $resultData = Mage::helper('expertrec_recommendation/autocompletehelper')
                        ->prepareLayer($requestParams);
        header('Content-Type: application/json');
        echo json_encode($resultData);
    }
}

?>
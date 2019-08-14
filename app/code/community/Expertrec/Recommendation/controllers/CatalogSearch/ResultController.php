<?php

require_once 'Mage/CatalogSearch/controllers/ResultController.php';

class Expertrec_Recommendation_CatalogSearch_ResultController extends Mage_CatalogSearch_ResultController
{
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }    
}

?>
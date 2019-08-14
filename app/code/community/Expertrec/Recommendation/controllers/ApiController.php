<?php

class Expertrec_Recommendation_ApiController extends Mage_Core_Controller_Front_Action {
      const BUILD_NO = "1486454605";
      private $_password;

       //main function which loads the feed API
      public function infoAction()
      {
         //return array of all parameters sent
          $requestParams = Mage::app()->getRequest()->getParams(); 
          
          $Password = isset($requestParams['secret']) ? $requestParams['secret'] : '';
          
          // Check password. if invalid password, it will not proceed.
          if(!Mage::getModel('expertrec_recommendation/validate')->checkPassword($Password)){
            die('ERROR: The specified password is invalid.');
          }

         Mage::register('buildno',self::BUILD_NO);          
         Mage::register('secret',Mage::getModel('expertrec_recommendation/validate')->getPassword());        
         $this->loadLayout();
         $this->renderLayout();
      }  

}
?>
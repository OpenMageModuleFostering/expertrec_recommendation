<?php

/**
 * Class to make the request to the Expertrec api
 *
 * @category Expertrec
 * @package Expertrec_Recommendation
 * 
 */
class Expertrec_Recommendation_Model_Api_Request extends Varien_Object {

    const LOG_URL = "https://log.expertrec.com/v2/collect/";

    /**
     * end url where the request is firing
     */
    protected $url = "";

    protected $userId = "";

    protected $prepareRequestStatus=false;

    /**
     * http method
     */
    protected $method = Zend_Http_Client::GET;


    protected $headers = array();


    public function setHeaders(array $headers) {
        $this->headers = $headers;
        return $this;
    }


    public function setHeader($header,$value) {
        $this->headers[$header] = $value;
        return $this;
    }

    
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }


    public function setUserId($uid){
        $this->userId = $uid;
        return $this;
    }

    
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    public function setPrepareRequestStatus($status=false){
        $this->prepareRequestStatus = $status;
        return $this;
    }


    /**
     * Prepare Url and User_id before sending request
     */

    public function prepareRequest(){
        $uid = array_key_exists('ci_id', $_COOKIE) ? $_COOKIE['ci_id'] : 'Not_Found';

        $mid = Mage::getModel('expertrec_recommendation/validate')->getSiteKey();

        if (empty($mid)) {
            Mage::getSingleton('expertrec_recommendation/log')->log("merchant_id missing",Zend_Log::ERR);
            $this->setPrepareRequestStatus(false);
            return $this;
        }


        $this->setUserId($uid);

        $this->setUrl(self::LOG_URL.$mid);
        
        $this->setPrepareRequestStatus(true);

        return $this;
    }

    /**
     * @return Zend_Http_Client
     */
    protected function getRestClient(){

        $request = new Zend_Http_Client();
        $request->setUri($this->url)
            ->setHeaders($this->headers)
            ->setMethod($this->method)
            ->setParameterGet("user",$this->userId);

        if($this->method == Zend_Http_Client::GET) {
            $request->setParameterGet($this->getData());
        } else {
            $request->setParameterPost($this->getData());
        }
        return $request;
    }



    /**
     * Method which will make the api call
     *
     * @return boolean
     */
    public function sendRequest(){
        $status = 0;

        //if mid and uid are not set, it will not proceed
        if(!$this->prepareRequestStatus){
            return false;
        }

        try {
            $request = $this->getRestClient();
            $response = $request->request();

            //set status code
            $status = (int)$response->getStatus();

        } catch (Zend_Http_Client_Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log(sprintf($this->url ." failed because HTTP error: %s", $e->getMessage()),Zend_Log::ERR);
            return false;
        }

        if($status >= 200 && $status < 400){
            return true;
        }
        return false;
    }


}

?>
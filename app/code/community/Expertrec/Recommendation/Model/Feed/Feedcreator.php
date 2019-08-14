<?php

/**
 * Feed generator class
 *
 * @category    Expertrec
 * @package     Expertrec_Recommendation
 * @copyright   Copyright (c) 2016 CloudInfra LLC.
 * @license     http://opensource.org/licenses/osl-3.0.php
 */
class Expertrec_Recommendation_Model_Feed_Feedcreator {

    const CONFIG_HEADERS  = 'expertrec/general/headers';
    const CONFIG_FILTERS = 'expertrec/general/filters';
    protected $_oWriter;
    protected $_ofields=array();

    public function export(Mage_Core_Model_Website $website, Mage_Core_Model_Store $oStore, $vFeedname) {

        $logger = Mage::getSingleton('expertrec_recommendation/log');

        try{
            $logger->log("Beginning $vFeedname export for store #".$oStore->getId()." - ".$oStore->getName());
            $logger->logMemoryUsage();
            // $logger->log("Initialising file writers...");
            $this->initWriters($oStore, $vFeedname);

            // Prepare the csv file header
            // $logger->log("Begin preparing header rows...");
            $logger->logMemoryUsage();

            //if any error occur during header writing, it will return false
            if(!$this->prepareHeaders()){
                return false;
            }

            // Initialise the formatter
            // $logger->log("Initialising Feed Formatter...");
            $formatter = Mage::getSingleton('expertrec_recommendation/feed_formatter');
            $formatter->init();
            // $logger->log("Initialised Feed Formatter.");

            $logger->logMemoryUsage();

            //get product collection
            $logger->log("Getting product collection for store#".$oStore->getId()." - ".$oStore->getName());

            $feedConfig = Mage::getSingleton('expertrec_recommendation/feed_feedconfig');

            $collection=Mage::getSingleton('expertrec_recommendation/feed_feedfilter')->addBasicFilter($website,$oStore)
                ->setPageSize($feedConfig->pageSize);

            $pageEnd = $feedConfig->pageEnd;
            $lastPageNumber = $collection->getLastPageNumber();

            if($pageEnd != 0 && $pageEnd < $lastPageNumber){
                $pages = $pageEnd;
            }else{
                $pages = $lastPageNumber;
            }
            $logger->log("Total no. of pages for which we are collecting feed in this reqeust: #".$pages);

            for($currentPage = $feedConfig->pageStart; $currentPage <= $pages; $currentPage++) {
                $logger->log("Collecting feed for page: #".$currentPage);
                $collection->setCurPage($currentPage);
                foreach ($collection as $product) {
                    
                    $aRow = $formatter->prepareRow($this->_ofields,$product);
                    
                    //writing data row
                    $this->_oWriter->writeDataRow($aRow);
                }
                $collection->clear();
            }
            
            $this->closeWriters();

            $logger->log("Finished $vFeedname data export for store #".$oStore->getId()." - ".$oStore->getName());
            $logger->logMemoryUsage();
            return true;
        }catch (Exception $e) {
            $logger->log("Error in feed creation:--".$e->getMessage());
            return false;
        }
    }


    protected function initWriters(Mage_Core_Model_Store $oStore, $vFeedname) {
                
        $oWriter = Mage::getModel('expertrec_recommendation/writer_csv')->init($oStore->getCode(), $vFeedname);
        if ($oWriter instanceof Expertrec_Recommendation_Model_Writer_Abstract) {
            $this->_oWriter = $oWriter;
        }
                    
        return $this;
    }

    protected function prepareHeaders() {
        try{
            $storedHeaders = Mage::getStoreConfig(self::CONFIG_HEADERS);
            $storedFilters = Mage::getStoreConfig(self::CONFIG_FILTERS);
            if (isset($storedHeaders)){
                $header = explode(',', $storedHeaders);
            }else{
                $header = array();
            }

            if(empty($header)){
                Mage::getSingleton('expertrec_recommendation/log')->log("Headers are not selected. Go to the info page,select headers and try again.");
                return false;
            }

            // checking for filters
            if(isset($storedFilters)){
                if (empty($storedFilters)){
                    $totalHeaders = $header;
                }
                else{
                    $filter = explode(',', $storedFilters);
                    $totalHeaders = array_merge($header,$filter);
                }
            }
            else{
                $totalHeaders = $header;
            }

            //setting header fields array to this, so we can use it later
            $this->_ofields = $totalHeaders;

            //writing header row
            $this->_oWriter->setHeader($totalHeaders)->writeHeaderRow();
            //$this->_oWriter->setHeader($filter)->writeHeaderRow();

        }catch (Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log("Error in writing header: ".$e->getMessage());
            return false;
        }

        return true;
    }


    /**
     * Closes the file writer once finished
     *
     * @return $this
     */
    protected function closeWriters() {
        $this->_oWriter->close();
        return $this;
    }


}
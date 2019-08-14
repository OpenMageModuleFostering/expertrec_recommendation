<?php

    class Expertrec_Recommendation_Helper_Suggestionhelper extends Mage_Core_Helper_Abstract{

        protected $_fileName='expertrec_sugg_feed';
        protected $_oWriter;
        protected $_ofields;

        public function exportPopularProducts() {
            try {
                $logger = Mage::getSingleton('expertrec_recommendation/log');
                $feedConfig = Mage::getSingleton('expertrec_recommendation/feed_feedconfig');
                $storeId =  $feedConfig->storeId;
                //load Store by given store id
                $oStore =  Mage::getModel('core/store')->load($storeId);
                if(!isset($oStore)){
                    $logger->log("Store id is not set");
                    return false;
                }
                // $logger->log("Beginning ".$this->_fileName." export for store #".$storeId);
                $logger->logMemoryUsage();
                // $logger->log("Initialising file writers...");
                $this->initWriters($oStore, $this->_fileName);
                // Prepare the csv file header
                // $logger->log("Begin preparing header rows...");
                //if any error occur during header writing, it will return false
                if(!$this->prepareHeaders()){
                    return false;
                }
                $collection = $this->getSuggestionCollection($storeId)->setPageSize($feedConfig->pageSize);
                $pageEnd = $feedConfig->pageEnd;
                $lastPageNumber = $collection->getLastPageNumber();
                if($pageEnd != 0 && $pageEnd < $lastPageNumber){
                    $pages = $pageEnd;
                }else{
                    $pages = $lastPageNumber;
                }
                $logger->log("Total no. of pages for which we are collecting popular products feed in this reqeust: #".$pages);
                for($currentPage = $feedConfig->pageStart; $currentPage <= $pages; $currentPage++) {
                    $logger->log("Collecting popular products feed for page: #".$currentPage);
                    $collection->setCurPage($currentPage);
                    foreach ($collection as $suggestion) {
                        $aRow = array();
                        foreach ($this->_ofields as $field) {
                            $fValue = $suggestion->getData($field);
                            if(empty($fValue)){
                                $fValue = 'None';
                            }
                            //$logger->log("key: ".$field." value: ".$fValue);
                            $aRow[$field] = $fValue;
                        }
                        //writing data row
                        $this->_oWriter->writeDataRow($aRow);
                    }
                    $collection->clear();
                }
                $this->closeWriters();
                $logger->log("Finished ".$this->_fileName." data export for store #".$storeId);
                $logger->logMemoryUsage();
                return true;
            }catch (Exception $e) {
                $logger->log( "Exception during suggestion feed generation: ".$e->getMessage());
                return false;
            }
        }

        protected function getSuggestionCollection($storeId){
            $collection = Mage::getResourceModel('catalogsearch/query_collection')
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);
            $collection->getSelect()->where('query_text != "__empty__"');
            return $collection;
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
                $this->_ofields = array('query_id','query_text','num_results','popularity','redirect',' synonym_for','is_active','is_processed','updated_at');
                //writing header row
                $this->_oWriter->setHeader($this->_ofields)->writeHeaderRow();
            }catch (Exception $e) {
                Mage::getSingleton('expertrec_recommendation/log')->log("Error in writing header: ".$e->getMessage());
                return false;
            }
            return true;
        }

        protected function closeWriters() {
            $this->_oWriter->close();
            return $this;
        }
    }
?>
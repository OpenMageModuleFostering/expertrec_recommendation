<?php

abstract class Expertrec_Recommendation_Model_Writer_Abstract extends Varien_Object {

    protected $_vFileExtension = 'csv';
    const FEED_PATH = '/Expertrec';

    public function init($vStoreCode, $vFeedname) {
        $this->setFilename($this->_getFilename($vStoreCode, $vFeedname));
    }

    /**
     * Writes the header row to the file where appropriate
     *
     * @return $this
     */
    abstract function writeHeaderRow();

    /**
     * Writes a data row to the file
     *
     * @param array $aData Data to write.  keys in this array must be the same as the keys in the setHeader array.
     * @return $this
     */
    abstract function writeDataRow($aRow);

    /**
     * Closes the file once finished.
     */
    abstract function close();

    protected function _getFilename($vStoreCode, $vFeedname) {
        $vFeedDir = "/tmp".self::FEED_PATH;
        if (!is_dir($vFeedDir)) {
            $vFeedDir_status=mkdir($vFeedDir);
            if(!$vFeedDir_status){
                Mage::getSingleton('expertrec_recommendation/log')->log("Feed export directory does not exist: ".$vFeedDir);
                return false;
            }
        }

        //Appending storecode,timestamp and file_extension in feedname
        $vtimestamp = (string)time();
        $vFileName = $vFeedDir.'/'.$vFeedname.'-'.$vStoreCode.'_'.$vtimestamp.'.'.$this->_vFileExtension;

        if (!file_exists($vFileName)) {
          touch($vFileName);
        }
        //setting filename to file config
        Mage::getSingleton('expertrec_recommendation/feed_feedconfig')->setFeedFilePath($vFileName);

        if (is_writable($vFileName)) {
            return $vFileName;
        } else {
            $this->_log("Feed file is not writable: ".$vFileName);
            return false;
        }

    }

}

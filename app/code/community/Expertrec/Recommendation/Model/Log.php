<?php

/**
 * Simple logging interface for this extension
 *
 * @category    Expertrec
 * @package     Expertrec_Recommendation
 * @copyright   Copyright (c) 2016 CloudInfra LLC.
 * @license     http://opensource.org/licenses/osl-3.0.php
 */
class Expertrec_Recommendation_Model_Log {

    // Name of the log file in var/log
    const LOG_FILE = 'expertrec_feed.log';
    const FEED_PATH = '/Expertrec';


    /**
     * Logging for Feed exporter
     * @param string $message
     * @param int $level  ZEND_LOG log level
     * @param boolean $bDeveloperModeOnly True to log only in Developer mode
     */
    public function log($message, $level = Zend_Log::INFO, $bDeveloperModeOnly = false) {
        
        try{
            $vFeedDir = Mage::getBaseDir().self::FEED_PATH;
            if (!is_dir($vFeedDir)) {
                $vFeedDir_status=mkdir($vFeedDir);
                if(!$vFeedDir_status){
                    return $this;
                }
            }
            $filename = $vFeedDir.'/'.self::LOG_FILE;
            // open file 
            $fd = fopen($filename, "a+"); 
            // append date/time to message 
            $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . (string)$message;  
            // write string 
            fwrite($fd, $str . "\n"); 
            // close file 
            fclose($fd);
        }catch(Exception $e){
            return $this;
        } 
            
    }

    /**
     * Logs the current php memory usage.
     *
     */
    public function logMemoryUsage() {
        $iCurrentKb = ceil(memory_get_usage(true) / 1024);
        $iPeakKb = ceil(memory_get_peak_usage(true) / 1024);
        $this->log("Memory Usage - Current (Kb): ".$iCurrentKb."   Peak (Kb): ".$iPeakKb, Zend_Log::DEBUG);
        return $this;
    }


}
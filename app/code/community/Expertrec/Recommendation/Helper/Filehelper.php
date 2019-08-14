<?php

class Expertrec_Recommendation_Helper_Filehelper extends Mage_Core_Helper_Abstract{

	public function createFeedZipFile($rootPath,$zipFileName) {
        // Mage::getSingleton('expertrec_recommendation/log')->log("Feed compression initiated");
        
    	if(!is_dir($rootPath)){return $this;}

        try{
        	// Create recursive directory iterator
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
              
            if(count($files)){
                $zip = new ZipArchive();
                $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                  
                foreach ($files as $name => $file){
                    // Skip directories (they would be added automatically)
                    if (!$file->isDir()){
                        // Get real and relative path for current file
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($rootPath) + 1);

                        // Add current file to archive
                        $zip->addFile($filePath, $relativePath);
                    }
                }

                // Zip archive will be created only after closing object
                $zip->close();
                Mage::getSingleton('expertrec_recommendation/log')->log("Feed compression done");
            }
        }catch(Exception $e){
            Mage::getSingleton('expertrec_recommendation/log')->log("Error: feed compression error: ".$e->getMessage());
        }
        return $this;
    }

    public function createZipFile($path,$fileName,$zipFileName){
    	Mage::getSingleton('expertrec_recommendation/log')->log($zipFileName." compression initiated");
    	try{
    		$zip = new ZipArchive();
			if ( $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
				$zip->addFile($path.'/'.$fileName, $fileName);
				$zip->close();
            	Mage::getSingleton('expertrec_recommendation/log')->log($zipFileName." compression done");
			}
			
    	}catch(Exception $e){
            Mage::getSingleton('expertrec_recommendation/log')->log("Error: Create Zip file error: ".$e->getMessage());
        }
        return $this;
    }

    public function deleteFile($file){
        Mage::getSingleton('expertrec_recommendation/log')->log("Deleting file: ".$file);
        unlink($file);
        return $this;
    }

    public function cleanDir($path) {
    	try{
	        Mage::getSingleton('expertrec_recommendation/log')->log("Deleting feed directory: ".$path);
	        if (is_dir($path) === true){
		        $files = array_diff(scandir($path), array('.', '..'));

		        foreach ($files as $file){
		        	if (is_dir($file) === true ) {
		            	self::deleteDir(realpath($path) . '/' . $file);
		            }else {
            			unlink(realpath($path) . '/' . $file);
            			Mage::getSingleton('expertrec_recommendation/log')->log("Deleted file: ".$file);
        			}
		        }
		        
		    }

	    }catch(Exception $e){
            Mage::getSingleton('expertrec_recommendation/log')->log("Error: deleting dir error: ".$e->getMessage());
        }
        return $this;
    }
}
?>

<?php

class Expertrec_Recommendation_Model_Feed_Formatter {

    //getting image width and height from db
    const IMAGE_WIDTH = 'expertrec/general/expertrec_image_width';
    const IMAGE_HEIGHT = 'expertrec/general/expertrec_image_height';

    protected $_oConfig= array();
    
    public function init() {
	$feedConfig = Mage::getSingleton('expertrec_recommendation/feed_feedconfig');
        $this->_oConfig['imageWidth'] = $feedConfig->imageWidth;
        $this->_oConfig['imageHeight'] = $feedConfig->imageHeight;
        $this->_oConfig['generateImage'] = $feedConfig->generateImage;
        //Mage::getSingleton('expertrec_recommendation/log')->log($this->_oConfig['imageWidth']."---->".$this->_oConfig['imageHeight']."---->".$this->_oConfig['generateImage']);
        return $this;
    }
    
    public function prepareRow($fields,$product) {
        $aFeedRow = array();

        foreach ($fields as $vKey) {
            $vValue='';
            $skey = (string) $vKey;
            switch ($skey) {
                case 'expert_image':
                    $vValue = $this->_getImage($skey,$product);
                    break;
                case 'expert_smallImage':
                    $vValue = $this->_getImage($skey,$product);
                    break;
                case 'expert_thumbnail':
                    $vValue = $this->_getImage($skey,$product);
                    break;
                case 'expert_category':
                    $vValue = $this->_getCategories($product);
                    break;
                case 'expert_url':
                    $vValue=$product->getProductUrl();
                    break;
                case 'qty':
                    $vValue = (int)$product->getData("qty");
                    break;
                default:
                    $vValue =$product->getData($skey); 

            }
           
            if(empty($vValue)){
              $vValue = (string)$vValue == "0" ? 0 : 'None';
            }
            //Mage::getSingleton('expertrec_recommendation/log')->log("data---------->: ".$skey."----------->".$vValue);
            $aFeedRow[$skey] = $vValue;
        }

        return $aFeedRow;
    }

    protected function _getCategories($product){
        $path = Mage::getSingleton('expertrec_recommendation/translator_category')
                    ->translate($product);
        return $path;
    }

    protected function _getImage($fieldName, $product) {
        try {
            $width = Mage::getStoreConfig(self::IMAGE_WIDTH);
            $height = Mage::getStoreConfig(self::IMAGE_HEIGHT);

            if('expert_image' == $fieldName && $this->_oConfig['generateImage']){
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize($this->_oConfig['imageWidth'], $this->_oConfig['imageHeight']);
            }
            else if(!empty($width) && !empty($height)){
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize($width, $height);
            } 
            else if(empty($width) && !empty($height)){
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize($this->_oConfig['imageWidth'], $height);
            }
            else if(empty($height) && !empty($width)){
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize($width, $this->_oConfig['imageHeight']);
            }
            else if(empty($height) && empty($width)){
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize($this->_oConfig['imageWidth'], $this->_oConfig['imageHeight']);
            }
            else if('expert_smallImage' == $fieldName){
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize(250,250);
            } else if ('expert_thumbnail' == $fieldName) {
                return (string)Mage::helper('catalog/image')->init($product, 'image')->resize(80,80);
            }
        } catch (Exception $e) {
            Mage::getSingleton('expertrec_recommendation/log')->log("Error while fetching the image" . $e->getMessage());
        }
        
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getData('image');
    }
}
?>

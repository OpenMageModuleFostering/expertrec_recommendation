<?php

    class Expertrec_Recommendation_Model_Feed_Formatter {

        //getting image width and height from db
        const IMAGE_WIDTH = 'expertrec/general/expertrec_image_width';
        const IMAGE_HEIGHT = 'expertrec/general/expertrec_image_height';
        const THUMBNAIL_WIDTH = 'expertrec/general/expertrec_thumbnail_width';
        const THUMBNAIL_HEIGHT = 'expertrec/general/expertrec_thumbnail_height';

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
                        $vValue = $this->_getCategories($product,0);
                        break; 
                    case 'expert_category_ids':
                        $vValue = $this->_getCategories($product,1);
                        break;           
                    case 'expert_url':
                        //$vValue=$product->getProductUrl();
                        //changing ProductUrl from /index.php/catalog/product/view/id/539/s/racer-back-maxi-dress/ to /index.php/racer-back-maxi-dress.html
                        //$url = Mage::getBaseUrl().$product->url_path;
                        //Stripping the parts of the getpayload, only URL needed
                        $url =$product->getUrlInStore(array('_ignore_category' => true));
                        // $url = substr($url,0,strpos($url,"?"));
                        // substring gives null on split with '?' if it is not there
                        $url_array = explode('?', $url);
                        $url = $url_array[0];
                        //Mage::getSingleton('expertrec_recommendation/log')->log("Getting product url ".$url);
                        $vValue=$url;
                        break;
                    case 'qty':
                        $vValue = (int)$product->getData("qty");
                        break;
                    default:
                        $attributedata = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $skey)->getData();
                        $vValue =$product->getData($skey);
                        //For multiselect attr, need to use attrText to retrieve label value
                        if(!empty($vValue) && array_key_exists('frontend_input',$attributedata)
                        && (isset($attributedata['frontend_input']) && $attributedata['frontend_input'] == 'select')){
                            $vValue .= chr(4).$product->getAttributeText($skey);
                            //Mage::getSingleton('expertrec_recommendation/log')->log(" The select dropdown is : ".$attributedata['frontend_input']." key is : ".$skey);
                        }
                        break;
                }
                if(empty($vValue)){
                    $vValue = (string)$vValue == "0" ? 0 : 'None';
                }
                //Mage::getSingleton('expertrec_recommendation/log')->log("data---------->: ".$skey."----------->".$vValue);
                $aFeedRow[$skey] = $vValue;
            }
            return $aFeedRow;
        }

        /*
        @idstr determines if this combination is called for categories, or for category ids
        */
        protected function _getCategories($product,$idstr){
            $path = Mage::getSingleton('expertrec_recommendation/translator_category')
            ->translate($product,$idstr);
            return $path;
        }

        protected function _getImage($fieldName, $product) {

            $storeId = $product->getStoreId();

            try {
                $width = Mage::getStoreConfig(self::IMAGE_WIDTH);
                $height = Mage::getStoreConfig(self::IMAGE_HEIGHT);
                $twidth = Mage::getStoreConfig(self::THUMBNAIL_WIDTH);
                $theight = Mage::getStoreConfig(self::THUMBNAIL_HEIGHT);
                if('expert_image' == $fieldName ){
                    if($this->_oConfig['generateImage']){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'image')->resize($this->_oConfig['imageWidth'], $this->_oConfig['imageHeight']);

                    }else if(!empty($width) && !empty($height)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'image')->resize($width, $height);

                    }else if(empty($width) && !empty($height)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'image')->resize($this->_oConfig['imageWidth'], $height);

                    }else if(empty($height) && !empty($width)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'image')->resize($width, $this->_oConfig['imageHeight']);

                    }else if(empty($height) && empty($width)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'image')->resize($this->_oConfig['imageWidth'], $this->_oConfig['imageHeight']);
                    }
                }else if('expert_smallImage' == $fieldName){

                    $image_obj = Mage::helper('catalog/image')->init($product, 'image')->resize(250,250);

                }else if ('expert_thumbnail' == $fieldName) {
                    if(!empty($twidth) && !empty($theight)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'thumbnail')->resize($twidth, $theight);

                    }else if(empty($twidth) && !empty($theight)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(80, $theight);

                    }else if(empty($theight) && !empty($twidth)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'thumbnail')->resize($twidth, 80);

                    }else if(empty($theight) && empty($twidth)){

                        $image_obj = Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(80,80);
                    }
                }
                $image_url = (string)$image_obj;
                // parse image_url and check the image_path is valid with http/ response status = 200
                // and if it valid return image with store specific base_url
                // else return base_url as it is
                // this check is to get image_url from media directory, for multisite 
                //('different media dir on each site' and 'sharing same media_dir of a site to all sites')
                $image_parsed_url = parse_url($image_url);
                $image_url_path = substr($image_parsed_url['path'], 1);
                $image_store_url = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK).$image_url_path;
                $headers = @get_headers($image_store_url);
                if(!strpos($headers[0],'200')===false){
                    $image_url = $image_store_url;
                }
                return $image_url;
            } catch (Exception $e) {
                Mage::getSingleton('expertrec_recommendation/log')->log("Error while fetching the image" . $e->getMessage());
            }
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getData('image');
        }
    }
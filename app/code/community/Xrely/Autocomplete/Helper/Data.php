<?php

class Xrely_Autocomplete_Helper_Data extends Mage_Core_Helper_Abstract
{

    const SEARCH_BASE_URL = "http://radium.xrely.com/web/";
    const STATIC_CDN_BASE = "http://sthir.xrely.com/";
    const XRELY_BASE_URL = "http://autocomplete.xrely.com/";
    const KEYWORD_DATA_API = "http://autocomplete.xrely.com/WebService/acceptPostKeyword";
    const KEYWORD_DATA_REMOVED_API = "http://autocomplete.xrely.com/WebService/keywordRemove";
    const STORE_REGISTER_APT = 'http://autocomplete.xrely.com/WebService/register';
    const KEY_SIGN_IN_SERVICE_URL = 'http://autocomplete.xrely.com/WebService/signInByAPIKey';
    const DESIGN_SERVICE_URL = 'http://autocomplete.xrely.com/WebService/signInAndDesignAPIKey';
    const UPGRADE_SERVICE_URL = 'http://autocomplete.xrely.com/WebService/signInAndUpgradeAPIKey/magento';
    var $apiKey = null;
    var $debug_on = null;
    const LOG_LEVEL = "xrely_autocomplete/debug/log_level";
    const LOG_FILE_NAME = "XRelY_Error.log";
    
    private static  $listOfAttribute = null;
    private static  $currencySymbol = null;
    private static  $imageBaseUrl = null;
    public function __construct()
    {
        $this->apiKey =  Mage::getStoreConfig('xrely_autocomplete/config/api_key',Mage::app()->getStore());
        $this->debug_on = Mage::getStoreConfig('xrely_autocomplete/config/debug_on',Mage::app()->getStore());
    }


    public function registerStore($email, $user, $baseUrl)
    {
        return $this->callAPI(self::STORE_REGISTER_APT, 'post', array('email' => $email, 'userName' => $user, 'baseUrl' => $baseUrl,'client'=>'magento'));
    }
    
    public function sync($data)
    {
        $data["API_KEY"] = $this->apiKey;
        return $this->callAPI(self::KEYWORD_DATA_API,'post',array("data"=>  json_encode($data)));
    }

     public function syncRemoved($data)
    {
        $data["API_KEY"] = $this->apiKey;
        return $this->callAPI(self::KEYWORD_DATA_REMOVED_API,'post',array("data"=>  json_encode($data)));
    }
    
    private function callAPI($url, $type = 'post', $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if (strtolower($type) == 'post')
            curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function productDetailChanged($product)
    {
        $pData = array();
        $pData["client"] = "magento";
        $pData["cmsName"] = "magento";
        $pData["lastpage"] = false;
        $pData["magentoData"]["items"] = [];
        $pData["magentoData"]["items"][] = $this->getProductDetails($product);
        return $this->sync($pData);
    }

    public function productRemoved($product)
    {
        $pData = array();
        $pData["client"] = "magento";
        $pData["cmsName"] = "magento";
        $pData["lastpage"] = false;
        $pData['action'] = 'delete';
        $pData["magentoData"]["items"] = [];
        $pData["magentoData"]["items"][] = $this->getProductDetails($product);
        return $this->syncRemoved($pData);
    }

    public function getAttributeListForIndex()
    {
        if(self::$listOfAttribute === null){
            self::$listOfAttribute = array_flip(explode(",",Mage::getStoreConfig('xrely_autocomplete/config/serchable_field',Mage::app()->getStore())));
        }
        return self::$listOfAttribute;
    }

    public function getCurrencySymbol()
    {
        if(self::$currencySymbol === null){        
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            self::$currencySymbol = Mage::app()->getLocale()->currency( $currencyCode )->getSymbol();
        }
        return self::$currencySymbol;
    }

    public function getImageBaseUrl()
    {
        if(self::$imageBaseUrl === null){
            self::$imageBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
        }
        return self::$imageBaseUrl;
    }

    public function getXRelYSearchBaseUrl()
    {        
        return self::SEARCH_BASE_URL;
    }

    public function getXRelYCDNBaseUrl()
    {        
        return self::STATIC_CDN_BASE;
    }

    public function getProductDetails($product)
    {
        try {

            $catCollection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('url')
                    ->addAttributeToSelect('is_active');
            $catList = array();
            $listOfAttribute = $this->getAttributeListForIndex();
            $storeId = Mage::app()->getStore()->getId();
            foreach ($catCollection as $cat)
            {
                $catList[] = 
                [
                    'name' => $cat->getName(),
                    'url' => $cat->getUrl()
                ];
            }
            $imgUrl = "";
            try {
                    $imgUrl = (String)$this->getThumbnailURL($product->getImage(),32,32);
            } catch (Exception $e) {
                
            }
            $attributes = $product->getAttributes();
            $productAttribute = array();
            $metaKeyword = $product->getMetaKeyword();
            $metaKeywordSplit = array();
            if($metaKeyword != null ){
                 $metaKeywordSplit = preg_split('/,/is',  $metaKeyword);
            }
            $productAttribute["meta_keywords"]['label'] = "Meta Keywords";
            $productAttribute["meta_keywords"]['value'] = $metaKeywordSplit;
            foreach ($attributes as $attribute) 
            {          
                    if($attribute->getFrontendLabel() != null &&  ($attribute->getIsSearchable() || $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch() || $attribute->getUsedForSortBy() ||  in_array($attribute->getAttributecode(),array('image','small_image','media_gallery','gallery')) || isset($listOfAttribute[$attribute->getAttributecode()])))
                    {
                        $attributeLabel = $attribute->getAttributecode();
                        $productAttribute[$attribute->getAttributecode()]["type"] = $attribute->getFrontendInput();
                        switch ($attribute->getAttributecode()) {
                            case 'media_gallery':
                                $images = $attribute->getFrontend()->getValue($product);
                                $images = $images["images"];
                                $imageList = array();
                                if (is_array($images)) {
                                    foreach ($images as $image) {
                                        $imageUrl = trim($this->getImageBaseUrl(),"/\\") . "/" .  trim($image["file"],"/\\");
                                        $imageLabel = $image["label"];
                                        $imageList[] = array("url" => $imageUrl,"label" => $imageLabel);
                                    }
                                    $productAttribute['media_gallery']['label'] =  $attribute->getFrontendLabel();
                                    $productAttribute['media_gallery']['value'] =  $imageList;
                                }
                                break;
                            default:
                                $productAttribute[$attribute->getAttributecode()]['label'] = $attribute->getFrontendLabel();
                                $productAttribute[$attribute->getAttributecode()]['value'] = $attribute->getFrontend()->getValue($product);
                                break;
                        }
                        
                    }  
            }
            $storeId = Mage::app()->getStore()->getId();
            $ratingSummaryData = Mage::getModel('review/review_summary')->setStoreId($storeId)->load($product->getId());
            $productAttribute['reviews_count']['label'] = 'Reviews';
            $productAttribute['reviews_count']['value'] =  isset($ratingSummaryData['reviews_count'])?$ratingSummaryData['reviews_count']:"0";
            $productAttribute['rating']['label'] =  'Rating';
            $productAttribute['rating']['value'] =  $ratingSummaryData->getRatingSummary() == null ? "0":$ratingSummaryData->getRatingSummary();
            $thumbnailImage = $this->getThumbnailURL( $product->getImage());
            $stocklevel = (int)Mage::getModel('cataloginventory/stock_item')
                ->loadByProduct($product)->getQty();
            return array(
                'xid' => $product->getId(),
                'keyword' => $product->getName(),
                "metaData" => array(
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'thumbnail'=> $thumbnailImage,
                    'url' => $this->getFullProductUrl($product),
                    'image' => $imgUrl,
                    'categories' => $catList,
                    'type' => 'product',
                    'attributes' => $productAttribute,
                    'currencySymbol' => $this->getCurrencySymbol(),
                    'imageBasePath' =>$this->getImageBaseUrl(),
                    'quantity' => $stocklevel
                )
            );    
        } catch (Exception $e) {
            $this->log(Zend_Log::CRIT,"Error will fetching product info ".$e->getMessage());
        }
    }


    public function getXRelYBaseUrl()
    {
        return self::XRELY_BASE_URL;
    }

    public function getRedirectFormAction()
    {
        return self::KEY_SIGN_IN_SERVICE_URL;
    }

    public function getDesignRedirectFormAction()
    {
        return self::DESIGN_SERVICE_URL;
    }

    public function getUpgradeRedirectFormAction()
    {
        return self::UPGRADE_SERVICE_URL;
    }

    public function getLogLevel() {
        $log_level = Mage::getStoreConfig(self::LOG_LEVEL);

        return ($log_level !== null) ? intval($log_level) : Zend_Log::INFO;
    }

     public function log($level, $message) {
        $logOn = Mage::getStoreConfig("xrely_autocomplete/debug/enabled");

        if ($level <= $this->getLogLevel()) {
            Mage::log($message, $level, self::LOG_FILE_NAME, $logOn);
        }
    }
    public function getAPIKey()
    {
        return $this->apiKey;
    }

    public function getFullProductUrl(Mage_Catalog_Model_Product $product = null){

        // Force display deepest child category as request path.
        $categories = $product->getCategoryCollection();
        $deepCatId = 0;
        $path = '';
        $productPath = false;

        foreach ($categories as $category) {
            // Look for the deepest path and save.
            if (substr_count($category->getData('path'), '/') > substr_count($path, '/')) {
                $path = $category->getData('path');
                $deepCatId = $category->getId();
            }
        }

        // Load category.
        $category = Mage::getModel('catalog/category')->load($deepCatId);

        // Remove .html from category url_path.
        $categoryPath = str_replace('.html', '',  $category->getData('url_path'));

        // Get product url path if set.
        $productUrlPath = $product->getData('url_path');

        // Get product request path if set.
        $productRequestPath = $product->getData('request_path');

        // If URL path is not found, try using the URL key.
        if ($productUrlPath === null && $productRequestPath === null) {
            $productUrlPath = $product->getData('url_key');
        }

        // Now grab only the product path including suffix (if any).
        if ($productUrlPath) {
            $path = explode('/', $productUrlPath);
            $productPath = array_pop($path);
        } elseif ($productRequestPath) {
            $path = explode('/', $productRequestPath);
            $productPath = array_pop($path);
        }

        // Now set product request path to be our full product url including deepest category url path.
        if ($productPath !== false) {
            if ($categoryPath && false) {
                // Only use the category path is one is found.
                $product->setData('request_path', $categoryPath . '/' . $productPath);
            } else {
                $product->setData('request_path', $productPath);
            }
        }

        return $product->getProductUrl();
    }

    public function resizeImg($fileName, $width, $height = '')
    {
        $folderURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $imageURL = $folderURL . $fileName;
     
        $basePath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . $fileName;
        $newPath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . "resized" . DS . $fileName;
        if ($width != '') {
            if (file_exists($basePath) && is_file($basePath) && !file_exists($newPath)) {
                $imageObj = new Varien_Image($basePath);
                $imageObj->constrainOnly(TRUE);
                $imageObj->keepAspectRatio(FALSE);
                $imageObj->keepFrame(FALSE);
                $imageObj->resize($width, $height);
                $imageObj->save($newPath);
            }
            $resizedURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "resized" . DS . $fileName;
         } else {
            $resizedURL = $imageURL;
         }
         return $resizedURL;
    }
    public function getThumbnailURL($image,$targetWidth = 200,$targetHeight = 200)
    {
         try {
                $baseImageUrl = Mage::getBaseDir('media').DS."catalog".DS."product".$image;

                if(file_exists($baseImageUrl)) {
                    list($width, $height, $type, $attr)=getimagesize($baseImageUrl); 
                    if($width > $targetWidth && $height > $targetHeight) {
                        $imageResized = Mage::getBaseDir('media'). DS ."xrely". DS . $targetWidth . DS . $targetHeight . DS . $image;
                        if(!file_exists($imageResized)) {
                           $this->saveImageToPath($baseImageUrl,$imageResized,$targetWidth,$targetHeight);
                        }
                    return preg_replace('/((?<=[^:])\/\/)|(\\\\)/', '/', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . "xrely" . DS . $targetWidth . DS . $targetHeight . DS .  $image);
                    }
                return preg_replace('#//#is', '/', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "/catalog/product" . $image);
                }
            }catch(Exception $e) {
            
            }
    }
    public function saveImageToPath($imageUrl,$imageResized,$targetWidth,$targetHeight)
    {
        $imageObj = new Varien_Image($imageUrl);
        $imageObj->constrainOnly(TRUE);
        $imageObj->keepAspectRatio(TRUE);
        $imageObj->keepFrame(FALSE);
        $imageObj->keepTransparency(true);
        $imageObj->backgroundColor(array(255, 255, 255));
        $imageObj->resize($targetWidth,$targetHeight);
        return $imageObj->save($imageResized);
    }

    public function arrayColumnSelect($results,$col) {
        $return = array();
        foreach ($results as $key => $value) {
            if(isset($value[$col])){
                $return[] = $value[$col];
            }
        }
        return $return;
    }

}

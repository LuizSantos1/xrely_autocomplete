<?php

class Xrely_Autocomplete_Helper_Data extends Mage_Core_Helper_Abstract
{

    const KEYWORD_DATA_API = "http://autocomplete.xrely.com/WebService/acceptPostKeyword";
    const STORE_REGISTER_APT = 'http://autocomplete.xrely.com/WebService/register';
    const KEY_SIGN_IN_SERVICE_URL = 'http://autocomplete.xrely.com/WebService/signInByAPIKey';
    const DESIGN_SERVICE_URL = 'http://autocomplete.xrely.com/WebService/signInAndDesignAPIKey';
    const UPGRADE_SERVICE_URL = 'http://autocomplete.xrely.com/WebService/signInAndUpgradeAPIKey/magento';
    var $apiKey = null;
    
    public function __construct()
    {
        $this->apiKey =  Mage::getStoreConfig('xrely_autocomplete/config/api_key',Mage::app()->getStore());
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
        $catCollection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('url')
                    ->addAttributeToSelect('is_active');
        $catList = [];
        foreach ($catCollection as $cat)
        {
            $catList[] = ['name' => $cat->getName(),
                'url' => $cat->getUrl()];
        }
        $imgUrl = "";
        if($product->getThumbnail() != "" && $product->getThumbnail() != "no_selection")
            $imgUrl = (String) Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(32);
        $pData = array();
        $pData["client"] = "magento";
        $pData["cmsName"] = "magento";
        $pData["lastpage"] = false;
        $pData["magentoData"]["items"] = [];
        $pData["magentoData"]["items"][] = array(
                'xid' => $product->getId(),
                'keyword' => $product->getName(),
                "metaData" => array(
                    'url' => $product->getProductUrl(TRUE),
                    'image' => $imgUrl,
                    'categories' => $catList,
                    'type' => 'product'
                )
            );
        return $this->sync($pData);
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
            if ($categoryPath) {
                // Only use the category path is one is found.
                $product->setData('request_path', $categoryPath . '/' . $productPath);
            } else {
                $product->setData('request_path', $productPath);
            }
        }

        return $product->getProductUrl();
    }
}

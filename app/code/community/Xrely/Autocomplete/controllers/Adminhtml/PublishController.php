<?php

class Xrely_Autocomplete_Adminhtml_PublishController extends Mage_Adminhtml_Controller_Action
{

    /*
    * Batch size, Can be configured by you
    * ===========================
    */
    const BATCH_SIZE = 100;
    /*
    * ===========================
    */
    const STATUS_IN_PROGRESS = 1;
    const STATUS_DONE_NOW = 2;
    const STATUS_DONE_IN_PAST = 3;
    const STATUS_UPGRADE = 4;


    public function urlAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()
                ->createBlock('xrely_autocomplete/adminhtml_progress')
                ->setTemplate('xrely_autocomplete/progress.phtml');
        $this->_addContent($block);
        $this->renderLayout();
    }

    public function initAction()
    {
        
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('catalog/product');
        $insertTable = Mage::getSingleton('core/resource')->getTableName('xrely_autocomplete/settings');

        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $iDefaultStoreId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        $productCollection->setStoreId($iDefaultStoreId);
        $productCollection->addAttributeToSelect(array('entity_id'));
        $productCollection->setVisibility(array(
           Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
           Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
           Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
        ));
        $query = "INSERT INTO ". $resource->getTableName('xrely_autocomplete/settings'). "(`id`, `type`, `eid`, `key`, `value`, `comment`)  SELECT NULL, '0', p.entity_id, CONCAT('p:', p.entity_id) , now(), NULL FROM  ( ".$productCollection->getSelect()->joinLeft(
                                array('xs' => $resource->getTableName('xrely_autocomplete/settings')),
                                "xs.eid = e.entity_id",
                                ""
                            )->where("xs.eid is null") . "  ) p";   
        $writeConnection->query($query);
        $model = Mage::getModel('xrely_autocomplete/settings');
        $processed = $model->totalProcessed();
        $totalProduct = $model->getTotalProduct();
        if($processed >= $totalProduct)
        {
            Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
            echo json_encode(
                array(
                    'status' => array(
                        'code' => self::STATUS_DONE_IN_PAST,
                        'current' => $model->totalProcessed(),
                        'total' => $model->getTotalProduct()
                    )
                )
            );
           die;
        }
        echo json_encode(
            array(
                'status' => array(
                    'code' => self::STATUS_IN_PROGRESS,
                    'current' => $model->totalProcessed(),
                    'total' => $model->getTotalProduct()
                )
            )
        );
    }

    public function prepareAction()
    {  
        try {
            set_time_limit(0);
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = 'SELECT eid FROM ' . $resource->getTableName('xrely_autocomplete/settings') ." where type != '1' LIMIT ".self::BATCH_SIZE.";";
            $results = $readConnection->fetchAll($query);
            $products = $this->arrayColumnSelect($results,'eid');
            $helper = Mage::helper('xrely_autocomplete');

            $i = 0;
            $pData = array();
            $pData["client"] = "magento";
            $pData["cmsName"] = "magento";
            $pData["magentoData"]["items"] = array();
            $model = Mage::getModel('xrely_autocomplete/settings');
            $processed = $model->totalProcessed();
            $totalProduct = $model->getTotalProduct();
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $currencySymbol = Mage::app()->getLocale()->currency( $currencyCode )->getSymbol();
            $imageBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
            if($processed >= $totalProduct)
            {
                Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
                echo json_encode(
                    array(
                        'status' => array(
                            'code' => self::STATUS_DONE_IN_PAST,
                            'current' => $model->totalProcessed(),
                            'total' => $model->getTotalProduct()
                        )
                    )
                );
               die;
            }
            $markProd = array();   
            $listOfAttribute = array_flip(explode(",",Mage::getStoreConfig('xrely_autocomplete/config/serchable_field',Mage::app()->getStore())));
            foreach ($products as $prod)
            {

                $product = Mage::getModel('catalog/product')->load($prod);
                $pData["magentoData"]["items"][] = $helper->getProductDetails($product);
                $markProd[] = $product->getId();                
            }
            if ((count($markProd) + $processed) >= $totalProduct)
                $pData["lastpage"] = true;
            else
                $pData["lastpage"] = false;
            /*
             * Call to API 
             */
            $helper->log(Zend_Log::DEBUG, sprintf("Syncing product info processed - %s:: totalProduct - %s", $processed, $totalProduct));
            $resp =  json_decode($helper->sync($pData),true);
            if(!$resp['success'])
            {
                switch ($resp['action']) {
                    case 'upgrade':
                        echo json_encode(
                            array(
                                'status' => array(
                                    'code' => self::STATUS_UPGRADE,
                                    'link' => Mage::helper("adminhtml")->getUrl("xrely_autocomplete/adminhtml_redirect/upgrade"),
                                    'text' => $resp['text'],
                                    'current' => $model->totalProcessed(),
                                    'total' => $model->getTotalProduct()
                                )
                            )
                        );
                        break;     
                    default:
                        break;
                }
                die;
            }
            else
            {

                foreach ($markProd as  $prodId) {
                     $model->markSent($prodId);
                }
            }
            $processed = $model->totalProcessed();
            $totalProduct = $model->getTotalProduct();
            if($processed >= $totalProduct)
            {
                Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
                Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync_search', 1);
            }    
            echo json_encode(
                array(
                    'status' => array(
                        'code' => self::STATUS_IN_PROGRESS,
                        'current' => $model->totalProcessed(),
                        'total' => $model->getTotalProduct()
                    )
                )
            );
        } catch (Exception $e) {
            
            $helper->log(Zend_Log::CRIT, sprintf("Error in While syncing in Publish controller %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
        
    }

    public function statusAction()
    {
        $model = Mage::getModel('xrely_autocomplete/settings');
        $processed = $model->totalProcessed();
        $totalProduct = $model->getTotalProduct();
        if($processed >= $totalProduct)
        {
            Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
            Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync_search', 1);
        }    
        echo json_encode(
                array(
                    'status' => array(
                        'current' => $processed,
                        'total' => $totalProduct
                    )
                )
        );
        die;
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

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
        $query = "INSERT INTO {$insertTable} (`id`, `type`, `eid`, `key`, `value`, `comment`) SELECT NULL, '0', cpe.entity_id, CONCAT('p:', cpe.entity_id) , '".date('Y-m-d')."', NULL FROM {$table} as cpe left join  ".$resource->getTableName('xrely_autocomplete/settings')." as x on x.eid =  cpe.entity_id where x.eid is null;";     
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
        $pData["magentoData"]["items"] = [];
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
        $markProd = array();   
        foreach ($products as $prod)
        {
                
            $product = Mage::getModel('catalog/product')->load($prod);
            $catCollection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('url')
                    ->addAttributeToSelect('is_active');
            $catList = [];
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
                if($product->getThumbnail() != "" && $product->getThumbnail() != "no_selection")
                    $imgUrl = (String) Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(32);
            } catch (Exception $e) {
                
            }
            $pData["magentoData"]["items"][] = array(
                'xid' => $product->getId(),
                'keyword' => $product->getName(),
                "metaData" => array(
                    'url' => $helper->getFullProductUrl($product),
                    'image' => $imgUrl,
                    'categories' => $catList
                )
            );
            $markProd[] = $product->getId();  
            
        }
        if ((count($markProd) + $processed) >= $totalProduct)
            $pData["lastpage"] = true;
        else
            $pData["lastpage"] = false;
        /*
         * Call to API 
         */
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

    public function statusAction()
    {
        $model = Mage::getModel('xrely_autocomplete/settings');
        $processed = $model->totalProcessed();
        $totalProduct = $model->getTotalProduct();
        if($processed >= $totalProduct)
            Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
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

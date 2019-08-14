<?php

class Xrely_Autocomplete_Adminhtml_PublishController extends Mage_Adminhtml_Controller_Action
{

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

    public function prepareAction()
    {
        set_time_limit(0);
        $helper = Mage::helper('xrely_autocomplete');
        $collection = Mage::getResourceModel('reports/product_collection');
        $collection->addStoreFilter();
        $products = Mage::getModel('catalog/product')->getCollection();
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
                if ($model->isProcessed($prod->getId()))
                continue;
            $product = Mage::getModel('catalog/product')->load($prod->getId());
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
            if($product->getThumbnail() != "" && $product->getThumbnail() != "no_selection")
                $imgUrl = (String) Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(32);
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
           

            if (++$i % 5 == 0)
            {
                if ($i == $total_products_in_store)
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
                         $model->markProduct($prodId);
                    }
                    $markProd = array();
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
                $processed = $model->totalProcessed();
                $totalProduct = $model->getTotalProduct();
                if($processed >= $totalProduct)
                    Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
                die;
                $pData["magentoData"]["items"] = [];
            }
        }
        if (count($pData["magentoData"]["items"] > 0))
        {
            /*
             * Call to API 
             */
            foreach ($markProd as  $prodId) {
                         $model->markProduct($prodId);
                    }
            $markProd = array();
            echo json_encode(
                array(
                    'status' => array(
                        'code' => self::STATUS_IN_PROGRESS,
                        'current' => $model->totalProcessed(),
                        'total' => $model->getTotalProduct()
                    )
                )
            );
            $helper->sync($pData);
            $processed = $model->totalProcessed();
            $totalProduct = $model->getTotalProduct();
            if($processed >= $totalProduct)
                Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 1);
            die;
        }
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
}

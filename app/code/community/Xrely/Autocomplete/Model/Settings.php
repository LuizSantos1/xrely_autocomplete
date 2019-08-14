<?php

class Xrely_Autocomplete_Model_Settings extends Mage_Core_Model_Abstract
{

    const PRODUCT_PROCESSED = 1;
    static $totalProduct = null;
    protected function _construct()
    {
        $this->_init('xrely_autocomplete/settings');
    }

    public function markProduct($productId)
    {
        $data = array('id' => null,'eid' => $productId, 'key' => "p:$productId", 'value' => date('Y-m-d',time()), 'type' => self::PRODUCT_PROCESSED,'comment' => '');
        $this->setData($data);
        $id =  $this->save()->getId();
        $this->unsetData();
        return $id;
    }

    public function totalProcessed()
    {
        return $this->getCollection()->addFieldToFilter('type', 1)->load()->getSize();
    }
    
    public function isProcessed($prodId)
    {
    	$collection = $this->getCollection();
        $collection->addFieldToFilter('type',1);
        $collection->addFieldToFilter('eid',$prodId);
        return $collection->count() > 0;
    }
    public static function getTotalProduct()
    {
    	if(self::$totalProduct === null)
    	{
    		$collection = Mage::getResourceModel('reports/product_collection');
	        $collection->addStoreFilter();
	        return self::$totalProduct =  $collection->getSize();	
    	}
    	return self::$totalProduct;
    }
}

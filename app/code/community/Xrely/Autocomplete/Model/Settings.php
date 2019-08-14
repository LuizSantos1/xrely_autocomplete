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

    public function markSent($productId)
    {
            $resource = Mage::getSingleton('core/resource');
            $write = $resource->getConnection('core_write');
            $updateTable = $resource->getTableName('xrely_autocomplete/settings');
            $data = array("type" => "1"); 
            $where = "eid = '$productId'"; 
             $write->update($updateTable, $data, $where);
    }

    public function totalProcessed()
    {
        return $this->getCollection()->addFieldToFilter('type', '1')->load()->getSize();
    }
    
    public function isProcessed($prodId)
    {
    	$collection = $this->getCollection();
        $collection->addFieldToFilter('type','1');
        $collection->addFieldToFilter('eid',$prodId);
        return $collection->count() > 0;
    }
    public function getTotalProduct()
    {
    	if(self::$totalProduct === null)
    	{
	        return self::$totalProduct =  $this->getCollection()->getSize();	
    	}
    	return self::$totalProduct;
    }
}

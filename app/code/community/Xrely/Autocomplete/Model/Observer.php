<?php 
class Xrely_Autocomplete_Model_Observer{

    public function updateProductName(Varien_Event_Observer $observer){
    	$product = $observer->getData('product');
        $helper = Mage::helper('xrely_autocomplete');
        $helper->productDetailChanged($product);
    }
    public function deleteProductName(Varien_Event_Observer $observer)
    {
    	$product = $observer->getData('product');
        $helper = Mage::helper('xrely_autocomplete');
        $helper->productRemoved($product);
    }
}
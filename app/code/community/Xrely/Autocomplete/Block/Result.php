<?php 
/**
* 
*/
class Xrely_Autocomplete_Block_Result extends Mage_Core_Block_Template
{
	
	function __construct()
	{
		parent::__construct();
	}

	public function getLoclhostDomainName()
	{
		return  Mage::getStoreConfig('xrely_autocomplete/config/localhost_domain',Mage::app()->getStore()) ;
	}

	public function getXRelyBaseUrl()
	{
		$helper = Mage::helper('xrely_autocomplete');
		return $helper->getXRelYBaseUrl();
	}

	public function getFacetList()
	{
		return  json_encode(preg_split('/,/is', Mage::getStoreConfig('xrely_autocomplete/config/serchable_field',Mage::app()->getStore()))) ;
	}
	
	public function getXRelYSearchBaseUrl()
	{
		$helper = Mage::helper('xrely_autocomplete');
		return $helper->getXRelYSearchBaseUrl();
	}

	public function getXRelYCDNBaseUrl()
	{
		$helper = Mage::helper('xrely_autocomplete');
		return $helper->getXRelYCDNBaseUrl();
	}
}
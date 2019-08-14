<?php 
class Xrely_Autocomplete_Block_Js extends Mage_Core_Block_Template
{
	const XRELY_SCRIPT_BASE = 'autocomplete.xrely.com/';
	const XRELY_FILE_PATH = 'js/autocomplete/autoScript.js';

	public function getXrelyJsUrl()
	{
		$surl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$partUrl = parse_url($surl);
		if($partUrl["host"] == 'localhost')
		{
			$partUrl["host"] = Mage::getStoreConfig('xrely_autocomplete/config/localhost_domain',Mage::app()->getStore());
		}
		return "//".self::XRELY_SCRIPT_BASE.self::XRELY_FILE_PATH."?_=".$partUrl["host"]."&no=\"+((typeof jQuery == \"undefined\")?0:1)+\"&system=magento";
	}
}
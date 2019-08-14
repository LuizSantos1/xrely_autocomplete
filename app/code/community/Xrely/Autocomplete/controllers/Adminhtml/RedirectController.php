<?php 
class Xrely_Autocomplete_Adminhtml_RedirectController extends Mage_Adminhtml_Controller_Action
{
	public function postAction()
	{
		$this->getResponse()->setBody($this->getLayout()->createBlock('xrely_autocomplete/adminhtml_redirect')->toHtml());
		$this->getResponse()->sendResponse();
		die;
	}

	public function designAction()
	{
		$this->getResponse()->setBody($this->getLayout()->createBlock('xrely_autocomplete/adminhtml_design')->toHtml());
		$this->getResponse()->sendResponse();
		die;
	}

	public function upgradeAction()
	{
		$this->getResponse()->setBody($this->getLayout()->createBlock('xrely_autocomplete/adminhtml_upgrade')->toHtml());
		$this->getResponse()->sendResponse();
		die;
	}
}	
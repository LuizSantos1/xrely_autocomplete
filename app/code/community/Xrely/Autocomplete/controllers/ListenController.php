<?php 
class Xrely_Autocomplete_ListenController extends Mage_Core_Controller_Front_Action
{

    public function eventAction()
    {
    	if($this->checkKey($_POST['API_KEY']))
    	{
    		switch ($_POST['type']) {
    			case 'notify':
    				Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/notify_msg', $_POST['msg']);
    				Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/notify', 1);
    				echo json_encode(array('sucess'=>true,'msg' => 'Notified'));
    				break;
    			default:
    				echo json_encode(array('sucess'=>false,'msg' => 'No Metdhod'));
    				break;
    		}
    	}
    }

    public function checkKey($key)
    {
    	if($key != Mage::getStoreConfig('xrely_autocomplete/config/api_key',Mage::app()->getStore()))
		{
			die(json_encode(array('sucess' => false,'msg' => 'Unauthorized')));
		}
		else
		{
			return true;
		}
    }
}
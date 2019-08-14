<?php 
class Xrely_Autocomplete_ListenController extends Mage_Core_Controller_Front_Action
{

    public function eventAction()
    {
    	if(isset($_POST['API_KEY']) && $this->checkKey($_POST['API_KEY']))
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

    public function getProductDetailsAction()
    {
        $this->getResponse()->clearHeaders()->setHeader(
            'Content-type',
            'application/json'
        );
        $rData = array();
        if(isset($_POST['API_KEY']) && $this->checkKey($_POST['API_KEY']))
        {
            if(isset($_POST['product_id']))
            {
                set_time_limit(0);
                $helper = Mage::helper('xrely_autocomplete');
                $pData = array();
                $pData["client"] = "magento";
                $pData["cmsName"] = "magento";
                $pData["lastpage"] = isset($_POST['lastpage'])?true:false;
                $pData["magentoData"]["items"] = [];
                $productIdList = json_decode($_POST['product_id']);
                if(is_array($productIdList))
                {

                    $collection = Mage::getResourceModel('catalog/product_collection');
                    $collection->addFieldToFilter('entity_id',array('in'=>$productIdList));
                    $foundIds = $collection->getAllIds();
                    if(is_array($foundIds)){
                        foreach ($foundIds as $key => $prodId) {
                          $pData["magentoData"]["items"][] =  $helper->getProductDetails(Mage::getModel('catalog/product')->load($prodId)); 
                        }
                    }else
                    {
                        $rData['success'] = false;
                        $rData['message'] = 'Products Not found';   
                    }
                }else
                {
                    $rData['success'] = false;
                    $rData['message'] = 'Product Id not in correct format';     
                }
                $rData =  $helper->sync($pData);
            }else{
                $rData['success'] = false;
                $rData['message'] = 'Missing Product Id/s';  
            }
        }else{
                $rData['success'] = false;
                $rData['message'] = 'Invalid Key';
        }
         $this->getResponse()->setBody(
            $rData
        );
    }

    public function getConfigValueAction()
    {
        $this->getResponse()->clearHeaders()->setHeader(
            'Content-type',
            'application/json'
        );
        if(isset($_POST['API_KEY']) && $this->checkKey($_POST['API_KEY']))
        {
            $configModel = Mage::getResourceModel('core/config');
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $select = $readConnection->select()->from($configModel->getMainTable(), array('scope', 'scope_id', 'path', 'value'));
            $select->where('path LIKE ?', "%xrely_autocomplete%");
            $rowset = $readConnection->fetchAll($select);
            $rData = array();$i =0;
            foreach ($rowset as $r) 
            {
                $rData[$i]["scope"] = $r["scope"];
                $rData[$i]["scope_id"] = $r["scope_id"];
                $rData[$i]["path"] = $r["path"];
                $rData[$i++]["value"] = $r["value"];
            }   
        }
         $this->getResponse()->setBody(
             Mage::helper('core')->jsonEncode($rData)
        );
    }
}
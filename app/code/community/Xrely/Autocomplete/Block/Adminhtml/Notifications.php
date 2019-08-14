<?php

class Xrely_Autocomplete_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{

    public function getMessage()
    {
    	$message = null;
        if (Mage::getStoreConfig('xrely_autocomplete/config/ini_sync') == 0)
        {
            $publishUrl = Mage::helper('adminhtml')->getUrl('xrely_autocomplete/adminhtml_publish/url');

            $message = '<a class="label" href="'.$publishUrl.'" target="_blank"><strong class="label">XRelY Sync</strong></a> : Initial Syncronization is not done yet and it is necessary to start using XrelY\'s Autocomplete. <a href="'.$publishUrl.'">Let\'s do it</a>';
        }
        else
        {
        	$datetime1 = new DateTime(date('Y-m-d H:i:s', Mage::getStoreConfig('xrely_autocomplete/config/time')));
            $datetime2 = new DateTime(date('Y-m-d H:i:s'));
            $oDiff = $datetime1->diff($datetime2);
            if ($oDiff->m >=  2)
            {
            	$upgradeUrl =  Mage::helper("adminhtml")->getUrl("xrely_autocomplete/adminhtml_redirect/upgrade");
               	$message = '<a class="label" href="'.$upgradeUrl.'" target="_blank"><strong class="label">XRelY Upgrade</strong></a> : Free trial period is over, please <a href="'.$upgradeUrl.'">click</a> to Upgrade';
            } else if(Mage::getStoreConfig('xrely_autocomplete/config/notify') == 1)
            {
            	$data = json_decode(Mage::getStoreConfig('xrely_autocomplete/config/notify_msg'),true);
				$message = '<a class="label" href="'.$data['url'].'" target="_blank"><strong class="label">XRelY Message</strong></a> : '.$data['text'];
            }
        }
        return $message;
    }

}
<?php

$adminUserModel = Mage::getModel('admin/user');
$userCollection = $adminUserModel->getCollection()->load();
Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/admin_email', $userCollection->getData()[0]["email"]);
Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 0);
$helper = Mage::helper('xrely_autocomplete');
$email = $userCollection->getData()[0]["email"];
$userName = $userCollection->getData()[0]["firstname"];

$details = json_decode($helper->registerStore($email, $userName, Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)), true);

Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/imagefield', 'thumbnail');
Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/enabled', '0');

if (isset($details["success"]) && $details["success"] == true)
{
    if (isset($details["details"]["apiKey"]))
    {
        Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/api_key', $details["details"]["apiKey"]);
        Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/notify', 0);
        if(preg_match('/[0-9]+\.localhost/is', $details["details"]['host']))
        {
        	Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/localhost_domain', $details["details"]["host"]);
        }
        Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/time', time());
    }
}  
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
        /*
        Setup default configuration for searchable fields
        */
        $productEntityType = Mage::getModel('eav/entity_type')->loadByCode(Mage_Catalog_Model_Product::ENTITY);
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributesInfo = Mage::getResourceModel('eav/entity_attribute_collection')
        ->setEntityTypeFilter($productEntityType->getId())  //4 = product entities
        ->addSetInfo()
        ->getData();
        $defaultSearchable = [];
        foreach ($attributesInfo  as $key => $value) {
            if($value["is_searchable"] == 1 ||  in_array($value['attribute_code'], array('image','small_image','media_gallery','gallery')))
                $defaultSearchable[] = $value['attribute_code'];
        }
        $searchableConf = implode(",", $defaultSearchable);
        Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/serchable_field', $searchableConf); 
    }
}  
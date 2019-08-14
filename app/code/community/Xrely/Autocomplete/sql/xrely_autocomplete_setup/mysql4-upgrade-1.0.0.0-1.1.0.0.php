<?php 

$installer = $this;

$installer->startSetup();

$xrely_table_name = $installer->getTable('xrely_autocomplete/settings');

$installer->run("DELETE FROM  `{$xrely_table_name}` ;");

Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync', 0);

Mage::getModel('core/config')->saveConfig('xrely_autocomplete/config/ini_sync_search', 0);

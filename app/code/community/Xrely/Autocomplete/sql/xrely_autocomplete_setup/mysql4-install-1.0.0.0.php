<?php
//var_dump($user = Mage::getSingleton('admin/session')->getUser());;die;
$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
        ->newTable($installer->getTable('xrely_autocomplete/settings'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
                ), 'Id')
        ->addColumn('type', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
                ), 'Id')
        ->addColumn('eid', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
                ), 'EId')
        ->addColumn('key', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false
                ), 'Key')
        ->addColumn('value', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
                ), 'Value')
        ->addColumn('comment', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
                'nullable' => true,
        ), 'Comment')    
        ->addIndex(
        'xrely_autocomplete_eid_index',
        'eid'
    );;
$installer->getConnection()->createTable($table);
$installer->endSetup();

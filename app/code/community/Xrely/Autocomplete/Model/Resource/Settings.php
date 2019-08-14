<?php

class Xrely_Autocomplete_Model_Resource_Settings extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('xrely_autocomplete/settings', 'id');
    }
}
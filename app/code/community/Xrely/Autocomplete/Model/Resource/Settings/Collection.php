<?php

class Xrely_Autocomplete_Model_Resource_Settings_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('xrely_autocomplete/settings');
    }
}

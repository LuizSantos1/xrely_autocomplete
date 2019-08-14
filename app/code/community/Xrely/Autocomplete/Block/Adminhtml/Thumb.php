<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Xrely_Autocomplete_Block_Adminhtml_Thumb extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = Mage::helper('adminhtml')->getUrl('xrely_autocmplete/adminhtml_publish/url');
//
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Scan & Sync')
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
    
    protected function getSnycUrl()
    {
        
    }
}


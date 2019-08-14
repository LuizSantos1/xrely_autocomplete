<?php 
/**
* 
*/
class Xrely_Autocomplete_Model_Adminhtml_FieldList
{
	
	function __construct()
	{
	}
	public function toOptionArray()
    {
        /*
         * Fetch all the product attributes
         */
       $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();
        $options = array();
        foreach ($attributes as $attribute)
        {
            $label = $attribute->getFrontendLabel();
            $code = $attribute->getAttributecode();
            if ($code != "" && $label != "")
                $options[] = ['value' => $code,'label' => $label];
        }
        return $options;
    }
}
<?php 
 class Xrely_Autocomplete_Block_Adminhtml_Design extends Mage_Core_Block_Template{
   

    public function _toHtml()
    {
    	$helper = Mage::helper('xrely_autocomplete');
    	$action = $helper->getDesignRedirectFormAction();
    	$html = "<form method='POST' action='".$action."'  ><input name='API_KEY' type='hidden' value='".$helper->getAPIKey()."' /></form>";
    	return "<!DOCTYPE html>
<html>
<head>
	<title>Signing in to dashboard</title>
	".$html."
</head>
<body>
<center style='margin-top:10%;font-size:20px;color: #eb5e00;'><b>Signing in to XRelY Dashboard</b></center>
<script type='text/javascript'>
setTimeout(function(){document.forms[0].submit();},1000);
</script>
</body>
</html>";

    }
    
}
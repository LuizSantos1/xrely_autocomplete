document.observe("dom:loaded", function() {
	var anchors = document.getElementsByTagName("a");
	[].forEach.call(anchors , function (anchor) {
	if (anchor.getAttribute("href").indexOf("xrely_autocomplete/adminhtml_redirect/") > -1) 
	{
	         anchor.setAttribute("target", "_blank");
	  }
	});
});
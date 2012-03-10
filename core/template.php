<?
//header("Expires: Thu, 29 Oct 1998 17:04:19 GMT");
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

##
# template.php :: Template, including CSS for display
##

# global settings
require("../template.php");

/*
# If this script is called by itself, abort
if (SELF == "template.php") {
	exit;
}

$reload = ""; # temporary : sometimes menu refreshes over and over
$bgColor = TMPL_bgColor;

if ( defined("CUBIT_MENU_PAGE") ) {
	$js_hide_menu = "";
} else {
	$js_hide_menu = "
		<script>
			document.captureEvents(Event.MOUSEMOVE);
			document.onmousemove = top.theframe.cubitmenuItemMouseOut;
		</script>";
}

print "
<html>
<head>
<META HTTP-EQUIV=Expires CONTENT='Sun, 22 Mar 1998 16:18:35 GMT'>
<title>".TMPL_title."</title>
<style type='text/css'>
<!--
	body
	{
		font-family: ".TMPL_fntFamily.";
		background-color: $bgColor;
		font-size: ".TMPL_fntSize."pt;
		color: ".TMPL_fntColor.";
	}
	td, p, .text
	{
		font-family: ".TMPL_fntFamily.";
		font-size: ".TMPL_fntSize."pt;
		color: ".TMPL_fntColor2.";
	}
	a
	{
		color: ".TMPL_lnkColor.";
		text-decoration: none;
	}
	a:hover
	{
		color: ".TMPL_lnkHvrColor.";
		text-decoration: underline;
	}
	a.nav
	{
		color: ".TMPL_navLnkColor.";
	}
	a:hover
	{
		color: ".TMPL_navLnkHvrColor.";
	}
	h3, .h3
	{
		font-size: ".TMPL_h3FntSize."pt;
		color: ".TMPL_h3Color.";
	}
	h4, .h4
	{
		font-size: ".TMPL_h4FntSize."pt;
		color: ".TMPL_h4Color.";
	}
	.datacell
	{
		background-color: ".TMPL_tblDataColor1.";
	}
	.datacell2
	{
		background-color: ".TMPL_tblDataColor2.";
	}
	th
	{
		background-color: ".TMPL_tblHdngBg.";
		font-size: ".TMPL_fntSize."pt;
		color: ".TMPL_tblHdngColor.";
	}
	th.plain
	{
		background-color: ".TMPL_bgColor.";
		font-size: ".TMPL_fntSize."pt;
	}
	input, textarea, select
	{
		font-size: 10pt;
		border: 1px solid #000000;
		padding: 2px;
		color: #000000;
	}
	.right
	{
		text-align: right;
	}
	.err
	{
		color: #FF0000;
        	background-color: #FFFFFF;
                border: 2px solid ".TMPL_tblHdngBg.";
	}
	hr
	{
		color: #000000;
	}
	.white
	{
		color: #FFFFFF;
	}
	.select
	{
		width: 100%;
	}
-->
</style>
<script language='JavaScript' type='text/javascript'>
	function imgSwop (img_name, new_img_src) {
		document[img_name].src = new_img_src;
}
</script>
$js_hide_menu
</head>

$OUTPUT

</body>
</html>
";
exit;
*/
?>

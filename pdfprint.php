<?
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

//require_once ("settings.php");
require_once("dompdf/dompdf_config.inc.php");

# If this script is called by itself, abort
if (SELF == "pdfprint.php") {
	exit;
}

$out="
<html>
<head>
<title>".TMPL_title." : Press CTRL + P to Print, Then close this window</title>
<style type=\"text/css\">
<!--
	body
	{
		font-family: ".TMPL_fntFamily.";
		background-color: #FFFFFF;
		font-size: 10pt;
		color: #000000;
	}
	td, p
	{
		font-family: ".TMPL_fntFamily.";
		font-size: 10pt;
	}
	a
	{
		color: ".TMPL_lnkColor.";
		text-decoration: underline;
	}
	a:hover
	{
		color: ".TMPL_lnkColor.";
		text-decoration: underline;
	}
	h3, .h3
	{
		font-size: ".TMPL_h3FntSize."pt;
	}
	h4, .h4
	{
		font-size: ".TMPL_h4FntSize."pt;
	}
	.datacell
	{
		background-color: #FFFFFF;
	}
	.datacell2
	{
		background-color: #FFFFFF;
	}
	th
	{
		background-color: #000000;
		color: #FFFFFF;
		font-size: 10pt;
		text-align: center;
	}
	th.plain
	{
		background-color: #FFFFFF;
		font-size: 10pt;
		text-align: center;
	}
	.border
	{
		border: 1px black solid;
	}
-->
</style>
</head>
<body>
$OUTPUT
</body>
</html>";


$dompdf = new DOMPDF();
$dompdf->load_html($out);

$dompdf->render();
$dompdf->stream("file.pdf");
?>

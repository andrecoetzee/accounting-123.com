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

// mark this as the undocked menu
$docked = false;

db_conn('cubit');
$Sl="SELECT * FROM users WHERE username='".USER_NAME."'";
$Ri=db_exec($Sl);

$data=pg_fetch_array($Ri);

if($data['help']!="S" && $data['help']!="P") {
// create the services menu used at the left
	$OUTPUT = "
	<table height='100%' width='100%'>
	<tr><td valign=top>
	<div id=\"services_menu\" style=\"width:0px; border-left: 1px solid #FFFFFF; border-bottom: 1px solid #FFFFFF\"></div>
	<a class=nav href='doc-index.php?action=dock' target=theframe>Move to Top</a>
	</td>
	</tr>
	</table>
	
	<script type=\"text/javascript\">
	var servicesMenu = [
		// MENU Services";
} else {
	$OUTPUT="<table height='100%' width='100%'>
	<tr><td valign=top>
	<div id=\"services_menu\" style=\"width:0px; border-left: 1px solid #FFFFFF; border-bottom: 1px solid #FFFFFF\"></div>
	
	</td>
	</tr>
	</table>
	
	<script type=\"text/javascript\">
	var servicesMenu = [
		// MENU Services";
}

// get te service menu's data
require("lmenu_services.php");

$OUTPUT .= "
];

	cubitmenuDraw (servicesMenu, 'services_menu', 'vv', cubitmenuObject, 'left');

	// [null, '', '', 'mainframe', null],
</script>";

$LEFT_MENU=$OUTPUT;
?>

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

require("../../settings.php");
require("../https_urlsettings.php");

if ( ! isset($_GET["step"]) ) {
	$OUTPUT = "<li class=err>Invalid use of module</li>";
	require("../../template.php");
}

$OUTPUT = choose_step();

require("../../template.php");

function choose_step() {
	global $_GET;
	extract($_GET);

	switch($step) {
	case "0":
		if ( ! isset($msg) ) $msg = "";
		$OUTPUT = "$msg";
		break;

	case "1":
		$OUTPUT = "
		<h3>General Message</h3>
		<form method=get action='".SELF."'>
		<input type=hidden name=step value='2'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan=2>Message Details</th>
		</tr>
		<tr class='bg-odd'>
			<td>Cell Number:</td>
			<td><input name=cellnum type=text></td>
		</tr>
		<tr class='bg-even'>
			<td>Message</td>
			<td><textarea cols=25 rows=4 name=message></textarea></td>
		</tr>
		<tr>
			<td colspan=2 align=center><input type=submit value='Send'></td>
		</tr>
		</table>
		</form>";
		break;

	case "2":
		$message = str_replace("=", "|", base64_encode($message));
		$request = @file( urler(GENERALMSG_URL."?cellnum=$cellnum&message=$message&".sendhash()) );

		if ( $request == false ) {
			return "<li class=err>Connection failed. Check your internet connection and try again.</li>";
		}

		$OUTPUT = implode("", $request);
		break;
	}

	return $OUTPUT;
}

?>

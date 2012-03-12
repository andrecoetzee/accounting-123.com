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

require ("settings.php");
require ("core-settings.php");
require ("libs/ext.lib.php");
require_lib("docman");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printPurch($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
    # Display default output
    $OUTPUT = slct();
}

require ("template.php");




# Default view
function slct($purnum = "", $err = "")
{

	//layout
	$slct = "
		<h3>Receive Purchase<h3>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<td>$err</td>
			</tr>
			<tr>
				<th>Purchase Number</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='5' name='purnum'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='submit' value='Continue'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='purchase-view.php'>View purchases</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slct;

}


# show invoices
function printPurch($_POST)
{

	# get vars
	extract ($_POST);
	
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($purnum, "num", 1, 10, "Invalid Purchase number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return slct($purnum, $confirm);
	}

	db_connect ();

	# local
	$sql = "SELECT * FROM purchases WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($purRslt) > 0) {
		$pur = pg_fetch_array($purRslt);
		if($pur['supid'] <> 0){
			$recv = "purch-recv.php?purid=$pur[purid]";
		}else{
			$recv = "purch-recv-cash.php?purid=$pur[purid]";
		}
		header("Location: $recv");
	}

	# inter
	$sql = "SELECT * FROM purch_int WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($purRslt) > 0) {
		$pur = pg_fetch_array($purRslt);
		$recv = "purch-int-recv.php?purid=$pur[purid]";
		header("Location: $recv");
	}

	# nons
	$sql = "SELECT * FROM nons_purchases WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($purRslt) > 0) {
		$pur = pg_fetch_array($purRslt);
		$recv = "nons-purch-recv.php?purid=$pur[purid]";
		header("Location: $recv");
	}

	# inter nons
	$sql = "SELECT * FROM nons_purch_int WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($purRslt) > 0) {
		$pur = pg_fetch_array($purRslt);
		$recv = "nons-purch-int-recv.php?purid=$pur[purid]";
		header("Location: $recv");
	}

	return slct($purnum, "<li class=err> - Purchase number not found in outstanding purchases.");

}



?>
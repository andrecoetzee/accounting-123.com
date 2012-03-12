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
# get settings
require ("../settings.php");
require ("../core-settings.php");
require ("../libs/ext.lib.php");

if (isset($_GET['listid'])){
	$OUTPUT = edit ($_GET['listid']);
} else {
	$OUTPUT = "<li> - Invalid use of module.</li>";
}

# display output
require ("../template.php");




function edit($listid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
		return "<li> Invalid Price List ID.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
	}

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	$enter = "
		<h3>Price List</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr>
				<td>Price list</td>
				<td align='center'>$list[listname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
			</tr>";

		# Query server
		$i = 0;
		db_conn('exten');
		$sql = "SELECT * FROM plist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' AND show='Yes' ORDER BY stkid ASC";
		$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
		if (pg_numrows ($stkpRslt) < 1) {
			return "<li class='err'> There are no stock item on the selected pricelist.</li>";
		}
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);

			$enter .= "
				<tr>
					<td>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
					<td align='right'>".CUR." ".sprint($stkp['price'])." $vattype</td>
				</tr>";
		}

		$enter .= "
			</table>";

	$OUTPUT = $enter;
	require("../tmpl-print.php");

}



?>
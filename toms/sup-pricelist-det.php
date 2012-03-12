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
	$OUTPUT = "<li> - Invalid use of module";
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
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

		# Select Stock
		db_conn("exten");
		$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
        $listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($listRslt) < 1){
                return "<li> Invalid Price List ID.";
        }else{
                $list = pg_fetch_array($listRslt);
        }

		$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

		$enter =
		"<h3>Supplier Price List</h3>
		<form action='".SELF."' method=post>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Price list</td><td align=center>$list[listname]</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2><h3>Prices</h3><td><tr>
		<tr><th>Item</th><th>Price Amount</th></tr>";

		# Query server
		$i = 0;
		db_conn('exten');
		$sql = "SELECT * FROM splist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' ORDER BY stkid ASC";
		$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
		if (pg_numrows ($stkpRslt) < 1) {
			return "<li class=err> There are no stock item on the selected pricelist.";
		}
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$enter .= "<tr bgcolor='$bgColor'><td>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td><td align=right>".CUR." $stkp[price] $vattype</td></tr>";
		}

		$enter .= "
		</table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

	return $enter;
}
?>

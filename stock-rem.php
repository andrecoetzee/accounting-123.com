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

# Get settings
require("settings.php");

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "rem":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['stkid'])){
					$OUTPUT = confirm ($_GET['stkid']);
			} else {
					$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
        if (isset($_GET['stkid'])){
                $OUTPUT = confirm ($_GET['stkid']);
        } else {
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# Get template
require("template.php");

# Confirm
function confirm($stkid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");

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
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
        $stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $stk = pg_fetch_array($stkRslt);
        }

		# get stock vars
		foreach ($stk as $key => $value) {
			$$key = $value;
		}

		db_conn("exten");
		# get warehouse
		$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		// Layout
		$confirm =
		"<h3>Remove Stock</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=45%>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=rem>
		<input type=hidden name=stkid value='$stkid'>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Store</td><td>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock code</td><td>$stkcod</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock description</td><td><pre>$stkdes</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td>$catname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Product class</td><td>$classname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Bought Unit of measure</td><td>$buom</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Selling Unit of measure</td><td>$suom</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Selling Units per Bought unit</td><td>$rate</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum level</td><td>$minlvl</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum level</td><td>$maxlvl</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Cost price per selling unit</td><td>".CUR." $csprice</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'>
			<td>Selling price per selling unit</td>
			<td>".CUR." ".sprint($selamt)."</td>
		</tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Remove &raquo'></td></tr>
		</form>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='#88BBFF'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<tr bgcolor='#88BBFF'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $confirm;
}

# Write
function rem($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
	}else{
			$stk = pg_fetch_array($stkRslt);
	}

	# Get stock vars
	foreach ($stk as $key => $value) {
		$$key = $value;
	}

	# Remove stock
	db_connect();
	$sql = "DELETE FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to remove stock from Cubit.",SELF);

	db_conn("exten");
	$Sl="DELETE FROM plist_prices WHERE stkid='$stkid' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to remove stock prices from database.");

	$Sl="DELETE FROM splist_prices WHERE stkid='$stkid' AND div = '".USER_DIV."'";
        $Rs = db_exec ($Sl) or errDie ("Unable to remove stock prices from database.");
	// Layout
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Stock removed from Cubit</th></tr>
	<tr class=datacell><td>Stock, $stkdes ($stkcod) has been successfully removed from Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='stock-view.php'>View Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

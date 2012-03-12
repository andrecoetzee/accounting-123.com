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
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "details":
				$OUTPUT = details($_POST);
				break;

			case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
				$OUTPUT = write($_POST);
				break;

            default:
				$OUTPUT = view();
			}
	} else {
		$OUTPUT = view();
	}
}

# get templete
require("../template.php");

# Default view
function view()
{

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec ($sql) or errDie ("Unable to view Stores");
	if (pg_numrows ($whRslt) < 1) {
		return "<li class=err>There are no Stores found in Cubit.";
	}else{
		$whs = "<select name='whid'>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>$wh[whname]</option>";
		}
		$whs .= "</select>";
	}


	// Layout
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=confirm>
		<tr><th colspan=2>Increase Selling Price</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Store</td><td valign=center>$whs</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Increase Percentage</td><td valign=center><input type=text size=5 name=incp maxlength=5>%</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $view;
}

# Default view
function view_err($_POST, $err = "")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec ($sql) or errDie ("Unable to view Stores");
	if (pg_numrows ($whRslt) < 1) {
		return "<li class=err>There are no Stores found in Cubit.";
	}else{
		$whs = "<select name='whid'>";
		while($wh = pg_fetch_array($whRslt)){
			if($wh['whid'] == $whid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$whs .= "<option value='$wh[whid]' $sel>$wh[whname]</option>";
		}
		$whs .= "</select>";
	}

	// Layout
	$view = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=confirm>
		<tr><th colspan=2>Increase Selling Price</th></tr>
		<tr><td colspan=2>$err</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Stores</td><td valign=center>$whs</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Increase Percentage</td><td valign=center><input type=text size=5 name=incp value='$incp' maxlength=5>%</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $view;
}

# details
function confirm($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 20, "Invalid Store number.");
	$v->isOk ($incp, "float", 1, 20, "Invalid Increase Percentage.");

	# display errors, if any
	if ($v->isError ()) {
		$error = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		# $confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return view_err($_POST, $error);
	}

	# get warehouse name
	db_conn("exten");
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# Set up table to display in
	$printStk = "
    <h4>Stock</h4>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Code</th><th>Description</th><th>Selling Price</th><th>Selling Price + $incp%</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
    $stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		$error = "<li class=err> There are no stock items under the selected warehouse.";
		return view_err($_POST, $error);
	}
	while ($stk = pg_fetch_array ($stkRslt)) {
		# get category account name
		db_connect();
		$sql = "SELECT cat FROM stockcat WHERE catid = '$stk[catid]' AND div = '".USER_DIV."'";
		$catRslt = db_exec($sql);
		$cat = pg_fetch_array($catRslt);

		$csprice = ($stk['csprice'] *($incp/100));
		$csprice = round(($csprice + $stk['csprice']), 2);
		$stk['csprice'] = round($stk['csprice'], 2);

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printStk .= "<tr bgcolor='$bgColor'><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>".CUR." $stk[csprice]</td><td align=right>".CUR." $csprice</td></tr>";
		$i++;
	}
	$printStk .= "</table>";

	// Layout
	$confirm = "<br><br><form action='".SELF."' method=post name=form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<input type=hidden name=key value=write>
		<input type=hidden name=whid value='$whid'>
		<input type=hidden name=incp value='$incp'>
		<tr><th colspan=2>Increase Selling Price</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Store</td><td valign=center>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Increase Percentage</td><td valign=center>$incp %</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	$printStk
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# Write
function write($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 20, "Invalid Store number.");
	$v->isOk ($incp, "float", 1, 20, "Invalid Increase Percentage.");

	# display errors, if any
	if ($v->isError ()) {
		$error = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		# $confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return view_err($_POST, $error);
	}

	# get warehouse name
	db_conn("exten");
	$sql = "SELECT whname,whno FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# Query server
	db_connect ();
	$sql = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
    $stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		$error = "<li class=err> There are no stock items in the selected warehouse.";
		return view_err($_POST, $error);
	}

	# Begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		while ($stk = pg_fetch_array ($stkRslt)) {
			$csprice = ($stk['csprice'] *($incp/100));
			$csprice = round(($csprice + $stk['csprice']), 2);

			$sql = "UPDATE stock SET selamt = '$csprice' WHERE stkid = '$stk[stkid]' AND div = '".USER_DIV."'";
			$upRslt = db_exec ($sql) or errDie ("Unable to update stocks in Cubit.");
		}

	# Commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=50%>
		<tr><th>Selling Price Increased</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Selling Price for All Items in Store <b>$wh[whno] - $wh[whname]</b> have been increase by <b>$incp%</b>.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;
}
?>

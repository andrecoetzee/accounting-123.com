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
require("core-settings.php");
require("libs/ext.lib.php");

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['id'])){
					$OUTPUT = confirm ($_GET['id']);
			} else {
					$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
        if (isset($_GET['id'])){
                $OUTPUT = confirm ($_GET['id']);
        } else {
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# Get template
require("template.php");

# Confirm
function confirm($id)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid transit number.");

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
	$sql = "SELECT * FROM transit WHERE id = '$id' AND div = '".USER_DIV."'";
	$tranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($tranRslt) < 1){
		return "<li> Invalid transit number.";
	}else{
		$tran = pg_fetch_array($tranRslt);
	}

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM stock WHERE stkid = '$tran[stkid]' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	$serials = "";
	$sRs = undget("cubit", "*", "transerial", "tid", $id);
	if(pg_numrows($sRs) > 0){
		$serials = "<tr><th colspan=2>Units Serial Numbers</th></tr>";
		while($ser = pg_fetch_array($sRs)){
			$serials .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2 align=center>$ser[serno]</td></tr>";
		}
	}

	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$tran[sdiv]'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	db_conn("exten");
	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$tran[swhid]' AND div = '$tran[sdiv]'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	// Layout
	$confirm =
	"<center>
	<h3>Cancel Stock Transfer</h3>
	<h4>Confirm Details</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td>$bran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Warehouse</td><td>$wh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td>$stk[catname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock description</td><td>".nl2br($stk['stkdes'])."</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>On Hand</td><td>$stk[units]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Allocated</td><td>$stk[alloc]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Available</td><td>$avstk</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>On Order</td><td>$stk[ordered]</td></tr>
		<tr><td><br></td></tr>
		$serials
		<tr><td><br></td></tr>
		<tr><th colspan=2>Transfer to</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To Branch</td><td>$sbran[branname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>To Store </td><td>$swh[whname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Number of units</td><td>$tran[tunits]</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Confirm &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-transit-view.php'>View Stock in transit</a></td></tr>
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
	$v->isOk ($id, "num", 1, 50, "Invalid transit number.");

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
	$sql = "SELECT * FROM transit WHERE id = '$id' AND div = '".USER_DIV."'";
	$tranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($tranRslt) < 1){
		return "<li> Invalid transit number.";
	}else{
		$tran = pg_fetch_array($tranRslt);
	}

	$sRs = undget("cubit", "*", "transerial", "tid", $id);
	if(pg_numrows($sRs) > 0){
		while($ser = pg_fetch_array($sRs)){
			ext_uninvSer($ser['serno'], $tran['stkid']);
		}
	}

	/* start replcing stock */

		# Put stock back
		$sql = "UPDATE stock SET units = (units + '$tran[tunits]'), csamt = (csamt + '$tran[cstamt]') WHERE stkid = '$tran[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);

		# Remove stock from transit
		$sql = "DELETE FROM transit WHERE id = '$id' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock from transit.",SELF);

		# todays date
		$date = date("d-m-Y");

		$refnum = getrefnum($date);

		# dt(conacc) ct(stkacc)
		# writetrans($wh['conacc'], $wh['stkacc'], $date, $refnum, $csamt, "Stock Transfer", USER_DIV);

	/* End replcing stock */

	// Layout
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Stock tranfer cancelled</th></tr>
	<tr class=datacell><td>Stock tranfer has been cancelled.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-transit-view.php'>View Stock in transit</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='stock-view.php'>View Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

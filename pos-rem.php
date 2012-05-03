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
require("settings.php");
require("libs/ext.lib.php");


# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "write":
			$OUTPUT = write($_POST);
			break;
                default:
			$OUTPUT = order($_POST);
	}
} elseif (isset($_GET["id"])) {
        # Display default output
	$_POST["id"]=$_GET["id"];
	$_POST["rid"]=$_GET["rid"];

	$OUTPUT = order($_POST);
	}

else {
        # Display default output

	$OUTPUT = order($_POST);

}

# get templete
require("template.php");

function order($_POST,$errors="")
{
	$Out="";
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}



	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($rid, "num", 1, 10, "Invalid Stock id.");
	$v->isOk ($id, "num", 1, 100, "Invalid POS id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn("cubit");

	$Sl = "SELECT stkcod,stkdes,units,alloc FROM stock WHERE stkid='$rid'";
	$Rs = db_exec ($Sl) or errDie ("Unable to view clients");
	if(pg_numrows($Rs)<1) {return "Invalid Stock id.";}
	$St = pg_fetch_array($Rs);
	$Av=$St['units']-$St['alloc'];

	switch (substr($id,(strlen($id)-1),1)) {
			case "0":
				$tab="ss0";
				break;
			case "1":
				$tab="ss1";
				break;
			case "2":
				$tab="ss2";
				break;
			case "3":
				$tab="ss3";
				break;
			case "4":
				$tab="ss4";
				break;
			case "5":
				$tab="ss5";
				break;
			case "6":
				$tab="ss6";
				break;
			case "7":
				$tab="ss7";
				break;
			case "8":
				$tab="ss8";
				break;
			case "9":
				$tab="ss9";
				break;
			default:
				return "Invalid Bar Code";
		}

	$Sl = "SELECT code FROM ".$tab." WHERE code='$id' AND active = 'yes'";
	$Rs = db_exec ($Sl) or errDie ("Unable to view clients");
	if(pg_numrows($Rs)<1) {return "Invalid Bar code";}

	$account_dets =
	"$errors
	<h3>Confirm Bar Code Removal</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=rid value='$rid'>
	<input type=hidden name=tab value='$tab'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
	<tr><th colspan=2>Stock Details</th>
	<tr class='bg-odd'><td>Code: $St[stkcod]</td>
	<tr class='bg-even'><td>Description: $St[stkdes]</td>
	<tr class='bg-odd'><td>Bar Code: $id</td>
	<tr><td valign=center><input type=submit value='Remove >>>'></td></tr>
	</form>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=30%>
	 <tr><td><br><br></tr>
	 <tr><th>Quick Links</th></tr>
	 <tr class='bg-odd'><td><a href='stock-view.php'>View Stock</td>
	 <tr class='bg-odd'><td><a href='main.php'>Main Menu</td>
	 </tr>
	</table>";



	return $account_dets;
}

# Write Barecode Info
function write($_POST)
{
	$Out="";
	#get & send vars
	foreach ($_POST as $key => $value) {

		$$key = $value;
		$Out .="<input type=hidden name=$$key value='$value'>";
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id, "num", 1, 100, "Invalid bar code.");
	$v->isOk ($rid, "num", 1, 10, "Invalid stock code.");
	$v->isOk ($tab, "string", 3, 3, "Invalid stock code.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = "";
		$Errors = $v->getErrors();
		foreach ($Errors as $e) {
			$errors .= "<li class=err>".$e["msg"];
		}
		$errors .= "<input type=hidden name=errors value='$errors'>";
		return order($_POST,$errors);
	}

	db_conn("cubit");
	
	$Sl = "DELETE FROM ".$tab." WHERE code='$id'";
	$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);

	header ("Location: pos.php?id=$rid");
	exit;

}

?>

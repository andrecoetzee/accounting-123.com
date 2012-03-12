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
require("../libs/ext.lib.php");

# decide what to do
if(isset($_GET['cashid'])){
	$OUTPUT = confirm($_GET['cashid']);
}else{
	$OUTPUT = "<li class=err> Invalid use of mudule";
}

# get template
require("../template.php");

function confirm($cashid)
{

	global $_GET;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	if(isset($_GET["type"]) AND ($_GET["type"] == "cash")){
		$bou = "cashbook";
	}else {
		$bou = "batch_cashbook";
	}

	// Connect to database
	Db_Connect ();
	$sql = "SELECT * FROM $bou WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	$accnt = pg_fetch_array($accntRslt);

	$confirm = "
	<center>
	<table ".TMPL_tblDflts." width='300'>
		<tr>
			<td colspan='2'><h3>Mutliple Debtors</h3></td>
		</tr>
		".TBL_BR."
		<tr>
			<th>Customer Name</th>
			<th>Amount</th>
		</tr>";

	$accids = explode(",", $accnt['multicusnum']);
	$amounts = explode(",", $accnt['multicusamt']);

	foreach($amounts as $key => $amt){
		$ci = qryCustomer($accids[$key]);
		$amt = sprint($amt);
		$confirm .= "
		<tr bgcolor='".TMPL_tblDataColor2 ."'>
			<td>$ci[surname], $ci[cusname]</td>
			<td>".CUR." $amt</td>
		</tr>";
	}

	$confirm .="<tr><td><br></td></tr>
	<tr><td colspan=2 align=center><input type=button value=' [X] Close ' onClick='javascript:window.close();'></td></tr>
	</table>";

	return $confirm;
}
?>

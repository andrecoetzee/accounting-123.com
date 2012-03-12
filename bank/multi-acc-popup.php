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
	$OUTPUT = "<li class='err'> Invalid use of mudule.</li>";
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
					<table ".TMPL_tblDflts." width='330'>
						<tr>
							<td colspan='2'><h3>Entry Accounts</h3></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th>Account</th>
							<th>Amount</th>
							<th>VAT</th>
						</tr>";

	$accids = explode("|", $accnt['accids']);
	unset($accids[0]);
	$amounts = explode("|", $accnt['amounts']);
	unset($amounts[0]);
	$vats = explode("|",$accnt['vats']);
	unset($vats[0]);

	foreach($amounts as $key => $amt){
		# get account name for the account involved
		$AccRslt = get("core","accname,topacc,accnum","accounts", "accid", $accids[$key]);
		$accinv = pg_fetch_array($AccRslt);
		$amt = sprint($amt);
		$confirm .= "
						<tr bgcolor='".bgcolorg() ."'>
							<td>$accinv[topacc]/$accinv[accnum] - $accinv[accname]</td>
							<td nowrap>".CUR." $amt</td>
							<td nowrap>".CUR." $vats[$key]</td>
						</tr>";
	}

	$confirm .= "
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2' align='center'><input type='button' value=' [X] Close ' onClick='javascript:window.close();'></td>
						</tr>
					</table>";
	return $confirm;

}


?>
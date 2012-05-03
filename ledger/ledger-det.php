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


# trans-new.php :: debit-credit Transaction
#
##

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if(isset($_GET['ledgid'])){
	$OUTPUT = det($_GET);
}else{
	$OUTPUT = "<li class='err'> Invalid use of module.</li>";
}

# get templete
require("../template.php");




# Remove
function det($_GET)
{

	# Get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ledgid, "num", 1, 20, "Invalid Input Ledger Number.");

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

	# Get ledger settings
	core_connect();
	$sql = "SELECT * FROM in_ledgers WHERE ledgid='$ledgid' AND div = '".USER_DIV."'";
	$ledRslt = db_exec($sql);
	if(pg_numrows($ledRslt) < 1){
		return "<li>Invalid Input Ledger Number.</li>";
	}
	$led = pg_fetch_array($ledRslt);

	foreach($led as $key => $value){
		$$key = $value;
	}

	# Account numbers
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);
	if($chrgvat == 'yes'){
		$vataccRs = get("core","*","accounts","accid",$vataccid);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatin = ucwords($vatinc);
		$vataccnum = "
			<tr class='".bg_class()."'>
				<td>Vat Account</td>
				<td><input type='hidden' name='vataccid' value='$vataccid'>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Vat Inclusive</td>
				<td><input type='hidden' name='vatinc' value='$vatinc'>$vatin</td>
			</tr>";
	}else{
		$vataccnum = "";
	}

	/* Toggle Options */

	# Charge Vat Option
	$vat = ucwords($chrgvat);

	# Date Option
	if($dateopt == 'system'){
		$date = 'System Date';
	}elseif($dateopt == 'user'){
		$date = 'User Input Date';
	}

	# Description and Refnum Option
	$options = array("num"=>"Auto Number", "emp"=>"Empty Input Box", "once"=>"Once Only Setting", "edit"=>"Default Editable Input");
	$descriptopt = $options[$desopt];
	$refnumopt = $options[$refopt];

	# put auto number if its auto number
	if($refopt == 'num'){
		$refnums = $options[$refopt];
	}else{
		$refnums = $refnum;
	}

	/* End Toggle Options */

	# uppercase first letter of name
	$lname = ucfirst($lname);

	// Details
	$details = "
		<center>
		<h3>High Speed Input Ledger details</h3>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Option</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Ledger Name</td>
				<td>$lname</td>
			</tr>
			<tr>
				<th><h4>Debit</h4></th>
				<th><h4>Credit</h4></th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td align='center'>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='3'>Options</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Number of Entries</td>
				<td>$numtran</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Date Entry</td>
				<td>$date</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Charge Vat </td>
				<td>$vat</td>
			</tr>
			$vataccnum
			<tr>
				<th colspan='3'>Description</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td>$descript</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Option</td>
				<td>$descriptopt</td>
			</tr>
			<tr>
				<th colspan='3'>Reference Number</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference Number</td>
				<td>$refnums</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Option</td>
				<td>$refnumopt</td>
			</tr>
			<tr><td><br></td></tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='ledger-new.php'>New High Speed Input Ledger</td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='ledger-view.php'>View High Speed Input Ledgers</td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $details;

}



?>
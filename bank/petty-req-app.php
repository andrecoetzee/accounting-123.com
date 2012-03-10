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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "cancel":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			# Display default output
			if(isset($HTTP_GET_VARS['cashid'])){
				$OUTPUT = confirm($HTTP_GET_VARS['cashid']);
			}else{
				$OUTPUT = "<li class='err'> Invalid use of mudule.</li>";
			}
	}
} else {
	# Display default output
	if(isset($HTTP_GET_VARS['cashid'])){
		$OUTPUT = confirm($HTTP_GET_VARS['cashid']);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of mudule.</li>";
	}
}

# Get template
require("../template.php");



# confirm
function confirm($cashid)
{

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



	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT = "<li clss='err'>Requisition not found in Cubit.</li>";
		return $OUTPUT;
	}
	$cash = pg_fetch_array($cashRslt);

	# check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	core_connect();

	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE accid = '$cashacc' AND div = '".USER_DIV."' AND month = '".PRD_DB."'";
	$accbRslt = db_exec($sql);
	if(pg_numrows($accbRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$accb = pg_fetch_array($accbRslt);
	$accb['bal'] = sprint($accb['bal']);

	# Mourn if the is not sufficient money
	if($cash['amount'] > $accb['bal']){
		return "<li class='err'>Error : Amount is more than the avaliable funds.</li>";
	}

	# Get account name for the account involved
	$accRslt = get("core","accname,accnum,topacc","accounts", "accid", $cash['accid']);
	$acc = pg_fetch_array($accRslt);

	# Keep the charge vat option stable
	if($cash['chrgvat'] == "inc"){
		$vchrgvat = "Yes";
	}elseif($cash['chrgvat'] == "exc"){
		$vchrgvat = "No";
	}else{
		$vchrgvat = "Non VAT";
	}

	#get actual vat amount
	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$cash[vatcode]'";
	$Ri = db_exec($Sl);

	$vd = pg_fetch_array($Ri);
	$VATP = $vd['vat_amount'];

	# If subtract vat
	if($cash['chrgvat'] == "inc"){
	//	$VATP = TAX_VAT;
		$VAT = sprint(($VATP/($VATP + 100)) * $cash['amount']);
		$samount = ($cash['amount'] - $VAT);
		
	}elseif($cash['chrgvat'] == "exc"){
	//	$VATP = TAX_VAT;
		$VAT = sprint(($VATP / 100) * $cash['amount']);
		$samount = ($cash['amount']);
		$cash['amount'] += $VAT;

	}else{
		$VAT = 0;
	}


	// Layout
	$confirm = "
		<h3>Approve Requisition</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='cancel'>
			<input type='hidden' name='cashid' value='$cash[cashid]'>
			<input type='hidden' name='vatcode' value='$cash[vatcode]'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>$cash[date]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid to</td>
				<td>$cash[name]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td>
				<td><pre>$cash[det]</pre></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td>".CUR." $cash[amount]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Inclusive</td>
				<td valign='center'>$vchrgvat</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Amount</td>
				<td valign='center'><input type='text' size='10' name='vat' value='$VAT'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$vd[description]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}


# write
function write($HTTP_POST_VARS)
{

    # get vars
	extract ($HTTP_POST_VARS);

	$vatcode += 0;
	
	$vat += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 4, "Invalid Reference number.");

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



	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	$cash = pg_fetch_array($cashRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($cash['date']) >= strtotime($blocked_date_from) AND strtotime($cash['date']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$refnum = getrefnum($cash['date']);

	# Check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode' AND zero='Yes'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	if(pg_num_rows($Ri) > 0) {
		$cash['chrgvat'] = "exc";
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$vd = pg_fetch_array($Ri);
	
	$VATP = $vd['vat_amount'];

	# If subtract vat
	if($cash['chrgvat'] == "inc"){
		# get vat account
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

//		$VATP = TAX_VAT;
		//$VAT = sprint(($VATP/($VATP + 100)) * $cash['amount']);
		$VAT = $vat;
		$samount = ($cash['amount'] - $VAT);
		//date("Y-m-d")
		# Write transaction
		writetrans($cash['accid'], $cashacc, $cash['date'], $refnum, $samount, $cash['det']);

		# Write VAT transaction
		writetrans($vatacc, $cashacc, $cash['date'], $refnum, $VAT, "VAT, ".$cash['det']);

		vatr($vd['id'],$cash['date'],"INPUT",$vd['code'],$refnum,"VAT, ".$cash['det'],-($samount+$VAT),-$VAT);

		# record vat statement
		/*
		db_connect();
		$sql = "INSERT INTO svatrec(edate, ref, amount, descript, div) VALUES('".date("Y-m-d")."', '$refnum', '-$VAT', 'VAT paid on Petty Cash requisition.', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
		*/
	}elseif($cash['chrgvat'] == "exc"){
		# get vat account
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");

//		$VATP = TAX_VAT;
//		$VAT = sprint(($VATP/100) * $cash['amount']);
		$VAT = $vat;
		$samount = ($cash['amount']);
		$cash['amount'] += $VAT;
		
		# Write transaction
		$VAT=$vat;
		writetrans($cash['accid'], $cashacc, $cash['date'], $refnum, $samount, $cash['det']);

		# Write VAT transaction
		writetrans($vatacc, $cashacc, $cash['date'], $refnum, $VAT, "VAT, ".$cash['det']);

		vatr($vd['id'],$cash['date'],"INPUT",$vd['code'],$refnum,"VAT, ".$cash['det'],-($samount+$VAT),-$VAT);

		# record vat statement
		/*
		db_connect();
		$sql = "INSERT INTO svatrec(edate, ref, amount, descript, div) VALUES('".date("Y-m-d")."', '$refnum', '-$VAT', 'VAT paid on Petty Cash requisition.', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
		*/
	}else{
		$samount = ($cash['amount']);
		# Write transaction
		writetrans($cash['accid'], $cashacc, $cash['date'], $refnum, $samount, $cash['det']);
	}


	# Update
	db_connect();

//	$date = date("Y-m-d");
	$date = $cash['date'];
	$sql = "
		INSERT INTO pettyrec (
			date, type, det, amount, name, div
		) VALUES (
			'$date', 'Req', '$cash[det]', '-$cash[amount]', 'Cash Paid to : $cash[name]', '".USER_DIV."'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	# Update
	$sql = "UPDATE pettycashbook SET approved = 'y',vatcode='$vatcode', vat_paid = '$vat',reced = 'no' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

	if(cc_TranTypeAcc($cash['accid'], $cashacc) != false){
		$cc_trantype = cc_TranTypeAcc($cash['accid'], $cashacc);
		$cc = "<script> CostCenter('$cc_trantype', 'Petty Cash Requisition', '$date', '$cash[det]', '$samount', '../'); </script>";
	}else{
		$cc = "";
	}

	# status report
	$write = "
		$cc
		<table ".TMPL_tblDflts." width='30%'>
			<tr>
				<th>Petty Cash Requisition Approved</th>
			</tr>
			<tr class='datacell'>
				<td>Petty Cash Requisition has been approved .</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>

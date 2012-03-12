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
require("../settings.php");
require("../core-settings.php");
require_lib("docman");
require("../libs/ext.lib.php");

# Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "cancel":
			$OUTPUT = write($_POST);
			break;
	case "confirm2":
			$OUTPUT = confirm2($_POST);
			break;
		default:
			# Display default output
			if(isset($_GET['cashid'])){
					$OUTPUT = confirm($_GET['cashid']);
			}else{
					$OUTPUT = "<li class=err> Invalid use of mudule";
			}
	}
} else {
	# Display default output
	if(isset($_GET['cashid'])){
			$OUTPUT = confirm($_GET['cashid']);
	}else{
			$OUTPUT = "<li class=err> Invalid use of mudule";
	}
}

# Get template
require("../template.php");

# confirm
function confirm($cashid)
{
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");

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

	# Connect to database
	db_Connect ();
	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT = "<li clss=err>Requisition not found in Cubit.";
		return $OUTPUT;
	}
	$cash = pg_fetch_array($cashRslt);

	# Check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
	core_connect();
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE accid = '$cashacc' AND div = '".USER_DIV."'";
	$accbRslt = db_exec($sql);
	if(pg_numrows($accbRslt) < 1){
		return "<li class=err> Petty Cash Account not found.";
	}
	$accb = pg_fetch_array($accbRslt);
	$accb['bal'] = sprint($accb['bal']);

	/* if f***** returning it u fool
	# Mourn if the is not sufficient money
	if($cash['amount'] > $accb['bal']){
		return "<li class=err>Error : Amount is more than the avaliable funds.
		<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}
	*/

	# Get account name for the account involved
	$accRslt = get("core","accname, accnum, topacc","accounts", "accid", $cash['accid']);
	$acc = pg_fetch_array($accRslt);

	// Layout
	$confirm ="<h3>Record Requisition Receipt</h3>
	<h4>Enter Data</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form name=form1 ENCTYPE='multipart/form-data' action='".SELF."' method=post>
	<input type=hidden name=key value=confirm2>
	<input type=hidden name=cashid value='$cash[cashid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$cash[date]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Paid to</td><td>$cash[name]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Details</td><td><pre>$cash[det]</pre></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td>".CUR." $cash[amount]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Account</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Receipt/Ref No.</td><td><input size=15 name='refno' value='$cash[refno]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Return/Change</td><td>".CUR." <input type=text size=10 name=ret value='0'></td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# confirm
function confirm2()
{
	global $_POST;

	extract($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Receipt/Ref No.");
	$v->isOk ($ret, "float", 0, 10, "Invalid Returned/Change amount.");


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

	# Connect to database
	db_Connect ();
	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT = "<li clss=err>Requisition not found in Cubit.";
		return $OUTPUT;
	}
	$cash = pg_fetch_array($cashRslt);

	# Check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
	core_connect();
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE accid = '$cashacc' AND div = '".USER_DIV."'";
	$accbRslt = db_exec($sql);
	if(pg_numrows($accbRslt) < 1){
		return "<li class=err> Petty Cash Account not found.";
	}
	$accb = pg_fetch_array($accbRslt);
	$accb['bal'] = sprint($accb['bal']);

	/* if f***** returning it u fool
	# Mourn if the is not sufficient money
	if($cash['amount'] > $accb['bal']){
		return "<li class=err>Error : Amount is more than the avaliable funds.
		<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}
	*/

	# Get account name for the account involved
	$accRslt = get("core","accname, accnum, topacc","accounts", "accid", $cash['accid']);
	$acc = pg_fetch_array($accRslt);

	if($ret <> 0){
		# If subtract vat
		if($cash['chrgvat'] == "inc"){
			$VATP = TAX_VAT;
			$vatret = sprint(($VATP/($VATP + 100)) * $ret);
		}elseif($cash['chrgvat'] == "exc"){
			$VATP = TAX_VAT;
			$vatret = sprint(($VATP/100) * $ret);
		} else {
			$vatret=0;
		}
	} else {
		$vatret=0;
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name=vatcode>
	<option value='0'>Select</option>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['del']=="Yes") {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	
	$Vatcodes.="</select>";

	// Layout
	$confirm ="<h3>Record Requisition Receipt</h3>
	<h4>Confirm entry</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form name=form1 ENCTYPE='multipart/form-data' action='".SELF."' method=post>
	<input type=hidden name=key value=cancel>
	<input type=hidden name=cashid value='$cash[cashid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$cash[date]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Paid to</td><td>$cash[name]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Details</td><td><pre>$cash[det]</pre></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td>".CUR." $cash[amount]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Account</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Receipt/Ref No.</td><td><input type=hidden name='refno' value='$refno'>$refno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Return/Change</td><td>".CUR." <input type=hidden name=ret value='$ret'>$ret</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT</td><td>".CUR." <input type=text size=20 name=vatret value='$vatret'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Code</td><td>$Vatcodes</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>File</td><td><input type=file size=20 name=doc></td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td align=right><input type=submit value='Write &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write
function write($_POST)
{
    # Get vars
	global $_FILES;
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	$vatcode+=0;
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 4, "Invalid Reference number.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Receipt/Ref No.");
	$v->isOk ($ret, "float", 0, 10, "Invalid Returned/Change amount.");
	$vatret+=0;

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
	
	$date = DATE_STD;

	if (is_uploaded_file ($_FILES["doc"]["tmp_name"])) {
		$doctyp = $_FILES["doc"]["type"];
		$filename = $_FILES["doc"]["name"];

		# Open file in "read, binary" mode
		$docu = "";
		$file = fopen ($_FILES['doc']['tmp_name'], "rb");
		while (!feof ($file)) {
			# fread is binary safe
			$docu .= fread ($file, 1024);
		}
		fclose ($file);

		# Compress and encode the file
		$docu = doclib_encode($docu, 9);
	}

	# Connect to database
	db_Connect ();
	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	$cash = pg_fetch_array($cashRslt);

	# Mourn if the is not sufficient money
	if($ret > $cash['amount']){
		return "<li class=err>Error : Returned/Change amount is more than the requisistion amount.</li>
		<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}

	# Ya Nasty zero
	$ret += 0;

	$refnum = getrefnum($date);

	# Check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE id='$vatcode' AND zero='Yes'";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	if(pg_num_rows($Ri)>0) {
		$cash['chrgvat']="exc";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$vd=pg_fetch_array($Ri);

	# if amount is not equal to zero, write tran
	if($ret <> 0){
		# If subtract vat
		if($cash['chrgvat'] == "inc"){
			# Get vat account
			$vatacc = gethook("accnum", "salesacc", "name", "VAT");

			$VATP = TAX_VAT;
			//$vatret = sprint(($VATP/($VATP + 100)) * $ret);
			$sret = ($ret - $vatret);

			# Write returning transaction
			writetrans($cashacc, $cash['accid'], date("Y-m-d"), $refnum, $sret, "Petty Cash Change");

			# Write VAT returning  transaction
			writetrans($cashacc, $vatacc, date("Y-m-d"), $refnum, $vatret, "VAT return, ".$cash['det']);

			vatr($vd['id'],date("Y-m-d"),"INPUT",$vd['code'],$refnum,"VAT return, ".$cash['det'],($sret+$vatret),$vatret);

			/*
			# Record vat statement
			db_connect();
			$sql = "INSERT INTO svatrec(edate, ref, amount, descript, div) VALUES('".date("Y-m-d")."', '$refnum', '$vatret', 'VAT returned on Petty Cash Change.', '".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			*/
		}elseif($cash['chrgvat'] == "exc"){
			# Get vat account
			$vatacc = gethook("accnum", "salesacc", "name", "VAT");

			$VATP = TAX_VAT;
			//$vatret = sprint(($VATP/100) * $ret);
			$sret = ($ret);
			$ret += $vatret;

			# Write returning transaction
			writetrans($cashacc, $cash['accid'], date("Y-m-d"), $refnum, $sret, "Petty Cash Change");

			# Write VAT returning  transaction
			writetrans($cashacc, $vatacc, date("Y-m-d"), $refnum, $vatret, "VAT return, ".$cash['det']);

			vatr($vd['id'],date("Y-m-d"),"INPUT",$vd['code'],$refnum,"VAT return, ".$cash['det'],($sret+$vatret),$vatret);

			/*
			# Record vat statement
			db_connect();
			$sql = "INSERT INTO svatrec(edate, ref, amount, descript, div) VALUES('".date("Y-m-d")."', '$refnum', '$vatret', 'VAT returned on Petty Cash Change.', '".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
			*/
		}else{
			# Write returning transaction
			writetrans($cashacc, $cash['accid'], date("Y-m-d"), $refnum, $ret, "Petty Cash Change");
			$sret = $ret;
		}

		# Record tranfer for patty cash report
		db_connect();
		$date = date("Y-m-d");
		$sql = "INSERT INTO pettyrec(date, type, det, amount, name, div) VALUES ('$date', 'Change', 'Petty Cash Change', '$ret', 'Cash Received From : $cash[name]', '".USER_DIV."')";
		$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
	}
	
	if(!isset($sret)) {
		$sret=0;
	}
	
	# Update
	db_connect();
	$sql = "UPDATE pettycashbook SET refno = '$refno', amount = (amount - '$ret'), reced = 'yes' WHERE cashid='$cashid' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

	if(isset($docu)){
		db_conn(YR_DB);
		$sql = "INSERT INTO documents(typeid, typename, xin, docref, docdate, docname, filename, mimetype, descrip, docu, div) VALUES ('prec', 'Petty Cash Receipt', '$refno', '$refno', '$date', '$filename', '$filename', '$doctyp', 'Receipt from $cash[name]', '$docu', '".USER_DIV."')";
		$docRslt = db_exec ($sql) or errDie ("Unable to add $docname to system.", SELF);
	}

	if(cc_TranTypeAcc($cashacc, $cash['accid']) != false){
		$cc_trantype = cc_TranTypeAcc($cashacc, $cash['accid']);
		$cc = "<script> CostCenter('$cc_trantype', 'Petty Cash Receipt', '$date', '$cash[det]', '$sret', '../'); </script>";
	}else{
		$cc = "";
	}

	# Status report
	$write = "$cc
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='30%'>
		<tr><th>Petty Cash Requisition Approved</th></tr>
		<tr class=datacell><td>Petty Cash Requisition has been approved .</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

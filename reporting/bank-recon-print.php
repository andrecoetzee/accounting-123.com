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
if (isset($_GET["id"])) {
	$OUTPUT = recon($_GET["id"]);
} else {
	# Display error
	$OUTPUT = "<li> Error : Invalid Bank Recon Number.";
}

require ("../template.php");

function recon($id)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 20, "Invalid Bank Recon number.");

		# display errors, if any
		if ($v->isError ()) {
			$theseErrors = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$theseErrors .= "<li class=err>".$e["msg"];
			}
			$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $theseErrors;
		}

		# connect to core DB
        core_connect();

        # get the Bank Recon
        $sql = "SELECT * FROM save_bank_recon WHERE id = '$id'";
        $recRslt = db_exec($sql) or errDie("Unable to retrieve Bank Recon from the Database",SELF);
        if(pg_numrows($recRslt) < 1){
                return "<center><li> Invalid Bank Recon Number.";
        }

        $rec = pg_fetch_array($recRslt);
		
	$recon = base64_decode($rec['recon']);

	db_conn('cubit');
	
	$payments="<p>
	<h3>Reconciled Payments</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='80%'>
	<tr><td><b>Date</b></td><td><b>To</b></td><td><b>Description</b></td><td><b>Cheque</b></td><td><b>Amount</b></td></tr>";
	
	$Sl="SELECT * FROM cashbook WHERE rid='$id' AND trantype='withdrawal'";
	$Ri=db_exec($Sl) or errDie("Unable to get data,");
	
	if(pg_num_rows($Ri)>0) {
		while($data=pg_fetch_array($Ri)) {
			$payments.="<tr><td>$data[date]</td><td>$data[name]</td><td>$data[descript]</td><td>$data[cheqnum]</td><td align=right>".CUR." ".sprint($data['amount'])."</td></tr>";
		}
		
		$payments.="</table>";
	} else {
		$payments="<p>There were no reconciled payments.";
	}
	
	$depos="<p>
	<h3>Reconciled Deposits</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='80%'>
	<tr><td><b>Date</b></td><td><b>From</b></td><td><b>Description</b></td><td><b>Cheque</b></td><td><b>Amount</b></td></tr>";
	
	$Sl="SELECT * FROM cashbook WHERE rid='$id' AND trantype='deposit'";
	$Ri=db_exec($Sl) or errDie("Unable to get data,");
	
	if(pg_num_rows($Ri)>0) {
		while($data=pg_fetch_array($Ri)) {
			$depos.="<tr><td>$data[date]</td><td>$data[name]</td><td>$data[descript]</td><td>$data[cheqnum]</td><td align=right>".CUR." ".sprint($data['amount'])."</td></tr>";
		}
		
		$depos.="</table>";
	} else {
		$depos="<p>There were no reconciled deposits.";
	}
	
	$OUTPUT = $recon."</table>".$payments.$depos;
	require("../tmpl-print.php");
}


















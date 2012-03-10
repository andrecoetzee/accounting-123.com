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

##
# trans-new.php :: Multiple debit-credit Transactions
##

# get settings
require("settings.php");
require("core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "writerem":
			$OUTPUT = writerem($HTTP_POST_VARS);
			break;
		default:
			if(isset($HTTP_POST_VARS['proc'])){
				# Process
				$OUTPUT = confirm($HTTP_POST_VARS);
			}elseif(isset($HTTP_POST_VARS["rem"])){
				# Remove
				$OUTPUT = confirmrem($HTTP_POST_VARS);
			}
	}
} else {
	if(isset($HTTP_POST_VARS["proc"])){
		# Process
		$OUTPUT = confirm($HTTP_POST_VARS);
	}elseif(isset($HTTP_POST_VARS["rem"])){
		# Remove
		$OUTPUT = confirmrem($HTTP_POST_VARS);
	}
}

# get templete
require("template.php");



# Confirm
function confirm($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($bank)){
		foreach($bank as $key => $value){
			$v->isOk ($bank[$key], "num", 1, 50, "Invalid Batch ID.");
		}
	}else{
		return "<li> - No Batch Entries Seleted. Please select at least one batch entry.</li>";
	}

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



	$refnum = getrefnum();
/*refnum*/

    # connect to core
    core_Connect();

    $trans = "";
    # batches
    foreach($bank as $key => $value){

    		core_Connect();

            # Get all the details
            $sql = "SELECT * FROM batch WHERE batchid = '$value' AND div = '".USER_DIV."'";
            $rslt = db_exec($sql) or errDie("Unable to access database.");
            $tran = pg_fetch_array($rslt);

            # get account to be debited
            $dtaccRs = get("core","accname","accounts","accid",$tran['debit']);
            if(pg_numrows($dtaccRs) < 1){
                    return "<li> Accounts to be debited does not exist.</li>";
            }
            $dtacc = pg_fetch_array($dtaccRs);

            # get account to be debited
            $ctaccRs = get("core","accname","accounts","accid",$tran['credit']);
            if(pg_numrows($ctaccRs) < 1){
                    return "<li> Accounts to be debited does not exist.</li>";
            }
            $ctacc = pg_fetch_array($ctaccRs);

            $trans .= "
        				<tr bgcolor=".bgcolorg().">
        					<td>
								<input type='hidden' name='batchid[]' value='$tran[batchid]'>
								<input type='hidden' name='bank[]' value='$value'>
								<input type='hidden' name='date[]' value='$tran[date]'>$tran[date]
							</td>
							<td><input type='text' size='7' name='refnum[]' value='".($refnum + $key)."'></td>
							<td valign='center'><input type='hidden' name='dtaccid[]' value='$tran[debit]'>$dtacc[accname]</td>
							<td valign='center'><input type='hidden' name='ctaccid[]' value='$tran[credit]'>$ctacc[accname]</td>
							<td><input type='hidden' name='amount[]' value='$tran[amount]'>".CUR." $tran[amount]</td>
							<td>
								<input type='hidden' name='descript[]' value ='$tran[details]'>$tran[details]
								<input type='hidden' name='vatcodes[]' value ='$tran[vatcode]'>
							</td>";


			if($tran['chrgvat'] == "yes"){
				$vataccRs = get("core","*","accounts","accid",$tran['vataccid']);
				$vatacc  = pg_fetch_array($vataccRs);
				$vataccRs = get("core","*","accounts","accid",$tran['vatdedacc']);
				$vatdedacc  = pg_fetch_array($vataccRs);
				$trans .= "
								<input type='hidden' name='chrgvat[$value]' value ='yes'>
								<td align='center'><input type='hidden' name='vatinc[$value]' value ='$tran[vatinc]'>$tran[vatinc]</td>
								<td align='center'><input type='hidden' name='vataccid[$value]' value ='$tran[vataccid]'>$vatacc[accname]</td>
								<td align='center'><input type='hidden' name='vatdedacc[$value]' value ='$tran[vatdedacc]'>$vatdedacc[accname]</td>";
			}else{
				$trans .= "<td></td><td></td><td></td>";
			}
			$trans .= "</tr>";
    }

        $confirm = "
						<center>
						<h3>Process Multiple Journal transactions</h3>
						<h4>Confirm entry</h4>
						<form action='".SELF."' method='POST'>
							<input type='hidden' name='key' value='write'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Date</th>
								<th>Ref num</th>
								<th>Debit</th>
								<th>Credit</th>
								<th>Amount</th>
								<th>Description</th>
								<th>VAT Inclusive</th>
								<th>VAT Account</th>
								<th>VAT Deductable Account</th>
							</tr>
							$trans
							<tr><td><br></td></tr>
							<tr>
								<td></td>
								<td align='right' colspan='2'><input type='submit' value='Write &raquo'></td>
							</tr>
						</form>
						</table>
						<table border='0' cellpadding='2' cellspacing='1' width='15%'>
							<tr><td><br></td></tr>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr class='datacell'>
								<td align='center'><a href='trans-new.php'>Journal Transactions</td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>";
	return $confirm;

}



# Write
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
    foreach($bank as $key => $value){
		$v->isOk ($batchid[$key], "num", 1, 10, "Invalid Batch ID.[$key]");
		$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
		$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
		$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
		$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
		$datea = explode("-", $date[$key]);
		if(count($datea) == 3){
			if(!checkdate($datea[1], $datea[2], $datea[0])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
			}
		}else{
			$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
		}
    }

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	foreach($bank as $key => $value){
		// Accounts details
		$dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$dtaccid[$key]);
		$dtacc[$key]  = pg_fetch_array($dtaccRs);
		$ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$ctaccid[$key]);
		$ctacc[$key]  = pg_fetch_array($ctaccRs);
	}

    // Start layout
    $write = "
				<center>
				<h3>Journal transactions have been recorded</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Date</th>
						<th>Ref num</th>
						<th>Debit</th>
						<th>Credit</th>
						<th>Amount</th>
						<th>Description</th>
						<th>VAT Amount</th>
						<th>Total Transaction Amount</th>
					</tr>";

    	$cc = "";
		foreach($bank as $key => $value){
			$write .= "
						<tr bgcolor=".bgcolorg().">
							<td>$date[$key]</td>
							<td>$refnum[$key]</td>
							<td valign='center'>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
							<td valign='center'>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
							<td>".CUR." $amount[$key]</td>
							<td>$descript[$key]</td>";

			if(isset($chrgvat[$value])){

				$datea = explode("-", $date[$key]);

				$cdate="$datea[2]-$datea[1]-$datea[0]";

				$vataccRs = get("core","*","accounts","accid", $vataccid[$value]);
				$vatacc  = pg_fetch_array($vataccRs);
				$vataccRs = get("core","*","accounts","accid", $vatdedacc[$value]);
				$vdedacc  = pg_fetch_array($vataccRs);

				//$VATP = TAX_VAT;

				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$key]'";
				$Ri=db_exec($Sl);

				$vd=pg_fetch_array($Ri);
				$VATP = $vd['vat_amount'];


				# if vat must be charged
				if($vatinc[$value] == "no"){
					$vatamt[$value] = sprint((($VATP/100) * $amount[$key]));
					$amt[$key] = sprint($amount[$key]);
					$totamt = sprint($amount[$key] + $vatamt[$value]);
				}else{
					$vatamt[$value] = sprint((($amount[$key]/($VATP + 100)) * $VATP));
					$amt[$key] = sprint($amount[$key] - $vatamt[$value]);
					$totamt = sprint($amount[$key]);
				}

				# Check VAt Deductable account
				if($vatdedacc[$value] == $dtaccid[$key]){

					db_connect();
					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$key]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return "Please select the vatcode";
					}

					$vd=pg_fetch_array($Ri);

					vatr($vd['id'],$date[$key],"INPUT",$vd['code'],$refnum[$key],"$descript[$key] VAT",-$totamt,-$vatamt[$value]);

					writetrans($vataccid[$value], $ctaccid[$key], $date[$key], $refnum[$key], $vatamt[$value], $descript[$key]."  VAT");
					writetrans($dtaccid[$key], $ctaccid[$key], $date[$key], $refnum[$key], $amt[$key], $descript[$key]);
				}elseif($vatdedacc[$value] == $ctaccid[$key]){

					db_connect();
					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$key]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return "Please select the vatcode";
					}

					$vd=pg_fetch_array($Ri);

					vatr($vd['id'],$date[$key],"OUTPUT",$vd['code'],$refnum[$key],"$descript[$key] VAT",$totamt,$vatamt[$value]);

					writetrans($dtaccid[$key], $vataccid[$value], $date[$key], $refnum[$key], $vatamt[$value], $descript[$key]."  VAT");
					writetrans($dtaccid[$key], $ctaccid[$key], $date[$key], $refnum[$key], $amt[$key], $descript[$key]);
				}
				$write .= "
							<td align='right'>".CUR." $vatamt[$value]</td>
							<td align='right'>".CUR." $totamt</td>";
			}else{
				$totamt[$key] = sprint($amount[$key]);
				# Write normal transaction
				writetrans($dtaccid[$key],$ctaccid[$key], $date[$key], $refnum[$key], $totamt[$key], $descript[$key]);
				$write .= "<td>0</td><td>".CUR." $totamt[$key]</td>";
			}
			$write .= "</tr>";



			db_connect ();
			$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' LIMIT 1";
			$banks = db_exec($sql);
			if(pg_numrows($banks) < 1){
				return "<li class='err'> There are no accounts held at the selected Bank.
				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
			}
			$barr = pg_fetch_array($banks);
			$bankid = $barr['bankid'];

			core_connect();
			$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
			# Check if link exists
			if(pg_numrows($rslt) <1){
				return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
			}

			$banklnk = pg_fetch_array($rslt);

			$cc_trantype = cc_TranTypeAcc($dtaccid[$key], $ctaccid[$key]);

			if($cc_trantype != false){
				$cc .= "
					<script>
						CostCenter('$cc_trantype', 'Batch Journal', '$date[$key]', '$descript[$key]', '$amount[$key]', '../');
					</script>";
			}else{
				$cc .= "";
			}


			db_conn('core');

			#process complete ... remove entry
			$rem_sql = "DELETE FROM batch WHERE batchid = '$batchid[$key]'";
			$run_sql = db_exec($rem_sql) or errDie("Unable to remove batch entry.");

		}

        $write .= "
						</table>
						$cc
						<br>
						<table ".TMPL_tblDflts." width='25%'>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr class='datacell'>
								<td align='center'><a href='trans-batch.php'>Add Journal Transactions to batch</td>
							</tr>
							<tr class='datacell'>
								<td align='center'><a href='batch-view.php'>View batch Entries</td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>";
        return $write;

}


# Confirm
function confirmrem($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

    # validate input
	require_lib("validate");
	$v = new  validate ();
    if(isset($bank)){
		foreach($bank as $key => $value){
			$v->isOk ($bank[$key], "num", 1, 50, "Invalid Batch ID.");
		}
	}else{
		return "<li> - No Batch Entries Seleted. Please select at least one batch entry.</li>";
	}

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

	$trans = "";
	db_conn('core');
	# batches
	foreach($bank as $key => $value){
		# Get all the details
		$sql = "SELECT * FROM batch WHERE batchid = '$value' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to access database.");
		$tran = pg_fetch_array($rslt);

		# get account to be debited
		$dtaccRs = get("core","accname","accounts","accid",$tran['debit']);
		if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$dtacc = pg_fetch_array($dtaccRs);

		# get account to be debited
		$ctaccRs = get("core","accname","accounts","accid",$tran['credit']);
		if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts to be debited does not exist.</li>";
		}
		$ctacc = pg_fetch_array($ctaccRs);

        $trans .= "
        			<tr bgcolor=".bgcolorg().">
        				<td><input type='hidden' size='20' name='bank[]' value='$value'>$tran[date]</td>
                        <td>$tran[refnum]</td>
                        <td valign='center'>$dtacc[accname]</td>
                        <td valign='center'>$ctacc[accname]</td>
                        <td>".CUR." $tran[amount]</td>
                        <td>$tran[details]</td>
                    </tr>";
        }

        $confirm = "
						<center>
						<h3>Remove Multiple Journal transactions from the batch file</h3>
						<h4>Confirm entry</h4>
						<form action='".SELF."' method='POST'>
							<input type='hidden' name='key' value='writerem'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Date</th>
								<th>Ref num</th>
								<th>Debit</th>
								<th>Credit</th>
								<th>Amount</th>
								<th>Description</th>
							</tr>
							$trans
							<tr><td><br></td></tr>
							<tr>
								<td></td>
								<td align='right' colspan='2'><input type='submit' value='Write &raquo'></td>
							</tr>
						</form>
						</table>
						<table border='0' cellpadding='2' cellspacing='1' width=15%>
							<tr><td><br></td></tr>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr class='datacell'>
								<td align='center'><a href='trans-new.php'>Journal Transactions</td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>";
	return $confirm;

}



# Write
function writerem($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($bank)){
		foreach($bank as $key => $value){
			$v->isOk ($bank[$key], "num", 1, 50, "Invalid Batch ID.");
		}
	}else{
		return "<li> - No Batch Entries Seleted. Please select at least one batch entry.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	db_conn('core');

    foreach($bank as $key => $value){
        # Get all the details
        $sql = "SELECT * FROM batch WHERE batchid = '$value' AND div = '".USER_DIV."'";
        $rslt = db_exec($sql) or errDie("Unable to access database.");
        $tran = pg_fetch_array($rslt);

        // Accounts details
        $dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$tran['debit']);
        $dtacc[$key]  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$tran['credit']);
        $ctacc[$key]  = pg_fetch_array($ctaccRs);

        $date[$key] = $tran['date'];
        $refnum[$key] = $tran['refnum'];
        $amount[$key] = $tran['amount'];
        $descript[$key] = $tran['details'];

        # Remove the entries one by one
        core_Connect();
        $query = "DELETE FROM batch WHERE batchid = '$bank[$key]'";
        $Ex = db_exec($query) or errDie("Unable to delete batch file entries.",SELF);
    }

    // Start layout
    $write = "
				<center>
				<h3>Journal transactions entries have been removed from batch file</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Date</th>
						<th>Ref num</th>
						<th>Debit</th>
						<th>Credit</th>
						<th>Amount</th>
						<th>Description</th>
					</tr>";

	foreach($bank as $key => $value){
        $write .= "
    				<tr bgcolor=".bgcolorg().">
    					<td>$date[$key]</td>
    					<td>$refnum[$key]</td>
                        <td valign='center'>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
                        <td valign='center'>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
                        <td>".CUR." $amount[$key]</td>
                        <td>$descript[$key]</td>
					</tr>";
	}

	$write .= "
						</table>
			        <br>
			        <table ".TMPL_tblDflts." width='25%'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='trans-batch.php'>Add Journal Transactions to batch</td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='batch-view.php'>View batch Entries</td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
			        </table>";
	return $write;

}


?>
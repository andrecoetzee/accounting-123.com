<?

require ("../settings.php");
require("../core-settings.php");

if(isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = confirm_list ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_list($HTTP_POST_VARS);
	}	
}else {
	$OUTPUT = get_list ($HTTP_POST_VARS);
}

if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
	require ("../tmpl-print.php");
}else {
	require ("../template.php");
}



function get_list ($HTTP_POST_VARS,$err="")
{


	db_conn ('contract');

	if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
		$buttons = "";
	}else {
		$buttons = "
						<tr>
							<td><input type='submit' name='print' value='Print'></td>
							<td colspan='2'><input type='submit' name='management' value='Print Management Report'></td>
							<td><input type='submit' name='process' value='Process'></td>
						</tr>
					";
	}


	$listing = "";

	if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
		$filter = "";
		if(isset($HTTP_POST_VARS['search'])){
			switch ($HTTP_POST_VARS['search']){
				case "name":
					$search_string = "proc_date";
					break;
				case "cheque":
					$search_string = "cheq_num";
					break;
				case "project":
					$search_string = "proc_date";
					break;
				default:
					$search_string = "proc_date, cheq_num";
			}
		}else {
			$search_string = "proc_date";
		}
	}else {
		$sal1 = "";
		$sal2 = "";
		$sal3 = "";
		if(isset($HTTP_POST_VARS['search'])){
			switch ($HTTP_POST_VARS['search']){
				case "name":
					$sal1 = "selected";
					$search_string = "proc_date";
					break;
				case "cheque":
					$sal2 = "selected";
					$search_string = "cheq_num";
					break;
				case "project":
					$sal3 = "selected";
					$search_string = "proc_date";
					break;
				default:
					$sal1 = "selected";
					$search_string = "proc_date, cheq_num";
			}
		}else {
			$search_string = "proc_date";
		}

		$filter = "
						<tr>
							<th colspan='2'>Sort By</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2'>
								<select name='search' onChange='document.form1.submit();'>
									<option $sal1 value='name'>Name</option>
									<option $sal2 value='cheque'>Cheque Number</option>
									<option $sal3 value='project'>Project</option>
								</select>
							</td>
						</tr>
						".TBL_BR."
				";
	}


	$get_list = "SELECT * FROM supp_creditor_run_cheques WHERE handed_over = 'no' ORDER BY $search_string";
	$run_list = db_exec($get_list) or errDie("Unable to get cheque information.");
	if(pg_numrows($run_list) < 1){
		if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
			$listing .= "<tr>";
		}else {
			$listing .= "<tr bgcolor='".bgcolorg()."'>";
		}
		$listing .= "
							<td colspan='10'>No Printed Cheques Found.</td>
						</tr>
					";
	}else {

		db_connect ();

		$i = 1;
		$total = 0;
		while ($larr = pg_fetch_array($run_list)){

			if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
				$listing .= "<tr>";
			}else {
				$listing .= "<tr bgcolor='".bgcolorg()."'>";
			}

			db_connect ();

			$get_sub = "SELECT supname FROM suppliers WHERE supid = '$larr[supid]' LIMIT 1";
			$run_sub = db_exec($get_sub) or errDie("Unable to get supplier information.");
			if(pg_numrows($run_sub) < 1){
				$sub = "";
			}else {
				$sarr = pg_fetch_array($run_sub);
				$sub = $sarr['supname'];
			}




			$project = "";

##PTH/CUBIT
//			db_conn('contract');

			#get remark for this remittance
//			$get_rem = "SELECT * FROM contract_recs WHERE conid = '$larr[conid]' AND remit = '$larr[remit]' LIMIT 1";
//			$run_rem = db_exec($get_rem) or errDie("Unable to get remark information.");
//			if(pg_numrows($run_rem) < 1){
//				$remark = "";
//			}else {
//				$rarr = pg_fetch_array($run_rem);
//				$remark = $rarr['remarks'];
//			}

			$listing .= "
								<input type='hidden' name='conid[]' value='$larr[conid]'>
								<input type='hidden' name='remit[]' value='$larr[remit]'>
								<td>$i</td>
								<td>$project</td>
								<td>$sub</td>
								<td><textarea name='remark[$larr[id]]' cols='20' rows='2'>$larr[remarks]</textarea></td>
								<td>$larr[cheq_num]</td>
								<td>$larr[proc_date]</td>
								<td nowrap>".CUR." $larr[amount]</td>
						";
			if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
				$listing .= "</tr>";
			}else {
				$listing .= "
								<td>____________________</td>
								<td>____________________</td>
								<td><input type='checkbox' name='ids[]' value='$larr[id]'></td>
							</tr>
						";
			}
			$i++;
			$total = $total + $larr['amount'];
		}
		$listing .= "
						<tr>
							<td colspan='5'></td>
							<th>Total</th>
							<th nowrap>".CUR." ".sprint ($total)."</th>
						</tr>
					";
	}

	if(isset($HTTP_POST_VARS['search']) AND (strlen($HTTP_POST_VARS['search']) > 0)){
		$send_search = "<input type='hidden' name='search' value='$HTTP_POST_VARS[search]'>";
	}else {
		$send_search = "";
	}

	$display = "
					<h2>Cheque Handling Listing</h2>
					$err
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form1'>
						<input type='hidden' name='key' value='confirm'>
						$send_search
						$filter
						<tr>
							<th>#</th>
							<th>Project</th>
							<th>Sub Contractor</th>
							<th>Comments</th>
							<th>Cheque Num</th>
							<th>Process Date</th>
							<th>Amount</th>
				";
	if(isset($HTTP_POST_VARS["print"]) OR isset($HTTP_POST_VARS["management"])){
		$display .= "
						</tr>
						$listing
						".TBL_BR."
						$buttons
					</form>
					</table>
					";
	}else {
		$display .= "
							<th>Signed Date</th>
							<th>Signature</th>
							<th>Handed Over</th>
						</tr>
						$listing
						".TBL_BR."
						$buttons
					</form>
					</table>
				";
	}
	return $display;

}





function confirm_list ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($ids) OR !is_array($ids) OR count($ids) < 1){
		return get_list($HTTP_POST_VARS);
	}

//print "<pre>";
//var_dump ($HTTP_POST_VARS);
//print "</pre>";

	foreach ($ids AS $id){

		db_conn ('contract');

		$get_info = "SELECT * from supp_creditor_run_cheques WHERE id = '$id' LIMIT 1";
		$run_info = db_exec($get_info) or errDie("Unable to get sub contractor information.");
		if(pg_numrows($run_info) < 1){
			return "Could not get cheque information.";
		}
		$suparr = pg_fetch_array ($run_info);

		$bankid = $suparr['bankid'];
		$supid = $suparr['supid'];
		$date = $suparr['proc_date'];
		$cheqnum = $suparr['cheq_num'];

		//$date = "$date_year-$date_month-$date_day";
		$amt = $suparr['amount'];

		$upd_sql = "UPDATE supp_creditor_run_cheques SET handed_over = 'yes' WHERE id = '$id'";
		$run_upd = db_exec($upd_sql) or errDie("Unable to update cheque run information.");

		#now we need to process these cheques
		# get hook account number
		core_connect();
		$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."' AND accid!=0";
		$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);

        # check if link exists
        if(pg_numrows($rslt) <1){
			$Sl="SELECT * FROM accounts WHERE accname='Cash on Hand'";
			$Rg=db_exec($Sl);
			if(pg_num_rows($Rg)<1) {
				if($bankid==0) {
					return "There is no 'Cash on Hand' account, there was one, but its not there now, you must have deleted it, if you want to use cash functionality please create a 'Cash on Hand' account.";
				} else {
					return "Invalid bank acc.";
				}
			}
			$add=pg_fetch_array($Rg);
			$bank['accnum']=$add['accid'];
		} else {
			$bank=pg_fetch_array($rslt);
		}

		db_connect();
		# Supplier name
		$sql = "SELECT supid,supno,supname,deptid FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$supRslt = db_exec($sql);
		$sup = pg_fetch_array($supRslt);

		db_conn("exten");
		# get debtors control account
		$sql = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec ($sql);
		$dept = pg_fetch_array($deptRslt);

		# date format
		$sdate = $date;
		$cheqnum = 0 + $cheqnum;
		$pay = "";
		$accdate=$sdate;

		# Paid invoices
		$invidsers = "";
		$rinvids = "";
		$amounts = "";
		$invprds = "";
		$out = "";
		$reference = "";

		db_conn("cubit");

		pglib_transaction("BEGIN");

		$all = 0;

		if($all==0)
		{
			$ids2 = "";
			$purids = "";
			$pamounts = "";
			$pdates = "";

			if (isset($invids)) {
				foreach($invids as $key => $value)
				{
					#debt invoice info
					$sql = "SELECT id,pdate FROM suppurch WHERE purid ='$invids[$key]' AND div = '".USER_DIV."' ORDER BY balance LIMIT 1";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$pur = pg_fetch_array($invRslt);
	
					# reduce the money that has been paid
					$sql = "UPDATE suppurch SET balance = (balance - '$paidamt[$key]'::numeric(13,2)) WHERE purid = '$invids[$key]' AND div = '".USER_DIV."' AND id='$pur[id]'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	
					$ids2 .= "|$pur[id]";
					$purids .= "|$invids[$key]";
					$pamounts .= "|$paidamt[$key]";
					$pdates .= "|$pur[pdate]";
				}
			}

			$samount = ($amt - ($amt * 2));

			if ($out>0) {
				recordDT($out, $sup['supid'],$sdate);
			}

			$Sl = "INSERT INTO sup_stmnt(supid, amount, edate, descript,ref,cacc, div) VALUES('$sup[supid]','$samount','$sdate', 'Payment','$cheqnum','$bank[accnum]', '".USER_DIV."')";
			$Rs= db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

			db_connect();

			# Update the supplier (make balance less)
			$sql = "UPDATE suppliers SET balance = (balance - '$amt'::numeric(13,2)) WHERE supid = '$sup[supid]'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			suppledger($sup['supid'], $bank['accnum'], $sdate, $cheqnum, "Payment for purchases", $amt, "d");

			db_connect();
			# Record the payment record
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, supid, ids, purids, pamounts, pdates, reference, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$sup[supno] - $sup[supname]', 'Supplier Payment to $sup[supname]', '$cheqnum', '$amt', 'no', '$dept[credacc]', '$sup[supid]', '$ids2', '$purids', '$pamounts', '$pdates', '$reference', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$refnum = getrefnum($accdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				return "Invalid bank acc.";
			}
			$link=pg_fetch_array($Rx);

			$link['accnum'] = $bank['accnum'];

			writetrans($dept['credacc'], $link['accnum'], $accdate, $refnum, $amt, "Supplier Payment to $sup[supname]");

			db_conn('cubit');
		}

		$Sl="DELETE FROM suppurch WHERE balance=0::numeric(13,2)";
		$Rx=db_exec($Sl);

		#add the supplier purchase entry ?
		$purch_sql = "
						INSERT INTO suppurch 
							(supid,purid,intpurid,pdate,div,npurid,balance,fcid,fbalance) 
						VALUES 
							('$sup[supid]','0','0','$date','".USER_DIV."','0','-$amt','0','0.00')";
		$run_purch = db_exec($purch_sql) or errDie ("Unable to update supplier purchase information.");

		db_conn('contract');

		$upd_sql = "UPDATE supp_creditor_run_cheques SET remarks = '$remark[$id]' WHERE id = '$id'";
		$run_upd = db_exec($upd_sql) or errDie("Unable to update contract remittance.");

		#also update the run listing
		$upd2_sql = "UPDATE credit_runs SET remarks = '$remark[$id]' WHERE entry_id = '$id'";
		$run_upd2 = db_exec($upd2_sql) or errDie ("Unable to update creditor run information.");

		pglib_transaction("COMMIT");

	}

##PTH/CUBIT
//	db_conn ("contract");
//	#process the remarks
//	foreach ($conid AS $each => $own){
//		$upd_sql = "UPDATE contract_recs SET remarks = '$remark[$each]' WHERE conid = '$own' AND remit = '$remit[$each]'";
//		$run_upd = db_exec($upd_sql) or errDie("Unable to update contract remittance.");
//	}

	return get_list ($HTTP_POST_VARS,"<li class='err'>Cheques Have been recorded.</li>");

}



function sage($supid, $days){
	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current outstanding
	db_conn("cubit");
	$sql = "SELECT sum(balance) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] += 0);
}


function recordDT($amount, $supid,$edate,$age="0")
{
	db_connect();

	$py = array();
	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid > 0 AND balance > 0 OR supid = '$supid' AND intpurid > 0 AND balance > 0 ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					if($dat['purid'] > 0){
						$py[] = "$dat[id]|$dat[purid]|$amount|$dat[pdate]";
					}else{
						$py[] = "$dat[id]|$dat[intpurid]|$amount|$dat[pdate]";
					}
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
						if($dat['purid'] > 0){
							$py[] = "$dat[id]|$dat[purid]|$dat[balance]|$dat[pdate]";
						}else{
							$py[] = "$dat[id]|$dat[intpurid]|$dat[balance]|$dat[pdate]";
						}
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");

			if($age != "0"){
				switch ($age){
					case "1":
						$days = 30;
						break;
					case "2":
						$days = 60;
						break;
					case "3":
						$days = 90;
						break;
					case "4":
						$days = 120;
						break;
					default:
						$days = 30;
				}
				$edate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
				$extra1 = ",actual_date";
				$extra2 = ",'$date'";
			}else {
				$extra1 = "";
				$extra2 = "";
			}

			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div $extra1) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."' $extra2)";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");

			if($age != "0"){
				switch ($age){
					case "1":
						$days = 30;
						break;
					case "2":
						$days = 60;
						break;
					case "3":
						$days = 90;
						break;
					case "4":
						$days = 120;
						break;
					default:
						$days = 30;
				}
				$edate = date("Y-m-d",mktime (0,0,0,date("m"),date("d")-$days,date("Y")));
				$extra1 = ",actual_date";
				$extra2 = ",'$date'";
			}else {
				$extra1 = "";
				$extra2 = "";
			}

		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div $extra1) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."' $extra2)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

	return $py;
}



?>
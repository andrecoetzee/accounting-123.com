<?

require ("../settings.php");
require ("../core-settings.php");
require("parsexml.php");

if(isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = get_do_trans ($HTTP_POST_VARS);
			break;
		case "replay":
			$OUTPUT = do_trans ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_file ($HTTP_POST_VARS);
	}
}else {
	$OUTPUT = get_file();
}

$OUTPUT .= "<br>"
	.mkQuickLinks(
		ql("record-trans.php", "Add Replay Transaction"),
		ql("export-xml.php", "Export Replay Transactions To File"),
		ql("replay-file-trans.php", "Replay Transaction File")
	);

require ("../template.php");



function get_file ($err="")
{

	$display = "
		<h2>Select File To Replay</h2>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST' enctype='multipart/form-data' name='form1'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>File Location</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='file' name='filename'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Replay'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function get_do_trans ($HTTP_POST_VARS)
{

	global $HTTP_POST_FILES,$complete;

	#verify the file here ...
	if (preg_match ("/(application\/xml|text\/xml)/", $HTTP_POST_FILES["filename"]["type"], $extension)) {
		#no probs
	}else {
		return get_file ("<li class='err'>Please Ensure The File You Are Importing Is Of Type XML. Invalid File Type Selected.</li>");
	}

	parseXML($HTTP_POST_FILES['filename']['tmp_name']);

	#set the missing vars here ...
	$listing = "";
	$show_debtor_list = "";
	$debtor_list = "";
	$show_creditor_list = "";
	$creditor_list = "";

	//make a copy of the file for records ...
	move_uploaded_file($HTTP_POST_FILES["filename"]["tmp_name"],$HTTP_POST_FILES["filename"]["tmp_name"]."1");

	if(isset($complete["DEBTOR"]) AND is_array($complete["DEBTOR"]))
		foreach ($complete["DEBTOR"] as $jobjs) {

			$parms = $jobjs->cols;
	
	//			if ($parms["debitacc"] != "0") {
	//				$debitacc = clsIncludes::$accounts[$parms["debitacc"]];
	//			}
	
	//			if ($parms["creditacc"] != "0") {
	//				$creditacc = clsIncludes::$accounts[$parms["creditacc"]];
	//			}

			$debtor = $jobjs->cols;
	
			db_connect ();
			#check if this debtor is in our books .. else add it.
			$get_debt = "SELECT * FROM customers WHERE accno = '$debtor[accno]' LIMIT 1";
			$run_debt = db_exec($get_debt) or errDie("Unable to get customer check information");
			if(pg_numrows($run_debt) < 1){
				#debtor is not available on this pc .. request to add it
				$debtor_list .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$debtor[accno]</td>
						<td>$debtor[surname]</td>
						<td>$debtor[bustel]</td>
						<td>$debtor[vatnum]</td>
						<td><input type='checkbox' name='debtadd[$debtor[accno]]' checked='yes' value='yes'></td>
					</tr>";
			}
		}

	if(isset($complete["CREDITOR"]) AND is_array($complete["CREDITOR"]))
		foreach ($complete["CREDITOR"] as $jobjs) {

			$creditor = $jobjs->cols;

			#check if this debtor is in our books .. else add it.
			db_connect ();
			$get_cred = "SELECT * FROM suppliers WHERE supno = '$creditor[supno]' LIMIT 1";
			$run_cred = db_exec($get_cred) or errDie("Unable to get customer check information");
			if(pg_numrows($run_cred) < 1){
				$creditor_list .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$creditor[supno]</td>
						<td>$creditor[supname]</td>
						<td>$creditor[tel]</td>
						<td>$creditor[vatnum]</td>
						<td><input type='checkbox' name='suppadd[$creditor[supno]]' checked='yes' value='yes'></td>
					</tr>";
			}
		}
	
	if(isset($complete["STOCK"]) AND is_array($complete["STOCK"]))
		foreach ($complete["STOCK"] as $jobjs) {

			$stock = $complete["STOCK"][$parms["iid"]]->cols;

		}

	if(isset($complete["JOURNAL"]) AND is_array($complete["JOURNAL"]))
		foreach ($complete["JOURNAL"] as $jobjs) {
			$parms = $jobjs->cols;
			$doid = $jobjs->id;
				$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$parms[debitacc]</td>
						<td>$parms[creditacc]</td>
						<td>$parms[date]</td>
						<td>$parms[amount]</td>
						<td>$parms[details]</td>
						<td><input type='checkbox' checked='yes' name='replay[$doid]' value='yes'></td>
					</tr>";
	
		}

	#decide what to show ...
	if(strlen($debtor_list) > 0)
		$show_debtor_list = "
			<tr>
				<td class='err' colspan='6'>Some Transactions Require Debtor(s) Which Are Not Present On This System.</td>
			</tr>
			<tr>
				<td class='err' colspan='6'>If You Would Like All Transactions To Replay, Please Select To Add Them.</td>
			</tr>
			<tr>
				<th>Account Number</th>
				<th>Customer Name</th>
				<th>Telephone Number</th>
				<th>VAT Number</th>
				<th>Add Customer</th>
			</tr>";

	if(strlen($creditor_list) > 0)
		$show_creditor_list = "
			<tr>
				<td class='err' colspan='6'>Some Transactions Require Creditor(s) Which Are Not Present On This System.</td>
			</tr>
			<tr>
				<td class='err' colspan='6'>If You Would Like All Transactions To Replay, Please Select To Add Them.</td>
			</tr>
			<tr>
				<th>Supplier Number</th>
				<th>Supplier Name</th>
				<th>Telephone Number</th>
				<th>VAT Number</th>
				<th>Add Supplier</th>
			</tr>";

	$filename_path = $HTTP_POST_FILES["filename"]["tmp_name"];
	$filename_type = $HTTP_POST_FILES["filename"]["type"];


	$display = "
		<h2>Select Entries To Replay</h2>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='replay'>
			<input type='hidden' name='filename_path' value='$filename_path'>
			<input type='hidden' name='filename_type' value='$filename_type'>
		<table ".TMPL_tblDflts.">
			$show_debtor_list
			$debtor_list
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			$show_creditor_list
			$creditor_list
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Debit Account</th>
				<th>Credit Account</th>
				<th>Transaction Date</th>
				<th>Amount</th>
				<th>Details</th>
				<th>Replay</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Replay Selected'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function do_trans ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	global $complete;

	#use the perm file we saved in the previous function ...
	$filename_path = $filename_path."1";

	#now parse it into $complete
	parseXML($filename_path);


	db_connect ();

//print "<pre>";
//var_dump($complete);
//print "</pre>";

pglib_transaction("BEGIN") or errDie("Unable to start transaction.");

	if(isset($complete["DEBTOR"]) AND is_array($complete["DEBTOR"]))
		foreach ($complete["DEBTOR"] as $jobjs) {
			$parms = $jobjs->cols;
			$debtor = $complete["DEBTOR"][$parms["iid"]]->cols;
			if(!isset($debtadd[$parms["accno"]]) OR (strlen($debtadd[$parms["accno"]]) < 1)){
				continue;
			}
			#this debtor is checked for adding ... so add it
			$ins_sql = "
				INSERT INTO customers (
					accno,surname,title,init,category,class,paddr1,addr1,contname,bustel,tel,cellno,fax,email,saleterm,traddisc,setdisc,pricelist,chrgint,overdue,chrgvat,credterm,odate,credlimit,blocked,deptid,vatnum,div,url,ddiv,intrate,balance,day30,day60,day90,day120,classname,catname,deptname,fbalance,fcid,location,currency,lead_source,comments,del_addr1,sales_rep,bankname,branname,brancode,bankaccno,bankaccname,team_id,registration,bankacctype
				) VALUES (
					'$parms[accno]','$parms[surname]','$parms[title]','$parms[init]','$parms[category]','$parms[class]','$parms[paddr1]','$parms[addr1]','$parms[contname]','$parms[bustel]','$parms[tel]','$parms[cellno]','$parms[fax]','$parms[email]','$parms[saleterm]','$parms[traddisc]','$parms[setdisc]','$parms[pricelist]','$parms[chrgint]','$parms[overdue]','$parms[chrgvat]','$parms[credterm]','$parms[odate]','$parms[credlimit]','$parms[blocked]','$parms[deptid]','$parms[vatnum]','$parms[div]','$parms[url]','$parms[ddiv]','$parms[intrate]','$parms[balance]','$parms[day30]','$parms[day60]','$parms[day90]','$parms[day120]','$parms[classname]','$parms[catname]','$parms[deptname]','$parms[fbalance]','$parms[fcid]','$parms[location]','$parms[currency]','$parms[lead_source]','$parms[comments]','$parms[del_addr1]','$parms[sales_rep]','$parms[bankname]','$parms[branname]','$parms[brancode]','$parms[bankaccno]','$parms[bankaccname]','$parms[team_id]','$parms[registration]','$parms[bankacctype]'
				)";
			$run_ins = db_exec($ins_sql) or errDie("Unable to add debtor information.");
		}

	if (isset($complete["CREDITOR"]) AND is_array($complete["CREDITOR"]))
		foreach ($complete["CREDITOR"] as $jobjs) {
			$parms = $jobjs->cols;
			$creditor = $complete["CREDITOR"][$parms["iid"]]->cols;
			if(!isset($suppadd[$parms["supno"]]) OR (strlen($suppadd[$parms["supno"]]) < 1)){
				continue;
			}
			#this creditor is checked for adding ... so add it
			$ins_sql = "
				INSERT INTO suppliers (
					supno,supname,supaddr,contname,tel,fax,email,bankname,branname,brancode,bankaccno,deptid,vatnum,div,url,ddiv,balance,listid,fbalance,fcid,location,currency,lead_source,comments,branch,groupid,reference,bee_status,team_id,registration,bankaccname,bankacctype
				) VALUES (
					'$parms[supno]','$parms[supname]','$parms[supaddr]','$parms[contname]','$parms[tel]','$parms[fax]','$parms[email]','$parms[bankname]','$parms[branname]','$parms[brancode]','$parms[bankaccno]','$parms[deptid]','$parms[vatnum]','$parms[div]','$parms[url]','$parms[ddiv]','$parms[balance]','$parms[listid]','$parms[fbalance]','$parms[fcid]','$parms[location]','$parms[currency]','$parms[lead_source]','$parms[comments]','$parms[branch]','$parms[groupid]','$parms[reference]','$parms[bee_status]','$parms[team_id]','$parms[registration]','$parms[bankaccname]','$parms[bankacctype]'
				)";
			$run_ins = db_exec($ins_sql) or errDie("Unable to add supplier information.");
		}


	$sdate = date("Y-m-d");

	if(isset($complete["JOURNAL"]) AND is_array($complete["JOURNAL"]))
		foreach ($complete["JOURNAL"] as $jobjs) {
			$parms = $jobjs->cols;
			$doid = $jobjs->id;

			#check if we should run this transaction
			if(!isset($replay[$doid]) OR (strlen($replay[$doid]) < 1))
				continue;

			if(!isset($parms["debitacc"]))
				$parms["debitacc"] = "0";
			db_connect();
			switch ($jobjs->type) {
				case "DEBTOR":
					$debtor = $complete["DEBTOR"][$parms["iid"]]->cols;
					if (!isset ($parms['creditacc']))
						$parms['creditacc'] = "0";

					if(($parms['debitacc'] == '0') AND ($parms['creditacc'] == '0')){

						#its not 1 of the custom saves ... so do generic ...
	
						# record the payment on the statement
						$sql = "
							INSERT INTO stmnt (
								cusnum, invid, amount, date, type, st, div, allocation_date
							) VALUES (
								'$parms[iid]', '0', '$parms[amount]', '$parms[date]', '$parms[details]', 'n', '".USER_DIV."', '$parms[date]'
							)";
						$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
	
						$sql = "INSERT INTO open_stmnt(cusnum, invid, amount, balance, date, type, st, div) VALUES('$parms[iid]', '0', '$parms[amount]', '$parms[amount]', '$parms[date]', '$parms[details]', 'n', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
	
						# update the customer (make balance more)
						$sql = "UPDATE customers SET balance = (balance + '$parms[amount]') WHERE cusnum = '$parms[iid]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);
					}else {

						if($parms['debitacc'] == '0'){
							if($parms['creditacc'] == '1'){
								recordCT($parms['amount'], $parms['iid'],$parms['date']);
							}else {
								custledger($parms['iid'],$parms['creditacc'],$parms['date'],$parms['refno'],$parms['details'],$parms['amount'],'c');
							}
						}elseif ($parms['creditacc'] == '0') {
							if($parms['debitacc'] == '1'){
								recordDT($parms['amount'], $parms['iid'],$parms['date']);
							}else {
								custledger($parms['iid'],$parms['creditacc'],$parms['date'],$parms['refno'],$parms['details'],$parms['amount'],'d');
							}
						}
					}

					break;
				case "CREDITOR":
					$creditor = $complete["CREDITOR"][$parms["iid"]]->cols;

					if($parms['debitacc'] == '0'){
						if($parms['creditacc'] == '1'){
							recordCT(-$parms['amount'], $parms['iid'],$parms['date']);
						}else {
							suppledger($parms['iid'], $parms['creditacc'], $parms['date'], $parms['refno'], $parms['details'], $parms['amount'], 'c');
						}
					}elseif ($parms['creditacc'] == '0') {
						if($parms['debitacc'] == '1'){
							recordDT($parms['amount'], $parms['iid'],$parms['date']);
						}else {
							suppledger($parms['iid'], $parms['debitacc'], $parms['date'], $parms['refno'], $parms['details'], $parms['amount'], 'd');
						}
					}elseif (($parms['debitacc'] == "9999") OR ($parms['creditacc'] == "9999")){

						# record the payment on the statement
						$sql = "INSERT INTO sup_stmnt(supid, edate, ref, cacc, descript, amount, div) VALUES('$parms[iid]', '$parms[date]', '0', '$parms[refno]', '$parms[details]', '$parms[amount]', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

						# update the supplier (make balance more)
						$sql = "UPDATE suppliers SET balance = (balance + '$parms[amount]') WHERE supid = '$parms[iid]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

					}else {

						#do nothing ?

						#its not 1 of the custom saves ... so do generic ...

//						db_connect();
//						$sql = "INSERT INTO sup_stmnt(supid, edate, ref, cacc, descript, amount, div) VALUES('$parms[iid]', '$parms[date]', '0', '$parms[vat]', '$parms[details]', '$parms[amount]', '".USER_DIV."')";
//						$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

//						# update the supplier (make balance more)
//						$sql = "UPDATE suppliers SET balance = (balance + '$parms[amount]') WHERE supid = '$parms[iid]' AND div = '".USER_DIV."'";
//						$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);
	
					}
					break;
				case "STOCK":
					$stock = $complete["STOCK"][$parms["iid"]]->cols;
	
					if(($parms['debitacc'] == "0") AND ($parms['creditacc'] == "0")){
						$sql = "UPDATE stock SET csamt = (csamt - $parms[amount]), units = (units - '$parms[refno]') WHERE stkid = '$parms[iid]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
					}elseif (($parms['debitacc'] == "1") AND ($parms['creditacc'] == "1")){
						$sql = "UPDATE stock
								SET units = (units + '$parms[refno]'),
									lcsprice = '$parms[vat]',
									csamt = (csamt + $parms[amount]),
									csprice = (
										SELECT
										CASE WHEN (units != -$parms[refno]) THEN (csamt+$parms[amount])/(units+$parms[refno])
											ELSE 0
										END
										FROM cubit.stock
										WHERE stkid='$parms[iid]' AND div='".USER_DIV."'
									)
								WHERE stkid = '$parms[iid]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
					}else {
						if($parms['debitacc'] == "1" AND $parms['creditacc'] == "0"){
	
							#first get the stock information
							$sql = "SELECT * FROM stock WHERE stkid = '$parms[iid]' AND div = '".USER_DIV."'";
							$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
							if(pg_numrows($stkRslt) < 1){
								return "<li> Invalid Stock ID.</li>";
							}else{
								$stk = pg_fetch_array($stkRslt);
							}
	
							stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $parms['date'], $parms['refno'], $parms['amount'], $parms['details']);
	
							$sql = "INSERT INTO stockrec (edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
												VALUES ('$parms[date]', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'inc', '$parms[refno]', '$parms[amount]', '$parms[vat]', '$parms[details]', '".USER_DIV."')";
							$recRslt = db_exec($sql);
	
						}elseif ($parms['debitacc'] == "0" AND $parms['creditacc'] == "1"){
		
							$sql = "SELECT * FROM stock WHERE stkid = '$parms[iid]' AND div = '".USER_DIV."'";
							$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
							if(pg_numrows($stkRslt) < 1){
								return "<li> Invalid Stock ID.</li>";
							}else{
								$stk = pg_fetch_array($stkRslt);
							}
	
							stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $parms['date'], $parms['refno'], $parms['amount'], $parms['details']);
							$parms[vat] += 0;
							db_connect ();
							$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
												VALUES('$parms[date]', '$stk[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'dec', '-$parms[refno]', '$parms[amount]', '$parms[vat]', '$parms[details]', '".USER_DIV."')";
							$recRslt = db_exec($sql);
	
							# Units
							if($stk['units'] <> 0){
								$sql = "UPDATE stock SET csprice = (csamt/units) WHERE stkid = '$parms[iid]' AND div = '".USER_DIV."'";
								$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
							}else{
								$sql = "UPDATE stock SET csprice = '$parms[vat]' WHERE stkid = '$parms[iid]' AND div = '".USER_DIV."'";
								$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
							}
						}else {
							#nothing to do ...
						}
					}
					break;
				case "JOURNAL":
					#process the writetrans
					if($parms['debitacc'] == "0" AND $parms['creditacc'] == "0"){
print "WROTE JOURNAL: VAT<br>";
						#we are dealing with vatr .. proceed
						#get the compressed vars
						$arrs = explode ("|",$parms['details']);
						vatr($arrs[1],$parms['date'],$arrs[2],$arrs[3],$parms['refno'],$arrs[0],$parms['amount'],$parms['vat']);
					}else {
print "WROTE JOURNAL: TRANSACTION<br>";
						writetrans ($parms['debitacc'],$parms['creditacc'],$parms['date'],$parms['refno'],$parms['amount'],$parms['details']);
					}
					break;
			}

		}

pglib_transaction("COMMIT") or errDie("Unable to commit transactions.");


	$display = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Transactions Completed</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>All Selected Replay Transactions Completed.</td>
						</tr>
					</table>
				";
	return $display;

}










# records for CT
function recordCT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			# $amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance,div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}










# records for DT
function recordDT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance < 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}


?>

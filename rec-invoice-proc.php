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
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "process":
			if (isset($HTTP_POST_VARS["set_date"]) or isset ($HTTP_POST_VARS["set_month"])){
				$OUTPUT = details($HTTP_POST_VARS);
			}else {
				$OUTPUT = process($HTTP_POST_VARS);
			}
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "update_prices":
			$OUTPUT = update_prices ($HTTP_POST_VARS);
			break;
		default:
			# decide what to do
			if (isset($HTTP_GET_VARS["invids"])) {
				$OUTPUT = details($HTTP_GET_VARS);
			} else {
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
			}
		}
} else {
	# decide what to do
	if (isset($HTTP_GET_VARS["invids"])) {
		if (isset($HTTP_GET_VARS["edit"]))
			$OUTPUT = edit_items ($HTTP_GET_VARS);
		else 
			$OUTPUT = details($HTTP_GET_VARS);
	} else {
		$OUTPUT = "<li class='err'>Please select at least one invoice</li><br><input type='button' onClick=\"document.location='rec-invoice-view.php';\" value='&laquo Correction'>";
	}
}

# get templete
require("template.php");




# details
function details($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	if (!isset($all_day))
		$all_day = "";
	if (!isset($all_month))
		$all_month = "";
	if (!isset($all_year))
		$all_year = "";
	if (!isset($just_month))
		$just_month = "";

	/* --- Start Display --- */
	$printInv = "
		<h3>Confirm Invoice Process</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='process'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td class='err'>Please Note : This process might take long depending on the number of invoices. It is best to run it overnight.</td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			".TBL_BR."
			<tr>
				<th>Set All Invoices To This Date</th>
				<td width='10'></td>
				<th>Change All To This Month</th>
			</tr>
			<tr>
				<td bgcolor='".bgcolorg()."' align='center'>
					<input type='text' size='2' name='all_day' value='$all_day'> 
					<input type='text' size='2' name='all_month' value='$all_month'> 
					<input type='text' size='4' name='all_year' value='$all_year'> 
					<input type='submit' name='set_date' value='Set All Dates'>
				</td>
				<td></td>
				<td bgcolor='".bgcolorg()."' align='center'>
					<input type='text' size='2' name='just_month' value='$just_month'> 
					<input type='submit' name='set_month' value='Change'>
				</td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Invoice No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
			</tr>";

	$i = 0;
	$inv_tot = 0;
	foreach($invids as $key => $invid){
		# Get recuring invoice info
		db_connect();
		$sql = "SELECT * FROM rec_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		if (isset($inv['cusnum']) AND $inv['cusnum'] != "0"){
			#customer set ... check if he's blocked
			$cust_sql = "SELECT cusnum FROM customers WHERE cusnum = '$inv[cusnum]' AND blocked != 'yes'";
			$run_cust = db_exec ($cust_sql) or errDie ("Unable to get customer information.");
			if (pg_numrows($run_cust) < 1) {
				#blocked? customer found !!
				return "<li class='err'>Invalid Customer/Customer is blocked.</li><br><input type='button' onClick=\"document.location='rec-invoice-view.php';\" value='&laquo Correction'>";
			}
		}

		$inv['total'] = sprint($inv['total']);
		$inv['balance'] = sprint($inv['balance']);

		# Format date
		if ((strlen($all_day) > 0) AND (strlen($all_month) > 0) AND (strlen($all_year) > 0)){
			$o_day = $all_day;
			$o_month = $all_month;
			$o_year = $all_year;
		}else {
			list($o_year, $o_month, $o_day) = explode("-", $inv['odate']);
		}

		if (strlen($just_month) > 0)
			$o_month = $just_month;

		$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='invids[]' value='$inv[invid]'>$inv[deptname]</td>
				<td>$inv[salespn]</td>
				<td>RI $inv[invid]</td>
				<td valign='center'>
					<table cellspacing='0'>
						<tr>
							<td>".mkDateSelecta("o",array("$i"),$o_year,$o_month,$o_day)."</td>
						</tr>
					</table>
				</td>
				<td>$inv[cusname] $inv[surname]</td>
				<td align='right'>$inv[ordno]</td>
				<td align='right'>".CUR." ".sprint ($inv['total'])."</td>
			</tr>";
		$inv_tot += $inv['total'];
		$i++;
	}


	$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><b>Totals:</b></td>
				<td colspan='5' align='right'>Invoices : $i</td>
				<td align='right'>".CUR." ".sprint ($inv_tot)."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='6' align='right'><input type='submit' value='Process >>'></td>
			</tr>
		</form></table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-invoice-new.php'>New Recurring Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printInv;

}




# Create the company
function process ($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
		$odate[$key] = $o_year[$key]."-".$o_month[$key]."-".$o_day[$key];
		if(!checkdate($o_month[$key], $o_day[$key], $o_year[$key])){
			$v->isOk ($odate[$key], "num", 1, 1, "Invalid Invoice Date.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}


	$postvars = "";
	foreach($invids as $key => $invid){
		$postvars .= "
			<input type='hidden' name='invids[]' value='$invid'>
			<input type='hidden' size='2' name='o_day[]' maxlength='2' value='$o_day[$key]'>
			<input type='hidden' size='2' name='o_month[]' maxlength='2' value='$o_month[$key]'>
			<input type='hidden' size='2' name='o_year[]' maxlength='2' value='$o_year[$key]'>";
	}

	$OUTPUT = "
		<form action='rec-invoice-proc.php' method='POST' name='postvars'>
			<input type='hidden' name='key' value='write'>
			$postvars
		</form>
		<table width='100%' height='100%'>
			<tr>
				<td align='center' valign='middle'>
					<font size='2' color='white'>Please wait while the invoices are being processed. This may take several minutes.</font><br>
					<div id='wait_bar_parent' style='border: 1px solid black; width:100px'>
						<div id='wait_bar' style='font-size: 15pt'>...</div>
					</div>
				</td>
			</tr>
		</table>

		<script>
			wait_bar = getObjectById('wait_bar')
			function moveWaitBar() {
				if ( wait_bar.innerHTML == '...................')
					wait_bar.innerHTML = '.';
				else
					wait_bar.innerHTML = wait_bar.innerHTML + '.';

				setTimeout('moveWaitBar()', 50);
			}

			setTimeout('moveWaitBar()', 100);

			document.postvars.submit();
		</script>";
	return $OUTPUT;

}




# Details
function write($HTTP_POST_VARS)
{

	# Set mas execution time to 12 hours
	ini_set("max_execution_time", 43200);

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
		$odate[$key] = $o_year[$key]."-".$o_month[$key]."-".$o_day[$key];
		if(!checkdate($o_month[$key], $o_day[$key], $o_year[$key])){
			$v->isOk ($odate[$key], "num", 1, 1, "Invalid Invoice Date.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}


	$i = 0;
	foreach($invids as $key => $invid){

		# Get recuring invoice info
		db_connect();

		$sql = "SELECT * FROM rec_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		/* - Start Copying - */
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# Insert invoice to DB
			$sql = "
				INSERT INTO invoices (
					deptid, cusnum, deptname, cusacc, cusname, surname, 
					cusaddr, cusvatno, cordno, ordno, chrgvat, terms, 
					traddisc, salespn, odate, delchrg, subtot, vat, 
					discount, delivery, total, balance, comm, printed, 
					done, prd, serd, div, jobid
				) VALUES (
					'$inv[deptid]', '$inv[cusnum]', '$inv[deptname]', '$inv[cusacc]', '$inv[cusname]', '$inv[surname]', 
					'$inv[cusaddr]', '$inv[cusvatno]', '$inv[cordno]', '$inv[ordno]', '$inv[chrgvat]', '$inv[terms]', 
					'$inv[traddisc]', '$inv[salespn]', '$odate[$key]', '$inv[delchrg]', '$inv[subtot]', '$inv[vat]' , 
					'$inv[discount]', '$inv[delivery]', '$inv[total]', '$inv[total]', '$inv[comm]', 'n', 'y', '".PRD_DB."', 
					'n', '".USER_DIV."', '$invid'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

			# get next ordnum
			$invid = lastinvid();
	
			# get selected stock in this recuring invoice
			db_connect();
			$sql = "SELECT * FROM recinv_items  WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$serd = "y";
			while($stkd = pg_fetch_array($stkdRslt)){

				# Insert one by one per quantity
				if(ext_isSerial("stock", "stkid", $stkd['stkid'])){
					$stkd['amt'] = sprint($stkd['amt']/$stkd['qty']);
					$serd = "n";
					for($i = 0; $i < $stkd['qty']; $i++){
						# insert invoice items
						$sql = "
							INSERT INTO inv_items (
								invid, whid, stkid, qty, unitcost, amt, 
								disc, discp, div, vatcode, account, 
								description
							) VALUES (
								'$invid', '$stkd[whid]', '$stkd[stkid]', '1', '$stkd[unitcost]', '$stkd[amt]', 
								'$stkd[disc]', '$stkd[discp]', '".USER_DIV."', '$stkd[vatcode]', '$stkd[account]', 
								'$stkd[description]'
							)";
						$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
					}
				}else{
					# insert invoice items
					$sql = "
						INSERT INTO inv_items (
							invid, whid, stkid, qty, unitcost, amt, 
							disc, discp, div, vatcode, account, 
							description
						) VALUES (
							'$invid', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', 
							'$stkd[disc]', '$stkd[discp]', '".USER_DIV."', '$stkd[vatcode]', '$stkd[account]', 
							'$stkd[description]'
						)";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
	
				# update stock(alloc + qty)
				$sql = "UPDATE stock SET alloc = (alloc + '$stkd[qty]') WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			}
	
			# set to not serialised
			$sql = "UPDATE invoices SET serd = '$serd' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update quotes in Cubit.",SELF);
	
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		/* - End Copying - */
	}

	// Final Laytout
	$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Recurring Invoices Proccesed</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>New Invoices have been created from Recurring Invoices</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-view.php'>View Invoices</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


function edit_items ($HTTP_POST_VARS,$err="")
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$search_arr = array ();
	$send_invids = "";
	foreach ($invids AS $each){
		$search_arr[] = "invid='$each'";
		$send_invids .= "<input type='hidden' name='invids[]' value='$each'>\n";
	}

	$search_string = implode (" OR ",$search_arr);

	#get a list of all the items for these selected recurring invoices ....
	$get_invs = "SELECT distinct(stkid) FROM recinv_items WHERE $search_string";
	$run_invs = db_exec($get_invs) or errDie ("Unable to get invoice information.");
	if (pg_numrows($run_invs) < 1){
		#no entries found ?
	}else {
		while ($iarr = pg_fetch_array ($run_invs)){

			#get info for this stock item ...
			$get_stk = "SELECT stkdes FROM stock WHERE stkid = '$iarr[stkid]' LIMIT 1";
			$run_stk = db_exec($get_stk) or errDie ("Unable to get stock information.");
			if (pg_numrows($run_stk) < 1){
				$stock_desc = "";
				$stock_price = 0;
			}else {
				$arr = pg_fetch_array ($run_stk);
				$stock_desc = $arr['stkdes'];
				//price we get from the recurring invoice
				$r_sql = "SELECT unitcost FROM recinv_items WHERE stkid = '$iarr[stkid]' AND ($search_string) LIMIT 1";
				$run_r = db_exec($r_sql) or errDie ("Unable to get stock unit cost information.");
				if (pg_numrows($run_r) < 1){
					$unitcost = 0;
				}else {
					$unitcost = sprint (pg_fetch_result ($run_r,0,0));
				}

			}

			#compile a list of the items ...
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td width='50%'>$stock_desc <input type='hidden' size='10' name='stkid[]' value='$iarr[stkid]'></td>
					<td>".CUR." $unitcost</td>
					<td>".CUR." <input type='text' size='10' name='unitcost[]' value='$unitcost'></td>
				</tr>";
		}
	}




	$display = "
		<h4>Change Price On Selected Invoices</h4>
		<table ".TMPL_tblDflts." width='60%'>
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='update_prices'>
			$send_invids
			<tr>
				<th>Stock Item</th>
				<th>Current Amount</th>
				<th>New Amount</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Update'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function update_prices ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if (!isset($stkid) OR !is_array ($stkid))
		return edit_items($HTTP_POST_VARS,"<li class='err'>No Items To Update.</li><br>");

	$search_arr = array ();
	foreach ($invids AS $each){
		$search_arr[] = "invid='$each'";
	}
	
	$inv_search = implode (" OR ",$search_arr);

	db_connect ();

	#go through all stock items ... and update the items ...
	foreach ($stkid AS $key => $value){
		$upd_sql = "UPDATE recinv_items SET unitcost = '$unitcost[$key]', amt = qty * '$unitcost[$key]' WHERE stkid = '$value' AND ($inv_search)";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update recurring invoice item information.");
	}

	db_connect ();

	foreach ($invids AS $invid){

		#new invoice ... new totals ...
		$total = 0;
		$vat = 0;

		#update the totals of each ... based on its 'new' items ...
		$get_inv = "SELECT * FROM rec_invoices WHERE invid = '$invid' LIMIT 1";
		$run_inv = db_exec($get_inv) or errDie ("Unable to get recurring invoice information.");
		if (pg_numrows($run_inv) > 0){
			#found!
			$inv_arr = pg_fetch_array ($run_inv);

			#ok .. incl ... go through each item ... checking its vat code ...
			$get_items = "SELECT * FROM recinv_items WHERE invid = '$inv_arr[invid]'";
			$run_items = db_exec ($get_items) or errDie ("Unable to get received items information.");
			if (pg_numrows ($run_items) > 0){
				while ($item_arr = pg_fetch_array ($run_items)){
					#get vatcode for this item ...
					$get_vat = "SELECT vat_amount FROM vatcodes WHERE id = '$item_arr[vatcode]' LIMIT 1";
					$run_vat = db_exec($get_vat) or errDie ("Unable to get vat amount for invoice item.");
					if (pg_numrows($run_vat) > 0){
						#vat code found ... 
						$vat_perc = sprint (pg_fetch_result ($run_vat,0,0));
					}else {
						$vat_perc = 0;
					}

					#add amount to total ..
					if ($vat_perc == 0){
						$total += $item_arr['amt'];
					}else {
						if ($inv_arr['chrgvat'] == "exc"){
							$total += $item_arr['amt'];
							$vat += sprint(($item_arr['amt'] / 100) * $vat_perc);
						}else {
							$vat_amt = sprint (($item_arr['amt']) - ($item_arr['amt'] / (($vat_perc/100)+1)));
							$vat += $vat_amt;
							$total += ($item_arr['amt'] - $vat_amt);
						}
					}
				}
			}

		}

		$subtot = sprint ($total);
		$total = sprint ($total+$vat);
		$vat = sprint ($vat);

		#update this invoice with the new totals ...
		$upd_sql = "UPDATE rec_invoices SET subtot = '$subtot', vat = '$vat', total = '$total', balance = '$total' WHERE invid = '$invid'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update recurring invoices information.");

	}

	header ("Location: rec-invoice-view.php");

}


?>

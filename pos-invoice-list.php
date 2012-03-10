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

require ("settings.php");
require ("core-settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
	case "cancelsel":
		$OUTPUT = cancel();
		break;
	case "allsel":
		$OUTPUT = printInv();
		break;
	}
} elseif (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
        case "view":
			$OUTPUT = printInv();
			break;
		case "delete_confirm":
			$OUTPUT = delete_confirm();
			break;
		case "delete_write":
			$OUTPUT = delete_write();
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct();
}

require ("template.php");




# Default view
function slct()
{

	//layout
	$slct = "
		<h3>View Cash Point of Sale Invoices<h3>
		<table ".TMPL_tblDflts." width='460'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' nowrap='t'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-list.php'>View Unprocessed Point of Sale Invoice</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}



# show invoices
function printInv()
{

	extract($_REQUEST);

	if (isset($button)) {
		list($button) = array_keys($button);
	}

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}


	# Set up table to display in
	$printInv = "
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='view' />
			<input type='hidden' name='from_year' value='$from_year' />
			<input type='hidden' name='from_month' value='$from_month' />
			<input type='hidden' name='from_day' value='$from_day' />
			<input type='hidden' name='to_year' value='$to_year' />
			<input type='hidden' name='to_month' value='$to_month' />
			<input type='hidden' name='to_day' value='$to_day' />
		<h3>View Cash Point of Sale invoices</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Inv No.</th>
				<th>Invoice Date</th>
				<th>Customer</th>
				<th>Grand Total</th>
				<th colspan='4'>Options</th>
				<th>&nbsp;</th>	
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1 = 0;

	$sql = "SELECT invid, total, odate, deptname, salespn, cusname, printed, balance,cusnum FROM pinvoices WHERE odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."' ORDER BY invid DESC";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>No Point of Sale Invoices found for the selected date range.</li>".slct();
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {

			$inv['total'] = sprint($inv['total']);
			$tot1 = $tot1 + $inv['total'];
			# format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			if($inv['cusnum'] != "0"){
				#overwrite the default cusname
				$get_cust = "SELECT surname FROM customers WHERE cusnum = '$inv[cusnum]' LIMIT 1";
				$run_cust = db_exec($get_cust) or errDie("Unable to get customer information.");
				if(pg_numrows($run_cust) == 1){
					$arr = pg_fetch_array($run_cust);
					$inv['cusname'] = $arr['surname'];
				}
			}

			$printInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>TP $inv[invid]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname]</td>
					<td align=right>".CUR." $inv[total]</td>
					<td><a href='pos-invoice-details.php?invid=$inv[invid]'>Details</a></td>";

			if($inv['printed'] == "n"){
				$printInv .= "
					<td><a href='pos-invoice-new.php?invid=$inv[invid]&cont=1'>Edit</a></td>
					<td><a href='?invid=$inv[invid]&key=delete_confirm'>Delete</a></td>
					<td><a target='_blank' href='pos-invoice-print.php?invid=$inv[invid]'>Process</a></td>";
			}else{
				$printInv .= "
					<td></td>
					<td>
						<a target='_blank' href='pos-invoice-reprint.php?invid=$inv[invid]'>Reprint</a>
					</td>";
			}
			if (isset($button) && $button == "allsel") {
				$checked = "checked='checked'";
			} else {
				$checked = "";
			}

			$printInv .= "
					<td><input type='checkbox' name='rem[$inv[invid]]' value='$inv[invid]' $checked /></td>
				</tr>";
			$i++;
		}
	}
	$tot1 = sprint($tot1);

		// Layout
		$printInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='5'>Total Invoices: $i</td>
					<td align='right'>".CUR." $tot1</td>
				</tr>
				<tr>
					<td colspan='15' align='right'>
						<input type='submit' name='button[cancelsel]' value='Cancel Selected' />
						<input type='submit' name='button[allsel]' value='Select All' />
					</td>
				</tr>
			</table>
			</form>
			<p>
			<table ".TMPL_tblDflts.">
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-list.php'>View Unprocessed Point of Sale Invoice</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	return $printInv;

}



function delete_confirm()
{

	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($invid, "num", 1, 9, "Invalid invoice id.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return printInv($confirm);
	}

	db_conn("cubit");

	$sql = "SELECT * FROM pinvoices WHERE invid='$invid'";
	$pinvRslt = db_exec($sql) or errDie("Unable to retrieve POS invoice information from Cubit.");
	$pinvData = pg_fetch_array($pinvRslt);

	$OUTPUT = "
		<h3>Delete Unprocessed Point of Sale Invoice</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='delete_write'>
			<input type='hidden' name='invid' value='$invid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Confirm</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td>$pinvData[deptname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Sales Person</td>
				<td>$pinvData[salespn]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Inv No.</td>
				<td>$pinvData[invid]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice Date</td>
				<td>$pinvData[odate]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer</td>
				<td>$pinvData[cusname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Grand Total</td>
				<td>".CUR."$pinvData[total]</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-list.php'>View Unprocessed Point of Sale Invoice</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $OUTPUT;

}




function delete_write()
{

	extract($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($invid, "num", 1, 9, "Invalid invoice id.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return printInv($errors);
	}

	pglib_transaction("BEGIN");

	db_conn("cubit");

	$sql = "DELETE FROM pinvoices WHERE invid='$invid'";
	$pinvRslt = db_exec($sql) or errDie("Unable to remove invoice from Cubit.");

	#get any allocated serial numbers and remove items ... AND re-allocate stock
	$get_sers = "SELECT ss,serno,stkid,qty FROM pinv_items WHERE invid = '$invid'";
	$run_sers = db_exec($get_sers) or errDie("Unable to get invoice items serial numbers");
	if (pg_numrows($run_sers) < 1) {
		#no items ?
	}else {
		while ($parr = pg_fetch_array($run_sers)) {
			if(strlen($parr['ss']) > 0){
				$me = $parr['ss'];
			}else {
				$me = $parr['serno'];
			}
			#determine which table to connect to and update it
			switch (substr($me,(strlen($me)-1),1)) {
				case "0":
					$tab = "ss0";
					break;
				case "1":
					$tab = "ss1";
					break;
				case "2":
					$tab = "ss2";
					break;
				case "3":
					$tab = "ss3";
					break;
				case "4":
					$tab = "ss4";
					break;
				case "5":
					$tab = "ss5";
					break;
				case "6":
					$tab = "ss6";
					break;
				case "7":
					$tab = "ss7";
					break;
				case "8":
					$tab = "ss8";
					break;
				case "9":
					$tab = "ss9";
					break;
				default:
					return order($HTTP_POST_VARS,"The code you selected is invalid");
			}

			$upd = "UPDATE $tab SET active = 'yes' WHERE code = '$parr[ss]' OR code = '$parr[serno]'";
			$run_upd = db_exec($upd) or errDie("Unable to update stock serial numbers");

			#look 4 this stock item
			$get_stock = "SELECT * FROM stock WHERE stkid = '$parr[stkid]' LIMIT 1";
			$run_stock = db_exec($get_stock) or errDie("Unable to get stock information.");
			if(pg_numrows($run_stock) < 1){
				#cant find stock item ???
			}else {
				$min_alloc = $parr['qty'] + 0;
				$starr = pg_fetch_array($run_stock);

				#all set ... re-allocate stock
				$update_sql = "UPDATE stock SET alloc = alloc - '$min_alloc' WHERE stkid = '$starr[stkid]'";
				$update_run = db_exec($update_sql) or errDie("Unable to update allocated stock information.");
			}
		}
	}

	#now remove the items
	$rem_items = "DELETE FROM pinv_items WHERE invid = '$invid'";
	$run_rem = db_exec($rem_items) or errDie("Unable to remove invoice items");

	if (pg_affected_rows($pinvRslt) > 0) {
		$OUTPUT = "
			<li>Invoice has been successfully removed.</li>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th></tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-list.php'>View Unprocessed Point of Sale Invoice</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	} else {
		$OUTPUT = "
			<li class='err'>Invoice was not found.</li>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-list.php'>View Unprocessed Point of Sale Invoice</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}

	pglib_transaction("COMMIT");

	return $OUTPUT;

}



function cancel()
{
	extract ($_REQUEST);

	pglib_transaction("BEGIN");
	
	if (isset($rem) && is_array($rem)) {
		foreach ($rem as $invid) {
			db_conn("cubit");
			$sql = "DELETE FROM pinvoices WHERE invid='$invid'";
			$pinvRslt = db_exec($sql) or errDie("Unable to remove invoice from Cubit.");

			#get any allocated serial numbers and remove items ... AND re-allocate stock
			$get_sers = "SELECT ss,serno,stkid,qty FROM pinv_items WHERE invid = '$invid'";
			$run_sers = db_exec($get_sers) or errDie("Unable to get invoice items serial numbers");
			if (pg_numrows($run_sers) < 1) {
				#no items ?
			}else {
				while ($parr = pg_fetch_array($run_sers)) {
					if(strlen($parr['ss']) > 0){
						$me = $parr['ss'];
					}else {
						$me = $parr['serno'];
					}
					#determine which table to connect to and update it
					switch (substr($me,(strlen($me)-1),1)) {
					case "0":
						$tab = "ss0";
						break;
					case "1":
						$tab = "ss1";
						break;
					case "2":
						$tab = "ss2";
						break;
					case "3":
						$tab = "ss3";
						break;
					case "4":
						$tab = "ss4";
						break;
					case "5":
						$tab = "ss5";
						break;
					case "6":
						$tab = "ss6";
						break;
					case "7":
						$tab = "ss7";
						break;
					case "8":
						$tab = "ss8";
						break;
					case "9":
						$tab = "ss9";
						break;
					default:
						return order($HTTP_POST_VARS,"The code you selected is invalid");
					}
					$upd = "UPDATE $tab SET active = 'yes' WHERE code = '$parr[ss]' OR code = '$parr[serno]'";
					$run_upd = db_exec($upd) or errDie("Unable to update stock serial numbers");

					#look 4 this stock item
					$get_stock = "SELECT * FROM stock WHERE stkid = '$parr[stkid]' LIMIT 1";
					$run_stock = db_exec($get_stock) or errDie("Unable to get stock information.");
					if(pg_numrows($run_stock) < 1){
						#cant find stock item ???
					}else {
						$min_alloc = $parr['qty'] + 0;
						$starr = pg_fetch_array($run_stock);

						#all set ... re-allocate stock
						$update_sql = "UPDATE stock SET alloc = alloc - '$min_alloc' WHERE stkid = '$starr[stkid]'";
						$update_run = db_exec($update_sql) or errDie("Unable to update allocated stock information.");
					}
				}
			}
			#now remove the items
			$rem_items = "DELETE FROM pinv_items WHERE invid = '$invid'";
			$run_rem = db_exec($rem_items) or errDie("Unable to remove invoice items");
		}	
	}
	return printInv();

}



?>
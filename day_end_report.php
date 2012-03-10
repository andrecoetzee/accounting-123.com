<?php

require ("settings.php");

$OUTPUT = display($HTTP_POST_VARS);

require ("template.php");

function display($HTTP_POST_VARS)
{

	extract ($_REQUEST);

	if (!isset($date_year) OR strlen($date_year) < 1){
		$date_year = date("Y");
		$date_month = date("m");
		$date_day = date ("d");
	}

	$date = "$date_year-$date_month-$date_day";

	$from_date = $date;
	$to_date = $date;
	$from_time = date("Y-m-d G:i:s",mktime (0, 0, 0, $date_month, $date_day, $date_year));
	$to_time = date("Y-m-d G:i:s",mktime (0, 0, 0, $date_month, $date_day, $date_year));


###################[ GET TOTALS ]####################

	// Sum up totals of hire
	$sql = "
	SELECT sum(total - vat) AS exc_vat, sum(total) AS inc_vat
	FROM cubit.nons_invoices
	WHERE 
		sdate BETWEEN '$from_date' AND 
		'$to_date' AND done='y' AND
		hire_invid > 0";
	$inv_hire_rslt = db_exec($sql)
		or errDie("Unable to retrieve hire invoices.");
	list($inv_hire_exc, $inv_hire_inc) = pg_fetch_array($inv_hire_rslt);

	// Sum up totals for stock
	$sql = "
	SELECT sum(total - vat) AS exc_vat, sum(total) AS inc_vat
	FROM cubit.invoices
	WHERE 
		odate BETWEEN '$from_date' AND 
		'$to_date' AND done='y' AND
		printed='y'";
	$inv_stock_rslt = db_exec($sql)
		or errDie("Unable to retrieve stock invoices.");
	list($inv_stock_exc, $inv_stock_inc) = pg_fetch_array($inv_stock_rslt);

	$sql = "
	SELECT sum(total - vat) AS exc_vat, sum(total) AS inc_vat
	FROM cubit.pinvoices
	WHERE 
		odate BETWEEN '$from_date' AND 
		'$to_date' AND 
		done='y' AND
		printed='y'";
	$pinv_stock_rslt = db_exec($sql)
		or errDie("Unable to retrieve stock invoices.");
	list($pinv_stock_exc, $pinv_stock_inc) = pg_fetch_array($pinv_stock_rslt);
	$inv_stock_exc += $pinv_stock_exc;
	$inv_stock_inc += $pinv_stock_inc;

	// Sum up totals for other
	$sql = "
	SELECT sum(total - vat) AS exc_vat, sum(total) AS inc_vat
	FROM cubit.nons_invoices
	WHERE 
		sdate BETWEEN '$from_date' AND 
		'$to_date' AND 
		done='y' AND
		hire_invid = 0";
	$inv_other_rslt = db_exec($sql) or errDie("Unable to retrieve other.");
	list($inv_other_exc, $inv_other_inc) = pg_fetch_array($inv_other_rslt);

	$inv_total_exc = $inv_hire_exc + $inv_stock_exc + $inv_other_exc;
	$inv_total_inc = $inv_hire_inc + $inv_stock_inc + $inv_other_inc;

	$OUTPUT = "

	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<td><h3>".COMP_NAME."</h3></td>
			<td align='right'><h3>".date("Y-m-d")."</h3></td>
		</tr>
		<tr>
			<td><h3>Daily Activity Report</h3></td>
			<td align='right'><h3>Prepared by: ".USER_NAME."</h3></td>
		</tr>
		".TBL_BR."
	</table>
	<form action='".SELF."' method='POST'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Select Date For Report</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><input type='submit' value='View'></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th width='80%'>&nbsp;</th>
			<th width='10%'>Exc Vat</th>
			<th width='10%'>Inc Vat</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Invoices - Hire</td>
			<td align='right'>".sprint($inv_hire_exc)."</td>
			<td align='right'>".sprint($inv_hire_inc)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Invoices - Stock</td>
			<td align='right'>".sprint($inv_stock_exc)."</td>
			<td align='right'>".sprint($inv_stock_inc)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Invoices - Other</td>
			<td align='right'>".sprint($inv_other_exc)."</td>
			<td align='right'>".sprint($inv_other_inc)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><strong>Total Invoices</strong></td>
			<td align='right'><b>".sprint($inv_total_exc)."</b></td>
			<td align='right'><b>".sprint($inv_total_inc)."</b></td>
		</tr>
	</table>";

	// Hires for the day
	$sql = "
	SELECT odate, invnum, cusname, total FROM hire.reprint_invoices
	WHERE odate BETWEEN '$from_date' AND '$to_date'";
	$hire_rslt = db_exec($sql) or errDie("Unable to retrieve hires.");

	$hire_out = "";
	$hire_total = 0;
	while ($hire_data = pg_fetch_array($hire_rslt)) {
		$hire_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$hire_data[invnum]</td>
			<td>$hire_data[cusname]</td>
			<td align='right'>".sprint($hire_data["total"])."</td>
		</tr>";

		$hire_total += $hire_data["total"];
	}

	if (empty($hire_out)) {
		$hire_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><li>No hires found</li></td>
		</tr>";
	}

	$OUTPUT .= "
	<h3>Hires for $date</h3>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Hire No</th>
			<th>Customer</th>
			<th width='10%'>Amount</th>
		</tr>
		$hire_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'><b>Total</b></td>
			<td align='right'><b>".sprint($hire_total)."</b></td>
	</table>";




	// Credit Notes for the day ----------------------------------------------
	$notes_total = 0;
	$union = array();
	for ($i = 1; $i <= 14; $i++) {
		$union[] = "
		SELECT odate, surname, invnum, notenum, total FROM \"$i\".inv_notes
		WHERE odate BETWEEN '$from_date' AND '$to_date'";
	}
	$sql = implode(" UNION ", $union);
	$notes_rslt = db_exec($sql) or errDie("Unable to retrieve credit notes.");

	$notes_out = "";
	while ($notes_data = pg_fetch_array($notes_rslt)) {
		$notes_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$notes_data[surname]</td>
			<td>$notes_data[invnum]</td>
			<td>$notes_data[notenum]</td>
			<td align='right'>$notes_data[total]</td>
		</tr>";
		$notes_total += $notes_data["total"];
	}

	$sql = "
	SELECT date, cusname, invnum, notenum, total FROM cubit.nons_inv_notes
	WHERE date BETWEEN '$from_date' AND '$to_date'";
	$notes_rslt = db_exec($sql) or errDie("Unable to retrieve credit notes.");

	while ($notes_data = pg_fetch_array($notes_rslt)) {
		$notes_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$notes_data[cusname]</td>
			<td>$notes_data[invnum]</td>
			<td>$notes_data[notenum]</td>
			<td align='right'>$notes_data[total]</td>
		</tr>";
		$notes_total += $notes_data["total"];
	}

	if (empty($notes_out)) {
		$notes_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'><li>No credit notes found.</li></td>
		</tr>";
	}

	$OUTPUT .= "
	<h3>Credit Notes for $date</h3>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Customer</th>
			<th>Invoice No</th>
			<th>Credit Note No</th>
			<th width='10%'>Amount</th>
		</tr>
		$notes_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><b>Total</b></td>
			<td align='right'><b>".sprint($notes_total)."</b></td>
		</tr>
	</table>";




	// Invoices --------------------------------------------------------------
	$inv_hire = 0;
	$inv_sales = 0;
	$inv_vat = 0;
	$inv_total = 0;
	$sql = "
	SELECT odate, surname, invnum, (total - vat) AS sales, vat, total
	FROM cubit.invoices
	WHERE odate BETWEEN '$from_date' AND '$to_date' AND done='y'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$inv_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$inv_data[surname]</td>
			<td>&nbsp;</td>
			<td>$inv_data[invnum]</td>
			<td align='right'>0.00</td>
			<td align='right'>$inv_data[sales]</td>
			<td align='right'>$inv_data[vat]</td>
			<td align='right'>$inv_data[total]</td>
		</tr>";
		$inv_hire += 0.00;
		$inv_sales += $inv_data["sales"];
		$inv_vat += $inv_data["vat"];
		$inv_total += $inv_data["total"];
	}

	$sql = "
	SELECT odate, surname, invnum, (total - vat) AS sales, vat, total
	FROM cubit.pinvoices
	WHERE odate BETWEEN '$from_date' AND '$to_date' AND done='y'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$inv_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$inv_data[surname]</td>
			<td>&nbsp;</td>
			<td>$inv_data[invnum]</td>
			<td align='right'>0.00</td>
			<td align='right'>$inv_data[sales]</td>
			<td align='right'>$inv_data[vat]</td>
			<td align='right'>$inv_data[total]</td>
		</tr>";
		$inv_hire += 0.00;
		$inv_sales += $inv_data["sales"];
		$inv_vat += $inv_data["vat"];
		$inv_total += $inv_data["total"];
	}

	$sql = "
	SELECT sdate, cusname, invnum, vat, total, hire_invid, hire_invnum, invid
	FROM cubit.nons_invoices
	WHERE sdate BETWEEN '$from_date' AND '$to_date' AND done='y'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$sql = "
		SELECT sum(amount) FROM hire.hire_stock_items
		WHERE invid='$inv_data[hire_invid]'";
		$sales_amt_rslt = db_exec($sql) or errDie("Unable to retrieve sales.");
		$sales_amt = pg_fetch_result($sales_amt_rslt, 0);

		$sql = "
		SELECT sum(amt) FROM hire.hire_nons_inv_items
		WHERE invid='$inv_data[invid]'";
		$hire_amt_rslt = db_exec($sql) or errDie("Unable to retrieve hire.");
		$hire_amt = pg_fetch_result($hire_amt_rslt, 0);

		$inv_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$inv_data[cusname]</td>
			<td>$inv_data[hire_invnum]</td>
			<td>$inv_data[invnum]</td>
			<td align='right'>".sprint($hire_amt)."</td>
			<td align='right'>".sprint($sales_amt)."</td>
			<td align='right'>".sprint($inv_data["vat"])."</td>
			<td align='right'>".sprint($inv_data["total"])."</td>
		</tr>";
		$inv_hire += $hire_amt;
		$inv_sales += $sales_amt;
		$inv_vat += $inv_data["vat"];
		$inv_total += $inv_data["total"];
	}

	if (empty($inv_out)) {
		$inv_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='7'><li>No invoices found</li></td>
		</tr>";
	}

	$OUTPUT .= "
	<h3>Invoices for $date</h3>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Customer</th>
			<th>Hire No</th>
			<th>Invoice No</th>
			<th width='10%'>Hire Exc</th>
			<th width='10%'>Sales Exc</th>
			<th width='10%'>Vat</th>
			<th width='10%'>Total</th>
		</tr>
		$inv_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><b>Total</b></td>
			<td align='right'>".sprint($inv_hire)."</td>
			<td align='right'>".sprint($inv_sales)."</td>
			<td align='right'>".sprint($inv_vat)."</td>
			<td align='right'>".sprint($inv_total)."</td>
		</tr>
	</table>";

	// Outstanding hires
	$sql = "SELECT customers.surname, invnum, from_date, to_date, amt, des
			FROM hire.hire_invitems
				LEFT JOIN hire.hire_invoices
					ON hire_invitems.invid=hire_invoices.invid
				LEFT JOIN cubit.customers
					ON hire_invoices.cusnum=customers.cusnum
				LEFT JOIN cubit.assets
					ON hire_invitems.asset_id=assets.id";
	$outstanding_rslt = db_exec($sql)
		or errDie("Unable to retrieve outstanding.");

	$outstanding_out = "";
	while ($outstanding_data = pg_fetch_array($outstanding_rslt)) {
		$outstanding_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$outstanding_data[des]</td>
			<td>$outstanding_data[surname]</td>
			<td>$outstanding_data[invnum]</td>
			<td>$outstanding_data[from_date]</td>
			<td>$outstanding_data[to_date]</td>
			<td align='right'>&nbsp;</td>
		</tr>";
	}

	if (empty($outstanding_out)) {
		$outstanding_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='6'><li>No open hires.</li></td>
		</tr>";
	}

	$OUTPUT .= "
	<h3>Open Hire Notes for $date</h3>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Asset</th>
			<th>Customer</th>
			<th>Hire No</th>
			<th>From Date</th>
			<th>To Date</th>
			<th width='10%'>Amount</th>
		</tr>
		$outstanding_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'><b>Total</b></td>
			<td align='right'>0.00</td>
	</table>";

	// Payments Received
	$sql = "
	SELECT date, type, surname, amount * -1 AS pamount
	FROM cubit.stmnt
		LEFT JOIN cubit.customers ON stmnt.cusnum=customers.cusnum
	WHERE date BETWEEN '$from_date' AND '$to_date' AND
		type LIKE 'Payment for%'";
	$payments_rslt = db_exec($sql) or errDie("Unable to retrieve payments.");

	$payments_out = "";
	$payment_total = 0;
	while ($payments_data = pg_fetch_array($payments_rslt)) {
		$payments_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$payments_data[date]</td>
			<td>$payments_data[type]</td>
			<td>$payments_data[surname]</td>
			<td align='right'>$payments_data[pamount]</td>
		</tr>";

		$payment_total += $payments_data["pamount"];
	}

	if (empty($payments_out)) {
		$payments_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'><li>No Payments found</li></td>
		</tr>";
	}

	$OUTPUT .= "
	<h3>Payments for $date</h3>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Date</th>
			<th>Details</th>
			<th>Customer</th>
			<th width='10%'>Amount</th>
		</tr>
		$payments_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><b>Total</b></td>
			<td align='right'>".sprint($payment_total)."</td>
		</tr>
	</table>";



	// General Credit Notes for the day ----------------------------------------------
	$gnotes_total = 0;
	$sql = "
	SELECT tdate, cusnum, creditnote_num, totamt 
	FROM cubit.credit_notes
	WHERE 
		tdate BETWEEN '$from_date' AND '$to_date'";
	$gnotes_rslt = db_exec($sql) or errDie("Unable to retrieve credit notes.");

	$gnotes_out = "";
	while ($gnotes_data = pg_fetch_array($gnotes_rslt)) {

		#get customer info
		$get_cust = "SELECT surname FROM cubit.customers WHERE cusnum = '$gnotes_data[cusnum]' LIMIT 1";
		$run_cust = db_exec($get_cust) or errDie ("Unable to get customer information.");
		if (pg_numrows($run_cust) < 1)
			$cust_surname = "";
		else 
			$cust_surname = pg_fetch_result ($run_cust,0,0);

		$gnotes_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$cust_surname</td>
			<td>$gnotes_data[creditnote_num]</td>
			<td align='right'>$gnotes_data[totamt]</td>
		</tr>";
		$gnotes_total += $gnotes_data["totamt"];
	}

	if (empty($gnotes_out)) {
		$gnotes_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'><li>No general credit notes found.</li></td>
		</tr>";
	}

	$OUTPUT .= "
	<h3>General Credit Notes for $date</h3>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th>Customer</th>
			<th>Credit Note No</th>
			<th width='10%'>Amount</th>
		</tr>
		$gnotes_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'><b>Total</b></td>
			<td align='right'><b>".sprint($gnotes_total)."</b></td>
		</tr>
	</table>";

	return $OUTPUT;
}

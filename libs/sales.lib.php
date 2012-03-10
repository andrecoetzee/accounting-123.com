<?php

function custTotalSales($cust_id, $date_from=0, $date_to=0)
{
	if (!$date_from) $date_from = date("Y-m")."-01";
	if (!$date_to) $date_to = date("Y-m-d");

	$total = 0;

	// Normal invoices
	$sql = "SELECT count(cusnum) FROM cubit.invoices
	WHERE cusnum='$cust_id' AND odate BETWEEN '$date_from' AND '$date_to'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	$total += pg_fetch_result($inv_rslt, 0);

	// POS invoices
	$sql = "SELECT count(invid) FROM cubit.pinvoices
	WHERE cusnum='$cust_id' AND odate BETWEEN '$date_from' AND '$date_to'";
	$pinv_rslt = db_exec($sql) or errDie("Unable to retrieve POS invoices.");
	$total += pg_fetch_result($pinv_rslt, 0);

	return $total;
}
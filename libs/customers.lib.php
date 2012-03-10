<?php

function customer_overdue($cusnum)
{

	$sql = "SELECT value FROM cubit.settings WHERE constant='OVERDUE_DAYS'";
	$days_rslt = db_exec($sql) or errDie("Unable to retrieve overdue days.");
	$days = pg_fetch_result($days_rslt, 0);

	if ($days == 0) return false;

	$sql = "SELECT fcid, location FROM cubit.customers WHERE cusnum='$cusnum'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer info.");
	list($fcid, $loc) = pg_fetch_array($cust_rslt, 0);

	$overdue = customer_age($cusnum, 10, $fcid, $loc);

	return ($overdue > 0) ? true : false;
}

function customer_age($cusnum, $days, $fcid, $loc)
{
	$ldays = $days;

	if ($days == 149) {
		$ldays = (365 * 10);
	}

	$sql = "
	SELECT sum(amount) FROM cubit.stmnt
	WHERE cusnum='$cusnum' AND
		date BETWEEN '".extlib_ago($ldays)."' AND '".extlib_ago($days-29)."'";
	$amount_rslt = db_exec($sql) or errDie("Unable to retrieve aging.");
	$amount = pg_fetch_result($amount_rslt, 0);

	return sprint($amount);
}

?>

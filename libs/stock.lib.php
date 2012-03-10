<?php

if (!defined("STOCK_LIB")) {
	define ("STOCK_LIB", true);

	function stock_is_blocked($stkid)
	{
		$sql = "SELECT blocked FROM cubit.stock WHERE stkid='$stkid'";
		$stock_rslt = db_exec($sql)
			or errDie("Unable to check if stock is blocked.");
		$blocked = pg_fetch_result($stock_rslt, 0);

		return $blocked;
	}
}
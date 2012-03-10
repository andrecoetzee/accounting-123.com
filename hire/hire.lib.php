<?

define("HOURS", 60 * 60);
define("DAYS", HOURS * 24);
define("WEEKS", DAYS * 7);

function monthly_invid($invid)
{
	$sql = "SELECT hire_invid FROM hire.hire_invoices WHERE invid='$invid'";
	$minv_rslt = db_exec($sql) or errDie("Unable to retrieve invid.");
	$minvid = pg_fetch_result($minv_rslt, 0);

	return (empty($minvid)) ? $invid : $minvid;
}

/**
 * returns the basis price for certain basis (basis = per_day/per_hour)
 *
 * @param int $asset_id
 * @param string $basis
 */
function basisPrice($cust_id, $asset_id, $basis)
{
// 	if ($basis != "per_day" && $basis != "per_hour") {
// 		return false;
// 	}

	switch ($basis) {
		case "per_hour":
			$basis = "hour";
			break;
		case "per_day":
			$basis = "day";
			break;
		case "per_week":
			$basis = "week";
			break;
		default:
			return false;
	}
	
	if (!empty($cust_id)) {
		$sql = "SELECT $basis FROM hire.cust_basis
				WHERE asset_id='$asset_id' AND cust_id='$cust_id'";
		$basis_rslt = db_exec($sql) or errDie("Unable to retrieve basis.");
	}

	if (empty($cust_id) || !pg_num_rows($basis_rslt)) {
		$sql = "SELECT per_$basis FROM hire.basis_prices WHERE assetid='$asset_id'";
		$basis_rslt = db_exec($sql) or errDie("Unable to retrieve default basis.");
	}

	return pg_fetch_result($basis_rslt, 0);
}

function updateTotals($invid)
{
	$sql = "
		SELECT 
			subtot, total, discount, delivery, vat, traddisc 
		FROM hire.hire_invoices
		WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$inv_data = pg_fetch_array($inv_rslt);

	$sql = "SELECT amt FROM hire.hire_invitems WHERE invid='$invid'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$subtot = 0;
	$vattot = 0;
	while ($item_data = pg_fetch_array($item_rslt)) {
		$subtot += $item_data["amt"];
		$vattot += $item_data["amt"] / 100 * 14;
	}

	$sql = "SELECT sum(excl_amount) FROM hire.hire_stock_items WHERE invid='$invid'";
	$stkamt_rslt = db_exec($sql) or errDie("Unable to retrieve amount.");
	$stkamt = pg_fetch_result($stkamt_rslt, 0) + 0;
	$subtot += $stkamt;

	$sql = "SELECT sum(vatamount) FROM hire.hire_stock_items WHERE invid='$invid'";
	$stkamt_rslt = db_exec($sql) or errDie("Unable to retrieve amount.");
	$vatamt = pg_fetch_result($stkamt_rslt, 0) + 0;
	$vattot += $vatamt;

	$subtot += $inv_data["delivery"];
	$subtot -= $inv_data["discount"];
//	$vat = $subtot / 100 * 14;

	#keep in mind the deduction of trade discount ...
	$vattot = sprint ($vattot - ($vattot / 100) * $inv_data['traddisc']);

	$total = $subtot + $vattot;

	$sql = "UPDATE hire.hire_invoices SET subtot='$subtot', vat='$vattot',
				total='$total' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update costs.");

	return;
}

function checkServicing($asset_id)
{
	$sql = "SELECT * FROM hire.service_days WHERE asset_id='$asset_id'";
	$sd_rslt = db_exec($sql) or errDie("Unable to retrieve service days.");
	$sd_data = pg_fetch_array($sd_rslt);

	if (rentalDays($asset_id) > $sd_data["days"]) {
		return $sd_data["days"];
	} else {
		return false;
	}
}

function rentalDays($asset_id)
{
	$seconds = 0;

	$sql = "SELECT *,
				EXTRACT('epoch' FROM hired_time) as e_hired,
				EXTRACT('epoch' FROM return_time) as e_return
			FROM hire.assets_hired WHERE asset_id='$asset_id'";
	$hi_rslt = db_exec($sql) or errDie("Unable to retrieve hired items.");
	$hi_data = pg_fetch_array($hi_rslt);

	$hired_time = $hi_data["e_hired"];
	$return_time = $hi_data["e_return"];

	$seconds = $return_time - $hired_time;

	$days = $seconds / 60 / 60 / 24;

	return $days;
}

function getSerial($asset_id, $brackets=false)
{
	$sql = "SELECT serial FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$serial = pg_fetch_result($asset_rslt, 0);

	if ($serial == "Not Serialized") {
		return "";
	} else {
		if ($brackets) $serial = "($serial)";
		return $serial;
	}
}

function inWorkshop($asset_id, $date)
{
	$sql = "SELECT refnum FROM cubit.workshop
				WHERE asset_id='$asset_id' AND
					'$date' BETWEEN cdate AND e_date";
	$ws_rslt = db_exec($sql) or errDie("Unable to retrieve workshop item.");

	if (pg_num_rows($ws_rslt)) {
		$refnum = pg_fetch_result($ws_rslt, 0);
		return $refnum;
	}

	return 0;
}

function isBooked($asset_id, $date)
{
	if (!isSerialized($asset_id)) {
		return false;
	}

	$sql = "SELECT * FROM hire.bookings
			WHERE asset_id='$asset_id' AND '$date' BETWEEN from_date AND to_date";
	$bk_rslt = db_exec($sql) or errDie("Unable to retrieve bookings.");
	$bk_data = pg_fetch_array($bk_rslt);

	if (pg_num_rows($bk_rslt)) {
		return $bk_data["cust_id"];
	}
	return false;
}

function isHired($asset_id, $date=false)
{
	if (!$date) $date = date("Y-m-d");

	$sql = "SELECT hire_invitems.id, hours, weeks, serial, serial2,
				printed, done, extract('epoch' FROM from_date) AS e_from,
				extract('epoch' FROM to_date) AS e_to, return_time
			FROM hire.hire_invitems
				LEFT JOIN hire.hire_invoices
					ON hire_invitems.invid = hire_invoices.invid
				LEFT JOIN cubit.assets
					ON hire_invitems.asset_id = assets.id
				LEFT JOIN hire.assets_hired
					ON hire_invitems.id = assets_hired.item_id
			WHERE hire_invitems.asset_id='$asset_id'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	// Check if item in workshop
	if (inWorkshop($asset_id, $date)) {
		return true;
	}

	while ($item_data = pg_fetch_array($item_rslt)) {
		if (!isSerialized($asset_id) && $item_data["serial2"] > 0) {
			return false;
		}

		if ($item_data["printed"] == "n" || $item_data["done"] == "n") {
			continue;
		}

		if (!empty($item_data["hours"])) {
			$to_date = hiredDate($item_data["id"], "U")+(HOURS*$item_data["hours"]);
		} elseif (!empty($item_data["weeks"])) {
			$to_date = hiredDate($item_data["id"], "U")+(WEEKS*$item_data["weeks"]);
		} else {
			$to_date = $item_data["e_to"];
		}

		$date = getDTEpoch("$date 0:00:00");

		if ($date >= $item_data["e_from"] && !$item_data["return_time"] && $date <= time()) {
			return true;
		}

		if ($date >= $item_data["e_from"] && $date <= $to_date) {
			return true;
		}
	}
	return false;
}

function hiredDate($item_id, $date_fmt="d-m-Y")
{
	$sql = "SELECT extract('epoch' FROM from_date) AS from_date,
				extract('epoch' FROM odate) AS odate
			FROM hire.hire_invitems
				LEFT JOIN hire.hire_invoices
					ON hire_invitems.invid=hire_invoices.invid
			WHERE id='$item_id'";
	$date_rslt = db_exec($sql) or errDie("Unable to retrieve date.");

	if (!pg_num_rows($date_rslt)) {
		$sql = "SELECT extract('epoch' FROM from_date) AS from_date,
					extract('epoch' FROM odate) AS odate
				FROM hire.reprint_invitems
					LEFT JOIN hire.reprint_invoices
						ON reprint_invitems.invid=reprint_invoices.invid
				WHERE item_id='$item_id'";
		$date_rslt = db_exec($sql) or errDie("Unable to retrieve date.");
	}

	list($from_date, $to_date) = pg_fetch_array($date_rslt);

	$date = ($from_date) ? $from_date : $odate;
	$date = date($date_fmt, $date);

	return $date;
}

function returnDate($item_id, $date_fmt="d-m-Y")
{
	$sql = "SELECT * FROM hire.hire_invitems WHERE id='$item_id'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve item.");
	$item_data = pg_fetch_array($item_rslt);

	if (!pg_num_rows($item_rslt)) {
		$sql = "SELECT * FROM hire.reprint_invitems WHERE item_id='$item_id'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve item.");
		$item_data = pg_fetch_array($item_rslt);
	}
	
	if (!isset($item_data['asset_id']))
		$item_data['asset_id'] = "0";

	$sql = "SELECT *,
				extract('epoch' FROM hired_time) AS e_hired
			FROM hire.assets_hired WHERE asset_id='$item_data[asset_id]'";
	$ah_rslt = db_exec($sql) or errDie("Unable to retrieve hire date.");
	$ah_data = pg_fetch_array($ah_rslt);

	if (!empty($item_data["to_date"])) {
		$to_date = getDTEpoch("$item_data[to_date] 23:59:59");
	}

	if (!empty($item_data["hours"])) {
		$to_date = hiredDate($item_id, "U") + (HOURS * $item_data["hours"]);
	}

	if (!empty($item_data["weeks"])) {
		$to_date = hiredDate($item_id, "U") + (WEEKS * $item_data["weeks"]);
	}

	if (!isset($to_date))
		$to_date = mktime (0,0,0,date("m"),date("d"),date("Y"));

	return date($date_fmt, $to_date);
}

function branchAddress($branch_id, $cust_id)
{
	if (!$branch_id) {
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cust_id'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		$addr = $cust_data["addr1"];
	} else {
		$sql = "SELECT branch_descrip FROM cubit.customer_branches
				WHERE id='$branch_id'";
		$branch_rslt = db_exec($sql) or errDie("Unable to retrieve customer branch.");
		$addr = pg_fetch_result($branch_rslt, 0);
	}

	return nl2br($addr);
}

function hireAddress($hire_id)
{
	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$hire_id'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve branch id.");
	$inv_data = pg_fetch_array($inv_rslt);

	if (!pg_num_rows($inv_rslt)) {
		$sql = "SELECT * FROM hire.reprint_invoices WHERE invid='$hire_id'";
		$inv_rslt = db_exec($sql) or errDie("Unable to retrieve branch id.");
		$inv_data = pg_fetch_array($inv_rslt);
	}

	return branchAddress($inv_data["branch_addr"], $inv_data["cusnum"]);
}

function isOverdue($item_id)
{
	$return_date = returnDate($item_id, "Y-m-d");

	$e_return = getDTEpoch("$return_date 12:00:00");

	if ($e_return < time()) {
		return true;
	} else {
		return false;
	}
}

function isOurs($asset_id)
{
	$sql = "SELECT remaction FROM cubit.assets WHERE id='$asset_id'";
	$rem_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$remaction = pg_fetch_result($rem_rslt, 0);

	switch ($remaction) {
		case "Removed":
		case "Sale":
			return false;
		default:
			return true;
	}
}

function isSerialized($asset_id)
{
	$sql = "SELECT serial FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$serial = pg_fetch_result($asset_rslt, 0);

	if ($serial == "Not Serialized") {
		return 0;
	} else {
		return 1;
	}
}

function getHirenum($invid, $show_revision=0)
{
	$sql = "SELECT invnum FROM hire.hire_invoices WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice number.");
	$invnum = pg_fetch_result($inv_rslt, 0);

	if (empty($invnum)) {
		$sql = "SELECT invnum FROM hire.notes_reprint WHERE invid='$invid'";
		$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice number.");
		$invnum = pg_fetch_result($inv_rslt, 0);
	}

	if (!$invnum && !$show_revision) {
		$invnum = divlastid('hire', USER_DIV);
	}

	if ($show_revision) {
		$invnum = $invnum . rev($invid);
	}

	return $invnum;
}

function newHirerev($invid)
{
	$sql = "SELECT invnum FROM hire.hire_invoices WHERE invid='$invid'";
	$invnum_rslt = db_exec($sql) or errDie("Unable to retrieve hire number.");
	$invnum = pg_fetch_result($invnum_rslt, 0);

	$sql = "SELECT max(last_value) FROM hire.hire_rev WHERE invnum='$invnum'";
	$rev_rslt = db_exec($sql) or errDie("Unable to retrieve hire number.");
	$rev = pg_fetch_result($rev_rslt, 0) + 1;

	$sql = "INSERT INTO hire.hire_rev (invid, invnum, last_value)
			VALUES ('$invid', '$invnum', '$rev')";
	db_exec($sql) or errDie("Unable to update hire revision.");

	return $rev;
}

function getHirerev($invid)
{
	$sql = "SELECT last_value, extract('epoch' FROM timestamp) AS e_time
			FROM hire.hire_rev
			WHERE invid='$invid'";
	$rev_rslt = db_exec($sql) or errDie("Unable to retrieve revision.");
	list($rev, $time) = pg_fetch_array($rev_rslt);

	if ($rev) {
		$rev = "-".date("Ymd", $time)."-$rev";
	} else {
		$rev = "";
	}

	return $rev;
}

function getUnits($asset_id)
{
	if (!isSerialized($asset_id)) {
		$sql = "SELECT serial2 FROM cubit.assets WHERE id='$asset_id'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
		$units = pg_fetch_result($asset_rslt, 0);
	} else {
		$units = 1;
	}
	return $units;
}

function unitsBooked($asset_id, $date)
{
	$current_time = time();
	$current_units = getUnits($asset_id);

	// Retrieve the max from time
	$sql = "SELECT max(to_date) FROM hire.bookings
				WHERE asset_id='$asset_id' AND to_date <= '$date'";
	$to_rslt = db_exec($sql) or errDie("Unable to retrieve to date.");
	$to_date = pg_fetch_result($to_rslt, 0);
	$to_date = getDTEpoch("$to_date 23:59:59");

	$units = array();
	for ($i = $current_time; $i < $to_date; $i += DAYS) {
		$tdate = date("Y-m-d", $i);

		// Retrieve all the bookings for this day
		$sql = "SELECT sum(units) FROM hire.bookings
					WHERE '$tdate' BETWEEN from_date AND to_date";
		$units_rslt = db_exec($sql) or errDie("Unable to retrieve bookings.");
		$units[$tdate] = pg_fetch_result($units_rslt, 0);
	}

	if (isset($units[$date])) {
		return $units[$date];
	} else {
		return 0;
	}
}

function getBookedItems($cusnum, $date)
{
	$sql = "SELECT asset_id, units FROM hire.bookings
			WHERE cust_id='$cusnum' AND '$date' BETWEEN from_date AND to_date";
	$booking_rslt = db_exec($sql) or errDie("Unable to retrieve booked items.");

	$assets = array();
	while ($booking_data = pg_fetch_array($booking_rslt)) {
		$assets[$booking_data["asset_id"]] = $booking_data["units"];
	}

	return $assets;
}

function unitsAvailable($asset_id, $date)
{
	$current_units = getUnits($asset_id);
	$booked_units = unitsBooked($asset_id, $date);

	$units = $current_units - $booked_units;
	return $units;
}

function utilisationDays($asset_id, $from_date, $to_date)
{
	$DAYS = 60 * 60 * 24;

	$from_time = getDTEpoch("$from_date 0:00:00");
	$to_time = getDTEpoch("$to_date 23:59:59");

	$total_days = ($to_time - $from_time) / $DAYS;
	$total_secs = 0;

	$sql = "
	SELECT extract('epoch' FROM hired_time) AS e_hired,
		extract('epoch' FROM return_time) AS e_return
	FROM hire.assets_hired
	WHERE asset_id='$asset_id'
		AND ((hired_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59')
		OR (return_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59')
		OR return_time IS NULL)";
	$util_rslt = db_exec($sql) or errDie("Unable to retrieve to utilisation.");

	while ($util_data = pg_fetch_array($util_rslt)) {
		// If the item has not yet been returned use the current time for the
		// utilisation calculations
		if (empty($util_data["e_return"])) $util_data["e_return"] = time();

		// Stay within the specified date range
		if ($util_data["e_hired"] < $from_time) $util_data["e_hired"] = $from_time;
		if ($util_data["e_return"] > $to_time) $util_data["e_return"] = $to_time;

		$total_secs += $util_data["e_return"] - $util_data["e_hired"];
	}

	$hired_days = $total_secs / $DAYS;
	return sprint($hired_days);
}

function utilisationPerc($asset_id, $from_date, $to_date)
{
	$DAYS = 60 * 60 * 24;

	$from_time = getDTEpoch("$from_date 0:00:00");
	$to_time = getDTEpoch("$to_date 23:59:59");

	$total_days = ($to_time - $from_time) / $DAYS;
	$hired_days = utilisationDays($asset_id, $from_date, $to_date);

	$percentage = $hired_days / $total_days * 100;
	return sprint($percentage);
}

function dateClean($year, $month, $day)
{
	if (!is_numeric($year)) $year = 1970;
	if (!is_numeric($month)) $month = '01';
	if (!is_numeric($day)) $day = '01';

	$time = mktime(0, 0, 0, $month, $day, $year);

	$year = date("Y", $time);
	$month = date("m", $time);
	$day = date("d", $time);

	return array($year, $month, $day);
}

function dateFmt($year, $month, $day, $format="Y-m-d")
{
	list($year, $month, $day) = dateClean($year, $month, $day);
	$time = mktime(0, 0, 0, $month, $day, $year);

	return date($format, $time);
}

function daysHired($item_id)
{
	$DAYS = 60 * 60 * 24;

	$sql = "SELECT extract('epoch' FROM hired_time) AS e_hired
			FROM hire.assets_hired
			WHERE item_id='$item_id'";
	$hired_rslt = db_exec($sql) or errDie("Unable to retrieve hired time.");
	$e_hired = pg_fetch_result($hired_rslt, 0);

	$days = (time() - $e_hired) / $DAYS;

	return round($days);
}

function detailsHoursHired($hired_id, $current=0)
{
	$HOURS = 60 * 60;

	$sql = "SELECT extract('epoch' FROM hired_time) AS e_hired,
				extract('epoch' FROM return_time) AS e_return,
				basis
			FROM hire.assets_hired
			WHERE id='$hired_id'";
	$hired_rslt = db_exec($sql) or errDie("Unable to retrieve hours hired.");

	list($e_hired, $e_return, $basis) = pg_fetch_array($hired_rslt);

	if ($basis != "per_hour") {
		return "-";
	}

	if (empty($e_return)) {
		return "Still on Hire";
	}

	if ($current) {
		$hired_secs = time() - $e_hired;
	} else {
		$hired_secs = $e_return - $e_hired;
	}
	$hired_hours = round($hired_secs / $HOURS);
	if (!$hired_hours) $hired_hours = 1;

	return $hired_hours;
}

function detailsDaysHired($hired_id, $current=0)
{
	$DAYS = 60 * 60 * 24;

	$sql = "SELECT extract('epoch' FROM hired_time) AS e_hired,
				extract('epoch' FROM return_time) AS e_return,
				basis
			FROM hire.assets_hired
			WHERE id='$hired_id'";
	$hired_rslt = db_exec($sql) or errDie("Unable to retrieve days hired.");

	list($e_hired, $e_return, $basis) = pg_fetch_array($hired_rslt);

	if ($basis != "per_day") {
		return "-";
	}

	if (empty($e_return)) {
		return "Still on Hire";
	}

	if ($current) {
		$hired_secs = time() - $e_hired;
	} else {
		$hired_secs = $e_return - $e_hired;
	}
	$hired_days = floor($hired_secs / $DAYS);
	if (!$hired_days) $hired_days = 1;

	return $hired_days;
}

function detailsWeeksHired($hired_id, $current=0)
{
	$WEEKS = (60 * 60 * 24) * 7;

	$sql = "SELECT extract('epoch' FROM hired_time) AS e_hired,
				extract('epoch' FROM return_time) AS e_return,
				basis
			FROM hire.assets_hired
			WHERE id='$hired_id'";
	$hired_rslt = db_exec($sql) or errDie("Unable to retrieve weeks hired.");

	list($e_hired, $e_return, $basis) = pg_fetch_array($hired_rslt);

	if ($basis != "per_week") {
		return "-";
	}

	if (empty($e_return)) {
		return "Still on Hire";
	}

	if ($current) {
		$hired_secs = time() - $e_hired;
	} else {
		$hired_secs = $e_return - $e_hired;
	}

	$hired_weeks = floor($hired_secs / $WEEKS);
	if (!$hired_weeks) $hired_weeks = 1;

	return $hired_weeks;
}

function numAlpha($num)
{
	$alphabet = array(1=>"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
					  "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w",
					  "x", "y", "z");
	
	$alpha = "";
	if ($num > 26) {
		$alpha .= "a";
		$num -= 26;
	} else {
		$alpha .= $alphabet[$num];
	}
	
	return $alpha;
}

function rev($invid)
{
	$sql = "SELECT revision FROM hire.hire_invoices WHERE invid='$invid'";
	$rev_rslt = db_exec($sql) or errDie("Unable to retrieve revision.");
	$rev = pg_fetch_result($rev_rslt, 0);
	
	if ($rev)
		return numAlpha($rev);
	else
		return "";
}

function rrev($invid)
{
	$sql = "SELECT revision FROM hire.reprint_invoices WHERE invid='$invid'";
	$rev_rslt = db_exec($sql) or errDie("Unable to retrieve revision.");
	$rev = pg_fetch_result($rev_rslt, 0);
	
	if ($rev)
		return numAlpha($rev);
	else
		return "";
}

function default_basis($asset_id)
{
	$sql = "
	SELECT default_basis FROM hire.basis_prices
	WHERE assetid='$asset_id'";
	$basis_rslt = db_exec($sql) or errDie("Unable to retrieve basis price.");
	$basis = pg_fetch_result($basis_rslt, 0);

	$basis_types = array("per_hour", "per_day", "per_week", "per_month");

	if (empty($basis) || !in_array($basis, $basis_types)) {
		$basis = "per_day";
	}
	return $basis;
}

function halfday_rate()
{
	$sql = "SELECT value FROM cubit.settings WHERE constant='HD_PERC'";
	$hd_rslt = db_exec($sql) or errDie("Unable to retrieve half day rate.");
	$hd_perc = pg_fetch_result($hd_rslt, 0);

	if (empty($hd_perc)) {
		$hd_perc = 60;
	}

	$hd_rate = 100 / $hd_perc;

	return $hd_rate;
}

?>

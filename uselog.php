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

if ( ! defined("USELOG_H") ) {
	define("USELOG_H", true);

	/* expiry reasons */
	define("UE_EXPIRED", 0x01);
	define("UE_INVALIDKEY", 0x02);
	define("UE_TIMELOSS", 0x04);
	define("UE_DAYLOGS", 0x08);
	define("UE_USAGETIME", 0x10);
	define("UE_TRANSDATE", 0x20);

	/* usage log array */
	$uselog_blank = array('str'=>'', 'timestamp'=>'', 'date'=>'', 'time'=>'');
	$uselog = array(
		'totdays'		=> $uselog_blank,
		'rfirstday'		=> $uselog_blank,
		'firstday'		=> $uselog_blank,
		'lastday'		=> $uselog_blank,
		'firsttrans'		=> $uselog_blank,
		'lasttrans'		=> $uselog_blank,
		'randhash'		=> $uselog_blank,
		'expired'		=> $uselog_blank,
		'registered'		=> $uselog_blank,
		'norefresh'		=> $uselog_blank, // tmp variable used to prevent a refresh on register.php

		'reg_randhash'		=> $uselog_blank,
		'reg_expired'		=> $uselog_blank,
		'reg_firstday'		=> $uselog_blank,
		'reg_lastday'		=> $uselog_blank,
		'reg_firsttrans'	=> $uselog_blank
	);

	function refreshUselog() {
		global $uselog;

		// get all the usage log values
		foreach ( $uselog as $desc => $val ) {
			$uselog[$desc] = getUsage($desc);
		}
	}

	function recordUsage() {
		global $uselog;

		if (SELF == "doc-index.php") return;

		// make EXPIRE_DAYS constant
		if (!defined("EXPIRE_DAYS")) {
			db_con("cubit");
			$sql = "SELECT * FROM uselog WHERE name='registered'";
			$rslt = db_exec($sql);

			if ( pg_num_rows($rslt) < 1 ) {
				define("EXPIRE_DAYS", 60);
			} else {
				define("EXPIRE_DAYS", 180);
			}
		}

		refreshUselog();

		// clear the norefresh var (used for register.php)
		if (!defined("REGISTER_PHP") && SELF != "register.php") {
			db_con("cubit");
			$sql = "DELETE FROM uselog WHERE name='norefresh'";
			$rslt = db_exec($sql) or errDie("Error clearing refresh block.");
		}

		// if no real first date was set, set it
		if (empty($uselog["rfirstday"]["date"])) {
			setUsage("rfirstday", "");
		}

		// if no first date was set, set it
		if (empty($uselog["firstday"]["date"])) {
			setUsage("firstday", "");
			setUsage("lastday", "");

			// generate 5 random numbers
			setUsage("randhash", genRandHash());

			refreshUselog();

			return;
		}

		// set first transaction date
		if (($tmp = getFirstTrans()) !== false) {
			$ft_arr = array(
				"str"		=> "",
				"timestamp"	=> "$tmp 00:00:00",
				"date"		=> "$tmp",
				"time"		=> "00:00:00"
			);
			$uselog["firsttrans"] = $ft_arr;
			setUsage("firsttrans", $ft_arr);
		}

		// if expired dont even go on
		if (!empty($uselog["expired"]["str"]) && empty($uselog["registered"]["str"])) {
			usageExpired(UE_EXPIRED);
		}

		// invalid key
		if (!empty($uselog["registered"]["str"]) && !checkkey($uselog["registered"]["str"], true)) {
			usageExpired(UE_INVALIDKEY);
		}

		// check if the current system date is before the last usage date
		if (!empty($uselog["lastday"]["date"])) {
			db_con("cubit");
			$sql = "SELECT AGE(CURRENT_TIMESTAMP, timestampval) < '-5 seconds' FROM uselog WHERE (name='lastday' OR name='firstday')
					UNION
					SELECT AGE(CURRENT_TIMESTAMP, timestampval) < '-5 seconds' FROM uselog WHERE name='daylogs' AND strval=CURRENT_DATE";
			$rslt = db_exec($sql) or errDie("Error doing date manipulation check.");

			while ( $row = pg_fetch_row($rslt) ) {
				if ( $row[0] == "t" ) {
					setUsage("expired", UE_TIMELOSS);
					$uselog["expired"] = getUsage("expired");
					usageExpired(UE_TIMELOSS);
				}
			}
		}

		// record todays date as last day
		setUsage("lastday", "");
		$uselog["lastday"] = getUsage("lastday");

		// record date in daylogs
		db_con("cubit");
		$sql = "SELECT * FROM uselog WHERE name='daylogs' AND strval=CURRENT_DATE";
		$rslt = db_exec($sql) or errDie("Error updating daylogs (READ).");

		if (pg_num_rows($rslt) <= 0) {
			$sql = "
				INSERT INTO uselog (
					name, strval, timestampval, dateval, timeval
				) VALUES (
					'daylogs', CURRENT_DATE, CURRENT_TIMESTAMP, CURRENT_DATE, CURRENT_TIME
				)";
			$rslt = db_exec($sql) or errDie("Error updating daylogs (INS).");
		} else {
			$sql = "
				UPDATE uselog 
				SET timestampval = CURRENT_TIMESTAMP, dateval = CURRENT_DATE, timeval = CURRENT_TIME
				WHERE name = 'daylogs' AND strval = CURRENT_DATE";
			$rslt = db_exec($sql) or errDie("Error updating daylogs (UPD).");
		}

		// count the number of individual days
		$sql = "SELECT COUNT(*) FROM uselog WHERE name='daylogs'";
		$rslt = db_exec($sql) or errDie("Error counting usage days.");
		$daycount = pg_fetch_result($rslt, 0, 0);

		if ($daycount > EXPIRE_DAYS) {
			setUsage("expired", UE_DAYLOGS);
			$uselog["expired"] = getUsage("expired");
			usageExpired(UE_DAYLOGS);
		}

		setUsage("totdays", $daycount);
		$uselog["totdays"] = getUsage("totdays");

		// check if usage time has passed
		$sql = "SELECT AGE(CURRENT_TIMESTAMP, timestampval) > '".EXPIRE_DAYS." days' FROM uselog WHERE name='firstday'";
		$rslt = db_exec($sql) or errDie("Error checking usage age.");

		while ($row = pg_fetch_row($rslt)) {
			if ($row[0] == 't') {
				setUsage("expired", UE_USAGETIME);
				$uselog["expired"] = getUsage("expired");
				usageExpired(UE_USAGETIME);
			}
		}

		// if not registered, also check with first transaction date
		if (empty($uselog["registered"]["str"])) {
			$sql = "SELECT AGE(CURRENT_TIMESTAMP, timestampval) > '".EXPIRE_DAYS." days' FROM uselog WHERE name='firsttrans'";
			$rslt = db_exec($sql) or errDie("Error checking usage age.");

			while ($row = pg_fetch_row($rslt)) {
				if ($row[0] == 't') {
					setUsage("expired", UE_TRANSDATE);
					$uselog["expired"] = getUsage("expired");
					usageExpired(UE_TRANSDATE);
				}
			}
		}
	}

	function genRandHash() {
		$rnums = array();

		$result = 0;

		for ($n = 0; $n < 5; ++$n) {
			list($usec, $sec) = explode(' ', microtime());
			srand((float) $sec + ((float) $usec * 1000000));

			$randhash = md5(sin(log10(rand()) << 1));

			for ($i = 0; $i < 32; ++$i) {
				$result += ord((($randhash[$i] << 2) ^ rand()) >> 3);
			}

			$rnums[] = $result % 256;
		}

		return implode("|", $rnums);
	}

	function getUsage($desc) {
		global $uselog_blank;

		db_con("cubit");

		$sql = "SELECT strval AS str,timestampval AS timestamp,dateval AS date,timeval AS time
				FROM uselog WHERE name='$desc'";
		$rslt = db_exec($sql) or errDie("Error reading usage log (GU).");

		if ( pg_num_rows($rslt) >= 0 ) {
			return pg_fetch_array($rslt);
		} else {
			return $uselog_blank;
		}
	}

	function setUsage($desc, $usage) {
		db_con("cubit");

		if ($desc == "expired") {
			$usage = getUEDesc($usage);
		}

		// when usage is a string, all values are set to CURRENT time, and strval to what usage equals
		if (!is_array($usage)) {
			$sql = "
				UPDATE uselog 
				SET strval = '$usage', timestampval = CURRENT_TIMESTAMP, dateval = CURRENT_DATE, timeval = CURRENT_TIME 
				WHERE name='$desc'";
			$rslt = db_exec($sql) or errDie("Error updating usage log (SUDU).");

			if ( pg_cmdtuples($rslt) <= 0 ) {
				$sql = "
					INSERT INTO uselog (
						name, strval, timestampval, dateval, timeval
					) VALUES (
						'$desc', '$usage', CURRENT_TIMESTAMP, CURRENT_DATE, CURRENT_TIME
					)";
				$rslt = db_exec($sql) or errDie("Error updating usage log (SUDI).");
			}
		} else {
			$sql = "
				UPDATE uselog 
				SET strval='$usage[str]', timestampval='$usage[timestamp]', dateval='$usage[date]', timeval='$usage[time]' 
				WHERE name='$desc'";
			$rslt = db_exec($sql) or errDie("Error updating usage log (SUU).");

			if ( pg_cmdtuples($rslt) <= 0 ) {
				$sql = "
					INSERT INTO uselog (
						name, strval, timestampval, dateval, timeval
					) VALUES (
						'$desc', '$usage[str]', '$usage[timestamp]', '$usage[date]', '$usage[time]'
					)";
				$rslt = db_exec($sql) or errDie("Error updating usage log (SUI).");
			}
		}
	}

	/**
	 * determines an description for expiry by UE_* constants
	 */
	function getUEDesc($reason) {
		switch($reason) {
		case UE_EXPIRED:
			$r = "Already Expired";
			break;
		case UE_INVALIDKEY:
			$r = "Invalid Key in Database";
			break;
		case UE_TIMELOSS:
			$r = "System Time before a Time Cubit Recorded";
			break;
		case UE_DAYLOGS:
			$r = "Total Individual Days Exceeds Maximum Allowed";
			break;
		case UE_USAGETIME:
			$r = "Usage Time Exceeds Maximum Allowed";
			break;
		case UE_TRANSDATE:
			$r = "Oldest Transactions Exceeds Maximum Allowed";
			break;
		default:
			$r = "Unknown reason. ($reason)";
			break;
		}

		return $r;
	}

	function usageExpired($reason) {
		if ( (defined("REGISTER_PHP") && SELF == "register.php") 
			|| (defined("SETUP_PHP") && SELF == "setup.php") ) return;
		if (SELF == "checkmsgs.php") {
			parse();
		}

		$register = "register.php";

		while (!is_file($register)) {
			$register = "../$register";
		}

		print "<script>document.location='register.php';</script>";
		die;
//		$OUTPUT = "You have been using Cubit for more than ".EXPIRE_DAYS." days without registering.
//					Please click <a href='$register' class='nav' style='color:red'><font size='4'>here</font></a>.
//					<!-- ".getUEDesc($reason)." //-->";
//		require("template.php");
	}

	function getFirstTrans() {
		$queries = array();
		for ($i = 1; $i <= 14; ++$i) {
			$queries[] = "SELECT date FROM \"$i\".transect";
		}

		db_conn("cubit");
		$sql = implode(" UNION ", $queries) . " ORDER BY date ASC LIMIT 1";
		$rslt = db_exec($sql) or errDie("Error getting first transaction date.");

		while ($row = pg_fetch_row($rslt)) {
			if (empty($oldest)) {
				$oldest = $row[0];
			} else if (!empty($row[0])) {
				list($year, $month, $day) = explode("-", $row[0]);
				list($o_year, $o_month, $o_day) = explode("-", $oldest);

				$ts = mktime(0, 0, 0, $month, $day, $year);
				$o_ts = mktime(0, 0, 0, $o_month, $o_day, $o_year);

				if ($ts < $o_ts) {
					$oldest = date("Y-m-d", $ts);
				}
			}
		}

		if (empty($oldest)) {
			return false;
		}

		return $oldest;
	}

	function uselog_version() {
		return array("version", CUBIT_IV.":".CUBIT_IVR);
	}

	function uselog_countusers() {
		$qry = new dbSelect("companies", DB_MCUBIT);
		$qry->run();

		$total = 0;
		$comps = $qry->num_rows();
		while ($ci = $qry->fetch_array()) {
			db_connComp("cubit", $ci["code"]);
			$sql = "SELECT count(*) FROM cubit.users";
			$rslt = db_exec($sql);

			$total += ($n = pg_fetch_result($rslt, 0));

		}

		db_conn("cubit");

// 		if (($total = $total/$comps) > 1295) {
// 			$total = 1295;
// 		}

		if (($total/$comps) > 1295) {
			$total = 1295;
		}

		return array("number", $total);
	}

	function uselog_countcomps() {
		$qry = new dbSelect("companies", DB_MCUBIT, grp(
			m("cols", "COUNT(*)")
		));
		$qry->run();

		if (($total = $qry->fetch_result()) > 1295) {
			$total = 1295;
		}

		return array("number", $total);
	}
}

?>

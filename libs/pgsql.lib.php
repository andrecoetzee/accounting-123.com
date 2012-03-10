<?

if (!defined("PGSQL_LIB")) {
	define("PGSQL_LIB", true);

define ("PGLIB_USER", "postgres");
define ("PGLIB_PASS", "i56kfm");
define ("PGLIB_DBNAME", "cubit");

# connect to db
function pglib_connect ($user=PGLIB_USER, $password=PGLIB_PASS, $dbname=PGLIB_DBNAME)
{
	$link = pg_connect ("user=$user password=$password dbname=$dbname");
	return $link;
}

# start, end or rollback a transaction
function pglib_transact ($command)
{
	return pglib_transaction($command);
}

function pglib_transaction ($cmd, $nodebug = false){
	# lowercase
	$command = strtoupper($cmd);

	if (defined("ERRORNET_OCCURED") && $command == "COMMIT") {
		$command = "ROLLBACK";
	}

	# begin / commit / rollback?
	switch ($command) {
		case "BEGIN":
			$sql = "BEGIN";
			break;
		case "COMMIT":
			$sql = "COMMIT";
			break;
		default:
			if (preg_match("/^SAVEPOINT/i", $cmd)
				|| preg_match("/^ROLLBACK TO/i", $cmd)
				|| preg_match("/^RELEASE SAVEPOINT/i", $cmd)) {
					$sql = $cmd;
			} else {
				$sql = "ROLLBACK";
			}
	}

	if ($transRslt = db_exec($sql, $nodebug)) {
		return 1;
	} else {
		return 0;
	}
}

function pglib_lastid($table, $col) {
	# Get last inserted id value, die if fails
	if (!$lastidRslt = db_exec ("SELECT last_value FROM ".$table."_".$col."_seq")) {
		return 0;
	}

	# die if no result
	if (pg_num_rows($lastidRslt) < 1) {
		return 0;
	}
	$myId = pg_fetch_row ($lastidRslt, 0);
	return $myId[0];
}


function pglib_getlastid ($table)
{
	# get last inserted id value, die if fails
	if (!$lastidRslt = db_exec ("SELECT last_value FROM ".$table)) {
		return 0;
	}
	# die if no result
	if (pg_numrows ($lastidRslt) < 1) {
		return 0;
	}
	$myId = pg_fetch_row ($lastidRslt, 0);
	return $myId[0];
}

} /* LIB END */

?>

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
##
# pgsql.lib.php :: Libraries for easy PostgreSQL access
##

##
# Default settings
##

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
	# lowercase
	$command = strtolower ($command);

	# begin / commit / rollback?
	switch ($command) {
		case "begin":
			$sql = "BEGIN";
			break;
		case "commit":
			$sql = "COMMIT";
			break;
		default:
			$sql = "ROLLBACK";
	}

	# execute sql & return success / fail
	if ($transRslt = db_exec ($sql)) {
		return 1;
	} else {
		return 0;
	}
}

function pglib_transaction ($command){
	# lowercase
	$command = strtolower ($command);

	# begin / commit / rollback?
	switch ($command) {
		case "begin":
			$sql = "BEGIN";
			break;
		case "commit":
			$sql = "COMMIT";
			break;
		default:
			$sql = "ROLLBACK";
	}

	# execute sql & return success / fail
	if ($transRslt = pg_exec (DB_CUBIT, $sql)) {
	} else {
		return 0;
	}
	
	return 1;
}

# get last inserted id from ANY standard sequence
function pglib_lastid ($table, $col)
{
	# Get last inserted id value, die if fails
	if (!$lastidRslt = db_exec ("SELECT last_value FROM ".$table."_".$col."_seq")) {
		return 0;
	}
	# die if no result
	if (pg_numrows ($lastidRslt) < 1) {
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

?>

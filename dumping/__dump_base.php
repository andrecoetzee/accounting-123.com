#!/usr/bin/php
<?
// script that generates Cubit, beating the living hell out of .sql :>

//* COMMENT THE NEXT LINE OUT TOO SEE DEBUGGING */
//define("DEBUG", true);

/******************************************/
/* DO NOT CHANGE ANYTHING BELOW THIS LINE */
/* DO NOT CHANGE ANYTHING BELOW THIS LINE */
/* DO NOT CHANGE ANYTHING BELOW THIS LINE */
/******************************************/

// open stderr
$stderr = fopen("/dev/stderr", "w");

// status debug function
function status($str, $always_show=false) {
	global $_chrs, $_cchr;
	if ( $always_show == true ) {
		perr($str);
	} else if ( defined("DEBUG") ) {
		perr($str);
	}
}

$squirrel_chrs = "/-\|";
$squirrel_at = 0;
function updatesquirrel() {
	global $squirrel_chrs, $squirrel_at;

	if ( !defined("DEBUG") ) {
		if ( $squirrel_at >= 0 ) {
			perr("\x08");
		}

		if ( ++$squirrel_at > 3 ) {
			$squirrel_at = 0;
		}

		perr(substr($squirrel_chrs, $squirrel_at, 1));
	}
}

$called = 0;
function updatestatbar($curtable) {
 	global $tbl_count, $called, $time_started;

	// print the status bar only if not in DEBUG mode
	if ( !defined("DEBUG") ) {
		// clear the status bar (only if has been drawn)
		if ( $called ) {
			for ( $i = 0; $i < 59; $i++ ) {
				perr("\x08");
			}
		}
		
		// calculate percentage finished
		$perc_fin = round($curtable/$tbl_count*100);

		// print the status bar
		perr("[");
		for ( $i = 0; $i < ($perc_fin/2); $i++ ) {
			perr("=");
		}
		for ( ; $i < 50; $i++ ) {
			perr(" ");
		}
		perr("] ");
		
		// percentage
		perr(str_pad($perc_fin, 3, ' ', STR_PAD_LEFT)."%  ");

		// print the time remaining

		$called = 1;
	}
}

// prints to stderr
function perr($str) {
	global $stderr;
	fputs($stderr, $str);
}

// get the command line parameter
$schema = "";
$ccode = "";
for ( $i = 0 ; $i < $argc ; $i++ ) {
	// if the current arg is schema and it not the last, then read the next as the schema name to dump
	if ( $argv[$i] == "schema" && $i < ($argc-1) ) {
		$schema = $argv[$i+1];
	}

	// if the current arg is comp and it not the last, then read the next as the company code
	if ( $argv[$i] == "comp" && $i < ($argc-1) ) {
		$ccode = $argv[$i+1];
	}
}

// if no specification was found
if ( $schema == "" || $ccode == "" ) {
	status("\n \033[31mError, invalid command syntax.\033[0m\n\n Run as: php -f $argv[0] -- schema <schema_name> comp <company_code>\n\n", true);
	die();
}

// constants
$DB_HOST = "";
$DB_USER = "postgres";
$DB_PASS = "aa";

// connect to Cubit before you can start the reading and dumping
$db_con = pg_connect("user=$DB_USER password=$DB_PASS $DB_HOST dbname=template1") or die("Err: Con to database template1.\n");

// ok check now if Cubit even exists
$rslt = pg_exec("SELECT * FROM pg_database WHERE datname='cubit_$ccode'");
if ( pg_num_rows($rslt) <= 0 ) {
	status("\n \033[31mError, no such database cubit_$ccode\".\033[0m\n\n Run as: php -f $argv[0] -- db <database_name> comp <company_code>\n\n", true);
	die();
}

pg_close($db_con);

// start the script
//print "\n-- START -- Creating schema $schema tables\n\n";

$db_con = pg_connect("user=$DB_USER password=$DB_PASS $DB_HOST dbname=cubit_$ccode") or die("Err: Con to database cubit_$ccode.\n");

// select all the user created tables from the current db schema
$sql = "SELECT c.relname AS table_name, c.relkind AS table_type 
		FROM pg_class c, pg_attribute a LEFT JOIN pg_attrdef ad 
			ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum, pg_type t, pg_namespace nc 
		WHERE t.oid=a.atttypid AND c.oid=a.attrelid AND nc.oid=c.relnamespace AND nc.nspname='$schema' 
			AND (c.relkind='r'::char OR c.relkind='v'::char OR c.relkind='c'::char)
		GROUP BY c.relkind, c.relname
		ORDER BY table_type";
$tbl_rslt = pg_exec($sql);
//$tbl_rslt = pg_exec("SELECT table_name,table_type FROM information_schema.tables WHERE table_schema = '$schema'");

// loop through each table and create the table creation queries
status("starting to dump.\n");
$tbl_count = pg_num_rows($tbl_rslt);
$tbl_cur = 0;
$time_started = time();	

status("prefetching field information... ");
// read the field information
$fields_sql = "
SELECT 
	CASE 
		WHEN pg_has_role(c.relowner, 'MEMBER'::text) THEN pg_get_expr(ad.adbin, ad.adrelid) 
		ELSE NULL::text 
	END::information_schema.character_data AS column_default, 
	information_schema._pg_numeric_precision(information_schema._pg_truetypid(a.*, t.*), 
		information_schema._pg_truetypmod(a.*, t.*))::information_schema.cardinal_number AS numeric_precision, 
	information_schema._pg_numeric_scale(information_schema._pg_truetypid(a.*, t.*), 
		information_schema._pg_truetypmod(a.*, t.*))::information_schema.cardinal_number AS numeric_scale,
	t.typname, a.attname AS fieldname, c.relname
FROM pg_class c, pg_attribute a LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum, 
	pg_type t, pg_namespace nc 
WHERE t.oid=a.atttypid AND c.oid=a.attrelid AND nc.oid=c.relnamespace 
	AND nc.nspname='$schema';";

$fields_rslt = pg_exec($fields_sql);
	
$fields_info = array();

while ($row = pg_fetch_array($fields_rslt)) {
	if (!isset($fields_info[$row["relname"]])) {
		$fields_info[$row["relname"]] = array();
	}

	$fields_info[$row["relname"]][$row["fieldname"]] = array(
		"default" => $row["column_default"],
		"precision" => $row["numeric_precision"],
		"scale" => $row["numeric_scale"],
		"datatype" => $row["typname"]
	);
}

/* views aren't printed directly, we need to make sure their tables are created first */
$VIEW_SQL = array();

while ( $tbl_row = pg_fetch_array($tbl_rslt) ) {
	updatestatbar($tbl_cur++);

	$tbl_name = $tbl_row["table_name"];

	/* check if view and handle appropriatly */
	if ($tbl_row["table_type"] == "v") {
		status("querying view definition... ");
		
		$view_sql = "SELECT view_definition FROM information_schema.views 
				WHERE table_schema='$schema' AND table_name='$tbl_name' LIMIT 1";
		$view_rslt = pg_exec($view_sql) or die("view query failed: $tbl_name");

		if (pg_num_rows($view_rslt) != 1) {
			die("view insufficient results: $tbl_name");
		}
		
		$view_def = pg_fetch_result($view_rslt, 0, 0);

		$VIEW_SQL[] = "CREATE VIEW $tbl_name AS $view_def\n";

		status("done\n");
		updatesquirrel();
		continue;
	}

	/* if custom datatype */
	if ($tbl_row["table_type"] == "c") {
		status("building custom datatype ($tbl_name) query... ");

		$parts = array();
		foreach ($fields_info[$tbl_name] as $fld_name => $fld_info) {
			$parts[] = "$fld_name $fld_info[datatype]";
		}

		print "CREATE TYPE \"$schema\".$tbl_name AS (".implode(", ", $parts).");\n";

		status("done\n");
		continue;
	}
	
	// get the fields
	status("querying for data... ");
	$flds_rslt = pg_exec("SELECT * FROM \"$schema\".$tbl_name");
	status("done\n");

	$create_fields = Array(); // reset it
	$insert_fields = Array(); // reset it
	$sequences = Array();

	status("creating create query...\n");
	for ( $fld_num = 0 ; $fld_num < pg_numfields($flds_rslt) ; $fld_num++ ) {
		$nextval = false; // last sequence value
		
		// read the fieldname
		$f_name = pg_fieldname($flds_rslt, $fld_num);
		$f_info = $fields_info[$tbl_name][$f_name];

		status("done\n");

		// if field has a default value starting with nextval, get the sequence name
		$seq_name = Array();
		status("checking for sequence... ");
		// true_ids is a column for which we should not recreate a sequence, they are
		// manually created (usually used for tables sharing 1 sequence)
		if ( $f_name != "true_ids" &&
			(preg_match("/^nextval\('(.*)\.(.*)'::text\)/", $f_info["default"], $seq_name) 
			|| preg_match("/^nextval\('(.*)\.(.*)'::regclass\)/", $f_info["default"], $seq_name)) ) {
			status(" (seq) ");
			$seq_schema = $seq_name[1];
			$seq_name = $seq_name[2];

			$f_type = "serial NOT NULL PRIMARY KEY";

			// read the next val it had
			$nval_rslt = pg_exec("SELECT last_value FROM $seq_schema.$seq_name");
			if ( pg_num_rows($nval_rslt) > 0 ) {
				$nextval = pg_fetch_result($nval_rslt,0,0);
			} else { // no serial values was in table
				$nextval = 0;
			}
		} else {
			status(" (no seq) ");
			$f_type = $f_info["datatype"];
		}
		status("done\n");

                // create the numeric precision
                if ( strncmp($f_type, "numeric", 7) == 0 ) {
			status("setting numeric precision... ");
			$precision = $f_info["precision"];
			$scale = $f_info["scale"];
			if ($precision > 10) {
				$precision = 16;
			}

                        // default precision, so leave it blank
			if ( ( !empty($precision) && !empty($scale) ) && ($precision <= 1000 || $scale <= 1000) ) {
				$f_type .= "($precision, $scale)";
			}
			status("done\n");
		}

		// for columns named true_ids we will manually create a sequence later (setting the dflt)
		if ($f_name == "true_ids") {
			$f_dflt = "";
		// if it is an number type make it have a default value
		} else if ($nextval === false && strlen($f_info["default"]) > 0) {
			$f_dflt = "DEFAULT $f_info[default]";
		} else if ($nextval === false && preg_match("/^(numeric|float|int)/", $f_type) ) {
			$f_dflt = "DEFAULT 0";
		} else {
			$f_dflt = "";
		}

		// add the field to the array (used when creating the query brackets)
		$create_fields[] = "\"$f_name\" $f_type $f_dflt";
		$insert_fields[] = "\"$f_name\"";
	
		// if we had a sequence, tell so in seq array
		if ( $nextval > 0 ) {
			$sequences[$seq_name] = $nextval;
		}

		updatesquirrel();
	}

	print "CREATE TABLE $tbl_name (".implode(",", $create_fields).") WITH OIDS;\n";

	foreach ( $sequences as $seq_name => $nextval ) {
		print "SELECT setval('$seq_name',$nextval);\n";
	}

	status("done creating query data.\n\n");

	// create the insert data queries
	status("dumping data for table... ");
	//print "BEGIN;\n";
	while ( $fld_row = pg_fetch_row($flds_rslt) ) {
		$idata = "";
		foreach ( $fld_row as $key => $value ) {
			// read the field information
			$f_type = pg_fieldtype($flds_rslt, $key);

			// if the type is numeric or float and empty set it to zero
			if ( empty($value) && preg_match("/^(float|numeric|int)/", $f_type) ) {
				$value = "0";
			}

			// escape the quotes
			$value = str_replace("'", "\'", $value);

			$idata[] = "'$value'";

			updatesquirrel();
		}
		$query_indata = implode(",", $idata);

		print "INSERT INTO $tbl_name (".implode(",", $insert_fields).") VALUES($query_indata);\n";
	}
	//print "COMMIT;\n";
	status("done\n");
}

foreach ($VIEW_SQL as $sql) {
	print $sql;
}
updatestatbar($tbl_count);
perr("\n");

// end the connection data
pg_close($db_con);
fclose($stderr);

//print "\n-- END -- Creating schema $schema tables\n\n";

?>


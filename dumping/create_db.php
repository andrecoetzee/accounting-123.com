<?

/* SETTINGS */
$TMPL_CODE = "blk1";
$COMP_CODE = "aaaa";
/* SETTINGS END */

$CWD = __FILE__;
$CWD = preg_replace("/\\\\/", "/", $CWD);
$CWD = preg_replace("/\/[^\/]+$/", "", $CWD);

/******************************************/
/* DO NOT CHANGE ANYTHING BELOW THIS LINE */
/* DO NOT CHANGE ANYTHING BELOW THIS LINE */
/* DO NOT CHANGE ANYTHING BELOW THIS LINE */
/******************************************/

if (is_file("${CWD}/../_platform.php")) {
	include("${CWD}/../_platform.php");
} else {
	die("Error creating databases. Cannot find _platform.php");
}

ini_set("max_execution_time", 0);

$argv = $_SERVER["argv"];
for($i = 1; $i < count($argv); ++$i) {
	switch ($argv[$i]) {
	case "-t":
	case "--tmpl":
	case "--tmpl-code":
	case "--tmplcode":
		$TMPL_CODE = $argv[++$i];
		break;
	case "-c":
	case "--comp":
	case "--comp-code":
	case "--compcode":
		$COMP_CODE = $argv[++$i];
		break;
	case "-o":
	case "--only":
	case "--tmpl-only":
	case "--tmplonly":
		define("TMPL_ONLY", true);
		break;
	case "-d":
	case "--drop":
	case "--drop-dbs":
	case "--dropdbs":
		define("DROP_DBS", true);
		break;
	case "-q":
	case "--quick":
	case "--optimize":
		if (is_numeric($argv[$i + 1])) {
			$q = $argv[++$i];
		} else {
			$q = 25;
		}
		define("OPTIMIZE", $q);
		break;
	case "-h":
	case "--help":
		showhelp();
	}
}

if (!defined("TMPL_ONLY")) {
	define("TMPL_ONLY", false);
}

if (!defined("OPTIMIZE")) {
	define("OPTIMIZE", -1);
}

if (!defined("DROP_DBS")) {
	define("DROP_DBS", false);
}

if (TMPL_ONLY) {
	status("Only creating template database with code: $TMPL_CODE...\n");
} else {
	status("Template code: $TMPL_CODE\nCompany code: $COMP_CODE\n");
}

if (OPTIMIZE > 1) {
	status("Optimized: Executing ".OPTIMIZE." queries at a time.\n");
}

status("\n");

// read the schemas as calculate the steps
$schemas = file("${CWD}/schema_list");
$status_steps_count = 4 + count($schemas);
$status_steps = 0;

/* START CUBIT database */
if (!TMPL_ONLY) {
	status("Creating main Cubit database... ");
	
	$db_con = pg_connect("user=postgres password=i56kfm dbname=template1") or pgdie();
	
	/* create plpgsql language :> */
	@pg_exec("CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler AS '\$libdir/plpgsql' LANGUAGE C;");
	@pg_exec("CREATE FUNCTION plpgsql_validator(oid) RETURNS void AS '\$libdir/plpgsql' LANGUAGE C;");
	@pg_exec("CREATE TRUSTED PROCEDURAL LANGUAGE plpgsql HANDLER plpgsql_call_handler VALIDATOR plpgsql_validator;");

	if (DROP_DBS) {
		status("(dropping existing database) ");
		@pg_exec("DROP DATABASE cubit");
	}

	$rslt = @pg_exec('CREATE DATABASE "cubit" WITH template=template0');

	pg_close($db_con);

	$db_con = pg_connect('user=postgres password=i56kfm dbname=cubit') or pgdie();
	
	if ($rslt !== false) {
		pg_exec("CREATE TABLE errordumps (id serial, errtime timestamp, company varchar, userid integer, errdata text);");
		pg_exec('CREATE TABLE globalset (id serial NOT NULL, name varchar, value varchar);') or pgdie();
		pg_exec('CREATE TABLE companies ("compid" serial NOT NULL,"code" varchar,"name" varchar, "ver" varchar, "status" varchar)') or pgdie();
		@pg_exec("CREATE TABLE version(ver varchar)");
		@pg_exec("INSERT INTO version values('" . CUBIT_VERSION . "')");
		@pg_exec('CREATE TABLE psql_location ("id" serial NOT NULL,"locat" varchar)') or pgdie();
		@pg_exec('CREATE TABLE comp_modules (code varchar, module varchar, version varchar);') or pgdie();
		@pg_exec("CREATE TABLE ch (
	        		id serial,
			        comp varchar,
        			code varchar,
	        		des varchar,
		        	f varchar,
			        t varchar,
        			date date
	        )");
		@pg_exec("CREATE TABLE uselog (
				id serial,
				name varchar, 
				strval varchar, 
				timestampval timestamp DEFAULT CURRENT_TIMESTAMP, 
				dateval date DEFAULT CURRENT_DATE, 
				timeval time DEFAULT CURRENT_TIME
		)");
	} else {
		pg_exec("INSERT INTO companies (code, name, ver, status) 
			VALUES('$COMP_CODE', '$COMP_CODE', '".CUBIT_VERSION."', 'active')");
	}

	pg_close($db_con);

	status("done. %s\n\n", true);
}
/* END CUBIT database */

/* START COMPANY TEMPLATE database */
status("Creating template company database... ");

$db_con = pg_connect('user=postgres password=i56kfm dbname=template1') or pgdie();
if (DROP_DBS) {
	status("(dropping existing database) ");
	@pg_exec("DROP DATABASE cubit_$TMPL_CODE");
}
pg_exec("CREATE DATABASE cubit_$TMPL_CODE WITH template=template0") or errDie("ERR: create db $TMPL_CODE");
pg_close($db_con);

// connect to the blank database to start creating the schemas
$db_con = pg_connect('user=postgres password=i56kfm dbname=cubit_'.$TMPL_CODE) or pgdie();

        /* create plpgsql language :> */
        @pg_exec("CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler AS '\$libdir/plpgsql' LANGUAGE C;");
        @pg_exec("CREATE FUNCTION plpgsql_validator(oid) RETURNS void AS '\$libdir/plpgsql' LANGUAGE C;");
        @pg_exec("CREATE TRUSTED PROCEDURAL LANGUAGE plpgsql HANDLER plpgsql_call_handler VALIDATOR plpgsql_validator;");

status("done. %s\n", true);

// create the schemas
foreach ( $schemas as $sl ) {
	list($schema_name, $action) = explode(" ", trim($sl));

	// determine which file to read for the schema creation
	// format is [db] [action] :: action = {$dbname, dump}
	// if action == dump, then db is the file
	// if action == $dbname, then action is the file
	// action == $dbname mean db is exactly the same as $dbname
	$dbfile = ($action == "dump" || $action == "special")?$schema_name:$action;

	if ($action == "special") {
		$dbfile .= "_special";
	} else {
		status("Creating schema \"$schema_name\"... ");
		pg_exec("CREATE SCHEMA \"$schema_name\"");
	}

	pg_exec("SET search_path='$schema_name'");

	$fd = fopen("${CWD}/sql/$dbfile.sql", "r");

	$lcount = 0;
	$query_count = 0;
	while ( ! feof($fd) ) {
		$line = "";
		$pc = "";
		// read all the characters into line until end of query
		while (($c = fgetc($fd)) !== false) {
			if ( $c == "\r" ) continue;
			if ( $c == "\n" ) {
				// line is finished, blank or comment, break
				if ( strlen(trim($line)) < 1 || preg_match("/^--/", trim($line)) || $pc == ";" ) {
					if ($query_count < OPTIMIZE) {
						++$query_count;
					} else {
						$query_count = 0;
						break;
					}
				}
			}
			$line .= $c;
			if ( $c != " " && $c != "\n" ) $pc = $c;
		}
		$line = trim($line);
		++$lcount;

		if ( empty($line) || preg_match("/^--/", $line) ) continue;
		@pg_exec($line);
	}

	fclose($fd);

	status("done. %s\n", true);
}
	
status("Finalizing template database... ");
// create the special sequences
@pg_exec("SET SEARCH_PATH='core'");
@pg_exec("CREATE SEQUENCE balance_seq INCREMENT 5 MINVALUE 5 START 5") or pgdie();
@pg_exec("CREATE SEQUENCE income_seq INCREMENT 5 MINVALUE 5 START 5") or pgdie();
@pg_exec("CREATE SEQUENCE expenditure_seq INCREMENT 5 MINVALUE 5 START 5") or pgdie();

// FIX : move sequences up once
@pg_exec("SELECT nextval('balance_seq')") or pgdie();
@pg_exec("SELECT nextval('income_seq')") or pgdie();
@pg_exec("SELECT nextval('expenditure_seq')") or pgdie();

@pg_exec("CREATE SEQUENCE core.invoicesids_seq INCREMENT 1 MINVALUE 1 START 1") or pgdie();
@pg_exec("ALTER TABLE cubit.nons_invoices ALTER COLUMN invid SET DEFAULT nextval('core.invoicesids_seq'::regclass)");
@pg_exec("ALTER TABLE cubit.invoices ALTER COLUMN invid SET DEFAULT nextval('core.invoicesids_seq'::regclass)");
@pg_exec("ALTER TABLE cubit.pinvoices ALTER COLUMN invid SET DEFAULT nextval('core.invoicesids_seq'::regclass)");
fix_seqdeps("cubit", "nons_invoices", "invid");
fix_seqdeps("cubit", "invoices", "invid");
fix_seqdeps("cubit", "pinvoices", "invid");

@pg_exec("CREATE SEQUENCE core.purchasesids_seq INCREMENT 1 MINVALUE 1 START 1") or pgdie();
@pg_exec("ALTER TABLE cubit.nons_purchases ALTER COLUMN purid SET DEFAULT nextval('core.purchasesids_seq'::regclass)") or pgdie();
@pg_exec("ALTER TABLE cubit.purchases ALTER COLUMN purid SET DEFAULT nextval('core.purchasesids_seq'::regclass)") or pgdie();
fix_seqdeps("cubit", "nons_purchases", "purid");
fix_seqdeps("cubit", "purchases", "purid");

@pg_exec("SET SEARCH_PATH='cubit'");
@pg_exec("CREATE SEQUENCE payslip_ids_seq INCREMENT 1 MINVALUE 1 START 1") or pgdie();
@pg_exec("ALTER TABLE cubit.salpaid ALTER COLUMN true_ids SET DEFAULT nextval('cubit.payslip_ids_seq'::regclass)");
@pg_exec("ALTER TABLE cubit.salr ALTER COLUMN true_ids SET DEFAULT nextval('cubit.payslip_ids_seq'::regclass)");

@pg_exec("SELECT nextval('paye_id_seq')") or pgdie();

@pg_exec ("CREATE FUNCTION check_allocation() RETURNS trigger AS 'BEGIN UPDATE cubit.stmnt SET allocation_balance=abs(amount) where allocation_processed=''0'' AND allocation_balance = ''0'';RETURN NEW;END;' LANGUAGE plpgsql;");
@pg_exec ("CREATE TRIGGER stmnt_trig AFTER INSERT ON cubit.stmnt FOR EACH STATEMENT EXECUTE PROCEDURE check_allocation();");

@pg_exec ("CREATE FUNCTION check_sup_allocation() RETURNS trigger AS 'BEGIN UPDATE cubit.sup_stmnt SET allocation_balance=abs(amount) where allocation_processed=''0'' AND allocation_balance = ''0'';RETURN NEW;END;' LANGUAGE plpgsql;");
@pg_exec ("CREATE TRIGGER sup_stmnt_trig AFTER INSERT ON cubit.sup_stmnt FOR EACH STATEMENT EXECUTE PROCEDURE check_sup_allocation();");

status("done. %s\n\n", true);

pg_close($db_con);
/* END COMPANY TEMPLATE database */

sleep(5);

/* START FIRST COMPANY database */
if (!TMPL_ONLY) {
	status("Create first company... ");

	$db_con = pg_connect('user=postgres password=i56kfm dbname=template1') or pgdie();
	if (DROP_DBS) {
		status("(dropping existing database) ");
		@pg_exec("DROP DATABASE cubit_$COMP_CODE");
	}
	@pg_exec("CREATE DATABASE cubit_$COMP_CODE WITH template=cubit_$TMPL_CODE") or errDie("ERR: create db $TMPL_CODE");
	pg_close($db_con);

	status("done. %s\n", true);
}

/* END FIRST COMPANY database */

/* FUNCTIONS */
function status($msg, $donestep = false) {
	global $status_steps_count, $status_steps;

	if ( $donestep == true ) {
		++$status_steps;
		$perc = round($status_steps / $status_steps_count * 100);
		$msg = str_replace("%s", "($perc% finished)", $msg);
	}
	
	print $msg;
}

/* shows usage information */
function showhelp() {
	$self = $_SERVER["argv"][0];
	
	print "Usage: php -f $self [-- [options]]\n";
	print "Options:\n";
	print "\tSet the code to be used in naming the template database.\n";
	print "\t\t-t <code>\n";
	print "\t\t--tmpl <code>\n";
	print "\t\t--tmpl-code <code>\n";
	print "\t\t--tmplcode <code>\n";
	print "\n";
	print "\tSet the code to be used in naming the precreated company database.\n";
	print "\t\t-c <code>\n";
	print "\t\t--comp <code>\n";
	print "\t\t--comp-code <code>\n";
	print "\t\t--compcode <code>\n";
	print "\n";
	print "\tOnly create the template company.\n";
	print "\t\t-o\n";
	print "\t\t--only\n";
	print "\t\t--tmpl-only\n";
	print "\t\t--tmplonly\n";
	print "\n";
	print "\tTry and drop a database before creating it.\n";
	print "\t\t-d\n";
	print "\t\t--drop\n";
	print "\t\t--drop-dbs\n";
	print "\t\t--dropdbs\n";
	print "\n";
	print "\tOptimize the creation of the database by executing multiple queries at once.\n";
	print "\tThis might cause the building of the database to not complete successfully.\n";
	print "\tIf you experience this, either reduce the counter or disable this completely.\n";
	print "\t[count] is the optional number of queries to execute simultaneously (Dflt: 25).\n";
	print "\t\t-q [count]\n";
	print "\t\t--quick [count]\n";
	print "\t\t--optimize [count]\n";
	print "\n";
	print "\tPrint this help message.\n";
	print "\t\t-h\n";
	print "\t\t--help\n";
	print "\n";

	exit(1);
}

// exits app and prints last pg error message
function pgdie() {
	$fd = fopen("php://stderr", "w+");

	fprintf($fd, "Query Failed: ".pg_last_error()."\n");

	fclose($fd);
	exit(1);
}

function fix_seqdeps($schema, $table, $colname) {
	$sql = "
	  DELETE FROM pg_depend WHERE 
	  refobjid=(
	    SELECT c.oid 
		FROM pg_class c, pg_attribute a LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum, 
			pg_type t, pg_namespace nc 
		WHERE t.oid=a.atttypid AND c.oid=a.attrelid AND nc.oid=c.relnamespace
			AND nc.nspname='$schema'
			AND c.relname='$table'
			AND a.attname='$colname'
	  ) AND
	  refobjsubid=(
	    SELECT a.attnum 
		FROM pg_class c, pg_attribute a LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum, 
			pg_type t, pg_namespace nc 
		WHERE t.oid=a.atttypid AND c.oid=a.attrelid AND nc.oid=c.relnamespace 
			AND nc.nspname='$schema'
			AND c.relname='$table'
			AND a.attname='$colname'
	  ) AND 
	  objid=(
	    SELECT c.oid 
		FROM pg_class c, pg_attribute a LEFT JOIN pg_attrdef ad ON a.attrelid = ad.adrelid AND a.attnum = ad.adnum, 
			pg_type t, pg_namespace nc 
		WHERE t.oid=a.atttypid AND c.oid=a.attrelid AND nc.oid=c.relnamespace
			AND nc.nspname='$schema'
			AND c.relname=('$table' || '_' || '$colname' || '_seq') LIMIT 1
	  );";

	@pg_exec($sql);
}
?>

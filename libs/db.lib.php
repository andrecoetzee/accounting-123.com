<?
/**
 * Generally used functions/constants related to date/time
 *
 * @package Cubit
 * @subpackage Database
 */
if (!defined("DB_LIB")) {
	define("DB_LIB", true);

/* EXAMPLE SELECTS
// example1:
// doing the query all in construction
$invoice = new dbSelect("invoices", "cubit", wgrp(m("where", "invid='$invid'")));
$invoice->run();

// example2:
// doing the query with internal functions, setting schema at construction
$invoice = new dbSelect(false, "cubit");
$invoice->setTable("invoices");
$invoice->setOpt(wgrp(m("where", "invid='$invid'")));
$invoice->run();

// example3:
// doing the query, everything with functions
$invoice = new dbSelect();
$invoice->setTable("invoices", "cubit");
$invoice->setOpt(wgrp(m("where", "invid='$invid'")));
$invoice->run();

// now, you can reset all conditionals/orders/limits/etc.. (table/schema kept intact)
$invoice->reset();
$invoice->setOpt(wgrp(m("where", "invid='$inv2'")));
$invoice->run();

$invdata = $invoice->fetch_array();

// after invoice data is read, lets get same invoices items
// we already have the where invid='$inv2', and the inv_items table has a column
// invid which tells which invoice it belongs to, so let's just change the table
// and leave the conditionals intact
$invoice->setTable("inv_items");
$invoice->run();

// reading the data
while ($row = $invoice->fetch_array()) {
	print "item: $row[description]<br>";
}

//	---	OR --- //

while ($invoice->fetch_array()) {
	print "item: ".$invoice->d["description"]."<br>";
}
*/

/* EXAMPLE DELETES
// deletes have same construction methods as above, only diff is
// you only specify a WHERE conditional as parameter and run
$invoice = new dbDelete();
$invoice->setTable("invoices", "cubit");
$invoice->setOpt("invid='$invid'");
$invoice->run();

$invoice = new dbDelete("invoices", "cubit", "invid='$invid'");
$invoice->run();
*/

/* EXAMPLE UPDATE
// inserts change a bit from above, table/schema selection is the same
// but a special function must be called to create the column-value matches

// example 1: standard insert
$cols = grp(
	m("name", "Special Client Services Pty. Ltd."),
	m("balance", "5000.99"),
	m("delivery_date", "2005-05-06")
);
$customers = new dbUpdate("customers", "cubit", $cols);
$customers->run(DB_INSERT);

// example 2: standard update
// $cols same as above
$customers = new dbUpdate("customers", "cubit", $cols, "id='4'");
$customers->run(DB_UPDATE);

// example 3: different ways to specify $cols/$where
// simple changes it
$customers->setOpt($cols, "id='5'");
// leaves $cols unchanged, and changes $where
$customers->setOpt(false, "id='6'");
// leaves $where unchanged
$customers->setOpt($cols);
// using a grp() to specify multiple $where
$wh = grp(m("delivery_date", "2005-07-01"),m("name", "Crow Inc."));
$customers->setOpt(false, $wh);
// changing a single/more column value
$customers->setCols(m("name", "Piet Co."));
$cols = grp(
	m("name", "Piet Co."),
	m("tel", "012 765 2232")
);
$customers->setCols($cols);

// example 4: the magic replace into (emulated for postgresql)
$cols = grp(
	m("name", "Do We Exist"),
	m("balance", "1023437),
	m("delivery_date", "2006-07-01")
);
$customers = new dbUpdate("customers", "cubit", $cols, wgrp(m("name", "Do We Exist")));

// this record doesn't exist, so it gets inserted
$customers->run(DB_REPLACE);

// now lets change a column
$customers->setCols(m("balance", "1026000"));

// according to the where condition, this record exists, so it will get updated to
// the current columns
$customers->run(DB_REPLACE);

// NEAT HUH?1?!

// COMPLEX WHERE CLAUSES, the wgrp() function
// use this function to make WHERE clauses look a bit more collected, in all other
// cases just use a string (it's easier, got a suggestion for building them?
// then go and tell Quintin, he'll buffer it.
this function takes as parameters either the constants DB_AND/DB_OR
to select how the next part should be matches. if no operator is specified
it defaults to DB_AND
it can also have itself as a parameter to create a "sub" where condition.
finally it can have a match string as a parameter. example usage:
$w = wgrp(
	wgrp(
		wgrp(
			"fname='John',
			"salary<='10000'"
		),
		DB_OR,
		wgrp(
			"fname='Jane',
			"salary<='8000'"
	),
	DB_AND,
	"lname='Doe'"
);

This creates a where condition, similiar to this:
((fname='John' AND salary<='10000') OR (fname='Jane' AND salary<='8000')) AND lname='Doe'
*/

/**
 * Used by dbQuery constructor as first parameter to hardcode sql query
 *
 */
define("DB_SQL", -65533);

/**
 * used as schema to specify the MAIN cubit database
 */
define("DB_MCUBIT", -65534);

/**
 * Parent class with basics of accessing tables, storing sql, etc...
 *
 * Connection to Cubit has to happen externally.
 *
 * @example db.lib.php
 * @todo Functionality for connecting to a database or using existing connection.
 *
 */
class dbQuery {
	/**
	 * schema in which table lies. will be "public" be default
	 *
	 * @var string
	 */
	public $schema = false;

	/**
	 * table which is being queried.
	 *
	 * @var string
	 */
	public $table = false;

	/**
	 * sql query to be executed. updated by inheriting classes
	 *
	 * @var string
	 */
	public $sql = false;

	/**
	 * sql result resource returned by db execute function
	 *
	 * @var int
	 */
	public $rslt;

	/**
	 * constructor (2 ways to use)
	 *
	 * 1. You can pass DB_SQL as first parameter and make your second parameter
	 * be an sql query.
	 *
	 * 2. you can set the table and schema here already. if you pass false as
	 * the value of $schema it will set to default value of "public".
	 *
	 * @param mixed table/DB_SQL
	 * @param string schema/SQL
	 */
	function __construct() {
		$args = func_get_args();

		if (count($args) >= 2 && $args[0] == DB_SQL) {
			$this->sql = $args[1];
		} else {
			if (!isset($args[0])) {
				$args[0] = false;
			}

			if (!isset($args[1])) {
				$args[1] = "public";
			}

			$this->setTable($args[0], $args[1]);
		}
	}

	/**
	 * destructor
	 *
	 */
	function __destruct() {
		$this->free();
	}

	/**
	 * cleanup. free Cubit result resources.
	 *
	 */
	function free() {
		if ($this->rslt) {
			pg_free_result($this->rslt);
		}
		$this->rslt = false;
	}

	/**
	 * sets the table/schema
	 *
	 * schema will only be set if a value is passed. this way you can change
	 * the table and leave the schema the same giving the effect of changing
	 * tables within a schema.
	 *
	 * returns:
	 * whether enough information is available to continue building the query
	 * using: dbQuery::makeSql()
	 *
	 * @see makeSql()
	 * @param string $table
	 * @param string $schema
	 * @return bool
	 */
	function setTable($table, $schema = false) {
		if ($schema) {
			$this->schema = $schema;
		}
		$this->table = $table;

		return self::makeSql();
	}

	/**
	 * determines if class has sufficient information to continue query building
	 *
	 * determines whether enough information is available to continue building
	 * the query. must be overided when inherited, and also used in overidden
	 * function.
	 *
	 * @return unknown
	 */
	function makeSql() {
		if (!$this->table) {
			return false;
		}

		return true;
	}

	/**
	 * sets (custom) sql query.
	 *
	 * @param string $sql
	 */
	function setSql($sql) {
		$this->sql = $sql;
	}

	/**
	 * returns schema.table
	 *
	 * @return string
	 */
	function getTable() {
		if (!$this->table) {
			return false;
		}

		if ($this->schema == DB_MCUBIT) {
			return "$this->table";
		} else {
			return "\"$this->schema\".$this->table";
		}
	}

	/**
	 * execute query and return result resource.
	 *
	 * first checks if sql is stored using dbQuery::sql
	 * then if a result exists (using dbQuery::rslt) and frees it using
	 * dbQuery::free()
	 *
	 * @see $sql
	 * @see $rslt
	 * @see free()
	 * @return unknown
	 */
	function run() {
		if (!$this->sql) {
			return false;
		} else if ($this->rslt) {
			$this->free();
		}

		//return $this->rslt = pg_exec($this->db(), $this->sql) or errDie("SQL Failed: $this->schema.$this->table - $this->where");
		return $this->rslt = pg_exec($this->db(), $this->sql) or $this->fatal();
	}

	/**
	 * dies with fatal error (sql error)
	 */
	function fatal() {
		if (DEBUG > 0) {
			if (function_exists("_DEBUG")) {
				_DEBUG("DB Class failed: $this->sql");
			} else {
				errDie("DB Class failed: $this->sql");
			}
		}

		errDie("SQL Failed: $this->schema.$this->table - $this->where");
	}

	/**
	 * returns which db we are executing on
	 *
	 * specifying a schema with the DB_MCUBIT function will change from to cubit database
	 *
	 * @return int postgres connection resource
	 */
	 function db() {
	 	global $ALINK;
	 	if ($this->schema == DB_MCUBIT) {
			return DB_CUBIT_MAIN;
		} else {
			return $ALINK;
		}
	 }

	/**
	 * returns the last id from current table sequence in form "tablename"_"colname"_seq
	 *
	 * @param string $colname
	 * @return int
	 */
	function lastid($colname) {
		return pglib_lastid($this->getTable(), $colname);
	}

	/**
	 * returns the affected rows (INSERT/UPDATE/DELETE/REPLACE)
	 *
	 * @return int
	 */
	function affected() {
		if (!$this->rslt) return false;
		return (int)pg_affected_rows($this->rslt);
	}

	/**
	 * returns the next row in result (SELECT)
	 *
	 * the keys of the array returned will match the columns in the table queried
	 *
	 * @return array
	 */
	function fetch_array() {
		if (!$this->rslt) return false;
		return $this->d = pg_fetch_assoc($this->rslt);
	}

	/**
	 * fetches the specified row and column from result (SELECT)
	 *
	 * if no column is specified $row will be used as $col and the next
	 * row in result will be return everytime
	 *
	 * @param int $row
	 * @param int $col
	 * @return mixed
	 */
	function fetch_result($row = 0, $col = false) {
		if (!$this->rslt) return false;
		if ($col === false) {
			/* return *next row with column == $row */
			return $this->d = pg_fetch_result($this->rslt, $row);
		} else {
			/* return row == $row and column == $col */
			return $this->d = pg_fetch_result($this->rslt, $row, $col);
		}
	}

	/**
	 * fetches the next available row (SELECT)
	 *
	 * the keys of the array returned will be numeric and match the positions
	 * of the columns in the table queried
	 *
	 * @return array
	 */
	function fetch_row() {
		if (!$this->rslt) return false;
		return $this->d = pg_fetch_row($this->rslt);
	}

	/**
	 * number of rows returned in query (SELECT)
	 *
	 * @return int
	 */
	function num_rows() {
		if (!$this->rslt) return false;
		return (int)pg_num_rows($this->rslt);
	}
}

/**
 * Class that accepts raw sql
 *
 * Connection to Cubit has to happen externally.
 *
 * @example db.lib.php
 *
 */
class dbSql extends dbQuery {
	/**
	 * constructor
	 *
	 * @param string $sql
	 */
	function __construct($sql = "") {
		parent::__construct(DB_SQL, $sql);
	}

	/**
	 * destructor
	 *
	 */
	function __destruct() {
		parent::__destruct();
	}
}

/**
 * Handles select queries. Extends parent class dbQuery
 *
 * @see dbQuery
 * @example db.lib.php
 */
class dbSelect extends dbQuery {
	public $cols = "*";
	public $where = "(true)";
	public $order = false;
	public $group = false;
	public $offset = "0";
	public $limit = "all";

	public $d;

	/* constructor, if you pass false as $schema it will set to default again */
	function __construct($table = false, $schema = false, $ar = array()) {
		if (!$schema) {
			$schema = "public";
		}

		parent::__construct($table, $schema);
		$this->setOpt($ar);
	}

	/**
	 * destructor
	 *
	 */
	function __destruct() {
		parent::__destruct();
	}

	/* cleanup: reset */
	function reset() {
		$this->free();

		$this->cols = "*";
		$this->where = "(true)";
		$this->order = false;
		$this->offset = "0";
		$this->limit = "all";
	}

	/* builds query */
	function makeSql() {
		if (!parent::makeSql()) {
			return false;
		}

		if ($this->order) {
			$order = "ORDER BY $this->order";
		} else {
			$order = "";
		}
		
		if ($this->group) {
			$group = "GROUP BY $this->group";
		} else {
			$group = "";
		}

		$this->sql =
			"SELECT $this->cols ".
			"FROM ".$this->getTable()." ".
			"WHERE $this->where ".
			"$group ".
			"$order ".
			"OFFSET $this->offset ".
			"LIMIT $this->limit";

		return true;
	}

	/**
	 * sets the table/schema
	 *
	 * overwrite of dbQuery::setTable since dbQuery only runs self::makeSql()
	 *
	 * @see makeSql()
	 * @param string $table
	 * @param string $schema
	 * @return bool
	 */
	function setTable($table, $schema = false) {
		if ($schema) {
			$this->schema = $schema;
		}
		$this->table = $table;

		return self::makeSql();
	}

	/* sets sql parts using array where key = part and value follows */
	function setOpt($ar = array()) {
		foreach ($ar as $k => $o) {
			$k = strtolower($k);
			$this->$k = $o;
		}

		return $this->makeSql();
	}

	/****************************/
	/* return results functions */
	/****************************/
}

/**
 * Handles delete queries. Extends parent class dbQuery
 *
 * @see dbQuery
 * @example db.lib.php
 */
class dbDelete extends dbQuery {
	public $where = "(true)";

	/* constructor, if you pass false as $schema it will set to default again */
	function __construct($table = false, $schema = false, $where = "(true)") {
		if (!$schema) {
			$schema = "public";
		}

		parent::__construct($table, $schema);
		$this->setOpt($where);
	}

	/**
	 * destructor
	 *
	 */
	function __destruct() {
		parent::__destruct();
	}

	/* cleanup */
	function reset() {
		$this->free();

		$this->where = "(true)";
	}

	/* builds query */
	function makeSql() {
		if (!parent::makeSql()) {
			return false;
		}

		$this->sql =
			"DELETE FROM ".$this->getTable()." ".
			"WHERE $this->where ";

		return true;
	}
	
	/**
	 * sets the table/schema
	 *
	 * overwrite of dbQuery::setTable since dbQuery only runs self::makeSql()
	 *
	 * @see makeSql()
	 * @param string $table
	 * @param string $schema
	 * @return bool
	 */
	function setTable($table, $schema = false) {
		if ($schema) {
			$this->schema = $schema;
		}
		$this->table = $table;

		return self::makeSql();
	}

	/* sets sql parts using array where key = part and value follows */
	function setOpt($where = "(true)") {
		$this->where = $where;

		return $this->makeSql();
	}
}

/**
 * Constant: Used by dbUpdate::run() to create INSERT query
 *
 * @see dbUpdate::run()
 */
define("DB_INSERT", 1);

/**
 * Constant: Used by dbUpdate::run() to create UPDATE query
 *
 * @see dbUpdate::run()
 */
define("DB_UPDATE", 2);

/**
 * Constant: Used by dbUpdate::run() to create REPLACE query
 *
 * Replace query will UPDATE if row does exist and INSERT if it doesn't
 *
 * @see dbUpdate::run()
 */
define("DB_REPLACE", 3);

/**
 * Handles select queries. Extends parent class dbQuery
 *
 * @see dbQuery
 * @example db.lib.php
 */
class dbUpdate extends dbQuery {
	public $cols = array();
	public $where = "(true)";

	public $qtype = DB_INSERT;

	/* constructor, if you pass false as $schema it will set to default again */
	function __construct($table = false, $schema = false, $cols = false, $where = "(true)") {
		if (!$schema) {
			$schema = "public";
		}

		if (!$cols) {
			$cols = array();
		}

		parent::__construct($table, $schema);
		$this->setOpt($cols, $where);
	}

	/**
	 * destructor
	 *
	 */
	function __destruct() {
		parent::__destruct();
	}

	/* cleanup */
	function reset() {
		$this->free();

		$this->cols = array();
	}

	/* set options */
	function setOpt($cols = false, $where = false) {
		$this->setCols($cols);

		if ($where) {
			$this->where = $where;
		}
	}

	/* change cols */
	function setCols($c) {
		if (!$c || !is_array($c)) {
			return false;
		}

		$this->cols = $c;
	}

	/* builds a where condition */
	function makeWhere() {
		if (is_string($this->where)) {
			return $this->where;
		}

		if (is_array($this->where)) {
			$cols = array();
			foreach ($this->safeData(true) as $f => $v) {
				if (preg_match("/^\+NOESC\+/", $f)) {
					$col = preg_replace("/^\+NOESC\+/", "", $f);
				} else {
					$col = "\"$f\"";
				}

				if (is_array($v) && $v[0] == "NOESC") {
					$val = $v[1];
				} else {
					$val = "'$v'";
				}

				$cols[] = "$col=$val";
			}
			return implode(" AND ", $cols);
		}

		return "(false)";
	}

	/* returns $cols with each element sql safe escaped */
	function safeData($where = false) {
		if ($where) {
			$c = $this->where;
		} else {
			$c = $this->cols;
		}

		foreach ($c as $key => $value) {
			if (is_array($value)) continue;
			$c[$key] = preg_replace("/['\\\\]/", "\\\\\\0", $value);
		}
		return $c;
	}

	/* INSERT FUNC: uses the $cols var to make the target columns */
	function makeCols() {
		$cols = array();
		foreach (array_keys($this->safeData()) as $k => $v) {
			if (preg_match("/^\+NOESC\+/", $v)) {
				$cols[] = preg_replace("/^\+NOESC\+/", "", $v);
			} else {
				$cols[] = "\"$v\"";
			}
		}
		return implode(",", $cols);
	}

	/* INSERT FUNC: uses $cols var to make column values */
	function makeVals() {
		$vals = array();
		foreach ($this->safeData() as $k => $v) {
			if (is_array($v) && $v[0] == "NOESC") {
				$vals[] = "$v[1]";
			} else {
				$vals[] = "'$v'";
			}
		}
		return implode(",", $vals);
	}

	/* UPDATE FUNC: uses $cols to make updates in form: "col"='val' */
	function makeUpdates() {
		$cols = array();

		foreach ($this->safeData() as $f => $v) {
			if (preg_match("/^\+NOESC\+/", $f)) {
				$col = preg_replace("/^\+NOESC\+/", "", $f);
			} else {
				$col = "\"$f\"";
			}

			if (is_array($v) && $v[0] == "NOESC") {
				$val = $v[1];
			} else {
				$val = "'$v'";
			}

			$cols[] = "$col=$val";
		}
		return implode(",", $cols);
	}

	/* builds query */
	function makeSql() {
		if (!parent::makeSql()) {
			return false;
		}

		if (count($this->cols) <= 0) {
			return false;
		}

		$tmp_qtype = $this->qtype;

		if ($tmp_qtype == DB_REPLACE) {
			$sql = "SELECT 1 FROM ".$this->getTable()."
					WHERE ".$this->makeWhere()." LIMIT 1";
			$rslt = pg_exec($this->db(), $sql) or errDie("Error REPLACE INTO: ".$this->getTable());

			if (pg_num_rows($rslt) > 0) {
				$tmp_qtype = DB_UPDATE;
			} else {
				$tmp_qtype = DB_INSERT;
			}

			pg_free_result($rslt);
		}

		if ($tmp_qtype == DB_INSERT) {
			$this->sql =
				"INSERT INTO ".$this->getTable().
				"(".$this->makeCols().") ".
				"VALUES(".$this->makeVals().") ";
		} else if ($tmp_qtype == DB_UPDATE) {
			$this->sql =
				"UPDATE ".$this->getTable()." ".
				"SET ".$this->makeUpdates()." ".
				"WHERE ".$this->makeWhere();
		}

		return true;
	}

	/* sets the query type (INSERT/UPDATE/REPLACE) */
	function setQtype($qtype) {
		if ($qtype == DB_INSERT || $qtype == DB_UPDATE || $qtype == DB_REPLACE) {
			$this->qtype = $qtype;
			return true;
		} else {
			return false;
		}
	}

	/* we overwrite run, because we can run multiples */
	function run($qtype = false) {
		if ($qtype) {
			if (!$this->setQtype($qtype)) {
				return false;
			}
		}

		if (!$this->makeSql()) {
			return false;
		}

		return parent::run();
	}

	/**
	 * returns the last id sequence value for selected column in current table
	 *
	 * @param string $col column name
	 * @return int
	 */
	function lastvalue($col = "id") {
		$qry = new dbSql("SELECT last_value FROM ".$this->getTable()."_${col}_seq");
		$qry->run();
		$ret = $qry->fetch_result();
		$qry->free();

		return $ret;
	}
}

/**
 * is a dbSelect object that simply creates a dropdown. Extends parent class dbSelect.
 *
 * @see dbQuery
 */
class dbList extends dbSelect {
	/**
	 * stores the key format
	 *
	 * @var string
	 */
	public $key;

	/**
	 * stores the display format
	 *
	 * @var string
	 */
	public $disp;

	/**
	 * grouping column
	 *
	 * @var string
	 */
	public $grpcol;

	/**
	 * group labels
	 *
	 * @var array
	 */
	public $grplabels;

	/**
	 * any opt
	 *
	 * @var array
	 */
	public $anyopt;

	/**
	 * constructor
	 * 
	 * if $table is a dbSelect object, it will be duplicated
	 *
	 * @see db_mksel()
	 * @param dbSelect/string $table
	 * @param string $schema
	 * @param array $ar dbSelect options array
	 * @param string $key specifies key columns similiar to db_mksel()
	 * @param string $disp specifies display columns similiar to db_mksel()
	 */
	function __construct($table = false, $schema = false, $ar = array(),
			$key = false, $disp = false) {
				
		if (is_object($table)) {
			$obj = $table;
			
			/* @var $obj dbSelect */			
			$table = $obj->table;
			$schema = $obj->schema;
			
			$ar = array(
				"cols" => $obj->cols,
				"where" => $obj->where,
				"order" => $obj->order,
				"offset" => $obj->offset,
				"limit" => $obj->limit
			);
		} 
		
		/* just some integrity */
		if ($ar === false) {
			$ar = array();
		}
		parent::__construct($table, $schema, $ar);

		/* own options */
		$this->key = $key;
		$this->disp = $disp;

		$this->grpcol = false;
		$this->grplabels = false;

		$this->anyopt = array();
	}

	/**
	 * changes dbSelect options
	 *
	 * @see dbSelect::setOpt()
	 * @param array $ar dbSelect options array
	 */
	function setOpt($ar = array()) {
		parent::setOpt($ar);
	}

	/**
	 * changes key/display format
	 *
	 * @see db_mksel()
	 * @param string $key specifies key columns similiar to db_mksel()
	 * @param string $disp specifies display columns similiar to db_mksel()
	 */
	function setFmt($key, $disp) {
		$this->key = $key;
		$this->disp = $disp;
	}

	/**
	 * adds a optgroup column to the select, for grouped selects
	 *
	 * to create a grouped select, specify the column to group by as $grpcol.
	 * then all the values that column will carry should be listed together
	 * with labels in $grplabels as array of the form value=>label.
	 *
	 * NOTE: once set it cannot be undone.
	 *
	 * @param string $grpcol column to group by
	 * @param array $grplabels labels for each group item
	 */
	function setGroup($grpcol, $grplabels) {
		$x = array();
		foreach ($grplabels as $val => $disp) {
			$x[] = "WHEN \"$grpcol\"='$val' THEN '$disp'";
		}

		if ($this->order) {
			$order = ",".$this->order;
		} else {
			$order = "";
		}

		$ar = array(
			"cols" => $this->cols.", CASE ".implode(" ", $x)." END AS optgroup",
			"order" => "optgroup ASC".$order
		);

		parent::setOpt($ar);
	}

	/**
	 * adds an "any" options
	 *
	 * example Any options are: Any User, Any Customer, Anywhere.
	 *
	 * @see db_mksel
	 * @param string $key value to use as key
	 * @param string $disp value to use as display
	 */
	function anyOpt($key, $disp) {
		$this->anyopt = array($key, $disp);
	}

	/**
	 * executes the query and returns the select box
	 *
	 * @see db_mksel
	 * @param string $name name of form element
	 * @param string $sel selected item's key
	 * @param string $anyopt "Any" opt.
	 * @param string $opt extra html options for <select>.
	 * @return string
	 */
	function get($name, $sel, $opt = "") {
		if (!function_exists("db_mksel")) {
			require_lib("ext");
		}

		/* run the query */
		$this->run();

		/* create the any option */
		$ao = implode(":", $this->anyopt);

		/* make the select */
		$sel = db_mksel($this, $name, $sel, $this->key, $this->disp, $ao, $opt);

		/* free the object */
		$this->free();

		return $sel;
	}

	/**
	 * returns the display value associated with selected key
	 *
	 * @param string $forkey key to get display for
	 * @return string
	 */
	function getDisp($forkey) {
		/* an "any" element */
		if ($this->ao !== false && $this->ao != "") {
			$anyopt = explode(":", $this->ao);

			if (count($anyopt) > 1 && $anyopt[0] == $forkey) {
				return $anyopt[1];
			}
		}
		
		/* check the rows in the db against the value supplied */
		$key = preg_replace("/##/", "\xFF", $this->key); // move all the ##
		if (!preg_match_all("/#([\\d\\w_]+)/", $key, $c_key)) {
			return "<li class='err'>Invalid key format.</li>";
		}
		$key = preg_replace("/[\\xFF]/", "#", $key);
		
		$disp = preg_replace("/##/", "\xFF", $this->disp); // move all the ##
		if (!preg_match_all("/#([\\d\\w_]+)/", $disp, $c_disp)) {
			return "<li class='err'>Invalid display format.</li>";
		}
		$disp = preg_replace("/[\\xFF]/", "#", $disp);

		$c_key = $c_key[1];
		$c_disp = $c_disp[1];

		/* loop through the items */
		$this->run();
		while ($r = $this->fetch_array()) {
			/* build a key from this row */
			$o_key = $key;
			foreach ($c_key as $col) {
				$o_key = preg_replace("/#$col/", $r[$col], $o_key);
			}

			/* built key doesn't match key we are looking for */
			if ($o_key != $forkey) {
				continue;
			}
			
			/* key matches, build the display */
			$o_disp = $disp;
			foreach ($c_disp as $col) {
				$o_disp = preg_replace("/#$col/", $r[$col], $o_disp);
			}
			
			return $o_disp;
		}
			
		return "<li class='err'>Value '$key' not found in Cubit</li>";
	}
}

/**
 * groups a bunch of m() results together in a form that can be used
 * by dbUpdate class
 *
 * @param any amount of m() function return values
 * @see dbUpdate
 * @see m()
 * @return array
 */
function grp() {
	$a = array();
	foreach (func_get_args() as $k => $v) {
		if (!is_array($v)) {
			//flagError("Invalid use of <b>grp()</b>. Needs parameters created by <b>m()</b>.");
			//continue;
		}
		if (!isset($v[1])) {
			//flagError("Invalid use of <b>m()</b>. Needs two parameters, received one: <i>$v[0]</i>.");
			//continue;
		}

		if (is_array($v[0])) {
			if ($v[0][0] == "NOESC") {
				$a["+NOESC+".$v[0][1]] = $v[1];
			}
		} else {
			$a[$v[0]] = $v[1];
		}
	}
	return $a;
}

/**
 * Constant: Used by wgrp to make next condition be included with AND
 *
 * @see wgrp()
 */
define("DB_AND", "AND");

/**
 * Constant: Used by wgrp to make next condition be included with OR
 *
 * @see wgrp()
 */
define("DB_OR", "OR");

/**
 * Groups where conditions together and returns a SQL where condition.
 *
 * to be used with dbSelect, dbUpdate, dbDelete or even by your
 * own queries as it returns an SQL where condition in string form.
 *
 * @param any amount of m() function return values or custom string conditions
 * @return string
 */
function wgrp() {
	$sql = "";
	$op = false;
	$start = true;
	foreach (func_get_args() as $k => $v) {
		if ($v === false) continue;

		if (is_array($v)) {
			// nothing specified, this is bad
			if (count($v) <= 0) {
				return false;
			} else if (isset($v[0])) {
				if (!$op && !$start) {
					$sql .= " AND ";
					$op = false;
				}

				/* raw column (no quotes) */
				if (is_array($v[0]) && $v[0][0] == "NOESC") {
					$w_col = $v[0][1];
				} else {
					$w_col = "\"$v[0]\"";
				}

				/* raw values (no quotes) */
				if (is_array($v[1]) && $v[1][0] == "NOESC") {
					$w_val = $v[1][1];
				} else {
					$w_val = "'".preg_replace("/['\\\\]/", "\\\\\\0", $v[1])."'";
				}

				$sql .= "($w_col=$w_val)";

				$start = false;
			}
		} else {
			switch (strtoupper($v)) {
				// if $v is a string operator add it and prevent the "dflt" AND
				case "AND":
				case "OR":
					if ($start) {
						continue;
					}
					$sql .= " $v ";
					$op = true;
					break;
				// we got a string, add it, if no op, add "dflt" AND first
				default:
					if (!$op && !$start) {
						$sql .= " AND ";
						$op = false;
					}
					$sql .= "($v)";
					$start = false;
			}
		}
	}

	if (empty($sql)) {
		$sql = "true";
	}

	return $sql;
}

/**
 * used by classes to create matches in where conditions
 *
 * @param string $col column name
 * @param string $val column value
 * @return array
 */
function m($col, $val = "") {
	return func_get_args();
}

/**
 * when passing a value to m(), tells to not escape the value.
 *
 * this is used when querying internal database constants or functions.
 *
 * @see m()
 * @param string $val
 * @return array
 */
function raw($val) {
	return array("NOESC", $val);
}

/**
 * builds a row for composite types
 *
 * @see raw()
 * @return string
 */
function dbrow() {
	$cols = func_get_args();
	foreach ($cols as $k => $v) {
		if (is_array($v) && $v[0] == "NOESC") {
			$cols[$k] = $v[1];
		} else {
			$cols[$k] = "'". preg_replace("/['\\\\]/", "\\\\\\0", $v) . "'";
		}
	}

	return raw("ROW(".implode(",", $cols).")");
}

/*
wgrp(
	grp(
		m("fname", "<", "Quintin"), m("lname", "<", "Beukes"),
	),
	"AND",
	grp(
		m("fname", "Peter"), m("lname", "Wallenda")
	)
);*/

} /* LIB END */
?>

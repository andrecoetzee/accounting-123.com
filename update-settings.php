<?
	define ("DB_USER", "postgres");
	define ("DB_PASS", "i56kfm");
	define ("DB_DB", "cubit");

	$ALINK = 0;
	function db_conn_main ($db){
		global $ALINK;
		$ALINK = pg_connect("user=".DB_USER." password=".DB_PASS." dbname=".$db) or die ("Unable to connect to database server.\n\n");
		return $ALINK;
	}
	function db_exec ($query){
		global $ALINK;
		return pg_exec($ALINK, $query);
	}
?>
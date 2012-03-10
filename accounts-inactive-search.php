<?

require ("settings.php");

$OUTPUT = begin_search ();

require ("template.php");


function begin_search ()
{

	db_conn ('core');

	$get_accs = "";

	db_connect ();

	#cust ledger
	for ($x = 1;$x < 14; $x++){
		$get_cust = "SELECT * FROM "

	}

}

?>
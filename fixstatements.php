<?

require ("settings.php");

db_connect ();

$upd_sql = "ALTER TABLE cubit.stmnt ADD id serial";
$run_upd = db_exec($upd_sql) or errDie ("Unable to update stmnt information");

$OUTPUT = "Done";

require ("template.php");


?>
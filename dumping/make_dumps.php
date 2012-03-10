<?

if ( empty($argv[1]) ) {
	die("Usage: $argv[0] <compcode>\n");
}

$schemas = file("schema_list");

foreach ( $schemas as $val ) {
	list($schema_name, $action) = explode(" ", trim($val));
	if ( $action != "dump") continue;

	print "Dumping cubit_$argv[1] schema $schema_name to sql/$schema_name.sql\n  -->  ";
	system("cubitphp -f __dump_base.php schema '$schema_name' comp '$argv[1]' > sql/$schema_name.sql\n");
}

?>

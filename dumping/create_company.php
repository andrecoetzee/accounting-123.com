<?

$fd = @fopen("/dev/stderr", "w");

$msg = "\nNOTE: This script is no longer used. You only need to execute 'create_db.php' to initialize the Cubit databases.\n\n";

if ( $fd ) {
	fputs($fd, $msg);
	fclose($fd);
} else {
	print $msg;
}

?>

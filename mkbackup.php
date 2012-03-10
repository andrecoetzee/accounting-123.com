<?

ini_set("max_execution_time", 0);

$argv = $_SERVER["argv"];

for ($i = 0; $i < count($argv); ++$i) {
	if ($argv[$i] == "path" && isset($argv[$i + 1])) {
		$CUBROOT = $argv[$i + 1];
	}
}

if (!isset($CUBROOT)) {
	print "Error backing backup.\n\n";
	exit(1);
}

require("$CUBROOT/root/_platform.php");

if (PLATFORM != "windows") {
	print "This script currently only works in windows through the Backup option on the Start menu.\n\n";
	exit(1);
}

if (!is_dir("$CUBROOT/backups")) {
	mkdir("$CUBROOT/backups");
}

$fd = fopen("php://stdout", "w+");

fprintf($fd, "Making backup of all data. Backups are saved in $CUBROOT\\backups.");

$backupname = "backup.".date("Y-m-d.H-i-s").".sql";

exec("$CUBROOT/PostgreSQL/bin/pg_dumpall.exe -U postgres -c > $CUBROOT/backups/$backupname");

fprintf($fd, "Done making backup, saved as: $backupname.");

fclose($fd);

?>

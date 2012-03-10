<?

if (!defined("CUBIT_WD")) {
	$CDIR = dirname(__FILE__);
} else {
	$CDIR = CUBIT_WD;
}

require("$CDIR/_platform.php");

if (empty($argv[1])) {
	$argv[1] = "15";
}

$notstarted = true;

$fd = fopen("php://stdout", "w+");

fputs($fd, "{PostgreSQL is starting up...\n");
fflush($fd);

for ($i = 1; $i <= $argv[1] - 1; ++$i) {
	fputs($fd, "[".(round($i / $argv[1] * 100))."\n");
	fflush($fd);
	sleep(1);
	if (postgres_started() !== false) {
		$notstarted = false;
		$i = ($argv[1]-1);
	}
}

$i = 0;
while ($notstarted) {
	sleep(1);
	if (postgres_started() !== false) {
		$notstarted = false;
	}

	++$i;
	if ($i == 20) {
		fputs($fd, "{Error starting PostgreSQL. Try restarting your computer and reinstalling.\n");
		fflush($fd);
	}
}

fputs($fd, "[100\n");
fflush($fd);

fclose($fd);

function postgres_started() {
	if (($l = pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=template1")) !== false) {
		pg_close($l);
		return true;
	}

	return false;
}

?>

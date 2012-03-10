<?

if (!defined("CUBIT_WD")) define("CUBIT_WD", dirname($_SERVER["SCRIPT_FILENAME"]));
$CWD = CUBIT_WD;
$CWD = preg_replace("/\\\\/", "/", $CWD);

if (is_file("${CWD}/../_platform.php")) {
	include("${CWD}/../_platform.php");
} else if (is_file("../_platform.php")) {
	include("../_platform.php");
	$CWD = ".";
} else {
	die("Error starting Transheks process. Cannot find ${CWD}/../_platform.php");
}

if (PLATFORM == "windows") {
	$com = new COM("WScript.Shell");
	$com->Run('"c:/cubit/apache/php/php.exe" -f "'.$CWD.'/daemon.php"', 0, false);
} else {
	system("/usr/bin/cubitphp -f $CWD/daemon.php");
}

?>

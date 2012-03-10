<?

if (!defined("SETTINGS_PHP")) {
	session_name("CUBIT_SESSION");
	session_start();
}

define("_DEFINEROOT_PHP", true);

if (isset($_SESSION["code"])) {
	$ex = "_$_SESSION[code]";
} else {
	$ex = "";
}

/* record document root */
require_once("_platform.php");
$link = pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=cubit");

$sql = "SELECT * FROM globalset WHERE name='docroot$ex'";
$rslt = @pg_exec($link, $sql);

if (pg_num_rows($rslt) < 1) {
	$sql = "INSERT INTO globalset (name, value) VALUES('docroot$ex', '".
		preg_replace("/[\\\\]/", "/", getcwd())."')";
	$rslt = @pg_exec($link, $sql);
}

pg_close($link);

if (!defined("INDEX_XUL") && isset($_GET["p"])) {
	header("Location: $_GET[p]");
	exit;
}

?>

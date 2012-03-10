<?

require("settings.php");

if (isset($_REQUEST["r2sid"])) {
	r2s_return($_REQUEST["r2sid"]);
}
	
header("Location: main.php");
exit;

?>

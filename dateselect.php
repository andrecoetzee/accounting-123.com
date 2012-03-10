<?

/**
 * if this script was called itself with date_selection and idprefix in request
 * it is handled slightly differently
 */
if (isset($_REQUEST["date_selection"]) && isset($_REQUEST["idprefix"])) {
	require("settings.php");

	$OUTPUT = dateSelection($_REQUEST["idprefix"]);
} else {
	$OUTPUT = "<li class='err'>Invalid User of Module.</li>";
}
require("template.php");

?>

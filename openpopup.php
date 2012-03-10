<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

require ("settings.php");

if(!isset($HTTP_GET_VARS["redir"]) OR (strlen($HTTP_GET_VARS["redir"]) < 1)){
	return "Invalid use of module.";
}

$redir = $HTTP_GET_VARS["redir"];

$navlink_target = getNavLinkTarget();

$OUTPUT = "
	<script>
		function link(url) {
			if ($navlink_target == 0) {
				document.location.href = url;
			} else {
				popupSized(url, 'popup' + url, 800, 600,'');
			}
		}
		link('$redir');
	</script>";

require ("template.php");



function getNavLinkTarget()
{

	db_conn("cubit");

	$sql = "SELECT LOWER(SUBSTR(value, 1, 1)) FROM settings WHERE constant='NAVLINK_TARGET'";
	$rslt = db_exec($sql) or errDie("Error reading navigation link target.");

	if (pg_num_rows($rslt) <= 0) {
		$sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'NAVLINK_TARGET', 'Home Navigation Opens in New Window', 'Yes', 'layout', 'string', '2', '3', 0, 't'
			)";
		$rslt = db_exec($sql) or errDie("Error updating navigation link target (INS).");
		$nlt = "y";
	} else {
		$nlt = pg_fetch_result($rslt, 0, 0);
	}
	return (($nlt == "y") ? "1" : "0");

}


?>
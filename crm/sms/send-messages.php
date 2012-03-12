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
require("../../settings.php");

if ( isset($_GET["type"]) ) {
	$funccall = "type_$_GET[type]";

	$OUTPUT = $funccall();
} else {
	$OUTPUT = "<li class=err>Invalid use of module</li>";
	require("../../template.php");
}

print $OUTPUT;

function type_general() {
	global $_GET;
	extract($_GET);

	$OUTPUT = "
	<html>

	<script>
	function load_new_step(framenum, stepnum, stepmsg) {
		if ( framenum == 0 )
			step0.document.location.href='".dirname( getenv("SCRIPT_NAME") )."/read-credits.php';
		else if ( framenum == 1 )
			step1.document.location.href='".dirname( getenv("SCRIPT_NAME") )."/generalmsg-loadstep.php?step=' + stepnum + '&msg=' + stepmsg;
	}
	</script>

	<frameset rows='*,40' border=0>
		<frame name='step1' src='generalmsg-loadstep.php?step=1'>
		<frame name='step0' src='read-credits.php' scrolling=no>
	</frameset>
	</html>";

	return $OUTPUT;
}

function type_bulk() {
	global $_GET;
	extract($_GET);

	$OUTPUT = "
	<html>

	<script>
	function load_new_step(framenum, stepnum, stepmsg) {
		if ( framenum == 0 )
			step0.document.location.href='".dirname( getenv("SCRIPT_NAME") )."/read-credits.php';
		else if ( framenum == 1 )
			step1.document.location.href='".dirname( getenv("SCRIPT_NAME") )."/bulkmsg-loadstep.php?step=' + stepnum + '&msg=' + stepmsg;
	}
	</script>

	<frameset rows='*,40' border=0>
		<frame name='step1' src='bulkmsg-loadstep.php?step=1'>
		<frame name='step0' src='read-credits.php' scrolling=no>
	</frameset>
	</html>";

	return $OUTPUT;
}

?>

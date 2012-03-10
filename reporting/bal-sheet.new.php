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

require ("../settings.php");
require ("../core-settings.php");

if (isset($HTTP_POST_VARS["key"])) {
	// Make all the keys lowecase to maintain consistency
	$HTTP_POST_VARS["key"] = strtolower($HTTP_POST_VARS["key"]);

	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "customize":
			$OUTPUT = customize($HTTP_POST_VARS);
			break;
		case "update":
			$OUTPUT = update($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = display();
}

function customize()
{
	print metaphone("That's just wack");

	$fields = array ();
	$fields["heading_1"] = COMP_NAME;
	$fields["heading_2"] = date("d/m/Y");
	$fields["heading_3"] = "Balance Sheet";
	$fields["heading_4"] = "Prepared by: ".USER_NAME;

	foreach ($fields as $var_name=>$value) {
		$$var_name = $value;
	}

	db_conn("cubit");


	$OUTPUT = "<table border='0' cellpadding='0' cellspacing='0' width='100%'>
		<tr>
			<td align='left'><h3>$heading_1</h3></td>
			<td align='right'><h3>$heading_2</h3></td>
		</tr>
		<tr>
			<td align='left'><h3>$heading_3</h3></td>
			<td align='right'><h3>$heading_4</h3></td>
		</tr>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th>";

	return $OUTPUT;
}
?>
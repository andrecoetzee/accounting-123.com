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

$OUTPUT = printSalesp ();

require ("../template.php");

function printSalesp () {
	$OUT = "
    <h3>Sales People</h3>
    <table ".TMPL_tblDflts.">
    <tr>
    	<th>Number</th>
    	<th>Sales Person</th>
    	<th>Commission</th>
    </tr>";

    $sp = qrySalesPerson();
	while ($salesp = $sp->fetch_array()) {
		$bgColor = bgcolorg();

		if ($salesp["com"] != 0) {
			$com_disp = "$salesp[com] %";
		} else {
			$com_disp = "Using Commission Set Under Stock Settings";
		}

		$OUT .= "
		<tr class='".bg_class()."'>
			<td>$salesp[salespno]</td>
			<td>$salesp[salesp]</td>
			<td>$com_disp</td>
			<td><a href='salesp-edit.php?salespid=$salesp[salespid]'>Edit</a></td>
			<td><a href='salesp-rem.php?salespid=$salesp[salespid]'>Remove</a></td>
		</tr>";
	}

	$OUT .= "
	</table>
	<br />";

	$OUT .= mkQuickLinks(
		ql("salesp-add.php", "Add Sales Person")
	);

	return $OUT;
}
?>

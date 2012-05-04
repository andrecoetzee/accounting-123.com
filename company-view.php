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

$OUTPUT = printComp();

require ("template.php");

function printComp() {
	$OUT = "
    <h3>View Companies</h3>
    <table ".TMPL_tblDflts.">
    <tr>
    	<th>Company Code</th>
    	<th>Company Name</th>
    	<th>Status</th>
    	<th>Options</th>
    </tr>";

	$qry = new dbSelect("companies", DB_MCUBIT, grp(
		m("order", "name ASC")
	));
	$qry->run();

	$i = 0;
	while ($comp = $qry->fetch_array()) {
		$bgcolor = bgcolor($i);

		$status = ucfirst($comp["status"]);

		$OUT .= "
		<tr class='".bg_class()."'>
			<td>$comp[code]</td>
			<td>$comp[name]</td>
			<td>$status</td>";

		if ($comp["status"] == "removed") {
			$OUT .= "
			<td><a href='company-rem.php?key=recover&code=$comp[code]'>Recover</a></td>";
		}

		$OUT .= "
			<td><a href='company-rem.php?key=confirm&perm=t&code=$comp[code]'>".($comp["status"] == "removed" ? "Remove Permanently (CAN NOT BE RECOVERED)" : "Remove")."</a></td>
		</tr>";
	}

	$OUT .= "
	</table>".
	mkQuickLinks(
		ql("company-new.php", "Add New Company")
	);

	return $OUT;
}
?>

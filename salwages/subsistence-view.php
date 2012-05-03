<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

require("../settings.php");

if (!isset($_GET["key"])) $_GET["key"] = "edit";

switch ($_GET["key"]) {
	case "write":
		$OUTPUT = write();
		break;
	case "edit":
	default:
		$OUTPUT = edit();
		break;
}

$OUTPUT .= "<br>".mkQuickLinks(
	ql ("subsistence-edit.php","Add Subsistence Allowance"),
	ql ("../admin-employee-add.php","Add Employee"),
	ql ("../admin-employee-view.php","View Employees")
);
require("../template.php");




function edit()
{

	extract($_GET);

	$subinfo = new dbSelect("subsistence", "cubit", array("where" => "div='".USER_DIV."'"));
	$subinfo->run();

	$bg = 0;

	$OUT = "
		<h3>Define Subsistence Allowance</h3>
		<form method='get' action='".SELF."'>
			<input type='hidden' name='key' value='write'>
			".(isset($id)?"<input type='hidden' name='id' value='$id'":"")."
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>In Republic</th>
				<th>Pays for own Meals</th>
				<th>Options</th>
			</tr>";

	while ($row = $subinfo->fetch_array()) {
		$OUT .= "
			<tr class='".bg_class()."'>
				<td>$row[name]</td>
				<td>".ucfirst($row["in_republic"])."</td>
				<td>".ucfirst($row["meals"])."</td>
				<td><a href='subsistence-edit.php?id=$row[id]'>Edit</a></td>
			</tr>";
	}

	$OUT .= "
		</table>
		</form>";
	return $OUT;

}

?>
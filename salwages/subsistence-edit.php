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

require("../template.php");



function edit()
{

	extract($_GET);

	if (isset($id)) {
		$subinfo = new dbSelect("subsistence", "cubit", array(
			"where" => wgrp(m("id", "$id"))
		));
		$subinfo->run();

		if ($subinfo->num_rows() > 0) {
			extract($subinfo->fetch_array(), EXTR_SKIP);
		}
	}

	$fields = array(
		"name" => "",
		"in_republic" => "yes",
		"meals" => "yes",
		"accid" => false
	);

	foreach ($fields as $fname => $val) {
		if (!isset($$fname)) $$fname = $val;
	}

	/* no accid is set, use salaries and wages */
	if ($accid === false) {
		$swacc = qryAccountsName("Salaries and Wages", "accid");
		$accid = $swacc["accid"];
	}

	$bg = 0;

	$OUT = "
	<h3>Define Subsistence Allowance</h3>
	<li class='err'>The selection on this window will create a Subsistence Allowance
	with the specified properties. To assign this subsistence to an employee you
	will need to edit the employee, and on the 'Calculate Salary' window
	fill out the employee specific information involving the Subsistence Allowance.</li>
	<form method='get' action='".SELF."'>
	<input type='hidden' name='key' value='write'>
	".(isset($id)?"<input type='hidden' name='id' value='$id'":"")."
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan='2'>Details</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Name:</td>
		<td><input type='text' name='name' value='$name'></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>In Republic (ZA):</td>
		<td>
			<select name='in_republic'>
				<option value='yes' ".($in_republic!="no"?"selected":"").">Yes</option>
				<option value='no' ".($in_republic=="no"?"selected":"").">No</option>
			</select>
		</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Employee pays for own Meals:</td>
		<td>
			<select name='meals'>
				<option value='yes' ".($meals!="no"?"selected":"").">Yes</option>
				<option value='no' ".($meals=="no"?"selected":"").">No</option>
			</select>
		</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Account:</td>
		<td>".finAccList("accid", false, $accid)."</td>
		<td class='err'>Select an account where the expense must be debited to.</td>
	</tr>
	<tr>
		<td colspan='2' align='right'><input type='submit' value='Next'></td>
	</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
	<tr>
		<td>
			<u><b>Subsistence allowance</b></u>
			<p>
			The income tax provides that there shall not be included in the
			taxable income of any person, any amount paid or granted by a
			principal as a reimbursement of, or as an advance for, the
			expenditure incurred or to be incurred by the employee - where
			that employee must produce proof to that employer that such
			expenditure was wholly incurred and must account to that employer
			for that expenditure.
			</p><p>
			For the purpose of the above provision an employee shall be deemed
			to have actually incurred expenditure:
			<li>Where the employee proves to the Commissioner the amount of
			the expenses incurred by him in respect of accommodation, meals or
			other incidental costs, the amount so actually incurred but
			limited to the amount of the allowance or advance paid or granted
			to meet those expenses; or</li>
			<li>For each day or part of a day in the period during which that
			employee is absent from his or her usual place of residence, an
			amount in respect of meals and other incidental costs, or
			incidental costs only, determined by the Minister for the relevant
			year of assessment by way of notice in The Gazette but limited to
			the amount of the allowance paid or granted to meet those
			expenses.
			</p><p>
			According to the regulations published in The Gazette the
			following amounts will be deemted to have been expended by an
			employee to whom an allowance or advance has been granted or paid:
			<li>Where the accommodation to which that allowance or advance
			relate is the Republic and that allowance or the advance is paid
			or granted to defray Incidental costs only, an amount equal to R85
			per day; or<br />
			The cost of meals and incidental costs, an amount equal to R276
			per day; or</li>
			<li>Where the accommodation to which that allowance or advance
			relates is outside the Republic and that the allowance or advance
			is paid or granted to defray the cost of meals and incidental
			costs, an amount equal to U.S. $215 per day.</li>
		</td>
	</tr>
	</table>";

	return $OUT;
}

function write() {
	extract($_GET);

	if (!isset($id)) {
		$id = 0;
	}

	$cols = grp(
		m("name", $name),
		m("in_republic", $in_republic),
		m("meals", $meals),
		m("accid", $accid),
		m("div", USER_DIV)
	);

	$subs = new dbUpdate("subsistence", "cubit", $cols, "id='$id'");
	$subs->run(DB_REPLACE);

	$OUT = "
	<h3>Define Subsistence Allowance</h3>
	Successfully created/updated subsistence allowance.";

	return $OUT;
}

?>

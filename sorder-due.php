<?php

require ("settings.php");
require_lib("ext");

// Are we an admin user, well then we're allowed to select a user other than
// ourselfs
if (user_is_admin(USER_ID) && !isset($_REQUEST["user_id"])) {
	$OUTPUT = slct();
} else {
	$OUTPUT = display();
}

$OUTPUT .= "<p></p>".
	mkQuickLinks (
		ql("sales_report.php", "Sales Report"),
		ql("sorder-new.php", "New Sales Order"),
		ql("sorder-invoiced.php", "View Invoiced Sales Orders")
	);

require ("template.php");



function slct()
{

	extract($_REQUEST);

	// Default values
	$fields = array();
	$fields["user_id"] = USER_ID;

	extract($fields, EXTR_SKIP);

	// Retrieve users
	$sql = "SELECT * FROM cubit.users";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");

	// Create the users dropdown
	$user_sel = "<select name='user_id' style='width: 100%'
				 onchange='javascript:document.form.submit()'>";
	$user_sel.= "<option value='0'>[None]</option>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		if ($user_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$user_sel.= "<option value='$user_data[userid]' $sel>
						$user_data[username]
					 </option>";
	}
	$user_sel.= "</select>";

	$OUTPUT = "
		<center>
		<h3>View Due Sales Orders</h3>
		<form method='POST' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Admin: Select User</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$user_sel</td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}


function display()
{

	extract ($_REQUEST);

	if (user_is_admin(USER_ID)) {
		$user_id = $_REQUEST["user_id"];
	} else {
		$user_id = USER_ID;
	}

	// Retrieve user information
	$sql = "SELECT * FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
	$user_data = pg_fetch_array($user_rslt);

	define ("SECONDS_IN_7_DAYS", 604800);
	$seven_days = date("Y-m-d", (time() + SECONDS_IN_7_DAYS));

	// Retrieve orders for expired orders and orders that will expire within
	// 7 days
	$sql = "SELECT * FROM cubit.sorders
			WHERE ddate<'$seven_days' AND accepted='n' AND done='y'
				AND username='$user_data[username]'
			ORDER BY ddate DESC";
	$sorder_rslt = db_exec($sql) or errDie("Unable to retrieve sales orders.");

	$sorder_out = "";
	while ($sorder_data = pg_fetch_array($sorder_rslt)) {
		$sorder_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$sorder_data[ddate]</td>
				<td>$sorder_data[odate]</td>
				<td>$sorder_data[cusname] $sorder_data[surname]</td>
				<td>$sorder_data[ordno]</td>
				<td>$sorder_data[cusname]</td>
				<td>".CUR."$sorder_data[discount]</td>
				<td>".CUR."$sorder_data[total]</td>
			</tr>";
	}

	if (empty($sorder_out)) {
		$sorder_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='7'>No items found</td>
		</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Sales Orders Past Due/Delivery Date</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Due Date</th>
				<th>Sales Order Date</th>
				<th>Customer</th>
				<th>Sales Order No</th>
				<th>Customer Order No</th>
				<th>Discount</th>
				<th>Total</th>
			</tr>
			$sorder_out
		</table>
		</center>";
	return $OUTPUT;

}

?>
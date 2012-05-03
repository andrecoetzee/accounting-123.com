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
	$user_sel = "<select name='user_id' style='width: 100%' onchange='javascript:document.form.submit()'>";
	$user_sel.= "<option value='0'>[None]</option>";
	while ($user_data = pg_fetch_array($user_rslt)) {
		if ($user_id == $user_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$user_sel.= "<option value='$user_data[userid]' $sel>$user_data[username]</option>";
	}
	$user_sel .= "</select>";

	$OUTPUT = "
		<center>
		<h3>View Invoiced Quotes</h3>
		<form method='POST' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Admin: Select User</th>
			</tr>
			<tr class='".bg_class()."'>
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

	$fields = array();
	$fields["frm_year"] = date("Y");
	$fields["frm_month"] = date("m");
	$fields["frm_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	if (user_is_admin(USER_ID)) {
		$user_id = $_REQUEST["user_id"];
	}else {
		$user_id = $_SESSION["USER_ID"];
	}


	// Retrieve user information
	$sql = "SELECT * FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
	$user_data = pg_fetch_array($user_rslt);

	// Keep track of the totals
	$totals = array();
	$totals["customers"] = 0;
	$totals["discount"] = 0;
	$totals["total"] = 0;
	$totals["ocustomers"] = 0;
	$totals["odiscount"] = 0;
	$totals["ototal"] = 0;
	$totals["bcustomers"] = 0;
	$totals["bdiscount"] = 0;
	$totals["btotal"] = 0;

	$frm_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";

	// Retrieve the invoices
	$sql = "SELECT * FROM cubit.quotes 
			WHERE username='$user_data[username]' AND done='y' AND accepted='y' 
				AND odate BETWEEN '$frm_date' AND '$to_date'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$inv_out.= "
			<tr class='".bg_class()."'>
				<td>$inv_data[odate]</td>
				<td>$inv_data[cusname] $inv_data[surname]</td>
				<td align='center'>$inv_data[quoid]</td>
				<td align='center'>$inv_data[ordno]</td>
				<td align='center'>$inv_data[cordno]</td>
				<td align='right'>".CUR."$inv_data[discount]</td>
				<td align='right'>".CUR."$inv_data[total]</td>
			</tr>";

		// Add to the totals
		$totals["customers"]++;
		$totals["discount"] += $inv_data["discount"];
		$totals["total"] += $inv_data["total"];
	}

	$totals["bcustomers"] += $totals["customers"];
	$totals["bdiscount"] += $totals["discount"];
	$totals["btotal"] += $totals["total"];

	$invtot_out = "
		<tr class='".bg_class()."'>
			<td colspan='5'>&nbsp;</td>
			<td align='right'><b>".CUR . sprint($totals["discount"])."</b></td>
			<td align='right'><b>".CUR . sprint($totals["total"])."</b></td>
		</tr>";

	// Outstanding invoices
	$sql = "
		SELECT * FROM cubit.quotes 
			WHERE username='$user_data[username]' AND done='y' AND accepted='n' 
				AND odate BETWEEN '$frm_date' AND '$to_date'";
	$out_rslt = db_exec($sql) or errDie("Unable to retrieve outstanding invoices.");

	$out_out = "";
	while ($out_data = pg_fetch_array($out_rslt)) {
		$out_out .= "
			<tr class='".bg_class()."'>
				<td>$out_data[odate]</td>
				<td>$out_data[cusname] $out_data[surname]</td>
				<td align='center'>$out_data[quoid]</td>
				<td align='center'>$out_data[ordno]</td>
				<td align='center'>$out_data[cordno]</td>
				<td align='right'>".CUR."$out_data[discount]</td>
				<td align='right'>".CUR."$out_data[total]</td>
			</tr>";

		$totals["ocustomers"]++;
		$totals["odiscount"] += $out_data["discount"];
		$totals["ototal"] += $out_data["total"];
	}

	$totals["bcustomers"] += $totals["ocustomers"];
	$totals["bdiscount"] += $totals["odiscount"];
	$totals["btotal"] += $totals["ototal"];

	$outtot_out = "
		<tr class='".bg_class()."'>
			<td colspan='5'>&nbsp;</td>
			<td align='right'><b>".CUR . sprint($totals["odiscount"])."</b></td>
			<td align='right'><b>".CUR . sprint($totals["ototal"])."</b></td>
		</tr>";

	$bigtot_out = "
		<tr>
			<th colspan='7'>Grand Totals</th>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='5'>&nbsp;</td>
			<td align='right'><b>".CUR . sprint($totals["bdiscount"])."</b></td>
			<td align='right'><b>".CUR . sprint($totals["btotal"])."</b></td>
		</tr>";

	$OUTPUT = "
		<center>
		<h3>View Invoiced Quotes</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='display' />
			<input type='hidden' name='user_id' value='$user_id' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
				<td><b> To </b></td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td><input type='submit' value='Select' /></td>
			</tr>
		</table>
		<p></p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='7'>Invoiced</th>
			</tr>
			<tr>
				<th>Date</th>
				<th>Customer</th>
				<th>Quote No</th>
				<th>Order No</th>
				<th>Customer Order No</th>
				<th>Discount</th>
				<th>Total</th>
			</tr>
			$inv_out
			$invtot_out
			<tr>
				<th colspan='7'>Outstanding</th>
			</tr>
			<tr>
				<th>Date</th>
				<th>Customer</th>
				<th>Quote No</th>
				<th>Order No</th>
				<th>Customer Order No</th>
				<th>Discount</th>
				<th>Total</th>
			</tr>
			$out_out
			$outtot_out
			".TBL_BR."
			$bigtot_out
		</table>
		</form>
		</center>";
	return $OUTPUT;

}


?>
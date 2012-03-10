<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");



function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["user_id"] = USER_ID;

	extract ($fields, EXTR_SKIP);

	$user_out = "";
	if (user_is_admin(USER_ID)) {
		// Retrieve user transactions
		$sql = "SELECT DISTINCT user_id FROM cubit.pos_trans";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve hire transactions.");

		$user_sel = "<select name='user_id'>";
		$i = 0;
		while ($user_data = pg_fetch_array($user_rslt)) {
			$i++;

			$sql = "SELECT username FROM cubit.users WHERE userid='$user_data[user_id]'";
			$username_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
			$username = pg_fetch_result($username_rslt, 0);

			$sel = ($user_data["user_id"] == $user_id) ? "selected='t'" : "";

			$user_sel .= "<option value='$user_data[user_id]' $sel>$username</option>";
		}
		$user_sel .= "</select>";

		if ($i == 0) {
			$user_sel = "No users with outstanding transactions found";
		}

		$user_out = "
			<form method='post' action='".SELF."'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Select User</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$user_sel</td>
					<td><input type='submit' value='Select' /></td>
				</tr>
			</table>
			</form>";
	}

	$sql = "
		SELECT extract('epoch' FROM timestamp) AS e_time, surname, subtotal, vat,
			total, paid, pay_type
		FROM cubit.pos_trans
			LEFT JOIN cubit.customers ON pos_trans.cusnum=customers.cusnum
		WHERE user_id='$user_id'";
	$trans_rslt = db_exec($sql) or errDie("Unable to retrieve transactions.");

	$totals = array();
	$totals["subtotal"] = "0.00";
	$totals["vat"] = "0.00";
	$totals["total"] = "0.00";
	$totals["cash"] = "0.00";
	$totals["cheque"] = "0.00";
	$totals["credit_card"] = "0.00";

	$trans_out = "";
	while ($trans_data = pg_fetch_array($trans_rslt)) {
		$cash = 0.00;
		$cheque = 0.00;
		$credit_card = 0.00;

		switch ($trans_data["pay_type"]) {
			default:
			case "cash":
				$cash = $trans_data["paid"];
				break;
			case "cheque":
				//$cheque = $trans_data["cheque"];
				$cheque = $trans_data["paid"];
				break;
			case "credit_card":
				//$credit_card = $trans_data["credit_card"];
				$credit_card = $trans_data["paid"];
				break;
		}

		$trans_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>".date("Y-m-d G:i:s", $trans_data["e_time"])."</td>
				<td>$trans_data[surname]</td>
				<td align='right'>".sprint($trans_data["subtotal"]-$trans_data["vat"])."</td>
				<td align='right'>".sprint($trans_data["vat"])."</td>
				<td align='right'>".sprint($trans_data["total"])."</td>
				<td align='right'>".sprint($cash)."</td>
				<td align='right'>".sprint($cheque)."</td>
				<td align='right'>".sprint($credit_card)."</td>
			</tr>";

		// Add to totals
		$totals["subtotal"] += $trans_data["subtotal"];
		$totals["vat"] += $trans_data["vat"];
		$totals["total"] += $trans_data["total"];
		$totals["cash"] += $cash;
		$totals["cheque"] += $cheque;
		$totals["credit_card"] += $credit_card;
	}

	if (empty($trans_out)) {
		$trans_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='10'><li>No outstanding transactions for this user found.</li></td>
			</tr>";
	} else {
		$trans_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total</b></td>
				<td align='right'><b>".sprint($totals["subtotal"])."</b></td>
				<td align='right'><b>".sprint($totals["vat"])."</b></td>
				<td align='right'><b>".sprint($totals["total"])."</b></td>
				<td align='right'><b>".sprint($totals["cash"])."</b></td>
				<td align='right'><b>".sprint($totals["cheque"])."</b></td>
				<td align='right'><b>".sprint($totals["credit_card"])."</b></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>POS Cash Up Report</h3>
		$user_out
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Date/Time</th>
				<th>Customer</th>
				<th>Subtotal</th>
				<th>VAT</th>
				<th>Total</th>
				<th>Cash Paid</th>
				<th>Cheque Paid</th>
				<th>Credit Card Paid</th>
			</tr>
			$trans_out
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='8' align='center'>
					<a href='pos_run.php?key=run&user_id=$user_id&cashup=1'
					style='font-size: 1.6em'>RUN TRANSACTIONS</a>
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}

?>

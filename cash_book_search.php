<?php

require ("settings.php");
error_reporting(E_ALL);

$OUTPUT = "<center>".display()."</center>";

require ("template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract($fields, EXTR_SKIP);

	$OUTPUT = "
	<h3>Search Cash Book</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	</form>";

	$sql = "SELECT name, cashbook.reference, trantype, cheqnum, amount, accname,
				bankacct.bankname, currency.fcid, symbol, rate,
				extract('epoch' FROM date) AS e_date
			FROM cubit.cashbook
				LEFT JOIN cubit.customers ON cashbook.cusnum=customers.cusnum
				LEFT JOIN cubit.bankacct ON cashbook.bankid=bankacct.bankid
				LEFT JOIN cubit.currency ON cashbook.fcid=currency.fcid
				LEFT JOIN cubit.suppliers ON cashbook.supid=suppliers.supid
			ORDER BY date, cashid DESC";
	$cb_rslt = db_exec($sql) or errDie("Unable to retrieve cashbook entries.");

	$cb_out = "";
	while ($cb_data = pg_fetch_array($cb_rslt)) {
		$name = preg_replace("/^(\(\[0-9]*\|[0-9] -\))/", "", $cb_data["name"]);
		$currency = ($cb_data["fcid"]) ? $cb_data["symbol"] : CUR;
		$cheq_num = ($cb_data["cheqnum"]) ? $cb_data["cheqnum"] : "&nbsp;";

		$cb_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>".ucfirst($cb_data["trantype"])."</td>
			<td>$cb_data[accname] - $cb_data[bankname]</td>
			<td>".date("d-m-Y", $cb_data["e_date"])."</td>
			<td>$cb_data[name]</td>
			<td>$cb_data[reference]</td>
			<td>$cheq_num</td>
			<td>$currency".sprint($cb_data["amount"])."</td>
		</tr>";
	}

	$OUTPUT.= "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Transaction Type</th>
			<th>Account</th>
			<th>Date</th>
			<th>Payee</th>
			<th>Reference</th>
			<th>Cheque Number</th>
			<th>Amount</th>
		</tr>
		$cb_out
	</table>";

	return $OUTPUT;
}
<?php

require ("settings.php");

// Navigation logic
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "select_customer":
		$OUTPUT = select_customer();
		break;
	case "input_amount":
		$OUTPUT = input_amount;
		break;

	}
} else {
	$OUTPUT = select_customer();
}
					
require ("template.php");

function select_customer()
{
	extract($_REQUEST);

	$fields = array();
	$fields["cusnum"] = 0;
	$fields["search"] = "[_BLANK_]";

	extract($fields, EXTR_SKIP);

	$sql = "
	SELECT cusnum, surname FROM cubit.customers
	WHERE surname ILIKE '$search%' ORDER BY surname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

	$cust_out = "";
	$i = 0;
	while (list($cusnum, $surname) = pg_fetch_array($cust_rslt)) {
		if ($i == 5) {
			$cust_out .= "</tr>";
			$i = 0;
		} 
		if ($i == 0) {
			$cust_out .= "<tr class='".bg_class()."'>";
		}
		$i++;

		$cust_out .= "
		<td>
			<a href='".SELF."?key=input_amounts&cusnum=$cusnum'>
				$i $surname
			</a>
		</td>";
	}

	if ($search == "[_BLANK_]") {
		$search = "";

		$cust_out = "
		<tr class='".bg_class()."'>
			<td>
				<li>Please enter the first letters of the customer name.</li>
			</td>
		</tr>";
	} elseif (empty($cust_out)) {
		$cust_out = "
		<tr class='".bg_class()."'>
			<td><li>No results found. Please redefine your search.</li></td>
		</tr>";
	}


	$OUTPUT = "
	<h3>Deposit Refund</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='select_customer' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Select Customer</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	
	<table ".TMPL_tblDflts.">
		$cust_out
	</table>";

	return $OUTPUT;
}

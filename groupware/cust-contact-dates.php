<?php

require ("../settings.php");

if (!isset($_REQUEST["cusnum"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("../template.php");
}

if (isset($_REQUEST["key"])) {
	$key = strtolower($_REQUEST["key"]);
	switch ($key) {
		case "display":
			$OUTPUT = display();
			break;
		case "add":
			$OUTPUT = add();
			break;
		case "remove":
			$OUTPUT = remove();
			break;
	}
} else {
	$OUTPUT = display();
}

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	// Retrieve customer information
	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cusnum'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information.");
	$cust_data = pg_fetch_array($cust_rslt);

	$OUTPUT = "
	<center>
	<h3>Customer Contact Dates</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Customer</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Contact Name</td>
			<td>$cust_data[surname]</td>
		</tr>
	</table>
	<p></p>";

	// Retrieve customer contact dates
	$sql = "
	SELECT *,extract('epoch' FROM date) as e_date FROM cubit.cust_dates
	WHERE cust_id='$cusnum' AND user_id='".USER_ID."' ORDER BY date DESC";
	$cd_rslt = db_exec($sql) or errDie("Unable to retrieve customer dates.");

	$dates_out = "";
	while ($cd_data = pg_fetch_array($cd_rslt)) {
		$date_year = date("Y", $cd_data["e_date"]);
		$date_month = date("m", $cd_data["e_date"]);
		$date_day = date("d", $cd_data["e_date"]);

		$dates_out .= "
		<form method='post' action='".SELF."'>
		<input type='hidden' name='id' value='$cd_data[id]' />
		<input type='hidden' name='cusnum' value='$cusnum' />
		<tr bgcolor='".bgcolorg()."'>
			<td>$date_day-$date_month-$date_year</td>
			<td>$cd_data[notes]</td>
			<td><input type='submit' name='key' value='Remove' /></td>
		</tr>
		</form>";
	}

	$OUTPUT .= "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Note</th>
			<th>Options</th>
		</tr>
		<form method='post' action='".SELF."'>
		<input type='hidden' name='cusnum' value='$cusnum' />
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("new_date")."</td>
			<td><input type='text' name='new_note' /></td>
			<td>
				<input type='submit' name='key' value='Add' style='width:100%'/>
			</td>
		</tr>
		</form>
		$dates_out
	</table>";

	return $OUTPUT;
}

function add()
{
	extract ($_REQUEST);

	$new_date = "$new_date_year-$new_date_month-$new_date_day";

	$sql = "
	INSERT INTO cubit.today ()
	VALUES ('".USER_ID."', '$cusnum', '$new_date', '$new_note')";
	$cd_rslt = db_exec($sql) or errDie("Unable to insert customer date.");


	return display();
}

function remove()
{
	extract ($_REQUEST);

	$sql = "DELETE FROM cubit.cust_dates WHERE id='$id'";
	db_exec($sql) or errDie("Unable to remove date.");

	return display();
}
<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display();
			break;
		case "get":
			$OUTPUT = getNote();
			break;
	}
}
$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["search"] = "~:BLANK:~";
	$fields["filter"] = "";
	$fields["rsearch"] = "";

	extract ($fields, EXTR_SKIP);

	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	$filter_list = array(
		"cust"=>"Customers",
		"inv"=>"Hire No"
	);

	$filter_sel = "<select name='filter' style='width: 100%'>";
	foreach ($filter_list as $key=>$value) {
		if ($filter == $key) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$filter_sel .= "<option value='$key' $sel>$value</option>";
	}
	$filter_sel .= "</select>";

	if (!empty($search) && $filter == "inv") {
		$nsearch = $search;
		if (!is_numeric($search)) {
			$nsearch = 0;
		}
		$rsearch = "AND invnum='$nsearch'";
	}

	if ($search == "~:BLANK:~") {
		$sql = "SELECT * FROM hire.hire_invoices
					WHERE done='haha notreally' and printed='youmustbekidding'";
		$search = "";
	} else {
		$sql = "SELECT * FROM hire.hire_invoices
				WHERE done='y' AND printed='y'
					AND odate BETWEEN '$from_date' AND '$to_date' $rsearch
				ORDER BY odate DESC";
	}
	$hinv_rslt = db_exec($sql) or errDie("Unable to retrieve hire notes.");

	$notes_out = "";
	while ($hinv_data = pg_fetch_array($hinv_rslt)) {
		if (!empty($search) && $filter == "cust") {
			$sql = "SELECT * FROM cubit.customers
					WHERE cusname ILIKE '%$search%' OR surname ILIKE '%$search%'";
			$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

			if (!pg_num_rows($cust_rslt)) {
				continue;
			}
		}

		// Retrieve the customer
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$hinv_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		// Check if we've got a signed hire note
		$sql = "SELECT * FROM hire.signed_hirenotes WHERE invid='$hinv_data[invid]'";
		$sh_rslt = db_exec($sql) or errDie("Unable to check for scanned hire note.");
		$sh_data = pg_fetch_array($sh_rslt);

		if (!pg_num_rows($sh_rslt)) {
			continue;
		}

		$notes_out .= "<tr class='".bg_class()."'>
			<td>$hinv_data[odate]</td>
			<td>H".getHirenum($hinv_data["invid"], 1)."</td>
			<td>$cust_data[surname] $cust_data[cusname]</td>
			<td>
				<a href='".SELF."?key=get&id=$sh_data[id]'>
					Signed Hire Note
				</a>
			</td>
		</tr>";
	}

	if (empty($notes_out)) {
		$notes_out = "<tr class='".bg_class()."'>
			<td colspan='4'><li>No results, please enter a customer name or hire
			no.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Signed Hire Notes</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b> To </b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' style='font-weight: bold' /></td>
		</tr>
		<tr class='".bg_class()."'>
			<th colspan='4'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='2'>
				<input type='text' name='search' value='$search' style='width: 100%;' />
			</td>
			<td>$filter_sel</td>
			<td><input type='submit' value='Search' style='font-weight: bold' /></td>
		</tr>
	</table>
	</form>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Hire No</th>
			<th>Customer</th>
			<th>Options</th>
		</tr>
		$notes_out
	</table>
	</center>";

	return $OUTPUT;
}

function getNote()
{
	extract($_REQUEST);

	$sql = "SELECT * FROM hire.signed_hirenotes WHERE id='$id'";
	$sh_rslt = db_exec($sql) or errDie("Unable to retrieve signed hire note.");
	$sh_data = pg_fetch_array($sh_rslt);

	$file = base64_decode($sh_data["file"]);

	header ("Content-Type: ". $sh_data["file_type"] ."\n");
	header ("Content-Transfer-Encoding: binary\n");
	header ("Content-length: " . strlen ($file) . "\n");
	header ( "Content-Disposition: attachment; filename=$sh_data[file_name]" );

	print $file;
}

<?php

require ("../settings.php");

if (!isset($_REQUEST["invid"]) && !is_numeric($_REQUEST["invid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module</li>";
	require ("../template.php");
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");

function enter()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$_REQUEST[invid]'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	$inv_data = pg_fetch_array($inv_rslt);

	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$inv_data[cusnum]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
	$cust_data = pg_fetch_array($cust_rslt);

	$sql = "SELECT invid FROM hire.unsigned_hirenotes WHERE invid='$invid'";
	$uh_rslt = db_exec($sql) or errDie("Unable to retrieve unsinged hirenotes.");

	if (!pg_num_rows($uh_rslt)) {
		$sql = "INSERT INTO hire.unsigned_hirenotes (invid, trans_date, invnum,
					cusnum)
				VALUES ('$inv_data[invid]', '$inv_data[odate]',
					'".getHirenum($inv_data["invid"])."', '$inv_data[cusnum]')";
		db_exec($sql) or errDie("Unable to create unsigned hire note.");

		$uh_id = pglib_lastid("hire.unsigned_hirenotes", "id");
	} else {
		$uh_id = pg_fetch_result($uh_rslt, 0);
	}

	$OUTPUT = "<h3>Signed Hire Note</h3>
	<form method='post' action='".SELF."' enctype='multipart/form-data'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='invid' value='$invid' />
	<input type='hidden' name='uh_id' value='$uh_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Hire No.</td>
			<td>H".getHirenum($inv_data["invid"], 1)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer</td>
			<td>$cust_data[cusname] $cust_data[surname]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Signed Hire Note</td>
			<td><input type='file' name='file' /></td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Write &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	pglib_transaction("BEGIN");
	if (is_uploaded_file($_FILES["file"]["tmp_name"])) {
		$file = "";
		$fp = fopen ($_FILES["file"]["tmp_name"], "rb");

		while (!feof ($fp)) {
			// fread is binary safe
			$file .= fread ($fp, 1024);
		}
		fclose ($fp);

		# base 64 encoding
		$file = base64_encode($file);

		$sql = "DELETE FROM hire.unsigned_hirenotes WHERE id='$uh_id'";
		db_exec($sql) or errDie("Unable to remove from unsigned hirenotes.");

		$sql = "INSERT INTO hire.signed_hirenotes (invid, file_name, file_type, file)
				VALUES ('$invid', '".$_FILES["file"]["name"]."',
					'".$_FILES["file"]["type"]."', '$file')";
		db_exec($sql) or errDie("Unable to retrieve scanned signed hire notes.");
	}
	pglib_transaction("COMMIT");

	$OUTPUT = "
	<script>
		parent.document.reload();
	</script>
	<h3>Signed Hire Note</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully saved signed hire note</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}

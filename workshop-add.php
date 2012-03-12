<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#
require ("settings.php");

// Merge post vars and get vars
foreach ($_GET as $key => $val) {
	$_POST[$key] = $val;
}

// Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "receipt":
			$OUTPUT = receipt($_POST);
			break;
		case "receipt-print":
			receipt-print($_POST);
			break;
		case "workshop-report":
			$OUTPUT = workshop-report($_POST);
			break;
	}
} else {
	$OUTPUT = enter();
}

// Quick links
$OUTPUT .= "
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					<tr>
					<tr class='datacell'>
						<td><a href='workshop-view.php'>View workshop</td>
					</tr>
					<tr class='datacell'>
						<td><a href='customers-new.php'>Add Customer<a></td>
					</tr>
					<tr class='datacell'>
						<td><a href='stock-add.php'>Add Stock</a></td>
					</tr>
					<tr class='datacell'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";

require ("template.php");




function enter($errors="")
{

	global $_POST;
	extract($_POST);

	require_lib("validate");
	$v = new validate;

	$fields["search_cus"] = "";
	$fields["stkid"] = "";
	$fields["cusnum"] = "";
	$fields["stkcod"] = "";
	$fields["stkname"] = "";
	$fields["serno"] = "";
	$fields["description"] = "";
	$fields["conditions"] = "";
	$fields["notes"] = "";

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	if (empty($conditions)) {
		// Retrieve the workshop conditions from Cubit.
		db_conn("cubit");
		$sql = "SELECT value FROM workshop_settings WHERE div='".USER_DIV."' AND setting='workshop_conditions'";
		$wssRslt = db_exec($sql) or errDie("Unable to retrieve workshop settings from Cubit.");
		$conditions = pg_fetch_result($wssRslt, 0);
	}

	if (isset($notes)) {
		$v->isOk($notes, "string", 1, 1024, "Invalid notes.");
	} else {
		$notes = "";
	}

	// Stock code dropdown
	$stkdn = "<select name=stkid style='width:180px'>
	  <option value='0'>Please select</th>";
	db_conn("cubit");
	$sql = "SELECT * FROM stock WHERE div='".USER_DIV."' ORDER BY stkcod ASC";
	$stkRslt = db_exec($sql) or errDie("Unable to retrieve the stock from Cubit.");
	while ($stkData = pg_fetch_array($stkRslt)) {
		if ($stkid == $stkData["stkid"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$stkdn .= "<option value='$stkData[stkid]' $selected>$stkData[stkcod]</option>";
	}
	$stkdn .= "</select>";

	// Customer dropdown
	$cusdn = "<select name='cusnum' style='width:180px'>";
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE surname LIKE '%$search_cus%' AND div='".USER_DIV."' ORDER BY surname ASC";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customers from Cubit.");
	if (pg_num_rows($cusRslt) == 0) {
		$cusdn .= "<option value='0'>No customers found</option>";
	}
	while ($cusData = pg_fetch_array($cusRslt)) {
		if ($cusnum == $cusData["cusnum"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$cusdn .= "<option value='$cusData[cusnum]' $selected>$cusData[surname] $cusData[init]</option>";
	}
	$cusdn .= "</select>";

	$OUTPUT = "
					<h3>Add to workshop</h3>
					$errors
					<form method='POST' action='".SELF."' name='frm_ws'>
						<input type='hidden' name='key' value='confirm'>
					<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
						<tr>
							<th colspan=2>Add</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Customer</td>
							<td>
								<center><input type='button' value='Search' onClick='popupSized(\"customers-view.php?action=select&".frmupdate_make("text", "frm_ws", "cusnum", "cusnum_only")."\", \"workshop_custsearch\", 800, 400);'></center><br>
								$cusdn
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Stock Code/Name</td>
							<td>
								$stkdn<br>
								<input type='text' name='stkname' value='$stkname' style='width:180px'>
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Serial number</td>
							<td><input type='text' name='serno' value='$serno' style='width:180px'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Description</td>
							<td><input type='text' name='description' value='$description' style='width:180px'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Workshop Conditions</td>
							<td><textarea name='conditions' rows='5' style='width:180px'>$conditions</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Notes</td>
							<td><textarea name='notes' rows='5' style='width:180px'>$notes</textarea></td>
						</tr>
						<tr>
							<td colspan='2' align='right'>
								<input type='submit' value='Confirm &raquo'>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}



function confirm($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($stkid, "num", 0, 9, "Invalid stock id (dropdown)");
	$v->isOk($stkname, "string", 0, 255, "Invalid stock name (input field)");
	$v->isOk($cusnum, "num", 0, 9, "Invalid customer number (dropdown)");
	$v->isOk($serno, "string", 0, 255, "Invalid serial number.");
	$v->isOk($description, "string", 1, 255, "Invalid description.");
	$v->isOk($conditions, "string", 1, 255, "Invalid workshop conditions.");

	if ($stkid != 0 && !empty($stkname)) {
		$v->addError(0,"Please use either stock dropdown or stock input, not both.");
	}
	if ($stkid == 0 && empty($stkname)) {
		$v->addError(0, "Please select stock from the dropdown or input field.");
	}
	if ($cusnum == 0) {
		$v->addError(0, "Please select a customer.");
	}

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
	}



	// Get the customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$cusnum'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customers from Cubit.");
	$cusData = pg_fetch_array($cusRslt);
	if (!empty($cusData["init"])) {
		$customer = "$cusData[title]. $cusData[init] $cusData[surname]";
	} else {
		$customer = "$cusData[surname]";
	}

	// See which stock selection we made
	if ($stkid != 0) {
		db_conn("cubit");
		$sql = "SELECT stkcod FROM stock WHERE stkid='$stkid'";
		$stkRslt = db_exec($sql) or errDie("Unable to retrieve stock from Cubit.");
		$stock = pg_fetch_result($stkRslt, 0);
	} else {
		$stock = $stkname;
	}

	$OUTPUT = "
					<h3>Add to workshop</h3>
					<form method='POST' action='".SELF."'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='stkid' value='$stkid'>
						<input type='hidden' name='stkname' value='$stkname'>
						<input type='hidden' name='cusnum' value='$cusnum'>
						<input type='hidden' name='serno' value='$serno'>
						<input type='hidden' name='description' value='$description'>
						<input type='hidden' name='conditions' value='$conditions'>
						<input type='hidden' name='notes' value='$notes'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Confirm</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td>$customer</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Stock Code/Name</td>
							<td>$stock</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Serial number</td>
							<td>$serno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td>$description</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Workshop Conditions</td>
							<td>$conditions</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Notes</td>
							<td>".nl2br($notes)."</td>
						</tr>
						<tr>
							<td colspan='2' align='right'>
								<input type='submit' name='key' value='&laquo Correction'>
								<input type='submit' value='Write &raquo'>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}



function write($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($stkid, "num", 0, 9, "Invalid stock id (dropdown)");
	$v->isOk($stkname, "string", 0, 255, "Invalid stock name (input field)");
	$v->isOk($cusnum, "num", 0, 9, "Invalid customer number (dropdown)");
	$v->isOk($serno, "string", 0, 255, "Invalid serial number.");
	$v->isOk($description, "string", 1, 255, "Invalid description.");

	if ($stkid != 0 && !empty($stkname)) {
		$v->addError(0,"Please use either stock dropdown or stock input, not both.");
	}
	if ($stkid == 0 && empty($stkname)) {
		$v->addError(0, "Please select stock from the dropdown or input field.");
	}
	if ($cusnum == 0) {
		$v->addError(0, "Please select a customer.");
	}

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
	}



	// See which stock selection we made
	if ($stkid != 0) {
		db_conn("cubit");
		$sql = "SELECT stkcod FROM stock WHERE stkid='$stkid'";
		$stkRslt = db_exec($sql) or errDie("Unable to retrieve stock from Cubit.");
		$stkcod = pg_fetch_result($stkRslt, 0);
	} else {
		$stkcod = $stkname;
	}

	$sql = "INSERT INTO workshop (stkcod, cusnum, serno, description, notes, status, cdate, active) VALUES ('$stkcod', '$cusnum', '$serno', '$description', '".base64_encode($notes)."', 'Present', current_date, 'true')";
	$wsRslt = db_exec($sql) or errDie("Unable to insert workshop data into Cubit.");

	if (pg_affected_rows($wsRslt) == 0) {
		return $OUTPUT = "<center><li class='err'>Could not be added to the workshop</li></center>";
	} else {
		$refnum = pglib_lastid("workshop", "refnum");

		return $OUTPUT = "<li>Successfully added to workshop</li> <script>printer(\"".SELF."?key=receipt&cusnum=$cusnum&refnum=$refnum&description=$description&conditions=$conditions&serno=$serno\");</script>";

	}

}



function receipt($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($cusnum, "num", 0, 9, "Invalid customer number (dropdown)");
	$v->isOk($description, "string", 1, 255, "Invalid description.");
	$v->isOk($refnum, "num", 0, 255, "Invalid reference number.");
	$v->isOk($conditions, "string", 1, 255, "Invalid workshop conditions.");
	$v->isOk($serno, "string", 0, 255, "Invalid serial number.");

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
	}



	// Retrieve customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$cusnum'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cusData = pg_fetch_array($cusRslt);
	if (!empty($cusData["init"])) {
		$customer = "$cusData[init]. $cusData[title] $cusData[surname]";
	} else {
		$customer = "$cusData[surname]";
	}

	$OUTPUT = "
					<center>
					<table border='1' cellpadding='10' cellspacing='1' width='750'>
						<tr>
							<td valign='top' colspan='2' width='100%'><h1>".COMP_NAME."</h1></td>
						</tr>
						<tr>
							<td valign='top' width='50%'>
								<b>Workshop receipt for:</b><br>
								$customer<br>
								".nl2br($cusData["addr1"])."<br><br>
								Tel: $cusData[tel]<br>
								Fax: $cusData[fax]<br>
								Cell: $cusData[cellno]<br>
								Bussiness Tel: $cusData[bustel]
							</td>
							<td valign='top' width='50%'>
								<b>Date:</b> ".date("Y-m-d")."<br>
								<b>Reference Number:</b> $refnum
							</td>
						</tr>
						<tr>
							<td valign='top' colspan='2' width='100%'><b>Description:</b> $description</td>
						</tr>
						<tr>
							<td valign='top' colspan='2' width='100%'><b>Serial No:</b> $serno</td>
						</tr>
						<tr>
							<td valign='top' colspan='2' width='100%'>
								<b>Workshop Conditions:</b><br>
								$conditions
							</td>
						</tr>
						<tr>
							<td valign='top' colspan='2' width='100%'>Workshop Sign: _____________________</td>
						</tr>
					</table>
					</center>";
	require ("tmpl-print.php");

}


?>
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

// Merge get vars with post vars
foreach ($_GET as $key => $val) {
	$_POST[$key] = $val;
}

// Decide which function to perform
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "update":
			$OUTPUT = update($_POST);

			if ($_POST["update"] == "client_collect") {
				// Extra quick links
				$extra_qlinks = "
									<tr class='datacell'>
										<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
									</tr>
									<tr class='datacell'>
										<td><a href='pos-invoice-new.php'>New POS Invoice</a></td>
									</tr>
									<tr class='datacell'>
										<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
									</tr>";
			}
			break;
		case "client_collect":
			$OUTPUT = client_collect($_POST);
			// Extra quick links
			$extra_qlinks = "
								<tr class='datacell'>
									<td><a href='cust-credit-stockinv.php'>New Invoice</a></td>
								</tr>
								<tr class='datacell'>
									<td><a href='pos-invoice-new.php'>New POS Invoice</a></td>
								</tr>
								<tr class='datacell'>
									<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
								</tr>";
			break;
		case "status_history":
			$OUTPUT = status_history($_POST);
			break;
		case "edit_notes":
			$OUTPUT = edit_notes($_POST);
			break;
		case "check_in":
			$OUTPUT = check_in($_POST);
			break;
		case "check_out":
			$OUTPUT = check_out($_POST);
			break;
		case "receipt":
			receipt($_POST);
			break;
		case "workshop_report":
			$OUTPUT = workshop_report($_POST);
			break;
	}
} else {
	$OUTPUT = display();
}

if (!isset($extra_qlinks)) $extra_qlinks = "";

// Append quick links to each page
$OUTPUT .= "
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					<tr>
					<tr class='datacell'>
						<td><a href='workshop-add.php'>Add to workshop</td>
					</tr>
					<tr class='datacell'>
						<td><a href='supp-new.php'>Add Supplier<a></td>
					</tr>
					$extra_qlinks
					<tr class='datacell'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";

require ("template.php");



function display($errors="")
{

	global $_POST;
	extract($_POST);

	// Validate and set variables
	require_lib("validate");
	$v = new validate;
	if (isset($search)) {
		$v->isOk($search, "string", 0, 255, "Invalid search string.");
	} else {
		$search = "";
	}
	if (isset($search_keysel)) {
		$v->isOk($search_keysel, "string", 0, 255, "Invalid search selection.");
	} else {
		$search_keysel = "";
	}
	if (isset($offset)) {
		$v->isOk($offset, "num", 1, 9, "Invalid offset defined");
	} else {
		$offset = 0;
	}

	// If any errors were found, clear the variables and display errors
	if ($v->isError()) {
		$errarr = $v->getErrors();
		foreach ($errarr as $e) {
			$errors .= "<li class='err'>$e[msg]</li>";
		}
		// Clear the variables
		$search = "";
		$search_keysel = "";
	}

	// Search query
	if (!empty($search) && $search_keysel != "cusname" && $search_keysel != "age") {
		$wsSql = "SELECT * FROM workshop WHERE $search_keysel LIKE '%$search%' ORDER BY status DESC";
	} elseif (!empty($search) && $search_keysel == "cusname") {
		// Retrieve the customer numbers
		db_conn("cubit");
		$cusSql = "SELECT cusnum FROM customers WHERE surname LIKE '%$search%'";
		$cusRslt = db_exec($cusSql) or errDie("Unable to retrieve customer numbers from Cubit.");

		$wsSqlarr = array();
		while ($cusData = pg_fetch_array($cusRslt)) {
			$wsSqlarr[] = "SELECT * FROM workshop WHERE cusnum='$cusData[cusnum] AND active='true''";
		}

		$wsSql = implode(" UNION ", $wsSqlarr);
		if (!empty($wsSql)) {
			$wsSql .= " ORDER BY status DESC";
		} else {
			// Do a select which would retrieve nothing instead of displaying an error
			$wsSql = "SELECT * FROM workshop WHERE cusnum='0' AND active='true'";
		}
	} elseif (!empty($search) && $search_keysel == "age") {
		if (is_numeric($search)) {
			$wsSql = "SELECT * FROM workshop WHERE age(cdate)<='$search Days' AND active='true'";
		} else {
			return display("<li class='err'>Age searching requires a numeric input</li>");
		}
	} else {
		$wsSql = "SELECT * FROM workshop WHERE active='true' ORDER BY status DESC";
	}
	// for use with the offset, offset needs to retrieve the full number of rows without the LIMIT
	$osSql = $wsSql;
	$wsSql .= " LIMIT 20 OFFSET $offset";
	// Retrieve items in the workshop
	db_conn("cubit");
	$wsRslt = db_exec($wsSql) or errDie("Unable to retrieve workshop items from Cubit.");

	// Previous and next offset values
	db_conn("cubit");
	$osRslt = db_exec($osSql) or errDie("Unable to retrieve offset data from Cubit.");
	$offset_prev = ($offset - 20);
	$offset_next = ($offset + 20);

	if ($offset_prev < 0) {
		$prev = "";
	} else {
		$prev = "<a href='?key=display&search=$search&search_keysel=$search_keysel&offset=$offset_prev'>&laquo Previous</a>";
	}
	if ($offset_next > pg_num_rows($osRslt)) {
		$next = "";
	} else {
		$next = "<a href='?key=display&search=$search&search_keysel=$search_keysel&offset=$offset_next'>Next &raquo</a>";
	}

	$items = "";
	$i = 0;

	// See if we got any results from the query
	if (pg_num_rows($wsRslt) == 0) {
		$items = "<tr bgcolor='".TMPL_tblDataColor1."'>
		  <td colspan=9>No items found.</td>";
	}
	while ($wsData = pg_fetch_array($wsRslt)) {
		// Get the customer name
		$sql = "SELECT surname, init FROM customers WHERE cusnum='$wsData[cusnum]'";
		$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer name from Cubit.");
		$cusData = pg_fetch_array($cusRslt);

		$i++;

		$status = explode(",", $wsData["status"]);
		$status = $status[count($status)-1];

		// Which links should be enabled/disabled
		if ($status == "Present") {
			$client_collect = "<a href='?key=client_collect&refnum=$wsData[refnum]'>Client Collect</a>";
			$checkinout = "<tr>
		      <td>
		        Check in
		      </td>
		      <td>
		        <a href='?key=check_out&refnum=$wsData[refnum]'>Check out to supplier</a>
		      </td>
		    </tr>";
		} else {
			$client_collect = "Client Collect";
			$checkinout = "<tr>
		      <td>
		        <a href='?key=check_in&refnum=$wsData[refnum]'>Check In</a>
		      </td>
		      <td>
		        <a href='?key=check_out&refnum=$wsData[refnum]'>Check Out to supplier</a>
		      </td>
		    </tr>";
		}

		db_conn("cubit");
		$sql = "SELECT * FROM serialrec WHERE serno='$wsData[serno]'";
		$ser_rslt = db_exec($sql) or errDie("Unable to retrieve serial number information from Cubit.");
		$ser_data = pg_fetch_array($ser_rslt);

		if (pg_num_rows($ser_rslt)) {
			db_conn("cubit");
			$sql = "SELECT warranty FROM stock WHERE stkid='$ser_data[stkid]'";
			$stk_rslt = db_exec($sql) or errDie("Unable to retrieve stock information from Cubit.");
			$stk_data = pg_fetch_array($stk_data);
			$warranty = $stk_data["warranty"];
		} else {
			$warranty = "";
		}

		// Items layout
		$items .= "
					<tr bgcolor='".bgcolorg()."' valign=top>
						<td>$wsData[refnum]</td>
						<td>$wsData[cdate]</td>
						<td><a href='cust-det.php?cusnum=$wsData[cusnum]' target=_blank>$cusData[surname] $cusData[init]</a></td>
						<td>$wsData[stkcod]</td>
						<td>$wsData[serno]</td>
						<td>$warranty</td>
						<td>$wsData[description]</td>
						<td style='width:180px'>
							<center><b>$status</b></center>
							<br>".nl2br(base64_decode($wsData["notes"]))."
						</td>
						<td nowrap align=center>
							<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
								<tr>
									<td nowrap>$client_collect</td>
									<td nowrap>
										<a href='?key=status_history&refnum=$wsData[refnum]'>Status History</a>
										<a href='?key=edit_notes&refnum=$wsData[refnum]'>Edit Notes</a>
									</td>
								</tr>
								$checkinout
							</table>
						</td>
					</tr>";
	}

	// Search dropdown
	$search_keys = "<select name=search_keysel>";
	$search_keyarr = array("refnum"=>"Reference Number",
		"cusname"=>"Customer",
		"serno"=>"Serial Number",
		"stkcod"=>"Stock Code/Name",
		"description"=>"Description",
		"cdate"=>"Date (YYYY-MM-DD)",
		"age"=>"Age (Days)"
	);

	foreach ($search_keyarr as $key => $val) {
		if ($search_keysel == $key) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$search_keys .= "<option value='$key' $selected>$val</option>";
	}
	$search_keys .= "</select>";

	// Layout
	$OUTPUT = "
				<center>
				<h3>View workshop</h3>
				$errors
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='display'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='3'>Search</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='text' name='search' value='$search'></td>
						<td>$search_keys</td>
						<td><input type='submit' value='Search'></td>
					</tr>
					<tr>
						<td>&nbsp</td>
					</tr>
					<tr>
						<td colspan='3>
							<table ".TMPL_tblDflts." width='100%'>
								<tr>
									<th colspan='2'>Workshop reports</th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td align='center'><a href='?key=workshop_report&report=age'>Age report</a></td>
									<td align='center'><a href='?key=workshop_report&report=date'>Date report</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				</form>
				<p>
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Ref no.</th>
						<th>Date</th>
						<th>Customer</th>
						<th>Stock/Asset Code/Name</th>
						<th>Serial Number</th>
						<th>Warranty</th>
						<th>Description</th>
						<th>Status/Notes</th>
						<th>Options</th>
					</tr>
					<tr class='datacell'>
						<td colspan='9' align='right'>
							$prev
							$next
						</td>
					</tr>
					$items
					<tr class='datacell'>
						<td colspan='9' align='right'>
							$prev
							$next
						</td>
					</tr>
				</table>
				</center>";
	return $OUTPUT;

}



function update($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 0, 9, "Invalid reference number");
	$v->isOk($update, "string", 1, 255, "Nothing to update");

	if (isset($status)) {
		$v->isOk($status, "string", 1, 255, "Invalid status location.");
	}
	if (isset($supname)) {
		$v->isOk($supname, "string", 1, 255, "Invalid supplier name.");
	}

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}



	db_conn("cubit");
	switch ($update) {
		case "client_collect":
			if (isset($printed)) {
				$sql = "UPDATE workshop SET active='false', status='Completed' WHERE refnum='$refnum'";
				$rslt = db_exec($sql) or errDie("Unable to update data to Cubit.");
			} else {
				// Print a receipt
	 			$OUTPUT = "
	 						<form method='POST' action='".SELF."'>
				 				<input type='hidden' name='key' value='update'>
				 				<input type='hidden' name='update' value='client_collect'>
				 				<input type='hidden' name='refnum' value='$refnum'
				 				<input type='hidden' name='printed' value='true'>
							<table ".TMPL_tblDflts." width='400'>
								<tr>
									<th>Info</th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><li>After the receipt has been printed successfully, please click on \"<b>Write &raquo</b>\" to commit the changes.</li></td>
								</tr>
								<tr>
									<td align='right'>
										<input type='submit' name='key' value='&laquo View Workshop'>
										<input type='submit' value='Write &raquo'>
									</td>
								</tr>
							</table>
 							<script>printer(\"".SELF."?key=receipt&type=delivery_note&refnum=$refnum\");</script>";
 				return $OUTPUT;
 			}
			break;
		case "edit_notes":
			$sql = "UPDATE workshop SET notes='".base64_encode($notes)."' WHERE refnum='$refnum'";
			$rslt = db_exec($sql) or errDie("Unable to update data to Cubit.");
			break;
		case "check_in":
			db_conn("cubit");
			$sql = "SELECT status FROM workshop WHERE refnum='$refnum'";
			$statusRslt = db_exec($sql) or errDie("Unable to retrieve status from Cubit.");
			$statusData = pg_fetch_result($statusRslt, 0) . ",Present";

			$sql = "UPDATE workshop SET status='$statusData' WHERE refnum='$refnum'";
			$rslt = db_exec($sql) or errDie("Unable to update data to Cubit.");
			break;
		case "check_out":
			db_conn("cubit");
			$sql = "SELECT status FROM workshop WHERE refnum='$refnum'";
			$statusRslt = db_exec($sql) or errDie("Unable to retrieve status from Cubit.");
			$statusData = pg_fetch_result($statusRslt, 0) . ",$supname";

			$sql = "UPDATE workshop SET status='$statusData' WHERE refnum='$refnum'";
			$rslt = db_exec($sql) or errDie("Unable to update data to Cubit.");

			// Print a receipt
 			$OUTPUT = "
						<form method='POST' action='".SELF."'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Info</th>
							</tr>
							<tr bgcolor='".TMPL_tblDataColor1."'>
								<td><li>Successfully checked out.</li></td>
							</tr>
							<tr>
								<td align='right'><input type='submit' name='key' value='&laquo View Workshop'></td>
							</tr>
						</table>
						<script>printer(\"".SELF."?key=receipt&type=receipt&refnum=$refnum&supname=$supname&conditions=$conditions\");</script>";
 			return $OUTPUT;
			break;
	}
	return display();

}



function client_collect($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 0, 9, "Invalid reference number");

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}



	db_conn("cubit");
	$sql = "SELECT * FROM workshop WHERE refnum='$refnum'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve item from the workshop.");
	$wsData = pg_fetch_array($wsRslt);

	// retrieve customer name
	db_conn("cubit");
	$sql = "SELECT surname FROM customers WHERE cusnum='$wsData[cusnum]'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer name from Cubit.");
	$surname = pg_fetch_result($cusRslt, 0);

	$OUTPUT = "
					<h3>View workshop</h3>
					<form method='POST' action='".SELF."'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='update' value='client_collect'>
						<input type='hidden' name='refnum' value='$refnum'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan=2>Client collect from workshop</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference number</td>
							<td>$wsData[refnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td>$surname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Stock Code/Name</td>
							<td>$wsData[stkcod]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Serial Number</td>
							<td>$wsData[serno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td>$wsData[description]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Notes</td>
							<td>".nl2br(base64_decode($wsData["notes"]))."</td>
						</tr>
						<tr>
							<td colspan='2' align='right'>
								<input type='submit' name='key' value='&laquo View Workshop'>
								<input type='submit' value='Confirm &raquo'>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}



function status_history($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 0, 9, "Invalid reference number");

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}



	db_conn("cubit");
	$sql = "SELECT status FROM workshop WHERE refnum='$refnum'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve status history from Cubit.");
	$status = pg_fetch_result($wsRslt, 0);

	$hist = explode(",", $status);
	$hist = array_reverse($hist);
	$hist_out = "";
	foreach ($hist as $val) {
		$hist_out .= "$val<br>";
	}

	$OUTPUT = "
					<h3>View workshop</h3>
					<form method='POST' action='".SELF."'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Status history</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$hist_out</td>
						</tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' name='key' value='&laquo View Workshop'></td>
						</tr>
					</table>
					</form>";
	return $OUTPUT;

}



function edit_notes($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 0, 9, "Invalid reference number.");

	// Display errors
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm = "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}

	// Retrieve the notes
	db_conn("cubit");
	$sql = "SELECT notes FROM workshop WHERE refnum='$refnum'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve notes from the workshop.");
	$notes = base64_decode(pg_fetch_result($wsRslt, 0));

	$OUTPUT = "
					<h3>View workshop</h3>
					<form method='POST' action='".SELF."'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='update' value='edit_notes'>
						<input type='hidden' name='refnum' value='$refnum'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Edit Notes</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><textarea name='notes' rows='5' style='width: 180px'>$notes</textarea></td>
						</tr>
						<tr>
							<td align='right'>
								<input type='submit' name='key' value='&laquo View Workshop'>
								<input type='submit' value='Update &raquo'>
							</td>
						</tr>
					</table>
					</form>";
	return $OUTPUT;

}



function check_in($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 0, 9, "Invalid reference number.");

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}



	db_conn("cubit");
	$sql = "SELECT * FROM workshop WHERE refnum='$refnum'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve item from the workshop.");
	$wsData = pg_fetch_array($wsRslt);

	// retrieve customer name
	db_conn("cubit");
	$sql = "SELECT surname FROM customers WHERE cusnum='$wsData[cusnum]'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer name from Cubit.");
	$surname = pg_fetch_result($cusRslt, 0);

	$status = explode(",", $wsData["status"]);
	$status = $status[count($status)-1];

	$OUTPUT = "
					<h3>View workshop</h3>
					<form method='POST' action='".SELF."'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='update' value='check_in'>
						<input type='hidden' name='refnum' value='$refnum'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Check in</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Status</td>
							<td>$status</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference number</td>
							<td>$wsData[refnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td>$surname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Stock Code/Name</td>
							<td>$wsData[stkcod]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Serial Number</td>
							<td>$wsData[serno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td>$wsData[description]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Notes</td>
							<td>".nl2br(base64_decode($wsData["notes"]))."</td>
						</tr>
						<tr>
							<td colspan='2' align='right'>
								<input type='submit' name='key' value='&laquo View Workshop'>
								<input type='submit' value='Confirm &raquo'>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}



function check_out($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 0, 9, "Invalid reference number");

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}



	db_conn("cubit");
	$sql = "SELECT * FROM workshop WHERE refnum='$refnum'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve workshop item from Cubit.");
	$wsData = pg_fetch_array($wsRslt);

	// Supplier dropdown
	db_conn("cubit");
	$sql = "SELECT * FROM suppliers ORDER BY supname ASC";
	$supRslt = db_exec($sql) or errDie("Unable to retrieve suppliers from Cubit.");

	$supdn = "<select name=supname style='width: 180px'>";
	while ($supData = pg_fetch_array($supRslt)) {
		$supdn .= "<option value='$supData[supname]'>$supData[supname]</option>";
	}

	// retrieve customer name
	db_conn("cubit");
	$sql = "SELECT surname FROM customers WHERE cusnum='$wsData[cusnum]'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer name from Cubit.");
	$surname = pg_fetch_result($cusRslt, 0);

	// Retrieve the default workshop conditions
	db_conn("cubit");
	$sql = "SELECT value FROM workshop_settings WHERE setting='workshop_conditions' AND div='".USER_DIV."'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve default workshop conditions");
	$conditions = pg_fetch_result($wsRslt, 0);

	$OUTPUT = "
					<h3>View workshop</h3>
					<form method='POST' action='".SELF."'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='update' value='check_out'>
						<input type='hidden' name='refnum' value='$refnum'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Check out</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Where</td>
							<td>$supdn</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Workshop conditions</td>
							<td><textarea name='conditions' rows='5' style='width: 180px'>$conditions</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference number</td>
							<td>$wsData[refnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td>$surname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Stock Code/Name</td>
							<td>$wsData[stkcod]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Serial Number</td>
							<td>$wsData[serno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td>$wsData[description]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Notes</td>
							<td>".nl2br(base64_decode($wsData["notes"]))."</td>
						</tr>
						<tr>
							<td colspan='2' align='right'>
								<input type='submit' name='key' value='&laquo View Workshop'>
								<input type='submit' value='Confirm &raquo'>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}



function receipt($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($refnum, "num", 1, 9, "Invalid reference number.");
	$v->isOk($type, "string", 1, 255, "Invalid receipt type.");
	if (isset($supname)) $v->isOk($supname, "string", 1, 255, "Invalid supplier name.");
	if (isset($conditions)) $v->isOk($conditions, "string", 1, 255, "Invalid workshop conditions.");


	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}



	// Retrieve the workshop item information
	db_conn("cubit");
	$sql = "SELECT * FROM workshop WHERE refnum='$refnum'";
	$wsRslt = db_exec($sql) or errDie("Unable to retrieve workshop information from Cubit.");
	$wsData = pg_fetch_array($wsRslt);

	switch ($type) {
		case "receipt":
			// Retrieve the supplier information
			db_conn("cubit");
			$sql = "SELECT * FROM suppliers WHERE supname='$supname'";
			$supRslt = db_exec($sql) or errDie("Unable to retrieve suppliers from Cubit.");
			$supData = pg_fetch_array($supRslt);

			$OUTPUT = "
							<center>
							<table border='1' cellpadding='10' cellspacing='1' width='750'>
								<tr>
									<td valign='top' colspan='2' width='100%'><h1>".COMP_NAME."</h1></td>
								</tr>
								<tr>
									<td valign='top' width='50%'>
										<b>Workshop receipt for</b><br>
										$supData[supname]<br>
										".nl2br($supData["supaddr"])."<br><br>
										Tel: $supData[tel]<br>
										Fax: $supData[fax]
									</td>
									<td valign='top' width='50%'><b>Date:</b>".date("Y-m-d")."</td>
								</tr>
								<tr>
									<td valign='top' colspan='2' width='100%'><b>Description:</b> $wsData[description]</td>
								</tr>
								<tr>
									<td valign='top' colspan='2' width='100%'><b>Serial No:</b> $wsData[serno]</td>
								</tr>
								<tr>
									<td valign='top' colspan='2' width='100%'><b>Workshop Conditions:</b><br>$conditions</td>
								</tr>
								<tr>
									<td valign=top colspan=2 width=100%>
										Received by: _____________________
									</td>
								</tr>
							</table>
							</center>";
			break;

		case "delivery_note":
			db_conn("cubit");
			$sql = "SELECT * FROM customers WHERE cusnum='$wsData[cusnum]'";
			$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer number from Cubit.");
			$cusData = pg_fetch_array($cusRslt);

			db_conn("cubit");
			$sql = "SELECT value FROM workshop_settings WHERE setting='workshop_conditions'";
			$wcRslt = db_exec($sql) or errDie("Unable to retrieve workshop settings from Cubit.");
			$workshop_conditions = pg_fetch_result($wcRslt, 0);

			$OUTPUT = "
							<center>
							<table border='1' cellpadding='10' cellspacing='1' width='750'>
								<tr>
									<td valign='top' colspan='2' width='100%'><h1>".COMP_NAME."</h1></td>
								</tr>
								<tr>
									<td valign='top' width='50%'>
										<b>Workshop receipt for:</b><br>
										$cusData[title]. $cusData[init] $cusData[surname]<br>
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
									<td valign='top' colspan='2' width='100%'><b>Description:</b> $wsData[description]</td>
								</tr>
								<tr>
									<td valign='top' colspan='2' width='100%'><b>Serial No:</b> $wsData[serno]</td>
								</tr>
								<tr>
									<td valign='top' colspan='2' width='100%'>
										<b>Workshop Conditions:</b><br>
										$workshop_conditions
									</td>
								</tr>
								<tr>
									<td valign=top colspan=2 width=100%>
										Workshop Sign: _____________________
									</td>
								</tr>
							</table>
							</center>";
			break;
	}
	require("tmpl-print.php");
}



function workshop_report($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($report, "string", 1, 255, "Invalid report type selected.");
	if (isset($date_year)) $v->isOk($date_year, "num", 4, 4, "Invalid year. (YYYY-MM-DD)");
	if (isset($date_month)) $v->isOk($date_month, "num", 2, 2, "Invalid month. (YYYY-MM-DD)");
	if (isset($date_day)) $v->isOk($date_day, "num", 2, 2, "Invalid day. (YYYY-MM-DD)");
	if (isset($age)) $v->isOk($age, "num", 1, 9, "Invalid age selection.");


	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return display($confirm);
	}

	// See if we should ask the user the date or age
	if (!isset($date_year) && !isset($date_month) && !isset($date_year) && !isset($age)) {
		switch ($report) {
			case "age":
				$input = "
							<td>Age</td>
							<td align='right'>From <input type='text' name='age' size='2'> days ago</td>";
				break;
			case "date":
				$input = "
							<td>Date</td>
							<td>".mkDateSelect("date")."</td>";
				break;
		}
		$OUTPUT = "
						<h3>View workshop<h3>
						<form method='POST' action='".SELF."'>
							<input type='hidden' name='key' value='workshop_report'>
							<input type='hidden' name='report' value='$report'>
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'>Workshop report</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								$input
							</tr>
							<tr>
								<td colspan='2' align='right'>
									<input type='submit' name='key' value='&laquo View workshop'>
									<input type='submit' value='Confirm &raquo'>
								</td>
							</tr>
						</table>
						</form>";

	} else {
		switch ($report) {
			case "age":
				$sql = "SELECT * FROM workshop WHERE age(cdate)<='$age Days' ORDER BY refnum DESC";
				break;
			case "date":
				// rebuild the date
				$date = "$date_year-$date_month-$date_day";

				$sql = "SELECT * FROM workshop WHERE cdate='$date' ORDER BY refnum DESC";
				break;
		}
		// Execute the query
		db_conn("cubit");
		$wsRslt = db_exec($sql) or errDie("Unable to retrieve workshop items from Cubit.");

		// Display a message if no items were found
		if (pg_num_rows($wsRslt) == 0) {
			$items = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='7'>No items found.</td>
						</tr>
					";
		} else {
			$items = "";
		}

		$i = 0;
		while ($wsData = pg_fetch_array($wsRslt)) {

			$i++;

			// Retrieve the customer name
			db_conn("cubit");
			$sql = "SELECT surname FROM customers WHERE cusnum='$wsData[cusnum]'";
			$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer name from Cubit.");
			$surname = pg_fetch_result($cusRslt, 0);

			// Retrieve the current item status
			$status = explode(",", $wsData["status"]);
			$status = $status[count($status)-1];

			// Items display
			$items .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$wsData[refnum]</td>
								<td>$wsData[cdate]</td>
								<td>$surname</td>
								<td>$wsData[stkcod]</td>
								<td>$wsData[serno]</td>
								<td>$wsData[description]</td>
								<td style='width:180px'>
								<center><b>$status</b></center><br>
								".nl2br(base64_decode($wsData["notes"]))."
								</td>
							</tr>";
		}

		$OUTPUT = "
						<center>
						<h3>Workshop Report</h3>
						<form method='POST' action='".SELF."'>
						<table ".TMPL_tblDflts." width='750'>
							<tr>
								<th>Ref no</th>
								<th>Date</th>
								<th>Customer</th>
								<th>Stock Code/Name</th>
								<th>Serial number</th>
								<th>Description</th>
								<th style='width:180px'>Status/Notes</th>
							</tr>
							$items
							<tr>
								<td colspan='7' align='center'><input type='submit' name='key' value='&laquo View workshop'></td>
							</tr>
						</table>
						</center>";
	}
	return $OUTPUT;

}


?>
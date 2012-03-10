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
foreach ($HTTP_GET_VARS as $key => $val) {
	$HTTP_POST_VARS[$key] = $val;
}
error_reporting(E_ALL);
// Decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "receipt":
			$OUTPUT = receipt($HTTP_POST_VARS);
			break;
		case "receipt-print":
			receipt-print($HTTP_POST_VARS);
			break;
		case "workshop-report":
			$OUTPUT = workshop-report($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = enter();
}

// Quick links
$OUTPUT .= "<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
  <tr><th>Quick Links</th><tr>
  <tr class=datacell><td><a href='workshop-view.php'>View workshop</td></tr>
  <tr class=datacell><td><a href='customers-new.php'>Add Customer<a></td></tr>
  <tr class=datacell><td><a href='stock-add.php'>Add Stock</a></td></tr>
  <tr class=datacell><td><a href='main.php'>Main Menu</a></td></tr>
</table>";

require ("template.php");

function enter($errors="")
{
	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;

	$fields["search_cus"] = "";
	$fields["assetid"] = "";
	$fields["cusnum"] = "";
	$fields["serno"] = "";
	$fields["description"] = "";
	$fields["conditions"] = "";
	$fields["notes"] = "";
	$fields["ex_year"] = date("Y");
	$fields["ex_month"] = date("m");
	$fields["ex_day"] = date("d");

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
	$stkdn = "
	<select name='assetid' style='width:180px'>
	  <option value='0'>Please select</th>";
	$sql = "SELECT * FROM cubit.assets WHERE div='".USER_DIV."' ORDER BY des ASC";
	$stkRslt = db_exec($sql) or errDie("Unable to retrieve the stock from Cubit.");
	$asset_id = 0;
	while ($stkData = pg_fetch_array($stkRslt)) {
		$selected = fsel($asset_id == $stkData["id"]);
		$stkdn .= "<option value='$stkData[id]' $selected>$stkData[des] ($stkData[serial])</option>";
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

	$OUTPUT = "<h3>Add to workshop</h3>
	$errors
	<form method=post action='".SELF."' name='frm_ws'>
	<input type=hidden name=key value='confirm'>
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
	    <td>".REQ." Asset</td>
	    <td>$stkdn</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	  	<td>Expected Date</td>
	  	<td>".mkDateSelect("ex", $ex_year, $ex_month, $ex_day)."</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".REQ."Description</td>
	    <td><input type=text name=description value='$description' style='width:180px'></td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".REQ."Workshop Conditions</td>
	    <td><textarea name=conditions rows=5 style='width:180px'>$conditions</textarea></td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>Notes</td>
	    <td><textarea name=notes rows=5 style='width:180px'>$notes</textarea></td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right>
	      <input type=submit value='Confirm &raquo'>
	    </td>
	  </tr>
	</table>";

	return $OUTPUT;
}

function confirm($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;
	$v->isOk($assetid, "num", 1, 9, "Invalid stock id (dropdown)");
	if ($assetid == 0) {
		$v->addError(0, "Please select an asset");
	}
	$v->isOk($cusnum, "num", 0, 9, "Invalid customer number (dropdown)");
	$v->isOk($description, "string", 1, 255, "Invalid description.");
	$v->isOk($conditions, "string", 1, 255, "Invalid workshop conditions.");

	if ($cusnum == 0) {
		$v->addError(0, "Please select a customer.");
	}

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
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
	$sql = "SELECT * FROM cubit.assets WHERE id='$assetid'";
	$stkRslt = db_exec($sql) or errDie("Unable to retrieve stock from Cubit.");
	$stock = pg_fetch_result($stkRslt, 0);

	$ex_date = "$ex_year-$ex_month-$ex_day";

	$OUTPUT = "
	<h3>Add to workshop</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='write'>
	<input type=hidden name=assetid value='$assetid' />
	<input type=hidden name=cusnum value='$cusnum' />
	<input type=hidden name=description value='$description' />
	<input type=hidden name=conditions value='$conditions' />
	<input type=hidden name=notes value='$notes' />
	<input type='hidden' name='ex_date' value='$ex_date' />
	<table ".TMPL_tblDflts.">
	  <tr>
	    <th colspan=2>Confirm</th>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>Customer</td>
	    <td>$customer</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>Asset</td>
	    <td>$stock[des] ($stock[serial])</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	  	<td>Expected Date</td>
	  	<td>$ex_date</td>
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
	    <td colspan=2 align=right>
	      <input type=submit name=key value='&laquo Correction'>
	      <input type=submit value='Write &raquo'>
	    </td>
	  </tr>
	</table>";

	return $OUTPUT;
}

function write($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;
	$v->isOk($assetid, "num", 0, 9, "Invalid stock id (dropdown)");
	if ($assetid == 0) {
		$v->addError(0, "Please select an asset");
	}
	$v->isOk($cusnum, "num", 0, 9, "Invalid customer number (dropdown)");
	$v->isOk($description, "string", 1, 255, "Invalid description.");

	if ($cusnum == 0) {
		$v->addError(0, "Please select a customer.");
	}

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return enter($confirm);
	}

	// See which stock selection we made
	$sql = "SELECT * FROM cubit.assets WHERE id='$assetid'";
	$stkRslt = db_exec($sql) or errDie("Unable to retrieve stock from Cubit.");
	$stock = pg_fetch_array($stkRslt);

	$sql = "INSERT INTO workshop (asset_id, stkcod, cusnum, serno, description, notes,
				status, cdate, active, e_date)
			VALUES ('$assetid', '$stock[des]', '$cusnum', '$stock[serial]', '$description',
				'".base64_encode($notes)."', 'Present', current_date, 'true',
				'$ex_date')";
	$wsRslt = db_exec($sql) or errDie("Unable to insert workshop data into Cubit.");

	$sql = "INSERT INTO hire.service_history(asset_id, description)
			VALUES ('$assetid', '$stock[des]')";
	db_exec($sql) or errDie("Unable to add to service history.");

	if (pg_affected_rows($wsRslt) == 0) {
		return $OUTPUT = "<center><li class=err>Could not be added to the workshop</li></center>";
	} else {
		$refnum = pglib_lastid("workshop", "refnum");

		return $OUTPUT = "<li>Successfully added to workshop</li> <script>printer(\"".SELF."?key=receipt&cusnum=$cusnum&refnum=$refnum&description=$description&conditions=$conditions\");</script>";
	}
}

function receipt($HTTP_POST_VARS) {
	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;
	$v->isOk($cusnum, "num", 0, 9, "Invalid customer number (dropdown)");
	$v->isOk($description, "string", 1, 255, "Invalid description.");
	$v->isOk($refnum, "num", 0, 255, "Invalid reference number.");
	$v->isOk($conditions, "string", 1, 255, "Invalid workshop conditions.");

	// Display Errors
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
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

	$OUTPUT = "<center>
	<table border=1 cellpadding=10 cellspacing=1 width=750>
	  <tr>
	    <td valign=top colspan=2 width=100%><h1>".COMP_NAME."</h1></td>
	  </tr>
	  <tr>
	    <td valign=top width=50%>
	      <b>Workshop receipt for:</b><br>
	      $customer<br>
	      ".nl2br($cusData["addr1"])."<br><br>
	      Tel: $cusData[tel]<br>
	      Fax: $cusData[fax]<br>
	      Cell: $cusData[cellno]<br>
	      Bussiness Tel: $cusData[bustel]
	    </td>
	    <td valign=top width=50%>
	      <b>Date:</b> ".date("Y-m-d")."<br>
	      <b>Reference Number:</b> $refnum
	    </td>
	  </tr>
	  <tr>
	    <td valign=top colspan=2 width=100%>
	      <b>Description:</b> $description
	    </td>
	  </tr>
	  <tr>
	    <td valign=top colspan=2 width=100%>
	      <b>Workshop Conditions:</b><br>
	      $conditions
	    </td>
	  </tr>
	  <tr>
	    <td valign=top colspan=2 width=100%>
	      Workshop Sign: _____________________
	    </td>
	  </tr>
	</table>
	</center>";

	require ("tmpl-print.php");
}
?>

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

require ("../settings.php");
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
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");




function enter($errors="")
{

	// Retrieve the settings from Cubit
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='invoices' AND div='".USER_DIV."'";
	$invRslt = db_exec($sql) or errDie("Unable to retrieve the invoices template setting from Cubit.");
	$invoices_db = pg_fetch_result($invRslt, 0);

	$sql = "SELECT filename FROM template_settings WHERE template='statements' AND div='".USER_DIV."'";
	$stmntRslt = db_exec($sql) or errDie("Unable to retrieve the statement template setting from Cubit.");
	$statements_db = pg_fetch_result($stmntRslt, 0);

	$sql = "SELECT filename FROM template_settings WHERE template='reprints' AND div='".USER_DIV."'";
	$rprntRslt = db_exec($sql) or errDie("Unable to retrieve pdf reprint template setting from Cubit.");
	$reprints_db = pg_fetch_result($rprntRslt, 0);

//	$invoices_ar = array (
//		"PDF Tax Invoice"=>"pdf/pdf-tax-invoice.php",
//		"HTML Tax Invoice"=>"invoice-print.php",
//	);
//
//	$statements_ar = array (
//		"PDF Statement"=>"pdf/pdf-statement.php",
//		"Default PDF"=>"pdf/cust-pdf-stmnt.php"
//	);
//
//	$reprints_ar = array (
//		"PDF Tax Invoice"=>"new",
//		"Default PDF"=>"default"
//	);

	$invoices_ar = array (
		"PDF Tax Invoice" => "pdf/pdf-tax-invoice.php",
		"HTML Tax Invoice" => "invoice-print.php",
	);

	$statements_ar = array (
		"New Format PDF Statement" => "pdf/pdf-statement.php",
		"Old Format PDF" => "pdf/cust-pdf-stmnt.php"
	);

	$reprints_ar = array (
		"New Format PDF Tax Invoice" => "new",
		"Old Format PDF Tax Invoice" => "default"
	);

	$invoices = "";
	foreach ($invoices_ar as $key=>$val) {
		if ($invoices_db == $val) {
			$selected = "checked";
		} else {
			$selected = "";
		}
		$invoices .= "<input type=radio name='invoices' value='$key::$val' $selected>$key<br>";
	}

	$statements = "";
	foreach ($statements_ar as $key=>$val) {
		if ($statements_db == $val) {
			$selected = "checked";
		} else {
			$selected = "";
		}
		$statements .= "<input type=radio name='statements' value='$key::$val' $selected>$key<br>";
	}

	$reprints = "";
	foreach ($reprints_ar as $key=>$val) {
		if ($reprints_db == $val) {
			$selected = "checked";
		} else {
			$selected = "";
		}
		$reprints .= "<input type=radio name='reprints' value='$key::$val' $selected>$key<br>";
	}

	$OUTPUT = "
		<h3>Template Settings</h3>
		$errors
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Option</th>
				<th>Layout</th>
				<th>Preview</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoices / Credit Notes</td>
				<td valign='top'>$invoices</td>
				<td valign='middle'>
					<a href='pdf-tax-invoice-prev.png' target='blank'>Preview PDF Tax Invoice</a><br>
					<a href='html-tax-invoice-prev.png' target='blank'>Preview HTML Tax Invoice</a>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Statements</th>
				<td valign='top'>$statements</td>
				<td valign='middle'>
					<a href='pdf-statement-prev.png' target='blank'>Preview PDF Statement</a><br>
					<a href='default-statement-prev.png' target='blank'>Preview Default PDF Statement</a>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice PDF Reprints</td>
				<td valign='top'>$reprints</td>
				<td>&nbsp</td>
			</tr>
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Confirm &raquo'\></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../cust-credit-stockinv.php", "New Invoice"),
			ql("../purchase-new.php", "New Purchase")
		);
	return $OUTPUT;

}




function confirm($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($invoices, "string", 1, 255, "Invalid invoice template.");
	$v->isOk($statements, "string", 1 ,255, "Invalid statement template.");
	$v->isOk($reprints, "string", 1, 255, "Invalid pdf reprints template.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
	}



	$invoices_h = explode("::", $invoices);
	$invoices_h = $invoices_h[0];
	$statements_h = explode("::", $statements);
	$statements_h = $statements_h[0];
	$reprints_h = explode("::", $reprints);
	$reprints_h = $reprints_h[0];

	$OUTPUT = "
		<h3>Template Settings</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='invoices' value='$invoices'>
			<input type='hidden' name='statements' value='$statements'>
			<input type='hidden' name='reprints' value='$reprints'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Option</th>
				<th>Layout</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoices / Credit Notes</td>
				<td>$invoices_h</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Statements</td>
				<td>$statements_h</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>PDF Reprints</td>
				<td>$reprints_h</td>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form><br>"
		.mkQuickLinks(
			ql("../cust-credit-stockinv.php", "New Invoice"),
			ql("../purchase-new.php", "New Purchase")
		);
	return $OUTPUT;

}




function write($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($invoices, "string", 1, 255, "Invalid invoice template.");
	$v->isOk($statements, "string", 1 ,255, "Invalid statement template.");
	$v->isOk($reprints, "string", 1, 255, "Invalid pdf reprints template.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
	}


	$invoices_fn = explode("::", $invoices);
	$invoices_fn = $invoices_fn[1];
	$statements_fn = explode("::", $statements);
	$statements_fn = $statements_fn[1];
	$reprints_fn = explode("::", $reprints);
	$reprints_fn = $reprints_fn[1];


	// See if we already have values for the current div
	db_conn("cubit");

	$sql = "SELECT * FROM template_settings WHERE div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");

	if (pg_num_rows($tsRslt) == 0) {
		$sql = "INSERT INTO template_settings (template, filename, div) VALUES ('statements', '$statements_fn', '".USER_DIV."')";
		$tsRslt = db_exec($sql) or errDie("Unable to update the statements template setting to Cubit.");

		$sql = "INSERT INTO template_settings (template, filename, div) VALUES ('invoices', '$invoices_fn', '".USER_DIV."')";
		$tsRslt = db_exec($sql) or errDie("Unable to update the invoice template settings to Cubit.");

		$sql = "INSERT INTO template_settings (template, filename, div) VALUES ('reprints', '$reprints_fn', '".USER_DIV."')";
		$tsRslt = db_exec($sql) or errDie("Unable to update the invoice template settings to Cubit.");
	} else {
		$sql = "UPDATE template_settings SET filename='$statements_fn' WHERE div='".USER_DIV."' AND template='statements'";
		$tsRslt = db_exec($sql) or errDie("Unable to update the statements template settings to Cubit.");

		$sql = "UPDATE template_settings SET filename='$invoices_fn' WHERE div='".USER_DIV."' AND template='invoices'";
		$tbRslt = db_exec($sql) or errDie("Unable to update the invoice template settings to Cubit.");

		$sql = "UPDATE template_settings SET filename='$reprints_fn' WHERE div='".USER_DIV."' AND template='reprints'";
		$tsRslt = db_exec($sql) or errDie("Unable to update the statements template settings to Cubit.");
	}

	$OUTPUT = "
		<li>Successfully updated the template settings.</li><br>"
		.mkQuickLinks(
			ql("../cust-credit-stockinv.php", "New Invoice"),
			ql("../purchase-new.php", "New Purchase")
		);
	return $OUTPUT;

}


?>
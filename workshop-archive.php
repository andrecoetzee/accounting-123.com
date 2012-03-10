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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "update":
			$OUTPUT = update($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = display();
}

require ("template.php");

function display()
{
	global $HTTP_POST_VARS;
	extract ($HTTP_POST_VARS);
	
	$fields = array ();
	$fields["customer"] = "";
	$fields["stock"] = "";
	$fields["frm_day"] = "01";
	$fields["frm_month"] = date("m");
	$fields["frm_year"] = date("Y");
	$fields["to_day"] = date("d");
	$fields["to_month"] = date("m");
	$fields["to_year"] = date("Y");
	
	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}
	
	$from_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";
	
	db_conn("cubit");
	$sql = "SELECT * FROM workshop WHERE active='false' AND cdate>='$from_date' AND cdate<='$to_date' ORDER BY cdate DESC";
	$rslt = db_exec($sql) or errDie("Unable to retrieve workshop items from Cubit.");
	
	if (!pg_num_rows($rslt)) {
		$ws_out = "<tr bgcolor='".TMPL_tblDataColor1."'>
			<td colspan='8'>No items were found in the workshop archive for the current date selection</td>
		</tr>";
	} else {
		$ws_out = "";
	}
	$i = 0;

	while ($ws_data = pg_fetch_array($rslt)) {
		// Alternate the background color
		$bgcolor = ($i % 2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		db_conn("cubit");
		$sql = "SELECT surname FROM customers WHERE cusnum='$ws_data[cusnum]'";
		$cus_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
		$customers = pg_fetch_result($cus_rslt, 0);
		
		$ws_out = "<tr bgcolor='$bgcolor'>
			<td>$ws_data[refnum]</td>
			<td>$ws_data[cdate]</td>
			<td><a href='cust-det.php?cusnum=$ws_data[cusnum]' target=_blank>$customers</a></td>
			<td>$ws_data[stkcod]</td>
			<td>$ws_data[serno]</td>
			<td>$ws_data[description]</td>
			<td>".nl2br(base64_decode(($ws_data["notes"])))."</td>
			<td nowrap>
				<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
					<tr>
						<td nowrap><a href='?key=update&update=return'>Return to Workshop</a></td>
					</tr>
				</table>
			</td>";
	}

	
	$OUTPUT = "<center>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='display'>
	<h3>Workshop Archive</h3>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan='4'>Filter</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor2."'>
			<td colspan='4' align='center'><b>Date Range</b></td>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>From</td>
			<td>
				<input type='text' name='frm_day' value='$frm_day' size='2'>-
				<input type='text' name='frm_month' value='$frm_month' size='2'>-
				<input type='text' name='frm_year' value='$frm_year' size='4'>
			</td>
			<td>To</td>
			<td>
				<input type='text' name='to_day' value='$to_day' size='2'>-
				<input type='text' name='to_month' value='$to_month' size='2'>-
				<input type='text' name='to_year' value='$to_year' size='4'>
			</td>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td colspan='4' align='center'>
				<input type='submit' value='Continue &raquo'>
			</td>
		</tr>
	</table>
	<p>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr>
			<th>Ref no.</th>
			<th>Date</th>
			<th>Customer</th>
			<th>Stock Code/Name</th>
			<th>Serial Number</th>
			<th>Description</th>
			<th>Notes</th>
			<th>Options</th>
		</tr>
		$ws_out
	</table>
	</center>";
	
	return $OUTPUT;
}

function update($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);
	
	switch ($update) {
		case "return":
			db_conn("cubit");
			$sql = "UPDATE workshop SET active='true' WHERE refnum='$refnum'";
			$rslt = db_exec($sql) or errDie("Unable to update workshop information to Cubit.");
	}
	
	return display();
}
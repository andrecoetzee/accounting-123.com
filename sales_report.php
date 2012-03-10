<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "xls":
			$OUTPUT = xls();
			break;
	}
} else {
	$OUTPUT = display();
}

$OUTPUT .= "<p></p>".
	mkQuickLinks (
		ql("sorder-new.php", "New Sales Order"),
		ql("sorder-invoiced.php", "View Invoiced Sales Orders"),
		ql("sorder-due.php", "View Due Sales Orders")
	);


require ("template.php");




function display()
{

	extract($_REQUEST);

	$fields = array();
	$fields["report_type"] = "";
	$fields["id"] = 0;
	$fields["frm_year"] = date("Y");
	$fields["frm_day"] = "01";
	$fields["frm_month"] = date("m");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$frm_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";

	switch ($report_type) {
		case "cust":
			unset($dept_id);
			unset($cat_id);
			unset($class_id);
			break;
		case "dept":
			unset($cust_id);
			unset($cat_id);
			unset($class_id);
			break;
		case "cat":
			unset($cust_id);
			unset($dept_id);
			unset($class_id);
			break;
		case "class":
			unset($cust_id);
			unset($dept_id);
			unset($cat_id);
			break;
	}


	$report_types_list = array(
		"cust" =>"Customers",
		"dept" =>"Sales Department",
		"cat"  =>"Category",
		"class"=>"Classification",
	);

	$type_sel = "<select name='report_type' onchange='javascript:document.form.submit()' style='width: 100%'>";
	$type_sel .= "<option value='0'>[None]</option>";
	foreach ($report_types_list as $key=>$value) {
		if ($report_type == $key) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$type_sel.= "<option value='$key' $sel>$value</option>";
	}
	$type_sel.= "</select>";


	if(!isset($cust_id))
		$cust_id = "";
	if(!isset($dept_id))
		$dept_id = "";
	if(!isset($cat_id))
		$cat_id = "";
	if(!isset($class_id))
		$class_id = "";


	switch ($report_type) {
		case "cust":
			// Customers ------------------------------------------------------
			$sql = "SELECT * FROM cubit.customers";
			$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");

			$id_sel = "<select name='cust_id' onchange='javascript:document.form.submit()' style='width: 100%'>";
			$all_sel = ($cust_id == "all") ? "selected" : "";
			$id_sel.= "
				<option value='0'>[None]</option>
				<option value='all' $all_sel>[All]</option>";
			while ($cust_data = pg_fetch_array($cust_rslt)) {
				if ($cust_id == $cust_data["cusnum"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$id_sel .= "<option value='$cust_data[cusnum]' $sel>$cust_data[cusname] $cust_data[surname]</option>";
			}
			$id_sel .= "</select>";

		break;
		case "dept":
			// Departments ----------------------------------------------------
			$sql = "SELECT * FROM exten.departments";
			$dept_rslt = db_exec($sql) or errDie("Unable to retrieve departments.");

			$id_sel = "<select name='dept_id' onchange='javascript:document.form.submit()' style='width: 100%'>";
			$all_sel = ($dept_id == "all") ? "selected" : "";
			$id_sel.= "
				<option value='0'>[None]</option>
				<option value='all' $all_sel>[All]</option>";
			while ($dept_data = pg_fetch_array($dept_rslt)) {
				if ($dept_id == $dept_data["deptid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$id_sel .= "<option value='$dept_data[deptid]' $sel>$dept_data[deptname]</option>";
			}
			$id_sel .= "</select>";

		break;
		case "cat":
			// Categories -----------------------------------------------------
			$sql = "SELECT * FROM cubit.stockcat";
			$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

			$id_sel = "<select name='cat_id' onchange='javascript:document.form.submit()' style='width: 100%'>";
			$all_sel = ($cat_id == "all") ? "selected" : "";
			$id_sel.= "
				<option value='0'>[None]</option>
				<option value='all' $all_sel>[All]</option>";
			while ($cat_data = pg_fetch_array($cat_rslt)) {
				if ($cat_id == $cat_data["catid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$id_sel .= "<option value='$cat_data[catid]' $sel>$cat_data[cat]</option>";
			}
			$id_sel .= "</select>";

		break;
		case "class":
			// Classification -------------------------------------------------
			$sql = "SELECT * FROM cubit.stockclass";
			$class_rslt = db_exec($sql) or errDie("Unable to retrieve class.");

			$id_sel = "<select name='class_id' onchange='javascript:document.form.submit()' style='width: 100%'>";
			$all_sel = ($cust_id == "all") ? "selected" : "";
			$id_sel.= "
				<option value='0'>[None]</option>
				<option value='all' $all_sel>[All]</option>";
			while ($class_data = pg_fetch_array($class_rslt)) {
				if ($class_id == $class_data["clasid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$id_sel .= "<option value='$class_data[clasid]' $sel>$class_data[classname]</option>";
			}
			$id_sel .= "</select>";
		break;
		default:
			$id_sel = "";
		break;
	}

	$OUTPUT = "
		<center>
		<h3>Sales Report (Invoices Only)</h3>
		<form method='POST' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
				<td><b> To </b></td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td><input type='submit' value='Select'></td>
			</tr>
			<tr>
				<th colspan='4'>Report Options</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$type_sel</td>
				<td colspan='2'>$id_sel</td>
			</tr>
		</table>
		</form>";

	$inv_out = "";

	if ($cust_id || $dept_id || $cat_id || $class_id) {
	switch ($report_type) {
		case "cust":
			// Customer -------------------------------------------------------
			if ($cust_id != "all") {
				$report_sql = "cusnum='$cust_id'";
			} else {
				$report_sql = "";
			}
			break;
		case "dept":
			// Department -----------------------------------------------------
			if ($dept_id != "all") {
				$report_sql = "deptid='$dept_id'";
			} else {
				$report_sql = "";
			}
			break;
		case "cat":
			// Category -------------------------------------------------------
			if ($cat_id != "all") {
				$sql = "SELECT * FROM cubit.stockcat WHERE catid='$cat_id'";
				$cat_rslt = db_exec($sql) or errDie("Unable to retrieve stock category.");
				$cat_data = pg_fetch_array($cat_rslt);

				$sql = "SELECT * FROM cubit.stock WHERE catname='$cat_data[cat]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

				$item_ar = array();
				while ($stock_data = pg_fetch_array($stock_rslt)) {
					$item_ar[] = "stkid='$stock_data[stkid]'";
				}

				$item_sql = implode(" OR ", $item_ar);
				$sql = "SELECT * FROM cubit.inv_items WHERE $item_sql";
				$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

				$report_ar = array();
				while ($item_data = pg_fetch_array($item_rslt)) {
					$report_ar[] = "invid='$item_data[invid]'";
				}

				$report_sql = implode(" OR ", $report_ar);
			} else {
				$report_sql = "";
			}
			
			break;
		case "class":
			// Classification -------------------------------------------------
			if ($class_id != "all") {
				$sql = "SELECT * FROM cubit.stockclass WHERE clasid='$class_id'";
				$class_rslt = db_exec($sql) or errDie("Unable to retrieve stock class.");
				$class_data = pg_fetch_array($class_rslt);

				$sql = "SELECT * FROM cubit.stock WHERE classname='$class_data[classname]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

				$item_ar = array();
				while ($stock_data = pg_fetch_array($stock_rslt)) {
					$item_ar[] = "stkid='$stock_data[stkid]'";
				}

				$item_sql = implode(" OR ", $item_ar);
				$sql = "SELECT * FROM cubit.inv_items WHERE $item_sql";
				$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

				$report_ar = array();
				while ($item_data = pg_fetch_array($item_rslt)) {
					$report_ar[] = "invid='$item_data[invid]'";
				}

				$report_sql = implode(" OR ", $report_ar);
			} else {
				$report_sql = "";
			}
			break;
		}

		if (!empty($report_sql)) $report_sql .= " AND ";

		$sql = "SELECT * FROM cubit.invoices WHERE $report_sql odate BETWEEN '$frm_date' AND '$to_date'";
		$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

		$totals["discount"] = 0;
		$totals["total"] = 0;
		while ($inv_data = pg_fetch_array($inv_rslt)) {
			// Retrieve department
			$sql = "SELECT * FROM exten.departments WHERE deptid='$inv_data[deptid]'";
			$dept_rslt = db_exec($sql) or errDie("Unable to retrieve department.");
			$dept_data = pg_fetch_array($dept_rslt);

			$inv_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv_data[cusname] $inv_data[surname]</td>
					<td>$dept_data[deptname]</td>
					<td>$inv_data[invnum]</td>
					<td align='right'>".CUR."$inv_data[discount]</td>
					<td align='right'>".CUR."$inv_data[total]</td>
				</tr>";

			$totals["discount"] += $inv_data["discount"];
			$totals["total"] += $inv_data["total"];
		}

		$OUTPUT .= "
			<form method='post' action='".SELF."'>
				<input type='hidden' name='key' value='xls' />
				<input type='hidden' name='report_type' value='$report_type' />
				<input type='hidden' name='cust_id' value='$cust_id' />
				<input type='hidden' name='dept_id' value='$dept_id' />
				<input type='hidden' name='cat_id' value='$cat_id' />
				<input type='hidden' name='class_id' value='$class_id' />
				<input type='hidden' name='frm_year' value='$frm_year' />
				<input type='hidden' name='frm_month' value='$frm_month' />
				<input type='hidden' name='frm_day' value='$frm_day' />
				<input type='hidden' name='to_year' value='$to_year' />
				<input type='hidden' name='to_month' value='$to_month' />
				<input type='hidden' name='to_day' value='$to_day' />
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Customer</th>
					<th>Department</th>
					<th>Invoice No</th>
					<th>Discount</th>
					<th>Total</th>
				</tr>
				$inv_out
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='3'>&nbsp;</td>
					<td align='right'><b>".CUR.sprint($totals["discount"])."</b></td>
					<td align='right'><b>".CUR.sprint($totals["total"])."</b></td>
				</tr>
				<tr>
					<td colspan='5' align='center'><input type='submit' value='Export to Spreadsheet' /></td>
				</tr>
			</table>";
	}
	return $OUTPUT;

}



function xls()
{

	extract ($_REQUEST);

	$frm_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";

	if ($cust_id || $dept_id || $cat_id || $class_id) {
	switch ($report_type) {
		case "cust":
			// Customer -------------------------------------------------------
			if ($cust_id != "all") {
				$report_sql = "cusnum='$cust_id'";
			} else {
				$report_sql = "";
			}
			break;
		case "dept":
			// Department -----------------------------------------------------
			if ($dept_id != "all") {
				$report_sql = "deptid='$dept_id'";
			} else {
				$report_sql = "";
			}
			break;
		case "cat":
			// Category -------------------------------------------------------
			if ($cat_id != "all") {
				$sql = "SELECT * FROM cubit.stockcat WHERE catid='$cat_id'";
				$cat_rslt = db_exec($sql) or errDie("Unable to retrieve stock category.");
				$cat_data = pg_fetch_array($cat_rslt);

				$sql = "SELECT * FROM cubit.stock WHERE catname='$cat_data[cat]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

				$item_ar = array();
				while ($stock_data = pg_fetch_array($stock_rslt)) {
					$item_ar[] = "stkid='$stock_data[stkid]'";
				}

				$item_sql = implode(" OR ", $item_ar);
				$sql = "SELECT * FROM cubit.inv_items WHERE $item_sql";
				$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

				$report_ar = array();
				while ($item_data = pg_fetch_array($item_rslt)) {
					$report_ar[] = "invid='$item_data[invid]'";
				}

				$report_sql = implode(" OR ", $report_ar);
			}else {
				$report_sql = "";
			}
			break;
		case "class":
			// Classification -------------------------------------------------
			if ($class_id != "all") {
				$sql = "SELECT * FROM cubit.stockclass WHERE clasid='$class_id'";
				$class_rslt = db_exec($sql) or errDie("Unable to retrieve stock class.");
				$class_data = pg_fetch_array($class_rslt);

				$sql = "SELECT * FROM cubit.stock WHERE classname='$class_data[classname]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

				$item_ar = array();
				while ($stock_data = pg_fetch_array($stock_rslt)) {
					$item_ar[] = "stkid='$stock_data[stkid]'";
				}

				$item_sql = implode(" OR ", $item_ar);
				$sql = "SELECT * FROM cubit.inv_items WHERE $item_sql";
				$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

				$report_ar = array();
				while ($item_data = pg_fetch_array($item_rslt)) {
					$report_ar[] = "invid='$item_data[invid]'";
				}

				$report_sql = implode(" OR ", $report_ar);
			}else {
				$report_sql = "";
			}
			break;
		}

		if (strlen ($report_sql) < 1) 
			$report_sql = "TRUE";

		$sql = "SELECT * FROM cubit.invoices WHERE $report_sql AND odate BETWEEN '$frm_date' AND '$to_date'";
		$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

		$totals["discount"] = 0;
		$totals["total"] = 0;
		while ($inv_data = pg_fetch_array($inv_rslt)) {
			// Retrieve department
			$sql = "SELECT * FROM exten.departments WHERE deptid='$inv_data[deptid]'";
			$dept_rslt = db_exec($sql) or errDie("Unable to retrieve department.");
			$dept_data = pg_fetch_array($dept_rslt);

			$inv_out .= "
				<tr>
					<td>$inv_data[cusname] $inv_data[surname]</td>
					<td>$dept_data[deptname]</td>
					<td>$inv_data[invnum]</td>
					<td align='right'>".CUR."$inv_data[discount]</td>
					<td align='right'>".CUR."$inv_data[total]</td>
				</tr>";

			$totals["discount"] += $inv_data["discount"];
			$totals["total"] += $inv_data["total"];
		}

		$OUTPUT .= "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Customer</th>
					<th>Department</th>
					<th>Invoice No</th>
					<th>Discount</th>
					<th>Total</th>
				</tr>
				$inv_out
				<tr>
					<td colspan='3'>&nbsp;</td>
					<td align='right'><b>".CUR.sprint($totals["discount"])."</b></td>
					<td align='right'><b>".CUR.sprint($totals["total"])."</b></td>
				</tr>
			</table>";
	}

	include("xls/temp.xls.php");
	Stream("SalesReport", $OUTPUT);

}


?>
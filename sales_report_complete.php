<?php
require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["report_type"] = "";
	$fields["id"] = 0;

	extract ($fields, EXTR_SKIP);

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
		"class"=>"Classification"
	);

	$type_sel = "<select name='report_type'
				 onchange='javascript:document.form.submit()'
				 style='width: 100%'>";
	$type_sel.= "<option value='0'>[None]</option>";

	foreach ($report_types_list as $key=>$value) {
		if ($report_type == $key) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$type_sel.= "<option value='$key' $sel>$value</option>";
	}
	$type_sel.= "</select>";

	switch ($report_type) {
		case "cust":
			// Customers ------------------------------------------------------
			$sql = "SELECT * FROM cubit.customers";
			$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");

			$id_sel = "<select name='cust_id'
					   onchange='javascript:document.form.submit()'
					   style='width: 100%'>";
			$id_sel.= "<option value='0'>[None]</option>";
			while ($cust_data = pg_fetch_array($cust_rslt)) {
				if ($cust_id == $cust_data["cusnum"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$id_sel.= "<option value='$cust_data[cusnum]' $sel>
						       $cust_data[cusname] $cust_data[surname]
						   </option>";
			}
			$id_sel.= "</select>";

		break;
		case "dept":
			// Departments ----------------------------------------------------
			$sql = "SELECT * FROM exten.departments";
			$dept_rslt = db_exec($sql) or errDie("Unable to retrieve departments.");

			$id_sel = "<select name='dept_id'
					   onchange='javascript:document.form.submit()'
					   style='width: 100%'>";
			$id_sel.= "<option value='0'>[None]</option>";
			while ($dept_data = pg_fetch_array($dept_rslt)) {
				if ($dept_id == $dept_data["deptid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$id_sel.= "<option value='$dept_data[deptid]' $sel>
						       $dept_data[deptname]
						   </option>";
			}
			$id_sel .= "</select>";

		break;
		case "cat":
			// Categories -----------------------------------------------------
			$sql = "SELECT * FROM cubit.stockcat";
			$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

			$id_sel = "<select name='cat_id'
					   onchange='javascript:document.form.submit()'
					   style='width: 100%'>";
			$id_sel.= "<option value='0'>[None]</option>";
			while ($cat_data = pg_fetch_array($cat_rslt)) {
				if ($cat_id == $cat_data["catid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$id_sel.= "<option value='$cat_data[catid]' $sel>
						       $cat_data[cat]
						   </option>";
			}
			$id_sel.= "</select>";

		break;
		case "class":
			// Classification -------------------------------------------------
			$sql = "SELECT * FROM cubit.stockclass";
			$class_rslt = db_exec($sql) or errDie("Unable to retrieve class.");

			$id_sel = "<select name='class_id'
					   onchange='javascript:document.form.submit()'
					   style='width: 100%'>";
			$id_sel.= "<option value='0'>[None]</option>";
			while ($class_data = pg_fetch_array($class_rslt)) {
				if ($class_id == $class_data["clasid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$id_sel.= "<option value='$class_data[clasid]' $sel>
						       $class_data[classname]
						   </option>";
			}
			$id_sel.= "</select>";
		break;
		default:
			$id_sel = "";
		break;
	}

	$OUTPUT = "<center>
	<h3>Sales Report</h3>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Report Options</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'><td>$type_sel</td></tr>
		<tr bgcolor='".bgcolorg()."'><td>$id_sel</td></tr>
	</table>
	</form>";

	if ($cust_id || $dept_id || $cat_id || $class_id) {
	switch ($report_type) {
		case "cust":
			// Customer -------------------------------------------------------
			$report_sql = "cusnum='$cust_id'";
			break;
		case "dept":
			// Department -----------------------------------------------------
			$report_sql = "deptid='$dept_id'";
			break;
		case "cat":
			// Category -------------------------------------------------------
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

			break;
		case "class":
			// Classification -------------------------------------------------
			$sql = "SELECT * FROM cubit.stockclass WHERE clasid='$class_id'";
			$class_rslt = db_exec($sql) or errDie("Unable to retrieve stock class.");
			$class_data = pg_fetch_array($class_rslt);

			$sql = "SELECT * FROM cubit.stock
					WHERE classname='$class_data[classname]'";
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
			break;
		}

		$sql = "SELECT * FROM cubit.invoices WHERE $report_sql";
		$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

		$totals["discount"] = 0;
		$totals["total"] = 0;
		$invit_out = "";
		while ($inv_data = pg_fetch_array($inv_rslt)) {
			// Retrieve department
			$sql = "SELECT * FROM exten.departments WHERE deptid='$inv_data[deptid]'";
			$dept_rslt = db_exec($sql) or errDie("Unable to retrieve department.");
			$dept_data = pg_fetch_array($dept_rslt);

			// Retrieve invoice items
			$sql = "SELECT * FROM cubit.inv_items WHERE invid='$inv_data[invid]'";
			$invit_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

			while ($invit_data = pg_fetch_array($invit_rslt)) {

				if ($invit_data["stkid"]) {
					$sql = "SELECT * FROM cubit.stock
							WHERE stkid='$invit_data[stkid]'";
					$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
					$stock_data = pg_fetch_array($stock_rslt);

					$stock_code = $stock_data["stkcod"];
					$stock_desc = $stock_data["stkdes"];
				} else {
					$sql = "SELECT accname FROM core.accounts
							WHERE accid='$invit_data[account]'";
					$acc_rslt = db_exec($sql)
						or errDie("Unable to retrieve accounts.");
					$accname = pg_fetch_result($acc_rslt, 0);

					$stock_code = $accname;
					$stock_desc = $invit_data["description"];
				}

				// Retrieve vatcode
				$sql = "SELECT * FROM cubit.vatcodes WHERE id='$invit_data[vatcode]'";
				$vc_rslt = db_exec($sql) or errDie("Unable to retrieve vatcode.");
				$vc_data = pg_fetch_array($vc_rslt);

				$invit_out .= "<tr bgcolor='".bgcolorg()."'>
					<td>$inv_data[cusname] $inv_data[surname]</td>
					<td>$dept_data[deptname]</td>
					<td>$inv_data[invid]</td>
					<td>$stock_data[stkcod]</td>
					<td>$stock_data[stkdes]</td>
					<td>$vc_data[code]</td>
					<td>$invit_data[qty]</td>
					<td align='right'>".CUR."$invit_data[unitcost]</td>
					<td align='right'>".CUR."$invit_data[disc]</td>
					<td align='right'>".CUR."$invit_data[amt]</td>
				</tr>";
			}

			$totals["discount"] += $inv_data["discount"];
			$totals["total"] += $inv_data["total"];
		}

		$OUTPUT .= "<table ".TMPL_tblDflts.">
			<tr>
				<th>Customer</th>
				<th>Department</th>
				<th>Invoice No</th>
				<th>Stock Code / Account</th>
				<th>Stock Description</th>
				<th>Vatcode</th>
				<th>Qty</th>
				<th>Unitcost</th>
				<th>Discount</th>
				<th>Amount</th>
			</tr>
			$invit_out
		</table>";
	}

	return $OUTPUT;

}
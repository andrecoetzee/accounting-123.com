<?php

require ("settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);
	switch ($button) {
		case "excel":
			$OUTPUT = excel();
			break;
	}
} elseif (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "select":
			$OUTPUT = select();
			break;
		case "display":
			$OUTPUT = display();
			break;
	}
} else {
	$OUTPUT = display();
}

require ("template.php");




function select()
{

	extract ($_REQUEST);

	// Retrieve stores
	$sql = "SELECT whid, whno, whname FROM exten.warehouses ORDER BY whno ASC";
	$stores_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");
	
	$stores_sel = "<select name='whid' style='width: 100%' />";
	while ($stores_data = pg_fetch_array($stores_rslt)) {
		$stores_sel .= "<option value='$stores_data[whid]'>($stores_data[whno]) $stores_data[whname]</option>";
	}
	$stores_sel .= "</select>";
	
	// Retrieve categories
	$sql = "SELECT catid, catcod, cat FROM cubit.stockcat ORDER BY cat ASC";
	$categories_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");
	
	$categories_sel = "<select name='catid style='width: 100%' />";
	while ($categories_data = pg_fetch_array($categories_rslt)) {
		$categories_sel .= "<option value='$categories_data[catid]'>($categories_data[catcod]) $categories_data[cat]</option>";
	}
	$categories_sel .= "</select>";
	
	// Retrieve classifications
	$sql = "SELECT clasid, classname FROM cubit.stockclass ORDER BY classname ASC";
	$classifications_rslt = db_exec($sql) or errDie("Unable to retrieve classifications.");
	
	$classifications_sel = "<select name='clasid' style='width: 100%'>";
	while ($classifications_data = pg_fetch_array($classifications_rslt)) {
		$classifications_sel .= "<option value='$classifications_data[clasid]'>$classifications_data[classname]</option>";
	}
	$classifications_sel .= "</select>";

	$OUTPUT = "
		<h3>Stock Sales GP Report</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='display' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Store</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'>$stores_sel</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>By Category</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$categories_sel</td>
				<td><input type='submit' name='button[category]' value='View' /></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>By Classification</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$classifications_sel</td>
				<td><input type='submit' name='button[classification]' value='View' /></td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>All Categories and Classifications</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><input type='submit' value='View All' style='width: 100%' /></td>
			</tr>
		</table>";
	return $OUTPUT;

}



function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["below_perc"] = 100;
	$fields["above_perc"] = 0;
	$fields["between_from_perc"] = 0;
	$fields["between_to_perc"] = 100;
	$fields["frm_year"] = date("Y");
	$fields["frm_month"] = date("m");
	$fields["frm_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["catid"] = 0;
	$fields["clasid"] = 0;
	$fields["sel_perc"] = "above";
	$fields["cust_name"] = "[All]";
	$fields["sp_name"] = "[All]";
	$fields["button"] = "";
	$fields["sort_order"] = "";

	extract ($fields, EXTR_SKIP);

	$from_date = "$frm_year-$frm_month-$frm_day";
	$to_date = "$to_year-$to_month-$to_day";

	// Create categories dropdown
	$sql = "SELECT catid, catcod, cat FROM cubit.stockcat ORDER BY cat ASC";
	$categories_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");
	
	$categories_sel = "
		<select name='catid' style='width: 100%'>
			<option value='0'>[All]</option>";
	while ($categories_data = pg_fetch_array($categories_rslt)) {
		if ($categories_data["catid"] == $catid) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}
		$categories_sel .= "<option value='$categories_data[catid]' $sel>($categories_data[catcod]) $categories_data[cat]</option>";
	}
	$categories_sel .= "</select>";

	// Create classifications dropdown
	$sql = "SELECT clasid, classname FROM cubit.stockclass ORDER BY classname ASC";
	$classifications_rslt = db_exec($sql) or errDie("Unable to retrieve classifications.");
	
	$classifications_sel = "
		<select name='clasid' style='width: 100%'>
			<option value='0'>[All]</option>";
	while ($classifications_data = pg_fetch_array($classifications_rslt)) {
		if ($classifications_data["clasid"] == $clasid) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}
		$classifications_sel .= "<option value='$classifications_data[clasid]' $sel>$classifications_data[classname]</option>";
	}
	$classifications_sel .= "</select>";

	$sql = "SELECT surname FROM cubit.customers ORDER BY cusname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

	$cust_sel = "
		<select name='cust_name' style='width: 100%'>
			<option value='[All]'>[All]</option>";
	while ($cust_data = pg_fetch_array($cust_rslt)) {
		if ($cust_name == $cust_data["surname"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$cust_sel .= "<option value='$cust_data[surname]' $sel>$cust_data[surname]</option>";
	}
	$cust_sel .= "</select>";
	
	$sql = "SELECT salesp FROM exten.salespeople ORDER BY salesp ASC";
	$sp_rslt = db_exec($sql) or errDie("Unable to retrieve sales people.");
	
	$sp_sel = "
		<select name='sp_name' style='width: 100%'>
			<option value='[All]'>[All]</option>";
	while ($sp_data = pg_fetch_array($sp_rslt)) {
		if ($sp_name == $sp_data["salesp"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$sp_sel .= "<option value='$sp_data[salesp]' $sel>$sp_data[salesp]</option>";
	}
	$sp_sel .= "</select>";

	$sql = "SELECT * FROM exten.warehouses ORDER BY whname";
	$wh_rslt = db_exec ($sql) or errDie ("Unable to retrieve stores information.");

	$stores_sel = "
		<select name='whid' style='width:100%'>
			<option value='[All]'>[All]</option>";
	while ($warr = pg_fetch_array ($wh_rslt)){
		if ($whid == $warr['whid']){
			$sel = "selected";
		}else {
			$sel = "";
		}
		$stores_sel .= "<option value='$warr[whid]' $sel>$warr[whname]</option>";
	}
	$stores_sel .= "</select>";


	$where_qry = array();

	if ($catid > 0) {
		$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$catid'";
		$cat_rslt = db_exec($sql) or errDie("Unable to retrieve category.");
		$catname = pg_fetch_result($cat_rslt, 0);
		
		$where_qry[] = "catname='$catname'";
	}

	if ($clasid > 0) {
		$sql = "SELECT classname FROM cubit.stockclass WHERE clasid='$clasid'";
		$class_rslt = db_exec($sql) or errDie("Unable to retrieve classification.");
		$classname = pg_fetch_result($class_rslt, 0);
		$where_qry[] = "classname='$classname'";
	}
	
	if ($whid > 0){
		$where_qry[] = "stock.whid='$whid'";
	}

	if (strlen ($stock_sel) > 0){
		$where_qry[] = "((stock.stkcod ILIKE '%$stock_sel%') OR (stock.stkdes ILIKE '%$stock_sel%'))";
	}
	
	$where_qry = implode(" AND ", $where_qry);
	if (!empty($where_qry)) $where_qry = " AND $where_qry";

	$sortcheck2 = "";
	$sortcheck3 = "";
	$sortcheck4 = "";
	$sortcheck5 = "";

	if (isset ($sort_order) AND strlen ($sort_order) > 0){
		switch ($sort_order){
			case "date":
				$sortsql = "edate ASC";
				$sortcheck2 = "checked";
				break;
			case "stkcod":
				$sortsql = "stock.stkcod ASC";
				$sortcheck3 = "checked";
				break;
			case "details":
				$sortsql = "details ASC";
				$sortcheck4 = "checked";
				break;
			case "profit":
				$sortsql = "profit ASC";
				$sortcheck5 = "checked";
				break;
			default:
				$sortsql = "edate DESC, stockrec.oid DESC";
		}
	}else {
		$sortsql = "edate DESC, stockrec.oid DESC";
	}

	$sql = "
		SELECT stockrec.oid, edate, whname, stock.stkcod, stock.stkdes, catname, details,
			classname, trantype, details, stockrec.csprice, qty, stockrec.csamt,
			(stockrec.csprice-stockrec.csamt) AS profit, stock.csprice AS scsprice
		FROM cubit.stockrec
			LEFT JOIN cubit.stock ON stockrec.stkid=stock.stkid
			LEFT JOIN exten.warehouses ON stock.whid=warehouses.whid
		WHERE (trantype='invoice' OR details ILIKE 'Credit Note%')
			AND edate BETWEEN '$from_date' AND '$to_date'
			$where_qry
		ORDER BY $sortsql";
	$stockrec_rslt = db_exec($sql) or errDie("Unable to retrieve stock transactions.");
	
	$stock_total = 0;
	$profit_total = 0;
	$cost_total = 0;
	$qty_total = 0;
	$stockrec_out = "";

	$inv_vat = array();
	while ($stockrec_data = pg_fetch_array($stockrec_rslt)) {
		if ($stockrec_data["profit"] != 0 && $stockrec_data["csamt"] != 0) {
			$stockrec_data["profit_perc"] = $stockrec_data["profit"] / $stockrec_data["csamt"] * 100;
		} else {
			$stockrec_data["profit_perc"] = 0;
		}
		// Reduce risk of typing related injuries
		$profit_perc = $stockrec_data["profit_perc"];
	
		$cusname = $salespn = "";
		if (preg_match("/Invoice No. (.*)/", $stockrec_data["details"], $matches)) {
			if (is_numeric($matches[1])) {
				$sql = "SELECT surname, salespn, vat FROM cubit.invoices WHERE invnum='$matches[1]' UNION ";
					$union = array();
					for ($i = 1; $i <= 14; $i++) {
						$union[] = "SELECT surname, salespn, vat FROM \"$i\".invoices WHERE invnum='$matches[1]'";
					}
					$sql = $sql.implode(" UNION ", $union);

				$inv_rslt = db_exec($sql) or errDie("Unable to retrieve info.");
				list($cusname, $salespn, $inv_vat[$matches[1]]) = pg_fetch_array($inv_rslt);
				
				if (empty($cusname) || empty($salespn)) {
					$sql = "SELECT cusname, salespn, vat FROM cubit.nons_invoices WHERE invnum='$matches[1]'";
					$inv_rslt = db_exec($sql) or errDie("Unable to retrieve info.");
					list($cusname, $salespn, $inv_vat[$matches[1]]) = pg_fetch_array($inv_rslt);
				}
				
				if (empty($cusname) || empty($salespn)) {
					$union = array();
					for ($i = 1; $i <= 14; $i++) {
						$union[] = "SELECT cusname, salespn, vat FROM \"$i\".pinvoices WHERE invnum='$matches[1]'";
					}
					$sql = implode(" UNION ", $union);
					$inv_rslt = db_exec($sql) or errDie("Unable to retrieve info.");
					list($cusname, $salespn, $inv_vat[$matches[1]]) = pg_fetch_array($inv_rslt);
				}
			}
		}
		
		if (preg_match("/Credit note No. (.*)/", $stockrec_data["details"],
			$matches)) {
			if (is_numeric($matches[1])) {
				$union = array();
				for ($i = 1; $i <= 14; $i++) {
					$union[] = "SELECT surname, salespn FROM \"$i\".inv_notes WHERE notenum='$matches[1]'";
				}
				$sql = implode(" UNION ", $union);
				$inv_rslt = db_exec($sql) or errDie("Unable to retrieve info");
				list($cusname, $salespn) = pg_fetch_array($inv_rslt);
				
				$stockrec_data["csprice"] *= -1;
				$stockrec_data["csamt"] *= -1;
				$stockrec_data["profit"] *= -1;
				$stockrec_data["qty"] *= -1;
				$stockrec_data["profit_perc"] *= -1;
			}
		}

		if ($cust_name != "[All]" && $cust_name != $cusname) continue;
		if ($sp_name != "[All]" && $sp_name != $salespn) continue;

		if (is_numeric($profit_perc)) {
			if ($sel_perc == "above" && $profit_perc < floatval($above_perc)) {
				continue;
			} elseif ($sel_perc == "below" &&
				$profit_perc > floatval($below_perc)) {
					
				continue;
			} elseif ($sel_perc == "between" &&
				($profit_perc < floatval($between_from_perc) ||
				$profit_perc > floatval($between_to_perc))) {
					
				continue;
			}
		}
	
		$stockrec_out .= "
			<tr class='".bg_class()."'>
				<td>$stockrec_data[edate]</td>
				<td>$stockrec_data[whname]</td>
				<td>$stockrec_data[stkcod]</td>
				<td>$stockrec_data[stkdes]</td>
				<td>$stockrec_data[catname]</td>
				<td>$stockrec_data[classname]</td>
				<td>$cusname</td>
				<td>$salespn</td>
				<td>$stockrec_data[details]</td>
				<td align='right'>".sprint($stockrec_data["csprice"])."</td>
				<td align='right'>".sprint($stockrec_data["csamt"])."</td>
				<td align='center'>".sprint3($stockrec_data['qty'])."</td>
				<td align='right'>".sprint($stockrec_data["profit"])."</td>
				<td align='center'>".sprint($stockrec_data["profit_perc"])."%</td>
			</tr>";
		
		$stock_total += $stockrec_data["csprice"];
		$cost_total += $stockrec_data["csamt"];
		$profit_total += $stockrec_data["profit"];
		$qty_total += $stockrec_data['qty'];
	}
	
	if (empty($stockrec_out)) {
		$stockrec_out = "
			<tr class='".bg_class()."'>
				<td colspan='14'><li>No results found.</li></td>
			</tr>";
	}

	$checked = array();
	if ($sel_perc == "above") {
		$checked["above"] = "checked";
		$checked["below"] = "";
		$checked["between"] = "";
	} elseif ($sel_perc == "below") {
		$checked["above"] = "";
		$checked["below"] = "checked";
		$checked["between"] = "";
	} elseif ($sel_perc == "between") {
		$checked["above"] = "";
		$checked["below"] = "";
		$checked["between"] = "checked";
	}

	$OUTPUT = "";

	if (is_array($button)) {
		list($button) = array_keys($button);
	}
	
	$vat_total = 0;
	foreach ($inv_vat as $vat) {
		$vat_total += $vat;
	}

	if ($button != "excel") {
		$OUTPUT .= "
			<center>
			<h3>Stock Sales GP Report</h3>
			<form method='post' action='".SELF."'>
			<table cellpadding='0' cellspacing='0'>
				<tr>
					<td>
						<table ".TMPL_tblDflts." width='100%'>
							<tr class='".bg_class()."'>
								<th colspan='4'>Date Range</th>
							</tr>
							<tr class='".bg_class()."'>
								<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
								<td>&nbsp; <b>To</b> &nbsp;</td>
								<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table ".TMPL_tblDflts." width='100%'>
							<tr>
								<th width='50%'>Category</th>
								<th width='50%'>Classification</th>
							</tr>
							<tr class='".bg_class()."'>
								<td>$categories_sel</td>
								<td>$classifications_sel</td>
							</tr>
							<tr>
								<th>Customer</th>
								<th>Sales Person</th>
							</tr>
							<tr>
								<td>$cust_sel</td>
								<td>$sp_sel</td>
							</tr>
							<tr>
								<th>Store</th>
								<th>Stock Filter</th>
							</tr>
							<tr>
								<td>$stores_sel</td>
								<td><input type='text' style='width:100%' name='stock_sel' value='$stock_sel'></td>
							</tr>
							<tr>
								<th colspan='2'>Sorting</th>
							</tr>
							<tr class='".bg_class()."'>
								<td colspan='2' align='center'>
									<input type='radio' name='sort_order' value='date' $sortcheck2> Date 
									<input type='radio' name='sort_order' value='stkcod' $sortcheck3> Stock Code 
									<input type='radio' name='sort_order' value='details' $sortcheck4> Details 
									<input type='radio' name='sort_order' value='profit' $sortcheck5> Profit 
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td>
						<table ".TMPL_tblDflts." width='100%'>
							<tr>
								<th colspan='2'>Display Stock GP</th>
							</tr>
							<tr class='".bg_class()."'>
								<td><input type='radio' name='sel_perc' value='below' $checked[below] /></td>
								<td>
									Below
									<input type='text' name='below_perc' value='$below_perc'
									size='3' style='text-align: center' /><b>%</b>
								</td>
							</tr>
							<tr class='".bg_class()."'>
								<td><input type='radio' name='sel_perc' value='above' $checked[above] /></td>
								<td>
									Above
									<input type='text' name='above_perc' value='$above_perc'
									size='3' style='text-align: center' /><b>%</b>
								</td>
							</tr>
							<tr class='".bg_class()."'>
								<td><input type='radio' name='sel_perc'  value='between' $checked[between] /></td>
								<td>
									Between
									<input type='text' name='between_from_perc' value='$between_from_perc' size='3'
									style='text-align: center' /><b>%</b>
									&nbsp; And &nbsp;
									<input type='text' name='between_to_perc' value='$between_to_perc' size='3'
									style='text-align: center' /><b>%</b>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input type='submit' name='button[excel]' value='Export to Spreadsheet' />
						<input type='submit' value='Apply' style='font-weight: bold' />
					</td>
				</tr>
			</table>";
	}
	$OUTPUT .= "
		<br />
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Warehouse</th>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Stock Category</th>
				<th>Classification</th>
				<th>Customer</th>
				<th>Sales Person</th>
				<th>Details</th>
				<th>Selling Price</th>
				<th>Stock Cost</th>
				<th>Quantity</th>
				<th>Gross Profit</th>
				<th>Gross Profit %</th>
			</tr>
			$stockrec_out
			<tr class='".bg_class()."'>
				<td colspan='9'>Total</td>
				<td align='right'>".sprint($stock_total)."</td>
				<td align='right'>".sprint($cost_total)."</td>
				<td align='right'>".sprint3($qty_total)."</td>
				<td align='right'>".sprint($profit_total)."</td>
				<td>&nbsp;</td>
			</tr>
			<!--<tr class='".bg_class()."'>
				<td colspan='12'>Total VAT</td>
				<td align='right'>$vat_total</td>
				<td>&nbsp;</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='12'><strong>GRAND TOTAL</strong></td>
				<td align='right'><b>".sprint($profit_total - $vat_total)."</b></td>
				<td>&nbsp;</td>
			</tr>-->
		</table>
		</center>";
	return $OUTPUT;

}



function excel()
{

	$OUTPUT = clean_html(display());
	require_lib("xls");
	StreamXLS("Gross Profit", $OUTPUT);

}


?>

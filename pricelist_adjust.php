<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	default:
	case "enter":
		$OUTPUT = enter();
		break;
	case "confirm":
		$OUTPUT = confirm();
		break;
	case "write":
		$OUTPUT = write();
		break;
	}
} else {
	$OUTPUT = enter();
}

// Quick Links
$OUTPUT .= mkQuickLinks (
	ql("toms/pricelist-add.php", "Add Pricelist"),
	ql("toms/pricelist-view.php", "View Pricelists")
);

require ("template.php");




function enter($errors="")
{

	extract ($_REQUEST);
	
	$fields = array();
	$fields["pricelist"] = 0;
	$fields["category"] = 0;
	$fields["classification"] = 0;
	$fields["increase"] = 0;
	$fields["decrease"] = 0;
	
	extract ($fields, EXTR_SKIP);
	
	// Pricelist dropdown ------------------------------------------------
	$sql = "SELECT listid, listname FROM exten.pricelist ORDER BY listname ASC";
	$pricelist_rslt = db_exec($sql) or errDie("Unable to retrieve pricelists.");
	
	$pricelist_sel = "
		<select name='pricelist' style='width: 100%'>
			<option value='0'>[All]</option>";
	while ($pricelist_data = pg_fetch_array($pricelist_rslt)) {
		if ($pricelist == $pricelist_data["listid"]) {
			$sel = "selected='t'";
		} else {
			$sel = "";
		}
	
		$pricelist_sel .= "
			<option value='$pricelist_data[listid]' $sel>
				$pricelist_data[listname]
			</option>";
	}
	$pricelist_sel .= "</select>";
	
	// Stock categories dropdown -----------------------------------------
	$sql = "SELECT catid, cat FROM cubit.stockcat ORDER BY cat ASC";
	$category_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");
	
	$category_sel = "
		<select name='category' style='width: 100%'>
			<option value='0'>[All]</option>";
	while ($category_data = pg_fetch_array($category_rslt)) {
		if ($category == $category_data["catid"]) {
			$sel = "selected='t'";
		} else {
			$sel = "";
		}
	
		$category_sel .= "
			<option value='$category_data[catid]' $sel>
				$category_data[cat]
			</option>";
	}
	$category_sel .= "</select>";
	
	// Stock classifications dropdown
	$sql = "SELECT clasid, classname FROM cubit.stockclass ORDER BY classname ASC";
	$classification_rslt = db_exec($sql) or errDie("Unable to retrieve classifications.");
	
	$classification_sel = "
		<select name='classification' style='width: 100%'>
			<option value='0'>[All]</option>";
	while ($classification_data = pg_fetch_array($classification_rslt)) {
		if ($classification == $classification_data["clasid"]) {
			$sel = "selected='t'";
		} else {
			$sel = "";
		}
		
		$classification_sel .= "<option value='$classification_data[clasid]' $sel>$classification_data[classname]</option>";
	}
	$classification_sel .= "</select>";
	
	$OUTPUT = "
		<center>
		<h3>Mass Adjust Pricelists</h3>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='confirm' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$errors &nbsp;</td>
			</tr>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Pricelist</td>
				<td>$pricelist_sel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Category</td>
				<td>$category_sel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Classification</td>
				<td>$classification_sel</td>
			</tr>
			<tr>
				<th>Increase %</th>
				<th>Decrease %</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='text' name='increase' value='$increase'
					style='text-align: center' />
				</td>
				<td>
					<input type='text' name='decrease' value='$decrease'
					style='text-align: center' />
				</td>
			</tr>
			<tr>
				<td colspan='2' align='center'>
					<input type='submit' value='Confirm &raquo' />
				</td>
			</tr>
		</table>
		</center>
		</form>";
	return $OUTPUT;

}




function confirm()
{

	validate($_REQUEST);
	extract($_REQUEST);
	
	$where_qry = array();
	
	// Retrieve pricelist name
	if ($pricelist) {
		$sql = "SELECT listname FROM exten.pricelist WHERE listid='$pricelist'";
		$pricelist_rslt = db_exec($sql) or errDie("Unable to retrieve pricelist.");
		$pricelist_name = pg_fetch_result($pricelist_rslt, 0);
		
		$where_qry[] = "listid='$pricelist'";
	} else {
		$pricelist_name = "[All]";
	}

	// Retrieve category name
	if ($category) {
		$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$category'";
		$category_rslt = db_exec($sql) or errDie("Unable to retrieve category.");
		$category_name = pg_fetch_result($category_rslt, 0);
		
		$where_qry[] = "catname='$category_name'";
	} else {
		$category_name = "[All]";
	}
	
	// Retrieve classification name
	if ($classification) {
		$sql = "SELECT classname FROM cubit.stockclass WHERE clasid='$classification'";
		$classification_rslt = db_exec($sql) or errDie("Unable to retrieve classification.");
		$classification_name = pg_fetch_result($classification_rslt, 0);
		
		$where_qry[] = "classname='$classification_name'";
	} else {
		$classification_name = "[All]";
	}
	
	$where = implode(" AND ", $where_qry);
	if (!empty($where)){
		$where = "WHERE $where AND length (stock.stkid) > 0";
	}else {
		$where = "WHERE length (stock.stkid) > 0";
	}
	
	$percentage = $increase - $decrease;
	
	$sql = "
		SELECT stkcod, stkdes, selamt AS old_selamt,
			(selamt / 100 * '$percentage') AS new_selamt
		FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid 
		$where";
	$prices_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");
	$prices_affected = pg_num_rows($prices_rslt);
	
	$OUTPUT = "
		<center>
		<h3>Mass Adjust Pricelists</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='pricelist' value='$pricelist' />
			<input type='hidden' name='category' value='$category' />
			<input type='hidden' name='classification' value='$classification' />
			<input type='hidden' name='increase' value='$increase' />
			<input type='hidden' name='decrease' value='$decrease' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Pricelist</td>
				<td>$pricelist_name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category</td>
				<td>$category_name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Classification</td>
				<td>$classification_name</td>
			</tr>
			<tr>
				<th>Increase %</th>
				<th>Decrease %</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$increase</td>
				<td align='center'>$decrease</td>
			</tr>
			<tr>
				<th colspan='2'>Prices Affected</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'><b>$prices_affected</b></td>
			</tr>
			<tr>
				<td><input type='submit' name='key' value='&laquo Correction' /></td>
				<td align='right'><input type='submit' value='Write &raquo' /></td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}




function write()
{

	validate($_REQUEST);
	extract($_REQUEST);

	$where_qry = array();
	
	if ($pricelist) {
		$where_qry[] = "listid='$pricelist'";
	} 	

	if ($category) {
		$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$category'";
		$category_rslt = db_exec($sql) or errDie("Unable to retrieve category.");
		$category_name = pg_fetch_result($category_rslt, 0);
		
		$where_qry[] = "catname='$category_name'";
	}
	
	if ($classification) {
		$sql = "SELECT classname FROM cubit.stockclass WHERE clasid='$classification'";
		$classification_rslt = db_exec($sql) or errDie("Unable to retrieve classification.");
		$classification_name = pg_fetch_result($classification_rslt, 0);
		
		$where_qry[] = "classname='$classification_name'";
	}
	
	$where = implode(" AND ", $where_qry);
	//if (!empty($where)) $where = "WHERE $where";
	
	$percentage = $increase - $decrease;
	
	if (!empty($where)){
		$where = "WHERE $where AND length (stock.stkid) > 0";
	}else {
		$where = "WHERE length (stock.stkid) > 0";
	}

	$sql = "
		SELECT listid, stkcod, stkdes, stock.stkid, price AS old_price,
			(price + (price / 100 * '$percentage')) AS new_price
		FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid 
		$where";
	$prices_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

	$i = 0;
	pglib_transaction("BEGIN");
	while ($prices_data = pg_fetch_array($prices_rslt)) {
		$sql = "
		UPDATE exten.plist_prices
		SET price='$prices_data[new_price]'
		WHERE listid='$prices_data[listid]' AND stkid = '$prices_data[stkid]'";
		db_exec($sql) or errDie("Unable to update price.");
		
		$i++;
	}
	pglib_transaction("COMMIT");

	$OUTPUT = "
		<center>
		<h3>Mass Adjust Prices</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><li>Successfully adjusted <b>$i</b> prices.</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}




function validate($data)
{

	extract ($data);
	
	require_lib("validate");
	$v = new validate;
	
	$v->isOk($pricelist, "num", 1, 20, "Invalid pricelist selection.");
	$v->isOk($category, "num", 1, 20, "Invalid category selection.");
	$v->isOk($classification, "num", 1, 20, "Invalid classification selection.");
	$v->isOk($increase, "float", 1, 20, "Invalid increase percentage.");
	$v->isOk($decrease, "float", 1, 20, "Invalid decrease percentage.");
	
	if (is_numeric($pricelist) && $pricelist) {
		$sql = "SELECT listid FROM exten.pricelist WHERE listid='$pricelist'";
		$pricelist_rslt = db_exec($sql) or errDie("Unable to retrieve pricelist.");
		
		if (!pg_num_rows($pricelist_rslt)) {
			$v->addError("", "Selected pricelist does not exist.");
		}
	}
	
	if (is_numeric($category) && $category) {
		$sql = "SELECT catid FROM cubit.stockcat WHERE catid='$category'";
		$category_rslt = db_exec($sql) or errDie("Unable to retrieve category.");
		
		if (!pg_num_rows($category_rslt)) {
			$v->addError("", "Selected category does not exist.");
		}
	}
	
	if (is_numeric($classification) && $classification) {
		$sql = "SELECT clasid FROM cubit.stockclass WHERE clasid='$classification'";
		$classification_rslt = db_exec($sql) or errDie("Unable to retrieve classification.");
		
		if (!pg_num_rows($classification_rslt)) {
			$v->addError("", "Selected classification does not exist.");
		}
	}
	
	if ($v->isError()) {
		return enter($v->genErrors());
	}
	
	return true;

}


?>

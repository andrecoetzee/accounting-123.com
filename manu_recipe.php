<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	$key = strtolower($_REQUEST["key"]);
	switch ($key) {
		case "recipe":
			$OUTPUT = recipe();
			break;
		case "add":
			$OUTPUT = add();
			break;
		case "remove":
			$OUTPUT = remove();
			break;
	}
} else {
	$OUTPUT = recipe();
}

$OUTPUT .= "
	<center>
		".mkQuickLinks(
			ql("stock-add.php", "Add Stock"),
			ql("stock-view.php", "View Stock")
		)."
	</center>";

require ("template.php");



function recipe()
{

	extract($_REQUEST);

	$fields = array();
	$fields["m_stock_id"] = 0;
	$fields["filter_store"] = 0;
	$fields["filter_class"] = 0;
	$fields["filter_cat"] = 0;
	$fields["each_filter_store"] = 0;
	$fields["each_filter_class"] = 0;
	$fields["each_filter_cat"] = 0;
	$fields["search_string"] = "";
	$fields["each_search_string"] = "";

	extract($fields, EXTR_SKIP);

	$check_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

	if (isset ($check_setting) AND $check_setting == "yes"){
		if (isset ($filter_class) AND $filter_class != "0"){
			$Wh .= " AND prdcls = '$filter_class'";
		}
		if (isset ($filter_cat) AND $filter_cat != "0"){
			$Wh .= " AND catid = '$filter_cat'";
		}
	}

	if (isset($filter_store) AND $filter_store != "0"){
		$Wh .= " AND stock.whid = '$filter_store'";
	}

	if (isset ($search) OR (isset ($m_stock_id) AND $m_stock_id > 0)){
		$dosearch = "TRUE";
	}else {
		$dosearch = "FALSE";
	}

	// Create the main stock item dropdown
	$sql = "
		SELECT stkid, stkcod, stkdes, whname
		FROM cubit.stock
			LEFT JOIN exten.warehouses ON stock.whid=warehouses.whid
		WHERE $dosearch $Wh AND (stkcod ILIKE '%$search_string%' OR stkdes = '%$search_string%') 
		ORDER BY stkcod ASC";
	$m_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$m_stock_sel = "<select name='m_stock_id' onchange='javascript:document.form.submit()' style='width: 100%'>";
	$m_stock_sel.= "<option value='0'>[None / Display All Stores]</option>";
	while ($m_stock_data = pg_fetch_array($m_stock_rslt)) {
		if ($m_stock_id == $m_stock_data["stkid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$m_stock_sel .= "
			<option value='$m_stock_data[stkid]' $sel>
				($m_stock_data[whname]) ($m_stock_data[stkcod]) $m_stock_data[stkdes]
			</option>";
	}
	$m_stock_sel .= "</select>";

	// Just a dummy message to make the user feel good about his/herself :)
	if (isset($save) && $save) {
		$msg = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='3'><li>The recipe has been successfully saved.</li></td>
			</tr>";
	} else {
		$msg = "";
	}

	$optional_filter_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

	if (isset ($optional_filter_setting) AND $optional_filter_setting == "yes"){

		db_connect ();

		$catsql = "SELECT catid, cat, catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
		$catRslt = db_exec($catsql);
		if(pg_numrows($catRslt) < 1){
			$cat_drop = "<input type='hidden' name='filter_cat' value='0'>";
		}else{
			$cat_drop = "<select name='filter_cat'>";
			$cat_drop .= "<option value='0'>All Categories</option>";
			while($cat = pg_fetch_array($catRslt)){
				if (isset ($filter_cat) AND $filter_cat == $cat['catid']){
					$cat_drop .= "<option value='$cat[catid]' selected>($cat[catcod]) $cat[cat]</option>";
				}else {
					$cat_drop .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
				}
			}
			$cat_drop .= "</select>";
		}

		# Select classification
		$classsql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
		$clasRslt = db_exec($classsql);
		if(pg_numrows($clasRslt) < 1){
			$class_drop = "<input type='hidden' name='filter_class' value='0'>";
		}else{
			$class_drop = "<select name='filter_class' style='width: 167'>";
			$class_drop .= "<option value='0'>All Classifications</option>";
			while($clas = pg_fetch_array($clasRslt)){
				if (isset ($filter_class) AND $filter_class == $clas['clasid']){
					$class_drop .= "<option value='$clas[clasid]' selected>$clas[classname]</option>";
				}else {
					$class_drop .= "<option value='$clas[clasid]'>$clas[classname]</option>";
				}
			}
			$class_drop .= "</select>";
		}

		$display_optional_filters = "
			<tr>
				<th>Select Category</th>
				<th>Select Classification</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$cat_drop</td>
				<td align='center'>$class_drop</td>
			</tr>";

	}

	db_conn("exten");

	$sql = "SELECT whid, whname, whno FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		$store_drop = "<input type='hidden' name='filter_store' value='0'>";
	}else{

		if (!isset ($filter_store)){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
			}
		}

		$store_drop = "<select name='filter_store'>";
		$store_drop .= "<option value='0'>All Stores</option>";
		while($wh = pg_fetch_array($whRslt)){
			if (isset ($filter_store) AND $filter_store == $wh['whid']){
				$store_drop .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
			}else {
				$store_drop .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
		}
		$store_drop .= "</select>";

	}

	$OUTPUT = "
		<center>
		<h3>Create Recipe</h3>
		<form method='POST' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			$msg
			<tr>
				<th colspan='2'>Search Stock</th>
			</tr>
			$display_optional_filters
			<tr>
				<th>Store</th>
				<th>Search Code/Description</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$store_drop</td>
				<td align='center'><input type='text' name='search_string' value='$search_string'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' colspan='2'><input type='submit' name='search' value='Search'></td>
			</tr>
			<tr>
				<th colspan='4'>Stock Item</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>$m_stock_sel</td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">";

	if ($m_stock_id) {

		$check_setting = getCSetting ("OPTIONAL_STOCK_FILTERS");

		if (isset ($check_setting) AND $check_setting == "yes"){
	
			db_connect ();
	
			$catsql = "SELECT catid, cat, catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
			$catRslt = db_exec($catsql);
			if(pg_numrows($catRslt) < 1){
				$each_cat_drop = "<input type='hidden' name='each_filter_cat' value='0'>";
			}else{
				$each_cat_drop = "<select name='each_filter_cat'>";
				$each_cat_drop .= "<option value='0'>All Categories</option>";
				while($cat = pg_fetch_array($catRslt)){
					if (isset ($each_filter_cat) AND $each_filter_cat == $cat['catid']){
						$each_cat_drop .= "<option value='$cat[catid]' selected>($cat[catcod]) $cat[cat]</option>";
					}else {
						$each_cat_drop .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
					}
				}
				$each_cat_drop .= "</select>";
			}
	
			# Select classification
			$classsql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
			$clasRslt = db_exec($classsql);
			if(pg_numrows($clasRslt) < 1){
				$each_class_drop = "<input type='hidden' name='each_filter_class' value='0'>";
			}else{
				$each_class_drop = "<select name='each_filter_class' style='width: 167'>";
				$each_class_drop .= "<option value='0'>All Classifications</option>";
				while($clas = pg_fetch_array($clasRslt)){
					if (isset ($each_filter_class) AND $each_filter_class == $clas['clasid']){
						$each_class_drop .= "<option value='$clas[clasid]' selected>$clas[classname]</option>";
					}else {
						$each_class_drop .= "<option value='$clas[clasid]'>$clas[classname]</option>";
					}
				}
				$each_class_drop .= "</select>";
			}
	
			$display_optional_filters_each1 = "
				<th>Select Category</th>
				<th>Select Classification</th>";
			$display_optional_filters_each2 = "
				<td align='center'>$each_cat_drop</td>
				<td align='center'>$each_class_drop</td>";
	
		}
	
		db_conn("exten");
	
		$sql = "SELECT whid, whname, whno FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
		$whRslt = db_exec($sql);
		if(pg_numrows($whRslt) < 1){
			$store_drop = "<input type='hidden' name='filter_store' value='0'>";
		}else{
	
			if (!isset ($filter_store)){
				# check if setting exists
				db_connect();
				$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
				$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
				if (pg_numrows ($Rslt) > 0) {
					$set = pg_fetch_array($Rslt);
					$whid = $set['value'];
				}
			}
	
			$each_store_drop = "<select name='each_filter_store'>";
			$each_store_drop .= "<option value='0'>All Stores</option>";
			while($wh = pg_fetch_array($whRslt)){
				if (isset ($each_filter_store) AND $each_filter_store == $wh['whid']){
					$each_store_drop .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
				}else {
					$each_store_drop .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
				}
			}
			$each_store_drop .= "</select>";
	
		}

		if (isset ($check_setting) AND $check_setting == "yes"){
			if (isset ($each_filter_class) AND $each_filter_class != "0"){
				$Wh2 .= " AND prdcls = '$each_filter_class'";
			}
			if (isset ($each_filter_cat) AND $each_filter_cat != "0"){
				$Wh2 .= " AND catid = '$each_filter_cat'";
			}
		}

		if (isset($each_filter_store) AND $each_filter_store != "0"){
			$Wh2 .= " AND stock.whid = '$each_filter_store'";
		}

		if (isset ($search_each)){
			$do_each = "TRUE";
		}else {
			$do_each = "FALSE";
		}

		// Create the stock dropdown
		$sql = "
			SELECT stkid, stkcod, stkdes 
			FROM cubit.stock 
			WHERE 
				$do_each AND stkid!='$m_stock_id' $Wh2 AND 
				(stkcod ILIKE '%$each_search_string%' OR stkdes ILIKE '%$each_search_string%') 
			ORDER BY stkcod ASC";
		$s_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

		$s_stock_sel = "<select name='s_stock_id' style='width: 100%'>";
		$s_stock_sel.= "<option value='0'>[None]</option>";

		while ($s_stock_data = pg_fetch_array($s_stock_rslt)) {
			$s_stock_sel .= "
				<option value='$s_stock_data[stkid]'>
					($s_stock_data[stkcod]) $s_stock_data[stkdes]
				</option>";
		}

		// Retrieve recipe for this item
		$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$m_stock_id' ORDER BY id DESC";
		$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

		$recipe_out = "";

		$cost_total = 0;
		while ($recipe_data = pg_fetch_array($recipe_rslt)) {
			// Retrieve stock
			$sql = "SELECT stkid, stkcod, stkdes, csprice FROM cubit.stock WHERE stkid='$recipe_data[s_stock_id]'";
			$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
			$stock_data = pg_fetch_array($stock_rslt);

			$cost = $stock_data["csprice"] * $recipe_data["qty"];
			$cost_total += $cost;
			$recipe_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>($stock_data[stkcod]) $stock_data[stkdes]</td>
					<td align='center'>$recipe_data[qty]</td>
					<td align='right'>".sprint($cost)."</td>
					<td align='center'>
						<input type='checkbox' name='rem[$recipe_data[id]]'
						value='$recipe_data[id]'
						onchange='javascript:document.form2.submit()' />
					</td>
				</tr>";
		}

		$OUTPUT .= "
			<form method='post' action='".SELF."' name='form2'>
			<input type='hidden' name='key' value='remove' />
			<input type='hidden' name='m_stock_id' value='$m_stock_id' />
			<input type='hidden' name='filter_store' value='$filter_store'>
			<input type='hidden' name='filter_class' value='$filter_class'>
			<input type='hidden' name='filter_cat' value='$filter_cat'>
			<input type='hidden' name='search_string' value='$search_string'>
			<tr>
				<th colspan='4'>Stock Used in Manufacturing</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>
					<table cellpadding='0' cellspacing='1'>
						<tr>
							$display_optional_filters_each1
							<th>Store</th>
							<th>Search Code/Description</th>

						</tr>
						<tr>
							$display_optional_filters_each2
							<td>$each_store_drop</td>
							<td><input type='text' name='each_search_string' value='$each_search_string'></td>
							<td><input type='submit' name='search_each' value='Search'></td>
						</tr>
						
					</table>
				</td>
			</tr>
			<tr>
				<th>Stock</th>
				<th>Qty</th>
				<th>Cost</th>
				<th>Add<br />Remove</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$s_stock_sel</td>
				<td><input type='text' name='qty' size='4' value='0' style='text-align: center'></td>
				<td>&nbsp;</td>
				<td><input type='submit' name='key' value='Add' style='width: 100%'></td>
			</tr>
			$recipe_out
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>Total</td>
				<td align='right'>".sprint($cost_total)."</td>
				<td>&nbsp;</td>
			</tr>
		</table>
		</form>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='recipe' />
			<input type='hidden' name='save' value='1' />
			<input type='hidden' name='m_stock_id' value='$m_stock_id' />
			<input type='submit' value='Save &raquo' />
		</form>
		</center>";
	} else {
		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><li>Please select a stock item to continue</li></td>
			</tr>
		</table>
		</center>";
	}

	return $OUTPUT;
}

function add()
{

	extract($_REQUEST);

	if (is_numeric($qty) && $qty > 0 && $s_stock_id) {
		$sql = "SELECT id FROM cubit.recipies WHERE s_stock_id='$s_stock_id' AND m_stock_id='$m_stock_id'";
		$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");
		$recipe_id = pg_fetch_result($recipe_rslt, 0);

		if (pg_num_rows($recipe_rslt)) {
			$sql = "UPDATE cubit.recipies SET qty=(qty + '$qty') WHERE m_stock_id='$m_stock_id' AND s_stock_id='$s_stock_id'";
			db_exec($sql) or errDie("Unable to update recipe.");
		} else {
			$sql = "INSERT INTO cubit.recipies (m_stock_id, s_stock_id, qty) VALUES ('$m_stock_id', '$s_stock_id', '$qty')";
			db_exec($sql) or errDie("Unable to create new recipe.");
		}
	}

	return recipe();
}



function remove()
{

	extract($_REQUEST);

	if (isset($rem)) {
		foreach ($rem as $id) {
			$sql = "DELETE FROM cubit.recipies WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove recipe.");
		}
	}

	return recipe();

}


?>
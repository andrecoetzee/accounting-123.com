<?php

require ("settings.php");

$limit = getCSetting ("PRE_STOCK_TAKE_LIMIT");
$limit += 0;
if ($limit == 0){
	$limit = 25;
}
define ("OFFSET_SIZE", $limit);

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "pretake_display":
			$OUTPUT = pretake_display();
			break;
		case "pretake_print":
			$OUTPUT = pretake_print();
			break;
		case "pretake_update":
			$OUTPUT = pretake_update();
			break;
	}
} else {
	$OUTPUT = pretake_display();
}

$OUTPUT .= mkQuicklinks (
	ql("stock-add.php", "Add Stock"),
	ql("stock-view.php", "View Stock")
);

require ("template.php");




function pretake_display()
{

	db_conn ("exten");

	$get_stores = "select * from warehouses";
	$run_stores = db_exec ($get_stores) or errDie ("Unable to get store information.");
	if (pg_numrows ($run_stores) < 1){
		$store_drop = "<input type='hidden' name='store' value='0'>";
	}else {
		$store_drop = "<select name='store'>";
		$store_drop .= "<option value='0'>All Stores</option>";
		while ($sarr = pg_fetch_array ($run_stores)){
			if (isset ($store) AND $store == $sarr['whid']){
				$store_drop .= "<option value='$sarr[whid]' selected>($sarr[whno]) $sarr[whname]</option>";
			}else {
				$store_drop .= "<option value='$sarr[whid]'>($sarr[whno]) $sarr[whname]</option>";
			}
		}
		$store_drop .= "</select>";
	}

	$OUTPUT = "
		<center>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='pretake_update' />
			<input type='hidden' name='offset' value='0' />
			<input type='hidden' name='limit' value='".OFFSET_SIZE."' />
			<input type='hidden' name='new' value='1' />
		<table ".TMPL_tblDflts.">
			<tr class='".bg_class()."'>
				<td>
					This will start a new <em>Stock Take</em> and remove all previous
					uncompleted pages $store_drop <input type='submit' value='OK' />
				</td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}




function pretake_print()
{

	extract ($_REQUEST);
	
	$fields = array();
	$fields["offset"] = 0;
	$fields["store"] = 0;
	$fields["limit"] = OFFSET_SIZE;
	
	extract ($fields, EXTR_SKIP);

	if (isset ($store) AND $store != "0"){
		$whsearch = "WHERE whid = '$store'";
	}else {
		$whsearch = "";
	}

	$sql = "SELECT stkid, stkcod, stkdes, whid FROM cubit.stock $whsearch ORDER BY stkcod ASC, whid LIMIT $limit OFFSET $offset";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	$stock_out = "";
	while (list($stkid, $stkcod, $stkdes, $whid) = pg_fetch_array($stock_rslt)) {

		db_conn ("exten");

		$get_wh = "SELECT whname FROM warehouses WHERE whid = '$whid' LIMIT 1";
		$run_wh = db_exec ($get_wh) or errDie ("Unable to get warehouse information.");
		if (pg_numrows($run_wh) < 1){
			$whname = "Default";
		}else {
			$whname = trim (pg_fetch_result($run_wh,0,0));
		}

		$stock_out .= "
			<tr>
				<td>$whname</td>
				<td>$stkcod</td>
				<td>$stkdes</td>
				<td width='10%' style='border-bottom: 1px solid #000'>&nbsp;</td>
			</tr>";
	}

	$OUTPUT = "
		<style>
			th { text-align: left }
		</style>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td><h2>Pre Stock Take</h2></td>
				<td align='right'><h3>Page ".page_number($offset, $store)."</h3>
			</tr>
		</table>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th align='left'>Store</th>
				<th align='left'>Stock Code</th>
				<th align='left'>Stock Description</th>
				<th align='left'>Quantity</th>
			</tr>
			$stock_out
		</table>";
	require ("tmpl-print.php");

}



function pretake_update()
{

	extract ($_REQUEST);

	pglib_transaction("BEGIN");
	
	$page = page_number($offset, $store);
	
	if (isset($new) && $new) {
		$sql = "DELETE FROM cubit.stock_take";
		db_exec($sql) or errDie("Unable to remove old stock take.");
	}

	if (isset ($store) AND $store != "0"){
		$whsearch = "WHERE whid = '$store'";
	}else {
		$whsearch = "";
	}

	$sql = "SELECT stkid FROM cubit.stock $whsearch ORDER BY stkcod ASC LIMIT $limit OFFSET $offset";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	while (list($stkid) = pg_fetch_array($stock_rslt)) {
		$sql = "INSERT INTO cubit.stock_take (stkid, page) VALUES ('$stkid', '$page')";
		db_exec($sql) or errDie("Unable to add to stock take.");
	}
	
	$sql = "SELECT stkid FROM cubit.stock $whsearch ORDER BY stkcod ASC LIMIT $limit OFFSET $offset";
	db_exec($sql) or errDie("Unable to retrieve stock take.");
	
	$next_page = $page + 1;
	$next_offset = page_offset($next_page);
	
	if ($next_page <= total_pages($store)) {
		$button = "<input type='submit' value='Page $next_page' />";
	} else {
		$button = "<input type='button' value='Post Stock Take' onclick='javascript:move(\"stock_take_post.php\")' />";
	}

	pglib_transaction("COMMIT");

	$OUTPUT = "
		<script>
			printer(\"".SELF."?key=pretake_print&offset=$offset&limit=$limit&store=$store\");
		</script>
		<center>
		<h3>Pre Stock Take</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='pretake_update' />
			<input type='hidden' name='store' value='$store' />
			<input type='hidden' name='limit' value='$limit' />
			<input type='hidden' name='offset' value='$next_offset' />
			$button
		</form>
		</center>";
	return $OUTPUT;

}



function page_number($offset, $store)
{

	if (isset ($store) AND $store != "0"){
		$whsearch = "WHERE whid = '$store'";
	}else {
		$whsearch = "";
	}

	$sql = "SELECT count(stkid) FROM cubit.stock $whsearch";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_count = pg_fetch_result($stock_rslt, 0);
	return intval(($offset / OFFSET_SIZE) + 1);

}



function page_offset($page_num)
{
	return ($page_num - 1) * OFFSET_SIZE;
}



function total_pages($store)
{

	if (isset ($store) AND $store != "0"){
		$whsearch = "WHERE whid = '$store'";
	}else {
		$whsearch = "";
	}

	$sql = "SELECT count(stkid) FROM cubit.stock $whsearch";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve total pages.");
	$stock_count = pg_fetch_result($stock_rslt, 0);
	return intval(($stock_count / OFFSET_SIZE) + 1);

}


?>
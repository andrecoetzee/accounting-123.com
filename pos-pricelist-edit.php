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
require ("core-settings.php");

if(isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			if (isset ($HTTP_POST_VARS["continue"]))
				$OUTPUT = confirm_data ($HTTP_POST_VARS);
			else 
				$OUTPUT = get_data($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write_data ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_data ($HTTP_POST_VARS);
	}
	
}else {
	$OUTPUT = get_data ($HTTP_POST_VARS);
}

$OUTPUT .= "<br>".
	mkQuickLinks(
		ql("stock-add.php", "Add Stock"),
		ql("stock-view.php", "View Stock"),
		ql("toms/pricelist-add.php", "Add Pricelist"),
		ql("toms/pricelist-view.php", "View Pricelists")
	);
	
require ("template.php");



function get_data ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$buttons = "";
	$sendvars_stockid = "";
	$sendvars_stockprice = "";
	if (!isset ($offset)) 
		$offset = 0;
	if (isset ($prev)) 
		$offset -= SHOW_LIMIT;
	if (isset ($next)) 
		$offset += SHOW_LIMIT;
	if ($offset < 0)
		$offset = 0;

	foreach ($stockid AS $each){
		$sendvars_stockid .= "<input type='hidden' name='stockid[]' value='$each'>\n";
	}
	foreach ($stockprice AS $each){
		$sendvars_stockprice .= "<input type='hidden' name='stockprice[]' value='$each'>\n";
	}

	db_connect ();

	#get stock total
	$get_tot = "SELECT count(stkid) FROM stock";
	$run_tot = db_exec ($get_tot) or errDie ("Unable to get stock total information.");
	$stktot = pg_fetch_result ($run_tot,0,0);

	#get list of customers
	$get_stock = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod OFFSET $offset LIMIT ".SHOW_LIMIT;
	$run_stock = db_exec($get_stock) or errDie("Unable to get stock information");
	if(pg_numrows($run_stock) < 1){
		return "No stock was found.";
	}else {
		$listing = "";
		while ($arr = pg_fetch_array($run_stock)){

			if ($idkey = array_search ($arr['stkid'],$stockid)){
				$arr['selamt'] = $stockprice[$idkey];
			}

//			if (isset($stockid) AND is_array($stockid) AND in_array($arr['stkid'],$stockid)){
//				$arr['selamt'] = $stockprice[$idkey];
//			}

			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<input type='hidden' name='stockid[]' value='$arr[stkid]'>
					<td>$arr[stkcod]</td>
					<td>$arr[stkdes]</td>
					<td><input type='text' name='stockprice[]' value='".sprint ($arr['selamt'])."'></td>
				</tr>";
		}
		if ($offset + SHOW_LIMIT < $stktot){
			$buttons = "
				<tr>
					<td colspan='2'><input type='submit' name='prev' value='Previous ".SHOW_LIMIT."'></td>
					<td align='right'><input type='submit' name='next' value='Next ".SHOW_LIMIT."'></td>
				</tr>";
		}
	}



	$display = "
		<h3>POS Pricelist Edit</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='offset' value='$offset'>
			$sendvars_stockid
			$sendvars_stockprice
			<tr>
				<th>Stock Name</th>
				<th>Stock Description</th>
				<th>Stock Selling Price</th>
			</tr>
			$listing
			$buttons
			".TBL_BR."
			<tr>
				<td colspan='3' align='right'><input type='submit' name='continue' value='Save'></td>
			</tr>
		</table>";
	return $display;

}



function confirm_data ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	#get list of customers
	$get_stock = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod";
	$run_stock = db_exec($get_stock) or errDie("Unable to get stock information");
	if(pg_numrows($run_stock) < 1){
		return "No stock was found.";
	}else {
		$listing = "";
		$i = 0;
		while ($arr = pg_fetch_array($run_stock)){

			if ((!isset ($stockid[$i]) OR strlen ($stockid[$i]) < 1) OR (!isset ($stockprice[$i]) OR strlen ($stockprice[$i]) < 1)) 
				continue;

			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<input type='hidden' name='stockid[$i]' value='$stockid[$i]'>
					<input type='hidden' name='stockprice[$i]' value='$stockprice[$i]'>
					<td>$arr[stkcod]</td>
					<td>$arr[stkdes]</td>
					<td>$stockprice[$i]</td>
				</tr>";
			$i++;
		}
	}

	$display = "
		<h3>Confirm Entries</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			
			<tr>
				<th>Stock Name</th>
				<th>Stock Description</th>
				<th>Stock Selling Price</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='<< Correction'></td>
				<td colspan='2' align='right'><input type='submit' value='Write'></td>
			</tr>
		</table>";
	return $display;

}



function write_data ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(isset($back))
		return get_data($HTTP_POST_VARS);

	db_connect ();

	#get list of customers
	$get_stock = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod";
	$run_stock = db_exec($get_stock) or errDie("Unable to get stock information");
	if(pg_numrows($run_stock) < 1){
		return "No stock was found.";
	}else {
		$listing = "";
		$i = 0;
		while ($arr = pg_fetch_array($run_stock)){

			if ((!isset ($stockid[$i]) OR strlen ($stockid[$i]) < 1) OR (!isset ($stockprice[$i]) OR strlen ($stockprice[$i]) < 1)) 
				continue;

			$update_sql = "UPDATE stock SET selamt = '$stockprice[$i]' WHERE stkid = '$stockid[$i]'";
			$run_update = db_exec($update_sql) or errDie("Unable to update stock information.");
			$i++;
		}
	}

	$display = "
		<table ".TMPL_tblDflts.">
			<tr>
				<td><h3>Batch Stock Update Completed.</h3></td>
			</tr>
		</table>";
	return $display;

}

?>

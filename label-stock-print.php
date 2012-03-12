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

$OUTPUT = print_labels ($_POST);

require ("tmpl-print.php");

function print_labels ($_POST)
{

	extract ($_POST);
	if (!isset($stock_cat))
		$stock_cat = "";
	if (!isset($stock_class))
		$stock_class = "";
	if (!isset($price_from))
		$price_from = "";
	if (!isset($price_to))
		$price_to = "";


	db_connect ();

	#get cats ...
	$cat_drop = "<select name='stock_cat'>";
	$sql = "SELECT * FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		$cat_drop .= "<option value='0'>All Categories</option>";
		while($cat = pg_fetch_array($catRslt)){
			if($cat['catid'] == $stock_cat){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$cat_drop .= "<option value='$cat[catid]' $sel>$cat[cat]</option>";
		}
	}
	$cat_drop .= "</select>";

	#get classes ...
	$class_drop = "<select name='stock_class'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no stock Classifications in Cubit.</li>";
	}else{
		$class_drop .= "<option value='0'>All Classifications</option>";
		while($clas = pg_fetch_array($clasRslt)){
			if($clas['clasid'] == $stock_class){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$class_drop .= "<option value='$clas[clasid]' $sel>$clas[classname]</option>";
		}
	}
	$class_drop .= "</select>";


	if ((strlen($stock_cat) > 0) OR (strlen($stock_class) > 0) OR (strlen($price_from) > 0 AND strlen($price_to) > 0)){
		$show_filters = "";
		$filter = "";
		if (strlen($stock_cat) > 0 AND $stock_cat != '0')
			$filter .= " AND catid = '$stock_cat'";
		if (strlen($stock_class) > 0 AND $stock_class != '0')
			$filter .= " AND prdcls = '$stock_class'";
		if (strlen($price_from) > 0 AND strlen($price_to) > 0)
			$filter .= " AND selamt >= '$price_from' AND selamt <= '$price_to'";
	}else {
		$filter = " AND stkid = '0'";
		$show_filters = "
			<tr>
				<th>Category</th>
				<th>Classification</th>
				<th>Retail Price Range</th>
			</tr>
			<tr>
				<td>$cat_drop</td>
				<td>$class_drop</td>
				<td><input type='text' size='6' name='price_from'> to <input type='text' size='6' name='price_to'></td>
			</tr>
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Filter'></td>
			</tr>
			<tr><td><br></td></tr>";
	}


	#get list of customers
	$get_stock = "SELECT * FROM stock WHERE div = '".USER_DIV."' $filter";
	$run_stock = db_exec($get_stock) or errDie("Unable to get stock information");
	if(pg_numrows($run_stock) < 1){
		$listing = "No stock was found.<br><br><input type='button' onClick=\"document.location='label-stock-print.php'\" value='Back'>";
	}else {
		$listing = "";
		while ($arr = pg_fetch_array($run_stock)){
			$listing .= "
				<table class='thkborder' width='150'>
					<tr>
						<td nowrap><font size='3'><b>$arr[stkcod]</b></font></td>
						<td nowrap align='right'><font align='right'>".CUR." ".money($arr["selamt"])."</font></td>
					</tr>
					<tr>
						<td colspan='2' align='center'><img src='".getBarcode($arr["bar"])."' width='185' height='70'></td>
					</tr>
					<tr>
						<td colspan='2' align='left'><font size='2'>".nl2br($arr['stkdes'])."</font></td>
					</tr>
				</table>
				<br>
				";
		}
	}

	$display = "
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				$show_filters
			</form>
			</table>
			<table ".TMPL_tblDflts.">
				<tr>
					<td>$listing</td>
				</tr>
			</table>
		";
	return $display;

}

?>
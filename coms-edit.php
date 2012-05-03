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
require ("libs/ext.lib.php");


if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "select":
			$OUTPUT = commision_select ($_POST);
			break;
		case "confirm":
			if (isset ($_POST["savenext"]) OR isset ($_POST["save"])){
				$OUTPUT = commision_write($_POST);
			}else {
				$OUTPUT = commision_select($_POST);
			}
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct();
}

require ("template.php");


# Default view
function slct()
{

	# check if setting exists
	db_connect();

	$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$set = pg_fetch_array($Rslt);
		$whid = $set['value'];
	}else{
		$whid = 0;
	}

	db_conn("exten");

	$whs = "<select name='whid'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "There are no Stores found in Cubit.";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			if($wh['whid'] == $whid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$whs .= "<option value='$wh[whid]' $sel>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	db_conn("cubit");

	//get the categories in a dropdown
	$cats = "<select name='cat'>";
	$Sl = "SELECT catid,cat FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$Rs = db_exec($Sl);
	if(pg_numrows($Rs) < 1){
		return "There are no categories found in Cubit.";
	}else{
		$cats .= "<option value='0'>All</option>";
		while($wh = pg_fetch_array($Rs)){

			$cats .= "<option value='$wh[catid]'>$wh[cat]</option>";
		}
	}
	$cats .= "</select>";

	//get the classifications in a dropdown
	$classes = "<select name='class'>";
	$Sl = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$Rs = db_exec($Sl);
	if(pg_numrows($Rs) < 1){
		return "There are no classes found in Cubit.";
	}else{
		$classes .= "<option value='0'>All</option>";
		while($wh = pg_fetch_array($Rs)){
			$classes .= "<option value='$wh[clasid]'>$wh[classname]</option>";
		}
        }
        $classes .= "</select>";

	$slct = "
		<h3>Set Sales Rep Commission</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='select'>
			<tr>
				<th colspan='2'>Select Store</th>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='center' colspan='2'>$whs</td>
			</tr>
			<tr>
				<th colspan='2'>Select Category</th>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='center' colspan='2'>$cats</td>
			</tr>
			<tr>
				<th colspan='2'>Select Classification</th>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='center' colspan='2'>$classes</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td align='right'><input type='submit' value='Enter &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}



function commision_select ($_POST)
{

	# get vars
	extract ($_POST);

	define ("LIMIT", 50);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Store.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}


	if (!isset ($offset)){
		$offset = 0;
	}
	if (isset ($next) || isset ($savenext)){
		$offset += LIMIT;
	}
	if (isset ($back)) {
		$offset -= LIMIT;
	}
	if ($offset < 0){
		$offset = 0;
	}

	$printStk = "
		<h3>Edit Sales Rep Commission</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='cat' value='$cat'>
			<input type='hidden' name='class' value='$class'>
			<input type='hidden' name='offset' value='$offset'>
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Category</th>
				<th>Class</th>
				<th>Commission</th>
			</tr>";

	db_connect ();

	$Whe = "";
	if($cat != 0) {$Whe .= " AND catid = '$cat'";}
	if($class != 0) {$Whe .= " AND prdcls = '$class'";}

	$i = 0;
	$Sl = "
		SELECT stkid, catid, prdcls, stkdes, stkcod, com FROM stock 
		WHERE whid = '$whid' AND div = '".USER_DIV."' $Whe 
		ORDER BY stkcod ASC LIMIT ".LIMIT." OFFSET $offset";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($Rs) < 1) {
		return "<li class='err'> There is no stock items in the selected warehouse.</li>";
	}
	while ($stk = pg_fetch_array ($Rs)) {

		$Sl = "SELECT cat FROM stockcat WHERE catid = '$stk[catid]' AND div = '".USER_DIV."'";
		$Rslt = db_exec($Sl);
		$cat = pg_fetch_array($Rslt);
		$category = $cat['cat'];

		$Sl = "SELECT classname FROM stockclass WHERE clasid = '$stk[prdcls]' AND div = '".USER_DIV."'";
		$Rslt = db_exec($Sl);
		$cla = pg_fetch_array($Rslt);
		$class = $cla['classname'];

		$printStk .= "
			<tr class='".bg_class()."'>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>$category</td>
				<td>$class</td>
				<td align='right'><input type='text' size='6' maxlength='5' name='com[$stk[stkid]]' value='$stk[com]'>%</td>
			</tr>";
		$i++;
	}


	$check_sql = "SELECT stkid FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' $Whe OFFSET ".($offset + LIMIT)." LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get stock information.");
	if (pg_numrows ($run_check) > 0){
		$more_values = TRUE;
	}else {
		$more_values = FALSE;
	}

	if ($offset != 0){
		$back_button = "<input type='submit' name='back' value='Previous'>";
	}else {
		$back_button = "";
	}

	if ($more_values){
		$next_button = "<input type='submit' name='savenext' value='Save & Next'>&nbsp;<input type='submit' name='next' value='Next'>";
	}else {
		$next_button = "";
	}

	$buttons = "
		<tr>
			<td>$back_button</td>
			<td colspan='3' align='center'><input type='submit' name='save' value='Save &raquo'></td>
			<td>$next_button</td>
		</tr>";

	$printStk .= "
			$buttons
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printStk;

}



function commision_write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Store.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}


	db_connect ();

	foreach ($com AS $stkid => $value){
		$Sl = "UPDATE stock SET com = '$value' WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$Rss = db_exec ($Sl) or errDie ("Unable to retrieve stocks from database.");
	}

	return commision_select ($_POST);

}


?>

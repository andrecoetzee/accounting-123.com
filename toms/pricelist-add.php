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

# get settings
require ("../settings.php");
require ("../core-settings.php");
require ("../libs/ext.lib.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("../template.php");




# enter new data
function enter ()
{

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";
	if(!isset($from_val) OR strlen($from_val) < 1)
		$from_val = 0;
	if(!isset($to_val) OR strlen($to_val) < 1)
		$to_val = 100;

	$enter = "
		<h3>Add Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'><input type='text' size='20' name='listname'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'></td>
				<td valign='left'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>";

	#check if we have too much stock
	$get_stock_check = "SELECT count(stkid) FROM stock";
	$run_stock_check = db_exec($get_stock_check) or errDie ("Unable to get stock information.");
	if(pg_numrows($run_stock_check) < 1){
		$stock_amount = 0;
	}else {
		$stock_amount = pg_fetch_result($run_stock_check,0,0);
	}

		#if its too much, ask for amount
//		if(($stock_amount > 10) AND (strlen($from_val) < 1 OR strlen($to_val) < 1)){
//			$enter .= "
//						<tr>
//							<td colspan='3'><h3>Large Amount Of Stock Item Found. Please Select The Item To Show.( Eg. 1-100 or 101-200)</h3></td>
//						</tr>
//						".TBL_BR."
//						<tr>
//							<th>From</th>
//							<th>To</th>
//						</tr>
//						<tr bgcolor='".bgcolorg()."'>
//							<td><input type='text' size='6' name='from_val'></td>
//							<td><input type='text' size='6' name='to_val'></td>
//						</tr>
//						".TBL_BR."
//					";
//		}else {
	$enter .= "
		<tr>
			<th>Item</th>
			<th>Price Amount</th>
			<th>Show on price list</th>
		</tr>";

	
	$limit_val = $to_val - $from_val;
	if($limit_val < 0)
		$limit_val = 0;

	$i = 0;
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod ASC";// OFFSET $from_val LIMIT $limit_val";
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "<li class='err'> There are no stock items in the selected warehouse.</li>";
	}
	while ($stk = pg_fetch_array ($stkRslt)) {
		$enter .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
				<td align='right'>".CUR." <input type='text' name='prices[]' size='8' value='".sprint($stk['selamt'])."'> $vattype</td>
				<td><input type='checkbox' name='add[$stk[stkid]]' checked></td>
			</tr>";
	}
//		}

	$enter .= "
			<tr><td><br></td></tr>
			<tr>
				<td align='right'></td>
				<td valign='left'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $enter;

}



# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'> No Stock Found For The Price List.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	$confirm = "
		<h3>Confirm Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='listname' value='$listname'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'>$listname</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
				<th>Add</th>
			</tr>";

	# Query server
	foreach($stkids as $key => $value){
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		if(isset($add[$stk['stkid']])) {
			$remove = "Yes";
		} else {
			$remove = "No";
		}

		$confirm .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
				<td>".CUR." <input type='hidden' name='prices[]' size='8' value='".sprint($prices[$key])."'>$prices[$key] $vattype</td>
				<td><input type='hidden' name='add[]' value='$remove'>$remove</td>
			</tr>";
	}

	$confirm .= "
			".TBL_BR."
			<tr>
				<td align='right'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}



# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'>No Stock For The Price List.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_conn ("exten");

	# write to db
	$sql = "INSERT INTO  pricelist(listname, div) VALUES ('$listname', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to price list to system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class='err'>Unable to add price list to database.</li>";
	}

	# get next ordnum
	$listid = pglib_lastid ("pricelist", "listid");


	# Insert price list items
	foreach($stkids as $key => $value){
		db_connect();
		$sql = "SELECT stkid, prdcls, catid FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		if($add[$key]=="Yes") {
			$rem[$key]="Yes";
		} else {
			$rem[$key]="No";
		}

		db_conn ("exten");
		$sql = "
			INSERT INTO plist_prices (
				listid, stkid, catid, clasid, 
				price, div, show
			) VALUES (
				'$listid', '$stkids[$key]', '$stk[catid]', '$stk[prdcls]', 
				'$prices[$key]', '".USER_DIV."', '$rem[$key]'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);
	}

	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Price list added to system</th>
			</tr>
			<tr class='datacell'>
				<td>New Price list <b>$listname</b>, has been successfully added to the system.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pricelist-view.php'>View Price Lists</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>
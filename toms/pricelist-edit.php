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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
        		$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			if (isset($HTTP_GET_VARS['listid'])){
				$OUTPUT = edit ($HTTP_GET_VARS['listid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($HTTP_GET_VARS['listid'])){
		$OUTPUT = edit ($HTTP_GET_VARS['listid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("../template.php");



function edit($listid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	# Select Stock
	db_conn("exten");

	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
		return "<li class='err'> Invalid Price List ID.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
	}

	$enter = "
		<h3>Edit Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='listid' value='$list[listid]'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'><input type='text' size='20' name='listname' value='$list[listname]'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>";

	# Query server
	$i = 0;
	$sql = "SELECT * FROM plist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' AND show='Yes' ORDER BY stkid ASC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
	if (pg_numrows ($stkpRslt) >0) {

		$enter .= "
			<tr>
				<td colspan='2'><h3>Prices on price list</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
				<th>Delete</th>
			</tr>";

		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				db_conn("exten");
				$Sl = "DELETE FROM plist_prices WHERE stkid='$stkp[stkid]' AND div = '".USER_DIV."'";
				$Rs = db_exec ($Sl) or errDie ("Unable to retrieve stocks from database.");
			}else {
				$stk = pg_fetch_array ($stkRslt);
				$stkp['price'] = sprint ($stkp['price']);
				$enter .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
						<td align='right'>".CUR." <input type='text' name='prices[]' size='8' value='$stkp[price]'> $vattype</td>
						<td><input type='checkbox' name='rem[$stk[stkid]]'></td>
					</tr>";
			}
		}

	}

	# Query server
	$i = 0;
	db_conn("exten");

	$sql = "SELECT * FROM plist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' AND (show='No') ORDER BY stkid ASC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
	if (pg_numrows ($stkpRslt) > 0) {
		$enter .= "
			<tr>
				<td colspan='2'><h3>Prices Not on price list</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
				<th>Add to price list</th>
			</tr>";
		
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				db_conn("exten");
				$Sl="DELETE FROM plist_prices WHERE stkid='$stkp[stkid]' AND div = '".USER_DIV."'";
				$Rs = db_exec ($Sl) or errDie ("Unable to retrieve stocks from database.");
			}else {
				$stk = pg_fetch_array ($stkRslt);
				$stkp['price'] = sprint ($stkp['price']);
				$enter .= "
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='hidden' name='dstkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
						<td align='right'>".CUR." <input type='text' name='dprices[]' size='8' value='$stkp[price]'> $vattype</td>
						<td><input type='checkbox' name='add[$stk[stkid]]'></td>
					</tr>";
			}
		}
	}
	
	$enter .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
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
function confirm ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'> There are no stock items on the price list.</li>";
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
		<h3>Confirm Edit Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='listname' value='$listname'>
			<input type='hidden' name='listid' value='$listid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'>$listname</td>
			</tr>
			<tr><td colspan='2'><br><td><tr>
			<tr>
				<td align='right'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>";
	

	if(isset($stkids)) {

		$confirm .= "
			<tr>
				<td colspan='2'><h3>Prices on price list</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
				<th>Delete</th>
			</tr>";

		# Query server
		db_connect();
		foreach($stkids as $key => $value){
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);
			
			//print "|$rem[$key]|";
	
			if(isset($rem[$stk['stkid']])) {
				$remove="Yes";
			} else {
				$remove="No";
			}
	
			$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' name='stkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
					<td>".CUR." <input type='hidden' name='prices[]' size='8' value='$prices[$key]'>$prices[$key] $vattype</td>
					<td><input type='hidden' name='rem[]' value='$remove'>$remove</td>
				</tr>";
		}
	}

	if(isset($dstkids)) {

		$confirm .= "
			<tr>
				<td colspan='2'><h3>Prices not on price list</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
				<th>Add</th>
			</tr>";

		foreach($dstkids as $key => $value){
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$dstkids[$key]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);

			if(isset($add[$stk['stkid']])) {
				$remove = "Yes";
			} else {
				$remove = "No";
			}
	
			$confirm .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' name='dstkids[]' value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
					<td>".CUR." <input type='hidden' name='dprices[]' size='8' value='$dprices[$key]'>$dprices[$key] $vattype</td>
					<td><input type='hidden' name='add[]' value='$remove'>$remove</td>
				</tr>";
		}
	}

	$confirm .= "
			<tr><td><br></td></tr>
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
function write ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");
	if(isset($stkids)){
		foreach($stkids as $key => $value){
			$v->isOk ($stkids[$key], "num", 1, 20, "Invalid Stock Item number.");
			$v->isOk ($prices[$key], "float", 1, 20, "Invalid Stock Item price.");
		}
	}else{
		return "<li class='err'> There is no stock for the price list.</li>";
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

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Write to db
	$sql = "UPDATE pricelist SET listname = '$listname' WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to system.", SELF);
	if (pg_cmdtuples ($listRslt) < 1) {
		return "<li class='err'>Unable to record pricelist information.</li>";
	}

	if(isset($stkids) && is_array($stkids)) {
	# Insert new price list items
		foreach($stkids as $key => $value){
			if($rem[$key] == "Yes") {
				$rem[$key] = "No";
			} else {
				$rem[$key] = "Yes";
			}
			$sql = "UPDATE plist_prices SET price = '$prices[$key]',show = '$rem[$key]' WHERE stkid = '$stkids[$key]' AND listid = '$listid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to record pricelist items information",SELF);
		}
	}

	if(isset($dstkids) && is_array($dstkids)) {
	# Insert new price list items
		foreach($dstkids as $key => $value){
			if($add[$key] == "Yes") {
				$rem[$key] = "Yes";
			} else {
				$rem[$key] = "No";
			}
			$sql = "UPDATE plist_prices SET price = '$dprices[$key]',show = '$rem[$key]' WHERE stkid = '$dstkids[$key]' AND listid = '$listid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to record pricelist items information.",SELF);
		}
	}

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);



	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Price List edited</th>
			</tr>
			<tr class='datacell'>
				<td>Price List <b>$listname</b>, has been edited.</td>
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
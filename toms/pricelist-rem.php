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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;
			case "write":
            	$OUTPUT = write($_POST);
				break;
			default:
				if (isset($_GET['listid'])){
					$OUTPUT = rem ($_GET['listid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
	if (isset($_GET['listid'])){
		$OUTPUT = rem ($_GET['listid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("../template.php");




function rem($listid)
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
		return "<li> Invalid Price List ID.";
    }else{
		$list = pg_fetch_array($listRslt);
    }

	$enter = "
		<h3>Confirm Remove Price list</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='listid' value='$list[listid]'>
			<input type='hidden' name='listname' value='$list[listname]'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Price list</td>
				<td align='center'>$list[listname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Remove &raquo;'></td>
			</tr>
			<tr>
				<td colspan='2'><h3>Prices</h3><td>
			<tr>
			<tr>
				<th>Item</th>
				<th>Price Amount</th>
			</tr>";

		# Query server
		$i = 0;
		$sql = "SELECT * FROM plist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' ORDER BY stkid ASC";
		$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
		if (pg_numrows ($stkpRslt) < 1) {
			return "<li class='err'> There are no stock item on the selected pricelist.</li>";
		}
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			$stk = pg_fetch_array ($stkRslt);

			$enter .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td>
					<td align='right'>".CUR." ".sprint($stkp['price'])." $vattype</td>
				</tr>";
		}

	$enter .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Remove &raquo;'></td>
			</tr>
		</table></form>
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




# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($listname, "string", 1, 255, "Invalid Price list name.");
	$v->isOk ($listid, "num", 1, 50, "Invalid Price List id.");

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
	$sql = "DELETE FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec ($sql) or errDie ("Unable to Remove Price List from the system.", SELF);
	if (pg_cmdtuples ($listRslt) < 1) {
		return "<li class='err'>Unable to remove Price List from database.</li>";
	}
	# write to db
	$sql = "DELETE FROM plist_prices WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec ($sql) or errDie ("Unable to Remove Price List from the system.", SELF);


	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Price List Removed</th>
			</tr>
			<tr class='datacell'>
				<td>Price list <b>$listname</b>, has been removed from Cubit.</td>
			</tr>
		</table>
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
	return $write;

}



?>
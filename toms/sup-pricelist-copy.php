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
					$OUTPUT = edit ($_GET['listid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['listid'])){
			$OUTPUT = edit ($_GET['listid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
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
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

		$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

		# Select Stock
		db_conn("exten");
		$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
        $listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($listRslt) < 1){
                return "<li> Invalid Price List ID.";
        }else{
                $list = pg_fetch_array($listRslt);
        }

	$enter =
	"<h3>Copy Supplier Price list</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=listid value='$list[listid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>From Supplier Price list</td><td align=center>$list[listname]</td></tr>
	<tr class='bg-odd'><td>To Supplier Price list</td><td align=center><input type=text size=20 name=listname value='Copy of $list[listname]'></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	<tr><td colspan=2><h3>Prices</h3><td><tr>
	<tr><th>Item</th><th>Price Amount</th></tr>";

		# Query server
		$i = 0;
		$sql = "SELECT * FROM splist_prices WHERE listid = '$listid' AND div = '".USER_DIV."' ORDER BY stkid ASC";
		$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock items from database.");
		if (pg_numrows ($stkpRslt) < 1) {
			return "<li class=err> There are no stock item on the selected pricelist.";
		}
		while ($stkp = pg_fetch_array ($stkpRslt)) {
			db_connect();
			# get stock details
			$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkp[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
			if (pg_numrows ($stkRslt) < 1) {
				db_conn("exten");
				$Sl="DELETE FROM splist_prices WHERE stkid='$stkp[stkid]' AND div = '".USER_DIV."'";
				$Rs = db_exec ($Sl) or errDie ("Unable to retrieve stocks from database.");
			}
			else{
				$stk = pg_fetch_array ($stkRslt);

				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$enter .= "<tr bgcolor='$bgColor'><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td><td align=right>".CUR." <input type=text name=prices[] size=8 value='$stkp[price]'> $vattype</td></tr>";
			}
		}
	$enter .= "
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
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
		return "<li class=err> there is not stock for the price list.";
	}

	$vattype = (getSetting("SELAMT_VAT") == 'inc') ? "Including Vat" : "Excluding Vat";

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
			return "<li> Invalid Price List ID.";
	}else{
			$list = pg_fetch_array($listRslt);
	}


	$confirm =
	"<h3>Confirm Copy Supplier Price list</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=listname value='$listname'>
	<input type=hidden name=listid value='$listid'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>From Price list</td><td align=center>$list[listname]</td></tr>
	<tr class='bg-even'><td>To Price list</td><td align=center>$listname</td></tr>
	<tr><td colspan=2><br><td><tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	<tr><td colspan=2><h3>Prices</h3><td><tr>
	<tr><th>Item</th><th>Price Amount</th></tr>";

	# Query server
	foreach($stkids as $key => $value){
		db_connect();
		$sql = "SELECT stkid, stkcod, stkdes FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		# Alternate bgcolor
		$bgColor = ($key % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod] - ".extlib_rstr($stk['stkdes'], 30)."</td><td>".CUR." <input type=hidden name=prices[] size=8 value='$prices[$key]'>$prices[$key] $vattype</td></tr>";
	}

	$confirm .= "
	<tr><td><br></td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
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
		return "<li class=err> there is not stock for the price list.";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) < 1){
			return "<li> Invalid Price List ID.";
	}else{
			$list = pg_fetch_array($listRslt);
	}

	# connect to db
	db_conn ("exten");

	# write to db
	$sql = "INSERT INTO spricelist(listname, div) VALUES ('$listname', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to copy price list to system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class=err>Unable to add copied price list to database.";
	}

	# get next ordnum
	$listid = pglib_lastid ("spricelist", "listid");

	# Insert price list items
	foreach($stkids as $key => $value){
		db_connect();
		$sql = "SELECT stkid, prdcls, catid FROM stock WHERE stkid = '$stkids[$key]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		db_conn ("exten");
		$sql = "INSERT INTO splist_prices(listid, stkid, catid, clasid, price, div) VALUES('$listid', '$stkids[$key]', '$stk[catid]', '$stk[prdcls]', '$prices[$key]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);
	}

	// Layout
	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Price list copy</th></tr>
		<tr class=datacell><td>Price list <b>$list[listname]</b>, has been copied to <b>$listname</b>.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='sup-pricelist-view.php'>View Supplier Price Lists</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

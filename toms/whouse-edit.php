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

# Get settings
require ("../settings.php");

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
			if (isset($_GET['whid'])){
				$OUTPUT = edit ($_GET['whid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['whid'])){
		$OUTPUT = edit ($_GET['whid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# display output
require ("../template.php");




function edit($whid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Store id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}

	# Select Stock
	db_conn("exten");

	$sql = "SELECT * FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
    $whRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($whRslt) < 1){
		return "<li> Invalid Store ID.</li>";
	}else{
		$wh = pg_fetch_array($whRslt);
	}

	$enter = "
		<h3>Edit Store</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='whid' value='$wh[whid]'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number</td>
				<td><input type='text' size='10' name='whno' value='$wh[whno]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Store</td>
				<td><input type='text' size='10' maxlength='10' name='whname' value='$wh[whname]'></td>
			</tr>
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
				<td><a href='whouse-view.php'>View Stores</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Account</a></td>
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
	$v->isOk ($whid, "num", 1, 50, "Invalid Store id.");
	$v->isOk ($whno, "num", 1, 10, "Invalid Store number.");
	$v->isOk ($whname, "string", 1, 10, "Invalid Store name or Store name is too long.");


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

	$confirm = "
		<h3>Confirm Edit Store</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='whname' value='$whname'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='whno' value='$whno'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number</td>
				<td>$whno</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Store</td>
				<td>$whname</td>
			</tr>
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
				<td><a href='whouse-view.php'>View Stores</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Account</a></td>
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
	$v->isOk ($whid, "num", 1, 50, "Invalid Store id.");
	$v->isOk ($whno, "num", 1, 10, "Invalid Store number.");
	$v->isOk ($whname, "string", 1, 10, "Invalid Store name or Store name is too long.");

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
	$sql = "UPDATE warehouses SET whno = '$whno', whname = '$whname' WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec ($sql) or errDie ("Unable to add edit Store to system.", SELF);
	if (pg_cmdtuples ($whRslt) < 1) {
		return "<li class='err'>Unable to edit Store to database.</li>";
	}

	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Store edited</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Store <b>$whname</b>, has been edited.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='whouse-view.php'>View Stores</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Account</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}



?>
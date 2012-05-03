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
require ("settings.php");
require ("core-settings.php");
require ("libs/ext.lib.php");

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
			if (isset($_GET['stkid'])){
				$OUTPUT = enter ($_GET['stkid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET['stkid'])){
		$OUTPUT = enter ($_GET['stkid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("template.php");




# enter new data
function enter ($stkid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	db_connect();

	$sql = "SELECT stkid, stkcod, stkdes, units FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	$stk = pg_fetch_array ($stkRslt);

	$sers  = ext_getserials($stkid);

	$enter = "
		<h3>Allocate Serial Numbers</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='stkid' value='$stkid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Stock</td>
				<td align='center'>$stk[stkcod] $stk[stkdes]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			<tr>
				<th colspan='2'>Serial Numbers</th>
			</tr>";

		foreach($sers as $key => $ser){
			$enter .= "
				<tr class='".bg_class()."'>
					<td align='center' colspan='2'><input type='text' name='sers[]' size='20' value='$ser[serno]'></td>
				</tr>";
		}

		for($i = 0; $i < ($stk['units'] - count($sers)); $i++){
			$enter .= "
				<tr class='".bg_class()."'>
					<td align='center' colspan='2'><input type='text' name='sers[]' size='20' value=''></td>
				</tr>";
		}

		$enter .= "
				<tr><td><br></td></tr>
				<tr>
					<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
					<td valign='left'><input type='submit' value='Confirm &raquo;'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
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
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
	if(!ext_isUnique(ext_remBlnk($sers))){
		$v->isOk ("error", "num", 1, 1, "Error : Serial numbers must be unique.");
	}
	foreach($sers as $key => $serno){
		if(strlen($serno) > 0){
			$v->isOk ($serno, "string", 1, 20, "Error : Invalid Serial number.");
			if (preg_match("/[-\/\\'\"]/", $serno)) {
				$v->addError(0, "Error: Serial number cannot contain any of the following characters - / \ ' \"");
			}
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm.= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";;
	}

	db_connect();

	$sql = "SELECT stkid, stkcod, stkdes, units FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	$stk = pg_fetch_array ($stkRslt);

	// Layout
	$confirm = "
		<h3>Confirm Serial Numbers</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='stkid' value='$stkid'>
			<tr class='".bg_class()."'>
				<td>Stock</td>
				<td align='center'>$stk[stkcod] $stk[stkdes]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<tr>
				<th colspan='2'>Serial Numbers</th>
			</tr>";

		foreach($sers as $key => $serno){
			if(strlen($serno) < 1) continue;
			$confirm .= "
				<tr class='".bg_class()."'>
					<td align='center' colspan='2'><input type='hidden' name='sers[]' size='20' value='$serno'>$serno</td>
				</tr>";
		}

		$confirm .= "
				<tr><td><br></td></tr>
				<tr>
					<td align='right'><input type='button' value='Back' onclick='javascript:history.back();'></td>
					<td valign='left'><input type='submit' value='Write &raquo;'></td>
				</tr>
			</table>
			</form>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='pricelist-view.php'>View Price Lists</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
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
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
	foreach($sers as $key => $serno){
		$v->isOk ($serno, "string", 1, 20, "Error : Invalid Serial number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}

	db_connect();

	$sql = "SELECT stkid, stkcod, stkdes, units FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	$stk = pg_fetch_array ($stkRslt);

	# Sake of updating without duplicates
	ext_delserials($stkid);

	# Insert serials
	foreach($sers as $key => $serno){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		$sql = "INSERT INTO serial$tab (stkid, serno, rsvd) VALUES ('$stkid', '$serno', 'n')";
		$rslt = db_exec($sql) or errDie("Unable to insert serial numbers to Cubit.",SELF);
	}

	// Layout
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Serial Numbers Allocated</th>
			</tr>
			<tr class='datacell'>
				<td>Serial Numbers for <b>($stk[stkcod]) $stk[stkdes]</b>, have been successfully added to the system.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>
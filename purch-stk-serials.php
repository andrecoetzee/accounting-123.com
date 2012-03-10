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
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			if (isset($HTTP_POST_VARS['stkids'])){
				$OUTPUT = enter ($HTTP_POST_VARS);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($HTTP_POST_VARS['stkids'])){
		$OUTPUT = enter ($HTTP_POST_VARS);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# display output
require ("template.php");

# enter new data
function enter ($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 50, "Invalid Purchase number.");
	foreach($stkids as $key => $stkid){
		$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
		$v->isOk ($qtys[$key], "num", 1, 50, "Invalid quantity.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	foreach($stkids as $key => $stkid){
		db_connect();
		$sql = "SELECT stkid, stkcod, stkdes, units FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		$sers  = ext_getserials($stkid);

		$data  = "";
		$data .= "<tr><td><br></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>$stk[stkcod]</td><td align=center>$stk[stkcod] $stk[stkdes]</td></tr>
		<tr><th colspan=2>Serial Numbers</th></tr>";
		for($i = 0; $i < $qtys[$key]; $i++){
			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$data .= "<tr bgcolor='$bgColor'><td align=center colspan=2><input type=text name=sers[$stkid][] size=20 value=''></td></tr>";
		}
	}

	$enter = "
	<h3>Allocate Serial Numbers</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Confirm &raquo;'></td></tr>
	$data
	<tr><td><br></td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 50, "Invalid purchase id.");
	foreach($sers as $stkid => $sernos){
		if(!ext_isUniquedb(ext_remBlnk($sernos))){
			$v->isOk ("error", "num", 1, 1, "Error : Serial numbers must be unique per Stock Item.");
		}
		foreach($sernos as $key => $serno){
			if(strlen($serno) > 0){
				$v->isOk ($serno, "string", 1, 20, "Error : Invalid Serial number.");
			}
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm.= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";;
	}

	print "Success !!!";exit;

	db_connect();
	$sql = "SELECT stkid, stkcod, stkdes, units FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	$stk = pg_fetch_array ($stkRslt);

	// Layout
	$confirm =
	"<h3>Confirm Serial Numbers</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=write>
		<input type=hidden name=stkid value='$stkid'>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock</td><td align=center>$stk[stkcod] $stk[stkdes]</td></tr>
		<tr><td><br></td></tr>
		<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
		<tr><th colspan=2>Serial Numbers</th></tr>";

		foreach($sers as $key => $serno){
			if(strlen($serno) < 1) continue;
			$bgColor = ($key % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$confirm .= "<tr bgcolor='$bgColor'><td align=center colspan=2><input type=hidden name=sers[] size=20 value='$serno'>$serno</td></tr>";
		}

		$confirm .= "
		<tr><td><br></td></tr>
		<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='pricelist-view.php'>View Price Lists</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
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
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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

		$sql = "INSERT INTO serial$tab(stkid, serno, rsvd) VALUES('$stkid', '$serno', 'n')";
		$rslt = db_exec($sql) or errDie("Unable to insert serial numbers to Cubit.",SELF);
	}

	// Layout
	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Serial Numbers Allocated</th></tr>
		<tr class=datacell><td>Serial Numbers for <b>($stk[stkcod]) $stk[stkdes]</b>, have been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

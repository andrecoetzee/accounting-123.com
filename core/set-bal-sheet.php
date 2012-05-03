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

require ("settings.php");          // Get global variables & functions
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
			case "Submit":
				$OUTPUT = printacc($_POST);
				break;

			case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
				$OUTPUT = write($_POST);
				break;

			default:
				$OUTPUT = sub();
	}
} else {
        # Display default output
        $OUTPUT = sub();
}

require ("../template.php");

# Default View
function view()
{

	db_connect();
	$sql = "SELECT * FROM branches";
	$branRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$brans = "";
	while($bran = pg_fetch_array ($branRslt)){
		$sp = "&nbsp;&nbsp;&nbsp;";
		$brans .= "<tr class='bg-odd'><td colspan=2><input type=checkbox name=divs[] checked=yes value='$bran[div]'>$sp $bran[branname]</td></tr>";
	}

	# Layout
	$view = "
	<h3>Trial Balance</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Select Branch</th></tr>
	$brans
	<tr><td><br></td></tr>
	<tr><td></td><td valign=center align=right><input type=submit value='Continue &raquo;'></td></tr>
	</table>";

	return $view;
}

function sub()
{

	// Set up table to display in
	$sub = "<center>
	<h3>Configure Balance Sheet</h3>
	<form action='".SELF."' method=post name=form>
	 <input type=hidden name=key value='Submit'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
	<tr><th colspan=3>Owners Equity</th></tr>";
	for($i = 0; $i < 5; $i++){
		$sub .= "<tr class='bg-odd'><td>Sub Heading ".($i+1)."</td><td align=center><input size=30 type=text name=oesub[]></td></tr>";
	}

	$sub .= "<tr><th colspan=3>Assets</th></tr>";
	for($i = 0; $i < 5; $i++){
		$sub .= "<tr class='bg-odd'><td>Sub Heading ".($i+1)."</td><td align=center><input size=30 type=text name=asssub[]></td></tr>";
	}

	$sub .= "<tr><td></td><td align=right><input type=submit value='Continue &raquo;'></td></tr>
	</table>
	</form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</tr>
	</table>";

	return $sub;
}

# print accounts
function printacc($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	# validate array input
	foreach($oesub as $key => $sub){
		$v->isOk ($sub, "string", 0, 255, "Invalid Owners Equity Sub Heading number $key.");
	}

	foreach($asssub as $key => $sub){
		$v->isOk ($sub, "string", 0, 255, "Invalid Assets Sub Heading number $key.");
	}

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

	$hide = "";
	$heads = "<select name=heads[]>";
	foreach($oesub as $key => $sub){
		if(strlen($sub)){
			$hide .= "<input type=hidden name=oesub[] value='$sub'>";
			$heads .= "<option value='o$key'>$sub</option>";
		}
	}
	$heads .= "<option value='' disabled>---------------</option>";
	foreach($asssub as $key => $sub){
		if(strlen($sub)){
			$hide .= "<input type=hidden name=asssub[] value='$sub'>";
			$heads .= "<option value='a$key'>$sub</option>";
		}
	}
	$heads .= "</select>";

	db_connect();
	$sql = "SELECT * FROM branches WHERE div = '".USER_DIV."'";
	$branRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$head = "";
	$head2 = "";

	# Add each selected branch to the headers
	while($bran = pg_fetch_array ($branRslt)){
		$head .= "<th colspan=2>$bran[branname]</th>";
		$head2 .= "<th>Debit</th><th>Credit</th>";
		$brans[] = $bran['div'];
		$tldebit[] = 0;
		$tlcredit[] = 0;
	}

	# Set up table to display in
	$OUTPUT = "
	<center>
	<h3>Configure Balance Sheet</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	$hide
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=2></th>$head</tr>
	<tr><th>Account Number</th><th>Account Name</th>$head2</tr>";

	# Connect to database
	core_connect();
	$sql = "SELECT DISTINCT topacc,accnum FROM trial_bal WHERE topacc >= ".MIN_BAL." AND div = '".USER_DIV."'";
	$accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);

	if ($numrows < 1) {
		return "<li class=err> There are no Balance Sheet a ccounts yet in Cubit.";
	}

	# Display all Accounts
	$i = 0;
	$ttldeb = 0;
	$ttlcred = 0;
	while($acc = pg_fetch_array ($accRslt)){
		# Alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		# To get the account name
		$sql = "SELECT * FROM trial_bal WHERE accnum = '$acc[accnum]' AND topacc = '$acc[topacc]' AND div = '".USER_DIV."' LIMIT 1";
		$acccRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$accc = pg_fetch_array ($acccRslt);

		$OUTPUT .= "<tr bgcolor='$bgColor'><td><input type=hidden name=accno[] value='$accc[topacc]/$accc[accnum]'>$accc[topacc]/$accc[accnum]</td><td>$accc[accname]</td>";

		$tldeb = 0;
		$tlcred = 0;

		# Get balances for each branch
		foreach($brans as $key => $value){
			$sql = "SELECT * FROM trial_bal WHERE accnum = '$acc[accnum]' AND topacc = '$acc[topacc]' AND div = '$value'";
			$subRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
			if(pg_numrows($subRslt) > 0){
				$sub = pg_fetch_array ($subRslt);
				$sub['debit'] = sprint($sub['debit']);
				$sub['credit'] = sprint($sub['credit']);

				if(floatval($sub['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=right>".CUR." $sub[debit]</td>";
				}

				if(floatval($sub['credit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=right>".CUR." $sub[credit]</td>";
				}

				$tldebit[$key] += $sub['debit'];
				$tlcredit[$key] += $sub['credit'];
				$tldeb += $sub['debit'];
				$tlcred += $sub['credit'];
			}else{
				$OUTPUT .="<td></td><td></td>";
			}
		}
		$OUTPUT .="<td>$heads</td></tr>";
		$i++;
	}

	# Totals
	$OUTPUT .= "<tr bgcolor='$bgColor'><td colspan=2><b>Total</b></td>";
	foreach($brans as $key => $value){
		$tldebit[$key] = sprint($tldebit[$key]);
		$tlcredit[$key] = sprint($tlcredit[$key]);
		$OUTPUT .= "<td align=center><b>".CUR." $tldebit[$key]</b></td><td align=center><b>".CUR." $tlcredit[$key]</b></td>";
	}

	$OUTPUT .= "</tr>";
	$OUTPUT .= "<tr><td><br></td></tr>
	<tr><td> </td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table>
	</form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}

function confirm($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	if(!isset($asssub) || !isset($oesub)) {
		$v->isOk ("##", "num", 0, 0, "Error : Please set at least one Owners Equity and one Assets Sub Heading.");
	}else{
		# Validate array input
		foreach($oesub as $key => $sub){
			$v->isOk ($sub, "string", 0, 255, "Invalid Owners Equity Sub Heading number $key.");
		}

		if(isset($asssub)) {
			foreach($asssub as $key => $sub){
				$v->isOk ($sub, "string", 0, 255, "Invalid Assets Sub Heading number $key.");
			}
		}
		foreach($accno as $key => $accn){
			list($topacc, $accnum) = explode("/", $accn);
			$v->isOk ($topacc, "num", 3, 3, "Invalid account number.");
			$v->isOk ($accnum, "num", 3, 3, "Invalid account number.");
		}

		foreach($heads as $key => $head){
			$v->isOk ($head, "string", 1, 10, "Invalid Sub Heading Selection.");
		}
	}

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

	# hide subs
	$hide = "";
	foreach($oesub as $key => $sub){
		$hide .= "<input type=hidden name=oesub[] value='$sub'>";
	}
	if(isset($asssub)) {
		foreach($asssub as $key => $sub){
			$hide .= "<input type=hidden name=asssub[] value='$sub'>";
		}
	}
	# hide account numbers
	foreach($accno as $key => $accn){
		$hide .= "<input type=hidden name=accno[] value='$accn'>";
	}
	# hide account headings
	foreach($heads  as $key => $head){
		$hide .= "<input type=hidden name=heads[] value='$head'>";
	}

	# Display oe subs
	$oesubdsp = "<tr><th colspan=2>Equity</th></tr>";
	foreach($oesub as $ksub => $sub){
		$oesubdsp .= "<tr class='bg-even'><td colspan=2><b>$sub</b></td></tr>";
		$subaccs = getaccs($ksub, $heads, 'o', $accno);
		foreach($subaccs as $kkey => $subacc){
			$acc = getaccnum($subacc);
			$oesubdsp .= "<tr class='bg-odd'><td>$subacc</td><td>$acc[accname]</td></tr>";
		}
	}

	# Display ass subs
	$asssubdsp = "<tr><th colspan=2>Assets</th></tr>";
	if(isset($asssub)) {
		foreach($asssub as $ksub => $sub){
			$asssubdsp .= "<tr class='bg-even'><td colspan=2><b>$sub</b></td></tr>";
			$subaccs = getaccs($ksub, $heads, 'a', $accno);
			foreach($subaccs as $kkey => $subacc){
				$acc = getaccnum($subacc);
				$asssubdsp .= "<tr class='bg-odd'><td>$subacc</td><td>$acc[accname]</td></tr>";
			}
		}
	}


	# Set up table to display in
	$OUTPUT = "<center>
	<h3>Configure Balance Sheet</h3>
	<h4>Confirm</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>$hide
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=60%>
		$oesubdsp
		<tr><td><br></td></tr>
		$asssubdsp
		<tr><td><br></td></tr>
		<tr><td> </td><td align=center><input type=submit value='Confirm &raquo'></td></tr>
	<table>
	</form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}

# Write configuration
function write($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();

	# Validate array input
	foreach($oesub as $key => $sub){
		$v->isOk ($sub, "string", 0, 255, "Invalid Owners Equity Sub Heading number $key.");
	}

	if(isset($asssub)) {
		foreach($asssub as $key => $sub){
			$v->isOk ($sub, "string", 0, 255, "Invalid Assets Sub Heading number $key.");
		}
	}

	foreach($accno as $key => $accn){
		list($topacc, $accnum) = explode("/", $accn);
		$v->isOk ($topacc, "num", 3, 3, "Invalid account number.");
		$v->isOk ($accnum, "num", 3, 3, "Invalid account number.");
	}

	foreach($heads as $key => $head){
		$v->isOk ($head, "string", 1, 10, "Invalid Sub Heading Selection.");
	}

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

	# First Empty the Table (Warning Was Given)
	core_connect();
	$sql = "DELETE FROM bal_sheet WHERE div = '".USER_DIV."'";
	$emptyRslt = db_exec($sql) or errDie("Unable to clean the balance sheet settings table before writing.",SELF);

	// Shooo !!!! Its hot in here, deal with them b***hies
	# Write Owner's Equity sub headigns and their accounts
	foreach($oesub as $oref => $sub){
		$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('OESUB', '$oref', '$sub', '".USER_DIV."')";
		$bsRslt = db_exec($query) or errDie("Unable to insert Balance Sheet settings to database",SELF);
		$subaccs = getaccs($oref, $heads, 'o', $accno);
		foreach($subaccs as $kkey => $subacc){
			$acc = getaccnum($subacc);
			$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('OEACC','$oref','$acc[accid]', '".USER_DIV."')";
			$accRslt = db_exec($query) or errDie("Unable to insert Balance sheet settings to Cubit.",SELF);
		}
	}

	if(isset($asssub)) {
		# Write Assets sub headings and their Accounts
		foreach($asssub as $aref => $sub){
			$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('ASSSUB', '$aref', '$sub', '".USER_DIV."')";
			$bsRslt = db_exec($query) or errDie("Unable to insert Balance Sheet settings to database",SELF);
			$subaccs = getaccs($aref, $heads, 'a', $accno);
			foreach($subaccs as $kkey => $subacc){
				$acc = getaccnum($subacc);
				$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('ASSACC','$aref','$acc[accid]', '".USER_DIV."')";
				$accRslt = db_exec($query) or errDie("Unable to insert Balance sheet settings to Cubit.",SELF);
			}
		}
	}

	// Status Report
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Balance Sheet Settings</th></tr>
		<tr class=datacell><td>The Selected Balance Sheet Settings were successfully added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}

# Get heading specific accounts ($ksub = heading key)
function getaccs($ksub, $heads, $type, $accno){
	$accs = array();
	foreach($heads as $key => $head){
		if($head == "$type$ksub"){
			$accs[] = $accno[$key];
		}
	}
	return $accs;
}


# Get account info by accno (XXX/XXX)
function getaccnum($accno){
	list($topacc, $accnum) = explode("/", $accno);
	core_connect();
	$sql = "SELECT * FROM trial_bal WHERE accnum = '$accnum' AND topacc = '$topacc' LIMIT 1";
	$acccRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$acc = pg_fetch_array ($acccRslt);

	# Return array
	return $acc;
}


function extracthead($num, $asssub, $oesub){
	#  Replace all numbers to get string
	$nums = preg_replace("/[\d]/", "", $num);

	# Replace all words to find the key number
	$key = str_replace($nums, "", $num);

	# If first letter of string is 'a' then the key is in assets
	if(substr($nums, 0, 1) == 'a'){
		return $asssub[$key];
	}else{
		return $oesub[$key];
	}
}
?>

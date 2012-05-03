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
require("../settings.php");
require("../core-settings.php");
require("budget.lib.php");


# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "details":
			$OUTPUT = details($_POST);
			break;

		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;
	}
} else {
	# Display default output
	$OUTPUT = slctOpt();
}

# get templete
require("../template.php");

# Select Accounts
function slctOpt()
{
	global $TYPES, $YEARS;
	$typesel = extlib_mksel("budtype", $TYPES);
	$fromyrsel = extlib_cpsel("fromyr", $YEARS, YR_DB);
	$toyrsel = extlib_cpsel("toyr", $YEARS, YR_DB);

	// Options Layout
	$Opts = "<center>
	<h3> New Yearly Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=details>
	<table ".TMPL_tblDflts." align=center>
		<tr>
			<th colspan=3>Details</th>
		</tr>
		<tr class='bg-odd'>
			<td>Budget Name</td>
			<td><input type=text size=30 name=budname></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<th colspan=3>Options</th>
		</tr>
		<tr class='bg-odd'>
			<td>Budget For</td>
			<td>
				<input type=radio name=budfor value=cost checked=yes>Cost Centers &nbsp;&nbsp;
				<input type=radio name=budfor value=acc>Accounts
			</td>
		<tr class='bg-even'>
			<td>Budget Type</td>
			<td>$typesel</td>
		</tr>
		<tr class='bg-odd'>
			<td>Budget Year</td>
			<td>$fromyrsel to $toyrsel</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align=right>
				<input type=submit value='Continue &raquo'>
			</td>
		</tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $Opts;
}

# Error Handler
function error($_POST, $error)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	/* Toggle options */
	global $BUDFOR, $TYPES, $YEARS;
	$typesel = extlib_cpsel("budtype", $TYPES, $budtype);
	$fromyrsel = extlib_cpsel("fromyr", $YEARS, $fromyr);
	$toyrsel = extlib_cpsel("toyr", $YEARS, $toyr);

	# keep the charge vat option stable
	$chc = "";
	$cha = "";
	if($budfor == 'cost'){
		$chc= "checked=yes";
	}else{
		$cha = "checked=yes";
	}

	/* End Toggle Options */

	// Options Layout
	$error = "<center>
	<h3> New Yearly Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=details>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>$error</th></tr>
	<tr><th colspan=2>Details</th></tr>
	<tr class='bg-odd'><td>Budget Name</td><td><input type=text size=30 name=budname value='$budname'></td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-odd'><td>Budget For</td><td><input type=radio name=budfor value=cost $chc>Cost Centers &nbsp;&nbsp; <input type=radio name=budfor value=acc $cha>Accounts</td>
	<tr class='bg-even'><td>Budget Type</td><td>$typesel</td>
	<tr class='bg-odd'><td>Budget Year</td><td>$fromyrsel to $toyrsel</td>
	<tr><td><br></td></tr>
	<tr><td align=right>&nbsp;</td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $error;
}

# Enter Details of Transaction
function details($_POST, $errata = "<br>")
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($budname, "string", 1, 255, "Invalid Budget Name.");
	$v->isOk ($budfor, "string", 1, 20, "Invalid Budget for option.");
	$v->isOk ($budtype, "string", 1, 20, "Invalid Budget type.");
	$v->isOk ($fromyr, "string", 1, 20, "Invalid Budget year.");
	$v->isOk ($toyr, "string", 1, 20, "Invalid Budget year.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return error($_POST, $confirm);
	}

	global $BUDFOR, $TYPES, $YEARS;
	$vbudfor = $BUDFOR[$budfor];
	$vbudtype = $TYPES[$budtype];
	$vfromyr = $YEARS[$fromyr];
	$vtoyr = $YEARS[$toyr];


	/* Toggle Options */
	$list = "";
	# budget for
	if($budfor == 'cost'){
		# cost centers
		db_connect();
		$sql = "SELECT * FROM costcenters WHERE div = '".USER_DIV."' ORDER BY centername ASC";
		$ccRslt = db_exec($sql);
		if(pg_numrows($ccRslt) < 1){
			return "<li>There are No cost centers in Cubit.";
		}
		$head = "<tr><th>Select Cost Centers</th>";
		while($cc = pg_fetch_array($ccRslt)){
			$list .= "<tr class='bg-odd'><td><input type=checkbox name=ccids[] value='$cc[ccid]'>$cc[centercode] - $cc[centername]</td>";

			# Budget prd
			if($fromyr < $toyr){
				for($i = $fromyr; $i <= $toyr; $i++){
					if (!isset($amts[$cc["ccid"]][$i])) $amts[$cc["ccid"]][$i] = 0;
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$cc[ccid]][$i] value='".$amts[$cc["ccid"]][$i]."'></td>";
				}
			}elseif($fromyr > $toyr){
				for($i = $fromyr; $i <= 10; $i++){
					if (!isset($amts[$cc["ccid"]][$i])) $amts[$cc["ccid"]][$i] = 0;
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$cc[ccid]][$i] value='".$amts[$cc["ccid"]][$i]."'></td>";
				}
				for($i = 0; $i <= $toyr; $i++){
					if (!isset($amts[$cc["ccid"]][$i])) $amts[$cc["ccid"]][$i] = 0;
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$cc[ccid]][$i] value='".$amts[$cc["ccid"]][$i]."'></td>";
				}
			}else{
				if (!isset($amts[$cc["ccid"]][$toyr])) $amts[$cc["ccid"]][$toyr] = 0;
				$list .= "<td>".CUR." <input type=text size=7 name=amts[$cc[ccid]][$toyr] value='".$amts[$cc["ccid"]][$toyr]."'></td>";
			}
		}
	}elseif($budfor == 'acc'){
		# budget type
		if($budtype == 'exp'){
			$acctype = "E";
		}elseif($budtype == 'inc'){
			$acctype = "I";
		}else{
			$acctype = "B";
		}

		# accounts
		core_connect();
		$sql = "SELECT * FROM accounts WHERE acctype = '$acctype' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
		}
		$head = "<tr><th>Select Accounts</th>";
		while($acc = pg_fetch_array($accRslt)){
			$list .= "<tr class='bg-odd'><td><input type=checkbox name=accids[] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			# Budget prd
			if($fromyr < $toyr){
				for($i = $fromyr; $i <= $toyr; $i++){
					if (!isset($amts[$acc["accid"]][$i])) $amts[$acc["accid"]][$i] = 0;
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$acc[accid]][$i] value='".$amts[$acc["accid"]][$i]."'></td>";
				}
			}elseif($fromyr > $toyr){
				for($i = $fromyr; $i < 10; $i++){
					if (!isset($amts[$acc["accid"]][$i])) $amts[$acc["accid"]][$i] = 0;
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$acc[accid]][$i] value='".$amts[$acc["accid"]][$i]."'></td>";
				}
				for($i = 0; $i <= $toyr; $i++){
					if (!isset($amts[$acc["accid"]][$i])) $amts[$acc["accid"]][$i] = 0;
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$acc[accid]][$i] value='".$amts[$acc["accid"]][$i]."'></td>";
				}
			}else{
				if (!isset($amts[$acc["accid"]][$toyr])) $amts[$acc["accid"]][$toyr] = 0;
				$list .= "<td>".CUR." <input type=text size=7 name=amts[$acc[accid]][$toyr] value='".$amts[$acc["accid"]][$toyr]."'></td>";
			}
		}
	}

	# Budget headings
	if($fromyr < $toyr){
		for($i = $fromyr; $i <= $toyr; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}elseif($fromyr > $toyr){
		for($i = $fromyr; $i < 10; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
		for($i = 0; $i <= $toyr; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}else{
		$head .= "<th>$YEARS[$toyr]</th>";
	}
	$head .= "</tr>";

	/* End Toggle Options */

	$details = "<center>
	<h3> New Yearly Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=budname value='$budname'>
	<input type=hidden name=budfor value='$budfor'>
	<input type=hidden name=budtype value='$budtype'>
	<input type=hidden name=fromyr value='$fromyr'>
	<input type=hidden name=toyr value='$toyr'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr>
	<tr class='bg-odd'><td>Budget Name</td><td>$budname</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-odd'><td>Budget For</td><td>$vbudfor</td>
	<tr class='bg-even'><td>Budget Type</td><td>$vbudtype</td>
	<tr class='bg-odd'><td>Budget Year</td><td>$vfromyr to $vtoyr</td>
	<tr><td colspan=2>$errata</td></tr>
	</table>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
		$head
		$list
	</table>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><td><br></td></tr>
	<tr><td>&nbsp;</td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $details;
}

# Enter Details of Transaction
function confirm($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($budname, "string", 1, 255, "Invalid Budget Name.");
	$v->isOk ($budfor, "string", 1, 20, "Invalid Budget for option.");
	$v->isOk ($budtype, "string", 1, 20, "Invalid Budget type.");
	$v->isOk ($fromyr, "string", 1, 20, "Invalid Budget year.");
	$v->isOk ($toyr, "string", 1, 20, "Invalid Budget year.");

	if($budfor == 'acc'){
		if(isset($accids)){
			foreach($accids as $akey => $accid){
				$v->isOk ($accid, "num", 1, 50, "Invalid Account number.");
				foreach($amts[$accid] as $skey => $amtr){
					$v->isOk ($amts[$accid][$skey], "float", 1, 20, "Invalid Budget amount.");
				}
			}
		}else{
			$v->isOk ("#", "num", 0, 0, "Error : please select at least one account.");
		}
	}elseif($budfor == 'cost'){
		if(isset($ccids)){
			foreach($ccids as $akey => $ccid){
				$v->isOk ($ccid, "num", 1, 50, "Invalid Cost Center.");
				foreach($amts[$ccid] as $skey => $amtr){
					$v->isOk ($amts[$ccid][$skey], "float", 1, 20, "Invalid Budget amount.");
				}
			}
		}else{
			$v->isOk ("#", "num", 0, 0, "Error : please select at least one cost center.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return details($_POST, $confirm);
	}

	$ce = new Validate();
	if (isset($accids)) {
		foreach ($accids as $akey => $accid) {
			$tot = $amts[$accid][BUDGET_YEARS_INDEX];
			$mon_tot = budgetTotalFromMonth($accid, "acc");

			if (strlen($mon_tot) > 0 && $tot != $mon_tot) {
				$accRs = get("core", "*", "accounts", "accid", $accid);
				$acc  = pg_fetch_array($accRs);
				$acc_name = "$acc[topacc]/$acc[accnum] - $acc[accname]";

				$ce->addError("", "Monthly annual budget total of ".CUR."$mon_tot doesn't
					match proposed total amount of ".CUR."$tot for Account: $accid $acc_name.");
			}
		}
	} else if (isset($ccids)) {
		foreach ($ccids as $akey => $ccid) {
			$tot = $amts[$ccid][BUDGET_YEARS_INDEX];
			$mon_tot = budgetTotalFromMonth($ccid, "acc");

			if (strlen($mon_tot) > 0 && $tot != $mon_tot) {
				$ccRs = get("cubit", "*", "costcenters", "ccid", $ccid);
				$cc  = pg_fetch_array($ccRs);
				$cc_name = "$cc[centercode] - $cc[centername]";

				$ce->addError("", "Monthly annual budget total of ".CUR."$mon_tot doesn't
					match proposed total amount of ".CUR."$tot for Cost Center: $cc_name.");
			}
		}
	}

	$mismatches = "";
	if ($ce->isError ()) {
		$mm = $ce->getErrors();
		foreach ($mm as $e) {
			$mismatches .= "<li class=err>".$e["msg"]."</li>";
		}
		return details($_POST, $mismatches);
	}

	global $BUDFOR, $TYPES, $YEARS;
	$vbudfor = $BUDFOR[$budfor];
	$vbudtype = $TYPES[$budtype];
	$vfromyr = $YEARS[$fromyr];
	$vtoyr = $YEARS[$toyr];

	/* Toggle Options */
	$list = "";
	# budget for
	if($budfor == 'cost'){
		$head = "<tr><th>Cost Centers</th>";
		foreach($ccids as $ckey => $ccid){
			$ccRs = get("cubit", "*", "costcenters", "ccid", $ccid);
			$cc  = pg_fetch_array($ccRs);
			$list .= "<tr class='bg-odd'><td><input type=hidden name=ccids[] value='$cc[ccid]'>$cc[centercode] - $cc[centername]</td>";

			foreach($amts[$ccid] as $sprd => $amtr){
				$amtr = sprint($amtr);
				$list .= "<td align=right><input type=hidden name=amts[$cc[ccid]][$sprd] value='$amtr'>".CUR." $amtr</td>";
			}
			$list .= "</tr>";
		}

	}elseif($budfor == 'acc'){
		$head = "<tr><th>Accounts</th>";
		foreach($accids as $akey => $accid){
			$accRs = get("core", "*", "accounts", "accid", $accid);
			$acc  = pg_fetch_array($accRs);
			$list .= "<tr class='bg-odd'><td><input type=hidden name=accids[] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			foreach($amts[$accid] as $sprd => $amtr){
				$amtr = sprint($amtr);
				$list .= "<td align=right><input type=hidden name=amts[$acc[accid]][$sprd] value='$amtr'>".CUR." $amtr</td>";
			}
			$list .= "</tr>";
		}
	}

	# Budget headings
	if($fromyr < $toyr){
		for($i = $fromyr; $i <= $toyr; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}elseif($fromyr > $toyr){
		for($i = $fromyr; $i < 10; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
		for($i = 0; $i <= $toyr; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}else{
		$head .= "<th>$YEARS[$toyr]</th>";
	}
	$head .= "</tr>";

	// $totamt = sprint(array_sum($amts));
	// $list .= "<tr class='bg-even'><td><b>Total Budget Amount</b></td><td align=right><b>".CUR." $totamt</b></td></tr>";

	/* End Toggle Options */

	$confirm = "<center>
	<h3> Confirm New Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=budname value='$budname'>
	<input type=hidden name=budfor value='$budfor'>
	<input type=hidden name=budtype value='$budtype'>
	<input type=hidden name=fromyr value='$fromyr'>
	<input type=hidden name=toyr value='$toyr'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr>
	<tr class='bg-odd'><td>Budget Name</td><td>$budname</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-odd'><td>Budget For</td><td>$vbudfor</td>
	<tr class='bg-even'><td>Budget Type</td><td>$vbudtype</td>
	<tr class='bg-odd'><td>Budget Year</td><td>$vfromyr to $vtoyr</td>
	<tr><td><br></td></tr>
	</table>

	$mismatches
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	$head
	$list
	</table>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><td><br></td></tr>
	<tr><td><input type='submit' name='key' value='&laquo Correction'></td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $confirm;
}

# Write
function write($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($budname, "string", 1, 255, "Invalid Budget Name.");
	$v->isOk ($budtype, "string", 1, 20, "Invalid Budget type.");
	$v->isOk ($budfor, "string", 1, 20, "Invalid Budget for option.");
	$v->isOk ($fromyr, "string", 1, 20, "Invalid Budget year.");
	$v->isOk ($toyr, "string", 1, 20, "Invalid Budget year.");

	if($budfor == 'acc'){
		if(isset($accids)){
			foreach($accids as $akey => $accid){
				$v->isOk ($accid, "num", 1, 50, "Invalid Account number.");
				foreach($amts[$accid] as $skey => $amtr){
					$v->isOk ($amts[$accid][$skey], "float", 1, 20, "Invalid Budget amount.");
				}
			}
		}else{
			$v->isOk ("#", "num", 0, 0, "Error : please select at least one account.");
		}
	}elseif($budfor == 'cost'){
		if(isset($ccids)){
			foreach($ccids as $akey => $ccid){
				$v->isOk ($ccid, "num", 1, 50, "Invalid Cost Center.");
				foreach($amts[$ccid] as $skey => $amtr){
					$v->isOk ($amts[$ccid][$skey], "float", 1, 20, "Invalid Budget amount.");
				}
			}
		}else{
			$v->isOk ("#", "num", 0, 0, "Error : please select at least one cost center.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return details($_POST, $confirm);
	}

	global $BUDFOR, $TYPES, $YEARS;
	$vbudfor = $BUDFOR[$budfor];
	$vbudtype = $TYPES[$budtype];
	$vfromyr = $YEARS[$fromyr];
	$vtoyr = $YEARS[$toyr];

	db_connect();
	$sql = "INSERT INTO budgets(budname, budtype, budfor, fromprd, toprd, edate, prdtyp, div) VALUES('$budname', '$budtype', '$budfor', '$fromyr', '$toyr', now(), 'yr', '".USER_DIV."')";
	$inRs = db_exec($sql);

	$budid = pglib_lastid("budgets", "budid");

	if($budfor == 'acc'){
		foreach($accids as $akey => $id){
			foreach($amts[$id] as $sprd => $amt){
				$sql = "INSERT INTO buditems(budid, id, prd, amt) VALUES('$budid', '$id', '$sprd', '$amt')";
				$itRs = db_exec($sql);
			}
		}
	}else{
		foreach($ccids as $akey => $id){
			foreach($amts[$id] as $sprd => $amt){
				$sql = "INSERT INTO buditems(budid, id, prd, amt) VALUES('$budid', '$id', '$sprd', '$amt')";
				$itRs = db_exec($sql);
			}
		}
	}

	// Start layout
	$write ="<center>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><th colspan=2>New Yearly Budget created</th></tr>
		<tr><td class='bg-odd' colspan=2>New Yearly Budget <b>$budname</b> has been created.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $write;
}
?>

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
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			if (isset($_GET["budid"])){
				$OUTPUT = details($_GET);
			} else {
				# Display default output
				$OUTPUT = "<li class=err> - Invalid use of module.";
			}
	}
} else {
	if (isset($_GET["budid"])){
		$OUTPUT = details($_GET);
	} else {
		# Display default output
		$OUTPUT = "<li class=err> - Invalid use of module.";
	}
}

# get templete
require("../template.php");

# Enter Details of Transaction
function details($_POST, $errata = "<br>")
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	global $BUDFOR, $PERIODS, $TYPES, $YEARS;
	$vbudfor = $BUDFOR[$bud['budfor']];
	$typesel = extlib_cpsel("budtype", $TYPES, $bud['budtype']);
	$vfromyr = $YEARS[$bud['fromprd']];
	$vtoyr = $YEARS[$bud['toprd']];

	/* Toggle Options */
	$list = "";
	# budget for
	if($bud['budfor'] == 'cost'){
		# cost centers
		db_connect();
		$sql = "SELECT * FROM costcenters WHERE div = '".USER_DIV."' ORDER BY centername ASC";
		$ccRslt = db_exec($sql);
		if(pg_numrows($ccRslt) < 1){
			return "<li>There are No cost centers in Cubit.";
		}
		$head = "<tr><th>Select Cost Centers</th>";
		while($cc = pg_fetch_array($ccRslt)){
			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$cc[ccid]' AND budid = '$budid'");
			if(pg_numrows($lstRs) > 0){
				$list .= "<tr class='bg-odd'><td><input type=checkbox name=ccids[] value='$cc[ccid]' checked=yes>$cc[centercode] - $cc[centername]</td>";
				while($lst = pg_fetch_array($lstRs)){
					$list .= "<td align=right>".CUR." <input type=text size=7 name=amts[$cc[ccid]][$lst[prd]] value='$lst[amt]'></td>";
				}
			}else{
				$list .= "<tr class='bg-odd'><td><input type=checkbox name=ccids[] value='$cc[ccid]'>$cc[centercode] - $cc[centername]</td>";
				# Budget prd
				if($bud['fromprd'] < $bud['toprd']){
					for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
						$list .= "<td>".CUR." <input type='text' size='7' name='amts[$cc[ccid]][$i]' value='0'></td>";
					}
				}elseif($bud['fromprd'] > $bud['toprd']){
					for($i = $bud['fromprd']; $i < 10; $i++){
						$list .= "<td>".CUR." <input type='text' size='7' name='amts[$cc[ccid]][$i]' value='0'></td>";
					}
					for($i = 0; $i <= $bud['toprd']; $i++){
						$list .= "<td>".CUR." <input type='text' size='7' name='amts[$cc[ccid]][$i]' value='0'></td>";
					}
				}else{
					$list .= "<td>".CUR." <input type=text size=7 name=amts[$cc[ccid]][$i] value='0'></td>";
				}
			}
		}
	}elseif($bud['budfor'] == 'acc'){
		# budget type
		if($bud['budtype'] == 'exp'){
			$acctype = "E";
		}elseif($bud['budtype'] == 'inc'){
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
			$accid = $acc["accid"];

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$acc[accid]' AND budid = '$budid'");
			if(pg_numrows($lstRs) > 0){
				$list .= "
				<tr class='bg-odd'>
					<td><input type=checkbox name='accids[$accid]' value='$acc[accid]' checked=yes>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

				while($lst = pg_fetch_array($lstRs)){
					if (isset($amts[$accid][$lst["prd"]])) $lst["amt"] = $amts[$accid][$lst["prd"]];

					$list .= "<td align=right>".CUR." <input type=text size=7 name='amts[$accid][$lst[prd]]' value='$lst[amt]'></td>";
				}
			}else{
				if (isset($accids[$accid])) {
					$ch = "checked";
				} else {
					$ch = "";
				}

				$list .= "
				<tr class='bg-odd'>
					<td><input type=checkbox name='accids[$accid]' value='$acc[accid]' $ch>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

				# Budget prd
				if($bud['fromprd'] <= $bud['toprd']){
					for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
						if (!isset($amts[$accid][$i])) $amts[$accid][$i] = 0;

						$list .= "<td>".CUR." <input type=text size=7 name='amts[$accid][$i]' value='".$amts[$accid][$i]."'></td>";
					}
				}elseif($bud['fromprd'] > $bud['toprd']){
					for($i = $bud['fromprd']; $i < 10; $i++){
						if (!isset($amts[$accid][$i])) $amts[$accid][$i] = 0;

						$list .= "<td>".CUR." <input type=text size=7 name='amts[$accid][$i]' value='".$amts[$accid][$i]."'></td>";
					}
					for($i = 0; $i <= $bud['toprd']; $i++){
						if (!isset($amts[$accid][$i])) $amts[$accid][$i] = 0;

						$list .= "<td>".CUR." <input type=text size=7 name='amts[$accid][$i]' value='".$amts[$accid][$i]."'></td>";
					}
				//}else{
				//	$list .= "<td>".CUR." <input type=text size=7 name=amts[$acc[accid]][$bud[toprd]] value='0'></td>";
				}
			}
		}
	}

	# Budget headings
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i < 10; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
		for($i = 0; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$YEARS[$i]</th>";
	}
	$head .= "</tr>";

	/* End Toggle Options */

	$details = "<center>
	<h3> Edit Yearly Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=budid value='$budid'>
	<input type=hidden name=budfor value='$bud[budfor]'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr><tr class='bg-odd'><td>Budget Name</td><td><input type=text size=30 name=budname value='$bud[budname]'></td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-odd'><td>Budget For</td><td>$vbudfor</td>
	<tr class='bg-even'><td>Budget Type</td><td>$typesel</td>
	<tr class='bg-odd'><td>Budget Year</td><td>$vfromyr to $vtoyr</td>
	<tr><td colspan=2>$errata</td></tr>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	$head
	$list
	</table>

	<tr><td><br></td></tr>
	<tr><td><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Continue &raquo'></td></tr>
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
	$v->isOk ($budid, "num", 1, 20, "Invalid Budget id.");
	$v->isOk ($budname, "string", 1, 255, "Invalid Budget Name.");
	$v->isOk ($budtype, "string", 1, 20, "Invalid Budget type.");

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
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	global $BUDFOR, $PERIODS, $TYPES, $YEARS;
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vbudtype = $TYPES[$budtype];
	$vfromyr = $YEARS[$bud['fromprd']];
	$vtoyr = $YEARS[$bud['toprd']];

	/* Toggle Options */
	$list = "";
	# budget for
	if($bud['budfor'] == 'cost'){
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

	}elseif($bud['budfor'] == 'acc'){
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
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i < 10; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
		for($i = 0; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$YEARS[$i]</th>";
	}
	$head .= "</tr>";

	// $totamt = sprint(array_sum($amts));
	// $list .= "<tr class='bg-even'><td><b>Total Budget Amount</b></td><td align=right><b>".CUR." $totamt</b></td></tr>";

	/* End Toggle Options */

	$confirm = "<center>
	<h3> Confirm New Yearly Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=budid value='$budid'>
	<input type=hidden name=budname value='$budname'>
	<input type=hidden name=budtype value='$budtype'>
	<input type=hidden name=budfor value='$bud[budfor]'>
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
	<tr><td><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Continue &raquo'></td></tr>
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
	$v->isOk ($budid, "num", 1, 20, "Invalid Budget id.");
	$v->isOk ($budname, "string", 1, 255, "Invalid Budget Name.");
	$v->isOk ($budtype, "string", 1, 20, "Invalid Budget type.");

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

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	global $BUDFOR, $PERIODS, $TYPES, $YEARS;
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vbudtype = $TYPES[$budtype];
	$vfromyr = $YEARS[$bud['fromprd']];
	$vtoyr = $YEARS[$bud['toprd']];

	db_connect();
	$sql = "UPDATE budgets SET budname = '$budname', budtype = '$budtype' WHERE budid = '$budid'";
	$inRs = db_exec($sql);

	# delete old values
	$rs = db_exec("DELETE FROM buditems WHERE budid = '$budid'");

	if($bud['budfor'] == 'acc'){
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
	$write = "<center>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><th colspan=2>Edit Yearly Budget</th></tr>
		<tr><td class='bg-odd' colspan=2>Yearly Budget <b>$budname</b> has been edited.</td></tr>
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

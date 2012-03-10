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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;

		default:
			if (isset($HTTP_GET_VARS["budid"])){
				$OUTPUT = details($HTTP_GET_VARS);
			} else {
				# Display default output
				$OUTPUT = "<li class=err> - Invalid use of module.";
			}
	}
} else {
	if (isset($HTTP_GET_VARS["budid"])){
		$OUTPUT = details($HTTP_GET_VARS);
	} else {
		# Display default output
		$OUTPUT = "<li class=err> - Invalid use of module.";
	}
}

# get templete
require("../template.php");

# Enter Details of Transaction
function details($HTTP_POST_VARS, $errata = "<br>")
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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

	global $BUDFOR, $PERIODS, $TYPES;
	$vbudfor = $BUDFOR[$bud['budfor']];
	$typesel = extlib_cpsel("budtype", $TYPES, $bud['budtype']);
	$vfromprd = $PERIODS[$bud['fromprd']];
	$vtoprd = $PERIODS[$bud['toprd']];

	/* Toggle Options */
	$list = "";
	# budget for
	$js_funcs = "var tot_annual = new Array();";
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
			$ccid = $cc["ccid"];

			$tot_annual = 0;
			$js_totannuals = array();

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$ccid' AND budid = '$budid'");
			if(pg_numrows($lstRs) > 0){
				$list .= "
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td><input type=checkbox name='ccids[$ccid]' id='cb$ccid' value='$ccid' checked=yes>$cc[centercode] - $cc[centername]</td>";

				while($lst = pg_fetch_array($lstRs)){
					$tot_annual += $lst["amt"];
					$js_totannuals[] = "amts_${ccid}_$lst[prd]";
					if (isset($amts[$ccid][$lst["prd"]])) $lst["amt"] = $amts[$ccid][$lst["prd"]];

					$list .= "<td align=right>".CUR." <input type=text size=7 onChange='changedVal$ccid();' id='amts_${ccid}_$lst[prd]' name=amts[$ccid][$lst[prd]] value='$lst[amt]'></td>";
				}
			}else{
				if (isset($ccids[$ccid])) {
					$ch = "checked";
				} else {
					$ch = "";
				}

				$list .= "
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td><input type=checkbox name='ccids[$ccid]' id='cb$ccid' value='$ccid' $ch>$cc[centercode] - $cc[centername]</td>";

				# Budget prd
				if ($bud['fromprd'] <= $bud['toprd']) {
					for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
						$js_totannuals[] = "amts_${ccid}_$i";
						if (!isset($amts[$ccid][$i])) $amts[$ccid][$i] = 0;
						$tot_annual += $amts[$ccid][$i];

						$list .= "<td nowrap>".CUR." <input type=text size=7 onChange='changedVal$ccid();' id='amts_${ccid}_$i' name=amts[$ccid][$i] value='".$amts[$ccid][$i]."'></td>";
					}
				} else if ($bud['fromprd'] > $bud['toprd']) {
					for($i = $bud['fromprd']; $i <= 12; $i++){
						$js_totannuals[] = "amts_${ccid}_$i";
						if (!isset($amts[$ccid][$i])) $amts[$ccid][$i] = 0;
						$tot_annual += $amts[$ccid][$i];

						$list .= "<td nowrap>".CUR." <input type=text size=7 onChange='changedVal$ccid();' id='amts_${ccid}_$i' name=amts[$ccid][$i] value='".$amts[$ccid][$i]."'></td>";
					}
					for($i = 1; $i <= $bud['toprd']; $i++){
						$js_totannuals[] = "amts_${ccid}_$i";
						if (!isset($amts[$ccid][$i])) $amts[$ccid][$i] = 0;
						$tot_annual += $amts[$ccid][$i];

						$list .= "<td nowrap>".CUR." <input type=text size=7 onChange='changedVal$ccid();' id='amts_${ccid}_$i' name=amts[$ccid][$i] value='".$amts[$ccid][$i]."'></td>";
					}
				//}else{
				//	$list .= "<td>".CUR." <input type=text size=7 name=amts[$ccid][$i] value='0'></td>";
				}
			}

			$js_funcs .= "
			function changedVal$ccid() {
				getObject('cb$ccid').checked = true;

				tot_annual[$ccid] = 0;";

			foreach ($js_totannuals as $fid) {
				$js_funcs .= "
					obj = getObject('$fid');
					val = parseFloat(obj.value);
					obj.value = val.toFixed(2);
					tot_annual[$ccid] += val;";
			}

			$js_funcs .= "
				getObject('annual$ccid').innerHTML = '".CUR." ' + tot_annual[$ccid].toFixed(2);
			}

			tot_annual[$ccid] = $tot_annual;\n";

			$tot_annual = sprint($tot_annual);
			$list .= "<td nowrap><div id='annual$ccid'>".CUR." $tot_annual</div></td>";
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
		$i = 0;
		while($acc = pg_fetch_array($accRslt)){
			$accid = $acc["accid"];

			$tot_annual = 0;
			$js_totannuals = array();

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id='$accid' AND budid='$budid'");
			if(pg_numrows($lstRs) > 0){
				$list .= "
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td><input type=checkbox name='accids[$accid]' id='cb$accid' value='$acc[accid]' checked=yes>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

				while($lst = pg_fetch_array($lstRs)){
					$tot_annual += $lst["amt"];
					$js_totannuals[] = "amts_${accid}_$lst[prd]";
					if (isset($amts[$accid][$lst["prd"]])) $lst["amt"] = $amts[$accid][$lst["prd"]];

					$list .= "<td align='right'>".CUR." <input type='text' size='7' onChange='changedVal$accid();' id='amts_${accid}_$lst[prd]' name='amts[$accid][$lst[prd]]' value='$lst[amt]'></td>";
				}
			}else{
				if (isset($accids[$accid])) {
					$ch = "checked";
				} else {
					$ch = "";
				}

				$list .= "
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td><input type=checkbox name='accids[$accid]' id='cb$accid' value='$acc[accid]' $ch>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

				# Budget prd
				if ($bud['fromprd'] <= $bud['toprd']) {
					for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
						$js_totannuals[] = "amts_${accid}_$i";
						if (!isset($amts[$accid][$i])) $amts[$accid][$i] = 0;
						$tot_annual += $amts[$accid][$i];

						$list .= "<td nowrap>".CUR." <input type='text' size='7' onChange='changedVal$accid();' id='amts_${accid}_$i' name='amts[$accid][$i]' value='".$amts[$accid][$i]."'></td>";
					}
				} else if ($bud['fromprd'] > $bud['toprd']) {
					for($i = $bud['fromprd']; $i <= 12; $i++){
						$js_totannuals[] = "amts_${accid}_$i";
						if (!isset($amts[$accid][$i])) $amts[$accid][$i] = 0;
						$tot_annual += $amts[$accid][$i];

						$list .= "<td nowrap>".CUR." <input type='text' size='7' onChange='changedVal$accid();' id='amts_${accid}_$i' name='amts[$accid][$i]' value='".$amts[$accid][$i]."'></td>";
					}
					for($i = 1; $i <= $bud['toprd']; $i++){
						$js_totannuals[] = "amts_${accid}_$i";
						if (!isset($amts[$accid][$i])) $amts[$accid][$i] = 0;
						$tot_annual += $amts[$accid][$i];

						$list .= "<td nowrap>".CUR." <input type='text' size='7' onChange='changedVal$accid();' id='amts_${accid}_$i' name='amts[$accid][$i]' value='".$amts[$accid][$i]."'></td>";
					}
				//}else{
				//	$list .= "<td>".CUR." <input type=text size=7 name=amts[$acc[accid]][$i] value='0'></td>";
				}
			}

			$js_funcs .= "
			function changedVal$accid() {
				getObject('cb$accid').checked = true;

				tot_annual[$accid] = 0;";

			foreach ($js_totannuals as $fid) {
				$js_funcs .= "
					obj = getObject('$fid');
					val = parseFloat(obj.value);
					obj.value = val.toFixed(2);
					tot_annual[$accid] += val;";
			}

			$js_funcs .= "
				getObject('annual$accid').innerHTML = '".CUR." ' + tot_annual[$accid].toFixed(2);
			}

			tot_annual[$accid] = $tot_annual;\n";

			$tot_annual = sprint($tot_annual);
			$list .= "<td nowrap><div id='annual$accid'>".CUR." $tot_annual</div></td>";
		}
	}

	/* Toggle Options
	$list = "";
	# budget for
	if($bud['budfor'] == 'cost'){
		$head = "<tr><th>Cost Centers</th>";

		db_connect();
		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$ccRs = get("cubit", "*", "costcenters", "ccid", $bit['id']);
			$cc  = pg_fetch_array($ccRs);
			$list .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=ccids[] value='$cc[ccid]'>$cc[centercode] - $cc[centername]</td>";

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			while($lst = pg_fetch_array($lstRs)){
				$list .= "<td align=right>".CUR." <input type=text size=10 name=amts[$bit[id]][$lst[prd]] value='$lst[amt]'></td>";
			}
			$list .= "</tr>";
		}
	}elseif($bud['budfor'] == 'acc'){
		$head = "<tr><th>Accounts</th>";

		db_connect();
		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$accRs = get("core", "*", "accounts", "accid", $bit['id']);
			$acc  = pg_fetch_array($accRs);
			$list .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=accids[] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			while($lst = pg_fetch_array($lstRs)){
				$list .= "<td align=right>".CUR." <input type=text size=10 name=amts[$bit[id]][$lst[prd]] value='$lst[amt]'></td>";
			}
			$list .= "</tr>";
		}
	}*/

	# Budget headings
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i <= 12; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
		for($i = 1; $i <= $bud['toprd']; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$PERIODS[$i]</th>";
	}
	$head .= "
		<th>Annual Total</th>
	</tr>";

	/* End Toggle Options */

	$details = "<center>
	<h3> Edit Budget </h3>
	<script>
	$js_funcs
	</script>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=budid value='$budid'>
	<input type=hidden name=budfor value='$bud[budfor]'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr><tr bgcolor='".TMPL_tblDataColor1."'><td>Budget Name</td><td><input type=text size=30 name=budname value='$bud[budname]'></td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget For</td><td>$vbudfor</td>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Budget Type</td><td>$typesel</td>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget Period</td><td>$vfromprd to $vtoprd</td>
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
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $details;
}

# Enter Details of Transaction
function confirm($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	include("../libs/validate.lib.php");
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
		return details($HTTP_POST_VARS, $confirm);
	}

	$ce = new Validate();
	if (isset($ccids)) {
		foreach ($ccids as $akey => $ccid) {
			$tot = array_sum($amts[$ccid]);
			$yr_tot = budgetTotalFromYear($ccid, "cost");
			if (strlen($yr_tot) > 0 && $tot != $yr_tot) {
				$ccRs = get("cubit", "*", "costcenters", "ccid", $ccid);
				$cc  = pg_fetch_array($ccRs);
				$cc_name = "$cc[centercode] - $cc[centername]";

				$ce->addError("", "Yearly budget amount of ".CUR."$yr_tot doesn't
					match proposed total amount of ".CUR."$tot for Cost Center: $cc_name.");
			}
		}
	} else if (isset($accids)) {
		foreach ($accids as $akey => $accid) {
			$tot = array_sum($amts[$accid]);
			$yr_tot = budgetTotalFromYear($accid, "acc");
			if (strlen($yr_tot) > 0 && $tot != $yr_tot) {
				$accRs = get("core", "*", "accounts", "accid", $accid);
				$acc  = pg_fetch_array($accRs);
				$acc_name = "$acc[topacc]/$acc[accnum] - $acc[accname]";

				$ce->addError("", "Yearly budget amount of ".CUR."$yr_tot doesn't
					match proposed total amount of ".CUR."$tot for Account: $acc_name.");
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

	global $BUDFOR, $PERIODS, $TYPES;
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vbudtype = $TYPES[$budtype];
	$vfromprd = $PERIODS[$bud['fromprd']];
	$vtoprd = $PERIODS[$bud['toprd']];

	/* Toggle Options */
	$list = "";
	# budget for
	if($bud['budfor'] == 'cost'){
		$head = "<tr><th>Cost Centers</th>";
		foreach($ccids as $ckey => $ccid){
			$ccRs = get("cubit", "*", "costcenters", "ccid", $ccid);
			$cc  = pg_fetch_array($ccRs);
			$list .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=ccids[] value='$cc[ccid]'>$cc[centercode] - $cc[centername]</td>";

			$tot_annual = 0;
			foreach($amts[$ccid] as $sprd => $amtr){
				$tot_annual += ($amtr = sprint($amtr));
				$list .= "<td align='right' nowrap><input type=hidden name=amts[$cc[ccid]][$sprd] value='$amtr'>".CUR." $amtr</td>";
			}
			$list .= "
				<td nowrap>".CUR." ".sprint($tot_annual)."</td>
			</tr>";
		}

	}elseif($bud['budfor'] == 'acc'){
		$head = "<tr><th>Accounts</th>";
		foreach($accids as $akey => $accid){
			$accRs = get("core", "*", "accounts", "accid", $accid);
			$acc  = pg_fetch_array($accRs);
			$list .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=accids[] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			$tot_annual = 0;
			foreach($amts[$accid] as $sprd => $amtr){
				$tot_annual += ($amtr = sprint($amtr));

				$list .= "<td align='right' nowrap><input type=hidden name=amts[$acc[accid]][$sprd] value='$amtr'>".CUR." $amtr</td>";
			}
			$list .= "
				<td nowrap>".CUR." ".sprint($tot_annual)."</td>
			</tr>";
		}
	}

	# Budget headings
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i <= 12; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
		for($i = 1; $i <= $bud['toprd']; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$PERIODS[$i]</th>";
	}
	$head .= "
		<th>Annual Total</th>
	</tr>";

	// $totamt = sprint(array_sum($amts));
	// $list .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><b>Total Budget Amount</b></td><td align=right><b>".CUR." $totamt</b></td></tr>";

	/* End Toggle Options */

	$confirm = "<center>
	<h3> Confirm New Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=budid value='$budid'>
	<input type=hidden name=budname value='$budname'>
	<input type=hidden name=budtype value='$budtype'>
	<input type=hidden name=budfor value='$bud[budfor]'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget Name</td><td>$budname</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget For</td><td>$vbudfor</td>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Budget Type</td><td>$vbudtype</td>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget Period</td><td>$vfromprd to $vtoprd</td>
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
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $confirm;
}

# Write
function write($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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
		return details($HTTP_POST_VARS, $confirm);
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	global $BUDFOR, $PERIODS, $TYPES;
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vbudtype = $TYPES[$budtype];
	$vfromprd = $PERIODS[$bud['fromprd']];
	$vtoprd = $PERIODS[$bud['toprd']];

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
		<tr><th colspan=2>Edit Budget</th></tr>
		<tr><td bgcolor='".TMPL_tblDataColor1."' colspan=2>Budget <b>$budname</b> has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $write;
}
?>

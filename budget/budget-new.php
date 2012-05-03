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
function slctOpt($errors="")
{
	global $_POST;
	extract ($_POST);

	$fields = array();
	$fields["budname"] = "Financial Budget";

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	global $BUDFOR, $TYPES, $PERIODS;
	global $MONPRD, $PRDMON;
	$typesel = extlib_mksel("budtype", $TYPES);
	$fromprdsel = extlib_cpsel("fromprd", $PERIODS, $PRDMON[1]);
	$toprdsel = extlib_cpsel("toprd", $PERIODS, $PRDMON[12]);

	if ($budname == "Financial Budget") {
		$bud_fin = "checked";
		$bud_spec = "";
	} else {
		$bud_fin = "";
		$bud_spec = "checked";
	}

	// Options Layout
	$Opts = "<center>
	<h3> New Monthly Budget</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=details>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr>
		<td>$errors</td>
	</tr>
	<tr>
		<th colspan=3>Details</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Budget</td>
		<td>
			<input type='radio' name='budname' value='Financial Budget' $bud_fin>Financial Budget<b> | </b>
			<input type='radio' name='budname' value='Special Budget' $bud_spec>Special Budget
		</td>
	</tr>
	<tr>
		<td colspan='3'><hr /></td>
	</tr>
	<tr>
		<th colspan=3>Create Budget</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Budget For</td>
		<td>
			<input type=radio name=budfor value=cost>Cost Centers &nbsp;&nbsp;
			<input type=radio name=budfor value=acc checked=yes>Accounts
		</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Budget Type</td>
		<td>$typesel</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Budget Period</td>
		<td>$fromprdsel to $toprdsel</td>
	</tr>";

	if (PYR_DB) {
		$Opts .= "<tr class='".bg_class()."'>
			<td>Use Previous Year Figures</td>
			<td><input type='checkbox' name='import' /></td>
		</tr>
		".TBL_BR."
		<tr>
			<th colspan='3'>'Use Previous Year Figures' Options</th>
		</tr>
		<tr>
			<td colspan='3' class='err'>This option is only used to create a budget for accounts,
				not Cost Centers.</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Increase Percentage</td>
			<td>
				<input type='text' name='incperc' size='3' value='0' /> %
				<span class='err'>Use negative value for decrease.</span>
			</td>
		</tr>";
	}

	$Opts .= "
	".TBL_BR."
	<tr>
		<td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td>
		<td align=right><input type=submit value='Continue &raquo'></td>
	</tr>
	</table>
	</form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $Opts;
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
	$v->isOk ($fromprd, "string", 1, 20, "Invalid Budget period.");
	$v->isOk ($toprd, "string", 1, 20, "Invalid Budget period.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return slctOpt($confirm);
	}

	global $BUDFOR, $TYPES, $PERIODS;
	$vbudfor = $BUDFOR[$budfor];
	$vbudtype = $TYPES[$budtype];
	$vfromprd = $PERIODS[$fromprd];
	$vtoprd = $PERIODS[$toprd];

	/* budget headings */
	if ($budfor == 'cost') {
		$head = "
		<tr>
			<th>Select Cost Centers</th>";
	} else {
		$head = "
		<tr>
			<th>Select Accounts</th>";
	}

	if($fromprd < $toprd){
		for($i = $fromprd; $i <= $toprd; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}elseif($fromprd > $toprd){
		for($i = $fromprd; $i <= 12; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
		for($i = 1; $i <= $toprd; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}else{
		$head .= "<th>$PERIODS[$toprd]</th>";
	}

	$head .= "<th>Annual Total</th>";
	$head .= "</tr>";

	/* Toggle Options */
	$list = "";
	$rowcnt = 0;
	$cellcnt = 0;
	# budget for
	$js_funcs_mon = "var tot_annual = new Array();";
	$js_funcs_tot = "";
	if($budfor == 'cost'){
		# cost centers
		db_connect();
		$sql = "SELECT * FROM costcenters WHERE div = '".USER_DIV."' ORDER BY centername ASC";
		$ccRslt = db_exec($sql);
		if(pg_numrows($ccRslt) < 1){
			return "<li>There are No cost centers in Cubit.";
		}

		$numacc = pg_num_rows($ccRslt);
		$cellcnt = $numacc * 3;

		while($cc = pg_fetch_array($ccRslt)){
			if ($rowcnt++ % 9 == 0) {
				$list .= $head;
			}
			$ccid = $cc["ccid"];

			if (isset($all) || isset($ccids[$ccid])) {
				$ch = "checked";
			} else {
				$ch = "";
			}

			$ci = $numacc + $rowcnt + 1; // extra one added so submit button is after annuals
			$list .= "
			<tr class='bg-odd'>
				<td><input tabindex='$ci' id='cb$ccid' type=checkbox name='ccids[$ccid]' value='$cc[ccid]' $ch>$cc[centercode] - $cc[centername]</td>";

			# Budget prd
			$tot_annual = 0;
			$js_totannuals = array();
			if($fromprd <= $toprd){
				for($i = $fromprd; $i <= $toprd; $i++){
					if (!isset($amts[$ccid][$i])) $amts[$ccid][$i] = 0;

					$tot_annual += $amts[$ccid][$i];
					$js_totannuals[] = "amts_${ccid}_$i";

					++$cellcnt;

					$list .= "<td nowrap>".CUR." <input tabindex='$cellcnt' type=text size=7 id='amts_${ccid}_$i' onChange='changedVal$ccid();' name=amts[$ccid][$i] value='".$amts[$ccid][$i]."'></td>";
				}
			}elseif($fromprd > $toprd){
				for($i = $fromprd; $i <= 12; $i++){
					if (!isset($amts[$ccid][$i])) $amts[$ccid][$i] = 0;

					$tot_annual += $amts[$ccid][$i];
					$js_totannuals[] = "amts_${ccid}_$i";

					++$cellcnt;

					$list .= "<td nowrap>".CUR." <input tabindex='$cellcnt' type=text size=7 id='amts_${ccid}_$i' onChange='changedVal$ccid();' name=amts[$ccid][$i] value='".$amts[$ccid][$i]."'></td>";
				}
				for($i = 1; $i <= $toprd; $i++){
					if (!isset($amts[$ccid][$i])) $amts[$ccid][$i] = 0;

					$tot_annual += $amts[$ccid][$i];
					$js_totannuals[] = "amts_${ccid}_$i";

					++$cellcnt;

					$list .= "<td nowrap>".CUR." <input tabindex='$cellcnt' type=text size=7 id='amts_${ccid}_$i' name=amts[$ccid][$i] value='".$amts[$ccid][$i]."'></td>";
				}
			//}else{
			//	if (!isset($amts[$cc["ccid"]][$i])) $amts[$cc["ccid"]][$i] = 0;
			//	$list .= "<td nowrap>".CUR." <input type=text size=7 onChange='changedVal$ccid();' name=amts[$cc[ccid]][$toprd] value='".$amts[$cc["ccid"]][$toprd]."'></td>";
			}

			$js_funcs_mon .= "
			function changedVal$ccid() {
				getObject('cb$ccid').checked = true;

				tot_annual[$ccid] = 0;";

			$months_cnt = count($js_totannuals);
			$js_funcs_tot .= "
			function changedTot$ccid(totobj) {
				mthval = parseFloat(totobj.value) / $months_cnt;

				sf = 0;";

			$last = 0;
			foreach ($js_totannuals as $fid) {
				++$last;

				$js_funcs_mon .= "
					obj = getObject('$fid');
					val = parseFloat(obj.value);
					obj.value = val.toFixed(2)
					tot_annual[$ccid] += val;";

				$js_funcs_tot .= "
					obj = getObject('$fid');";

				if ($last != $months_cnt) {
					$js_funcs_tot .= "
						obj.value = (Math.round(100*mthval)/100).toFixed(2);
						sf += Math.round(100*mthval)/100;";
				} else {
					$js_funcs_tot .= "
						obj.value = (parseFloat(totobj.value) - sf).toFixed(2);";
				}
			}

			$js_funcs_mon .= "
				//getObject('annual$ccid').innerHTML = '".CUR." ' + tot_annual[$ccid].toFixed(2);
				getObject('annual_$ccid').value = tot_annual[$ccid].toFixed(2);
			}

			tot_annual[$ccid] = $tot_annual;\n";

			$js_funcs_tot .= "
			}\n";

			$tot_annual = sprint($tot_annual);
			//$list .= "<td nowrap><div id='annual$ccid'>".CUR." $tot_annual</div></td>";
			$list .= "
			<td nowrap>".CUR."
				<input tabindex='$rowcnt' type=text size=7 onchange='changedTot$ccid(this);' id='annual_${accid}' name='annual[$ccid]' value='".$tot_annual."' />
			</td>";
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

		$tbval = new dbSelect("trial_bal_actual", PYR_DB, grp(
			m("cols", "acctype, debit, credit")
		));

		$numacc = pg_num_rows($accRslt);

		$cellcnt += $numacc * 3;

		while($acc = pg_fetch_array($accRslt)){
			if ($acc["accname"] == "Retained Income / Accumulated Loss") continue;

			if ($rowcnt++ % 9 == 0) {
				$list .= $head;
			}

			$accid = $acc["accid"];

			/* create default values */
			for ($i = 1; $i <= 12; ++$i) {
				if (!isset($amts[$accid][$i])) {
					if (isset($import)) {
						$tbval->setOpt(grp(
							m("where", "accid='$accid' AND month='$i'")
						));
						$tbval->run();

						$tbd = $tbval->fetch_array();

						switch ($tbd["acctype"]) {
							case "I":
								$bal = $tbval->d["credit"] - $tbval->d["debit"];
								break;
							case "E":
							case "B":
								$bal = $tbval->d["debit"] - $tbval->d["credit"];
								break;
						}

						$amts[$accid][$i] = sprint($bal + ($bal * $incperc / 100));
					} else {
						$amts[$accid][$i] = 0;
					}
				}
			}

			if(isset($all) || isset($accids[$accid]) || isset($import)) {
				$ch="checked";
			} else {
				$ch="";
			}

			$ci = $numacc + $rowcnt + 1; // extra one added so submit button is after annuals
			$list .= "
			<tr class='bg-odd'>
				<td><input tabindex='$ci' id='cb$accid' type='checkbox' name='accids[$accid]' value='$accid' $ch>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			# Budget prd
			$tot_annual = 0;
			$js_totannuals = array();
			if($fromprd <= $toprd){
				for($i = $fromprd; $i <= $toprd; $i++){
					$tot_annual += $amts[$accid][$i];
					$js_totannuals[] = "amts_${accid}_$i";

					++$cellcnt;

					$list .= "<td nowrap>".CUR." <input tabindex='$cellcnt' type=text size=7 onChange='changedVal$accid();' id='amts_${accid}_$i' name=amts[$accid][$i] value='".$amts[$accid][$i]."'></td>";
				}
			}elseif($fromprd > $toprd){
				for($i = $fromprd; $i <= 12; $i++){
					$tot_annual += $amts[$accid][$i];
					$js_totannuals[] = "amts_${accid}_$i";

					++$cellcnt;

					$list .= "<td nowrap>".CUR." <input tabindex='$cellcnt' type=text size=7 onChange='changedVal$accid();' id='amts_${accid}_$i' name=amts[$accid][$i] value='".$amts[$accid][$i]."' /></td>";
				}

				for($i = 1; $i <= $toprd; $i++){
					$tot_annual += $amts[$accid][$i];
					$js_totannuals[] = "amts_${accid}_$i";

					++$cellcnt;

					$list .= "<td nowrap>".CUR." <input tabindex='$cellcnt' type=text size=7 onChange='changedVal$accid();' id='amts_${accid}_$i' name=amts[$accid][$i] value='".$amts[$accid][$i]."' /></td>";
				}
			}

			/* JAVA SCRIPT: BEGIN */
			$js_funcs_mon .= "
			function changedVal$accid() {
				getObject('cb$accid').checked = true;

				tot_annual[$accid] = 0;";

			$months_cnt = count($js_totannuals);
			$js_funcs_tot .= "
			function changedTot$accid(totobj) {
				getObject('cb$accid').checked = true;
				mthval = parseFloat(totobj.value) / $months_cnt;

				sf = 0;";

			$last = 0;
			foreach ($js_totannuals as $fid) {
				++$last;

				$js_funcs_mon .= "
					obj = getObject('$fid');
					val = parseFloat(obj.value);
					obj.value = val.toFixed(2);
					tot_annual[$accid] += val;";

				$js_funcs_tot .= "
					obj = getObject('$fid');";

				if ($last != $months_cnt) {
					$js_funcs_tot .= "
						obj.value = (Math.round(100*mthval)/100).toFixed(2);
						sf += Math.round(100*mthval)/100;";
				} else {
					$js_funcs_tot .= "
						obj.value = (parseFloat(totobj.value) - sf).toFixed(2);";
				}
			}

			$js_funcs_mon .= "
				getObject('annual_$accid').value = tot_annual[$accid].toFixed(2);
			}

			tot_annual[$accid] = $tot_annual;\n";

			$js_funcs_tot .= "
			}\n";

			/* JAVA SCRIPT: END */

			$tot_annual = sprint($tot_annual);
			//$list .= "<td nowrap><div id='annual$accid'>".CUR." $tot_annual</div></td>";
			$list .= "
			<td nowrap='t'>".CUR."
				<input tabindex='$rowcnt' type=text size=7 onchange='changedTot$accid(this);' id='annual_${accid}' name='annual[$accid]' value='".$tot_annual."' />
			</td>
			<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";
		}
	}

	/* End Toggle Options */

	$OUT = "
	<script>
	$js_funcs_mon
	$js_funcs_tot
	</script>
	<div>
	<center><h3>New Monthly Budget</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=budname value='$budname'>
	<input type=hidden name=budfor value='$budfor'>
	<input type=hidden name=budtype value='$budtype'>
	<input type=hidden name=fromprd value='$fromprd'>
	<input type=hidden name=toprd value='$toprd'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
		<tr>
			<th colspan=2>Details</th>
		</tr>
		<tr class='bg-odd'>
			<td>Budget Name</td>
			<td>$budname</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr>
			<th colspan=2>Options</th>
		</tr>
		<tr class='bg-odd'>
			<td>Budget For</td>
			<td>$vbudfor</td>
		</tr>
		<tr class='bg-even'>
			<td>Budget Type</td>
			<td>$vbudtype</td>
		</tr>
		<tr class='bg-odd'>
			<td>Budget Period</td>
			<td>$vfromprd to $vtoprd</td>
		</tr>
		<tr>
			<td colspan=2>$errata</td>
		</tr>
	</table>
	</div>
	<div>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
		$list
	</table>
	</div>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr>
		<td align='right'><input tabindex='".($rowcnt+1)."' type=submit value='Continue &raquo'></td>
	</tr>
	</table>
	</form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $OUT;
}

# Enter Details of Transaction
function confirm($_POST)
{
	# Get vars
	extract($_POST);

	if(isset($all)) {
		return details($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($budname, "string", 1, 255, "Invalid Budget Name.");
	$v->isOk ($budfor, "string", 1, 20, "Invalid Budget for option.");
	$v->isOk ($budtype, "string", 1, 20, "Invalid Budget type.");
	$v->isOk ($fromprd, "string", 1, 20, "Invalid Budget period.");
	$v->isOk ($toprd, "string", 1, 20, "Invalid Budget period.");

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
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return details($_POST, $confirm);
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

	global $BUDFOR, $TYPES, $PERIODS;
	$vbudfor = $BUDFOR[$budfor];
	$vbudtype = $TYPES[$budtype];
	$vfromprd = $PERIODS[$fromprd];
	$vtoprd = $PERIODS[$toprd];

	/* Toggle Options */
	$list = "";
	# budget for
	if($budfor == 'cost'){
		$head = "<tr><th>Cost Centers</th>";
		foreach($ccids as $ckey => $ccid){
			$ccRs = get("cubit", "*", "costcenters", "ccid", $ccid);
			$cc  = pg_fetch_array($ccRs);
			$list .= "<tr class='bg-odd'><td><input type=hidden name='ccids[$cc[ccid]]' value='$cc[ccid]'>$cc[centercode] - $cc[centername]</td>";

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
			$list .= "<tr class='bg-odd'><td><input type=hidden name='accids[$acc[accid]]' value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			foreach($amts[$accid] as $sprd => $amtr){
				$amtr = sprint($amtr);
				$list .= "<td align=right><input type=hidden name=amts[$acc[accid]][$sprd] value='$amtr'>".CUR." $amtr</td>";
			}
			$list .= "</tr>";
		}
	}

	# Budget headings
	if($fromprd < $toprd){
		for($i = $fromprd; $i <= $toprd; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}elseif($fromprd > $toprd){
		for($i = $fromprd; $i <= 12; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
		for($i = 1; $i <= $toprd; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}else{
		$head .= "<th>$PERIODS[$toprd]</th>";
	}
	$head .= "</tr>";

	// $totamt = sprint(array_sum($amts));
	// $list .= "<tr class='bg-even'><td><b>Total Budget Amount</b></td><td align=right><b>".CUR." $totamt</b></td></tr>";

	/* End Toggle Options */

	// Create hidden values
	$hidden = "";
	foreach ($_POST as $name=>$value) {
		$hidden .= "<input type='hidden' name='$name' value='$value'>";
	}

	$confirm = "<center>
	<h3> Confirm New Monthly Budget </h3>
	<form action='".SELF."' method=post name=form>
	$hidden
	<input type=hidden name=key value=write>
	<input type=hidden name=budname value='$budname'>
	<input type=hidden name=budfor value='$budfor'>
	<input type=hidden name=budtype value='$budtype'>
	<input type=hidden name=fromprd value='$fromprd'>
	<input type=hidden name=toprd value='$toprd'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
		<tr>
			<th colspan=2>Details</th>
		</tr>
		<tr class='bg-odd'>
			<td>Budget Name</td>
			<td>$budname</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
		<tr>
			<th colspan=2>Options</th>
		</tr>
		<tr class='bg-odd'>
			<td>Budget For</td>
			<td>$vbudfor</td>
		</tr>
		<tr class='bg-even'>
			<td>Budget Type</td>
			<td>$vbudtype</td>
		</tr>
		<tr class='bg-odd'>
			<td>Budget Period</td>
			<td>$vfromprd to $vtoprd</td>
		</tr>
		<tr>
			<td><br></td>
		</tr>
	</table>

	$mismatches
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	$head
	$list
	</table>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
		<tr>
			<td><br></td>
		</tr>
		<tr>
			<td><input type='submit' name='key' value='&laquo Correction'></td>
			<td align=right><input type=submit value='Continue &raquo'></td>
		</tr>
	</table>
	</form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='bg-odd'>
			<td align=center><a href='budget-view.php'>View Budgets</td>
		</tr>
		<tr class='bg-odd'>
			<td align=center><a href='../main.php'>Main Menu</td>
		</tr>
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
	$v->isOk ($fromprd, "string", 1, 20, "Invalid Budget period.");
	$v->isOk ($toprd, "string", 1, 20, "Invalid Budget period.");

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
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return details($_POST, $confirm);
	}

	global $BUDFOR, $TYPES, $PERIODS;
	$vbudfor = $BUDFOR[$budfor];
	$vbudtype = $TYPES[$budtype];
	$vfromprd = $PERIODS[$fromprd];
	$vtoprd = $PERIODS[$toprd];

	db_conn("cubit");
	$sql = "SELECT * FROM budgets WHERE budname='$budname' AND budfor='$budfor' AND budtype='$budtype'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve budgets from Cubit.");
	$bud_data = pg_fetch_array($rslt);

	if (!pg_num_rows($rslt)) {
		db_connect();
		$sql = "INSERT INTO budgets(budname, budtype, budfor, fromprd, toprd, edate, div) VALUES('$budname', '$budtype', '$budfor', '$fromprd', '$toprd', now(), '".USER_DIV."')";
		$inRs = db_exec($sql);

		$budid = pglib_lastid("budgets", "budid");
	} else {
		$budid = $bud_data["budid"];
	}

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
		<tr>
			<th colspan=2>New Monthly Budget created</th>
		</tr>
		<tr>
			<td class='bg-odd' colspan=2>New Monthly Budget <b>$budname</b> has been created.</td>
		</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='bg-odd'>
			<td align=center><a href='budget-view.php'>View Budgets</td>
		</tr>
		<tr class='bg-odd'>
			<td align=center><a href='../main.php'>Main Menu</td>
		</tr>
	</table>";

	return $write;
}
?>

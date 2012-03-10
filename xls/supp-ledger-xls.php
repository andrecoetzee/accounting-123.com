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
require("../settings.php");
require("../core-settings.php");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	$OUTPUT = slctacc();
}

# Get templete
require("../template.php");



function slctacc()
{

	# from period
	$prds = "<select name='prd'>";
	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year";
	}
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$prds .= "</select>";

	db_connect();

	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supid ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no suppliers in Cubit.";
	}
	$supts = "<select name='supids[]' multiple size='10'>";
	while($sup = pg_fetch_array($supRslt)){
		$supts .= "<option value='$sup[supid]'>$sup[supname]</option>";
	}
	$supts .= "</select>";

	$slctacc = "
		<p>
		<h3>Creditors Ledger</h3>
		<h4>Select Options</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>suppliers</td>
				<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | <input type='radio' name='accnt' value='all'>All Accounts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Select Customer(s)</td>
				<td>$supts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select period</td>
				<td>$prds</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Order By</td>
				<td>Transaction Date<input type='radio' name='t' checked value='t'>System Date<input type='radio' name='t' value='s'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slctacc;

}



# View all transaction for the ledger
function viewtran($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($supids)){
				foreach($supids as $key => $supid){
					$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
				}
			}else{
				return "<li class='err'>Please select at least one Creditor.</li>".slctacc();
			}
		}
	}else{
		$v->isOk ("###", "num", 0, 0, "ERROR : Invalid Accounts Selection.");
	}

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



	# Get the ids
	if($accnt == 'all'){
		$supids = array();
		db_connect();
		$sql = "SELECT supid FROM suppliers WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$supids[] = $ac['supid'];
			}
		}else{
			return "<li calss='err'> There are no suppliers yet in Cubit.</li>";
		}
	}

	db_conn('core');

	$Sl = "SELECT yrname FROM year WHERE yrdb='$year'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$yrname = pg_fetch_result($Ri, 0);
	$auditdb = "${yrname}_audit";

	$prds = array();
	if ($tprd < $fprd) {
		for ($i = $fprd; $i <= 12; ++$i) {
			$prds[] = $i;
		}
		
		for ($i = 1; $i <= $tprd; ++$i) {
			$prds[] = $i;
		}
	} else {
		for ($i = $fprd; $i <= $tprd; ++$i) {
			$prds[] = $i;
		}
	}

	# Period name
	$prdname = prdname($prd);

	$trans = "";
	foreach($supids as $key => $supid){
		$supRs = get("cubit", "supname, supno, balance", "suppliers", "supid", $supid);
		$sup = pg_fetch_array($supRs);

		$trans .= "
			<tr>
				<td colspan='8' align='center'><h3>$sup[supname]</h3></td>
			</tr>";

		foreach ($prds as $prd) {
			$prdname = prdname($prd);
			$trans .= "
				<tr>
					<th colspan='8'>$prdname</th>
				</tr>
				<tr>
					<th>$sp</th>
					<th>Date</th>
					<th>Reference</th>
					<th>Description</th>
					<th>Debit</th>
					<th>Credit</th>
					<th>Balance</th>
					<th>Contra Acc</th>
				</tr>";

// 			if($id['min'] <> 0){
// 				$balRs = get($prd, "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", "suppledger", "id", $id['min']);
// 				$bal = pg_fetch_array($balRs);
// 				$bal['cbalance'] += 0;
// 				$bal['dbalance'] += 0;
// 			}else{
// 				$balRs = get("cubit", "balance", "suppliers", "supid", $supid);
// 				$bal = pg_fetch_array($balRs);
// 				$bal['balance']+=0;
// 	
// 				if($bal['balance']<0) {
// 					$bal['dbalance'] = $bal['balance'];
// 					$bal['cbalance'] = 0;
// 				} else {
// 					$bal['cbalance'] = $bal['balance'];
// 					$bal['dbalance'] = 0;
// 				}
// 				//$bal['dbalance'] += $amount;
// 			}

// 			$idRs = get($prd, "min(id)", "suppledger", "supid", $supid);
// 			$id = pg_fetch_array($idRs);

			$idRs = get($auditdb, "min(id)", "${prdname}_suppledger", "supid", $supid);
			$id = pg_fetch_array($idRs);

			if($id['min'] <> 0){
				$balRs = get($auditdb, "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", "${prdname}_suppledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$bal['cbalance'] += 0;
				$bal['dbalance'] += 0;
			} else {
				$sql = array();
				for ($i = $MONPRD[$prd] - 1; $i >= 1; --$i) {
					$pprdname = getMonthName($PRDMON[$i]);
	
					$sql[] = "SELECT id,cbalance,dbalance FROM $auditdb.${pprdname}_suppledger WHERE supid='$supid'";
				}

				if (count($sql) > 0) {
					$sql = "SELECT * FROM (".implode(" UNION ", $sql).") AS sl ORDER BY id DESC LIMIT 1";
					$balRs = db_exec($sql);
					$bal = pg_fetch_array($balRs);
				}
			}

			if(!isset($bal['dbalance']))
				$bal['dbalance'] = "";

			if(!isset($bal['cbalance']))
				$bal['cbalance'] = "";

			# Total balance changes
			if($bal['dbalance'] > $bal['cbalance']){
				$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
				$bal['cbalance'] = 0;
			}elseif($bal['cbalance'] > $bal['dbalance']){
				$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
				$bal['dbalance'] = 0;
			}else{
				$bal['cbalance'] = 0;
				$bal['dbalance'] = 0;
			}

			$bal['credit'] = sprint($bal['cbalance']);
			$bal['debit'] = sprint($bal['dbalance']);

			$balance = sprint($bal['cbalance']-$bal['dbalance']);

			$trans .= "
				<tr>
					<td colspan='8'><b>$sup[supno] - $sup[supname] </b></td>
				</tr>";
			$trans .= "
				<tr>
					<td colspan=2><br></td>
					<td>Br/Forwd</td>
					<td>Brought Forward</td>
					<td align='right'>$bal[debit]</td>
					<td align='right'>$bal[credit]</td>
					<td align='right'>$balance</td>
					<td> </td>
				</tr>";

			# --> Transaction reading comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;

// 			if($t == "s") {
// 				$tranRs = get($prd, "*", "suppledger", "supid", $supid,"ORDER BY id");
// 			} else  {
// 				$tranRs = get($prd, "*", "suppledger", "supid", $supid,"ORDER BY edate,id");
// 			}

			if($t == "s") {
				$tranRs = get($auditdb, "*", "${prdname}_suppledger", "supid", $supid,"ORDER BY id");
			} else  {
				$tranRs = get($auditdb, "*", "${prdname}_suppledger", "supid", $supid,"ORDER BY edate,id");
			}

			while($tran = pg_fetch_array($tranRs)){
				$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
				$cacc = pg_fetch_array($caccRs);

				$tran['debit'] = sprint($tran['debit']);
				$tran['credit'] = sprint($tran['credit']);

				$dbal['debit'] += $tran['debit'];
				$dbal['credit'] += $tran['credit'];

				if($t == "s") {
					$cbalance = sprint($tran['cbalance'] - $tran['dbalance']);
				} else {
					$cbalance = sprint(($dbal['credit']+$bal['credit']) - ($dbal['debit']+$bal['debit']));
				}

				if($t == "s") {
					$tran['edate']=$tran['sdate'];
				}

				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

				$trans .= "
					<tr>
						<td><br></td>
						<td>$tran[edate]</td>
						<td>$tran[eref]</td>
						<td>$tran[descript]</td>
						<td align='right'>$tran[debit]</td>
						<td align='right'>$tran[credit]</td>
						<td align='right'>$cbalance</td>
						<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
					</tr>";
			}

			# Total balance changes
			if($dbal['debit'] > $dbal['credit']){
				$dbal['debit'] = sprint($dbal['debit'] - $dbal['credit']);
				$dbal['credit'] = "";
			}elseif($dbal['credit'] > $dbal['debit']){
				$dbal['credit'] = sprint($dbal['credit'] - $dbal['debit']);
				$dbal['debit'] = "";
			}else{
				$dbal['credit'] = "";
				$dbal['debit'] = "0.00";
			}

			$trans .= "
				<tr>
					<td colspan='2'><br></td>
					<td>A/C Total</td>
					<td>Total for period $prdname to Date :</td>
					<td align='right'>$dbal[debit]</td>
					<td align='right'>$dbal[credit]</td>
					<td align='right'></td>
					<td> </td>
				</tr>";
			$trans .= "<tr><td colspan='8'><br></td></tr>";
		}
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
		<center>
		<h3>Creditors Ledger</h3>
		<table ".TMPL_tblDflts." width='75%'>
			$trans
		</table>";
	include("temp.xls.php");
	Stream("Ledger", $view);

}


?>
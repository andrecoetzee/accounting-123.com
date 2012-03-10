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
			if(isset($HTTP_POST_VARS["export"])){
				$OUTPUT = export_data($HTTP_POST_VARS);
			}else {
				$OUTPUT = viewtran($HTTP_POST_VARS);
				$OUTPUT .= "
								<p>
								<table ".TMPL_tblDflts." width='25%'>
									<tr><td><br></td></tr>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr class='datacell'>
										<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
									</tr>
									<tr class='datacell'>
										<td align='center'><a href='index-reports.php'>Financials</a></td>
									</tr>
									<tr class='datacell'>
										<td align='center'><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
									</tr>
									<tr class='datacell'>
										<td align='center'><a href='../main.php'>Main Menu</td>
									</tr>
								</table>";
			}
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

	global $PRDMON;

	$fprds = finMonList("fprd", $PRDMON[1]);
	$tprds = finMonList("tprd", PRD_DB);

	db_connect();
	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supid ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no suppliers in Cubit.</li>";
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
							<td valign='top'></td>
							<td>
								<input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | 
								<input type='radio' name='accnt' value='all'>All Accounts
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Select Supplier(s)</td>
							<td>$supts</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Select period</td>
							<td>$fprds to $tprds</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Order By</td>
							<td>
								Transaction Date <input type='radio' name='t' checked value='t'>
								System Date <input type='radio' name='t' value='s'>
							</td>
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
						<tr class='datacell'>
							<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='index-reports.php'>Financials</a></td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='../main.php'>Main Menu</td>
						</tr>
					</table>";
	return $slctacc;

}


# View all transaction for the ledger
function viewtran($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fprd, "string", 1, 14, "Invalid from period number.");
	$v->isOk ($tprd, "string", 1, 14, "Invalid to period number.");

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

	$hide="";
	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$trans = "";
	foreach($supids as $key => $supid){
		$supRs = get("cubit", "supname, supno, balance", "suppliers", "supid", $supid);
		$sup = pg_fetch_array($supRs);

		$hide .= "<input type='hidden' name='supids[]' value='$supid'>";

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
			
			$idRs = get($prd, "min(id)", "suppledger", "supid", $supid);
			$id = pg_fetch_array($idRs);
	
			if($id['min'] <> 0){
				$balRs = get($prd, "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", "suppledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$bal['cbalance'] += 0;
				$bal['dbalance'] += 0;
			}else{
				$balRs = get("cubit", "balance", "suppliers", "supid", $supid);
				$bal = pg_fetch_array($balRs);
				$bal['balance']+=0;
	
				if($bal['balance']<0) {
					$bal['dbalance'] = $bal['balance'];
					$bal['cbalance'] = 0;
				} else {
					$bal['cbalance'] = $bal['balance'];
					$bal['dbalance'] = 0;
				}
				//$bal['dbalance'] += $amount;
			}
	
			/* show the balance as a debit/credit, and not the individual balances of both */
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
	
			$balance=sprint($bal['cbalance']-$bal['dbalance']);
	
			$trans .= "
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='8'><b>$sup[supno] - $sup[supname] </b></td>
							</tr>";
	
			$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, getYearOfFinMon($prd)));
	
			$trans .= "
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='2' align='right'>$bbf_date</td>
								<td>Br/Forwd</td>
								<td>Brought Forward</td>
								<td align='right'>$bal[debit]</td>
								<td align='right'>$bal[credit]</td>
								<td align='right'>$balance</td>
								<td>&nbsp;</td>
							</tr>";
	
			# --> Transaction reading comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;
	
			if($t=="s") {
				$tranRs = get($prd, "*", "suppledger", "supid", $supid,"ORDER BY id");
			} else  {
				$tranRs = get($prd, "*", "suppledger", "supid", $supid,"ORDER BY edate,id");
			}
			while($tran = pg_fetch_array($tranRs)){
				$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
				$cacc = pg_fetch_array($caccRs);
	
				$tran['debit']=sprint($tran['debit']);
				$tran['credit']=sprint($tran['credit']);
	
				$dbal['debit'] += $tran['debit'];
				$dbal['credit'] += $tran['credit'];
	
				if($t=="s") {
	
					$cbalance = sprint($tran['cbalance'] - $tran['dbalance']);
	
				} else {
	
					$cbalance = sprint(($dbal['credit']+$bal['credit']) - ($dbal['debit']+$bal['debit']));
	
				}
	
				if($t=="s") {
					$tran['edate']=$tran['sdate'];
				}
	
				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];
	
				$trans .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td>$tran[edate]</td><td>$tran[eref]</td><td>$tran[descript]</td><td align=right>$tran[debit]</td><td align=right>$tran[credit]</td><td align=right>$cbalance</td><td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td></tr>";
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
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='2'><br></td>
								<td>A/C Total</td>
								<td>Total for period $prdname to Date :</td>
								<td align='right'>$dbal[debit]</td>
								<td align='right'>$dbal[credit]</td>
								<td align='right'></td>
								<td> </td>
							</tr>
							<tr>
								<td colspan='8'><br></td>
							</tr>";
		}
	}

	$view = "
				<center>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='viewtran'>
					<input type='hidden' name='accnt' value='$accnt'>
					<input type='hidden' name='prd' value='$prd'>
					<input type='hidden' name='t' value='$t'>
					<input type='hidden' name='fprd' value='$fprd'>
					<input type='hidden' name='tprd' value='$tprd'>
					$hide
					<h3>Creditors Ledger</h3>
				<table ".TMPL_tblDflts." width='75%'>
					$trans
					<tr>
						<td colspan='8' align='center'><input type='submit' name='export' value='Export to Spreadsheet'></td>
					</tr>
				</table>
				</from>";
	return $view;

}


function export_data ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);
	require_lib ("xls");

	$data = clean_html(viewtran($HTTP_POST_VARS));

	StreamXLS ("suppledger","$data");

}


?>
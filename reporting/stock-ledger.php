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

if (isset($HTTP_POST_VARS["key"]) AND isset($HTTP_POST_VARS["continue"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "excel":
			$OUTPUT = excel();
			break;
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctacc($HTTP_POST_VARS);
	}
} else {
	$OUTPUT = slctacc($HTTP_POST_VARS);
}

# Get templete
require("../template.php");




function slctacc($HTTP_POST_VARS)
{
	
	extract ($HTTP_POST_VARS);

	# from period
	global $PRDMON;
	$fprds = finMonList("fprd", $PRDMON[1]);
	$tprds = finMonList("tprd", PRD_DB);

	if (isset($sortby) && $sortby == "desc_store") {
		$orderby = "ORDER BY stkcod ASC";
	} else {
		$orderby = "ORDER BY whid ASC";
	}

	db_connect();
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' $orderby";
	$stkRslt = db_exec($sql) or errDie("Could not retrieve Stock Information from the Database.",SELF);

	if(pg_numrows($stkRslt) < 1){
		return "<li class='err'> There are no Stock Items in Cubit.</li>";
	}
	$stks = "<select name='stkids[]' multiple size='10'>";
	while($stk = pg_fetch_array($stkRslt)){

		db_conn("exten");
		#get this warehouse/store name

		$get_store = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."' LIMIT 1";
		$run_store = db_exec($get_store) or errDie("Unable to get get store information");
		if(pg_numrows($run_store) < 1){
			$store = "";
		}else {
			$arr = pg_fetch_array($run_store);
			$store = "($arr[whname])";
		}

		if(!isset($sortby))
			$sortby = "store_desc";

		$sel1 = "";
		$sel2 = "";
		if($sortby == "desc_store"){
			$sort = "$stk[stkcod] $stk[stkdes] $store";
			$sel2 = "checked='yes'";
		}else {
			$sort = "$store $stk[stkcod] $stk[stkdes]";
			$sel1 = "checked='yes'";
		}
		$stks .= "<option value='$stk[stkid]'>$sort</option>";
	}
	$stks .= "</select>";

	$ssel1 = "";
	$ssel2 = "";
	if(isset($accnt) AND ($accnt == "all")){
		$ssel2 = "checked='yes'";
	}else {
		$ssel1 = "checked='yes'";
	}

	$slctacc = "
					<p>
					<h3>Inventory Ledger</h3>
					<h4>Select Options</h4>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form1'>
						<input type='hidden' name='key' value='viewtran'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Stock Items</td>
							<td>
								<input type='radio' name='accnt' value='slct' $ssel1>Selected Items | 
								<input type='radio' name='accnt' value='all' $ssel2>All Items
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Sort Method</td>
							<td>
								<input type='radio' name='sortby' value='store_desc' $sel1 onChange='javascript:document.form1.submit()'>Store - Description |
								<input type='radio' name='sortby' value='desc_store' $sel2 onChange='javascript:document.form1.submit()'>Description - Store
							</td> 
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Select Stock Item(s)</td>
							<td>$stks</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Select period</td>
							<td>$fprds to $tprds</td>
						</tr>
						".TBL_BR."
						<tr>
							<td align='center'></td>
							<td align='right'><input type='submit' name='continue' value='Continue &raquo;'></td>
						</tr>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='index-reports.php'>Financials</a></td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='index-reports-other.php'>Other Reports</a></td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='../main.php'>Main Menu</td>
						</tr>
					</table>";
	return $slctacc;

}



function viewtran($HTTP_POST_VARS, $pure = false)
{

	extract($HTTP_POST_VARS);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fprd, "string", 1, 14, "Invalid from period number.");
	$v->isOk ($tprd, "string", 1, 14, "Invalid to period number.");
	$v->isOk ($accnt, "string", 1, 5, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if(isset($stkids)){
			foreach($stkids as $key => $stkid){
				$v->isOk ($stkid, "num", 1, 20, "Invalid Stock code.");
			}
		}else{
			return "<li class='err'>ERROR : Please select at least one Stock Item.</li>".slctacc($HTTP_POST_VARS);
		}
	}

	if ($v->isError ()) {
		return $v->genErrors();
	}

	if ($accnt == 'all'){
		$stkids = array();
		db_connect();
		$sql = "SELECT stkid FROM stock WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if (pg_num_rows($rs) > 0) {
			while($ac = pg_fetch_array($rs)){
				$stkids[] = $ac['stkid'];
			}
		} else {
			return "<li calss='err'> There are no Stock Items yet in Cubit.</li>";
		}
	}

	# Period name
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
	
	$hide = "";
	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$trans = "";
	foreach($stkids as $key => $stkid){
		$stkRs = get("cubit", "*", "stock", "stkid", $stkid);
		$stk = pg_fetch_array($stkRs);
		
		$trans .= "
		<tr>
			<td colspan='8' align='center'><h3>$stk[stkcod] - $stk[stkdes]</h3></td>
		</tr>";
		
		$hide .= "<input type='hidden' name='stkids[]' value='$stkid'>";

		foreach ($prds as $prd) {
			$prdname = getMonthName($prd);
			$trans .= "
							<tr>
								<th colspan='8'>$prdname</th>
							</tr>
							<tr>
								<th>DATE</th>
								<th>DETAILS</th>
								<th>QTY</th>
								<th>COST AMOUNT</th>
								<th>BALANCE</th>
							</tr>";
		
			$idRs = get($prd, "max(id), min(id)", "stkledger", "yrdb='".YR_DB."' AND stkid", $stkid);
			$id = pg_fetch_array($idRs);
	
			if($id['min'] <> 0){
				$balRs = get($prd, "qty, (bqty - qty) as bqty, trantype, (balance - csamt) as balance", "stkledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$cbalRs = get($prd, "balance", "stkledger", "id", $id['max']);
				$cbal = pg_fetch_array($cbalRs);
	
				/*
				if($bal['trantype'] == 'dt'){
					$bal['bqty'] =  ($bal['bqty'] + $bal['qty']);
				}else{
					$bal['bqty'] =  ($bal['bqty'] - $bal['qty']);
				}
				*/
	
			}else{
				$balRs = get("cubit", "csamt as balance, units as bqty", "stock", "stkid", $stkid);
				$bal = pg_fetch_array($balRs);
				$cbal['balance'] = 0;
				$cbal['bqty'] = 0;
			}
	
			$balance = sprint($bal['balance']);
	
			$trans .= "
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='5'><b>($stk[stkcod]) $stk[stkdes]</b></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>&nbsp;</td>
								<td>Balance Brought Forward</td>
								<td align='right'>".sprint3($bal['bqty'])."</td>
								<td>&nbsp;</td>
								<td align='right'>".sprint($balance)."</td>
							</tr>";
	
			$dbal['balance'] = 0;
			$dbal['bqty'] = 0;
			$qtytotal = 0;

			$tranRs = nget($prd, "*", "stkledger", "yrdb='".YR_DB."' AND stkid", $stkid." ORDER BY edate,id ASC");
			while($tran = pg_fetch_array($tranRs)){
				
	   			$dbal['balance'] += $tran['csamt'];
				$dbal['bqty'] = $tran['bqty'];
				$qtytotal += $tran['qty'];
	
				# sprinting
				$tran['csamt'] = sprint($tran['csamt']);
				$tran['balance'] = sprint($tran['balance']);
	
				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];
	
				$balance += $tran["csamt"];
	
				$trans .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$tran[edate]</td>
									<td>$tran[details]</td>
									<td align='right'>".sprint3($tran['qty'])."</td>
									<td align='right'>$tran[csamt]</td>
									<td align='right'>".sprint($balance)."</td>
								</tr>";
			}
			$dbal['balance'] = sprint($dbal['balance']);
	//$qtytotal was $dbal[bqty]
			$trans .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>&nbsp;</td>
								<td>Total for period $prdname to Date :</td>
								<td align='right'>".sprint3($qtytotal)."</td>
								<td align='right'>$dbal[balance]</td>
								<td align='right'>$dbal[balance]</td>
							</tr>
							".TBL_BR;
		}
	}

	$view = "";

	if (!$pure) {
		$view .= "
					<center>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='excel'>
						<input type='hidden' name='accnt' value='$accnt'>
						<input type='hidden' name='fprd' value='$fprd'>
						<input type='hidden' name='tprd' value='$tprd'>
						<input type='hidden' name='continue' value='yes'>
						$hide
						<h3>Inventory Ledger</h3>";
	}
	
	$view .= "
	<table ".TMPL_tblDflts." width=75%>
	$trans";
	
	if (!$pure) {
		$view .= "
						<tr>
							<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
						</tr>
					</form>";
	}
	
	$view .= "
	</table>";
	
	if (!$pure) {
		$view .= "
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-other.php'>Other Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	}
	return $view;

}


function excel()
{

	$OUTPUT = clean_html(viewtran($_POST, true));
	require_lib("xls");
	StreamXLS("Inventory Ledger", $OUTPUT);

}


?>

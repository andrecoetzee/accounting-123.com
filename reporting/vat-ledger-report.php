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

require("../settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "report":
			$OUTPUT = report($_POST);
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
} else {
	$OUTPUT = seluse();
}

require("../template.php");

function seluse()
{

	$types = "
		<select name='type'>
			<option value='79'>INPUT</option>
			<option value='80'>OUTPUT</option>
		</select>";

	db_conn("cubit");

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ry = db_exec($Sl) or errDie("Unable to vat codes.");

	$Out = "
		<h3>VAT Report</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='report'>
			<tr>
				<th colspan='2'>Report Criteria</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type</td>
				<td>$types</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>From</td>
				<td>".mkDateSelect("from")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To</td>
				<td>".mkDateSelect("to")."</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='View Report &raquo;'></td>
			</tr>
		</form>
		</table><p>
		<table ".TMPL_tblDflts.">
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='index-reports.php'>Financials</a></td></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='index-reports-other.php'>Other Reports</a></td></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	return $Out;

}



function report($_POST)
{

	extract($_POST);

	$date = $from_year."-".$from_month."-".$from_day;
	$tdate = $to_year."-".$to_month."-".$to_day;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(!checkdate($from_month, $from_day, $from_year)){
                $v->isOk ($date, "num", 1, 1, "Invalid order date.");
        }

	if(!checkdate($to_month, $to_day, $to_year)){
                $v->isOk ($tdate, "num", 1, 1, "Invalid order date.");
        }

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$fprd = $from_month+0;
	$tprd = $to_month+0;

	$prds = array ();
	if ($to_month < $fprd){
		for ($x=$fprd;$x<=12;$x++){
			$prds[] = $x;
		}
		for ($y=1;$y<=$tprd;$y++){
			$prds[] = $y;
		}
	}else {
		for ($x=$fprd;$x<=$tprd;$x++){
			$prds[] = $x;
		}
	}

	db_conn('cubit');

	# --> transactio reding comes here <--- #
	$dbal['debit'] = 0;
	$dbal['credit'] = 0;

	$prd = $from_month += 0;

	foreach ($prds AS $prd){

		$total = 0;
		$trans_total = 0;

		$out .= "
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='6' align='center'><h4>".date ("F", mktime (0,0,0,$prd,1,$from_year))."</h4></td>
			</tr>";

		$out .= "
			<tr>
				<th>G/L Account</th>
				<th>Date</th>
				<th>Ref</th>
				<th>Description</th>
				<th>Transaction Amount</th>
				<th>VAT Amount</th>
			</tr>";

		$tranRs = get($prd, "*", "ledger", "acc", $type);
		while($tran = pg_fetch_array($tranRs)) {

			if (($tran['edate'] < $date OR $tran['edate'] > $tdate) AND $tran['descript'] != "Balance"){
				continue;
			}

			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			# Current(Running) balance
			if($tran['dbalance'] > $tran['cbalance']){
				$tran['dbalance'] = sprint($tran['dbalance'] - $tran['cbalance']);
				$tran['cbalance'] = "";
				$cbalance = $tran['dbalance'];
				$cfl = "DT";
			}elseif($tran['cbalance'] > $tran['dbalance']){
				$tran['cbalance'] = sprint($tran['cbalance'] - $tran['dbalance']);
				$tran['dbalance'] = "";
				$cbalance = $tran['cbalance'];
				$cfl = "CT";
			}else{
				$tran['cbalance'] = "";
				$tran['dbalance'] = "";
				$cbalance  = "0.00";
				$cfl = "";
			}

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			if ($tran['credit'] > 0){
				if ($type == "80"){
					$multiplier = 1;
				}else {
					$multiplier = -1;
				}
				$amount = sprint ($tran['credit']*$multiplier);
			}else {
				if ($type == "80"){
					$multiplier = -1;
				}else {
					$multiplier = 1;
				}
				$amount = sprint ($tran['debit']*$multiplier);
			}

			$out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='#' onClick=\"window.open('drill-view-trans.php?accid=$tran[acc]&month_to=$prd','window','width=900, height=380, scrollbars=yes');\">$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
					<td>$tran[edate]</td>
					<td>$tran[eref]</td>
					<td>$tran[descript]</td>
					<td align='right'>".CUR." ".sprint (($amount/14)*100)."</td>
					<td align='right'>".CUR." $amount</td>
				</tr>";
			$total += $amount;
			$trans_total += ($amount / 14) * 100;
		}

		$out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4' align='right'>Total:</td>
				<td align='right'>".CUR." ".sprint ($trans_total)."</td>
				<td align='right'>".CUR." ".sprint ($total)."</td>
			</tr>";

	}


	$Report = "
		<h3>VAT Report: $date TO $tdate</h3>
		<table ".TMPL_tblDflts.">
			$out
			<tr><td><br></td></tr>
		</table>
		<p>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='vat-ledger-report.php'>VAT Ledger Report</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='index-reports-other.php'>Other Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $Report;

}


?>

<?

require ("../settings.php");

$OUTPUT = show_menu();

require ("../template.php");




function show_menu ()
{

	extract ($_REQUEST);

	if (!isset ($month_to)) 
		$month_to = date ("m");

	if (!isset ($from_year)) 
		$from_year = date("Y");
	if (!isset ($from_month)) 
		$from_month = date ("m");
	if (!isset ($from_day)) 
		$from_day = "01";
	if (!isset ($to_year)) 
		$to_year = date("Y");
	if (!isset ($to_month)) 
		$to_month = date ("m");
	if (!isset ($to_day)) 
		$to_day = date ("d");



	# validate input
	require_lib("validate");
	$v = new validate ();

	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.view();
	}

	core_connect ();

	$get_acc = "SELECT accid,topacc,accnum,accname FROM accounts WHERE accid = '$accid' LIMIT 1";
	$run_acc = db_exec ($get_acc) or errDie ("Unable to get account information.");
	if (pg_numrows ($run_acc) < 1){
		#account not found ?
		$show_subs = "";
	}else {
		$show_subs = "
			<tr>
				<th>Account</th>
				<th>DR</th>
				<th>CR</th>
			</tr>";

		$carr = pg_fetch_array ($run_acc);

		#if this account is main, just get subs ....
		if ($carr['accnum'] == "000"){

			$get_tot = "SELECT debit, credit FROM trial_bal WHERE month='$month_to' AND accid='$carr[accid]'";
			$run_tot = db_exec($get_tot) or errDie("Unable to retrieve trial balance information from Cubit.");
			$mcarr = pg_fetch_array ($run_tot);

			$show_subs .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><b>$carr[topacc]/$carr[accnum] $carr[accname]</b></td>
					<td nowrap><b>".CUR." ".sprint ($mcarr['debit'])."</td>
					<td nowrap><b>".CUR." ".sprint ($mcarr['credit'])."</td>
				</tr>";

			#get any subs
			$get_subs = "SELECT topacc,accnum,accname FROM accounts WHERE topacc = '$carr[topacc]' AND accnum != '000' ORDER BY accnum";
			$run_subs = db_exec ($get_subs) or errDie ("Unable to get sub accounts information.");
			while ($sarr = pg_fetch_array ($run_subs)){
				$show_subs .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$sarr[topacc]/$sarr[accnum] $sarr[accname]</td>
						<td></td>
						<td></td>
					</tr>";
			}
		}

		#if this account is sub, get main first
		if ($carr['accnum'] != "000"){


			#now get all the sub account
			$get_subs = "SELECT accid,topacc,accnum,accname FROM accounts WHERE topacc = '$carr[topacc]' AND accnum != '000' ORDER BY accnum ASC";
			$run_subs = db_exec ($get_subs) or errDie ("Unable to get main account information.");
			if (pg_numrows ($run_subs) > 0){
				while ($sub_arr = pg_fetch_array ($run_subs)){

					$get_tot = "SELECT debit, credit FROM trial_bal WHERE month='$month_to' AND accid='$sub_arr[accid]'";
					$run_tot = db_exec($get_tot) or errDie("Unable to retrieve trial balance information from Cubit.");
					$scarr = pg_fetch_array ($run_tot);

					if ($sub_arr['accnum'] == $carr['accnum']){
						$show_subs_tmp .= "
							<tr bgcolor='".bgcolorg()."'>
								<td><b>$sub_arr[topacc]/$sub_arr[accnum] $sub_arr[accname]</b></td>
								<td><b>".CUR." ".sprint ($scarr['debit'])."</b></td>
								<td><b>".CUR." ".sprint ($scarr['credit'])."</b></td>
							</tr>";
					}else {
						$show_subs_tmp .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$sub_arr[topacc]/$sub_arr[accnum] $sub_arr[accname]</td>
								<td>".CUR." ".sprint ($scarr['debit'])."</td>
								<td>".CUR." ".sprint ($scarr['credit'])."</td>
							</tr>";
					}

					$total_dr += $scarr['debit'];
					$total_cr += $scarr['credit'];

				}
			}

			$get_main = "SELECT accid,topacc,accnum,accname FROM accounts WHERE topacc = '$carr[topacc]' AND accnum = '000' LIMIT 1";
			$run_main = db_exec ($get_main) or errDie ("Unable to get main account information.");
			if (pg_numrows ($run_main) > 0){
				$main_arr = pg_fetch_array ($run_main);

				$get_tot = "SELECT debit, credit FROM trial_bal WHERE month='$month_to' AND accid='$main_arr[accid]'";
				$run_tot = db_exec($get_tot) or errDie("Unable to retrieve trial balance information from Cubit.");
				$mcarr = pg_fetch_array ($run_tot);

				$show_subs .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$main_arr[topacc]/$main_arr[accnum] $main_arr[accname]</td>
						<td>".CUR." ".sprint ($total_dr)."</td>
						<td>".CUR." ".sprint ($total_cr)."</td>
					</tr>
					$show_subs_tmp";
			}


		}
	}

	db_connect ();

	$get_trans = "
		SELECT * FROM \"1\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"2\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"3\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"4\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"5\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"6\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"7\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"8\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"9\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"10\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"11\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"12\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"13\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' UNION 
		SELECT * FROM \"14\".transect WHERE (debit = '$carr[accid]' OR credit = '$carr[accid]') AND date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY date ASC, refnum ASC";
	$run_trans = db_exec ($get_trans) or errDie ("Unable to get account transactions information.");
	if (pg_numrows ($run_trans) > 0){
		while ($tran = pg_fetch_array ($run_trans)){
			# get vars from tran as the are in db
			foreach ($tran as $key => $value) {
				$$key = $value;
			}

			# format date
			$date = explode("-", $date);
			$date = $date[2]."-".$date[1]."-".$date[0];
			$sdate = explode("-", $sdate);

			if(isset($sdate[2])) {
				$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
			} else {
				$sdate=$date;
			}

			$amount = sprint($amount);

			$show_results .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$date</td>
						<td>$sdate</td>
						<td>$dtopacc/$daccnum&nbsp;&nbsp;&nbsp;$daccname</td>
						<td>$ctopacc/$caccnum&nbsp;&nbsp;&nbsp;$caccname</td>
						<td align='right'>$refnum</td>
						<td align='right' nowrap>".CUR." $amount</td>
						<td>$details</td>
						<td>$author</td>
					</tr>";

			$total_amount += $amount;
		}


	}else {
		$show_results = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='8'>No transactions found for selected date range.</td>
			</tr>";
	}


	$display = "
		<center>
		<h3>Drilldown Report</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='accid' value='$accid'>
		<table ".TMPL_tblDflts." width='100%'>
			$show_subs
		</table>
		<table ".TMPL_tblDflts." width='100%'>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' onClick=\"document.location='../core/acc-new2.php';\" value='Create New Account'></td>
				<td colspan='7' align='right'><input type='button' onClick='window.close();' value='[X] Close Window'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='8'>Transaction Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7' align='center' nowrap>
					<table>
						<tr>
							<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
							<td>&nbsp;&nbsp;TO&nbsp;&nbsp;</td>
							<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
						</tr>
					</table>
				</td>
				<td><input type='submit' value='View Transactions'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th>Date</th>
				<th>System Date</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Ref No</th>
				<th>Amount</th>
				<th>Details</th>
				<th>User</th>
			</tr>
			$show_results
		</table>
		</form>";
	return $display;


}




?>
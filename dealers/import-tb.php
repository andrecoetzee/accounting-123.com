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
require ("../settings.php");
require("../core-settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "enter_data":
			$OUTPUT = enter_data($_POST,$_FILES);
			break;
		case "enter2":
			$OUTPUT = enter_data2($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm_data($_POST,$_FILES);
			break;
		case "write":
			$OUTPUT = write_data($_POST);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} else {
	$OUTPUT = select_file();
}

$OUTPUT .= mkQuickLinks();

require("../template.php");




function select_file ()
{

	global $_POST;

	$qry = new dbQuery(DB_SQL,"SELECT SUM(debit) = 0 AND SUM(credit) = 0 AS res FROM core.trial_bal");
	$qry->run();

	if ($qry->fetch_result() == "f") {
		$OUTPUT = "<li class='err'>You cannot import data when you have
			already have entries in your accounting journal. Importing data
			is used for open balances only.</li>";
		return $OUTPUT;
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) < 1) {
		$Sl = "SELECT * FROM vatcodes WHERE zero='Yes'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "Please set up your vatcodes first.";
		}
	}

	$vcd = pg_fetch_row($Ri);



	$OUTPUT = "
		<h3>Import Trial Balance</h3>
		<li class='err'>The data needs to be comma seperated (acc num,account name,debit,credit), Ex: (2000/000,Sales,0.00,50000)</li>
		<li class='err'>The import has a facility to create accounts for you if they
		do not exist. They will however be created with the same name and account number
		as in the CSV file. If you wish to create the account yourself you will have to do this before
		importing the file.</li>
		<li class='err'>Also make sure that the CSV file does not contain any totals.</li>
		<form method='POST' enctype='multipart/form-data' action='".SELF."'>
			<input type='hidden' name='key' value='enter_data'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>File details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Please select Trial Balance csv</td>
				<td><input type='file' name='compfile'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Import &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $OUTPUT;

}




function enter_data($_POST,$_FILES="")
{

	extract($_POST);

	if($_FILES != "") {

		$importfile = tempnam("/tmp", "cubitimport_");
		$file = fopen($_FILES["compfile"]["tmp_name"], "r");

		if ( $file == false) {
			return "<li class='err'>Cannot read file.</li>".select_file();
		}

		db_conn('cubit');

		$Sl = "DROP TABLE import_data";
		$Ri = @db_exec_safe($Sl);


		$Sl = "
			CREATE TABLE import_data (
				id serial,
				des1 varchar, des2 varchar,
				des3 varchar, des4 varchar,
				des5 varchar, des6 varchar,
				des7 varchar, des8 varchar,
				des9 varchar, des10 varchar,
				des11 varchar, des12 varchar,
				des13 varchar, des14 varchar,
				des15 varchar, des16 varchar
			)";
		$Ri = @db_exec($Sl);

		$Sl = "DELETE FROM import_data";
		$Ri = db_exec($Sl) or errDie("Unable to clear import table");

		while (!feof($file) ) {
			$data = safe(fgets($file, 4096));
			$datas = explode(",",$data);

			if(!isset($datas[1])) {
				continue;
			}

			$temp = explode('/',$datas['0']);
			$valtemp = $temp['0'];
			$datas['0'] = str_pad($valtemp,4,"0")."/".$temp['1'];

			$datas[2] = sprint(abs($datas[2]));
			$datas[3] = sprint(abs($datas[3]));

			$Sl = "
				INSERT INTO import_data (
					des1, des2, des3, des4
				) VALUES (
					'$datas[0]', '$datas[1]', '$datas[2]', '$datas[3]'
				)";
			$Rl = db_exec($Sl) or errDie("Unable to insert data.");
		}

		fclose($file);
	}

	global $_SESSION;

	$out = "
		<h3>Trial Balance Import</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='enter2'>
			<input type='hidden' name='login' value='1'>
			<input type='hidden' name='div' value='$_SESSION[USER_DIV]'>
			<input type='hidden' name='login_user' value='$_SESSION[USER_NAME]'>
			<input type='hidden' name='login_pass' value='$_SESSION[USER_PASS]'>
			<input type='hidden' name='code' value='$_SESSION[code]'>
			<input type='hidden' name='comp' value='$_SESSION[comp]'>
			<input type='hidden' name='noroute' value='1'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2' class='err'># Indicates an account (Number) that already exists</td>
			</tr>
			<tr>
				<td colspan='2' class='err'>! Indicates an account (Number) that is a duplicate of
					another account number in the import file.</td>
			</tr>
			<tr>
				<td colspan='2' class='err'>In order to avoid errors, ensure that account numbers
					are in the correct format</td>
			</tr>
			<tr>
				<td colspan='2' class='err'>Remember to select the accounts to link to in the lists provided.
					If the account you wish to link with does not exist, you can specify that the account
					should be created. Do this by selecting one of the following options:<br />
					1. New Income Account, will create a new Income account<br />
					2. New Balance Account, will create and new Balance Sheet account<br />
					3. New Expense Account, will create a new Expense account.
				</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorc(1)."'>
				<th>Period to import into:</th>
				<td>".finMonList("prd", PRD_DB)."</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Acc No</th>
				<th>Account Name</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Select Account to link to</th>
			</tr>";

	$accsql = new dbSelect("accounts", "core", array(
		"order" => "accname"
	));
	$accsql->run();

	$acclist = array();
	while ($ai = $accsql->fetch_array()) {
		$acclist[$ai["accname"]] = $ai["accid"];
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data ORDER BY des1";
	$Ri = db_exec($Sl);

	$i = 0;
	$tot_debit = 0;
	$tot_credit = 0;

	db_conn('core');
	$newacc_selections = array();
	while($fd = pg_fetch_array($Ri)) {
		$fid = $fd['id'];

		$bgcolor = bgcolor($i);

		$Accounts = "
		<select name='accounts[$fid]' id='accounts[$fid]'>
		<optgroup label='Create New Account'>
			<option value='n1'>New Income Account</option>
			<option value='n2'>New Expense Account</option>
			<option value='n3'>New Balance Account</option>
		</optgroup>
		<optgroup label='Use Existing Account'>";

		$has_sel = false;
		foreach ($acclist as $accname => $accid) {
			$sel = "";

			if (!$has_sel) {
				$m1 = isset($accounts[$fid])
						&& $accounts[$fid] == $accid;
				$m2 = $accname == $fd['des2']
						&& !isset($accounts[$fid]);
				$m3 = !empty($fd["des2"])
						&& (stristr($accname, $fd['des2']))
						&& !isset($accounts[$fid])
						&& !isset($acclist[$fd["des2"]]);

				if ($m1 || $m2 || $m3) {
					$sel = "selected";
					$has_sel = true;
				}
			}

			$Accounts .= "<option value='$accid' $sel>$accname</option>";
		}

		$Accounts.="
		</optgroup>
		</select>";

		list($var1, $var2) = explode('/',$fd['des1']);

		if (!$has_sel) {
			if ($var1 >= MIN_INC && $var1 <= MAX_INC) {
				$newacc_selections[$fid] = "n1";
			} else if ($var1 >= MIN_EXP && $var1 <= MAX_EXP) {
				$newacc_selections[$fid] = "n2";
			} else if ($var1 >= MIN_BAL && $var1 <= MAX_BAL) {
				$newacc_selections[$fid] = "n3";
			}
		}

		db_conn('core');

		$get_check = "SELECT * FROM accounts WHERE topacc = '$var1' AND accnum = '$var2'";
		$run_check = db_exec($get_check) or errDie("Unable to get account check");
		if (pg_numrows($run_check) < 1){
			$showerr = "";
		} else {
			$showerr = "<b class='err'>#</b>";
		}

		$check_dup = new dbSelect("import_data", "cubit", grp(
			m("where", "des1='$fd[des1]' AND id!='$fd[id]'")
		));
		$check_dup->run();

		if ($check_dup->num_rows() > 0) {
			$showerr .= "<b class='err'>!</b>";
		}

		$out .= "
			<tr class='".bg_class()."'>
				<td>$showerr $fd[des1]</td>
				<td>$fd[des2]</td>
				<td>$fd[des3]</td>
				<td>$fd[des4]</td>
				<td>$Accounts</td>
			</tr>";

		$tot_debit += $fd['des3'];
		$tot_credit += $fd['des4'];

	}

	$bgcolor = bgcolor($i);

	$tot_debit = sprint($tot_debit);
	$tot_credit = sprint($tot_credit);

	$out .= "
		<tr class='".bg_class()."'>
			<td colspan='2'>Total</td>
			<td align='right'>$tot_debit</td>
			<td align='right'>$tot_credit</td>
		</tr>";

	if($tot_debit == $tot_credit) {
		$out .= "
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>";
	} else {
		$out .= "
			<li class='err'>The Total debit and credit are not the same. You cannot import a trial balance that doesnt balance.<br>
			Please check the file you are trying to import, the total debits and total credits should be the same.<br>
			Please fix the file and try again.</li>";
	}

	$out .= "
		</form>
		</table>
		<script>";

	foreach ($newacc_selections as $fid => $pval) {
		$out .= "l = document.getElementById('accounts[$fid]'); l.value='$pval';";
	}

	$out .= "
	</script>";
	return $out;

}



function enter_data2($_POST)
{

	extract($_POST);

	global $_SESSION;

	$out = "
		<h3>Trial Balance Import</h3>
		%%USEDNUMS_MSG%%
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='login' value='1' />
			<input type='hidden' name='div' value='$_SESSION[USER_DIV]' />
			<input type='hidden' name='login_user' value='$_SESSION[USER_NAME]' />
			<input type='hidden' name='login_pass' value='$_SESSION[USER_PASS]' />
			<input type='hidden' name='code' value='$_SESSION[code]' />
			<input type='hidden' name='comp' value='$_SESSION[comp]' />
			<input type='hidden' name='noroute' value='1' />
			<input type='hidden' name='prd' value='$prd' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Importing into ".getMonthName($prd)." ".getYearOfFinMon($prd)."</th>
			</tr>
			".TBL_BR."
			<tr>
				<th>Acc No</th>
				<th>Account Name</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Confrim Account to link to/Select category of new account</th>
			</tr>";

	db_conn('exten');

	$Sl = "SELECT stkacc FROM warehouses";
	$Ri = db_exec($Sl);

	$wd = pg_fetch_array($Ri);

	$ic = $wd['stkacc'];

	$Sl = "SELECT debtacc,credacc FROM departments";
	$Ri = db_exec($Sl);

	$dd = pg_fetch_array($Ri);

	$cc = $dd['debtacc'];
	$sc = $dd['credacc'];

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data ORDER BY des1";
	$Ri = db_exec($Sl);

	$i = 0;
	$tot_debit = 0;
	$tot_credit = 0;

	db_conn('core');

	$Sl = "SELECT accnum FROM salacc WHERE name='salaries control'";
	$Rt = db_exec($Sl);

	$sd = pg_fetch_array($Rt);

	$salc = $sd['accnum'];

	$blocked = array();

	$cc_tot = 0;
	$sc_tot = 0;
	$sal_tot = 0;
	$i_tot = 0;

	$usednums_msg = $dupnums_msg = "";

	while($fd = pg_fetch_array($Ri)) {
		$fid = $fd['id'];

		$bgcolor = bgcolor($i);

		$accnum_parts = explode("/", $fd["des1"]);

		$accnum = "$fd[des1]";
		if(substr($accounts[$fid], 0, 1) == "n") {
			$check_num = new dbSelect("accounts", "core", grp(
				m("where", wgrp(
					m("topacc", $accnum_parts[0]),
					m("accnum", $accnum_parts[1])
				))
			));
			$check_num->run();
		} else {
			$check_num = false;
		}

		$check_dup = new dbSelect("import_data", "cubit", grp(
			m("where", "des1='$fd[des1]' AND id!='$fd[id]'")
		));
		$check_dup->run();

		if (($check_num && $check_num->num_rows() > 0) || $check_dup->num_rows() > 0) {
			$mark = "";

			if ($check_dup->num_rows() > 0) {
				$mark .= IMP;
				$dupnums_msg = "
				<tr>
					<td colspan='2' class='err'>Accounts marked with ".IMP." have account numbers
						used by other accounts in the import. Please change them so all
						accounts have unique numbers.
					</tr>
				</tr>";
			}

			if ($check_num && $check_num->num_rows() > 0) {
				$mark .= REQ;
				$usednums_msg = "
				<tr>
					<td colspan='2' class='err'>Accounts marked with ".REQ." have account numbers
						already in use by Cubit. Either delete these accounts from Cubit or change the
						account numbers in the fields provided.
					</td>
				</tr>";
			}

			$recommended_accnums = "
			<tr>
				<td colspan='2' class='err'><u><b>Recommended Account Numbers:</b></u></td>
			</tr>
			<tr class='err'>
				<td nowrap><b>Income Account</b>:</td>
				<td width='100%'>".str_pad(MIN_INC, 4, '0', STR_PAD_LEFT)."/000 <i>to</i> ".MAX_INC."/999</td>
			</tr>
			<tr class='err'>
				<td nowrap><b>Expense Account</b>:</td>
				<td width='100%'>".MIN_EXP."/000 <i>to</i> ".MAX_EXP."/999</td>
			</tr>
			<tr class='err'>
				<td nowrap><b>Balance Sheet Account</b>:</td>
				<td width='100%'>".MIN_BAL."/000 <i>to</i> ".MAX_BAL."/999</td>
			</tr>";

			$accnum = "$mark
				<input type='text' size='4' name='topacc[$fid]' value='$accnum_parts[0]' /> /
				<input type='text' size='3' name='accnum[$fid]' value='$accnum_parts[1]' />";
		}

		if(substr($accounts[$fid], 0, 1) == "n") {
			switch (substr($accounts[$fid], 1, 1)) {
				case "1":
					$catsa= array (
						"-- INCOME",
						"other_income"=>"Other Income",
						"sales"=>"Sales"
					);
					break;
				case "2":
					$catsa = array(
						"-- EXPENSES",
						"expenses"=>"Expenses",
						"cost_of_sales"=>"Cost of Sales"
					);
					break;
				case "3":
					$catsa = array(
						"-- ASSETS",
						"fixed_asset"=>"Fixed Assets",
						"investments"=>"Investments",
						"other_fixed_asset"=>"Other Fixed Assets",
						"current_asset"=>"Current Assets",
						"-- EQUITY AND LIABILITIES",
						"share_capital"=>"Share Capital",
						"retained_income"=>"Retained Income",
						"shareholders_loan"=>"Shareholders Loan",
						"non_current_liability"=>"Non-current Liabilities",
						"long_term_borrowing"=>"Long Term Borrowings",
						"other_long_term_liability"=>"Other Long Term Liabilities",
						"current_liability"=>"Current Liabilities"
					);
			}

			$cats = "<select name='cat[$fid]'>";
			$optgrouped = false;
			foreach($catsa as $dbval=>$humanval) {
				if (isset($cat) && $cat[$fid] == "$dbval:$humanval") {
					$sel = "selected";
				} else {
					$sel = "";
				}

				if (substr($humanval, 0, 3) == "-- ") {
					if ($optgrouped) $cats .= "</optgroup>";
					$cats .= "<optgroup label='".substr($humanval, 3)."'>";
					continue;
				}
				$cats .= "<option value='$dbval:$humanval' $sel>$humanval</option>";
			}
			if ($optgrouped) $cats .= "</optgroup>";
			$cats .= "</select>";

			$add = "$cats</td>";
		} else {
			$accounts[$fid] += 0;
			if (in_array($accounts[$fid],$blocked)) {
				$Sl = "SELECT accid,accname FROM accounts WHERE accid='$accounts[$fid]'";
				$Rx = db_exec($Sl);

				$ad = pg_fetch_array($Rx);

				return enter_data($_POST)."<li class='err'>You cannot link an account to more than one account($ad[accname]).</li>";
			}

			$blocked[] = $accounts[$fid];

			$Sl = "SELECT accid,accname FROM accounts WHERE accid='$accounts[$fid]'";
			$Rx = db_exec($Sl);

			$ad = pg_fetch_array($Rx);

			$add = "$ad[accname]</td>";

			if($ad['accid'] == $cc) {
				$cc_tot = sprint($fd['des3'] - $fd['des4']);
			}

			if($ad['accid'] == $sc) {
				$sc_tot = sprint($fd['des4'] - $fd['des3']);
			}

			if($ad['accid'] == $ic) {
				$i_tot = sprint($fd['des3'] - $fd['des4']);
			}

			if($ad['accid'] == $salc) {
				$sal_tot = sprint($fd['des4'] - $fd['des3']);
			}
		}

		$out .= "
			<input type='hidden' name='accounts[$fid]' value='$accounts[$fid]' />
			<tr class='".bg_class()."'>
				<td>$accnum</td>
				<td>$fd[des2]</td>
				<td>$fd[des3]</td>
				<td>$fd[des4]</td>
				<td>$add</td>
			</tr>";

		$tot_debit += $fd['des3'];
		$tot_credit += $fd['des4'];
	}

	$tot_debit = sprint($tot_debit);
	$tot_credit = sprint($tot_credit);

	$out .= "
		<tr class='".bg_class()."'>
			<td colspan='2'>Total</td>
			<td align='right'>$tot_debit</td>
			<td align='right'>$tot_credit</td>
		</tr>";

	if($cc_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT cusnum,accno,surname FROM customers ORDER BY surname";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your customer control account you need to add customers first</li>";
		}

		$out .= "
			<tr>
				<td colspan='4'><li class='err'>Please enter the customer balances to link up with 'Customer Control Account'</li></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Acc No</th>
							<th>Customer</th>
							<th>Balance</th>
						</tr>";

		$tot = 0;

		while ($cd = pg_fetch_array($Ri)) {
			$cid = $cd['cusnum'];

			if (!isset($cbalance[$cid])) {
				$cbalance[$cid] = "";
			}

			$out .= "
			<tr class='".bg_class()."'>
				<td>$cd[accno]</td>
				<td>$cd[surname]</td>
				<td><input type='text' size='12' name='cbalance[$cid]' value='$cbalance[$cid]'></td>
			</tr>";
		}

		$out .= "
				<tr class='".bg_class()."'>
					<td colspan='2'><b>Total</b></td>
					<td align='right'><b>".CUR." $cc_tot</b></td>
				</tr>
			</td>
		</tr>";

		$out .= "<tr><td><br></td></tr>";
	}

	if($sc_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT supid,supno,supname FROM suppliers ORDER BY supname";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your supplier control account you need to add suppliers first</li>";
		}

		$out .= "
			<tr>
				<td colspan='4'><li class='err'>Please enter the supplier balances to link up with 'Supplier Control Account'</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Supplier No</th>
							<th>Supplier</th>
							<th>Balance</th>
						</tr>";


		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$sid = $cd['supid'];

			if(!isset($sbalance[$sid])) {
				$sbalance[$sid] = "";
			}

			$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[supno]</td>
					<td>$cd[supname]</td>
					<td><input type='text' size='12' name='sbalance[$sid]' value='$sbalance[$sid]'></td>
				</tr>";
		}

		$out .= "
		<tr class='".bg_class()."'>
			<td colspan='2'><b>Total</b></td>
			<td align='right'><b>".CUR." $sc_tot</b></td>
		</tr>";

		$out .= "
		</td></tr>";

		$out .= TBL_BR;
	}

	if ($sal_tot > 0) {
		$emps = qryEmployee();

		$out .= "
			<tr>
				<td colspan='4'><li class='err'>Please enter the employee balances to link up with 'Employees Control Account'</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Employee Number</th>
							<th>Employee</th>
							<th>Balance</th>
						</tr>";

		$tot = 0;

		while($cd = $emps->fetch_array()) {
			$eid = $cd['empnum'];

			if(!isset($ebalance[$eid])) {
				$ebalance[$eid] = "";
			}

			$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[enum]</td>
					<td>$cd[sname], $cd[fnames]</td>
					<td><input type='text' size='12' name='ebalance[$eid]' value='$ebalance[$eid]'></td>
				</tr>";

			$i++;

		}

		$out .= "
					<tr class='".bg_class()."'>
						<td colspan='2'><b>Total</b></td>
						<td align='right'><b>".CUR." $sal_tot</b></td>
					</tr>
				</td>
			</tr>";

		$out .= "<tr><td><br></td></tr>";
	}

	if($i_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT stkid,stkcod,stkdes FROM stock ORDER BY stkcod";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your inventory control account you need to add stock first</li>";
		}

		$out .= "
			<tr>
				<td colspan='4'><li class='err'>Please enter the inventory balances to link up with 'Inventory Control Account'</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Stock Code</th>
							<th>Description</th>
							<th>Balance(".CUR.")</th>
							<th>Units(Qty)</th>
						</tr>";


		$tot = 0;
		$stocktot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$iid = $cd['stkid'];

			if(!isset($ibalance[$iid])) {
				$ibalance[$iid] = "";
			}

			if(!isset($units[$iid])) {
				$units[$iid] = "";
			}

			#check if this stock item has balance,units
			$stksql = "SELECT units,balance FROM stock_tbimport WHERE stkid = '$cd[stkid]' LIMIT 1";
			$runstk = db_exec($stksql) or errDie ("Unable to get stock information.");
			if (pg_numrows($runstk) > 0){
				if (!isset($ibalance[$iid]) OR (strlen($ibalance[$iid]) < 1)){
					$stkarr = pg_fetch_array($runstk);
					$ibalance[$iid] = $stkarr['balance'];
					$units[$iid] = $stkarr['units'];
				}
			}

			$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[stkcod]</td>
					<td>$cd[stkdes]</td>
					<td><input type=text size=12 name=ibalance[$iid] value='$ibalance[$iid]'></td>
					<td><input type=text size=5 name=units[$iid] value='$units[$iid]'></td>
				</tr>";

			$stocktot = $stocktot + $ibalance[$iid];
			$i++;

		}

		$bgcolor = bgcolor($i);
		$stocktot = sprint ($stocktot);

		$out .= "
					<tr class='".bg_class()."'>
						<td colspan='2'><b>Import Stock Total</b></td>
						<td align='right'><b>".CUR." $stocktot</b></td>
						<td></td>
					</tr>
					<tr class='".bg_class()."'>
						<td colspan='2'><b>Total</b></td>
						<td align='right'><b>".CUR." $i_tot</b></td>
						<td>&nbsp;</td>
					</tr>
				</td>
			</tr>";

		$out .= TBL_BR;
	}

	$out .= "
			<tr>
				<td colspan='2'><input type='submit' name='back' value='&laquo; Correction'></td>
				<td colspan='1' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			<input type='hidden' name='cc_tot' value='$cc_tot'>
			<input type='hidden' name='sal_tot' value='$sal_tot'>
			<input type='hidden' name='sc_tot' value='$sc_tot'>
			<input type='hidden' name='i_tot' value='$i_tot'>
		</form>
		</table>";

	if(!isset($recommended_accnums))
		$recommended_accnums = "";

	$dispmsg = "
		<table ".TMPL_tblDflts." width='600'>
			$usednums_msg
			$dupnums_msg
			".TBL_BR."
			$recommended_accnums
			".TBL_BR."
		</table>";

	$out = preg_replace("/%%USEDNUMS_MSG%%/", $dispmsg, $out);

	return $out;

}




function confirm_data($_POST)
{

	extract($_POST);

	if(isset($back)) {
		return enter_data($_POST);
	}

	/* do account number changes */
	if (isset($topacc) && is_array($topacc)) {
		$qry = new dbSql();
		foreach ($topacc as $fid => $v) {
			if (isset($accnum[$fid])) {
				$sql = "UPDATE cubit.import_data
						SET des1='$topacc[$fid]/$accnum[$fid]'
						WHERE id='$fid'";
				$qry->setSql($sql);
				$qry->run();
			}
		}
	}

	$qry = new dbSelect("import_data", "cubit");
	$qry->run();

	$check_num = new dbSelect("accounts", "core");
	$check_dup = new dbSelect("import_data", "cubit");
	while($fd = $qry->fetch_array()) {
		$fid = $fd['id'];

		$accnum_parts = explode("/", $fd["des1"]);

		if(isset($topacc[$fid]) && isset($accnum[$fid])) {
			$check_num->setOpt(grp(
				m("where", wgrp(
					m("topacc", $accnum_parts[0]),
					m("accnum", $accnum_parts[1])
				))
			));
			$check_num->run();

			$check_dup->setOpt(grp(
				m("where", "des1='$fd[des1]' AND id!='$fd[id]'")
			));
			$check_dup->run();

			if ($check_num->num_rows() > 0 || $check_dup->num_rows() > 0) {
				return enter_data2($_POST);
			}
		}
	}

	global $_SESSION;

	$out = "
		<h3>Trial Balance Import</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='login' value='1' />
			<input type='hidden' name='div' value='$_SESSION[USER_DIV]' />
			<input type='hidden' name='login_user' value='$_SESSION[USER_NAME]' />
			<input type='hidden' name='login_pass' value='$_SESSION[USER_PASS]' />
			<input type='hidden' name='code' value='$_SESSION[code]' />
			<input type='hidden' name='comp' value='$_SESSION[comp]' />
			<input type='hidden' name='noroute' value='1' />
			<input type='hidden' name='prd' value='$prd' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Importing into ".getMonthName($prd)." ".getYearOfFinMon($prd)."</th>
			</tr>
			".TBL_BR."
			<tr>
				<th>Acc No</th>
				<th>Account Name</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Select Account to link to</th>
			</tr>";

	db_conn('cubit');
	$sql = "SELECT * FROM import_data ORDER BY des1";
	$rslt = db_exec($sql);

	$i = 0;
	$tot_debit = 0;
	$tot_credit = 0;

	db_conn('core');

	while($fd = pg_fetch_array($rslt)) {
		$fid = $fd['id'];

		if($accounts[$fid] == 0) {
			$catss = explode(":",$cat[$fid]);

			if($catss[0] == "0") {
				return enter_data2($_POST)."<li class=err>You need to select a category for the new account</li>";
			}

			$add = "<input type='hidden' name='cat[$fid]' value='$cat[$fid]'>
			(New Account) $catss[1]</td>";
		} else {
			$Sl = "SELECT accid,accname FROM accounts WHERE accid='$accounts[$fid]'";
			$Rx = db_exec($Sl);

			$ad = pg_fetch_array($Rx);

			$add = "$ad[accname]</td>";
		}

		$out .= "
			<input type='hidden' name='accounts[$fid]' value='$accounts[$fid]' />
			<tr class='".bg_class()."'>
				<td>$fd[des1]</td>
				<td>$fd[des2]</td>
				<td>$fd[des3]</td>
				<td>$fd[des4]</td>
				<td>$add</td>
			</tr>";

		$i++;

		$tot_debit += $fd['des3'];
		$tot_credit += $fd['des4'];

	}

	$tot_debit = sprint($tot_debit);
	$tot_credit = sprint($tot_credit);

	$out .= "
		<tr class='".bg_class()."'>
			<td colspan='2'>Total</td>
			<td align='right'>$tot_debit</td>
			<td align='right'>$tot_credit</td>
		</tr>";

	if ($cc_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT cusnum,accno,surname FROM customers ORDER BY surname";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "<li class='err'>If you want to import your customer control account you need to add customers first</li>";
		}

		$out .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Acc No</th>
							<th>Customer</th>
							<th>Balance</th>
						</tr>";

		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$cid = $cd['cusnum'];

			$cbalance[$cid] = sprint($cbalance[$cid]);

			$out .= "
			<tr class='".bg_class()."'>
				<td>$cd[accno]</td>
				<td>$cd[surname]</td>
				<td align='right'><input type='hidden' size='12' name='cbalance[$cid]' value='$cbalance[$cid]' />$cbalance[$cid]</td>
			</tr>";

			$tot += $cbalance[$cid];
		}

		$out .= "
				<tr class='".bg_class()."'>
					<td colspan='2'><b>Total</b></td>
					<td align='right'><b>".CUR." $cc_tot</b></td>
				</tr>
			</td>
		</tr>";

		$out .= TBL_BR;

		if(sprint($cc_tot)!=sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for customers you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($cc_tot).". These need to be the same.</li>";
		}
	}


	if($sc_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT supid,supno,supname FROM suppliers ORDER BY supname";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "<li class='err'>If you want to import your supplier control account you need to add suppliers first</li>";
		}

		$out .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Supplier No</th>
							<th>Supplier</th>
							<th>Balance</th>
						</tr>";


		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$sid = $cd['supid'];

			$sbalance[$sid] = sprint($sbalance[$sid]);

			$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[supno]</td>
					<td>$cd[supname]</td>
					<td align='right'><input type='hidden' size='12' name='sbalance[$sid]' value='$sbalance[$sid]'>".CUR." $sbalance[$sid]</td>
				</tr>";

			$i++;

			$tot += $sbalance[$sid];
		}

		$out .= "
				<tr class='".bg_class()."'>
					<td colspan='2'><b>Total</b></td>
					<td align='right'><b>".CUR." $sc_tot</b></td>
				</tr>
			</td>
		</tr>";

		$out .= TBL_BR;

		if(sprint($sc_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for suppliers you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($sc_tot).". These need to be the same.</li>";
		}
	}


	if($sal_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT empnum,enum,sname,fnames FROM employees ORDER BY sname,fnames";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your employee control account you need to add employees first</li>";
		}

		$out .= "
			<tr>
				<td colspan='4'><li class='err'>Please enter the employee balances to link up with 'Employees Control Account'</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Employee Number</th>
							<th>Employee</th>
							<th>Balance</th>
						</tr>";


		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$eid = $cd['empnum'];

			if(!isset($ebalance[$eid])) {
				$ebalance[$eid] = "";
			}

			$ebalance[$eid] = sprint($ebalance[$eid]);

			$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[enum]</td>
					<td>$cd[sname], $cd[fnames]</td>
					<td align=right><input type='hidden' size='12' name='ebalance[$eid]' value='$ebalance[$eid]'>".CUR." $ebalance[$eid]</td>
				</tr>";

			$i++;

			$tot += $ebalance[$eid];

		}

		$out .= "
					<tr class='".bg_class()."'>
						<td colspan='2'><b>Total</b></td>
						<td align='right'><b>".CUR." $sal_tot</b></td>
					</tr>
				</td>
			</tr>";

		$out .= "<tr><td><br></td></tr>";

		if(sprint($sal_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class=err>The total amount for balances for employees you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($sal_tot).". These need to be the same.</li>";
		}
	}

	if($i_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT stkid,stkcod,stkdes FROM stock ORDER BY stkcod";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your inventory control account you need to add stock first</li>";
		}

		$out .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Stock Code</th>
							<th>Description</th>
							<th>Balance</th>
							<th>Units</th>
						</tr>";

		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$iid = $cd['stkid'];

			if(!isset($ibalance[$iid])) {
				$ibalance[$iid] = "";
			}

			$tot += $ibalance[$iid];

			$units[$iid] += 0;

			if((sprint($ibalance[$iid]) > 0) && ($units[$iid]) <= 0) {
				return enter_data2($_POST)."<li class='err'>You specified $units[$iid] units for $cd[stkcod], but ".CUR." $ibalance[$iid].
				If you want to enter an amount you need to give the qty.</li>";
			}

			$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[stkcod]</td>
					<td>$cd[stkdes]</td>
					<td><input type='hidden' size='12' name='ibalance[$iid]' value='$ibalance[$iid]'>$ibalance[$iid]</td>
					<td><input type='hidden' name='units[$iid]' value='$units[$iid]'>$units[$iid]</td>
				</tr>";

			$i++;

		}

		$out .= "
					<tr class='".bg_class()."'>
						<td colspan='2'><b>Total</b></td>
						<td align='right'><b>".CUR." $i_tot</b></td>
						<td></td>
					</tr>
				</td>
			</tr>";

		$out .= "<tr><td><br></td></tr>";

		if(sprint($i_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for inventory you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($i_tot).". These need to be the same.</li>";
		}
	}

	$out .= "
			<tr>
				<td colspan='2'><input type='submit' name='back' value='&laquo; Correction'></td>
				<td colspan='1' align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<input type='hidden' name='cc_tot' value='$cc_tot'>
			<input type='hidden' name='sal_tot' value='$sal_tot'>
			<input type='hidden' name='sc_tot' value='$sc_tot'>
			<input type='hidden' name='i_tot' value='$i_tot'>
		</form>
		</table>";
	return $out;

}

function write_data($_POST)
{

	extract($_POST);

	if(isset($back)) {
		return enter_data2($_POST);
	}

	db_conn('core');

	$Sl = "SELECT accnum FROM salesacc WHERE name='VATIN'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$vd = pg_fetch_array($Ri);
		$vatin = $vd['accnum'];
	} else {
		$vatin = 0;
	}

	$Sl = "SELECT accnum FROM salesacc WHERE name='VATOUT'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$vd = pg_fetch_array($Ri);
		$vatout = $vd['accnum'];
	} else {
		$vatout = 0;
	}

	db_conn('cubit');
	$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) < 1) {
		$Sl = "SELECT * FROM vatcodes WHERE zero='Yes'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "Please set up your vatcodes first.";
		}
	}

	$vcd = pg_fetch_array($Ri);

	db_conn('exten');

	$Sl = "SELECT debtacc FROM departments";
	$Ri = db_exec($Sl);

	$dd = pg_fetch_array($Ri);

	$cc = $dd['debtacc'];

	db_conn('core');

	$Sl = "SELECT * FROM accounts WHERE accname='Opening Balances / Suspense Account'";
	$Ri = db_exec($Sl) or errDie("Unable to get account.");

	if(pg_num_rows($Ri) < 1) {
		return "<li class='err'>There is no account called 'Opening Balances / Suspense Account'. <br>
		I Need that account.<br>
		Please create it. <br><br>
		Thank you.</li>";
	}

	$ad = pg_fetch_array($Ri);

	$bala = $ad['accid'];

	db_conn(PRD_DB);
	# get last ref number
	
	pglib_transaction("BEGIN");
	
	$refnum = getrefnum();

	/* check for main accounts whose sub accounts add up to same total,
			then clear main account
	   check for main accounts whose sub accounts dont add up, then unblock
	   		main accounts
	 */
	$sql = "SELECT * FROM cubit.import_data";
	$rslt = db_exec($sql) or errDie("Error validating data.");

	$acsub = $acmain = array();
	while ($fd = pg_fetch_array($rslt)) {
		$n = explode("/", $fd["des1"]);
		if (!isset($n[1]) || $n[1] == "000") {
			$n[1] = "000";
		}

		$a = array(
			"num" => $fd["des1"],
			"name" => $fd["des2"],
			"dt" => $fd["des3"],
			"ct" => $fd["des4"]
		);

		if ($n[1] == "000") {
			$acmain["$n[0]"] = $a;
		} else {
			if (!isset($acsub[$n[0]])) {
				$acsub[$n[0]] = array();
			}

			$acsub[$n[0]][] = $a;
		}
	}

	/* match subs with mains */
	$unblock_main = false;
	foreach ($acmain as $k => $v) {
		$totdt = 0;
		$totct = 0;

		if (isset($acsub[$k])) {
			foreach ($acsub[$k] as $sk => $sv) {
				$totdt += $sv["dt"];
				$totct += $sv["ct"];
			}

			if ($totdt - $totct != $v["dt"] - $v["ct"]) {
				$unblock_main = true;
			} else {
				$sql = "UPDATE cubit.import_data SET des3='0', des4='0'
						WHERE des1='$v[num]' AND des2='$v[name]'";
				$rslt = db_exec($sql) or errDie("Error balancing main account with sub accounts: $v[num] - $v[name] with $sv[num] - $sv[name].");;
			}
		}
	}

	if ($unblock_main) {
		$sql = "UPDATE cubit.set SET value = 'nuse', descript = 'Dont block main accounts'
				WHERE label = 'BLOCK' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Error unblocking main accounts.");
	}

	/* continue importing (validated/fixed) data */
	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Ri = db_exec($Sl);

	$i = 0;
	$tot_debit = 0;
	$tot_credit = 0;

	$date = mkdate(getYearOfFinMon($prd), $prd, 1);

	db_conn('core');

	while($fd = pg_fetch_array($Ri)) {
		$fid = $fd['id'];

		$accs=explode('/',$fd['des1']);

		$topacc=$accs[0];
		$topacc = str_pad($topacc,4,"0");

		if(isset($accs[1])) {
			$accnum = $accs[1];
		} else {
			$accnum = "000";
		}

		db_conn('core');

		if($accounts[$fid] == 0) {
			$catss = explode(":",$cat[$fid]);

			if($catss[0] == "other_income" || $catss[0] == "sales") {
				$catT = "I10";
				$type = "I";
			} elseif($catss[0] == "expenses" || $catss[0] == "cost_of_sales") {
				$catT = "E10";
				$type = "E";
			} else {
				$catT = "B10";
				$type = "B";
			}

			$Sl = "
				INSERT INTO accounts (
					topacc, accnum, catid, accname, vat, 
					div, toptype, acctype
				) VALUES (
					'$topacc', '$accnum', '$catT', '$fd[des2]', 'f', 
					'".USER_DIV."', '$catss[0]', '$type'
				)";
			$Rl = db_exec($Sl);

			$accname = $fd['des2'];

			$accid = pglib_lastid ("accounts", "accid");

// 			$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, div) VALUES('$accid', '$topacc', '$accnum', '$fd[des2]', '".USER_DIV."')";
// 			$trialRslt = db_exec($query);

			global $MONPRD;

			insert_trialbal($accid, $topacc, $accnum, $accname, $type, 'f', USER_DIV);

			for ($i = 1; $i <= 12; $i++) {
				$periodname = getMonthName($i);

				$sql = "INSERT INTO ".YR_DB.".$periodname (accid, topacc, accnum, accname,
							debit, credit, div)
						SELECT accid, topacc, accnum, accname, debit, credit, div
						FROM core.trial_bal WHERE month='$i' AND accid='$accid'";
				db_exec($sql) or die($sql);

				$sql = "INSERT INTO \"$i\".openbal (accid, accname, debit, credit, div)
						SELECT accid, accname, debit, credit, div
						FROM core.trial_bal WHERE month='$i' AND accid='$accid'";
				db_exec($sql) or die($sql);

				$sql = "INSERT INTO \"$i\".ledger (acc, contra, edate, eref, descript,
							credit, debit, div, caccname, ctopacc, caccnum, cbalance, dbalance)
						SELECT accid, accid, CURRENT_DATE, '0', 'Balance', '0', '0', div,
							accname, topacc, accnum, credit, debit
						FROM core.trial_bal WHERE month='$i' AND accid='$accid'";
				db_exec($sql) or die($sql);
			}

			$accounts[$fid] = $accid;
		} else {
			$Sl = "UPDATE accounts SET topacc='$topacc',accnum='$accnum',accname='$fd[des2]' WHERE accid='$accounts[$fid]'";
			$Rl = db_exec($Sl);

			$Sl = "UPDATE trial_bal SET topacc='$topacc',accnum='$accnum',accname='$fd[des2]' WHERE accid='$accounts[$fid]'";
			$Rl = db_exec($Sl);
		}

		$Sl = "SELECT accid,accname FROM accounts WHERE accid='$accounts[$fid]'";
		$Rx = db_exec($Sl);

		$ad = pg_fetch_array($Rx);

		$i++;

		$debit = $fd['des3'];
		$credit = $fd['des4'];

		if($debit > 0) {
			writetrans($ad['accid'], $bala,$date, $refnum, sprint($debit), "Opening balance imported");
		}

		if($credit > 0) {
			writetrans($bala,$ad['accid'], $date, $refnum, sprint($credit), "Opening balance imported");
		}

		$tot_debit += $fd['des3'];
		$tot_credit+=$fd['des4'];

		if($ad['accid'] == $vatin) {
			vatr($vcd['id'],$date,"INPUT",$vcd['code'],$refnum,"Opening balance VAT imported",sprint($credit-$debit),sprint($credit-$debit));
		}

		if($ad['accid'] == $vatout) {
			vatr($vcd['id'],$date,"OUTPUT",$vcd['code'],$refnum,"Opening balance VAT imported",sprint($credit-$debit),sprint($credit-$debit));
		}


	}

	$tot_debit = sprint($tot_debit);
	$tot_credit = sprint($tot_credit);

	if($cc_tot > 0) {

		$tot = array_sum($cbalance);
		if(sprint($cc_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for customers you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($cc_tot).". These need to be the same.</li>";
		}

		db_conn('cubit');

		$Sl = "SELECT cusnum,accno,surname FROM customers ORDER BY surname";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your customer control account you need to add customers first</li>";
		}


		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$cid = $cd['cusnum'];

			$cbalance[$cid] = sprint($cbalance[$cid]);

			if($cbalance[$cid] > 0) {
				db_conn('cubit');

				# Update the customer (make balance more)
				$sql = "UPDATE customers SET balance = (balance + '$cbalance[$cid]') WHERE cusnum = '$cid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

				$sql = "
					INSERT INTO stmnt (
						cusnum, invid, amount, date, type, 
						st, div
					) VALUES (
						'$cid', '0', '$cbalance[$cid]', '$date', 'Opening Balance Imported', 
						'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				$sql = "
					INSERT INTO open_stmnt (
						cusnum, invid, amount, balance, date, 
						type, st, div
					) VALUES (
						'$cid', '0', '$cbalance[$cid]', '$cbalance[$cid]', '$date', 
						'Opening Balance Imported', 'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				crecordDT($cbalance[$cid], $cid,$date);

				custledger($cid, $bala, $date, 0, "Opening Balance Imported",$cbalance[$cid] , "d");
			}  elseif($cbalance[$cid]<0) {
				db_conn('cubit');

				# Update the customer (make balance more)
				$sql = "UPDATE customers SET balance = (balance + '$cbalance[$cid]') WHERE cusnum = '$cid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

				$sql = "
					INSERT INTO stmnt (
						cusnum, invid, amount, date, type, 
						st, div
					) VALUES (
						'$cid', '0', '$cbalance[$cid]', '$date', 'Opening Balance Imported', 
						'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				$sql = "
					INSERT INTO open_stmnt (
						cusnum, invid, amount, balance, date, 
						type, st, div
					) VALUES (
						'$cid', '0', '$cbalance[$cid]', '$cbalance[$cid]', '$date', 
						'Opening Balance Imported', 'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				crecordCT(-$cbalance[$cid], $cid,$date);

				custledger($cid, $bala, $date, 0, "Opening Balance Imported",-$cbalance[$cid] , "c");
			}

			$i++;

			$tot += $cbalance[$cid];

		}


	}

	if($sc_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT supid,supno,supname FROM suppliers ORDER BY supname";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your supplier control account you need to add suppliers first</li>";
		}


		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$sid = $cd['supid'];

			$sbalance[$sid] += 0;

			if($sbalance[$sid] > 0) {

				db_conn('cubit');

				$sql = "UPDATE suppliers SET balance = (balance + '$sbalance[$sid]') WHERE supid = '$sid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

				$sql = "
					INSERT INTO sup_stmnt (
						supid, edate, ref, cacc, descript, 
						amount, div
					) VALUES (
						'$sid', '$date', '0', '$bala', 'Opening balance imported', 
						'$sbalance[$sid]', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				recordCT(-$sbalance[$sid], $sid,$date);

				suppledger($sid, $bala, $date, $refnum, "Opening balance imported", $sbalance[$sid], "c");

			} elseif($sbalance[$sid] < 0) {

				db_conn('cubit');

				$sql = "UPDATE suppliers SET balance = (balance + '$sbalance[$sid]') WHERE supid = '$sid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

				$sql = "
					INSERT INTO sup_stmnt (
						supid, edate, ref, cacc, descript, amount, div
					) VALUES (
						'$sid', '$date', '0', '$bala', 'Opening balance imported', 
						'$sbalance[$sid]', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				recordDT(-$sbalance[$sid], $sid,$date);

				suppledger($sid, $bala, $date, $refnum, "Opening balance imported", $sbalance[$sid], "d");

			}

			$i++;

			$tot += $sbalance[$sid];

		}



		if(sprint($sc_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for suppliers you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($sc_tot).". These need to be the same.</li>";
		}
	}

	if($sal_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT empnum,enum,sname,fnames FROM employees ORDER BY sname,fnames";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your employee control account you need to add employees first</li>";
		}




		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$eid = $cd['empnum'];

			if(!isset($ebalance[$eid])) {
				$ebalance[$eid] = "";
			}

			$ebalance[$eid] = sprint($ebalance[$eid]);

			db_conn('cubit');
			$Sl = "UPDATE employees SET balance=balance+'$ebalance[$eid]' WHERE empnum = '$eid' AND div = '".USER_DIV."'";
			$Rt = db_exec($Sl) or errDie("Unable to get employee details.");

			if($ebalance[$eid] > 0) {
				empledger($eid, $bala, $date, $refnum,"Opening balance imported" , $ebalance[$eid] , "c");
			} else {
				empledger($eid, $bala, $date, $refnum,"Opening balance imported" ,  abs($ebalance[$eid]), "d");
			}

			$i++;

			$tot += $ebalance[$eid];

		}

		if(sprint($sal_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for employees you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($sal_tot).". These need to be the same.</li>";
		}
	}


	if($i_tot > 0) {
		db_conn('cubit');

		$Sl = "SELECT stkid,stkcod,stkdes FROM stock ORDER BY stkcod";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>If you want to import your inventory control account you need to add stock first</li>";
		}




		$tot = 0;

		while($cd = pg_fetch_array($Ri)) {

			$iid = $cd['stkid'];

			if(!isset($ibalance[$iid])) {
				$ibalance[$iid] = "";
			}

			if($ibalance[$iid] > 0) {

				$unitnum = $units[$iid];
				db_connect();
				$sql = "UPDATE stock SET csamt = (csamt + '$ibalance[$iid]'), units = (units + '$unitnum') WHERE stkid = '$iid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

				stockrec($cd['stkid'], $cd['stkcod'], $cd['stkdes'], 'dt', $date, $unitnum, $ibalance[$iid], "Inventory balance imported");

				db_connect();
				$cspric = sprint($ibalance[$iid]/$unitnum);
				//$cspric = sprint(0);
				$sql = "
					INSERT INTO stockrec (
						edate, stkid, stkcod, stkdes, trantype, qty, 
						csprice, csamt, details, div
					) VALUES (
						'$date', '$cd[stkid]', '$cd[stkcod]', '$cd[stkdes]', 'inc', '$unitnum', 
						'$ibalance[$iid]', '$cspric', 'Inventory balance imported', '".USER_DIV."'
					)";
				$recRslt = db_exec($sql);

				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$iid' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
				if(pg_numrows($stkRslt) < 1){
					return "<li> Invalid Stock ID.</li>";
				}else{
					$stk = pg_fetch_array($stkRslt);
				}

				if($stk['units'] <> 0){
					$sql = "UPDATE stock SET csprice = (csamt/units) WHERE stkid = '$iid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
				}else{
					$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$iid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);
				}

			}

			$tot += $ibalance[$iid];


			$i++;

		}

		if(sprint($i_tot) != sprint($tot)) {
			return enter_data2($_POST)."<li class='err'>The total amount for balances for inventory you entered is: ".CUR." $tot, the
			total for the control account is: ".sprint($sal_tot).". These need to be the same.</li>";
		}
	}

	$out = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Data Imported</th>
			</tr>
			<tr class='datacell'>
				<td>Trial balance, has been successfully imported.</td>
			</tr>
		</table>";

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	block();

	return $out;

}




function write($_POST)
{

	extract($_POST);

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Rt = db_exec($Sl);

	$i = 0;

	$odate = date("Y-m-d");

	while($fd = pg_fetch_array($Rt)) {

		//$out.="<tr class='".bg_class()."'><td>$fd[des1]</td><td>$fd[des2]</td><td>$fd[des3]</td></tr>";

		$i++;

		db_conn('cubit');

		$sql = "
			INSERT INTO customers (
				deptid, accno, surname, title, init, location, 
				fcid, currency, category, class, addr1, paddr1, 
				vatnum, contname, bustel, tel, cellno, fax, 
				email, url, traddisc, setdisc, pricelist, chrgint, 
				overdue, intrate, chrgvat, credterm, odate, credlimit, 
				blocked, balance, div, deptname, classname, catname, lead_source
			) VALUES (
				'2', '$fd[des1]', '$fd[des2]', '', '', 'loc', 
				'2', 'R', '2', '2', '$fd[des4]', '$fd[des3]', 
				'$fd[des5]', '$fd[des6]', '$fd[des7]', '', '$fd[des8]', '$fd[des9]', 
				'$fd[des10]', '$fd[des11]', '0', '0', '2', 'no', 
				'30', '0', 'yes', '0', '$odate', '0', 
				'no', '0', '".USER_DIV."', 'Ledger 1', 'General', 'General', ''
			)";
		$custRslt = db_exec ($sql) or errDie ("Unable to add customer to system.", SELF);
		if (pg_cmdtuples ($custRslt) < 1) {
			return "<li class='err'>Unable to add customer to database.</li>";
		}

		if (($cust_id = pglib_lastid("customers", "cusnum")) == 0) {
			return "<li class='err'>Unable to add customer to contact list.</li>";
		}

// 		$sql = "INSERT INTO cons (surname,ref,tell,cell,fax,email,hadd,padd,date,cust_id,con,by,div)
// 		VALUES ('$surname','Customer','$bustel','$cellno','$fax','$email','$addr','$paddr','$odate','$cust_id','No','".USER_NAME."','".USER_DIV."')";
//
// 		$rslt = db_exec($sql) or errDie("Unable to add customer to contact list", SELF);



		$Date = date("Y-m-d");

		db_conn('audit');
		$Sl = "SELECT * FROM closedprd ORDER BY id";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {

			db_conn($pd['prdnum']);

			$Sl = "
				INSERT INTO custledger (
					cusnum, contra, edate, sdate, eref, descript, 
					credit, debit, div, dbalance, cbalance
				) VALUES (
					'$cust_id', '0', '$odate', '$Date', '0', 'Balance', 
					'0', '0', '".USER_DIV."', '0','0'
				)";
			$Rj = db_exec($Sl) or errDie("Unable to insert cust balances");
		}

	}

	$out = "Done";
	return $out;

}




function safe($value)
{

	$value = str_replace("!","",$value);
	$value = str_replace("=","",$value);
	//$value = str_replace("#","",$value);
	$value = str_replace("%","",$value);
	$value = str_replace("$","",$value);
	//$value = str_replace("*","",$value);
	$value = str_replace("^","",$value);
	$value = str_replace("?","",$value);
	$value = str_replace("[","",$value);
	$value = str_replace("]","",$value);
	$value = str_replace("{","",$value);
	$value = str_replace("}","",$value);
	$value = str_replace("|","",$value);
	$value = str_replace(":","",$value);
	$value = str_replace("'","",$value);
	$value = str_replace("`","",$value);
	$value = str_replace("~","",$value);
	$value = str_replace("\\","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace(";","",$value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);
	$value = str_replace("$","",$value);
	return $value;

}




# records for CT
function crecordCT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			# $amount = ($amount * (-1));

			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance,div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		# $amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}



# records for DT
function crecordDT($amount, $cusnum,$odate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance < 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




function recordDT($amount, $supid,$edate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance > 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] >= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '-$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




# records for CT
function recordCT($amount, $supid,$edate)
{

	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM suppurch WHERE supid = '$supid' AND purid = '0' AND balance < 0 AND div = '".USER_DIV."' ORDER BY pdate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) < 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE suppurch SET balance = (balance - '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM suppurch WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount < 0){
			$amount = ($amount * (-1));

  			/* Make transaction record for age analysis */
			//$edate = date("Y-m-d");
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));

		/* Make transaction record for age analysis */
		//$edate = date("Y-m-d");
		$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$supid', '0', '$edate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM suppurch WHERE balance = 0::numeric(13,2) AND div = '".USER_DIV."'";
	$rs = db_exec($sql);

}




?>

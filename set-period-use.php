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

require ("settings.php");
require ("core-settings.php");

define("PRD_STATE_NOWARN", true);

if ($_POST) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = enter();
        }
} else {
	$OUTPUT = enter();
}

require ("template.php");



# Enter settings
function enter()
{

	if(PRD_STATE == "py"){
		$button = "Transactions to be Entered Into Current Year";
	}else{
		$button = "Transactions to be Entered Into Previous Year";
	}

	db_conn("core");

	$sql = "SELECT yrname, yrdb FROM core.year WHERE closed='y' ORDER BY yrname LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error fetching previous year information.");

	if (pg_num_rows($rslt) <= 0) {
		return "<li class='err'>You are still in your first financial year.</li>";
	}

	# Connect to db
	$enter = "
		<h3>Cubit Settings</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Year/Period Control</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='submit' value='$button'></td>
			</tr>
			<tr><td><br></td></tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $enter;

}



# confirm entered info
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	if(PRD_STATE == "py"){
		$button = "Transactions to be Entered Into Current Year";
	}else{
		$button = "Transactions to be Entered Into Previous Year";
	}

	db_conn("core");

	$sql = "SELECT yrname, yrdb FROM core.year WHERE closed='y' ORDER BY yrname LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error fetching previous year information.");

	if (pg_num_rows($rslt) <= 0) {
		return "<li class='err'>You are still in your first financial year.</li>";
	}

	$confirm = "
		<h3>Cubit Settings</h3>
		<h4>Confirm</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<tr>
				<th colspan='2'>Year/Period Control</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$button</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><li class='err'>
				<b>Accounting entries in previous periods</b><br>
					There may be some accounting difficulties at the end of a financial 
					year because at the very moment when the 'old year' expires all the 
					necessary and essential financial facts have not yet materialised. 
					For instance, it is normally only after the year end that possible 
					bad debts are considered. The issue is, as far as the actual 
					bookkeeping entry is concerned, in which year must a bad debt entry 
					be recorded, in the old year or in the new year? Another example: 
					In the new year it is discovered that a client has been incorrectly 
					invoiced in regard to a certain invoice in the old year. Must the 
					entry be recorded in the old year, that is, to rectify the customer's 
					ledger account balance at the end of the year?
				</li></td>
			</tr>
			<tr>
				<td><li class='err'>
				<b>Debtors and creditors:</b><br>
				At the end of every month a creditor normally sends his debtor a 
				statement of account, irrespective of whether the details on the 
				statement are in fact correct. In the event of errors it is customary 
				to rectify errors by means of either additional invoices or credit notes. 
				Those correcting invoices and credit notes are always issued in a following 
				period. One cannot change ones debtor's ledger account balance retrospectively 
				to the affect that it will be in disagreement with a written statement that is 
				already in the possession of ones debtor. Thus, all adjusting entries to record 
				errors in a previous year must be adjusted in the new year. In the case of material 
				amounts it would be prudent to record a nominal journal entry in a previous period. 
				EXAMPLE: We discover that customer ABS was overcharged by R100,000 on 28 February 2007, 
				our financial year end. We use CUBIT and have already closed the year at the time of 
				the discovery. Further, R100,000 is a material figure as far as we are concerned. We 
				will issue a credit note in the new year, which will adjust the debtor's account. 
				The following journal entry is made at 28 February 2007 and is then reversed on 
				1 March 2007: Sales account DR with R100000; Provision for Debtors, a new account 
				may have to be created, CR R100000. In this manner, the financial position at 
				28 February 2007 will be correct and the entry in the new year would have been 
				erased - effectively we have made the entry in the previous year without disturbing 
				the debtor's ledger account balance at 28 February 2007.

				The same procedure should be followed by a debtor who has received a 
				statement of account from his creditor.
				</li></td>
			</tr>
			<tr>
				<td><li class='err'>
					<b>Stock</b><br>
					When errors are discovered in stock records the relevant adjusting 
					bookkeeping entries to correct actual stock accounts must be made 
					in the new year. In the case of material amounts the old year stock 
					figure must be adjusted, and of course, such adjustment must be 
					reversed in the new year. Again, provision accounts may be used 
					in order to 'house' the other leg of the entry. EXAMPLE: We 
					discover in the new year that a stock item is overstated by R500000. 
					The journal entry in the old year will be: Provision for stock 
					adjustment - cost of sales, a new account may have to be created, 
					DR R500000; Provision for stock write off CR R500000. The effect 
					will be that cost of sales in the old year will be increased while 
					the stock balance will be reduced. In the new year the actual stock 
					adjustment entries will be made and the adjusting entry in the 
					previous year will be reversed. We have in effect made the 
					adjustment in the old year.
				</li></td>
			</tr>
			<tr>
				<td><li class='err'>
					<b>Warning</b><br>
					If you choose to do stock,debtors or creditors transactions and you
					have transactions in you present year, you will break your system. If
					you do not have transactions in your present financial year, your 
					system should be unaffected.
				</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right' colspan='2'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write to db
function write ($_POST)
{

	# Get vars
	extract ($_POST);

	if(PRD_STATE == "py"){
		$button = "Current";
		$state = "";
		$cur_prd_db = "";
		$prddb = "";
		$prdname = "";
		
		#update previous year ledger ....
		correct_prev_ledgers();
	} else {
		$button = "Previous";
		$state = "py";
		$cur_prd_db = YR_DB;

		db_conn("core");
		$sql = "SELECT yrname, yrdb FROM core.year WHERE closed='y' ORDER BY yrname DESC LIMIT 1";
		$rslt = db_exec($sql) or errDie("Error fetching previous year information.");

		if (pg_num_rows($rslt) <= 0) {
			return "<li class='err'>You are still in your first financial year.</li>";
		}

		$py_info = pg_fetch_array($rslt);

		$prddb = $py_info["yrdb"];
		$prdname = $py_info["yrname"];
	}

	db_conn("cubit");
	$sql = "UPDATE users SET cur_prd_db='$cur_prd_db', state='$state',
				prddb='$prddb', prdname='$prdname'
			WHERE userid='".USER_ID."'";
	db_exec($sql) or errDie ("Unable to update year/period state.");

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='400'>
			<tr>
				<th>Cubit Settings</th>
			</tr>
			<tr class='datacell'>
				<td>$button Year $prdname has been successfully activated.</td>
			</tr>
		</table>
		<p>
		<tr>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



function correct_prev_ledgers ()
{

	global $PRDMON;

	db_conn ('core');

	#we need to cycle through the actual accounts now ...
	$get_accounts = "SELECT * FROM accounts";
	$run_accounts = db_exec($get_accounts) or errDie ("Unable to get account information.");
	if(pg_numrows($run_accounts) < 1){
		return "<li class='err'>No Accounts Found In Cubit.</li>";
	}else {
		while ($carr = pg_fetch_array($run_accounts)){

			$dbal = 0;
			$cbal = 0;

			db_conn('core');

			$get_years = "SELECT * from year where closed = 'y' order by yrname";
			$run_years = db_exec($get_years) or errDie ("Unable to get closed year information.");
			if (pg_numrows($run_years) < 1){
				return "<li class='err'>No Previous Yours Found. Cannot Update Previous Year Information.</li>";
			}

			while ($yarr = pg_fetch_array ($run_years)){

				db_conn("$yarr[yrname]_audit");//$PRDMON[$iPRD]);

				for ($iPRD = 1; $iPRD <= 12; ++$iPRD) {
					$conn_date = strtolower(date("F",mktime(0,0,0,$PRDMON[$iPRD],1,substr($yarr['yrname'],1))));

					#add a possible missing column
					$col_sql = "ALTER TABLE $conn_date"."_ledger ADD actyear varchar";
					$run_col = @db_exec($col_sql);

					#check for any entries
					$get_entry = "SELECT * FROM $conn_date"."_ledger WHERE acc = '$carr[accid]'";
					$run_entry = db_exec($get_entry) or errDie ("Unable to get previous year information.");
					if(pg_numrows($run_entry) < 1){

						#no entries .... insert balances
						$ins_sql = "
							INSERT INTO $conn_date"."_ledger (
								acc, contra, edate, sdate, eref, descript, credit, debit, div, 
								caccname, ctopacc, caccnum, cbalance, dbalance, actyear
							) VALUES (
								'$carr[accid]', '$carr[accid]', 'now', 'now', '1', 'Balance', '0', '0', '".USER_DIV."', 
								'$carr[accname]', '$carr[topacc]', '$carr[accnum]', '$cbal', '$dbal', '".YR_NAME."'
							)";
						$run_ins = db_exec($ins_sql) or errDie ("Unable to record previous year balance entry.");
					}else {
						#entries exist ... recalculate balances
						
						#go through all, and recalc their bals
						while ($ent_arr = pg_fetch_array($run_entry)){
							$get_latest = "SELECT debit,credit FROM $conn_date"."_ledger WHERE id = '$ent_arr[id]' LIMIT 1";
							$run_latest = db_exec($get_latest) or errDie ("Unable to get account balance information");
							$acc_arr = pg_fetch_array ($run_latest);
							$dbal += $acc_arr['debit'];
							$cbal += $acc_arr['credit'];

							#bring some sanity to the balances ... zero one ...
							if ($cbal - $dbal < 0){
								$dbal = abs($cbal - $dbal);
								$cbal = 0;
							}else {
								$cbal = abs($cbal - $dbal);
								$dbal = 0;
							}

							#now update this entry
							$upd_sql = "UPDATE $conn_date"."_ledger SET dbalance = '$dbal', cbalance = '$cbal' WHERE id = '$ent_arr[id]'";
							$run_upd = db_exec($upd_sql) or errDie ("Unable to update previous year ledger information.");
						}
					}
				}
			}
		}
	}

}



?>

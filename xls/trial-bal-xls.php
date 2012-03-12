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
require ("../settings.php");          // Get global variables & functions
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
			case "print":
				$OUTPUT = printacc($_POST);
				break;

			case "printsave":
				$OUTPUT = print_saveacc($_POST);
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

require ("../template.php");

# Default View
function view(){

	core_connect();
	$sql = "SELECT batchid FROM batch WHERE proc = 'no' AND div = '".USER_DIV."'";
	$Rs = db_exec($sql) or errdie("Batch file unreachable.");
	if(pg_numrows($Rs) > 0){
		$sum = pg_numrows($Rs);
		$out = pg_fetch_array($Rs);
		$note = "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 class=err><li>Note : There are $sum unprocessed batch entries.</td></tr><tr><td><br></td></tr>";
	}else{
		$note = "";
	}

	$view = "
	<h3>Trial Balance</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	$note
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>List Debit & Credit</td><td valign=center>
        <input type=radio name=work value=no checked=yes>Yes | <input type=radio name=work value=Yes >No</td></tr>
        <tr><td><br></td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

function printacc($_POST)
{

		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

		// Check what is selected
		if (!isset($display)) {
			$display = "detailed";
		}

		if ($display == "detailed") {
			$selected_detailed = "checked";
		} else {
			$selected_detailed = "";
		}

		if ($display == "main") {
			$selected_main = "checked";
		} else {
			$selected_main = "";
		}

		// Set up table to display in
		$OUTPUT = "
        <center>
        <h3>Trial Balance</h3>
		<form method=post action='".SELF."'>
		<input type=hidden name=key value='print'>
		<input type=hidden name=zero value='$zero'>
		<input type=hidden name=work value='$work'>
		</form>
		<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=620>
        <tr><th>Account Number</th><th>Account Name</th><th>Debit ".CUR."</th><th>Credit ".CUR."</th></tr>";
		// Connect to database
		core_connect();
        $sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND div = '".USER_DIV."' ORDER BY topacc, accnum ASC";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$numrows = pg_numrows ($accRslt);

        if ($numrows < 1) {
			$OUTPUT = "There are no Accounts yet in Cubit.";
			require ("../template.php");
		}

		$i = 0;
		$tldebit = 0;
		$tlcredit = 0;

		if ($display == "detailed") {
			while ($acc = pg_fetch_array($accRslt)) {
				$i++;
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				if ($zero == "no") {
					if(floatval($acc['debit']) == floatval($acc['credit'])) {
						$i++;
						continue;
					}
				}
				$OUTPUT .= "<tr><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";
				if ($work == "Yes")
				{
					if ($acc['debit'] > $acc['credit']) {
						$acc['debit'] = sprint($acc['debit']-$acc['credit']);
						$acc['credit'] = 0;
					} elseif ($acc['credit'] > $acc['debit']) {
						$acc['credit'] = sprint($acc['credit']-$acc['debit']);
						$acc['debit'] = 0;
					} elseif ($acc['debit'] == $acc['credit']) {
						$acc['debit'] = 0;
						$acc['credit'] = 0;
					}
				}

				if (floatval($acc['debit'] == 0)) {
					$OUTPUT .= "<td align=right> - </td>";
				} else {
					$OUTPUT .= "<td align=right>$acc[debit]</td>";
				}

				if (floatval($acc['credit'] == 0)) {
					$OUTPUT .= "<td align=right> - </td>";
				} else {
					$OUTPUT .= "<td align=right>$acc[credit]</td>";
				}

				$OUTPUT .= "</tr>";

				$tldebit += $acc['debit'];
				$tlcredit += $acc['credit'];
			}
		}
		if ($display == "main") {
			while ($acc = pg_fetch_array($accRslt)) {
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				$mdebit = 0;
				$mcredit = 0;

				if ($acc['accnum'] == 000) {
					$i++;
					$mdebit += $acc['debit'];
					$mcredit += $acc['credit'];

					$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND topacc='$acc[topacc]' AND accnum!='000' AND div='".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to query database");

					while ($data = pg_fetch_array($rslt)) {
						if ($data['accnum'] != 000) {
							$mdebit += $data['debit'];
							$mcredit += $data['credit'];
						}
					}

					if ($zero == "no") {
						if( floatval($mdebit) == floatval($mcredit) ) {
							continue;
						}
					}
						// Retrieve the info from the db
						db_conn("core");
						$sql = "SELECT * FROM accounts WHERE topacc='$acc[topacc]' AND accnum!='000' AND div='".USER_DIV."'";
						$saccRslt = db_exec($sql) or errDie("Unable to retrieve sub accounts from Cubit.");


						if (pg_num_rows($saccRslt) != 0) {
							if (isset($expand) && $expand == $acc["topacc"]) {
								$OUTPUT .= "<tr><td><a$acc[topacc]</a></td><td>$acc[accname]";
							} else {
								$OUTPUT .= "<tr><td>$acc[topacc]</a></td><td>$acc[accname]";
							}
						} else {
							$OUTPUT .= "<tr><td>$acc[topacc]</td><td>$acc[accname]</td>";
						}

						if ($work == "Yes")
						{
							if ($mdebit > $mcredit) {
								$mdebit = sprint($mdebit-$mcredit);
								$mcredit = 0;
							} elseif ($mcredit > $mdebit) {
								$mcredit = sprint($mcredit-$mdebit);
								$mdebit = 0;
							} elseif ($mdebit == $mcredit) {
								$mdebit = 0;
								$mcredit = 0;
							}
						}

						if (floatval($mdebit == 0)) {
							$OUTPUT .= "<td align=right> - </td>";
						} else {
							$OUTPUT .= "<td align=right>$mdebit</td>";
						}

						if (floatval($mcredit == 0)) {
							$OUTPUT .= "<td align=right> - </td>";
						} else {
							$OUTPUT .= "<td align=right>$mcredit</td>";
						}
					}
					$OUTPUT .= "</tr>";

						// Retrieve the subaccounts
						if (isset($expand) && $expand == $acc["topacc"]) {
							while ($sacc = pg_fetch_array($saccRslt)) {
								db_conn("core");
								$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND accid='$sacc[accid]'";
								$saccTbRslt = db_exec($sql) or errDie("Unable to query database");
								$saccTb = pg_fetch_array($saccTbRslt);

								if (pg_num_rows($saccTbRslt) > 0) {
									$OUTPUT .= "<tr><td>$sacc[topacc]/$sacc[accnum]</td><td>$sacc[accname]</td><td align=right>$saccTb[debit]</td><td align=right>$saccTb[credit]</td></tr>";
								}
							}
						}


				$tldebit += $mdebit;
				$tlcredit += $mcredit;
			}
		}

		$tldebit = sprint($tldebit);
		$tlcredit = sprint($tlcredit);

        $OUTPUT .= "<tr><td colspan=2><b>Total</b></td><td align=right><b>$tldebit</b></td><td align=right><b>$tlcredit</b></td></tr>
		</table>";

	# Send the stream
	include("temp.xls.php");
	Stream("TrialBalance", $OUTPUT);
}
?>

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

// Merge get and post
foreach ($_GET as $key=>$val) {
	$_POST[$key] = $val;
}

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
		$note = "<tr class='bg-even'><td colspan=2 class=err><li>Note : There are $sum unprocessed batch entries.</td></tr><tr><td><br></td></tr>";
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
	<tr class='bg-odd'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr class='bg-even'><td>List Debit & Credit</td><td valign=center>
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

	$HEADER = COMP_NAME." Trial Balance as at ".date("Y-m-d");
	$SUB_HEADER = "Displaying ".ucfirst($display);
	$HEADINGS = array('accnum' => "<b>Account Number</b>",'accname' => "<b>Account Name</b>",'debit' => "<b>Debit ".CUR."</b>", 'credit' => "<b>Credit ".CUR."</b>");

	// Connect to database
	core_connect();
	$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND div = '".USER_DIV."' ORDER BY topacc, accnum ASC";
	$accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);

	if ($numrows < 1) {
		$OUTPUT = "There are no Accounts yet in Cubit.";
		require ("../template.php");
	}

	# Display all Accounts
	$i=0;
	$tldebit = 0;
	$tlcredit = 0;
	$DATA = array();
	$OUTPUT = "<table>
	<tr><td colspan=4 align=center><font size=5><b>Trial Balance</b></font></td></tr>
	<tr><td></td><td></td><td></td><td></td></tr>
	<tr><td><b><u>Account Number</u></b></td><td><b><u>Account Name</u></b></td><td><b><u>Debit</u></b></td><td><b><u>Credit</u></b></td></tr>";

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

			$DATA[$i]['accnum'] = "$acc[topacc]/$acc[accnum]";
			$DATA[$i]['accname'] = "$acc[accname]";

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
				$DATA[$i]['debit'] = " - ";
			} else {
				$DATA[$i]['debit'] = $acc['debit'];
			}

			if (floatval($acc['credit'] == 0)) {
				$DATA[$i]['credit'] = " - ";
			} else {
				$DATA[$i]['credit'] = $acc['credit'];
			}

				$tldebit += $acc['debit'];
				$tlcredit += $acc['credit'];
		}
	}
/*		if ($display == "main") {
			while ($acc = pg_fetch_array($accRslt)) {
				$i++;
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				if ($zero == "no") {
					if(floatval($acc['debit']) == floatval($acc['credit'])) {
						$i++;
						continue;
					}
				}

				$mdebit = 0;
				$mcredit = 0;

				if ($acc['accnum'] == 000) {
					$DATA[$i]['accnum'] = "$acc[topacc]";
					$DATA[$i]['accname'] = "$acc[accname]";

					$mdebit += $acc['debit'];
					$mcredit += $acc['credit'];

					$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND topacc='$acc[topacc]'";
					$rslt = db_exec($sql) or errDie("Unable to query database");

					while ($data = pg_fetch_array($rslt)) {
						if ($data['accnum'] != 000) {
							$mdebit += $data['debit'];
							$mcredit += $data['credit'];
						}
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
						$DATA[$i]['debit'] = " - ";
					} else {
						$DATA[$i]['debit'] = $mdebit;
					}

					if (floatval($mcredit == 0)) {
						$DATA[$i]['credit'] = " - ";
					} else {
						$DATA[$i]['credit'] = $mcredit;
					}
				}
				$OUTPUT .= "</tr>";

				$tldebit += $mdebit;
				$tlcredit += $mcredit;
			}
		}*/

		if ($display == "main") {
			while ($acc = pg_fetch_array($accRslt)) {
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				$mdebit = 0;
				$mcredit = 0;

				if ($acc['accnum'] == 000) {
					$i++;
					$mdebit += $acc['debit'];
					$mcredit += $acc['credit'];

					$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND topacc='$acc[topacc]' AND accnum!='000'";
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

					$DATA[$i]['accnum'] = "$acc[topacc]";
					$DATA[$i]['accname'] = "$acc[accname]";

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
						$DATA[$i]['debit'] = " - ";
					} else {
						$DATA[$i]['debit'] = $mdebit;
					}

					if (floatval($mcredit == 0)) {
						$DATA[$i]['credit'] = " - ";
					} else {
						$DATA[$i]['credit'] = $mcredit;
					}

					$tldebit += $mdebit;
					$tlcredit += $mcredit;
				}
			}
		}

	$DATA[] = array('accnum' => "  ", 'accname' => "  ", 'debit' => "  ",'credit' => "  ",);
	$DATA[] = array('accnum' => "<b>Total</b>", 'accname' => "  ", 'debit' => "<b>$tldebit</b>",'credit' => "<b>$tlcredit<b>");

	require("temp.pdf.php");
}

function stream($filename, $output){
	header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
	header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	header ( "Pragma: no-cache" );
	header ( "Content-type: application/x-pdf" );
	header ( "Content-Disposition: attachment; filename=$filename.pdf" );
	header ( "Content-Description: PHP Generated PDF Data" );
	print $output;
	exit();
}
?>

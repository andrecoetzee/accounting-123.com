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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "viewcash":
			$OUTPUT = viewcash($_POST);
			break;
                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("../template.php");


# Default view
function view()
{
        # dates
        // First Day
        $day=1;
        $fday = "<select name=fday>";
        while($day <= 31){
                $fday .="<option value='$day'>$day</option>";
                $day++;
        }
        $fday .="</select>";

        $month=1;
        $months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        $fmonth = "<select name=fmonth>";
        while($month <= 12){
                $fmonth .="<option value='$month'>$months[$month]</option>";
                $month++;
        }
        $fmonth .="</select>";

        // Last Date
        $day=1;
        $lday = "<select name=lday>";
        while($day <= 31){
                $lday .="<option value='$day'>$day</option>";
                $day++;
        }
        $lday .="</select>";

        $month=1;
        $months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        $lmonth = "<select name=lmonth>";
        while($month <= 12){
                $lmonth .="<option value='$month'>$months[$month]</option>";
                $month++;
        }
        $lmonth .="</select>";

        // main layout
        $view = "
        <h3>View Cash Book</h3>
        <h4>Select Period</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=viewcash>
        <tr><th>Field</th><th>Value</th></tr>
        <tr class='bg-odd'><td>Bank Account</td>
        <td valign=center><select name=bankid>";
        db_connect();
        $sql = "SELECT * FROM bankacct";
        $banks = db_exec($sql);
        $numrows = pg_numrows($banks);

        if(empty($numrows)){
                return "<li class=err> There are no accounts held at the selected Bank.
                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }

        while($acc = pg_fetch_array($banks)){
                $view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname]</option>";
        }

        $view .= "</select></td></tr>
                <tr class='bg-odd'><td>From :</td><td valign=center>$fday - $fmonth - <input type=text name=fyear size=4 maxlength=4 value=".date("Y")."></td></tr>
                <tr class='bg-even'><td>To :</td><td valign=center>$lday - $lmonth - <input type=text name=lyear size=4 maxlength=4 value=".date("Y")."></td></tr>
                <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='View &raquo'></td></tr>
        </table>
        <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	        <tr><th>Quick Links</th></tr>
       	        <script>document.write(getQuicklinkSpecial());</script>
	</table>



";

        return $view;
}

# view cash book
function viewcash($_POST)
{
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($fday, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($fmonth, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($fyear, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($lday, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($lmonth, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($lyear, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = sprintf("%02.2d",$fday)."-".sprintf("%02.2d",$fmonth)."-".$fyear;
	$to = sprintf("%02.2d",$lday)."-".sprintf("%02.2d",$lmonth)."-".$lyear;

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        # get bank details
        $bankRslt = get("cubit", "accname,bankname", "bankacct", "bankid", $bankid);
        $bank = pg_fetch_array($bankRslt);

        // Set up table to display in
        # Receipts
        $OUTPUT = "<center><h3>Cash Book<br><br>Account : $bank[accname] - $bank[bankname]<br>Period : $from to $to</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Date</th><th>Received From/Paid to : </th><th>Description</th><th>Deposits</th><th>Withdrawals</th><th>Ledger Account</th></tr>";

        // Connect to database
		db_Connect ();
        # date format
        $from = explode("-", $from);
        $from = $from[2]."-".$from[1]."-".$from[0];

        $to = explode("-", $to);
        $to = $to[2]."-".$to[1]."-".$to[0];

        $sql = "SELECT * FROM cashbook WHERE  date >= '$from' AND date <= '$to' AND bankid='$bankid' ORDER BY date DESC";
        $tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

        if (pg_numrows ($tranRslt) < 1) {
                $OUTPUT .= "<tr><td colspan=7 align=center><li class=err>Bank Transactions on the selected period.</td></tr>";
		}else{
                # display all bank Deposits
                for ($i=0; $tran = pg_fetch_array ($tranRslt); $i++) {

					# alternate bgcolor
					$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

					# get account name for account involved
					$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $tran['accinv']);
					$acc = pg_fetch_array($accRslt);

					# format date
					$tran['date'] = explode("-", $tran['date']);
					$tran['date'] = $tran['date'][2]."-".$tran['date'][1]."-".$tran['date'][0];

					# $rtotal += $accnt['amount']; // add to rtotal
					$OUTPUT .= "<tr bgcolor='$bgColor'><td>$tran[date]</td><td>$tran[name]</td><td>$tran[descript]</td>";

					if($tran['trantype'] == "deposit"){

							$OUTPUT .= "<td>".CUR." $tran[amount]</td><td></td>";

					}elseif($tran['trantype'] == "withdrawal"){

							$OUTPUT .= "<td></td><td>".CUR." $tran[amount]</td>";
					}

					$OUTPUT .= "<td>$acc[topacc]/$acc[accnum]  $acc[accname]</td></tr>";
                }
        }

        # Seperate the tables with two rows
        $OUTPUT .= "<tr><td colspan=7><br></td></tr><tr><td colspan=7><br></td></tr>
        </table>
        <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	        <tr><th>Quick Links</th></tr>
       	        <script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $OUTPUT;
}
?>

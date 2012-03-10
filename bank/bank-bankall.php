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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "bank":
                        $OUTPUT = bank($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = confirm($HTTP_POST_VARS);
	}
} else {
        $OUTPUT = confirm($HTTP_POST_VARS);
}


# get templete
require("../template.php");

function confirm($HTTP_POST_VARS)
{
        # Get Vars ( banked[] )
        foreach($HTTP_POST_VARS as $key => $value){
                $$key = $value;
        }

        # check if anything is selected
        if(!isset($bank)){
                return "<li class=err> Please Select at least one entry to proccess.";
        }

        $OUTPUT = "<h3>Process Multiple Entries</h3>
        <h4>Confirm Selection</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=bank>
        <tr><th>Bank Name</th><th>Account Name</th><th>Date</th><th>Reference No</th><th>Paid to/Received from<th>Description</th><th>Transaction Type</th><th>Amount</th><th>Account paid/received from</th></tr>";

		$refnum = getrefnum();
/*refnum*/
        # get ifo for each deposit
		$tot = 0;
		foreach($bank as $key => $cashid){
                 // Connect to database
                db_Connect ();
                $sql = "SELECT * FROM cashbook WHERE cashid='$cashid'";
                $cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve details from database.", SELF);
	        	$numrows = pg_numrows ($cashRslt);

                # display all bank Deposits
                for ($i=0; $i < $numrows; $i++) {
	        	$cash = pg_fetch_array ($cashRslt, $i);

		        # alternate bgcolor
		        $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

                        # Get account name for account involved
                        $accRslt = get("core", "accname", "accounts", "accid", $cash['accinv']);
                        $acc = pg_fetch_array($accRslt);

                        # get account name for bank account
                        db_connect();
                        $sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$cash[bankid]'";
                        $bankRslt = db_exec($sql);
                        $bank = pg_fetch_array($bankRslt);

                        $OUTPUT .= "<input type=hidden name=bank[] value='$cashid'><tr bgcolor='$bgColor'><td>$bank[bankname]</td><td align=center>$bank[accname]</td>
                        <td align=center><input type=text size=2 name=day[] maxlength=2 value='".date("d")."'>-<input type=text size=2 name=mon[] maxlength=2 value='".date("m")."'>-<input type=text size=4 name=year[] maxlength=4 value='".date("Y")."'></td>
                        <td align=center><input type=text size=7 name=refnum[] value='".($refnum++)."'></td><td align=center>$cash[name]</td><td align=center>$cash[descript]</td><td align=center>$cash[trantype]</td>
                        <td align=right>".CUR." $cash[amount]</td><td align=center>$acc[accname]</td></tr>";
				$tot = $cash['amount'];
				}
        }
		$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=7><b>Total Outstanding</b></td><td colspan=2><b>".CUR." $tot</b></td></tr>
        			<tr><td colspan=8><br></td><td><input type=submit value='Confirm'></td></tr></form></table>";
        return $OUTPUT;
}


# write
function bank($HTTP_POST_VARS)
{
        //processes
        db_connect();

        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($bank as $key => $value){
                $v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.");
                $v->isOk ($day[$key], "num", 1,2, "Invalid Date day.");
                $v->isOk ($mon[$key], "num", 1,2, "Invalid Date month.");
                $v->isOk ($year[$key], "num", 1,4, "Invalid Date Year.");
                $date[$key] = $day[$key]."-".$mon[$key]."-".$year[$key];

                if(!checkdate($mon[$key], $day[$key], $year[$key])){
                        $v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
                }
        }

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

        # status for each deposit
        $status = "";

        # get deposits info
		$tot = 0;
		foreach($bank as $key => $cashid){
                // Connect to database
                Db_Connect ();
                $sql = "SELECT * FROM cashbook WHERE cashid = '$cashid'";
                $cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
                if (pg_numrows ($cashRslt) < 1) {
		        	$OUTPUT = "<li clss=err>The cashbook record with reference number, <b>$cashid</b> was not found in Cubit.";
		        	return $OUTPUT;
	       		}
                $cash = pg_fetch_array($cashRslt);

                # get hook account number
                core_connect();
                $sql = "SELECT * FROM bankacc WHERE accid = '$cash[bankid]'";
                $rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
                # check if link exists
                if(pg_numrows($rslt) <1){
                        return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
                }
                $banklnk = pg_fetch_array($rslt);

                # date format
                $date[$key] = explode("-", $date[$key]);
                $date[$key] = $date[$key][2]."-".$date[$key][1]."-".$date[$key][0];

                # write the transaction
                if($cash['trantype'] == "deposit"){
                        # debit bank and credit the account involved
                        writetrans($banklnk['accnum'], $cash['accinv'], $date[$key], $refnum[$key], $cash['amount'], $cash['descript']);
                }else{
                        # credit bank and debit the account involved
                        writetrans($cash['accinv'], $banklnk['accnum'], $date[$key], $refnum[$key], $cash['amount'], $cash['descript']);
                }

                # set records as banked
                db_connect();
                $sql = "UPDATE cashbook SET banked = 'yes' WHERE cashid='$cashid'";
	        	$Rslt = db_exec ($sql) or errDie ("Unable to set bank deposit as banked in Cubit.",SELF);
                
                // status var
                $status .= "<tr class=datacell><td>Cash book entry <b>&nbsp;&nbsp;:&nbsp;&nbsp; $cash[descript]  - &nbsp;&nbsp;R $cash[amount]</b></td></tr>";
				$tot += $cash['amount'];
		}

        # status report
		$banked ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>Cash deposit/transfer record Banked</th></tr>
        $status
		<tr class=datacell2><td><b>Total amount &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : ".CUR." $tot</b></td></tr>
        </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=60%>$banked</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
                <tr><th>Quick Nevigation</th></tr>
                <tr class=datacell><td align=center><a href='cashbook-view.php'>View Cash Book</td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr class=datacell><td align=center><a href='../reporting/not-banked.php'>View Outstanding Cash Book Entries</td></tr>
                <tr class=datacell><td align=center><a href='bank-pay-add.php'>Add bank Payment</td></tr>
                <tr class=datacell><td align=center><a href='bank-recpt-add.php'>Add Bank Receipt</td></tr>
        </table>
        </td></tr></table>";

        return $OUTPUT;
}
?>

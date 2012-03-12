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


# trans-new.php :: debit-credit Transaction
#
##

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
			case "slct":
				$OUTPUT = slctacc($_POST);
				break;


			case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
				$OUTPUT = write($_POST);
				break;

			case "details":
				if(isset($_POST['details'])){
						$OUTPUT = details($_POST);
				}else{
						$OUTPUT = details2($_POST);
				}
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

$OUTPUT="";

# get templete
require("../template.php");

function view(){

	// Select previous year database
	preg_match ("/yr(\d*)/", YR_DB, $id);
	$i = $id['1'];
	$i--;
	if(intval($i) == 0){
		return "<li class=err> Error : Your are on the first year of cubit operation, there are no previous closed years";
	}
	$yrdb ="yr".$i;

	// Get prev year name
	core_connect();
	$sql = "SELECT * FROM year WHERE yrdb ='$yrdb' AND closed = 'y'";
	$rslt = db_exec($sql);
	if(pg_numrows($rslt) < 1){
		return "<li class=err> Error : Previos year was not closed.";
	}
	$yr = pg_fetch_array($rslt);

	$view = "
	<center>
	<h3>Please Select previous year period</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=30%>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=slct>
	<input type=hidden name=yrdb value='$yrdb'>
	<input type=hidden name=yrname value='$yr[yrname]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Previous Year</td><td>$yr[yrname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Select Previous Period </td><td><select name=prd>";
                db_conn($yrdb);
                $sql = "SELECT * FROM info WHERE prdname !=''";
                $prdRslt = db_exec($sql);
                $rows = pg_numrows($prdRslt);
                if(empty($rows)){
                        return "ERROR : There are no periods set for the previous year";
                }
                while($prd = pg_fetch_array($prdRslt)){
                        if($prd['prddb'] == PRD_DB){
                               $sel = "selected";
                        }else{
                                $sel = "";
                        }
                        $view .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
                }
                $view .= "
        </select></td></tr>
	</table>
	<br>
	<input type=submit value='Enter >'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# Select Accounts
function slctacc($_POST)
{
	// Sanity Checking
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "num", 1, 10, "Invalid previous Period database.");
	$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
	$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");

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
		# get prd name
		db_conn($yrdb);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);
		$prdname = $prds['prdname'];

        // connect
        db_conn($yrname."_audit");

        # get last ref number
//        $sql = "SELECT refnum FROM ".strtolower($prdname)." WHERE div = '".USER_DIV."' ORDER BY refnum DESC";
//        $Rslt = @db_exec($sql) or ret("<li class=err>Unable to select next value for reference number. Period <b>$prdname</b> was not properly close on previous year.");
//        if(pg_numrows($Rslt) > 0){
//                $ref = pg_fetch_array($Rslt, 0);
//                $refnum = $ref['refnum'] + 1;
//        }else{
//                $refnum = 1;
//        }

		$refnum = getrefnum();
/*refnum*/

        // Accounts (debit)
        $view = "<center>
        <h3> Journal transaction On Previous Periods </h3>
        <br><br>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=details>
		<input type=hidden name=yrdb value='$yrdb'>
		<input type=hidden name=yrname value='$yrname'>
		<input type=hidden name=prd value='$prd'>
		<input type=hidden name=prdname value='$prdname'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Previous Year </td><td>$yrname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Previous Period </td><td>$prdname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Previous Date</td><td><input type=text size=2 name=day maxlength=2>-<input type=text size=2 name=mon maxlength=2>-<input type=text size=4 name=year maxlength=4></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Previous Reference Number</td><td><input type=text size=10 name=refnum value='".($refnum++)."'></td></tr>
        <tr><td><br></td></tr>
        <tr><td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><td><h4>Debit</h4></td></tr>
                <tr><th>Select Account</th></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'>
                <td valign=center>
                <select name='dtaccid'>";
                core_connect();
                $sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
                $accRslt = db_exec($sql);
                if(pg_numrows($accRslt) < 1){
                        return "<li>There are No accounts in Cubit.";
                }
                while($acc = pg_fetch_array($accRslt)){
                        $view .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        $view .="</select></td></tr>
                </table>
        </td>
        <td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><td><h4>Credit</h4></td></tr>
                <tr><th>Select Account</th></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'>
                <td valign=center>
                <select name=ctaccid>";
                $sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
                $accRslt = db_exec($sql);
                if(pg_numrows($accRslt) < 1){
                        return "<li>There are No accounts in Cubit.";
                }
                while($acc = pg_fetch_array($accRslt)){
                        $view .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        $view .="</select>
                </td><td><input name=details type=submit value='Enter Details >'></td></tr>
                </table>
        </td></tr>
        </table><br><br><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><td><h4>Debit</h4></td></tr>
                        <tr><th>Account number</th></tr>
                        <tr bgcolor='".TMPL_tblDataColor2."'><td valign=center><input type=text name=dtaccnum size=20></td></tr>
                </table>
        </td>
        <td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><td><h4>Credit</h4></td></tr>
                        <tr><th>Account number</th></tr>
                        <tr bgcolor='".TMPL_tblDataColor2."'>
                <td valign=center><input type=text name=ctaccnum size=20></td><td><input type=submit value='Enter Details >'></td></tr></table>
        </td></tr>
        </table>
        <br>
        <input type=button value='< Go Back' onClick='javascript:history.back();'>
        </form>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
	<tr><td><br></td></tr>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table></center>";

return $view;
}

function ret($OUTPUT){
	require("../template.php");
}

# Enter Details of Transaction
function details($_POST)
{
	// Sanity Checking
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($prd, "num", 1, 10, "Invalid previous Period database.");
	$v->isOk ($prdname, "string", 1, 255, "Invalid previous Period name .");
	$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
	$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");
	$v->isOk ($day, "num", 1,2, "Invalid to Date day.");

		$v->isOk ($mon, "num", 1,2, "Invalid to Date month.");
        $v->isOk ($year, "num", 1,4, "Invalid to Date Year.");
        $date = $day."-".$mon."-".$year;
        if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
        }
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");

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

        // account numbers
        $dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

        // Deatils
        $details ="
        <h3> Journal transaction details</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
		<input type=hidden name=yrdb value='$yrdb'>
		<input type=hidden name=yrname value='$yrname'>
		<input type=hidden name=prd value='$prd'>
		<input type=hidden name=prdname value='$prdname'>
        <input type=hidden name='date' value='$date'>
        <input type=hidden name='ctaccid' value='$ctaccid'>
        <input type=hidden name='dtaccid' value='$dtaccid'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><td width=50%><h3>Previous Year</h3></td><td width=50%><h3>Previous Period</h3></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>$yrname</td><td>$prdname</td></tr>
		<tr><td><br></td></tr>
		<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Previous Date</td><td valign=center>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Previous Reference No.</td><td valign=center><input type=text size=20 name=refnum value='$refnum'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>".CUR."<input type=text size=20 name=amount></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
        <tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Record Transaction'></td></tr>
        </form>
        </table>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
        <tr><td>
        <br>
        </td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $details;
}

# Enter Details of Transaction
function details2($_POST)
{
        // Sanity Checking
        # Get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
		$v->isOk ($prd, "num", 1, 10, "Invalid previous Period database.");
		$v->isOk ($prdname, "string", 1, 255, "Invalid previous Period name .");
		$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
		$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");
		$v->isOk ($day, "num", 1,2, "Invalid to Date day.");
		$v->isOk ($day, "num", 1,2, "Invalid to Date day.");
        $v->isOk ($mon, "num", 1,2, "Invalid to Date month.");
        $v->isOk ($year, "num", 1,4, "Invalid to Date Year.");
        $date = $day."-".$mon."-".$year;
        if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
        }
        $v->isOk ($dtaccnum, "string", 1, 50, "Invalid Account number  to be Debited.");
        $v->isOk ($ctaccnum, "string", 1, 50, "Invalid Account number to be Credited.");

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

        $dtaccnum = explode("/", rtrim($dtaccnum));
        $ctaccnum = explode("/", rtrim($ctaccnum));

        if(count($dtaccnum) < 2){
                // account numbers
                $dtaccRs = get("core","*","accounts","topacc",$dtaccnum[0]."' AND accnum = '000");
                if(pg_numrows($dtaccRs) < 1){
                        return "<li> Accounts number : $dtaccnum[0] does not exist";
                }
                $dtacc  = pg_fetch_array($dtaccRs);
        }else{
                // account numbers
                $dtaccRs = get("core","*","accounts","topacc","$dtaccnum[0]' AND accnum = '$dtaccnum[1]");
                if(pg_numrows($dtaccRs) < 1){
                        return "<li> Accounts number : $dtaccnum[0]/$dtaccnum[1] does not exist";
                }
                $dtacc  = pg_fetch_array($dtaccRs);
        }

        if(count($ctaccnum) < 2){
                # get top level account
                $ctaccRs = get("core","*","accounts","topacc",$ctaccnum[0]."' AND accnum = '000");
                if(pg_numrows($ctaccRs) < 1){
                        return "<li> Accounts number : $ctaccnum[0] does not exist";
                }
                $ctacc  = pg_fetch_array($ctaccRs);
        }else{
                # get low level account
                $ctaccRs = get("core","*","accounts","topacc","$ctaccnum[0]' AND accnum = '$ctaccnum[1]");
                if(pg_numrows($ctaccRs) < 1){
                        return "<li> Accounts number : $ctaccnum[0]/$ctaccnum[1] does not exist";
                }
                $ctacc  = pg_fetch_array($ctaccRs);
        }

		db_conn(YR_DB);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);

        // Details
        $details ="
        <h3> Journal transaction details</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name='date' value='$date'>
		<input type=hidden name=yrdb value='$yrdb'>
		<input type=hidden name=yrname value='$yrname'>
		<input type=hidden name=prd value='$prd'>
		<input type=hidden name=prdname value='$prdname'>
        <input type=hidden name='ctaccid' value='$ctacc[accid]'>
        <input type=hidden name='dtaccid' value='$dtacc[accid]'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><td width=50%><h3>Previous Year</h3></td><td width=50%><h3>Previous Period</h3></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>$yrname</td><td>$prdname</td></tr>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Previous Date</td><td valign=center>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Previous Reference No.</td><td valign=center><input type=text size=20 name=refnum value='$refnum'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>".CUR."<input type=text size=20 name=amount></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
        <tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Record Transaction'></td></tr>
        </form>
        </table>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
        <tr><td>
        <br>
        </td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $details;
}

# Confirm
function confirm($_POST)
{
		// Sanity Checking
			# Get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
		$v->isOk ($prd, "num", 1, 10, "Invalid previous Period database.");
		$v->isOk ($prdname, "string", 1, 255, "Invalid previous Period name .");
		$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
		$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");
		$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
		$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");
        $v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

        $datea = explode("-", $date);

        if(count($datea) == 3){
                if(!checkdate($datea[1], $datea[0], $datea[2])){
                        $v->isOk ($date, "num", 1, 1, "Invalid date.");
                }
        }else{
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
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

        //processes
        db_conn(PRD_DB);
        $confirm =
        "<h3>Record Journal transaction</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>";

        $dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

		db_conn(YR_DB);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);

        $confirm .="
        <input type=hidden name='dtaccid' value='$dtaccid'>
        <input type=hidden name='ctaccid' value='$ctaccid'>
        <input type=hidden name=dtaccname value='$dtacc[accname]'>
        <input type=hidden name=ctaccname value='$ctacc[accname]'>
        <input type=hidden name=date value='$date'>
		<input type=hidden name=yrdb value='$yrdb'>
		<input type=hidden name=yrname value='$yrname'>
		<input type=hidden name=prd value='$prd'>
		<input type=hidden name=prdname value='$prdname'>
        <input type=hidden name=refnum value='$refnum'>
        <input type=hidden name=amount value='$amount'>
        <input type=hidden name=details value='$details'>
        <input type=hidden name=author value='$author'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><td width=50%><h3>Previous Year</h3></td><td width=50%><h3>Previous Period</h3></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>$yrname</td><td>$prdname</td></tr>
		<tr><td><br></td></tr>
		<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Previous Date</td><td>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Previous Reference number</td><td>$refnum</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td>".CUR." $amount</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Details</td><td>$details</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Authorising Person</td><td>$author</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm Transaction &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
        <tr><td>
        <br>
        </td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

	return $confirm;
}

# Write
function write($_POST)
{
		// Sanity Checking and get vars(Respectively)
			# Get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
        $v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($prd, "num", 1, 10, "Invalid previous Period database.");
		$v->isOk ($prdname, "string", 1, 255, "Invalid previous Period name .");
		$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
		$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");
        $v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");
        $v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

		# display errors, if any
		if ($v->isError ()) {
			$write = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$write .= "<li class=err>".$e["msg"];
			}
			$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $write;
		}

        // Accounts details
        $dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

		db_conn(YR_DB);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);

		# write transaction
		writetransyr($dtaccid,$ctaccid, $date, $yrdb, $yrname, $prd, $prdname, $refnum, $amount, $details);

		// Start layout
        $write ="
        <center>
        <h3>Journal transaction has been recorded</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><td width=50%><h3>Previous Year</h3></td><td width=50%><h3>Previous Period</h3></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>$yrname</td><td>$prdname</td></tr>
		<tr><td><br></td></tr>
		<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr colspan=2><td><h4>Amount</h4></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>".CUR." $amount</b></td></tr>
        </table>
        <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $write;
}

# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
function writetransyr($dtacc, $ctacc, $date, $yrdb, $yrname, $prd,$prdname, $refnum, $amount, $details)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
		$v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
		$v->isOk ($date, "date", 1, 14, "Invalid date.");
		$v->isOk ($prd, "num", 1, 10, "Invalid previous Period database.");
		$v->isOk ($prdname, "string", 1, 255, "Invalid previous Period name .");
		$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
		$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");
		$v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
		$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
		$v->isOk ($details, "string", 0, 255, "Invalid Details.");

		if ($v->isError ()) {
			$write = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$write .= "<li class=err>".$e["msg"];
			}
			$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			$OUTPUT =  $write;
			require("template.php");
		}

		# date format
		$date = explode("-", $date);
		$date = $date[2]."-".$date[1]."-".$date[0];

		/* ------ Previous Records -------*/
		// take inserted date
		# Insert the records into the previous period name table
		db_conn($yrname."_audit");
		$sql = "INSERT INTO $prdname(debit, credit, date, refnum, amount, author, div) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '".USER_DIV."')";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

		db_conn($yrdb);
		# begin sql transaction
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$ctbal = "UPDATE $prdname SET credit = (credit + '$amount') WHERE accid = '$ctacc' AND div = '".USER_DIV."'";
		$dtbal = "UPDATE $prdname SET debit = (debit + '$amount') WHERE accid  = '$dtacc' AND div = '".USER_DIV."'";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

		# commit sql transaction
		pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);


		/* ------ Present Records ------- */
		// take present date
		# Insert the records into the transect table
		db_conn(PRD_DB);
		$sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details, div) VALUES('$dtacc', '$ctacc', '".date("Y-m-d")."', '$refnum', '$amount', '".USER_NAME."', '$details - Auditing transaction($prdname $yrname)', '".USER_DIV."')";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);


		# Update the balances by adding appropriate values to the trial_bal Table
		core_connect();

		# begin sql transaction
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc' AND div = '".USER_DIV."'";
		$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc' AND div = '".USER_DIV."'";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

		# commit sql transaction
		pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
}
?>

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
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "viewtrans":
			$OUTPUT = viewtrans($_POST);
			break;

		default:
			$OUTPUT = view();
	}
} else {
	# Display default output
	$OUTPUT = view();
}

# get templete
require("../template.php");

# Default view
function view()
{
        //layout
        $view = "<center><h3>Journal Entries Report</h3>
        <table cellpadding=5 width = 80%><tr><td width=60%>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='370'>
                <form action='".SELF."' method=post name=form>
                <input type=hidden name=key value=viewtrans>
                <input type=hidden name=search value=date>
                <tr><th colspan=2>By Date Range</th></tr>
                <tr class='bg-odd'><td width=80% align=center><table><tr><td>
                <input type=text size=2 name=fday maxlength=2 value='01'></td><td>-</td><td><input type=text size=2 name=fmon maxlength=2  value='".date("m")."'></td><td>-</td><td><input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'></td><td>
                &nbsp;&nbsp;TO&nbsp;&nbsp;</td><td>
                <input type=text size=2 name=today maxlength=2 value='".date("d")."'></td><td>-</td><td><input type=text size=2 name=tomon maxlength=2 value='".date("m")."'></td><td>-</td><td><input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'></td></tr></table></td>
                </td><td rowspan=2 valign=bottom><input type=submit value='Search'></td></tr>
                <tr class='bg-even'><td>Select Period  <select name=prd>";
                db_conn(YR_DB);
                $sql = "SELECT * FROM info WHERE prdname !=''";
                $prdRslt = db_exec($sql);
                $rows = pg_numrows($prdRslt);
                if(empty($rows)){
                        $OUTPUT = "ERROR : There are no periods set for the current year";
						require("../template.php");
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
                </form>
                </table>
        		</td></tr>
        		<tr><td>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='370'>
                <form action='".SELF."' method=post name=form>
                <input type=hidden name=key value=viewtrans>
                <input type=hidden name=search value=refnum>
                <tr><th colspan=2>By Journal number</th></tr>
                <tr class='bg-odd'><td width=80% align=center>
                From <input type=text size=5 name=fromnum> to <input type=text size=5 name=tonum>
                </td><td rowspan=2 valign=bottom><input type=submit value='Search'></td></tr>
                <tr class='bg-even'><td>Select Period <select name=prd>";
                db_conn(YR_DB);
                $sql = "SELECT * FROM info WHERE prdname !=''";
                $prdRslt = db_exec($sql);
                $rows = pg_numrows($prdRslt);
                if(empty($rows)){
                        $OUTPUT = "ERROR : There are no periods set for the current year";
						require("../template.php");
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
						</select>
					</td>
				</tr>
			</form>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table ".TMPL_tblDflts." width='370'>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='viewtrans'>
				<input type='hidden' name='search' value='all'>
				<tr><th colspan='2'>View All</th></tr>
				<tr class='".bg_class()."'>
					<td>Select Period <select name='prd'>";
                db_conn(YR_DB);
                $sql = "SELECT * FROM info WHERE prdname !=''";
                $prdRslt = db_exec($sql);
                $rows = pg_numrows($prdRslt);
                if(empty($rows)){
                        $OUTPUT = "ERROR : There are no periods set for the current year";
						require("../template.php");
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
							</select>
						</td>
					</td>
					<td rowspan='2' valign='bottom'><input type='submit' value='View All'></td>
				</tr>
			</form>
			</table>
		</td>
		</tr></table>";

        return $view;
}

# View Categories
function viewtrans($_POST)
{
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(!isset($accid)) {
		$accid=0;
	} else {
		$accid+=0;

	}

	if($accid>0) {
		$exw=" AND (debit = '$accid' OR credit = '$accid') ";
	} else {
		$exw="";
	}

	# Search by date
	if($search == "date"){
			$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
			$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
			$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
			$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
			$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
			$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
			# mix dates
			$fromdate = $fyear."-".$fmon."-".$fday;
			$todate = $toyear."-".$tomon."-".$today;

			if(!checkdate($fmon, $fday, $fyear)){
					$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
			}
			if(!checkdate($tomon, $today, $toyear)){
					$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
			}

			# create the Search SQL
			$search = "SELECT * FROM transect WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' $exw ORDER BY refnum ASC";
	}

        # Search by refnum
	if($search == "refnum"){
			$v->isOk ($fromnum, "num", 1, 20, "Invalid 'from' ref  number.");
			$v->isOk ($tonum, "num", 1, 20, "Invalid 'to' ref  number.");

			# create the Search SQL
			$search = "SELECT * FROM transect WHERE refnum >= $fromnum AND refnum <= $tonum AND div = '".USER_DIV."' $exw ORDER BY refnum ASC";
	}

	# view all
	if($search == "all"){
			# create the Search SQL
			$search = "SELECT * FROM transect WHERE div = '".USER_DIV."' $exw ORDER BY refnum ASC";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT = $confirm;
		require("../template.php");
	}

	db_conn($prd);
	// Set up table to display in
	$OUTPUT = "
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<th colspan='7'><h3>Journal Entries Report</h3></th>
				</tr>
				<tr><th colspan='7'></th></tr>
				<tr>
					<td width='50%' align='left' colspan='4'>".COMP_NAME."</td>
					<td width='50%' align='right' colspan='4'>".date("Y-m-d")."</td>
				</tr>
				<tr>
					<th><u>Date</u></th>
					<th>System Date</th>
					<th><u>Debit</u></th>
					<th><u>Credit</u></th>
					<th><u>Ref No</u></th>
					<th><u>Amount</u></th>
					<th><u>Details</u></th>
					<th><u>Authorised By</u></th>
				</tr>";

	$tranRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		$OUTPUT = "<li> There are no Transactions in the selected Period.<br><br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		require("../template.php");
	}

	# display all transaction
	while ($tran = pg_fetch_array ($tranRslt)){
		#get vars from tran as the are in db
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

		// get account names
		$deb = get("core","accname, topacc, accnum","accounts","accid",$debit);
		$debacc = pg_fetch_array($deb);
		$ct = get("core","accname, topacc,accnum","accounts","accid",$credit);
		$ctacc = pg_fetch_array($ct);

		$OUTPUT .= "
				<tr>
					<td>$date</td>
					<td>$sdate</td>
					<td>$debacc[topacc]/$debacc[accnum]&nbsp;&nbsp;&nbsp;$debacc[accname]</td>
					<td>$ctacc[topacc]/$ctacc[accnum]&nbsp;&nbsp;&nbsp;$ctacc[accname]</td>
					<td>$refnum</td>
					<td>".CUR." $amount</td>
					<td>$details</td>
					<td>$author</td>
				</tr>";
	}
	$OUTPUT .= "</table>";

	# return $OUTPUT;
	# Send the stream
	include("temp.xls.php");
	Stream("AllTransactions", $OUTPUT);
}
?>

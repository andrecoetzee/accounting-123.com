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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "viewtrans":
			$OUTPUT = viewtrans($HTTP_POST_VARS);
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
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='460'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=viewtrans>
		<input type=hidden name=search value=date>
		<tr><th colspan=2>By Date Range</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td align=center>
				".mkDateSelect("from",date("Y"),date("m"),"01")."
				&nbsp;&nbsp; TO &nbsp;&nbsp;
				".mkDateSelect("to")."
			</td>
			<td rowspan=2 valign=bottom><input type=submit value='Search'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period  <select name=prd>";
		db_conn(YR_DB);
		$sql = "SELECT * FROM info WHERE prdname !=''";
		$prdRslt = db_exec($sql);
		$rows = pg_numrows($prdRslt);
		if(empty($rows)){
				return "ERROR : There are no periods set for the current year";
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
		<tr bgcolor='".TMPL_tblDataColor1."'><td width=80% align=center>
		From <input type=text size=5 name=fromnum> to <input type=text size=5 name=tonum>
		</td><td rowspan=2 valign=bottom><input type=submit value='Search'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period <select name=prd>";
		db_conn(YR_DB);
		$sql = "SELECT * FROM info WHERE prdname !=''";
		$prdRslt = db_exec($sql);
		$rows = pg_numrows($prdRslt);
		if(empty($rows)){
				return "ERROR : There are no periods set for the current year";
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
	</td>
	</tr>
	<tr>
	<td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='370'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=viewtrans>
		<input type=hidden name=search value=all>
		<tr><th colspan=2>View All</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period <select name=prd>";
		db_conn(YR_DB);
		$sql = "SELECT * FROM info WHERE prdname !=''";
		$prdRslt = db_exec($sql);
		$rows = pg_numrows($prdRslt);
		if(empty($rows)){
				return "ERROR : There are no periods set for the current year";
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
		</select></td></td><td rowspan=2 valign=bottom><input type=submit value='View All'></td></tr>
		</form>
		</table>
	</td>
	</tr></table>";

	return $view;
}

# View Categories
function viewtrans($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();

		# Search by date
		if($search == "date"){
			$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
			$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
			$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
			$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
			$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
			$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
			# mix dates
			$fromdate = $from_year."-".$from_month."-".$from_day;
			$todate = $to_year."-".$to_month."-".$to_day;

			if(!checkdate($from_month, $from_day, $from_year)){
					$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
			}
			if(!checkdate($to_month, $to_day, $to_year)){
					$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
			}
			$hide = "
			<input type=hidden name=prd value='$prd'>
			<input type=hidden name=search value='$search'>
			<input type=hidden name=fday value='$fday'>
			<input type=hidden name=fmon value='$fmon'>
			<input type=hidden name=fyear value='$fyear'>
			<input type=hidden name=today value='$today'>
			<input type=hidden name=tomon value='$tomon'>
			<input type=hidden name=toyear value='$toyear'>";

			# Create the Search SQL
			$search = "SELECT * FROM transect WHERE date >= '$fromdate' AND date <= '$todate' ORDER BY refnum ASC";
		}

		# Search by refnum
		if($search == "refnum"){
			$v->isOk ($fromnum, "num", 1, 20, "Invalid 'from' ref  number.");
			$v->isOk ($tonum, "num", 1, 20, "Invalid 'to' ref  number.");
			$hide = "
			<input type=hidden name=prd value='$prd'>
			<input type=hidden name=search value='$search'>
			<input type=hidden name=fromnum value='$fromnum'>
			<input type=hidden name=tonum value='$tonum'>";

			# Create the Search SQL
			$search = "SELECT * FROM transect WHERE refnum >= $fromnum AND refnum <= $tonum ORDER BY refnum ASC";
		}

		# View all
		if($search == "all"){
			$hide = "
			<input type=hidden name=prd value='$prd'>
			<input type=hidden name=search value='$search'>";

			# Create the Search SQL
			$search = "SELECT * FROM transect ORDER BY refnum ASC";
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

        // Layout
        $OUTPUT = "<center>
		<h3>Journal Entries Report</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>Date</th><th>Debit</th><th>Credit</th><th>Ref No</th><th>Amount</th><th>Details</th><th>Authorised By</th></tr>";
		 db_conn($prd);
        $tranRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
        if (pg_numrows ($tranRslt) < 1) {
			return "<li> There are no Transactions in the selected Period.<br><br>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
				<tr><th>Quick Links</th></tr>
				<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		}

		# display all transaction
        while ($tran = pg_fetch_array ($tranRslt)){
			# get vars from tran as the are in db
			foreach ($tran as $key => $value) {
				$$key = $value;
			}

			# format date
			$date = explode("-", $date);
			$date = $date[2]."-".$date[1]."-".$date[0];

			# Get account names
			$deb = undget("core","div,accname, topacc, accnum","accounts","accid",$debit);
			$debacc = pg_fetch_array($deb);
			$ct = undget("core","div,accname, topacc,accnum","accounts","accid",$credit);
			$ctacc = pg_fetch_array($ct);
			$dtbranname = branname($debacc['div']);
			$ctbranname = branname($ctacc['div']);

			$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$date</td><td>$debacc[topacc]/$debacc[accnum]&nbsp;&nbsp;&nbsp;$debacc[accname] - $dtbranname</td><td>$ctacc[topacc]/$ctacc[accnum]&nbsp;&nbsp;&nbsp;$ctacc[accname] - $ctbranname</td><td>$refnum</td><td>".CUR." $amount</td><td>$details</td><td>$author</td></tr>";
		}

        $OUTPUT .= "
		<tr><td><br></td></tr>

		<!--
		<tr><td align=center colspan=10>
			<form action='../xls/alltrans-xls.php' method=post name=form>
			<input type=hidden name=key value=viewtrans>
			$hide
			<input type=submit name=xls value='Export to spreadsheet'>
			</form>
		</td></tr>
		-->

		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $OUTPUT;
}
?>

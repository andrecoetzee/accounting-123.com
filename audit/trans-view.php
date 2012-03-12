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
		$yrname = $yr['yrname'];

        //layout
        $view = "<center><h3>Journal Entries for previous year : $yrname</h3>
        <table cellpadding=5 width = 80%><tr><td width=50%>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <form action='".SELF."' method=post name=form>
                <input type=hidden name=key value=viewtrans>
                <input type=hidden name=search value=date>
				<input type=hidden name=yrdb value='$yrdb'>
				<input type=hidden name=yrname value='$yrname'>

                <tr><th>By Date Range</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td width=80% align=center>
                <input type=text size=2 name=fday maxlength=2>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
                &nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
                <input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'>
                </td><td rowspan=2 valign=bottom><input type=submit value='Search'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period  <select name=prd>";
                db_conn($yrdb);
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
        		<td>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <form action='".SELF."' method=post name=form>
                <input type=hidden name=key value=viewtrans>
                <input type=hidden name=search value=refnum>
				<input type=hidden name=yrdb value='$yrdb'>
				<input type=hidden name=yrname value='$yrname'>

                <tr><th>By Journal number</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td width=80% align=center>
                From <input type=text size=5 name=fromnum> to <input type=text size=5 name=tonum>
                </td><td rowspan=2 valign=bottom><input type=submit value='Search'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period <select name=prd>";
                db_conn($yrdb);
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
        <td colspan=2 align=center>
                <br><br><br>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <form action='".SELF."' method=post name=form>
                <input type=hidden name=key value=viewtrans>
                <input type=hidden name=search value=all>
				<input type=hidden name=yrdb value='$yrdb'>
				<input type=hidden name=yrname value='$yrname'>

                <tr><th>View All</th></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>Select Period <select name=prd>";
                db_conn($yrdb);
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
        </tr></table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        <tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $view;
}

function ret($OUTPUT){
	require("../template.php");
}

# View Categories
function viewtrans($_POST)
{
        # get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

		# get prd name
		db_conn($yrdb);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);
		$prdname = $prds['prdname'];

		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($yrdb, "string", 1, 5, "Invalid previous year database.");
		$v->isOk ($yrname, "string", 1, 255, "Invalid previous year name.");

		# Search by date
        if($search == "date"){
                $v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
                $v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
                $v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
                $v->isOk ($today, "num", 1,2, "Invalid to Date day.");
                $v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
                $v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
                # mix dates
                $fromdate = $fday."-".$fmon."-".$fyear;
                $todate = $today."-".$tomon."-".$toyear;

                if(!checkdate($fmon, $fday, $fyear)){
                        $v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
                }
                if(!checkdate($tomon, $today, $toyear)){
                        $v->isOk ($todate, "num", 1, 1, "Invalid to date.");
                }

                # create the Search SQL
                $search = "SELECT * FROM $prdname WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY refnum ASC";
        }

        # Search by refnum
        if($search == "refnum"){
                $v->isOk ($fromnum, "num", 1, 20, "Invalid 'from' ref  number.");
                $v->isOk ($tonum, "num", 1, 20, "Invalid 'to' ref  number.");

                # create the Search SQL
                $search = "SELECT * FROM $prdname WHERE refnum >= $fromnum AND refnum <= $tonum AND div = '".USER_DIV."' ORDER BY refnum ASC";
        }

        # view all
        if($search == "all"){
                # create the Search SQL
                $search = "SELECT * FROM $prdname WHERE div = '".USER_DIV."' ORDER BY refnum ASC";
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

		# connect to audit DB
		db_conn($yrname."_audit");

        // Set up table to display in
        $OUTPUT = "<center>
		<h3>Journal Entries for $prdname in previous year : $yrname</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='80%'>
        <tr><th>Date</th><th>Debit</th><th>Credit</th><th>Reference No</th><th>Amount</th><th>Details</th><th>Authorised By</th></tr>";

        $tranRslt = @db_exec ($search) or ret ("<li class=err>Unable to retrieve data from Cubit. Period <b>$prdname</b> was not properly close on previous year.", SELF);
        if (pg_numrows ($tranRslt) < 1) {
		return "<li> There are no Transactions in the selected Period.<br><br>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        	<tr><th>Quick Links</th></tr>
	        <script>document.write(getQuicklinkSpecial());</script>
                </table>";
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

		// get account names
		$deb = get("core","accname, topacc, accnum","accounts","accid",$debit);
		$debacc = pg_fetch_array($deb);
		$ct = get("core","accname, topacc,accnum","accounts","accid",$credit);
		$ctacc = pg_fetch_array($ct);

		$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$date</td><td>$debacc[topacc]/$debacc[accnum]&nbsp;&nbsp;&nbsp;$debacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum]&nbsp;&nbsp;&nbsp;$ctacc[accname]</td><td>$refnum</td><td>".CUR." $amount</td><td>$details</td><td>$author</td></tr>";
	}

    $OUTPUT .= "</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
	<tr><td>
	<br>
	</td></tr>
	<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $OUTPUT;
}
?>

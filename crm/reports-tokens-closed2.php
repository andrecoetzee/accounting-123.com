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

require("settings.php");

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "report":
			$OUTPUT = report($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
} else {
	$OUTPUT = select_dates();
}

$OUTPUT.="<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
</table>";

require("template.php");

function select_dates() {

	$out="<h3>Closed Queries</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='report'>
	<tr><th colspan=3>Open Date</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>
		".mkDateSelect("ofrom")."
	</td><td>TO</td><td>
		".mkDateSelect("oto")."
	</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3 align=center>OR</td></tr>
	<tr><th colspan=3>Closed Date</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>
		".mkDateSelect("cfrom")."
	</td><td>TO</td><td>
		".mkDateSelect("cto")."
	</td></tr>
	<tr><td colspan=3 align=right><input type=submit value='Report &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}


function report($HTTP_POST_VARS){
	extract($HTTP_POST_VARS);

	$ofdate = $ofrom_year."-".$ofrom_month."-".$ofrom_day;
	$otdate = $oto_year."-".$oto_month."-".$oto_day;
	$cfdate = $cfrom_year."-".$cfrom_month."-".$cfrom_day;
	$ctdate = $cto_year."-".$cto_month."-".$cto_day;

	if((!checkdate($ofrom_month, $ofrom_day, $ofrom_year))or(!checkdate($oto_month, $oto_day, $oto_year))
	or(!checkdate($cfrom_month, $cfrom_day, $cfrom_year)) or(!checkdate($cto_month, $cto_day, $cto_year))){
		return "<li class=err>Invalid dates</li>".select_dates();
	}

	db_conn('crm');
	$Sl="SELECT id,tid,name,username,sub,opendate,closedate FROM closedtokens WHERE
	(((opendate>='$ofdate')AND(opendate<='$otdate')) OR ((closedate>='$cfdate')AND(closedate<='$ctdate'))) ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_numrows($Ry)<1) {
		return "There are closed queries for those dates.";
	}

	$i=0;

	$out="<h3>Closed Queries</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>No.</th><th>Subject</th><th>User</th><th>Date Opened</th><th>Date Closed</th><th>Options</th></tr>";

	while($data=pg_fetch_array($Ry)) {
		$i++;

		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$out.="<tr bgcolor='$bgcolor'><td>$data[tid]</td><td>$data[sub]</td><td>$data[username]</td>
		<td>$data[opendate]</td><td>$data[closedate]</td>
		<td><a href='tokens-closed-details.php?id=$data[id]'>View Details</a></td></tr>";

	}

	$out.="</table>";

	return $out;
}





?>

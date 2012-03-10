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
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;

		default:
			if (isset($HTTP_GET_VARS["budid"])){
				$OUTPUT = details($HTTP_GET_VARS);
			} else {
				# Display default output
				$OUTPUT = "<li class=err> - Invalid use of module.";
			}
	}
} else {
	if (isset($HTTP_GET_VARS["budid"])){
		$OUTPUT = details($HTTP_GET_VARS);
	} else {
		# Display default output
		$OUTPUT = "<li class=err> - Invalid use of module.";
	}
}

# get templete
require("../template.php");

# Enter Details of Transaction
function details($HTTP_POST_VARS, $errata = "<br>")
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	require("budget.lib.php");
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vbudtype = $TYPES[$bud['budtype']];
	$vfromyr = $YEARS[$bud['fromprd']];
	$vtoyr = $YEARS[$bud['toprd']];

	/* Toggle Options */
	$list = "";

	db_connect();
	# budget for
	if($bud['budfor'] == 'cost'){
		$head = "<tr><th>Cost Centers</th>";
	
		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
		
		while($bit = pg_fetch_array($bitRslt)){
			$ccRs = get("cubit", "*", "costcenters", "ccid", $bit['id']);
			$cc  = pg_fetch_array($ccRs);
			
			$list .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$cc[centercode] - $cc[centername]</td>";
			
			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			while($lst = pg_fetch_array($lstRs)){
				$list .= "<td align=right>".CUR." $lst[amt]</td>";
			}
			$list .= "</tr>";
		}
	}elseif($bud['budfor'] == 'acc'){
		$head = "<tr><th>Accounts</th>";
		
		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$accRs = get("core", "*", "accounts", "accid", $bit['id']);
			$acc  = pg_fetch_array($accRs);
			$list .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";
			
			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			while($lst = pg_fetch_array($lstRs)){
				$list .= "<td align=right>".CUR." $lst[amt]</td>";
			}
			$list .= "</tr>";
		}
	}

	# Budget headings
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}	
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i < 10; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
		for($i = 0; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$YEARS[$i]</th>";
	}
	$head .= "</tr>";

	/* End Toggle Options */

	$details = "<center>
	<h3> Remove Yearly Budget </h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=budid value='$budid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr><tr bgcolor='".TMPL_tblDataColor1."'><td>Budget Name</td><td>$bud[budname]</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget For</td><td>$vbudfor</td>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Budget Type</td><td>$vbudtype</td>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Budget Year</td><td>$vfromyr to $vtoyr</td>
	<tr><td colspan=2>$errata</td></tr>
	
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	$head
	$list
	</table>

	<tr><td><br></td></tr>
	<tr><td><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Remove &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $details;
}

# Write
function write($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($budid, "num", 1, 20, "Invalid Budget id.");
	
	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return details($HTTP_POST_VARS, $confirm);
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	require("budget.lib.php");
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vbudtype = $TYPES[$bud['budtype']];
	$vfromyr = $YEARS[$bud['fromprd']];
	$vtoyr = $YEARS[$bud['toprd']];
	
	db_connect();
	# delete budget
	$rs = db_exec("DELETE FROM buditems WHERE budid = '$budid'");
	$rs = db_exec("DELETE FROM budgets WHERE budid = '$budid'");
	
	// Start layout
	$write = "<center>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
		<tr><th colspan=2>Remove Yearly Budget</th></tr>
		<tr><td bgcolor='".TMPL_tblDataColor1."' colspan=2>Yearly Budget <b>$bud[budname]</b> has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $write;
}
?>

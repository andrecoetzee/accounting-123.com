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

# Display default output
$OUTPUT = view();

$OUTPUT .= "

<br>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
<tr><th>Quick Links</th></tr>
<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
<script>document.write(getQuicklinkSpecial());</script>
</table>";

# get template
require("../template.php");

# Default view
function view()
{
//layout
return "<center><h3>All Categories</h3>
<!--
<form action='../xls/allcat-xls.php' method=post name=form>
<input type=hidden name=key value=view>
<input type=hidden name=amt value=' '>
<input type=submit name=xls value='Export to spreadsheet'>
</form>
-->
".viewCat("B").viewCat("I").viewCat("E")."
<p>
<!--
<form action='../xls/allcat-xls.php' method=post name=form>
<input type=hidden name=key value=view>
<input type=hidden name=amt value=' '>
<input type=submit name=xls value='Export to spreadsheet'>
</form>
-->";
}

# View Categories
function viewCat($type)
{
	// get table name
	core_connect();
	switch($type){
		case "I":
			$tab = "Income";
			$name= "Income";
			break;
		case "E":
			$tab = "Expenditure";
			$name= "Expenditure";
			break;
		case "B":
			$tab = "Balance";
			$name= "Balance";
			break;
		default:
			return "<li>Invalid Category type";
	}

	// Set up table to display in
	$view = "<p><h3>$name</h3>";

	// get categories
	$sql = "SELECT * FROM $tab ORDER BY div ASC";
	$catRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account category details from database.", SELF);
	$numrows = pg_numrows ($catRslt);


	if ($numrows < 1) {
	$view = "<br><h3>$name</h3>
			<li>There are no Categories under <b>$tab</b>.<br><p><p>";
		return $view;
	}

	# display all categories
	for ($i=0; $i < $numrows; $i++) {
			$cat = pg_fetch_array ($catRslt, $i);

			#get vars from acc as the are in db
			foreach ($cat as $key => $value) {
				$$key = $value;
			}

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$branname = branname($div);
		$view .= viewacc($type,$catid,$catname, $branname);
	}

	return $view;
}

function viewacc($type,$catid,$catname, $branname)
{
        // Connect to DB
        core_connect();


        // Set up table to display in
        $OUTPUT = "
        <center>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=70%>
        <tr><td><br></td><td></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><b>Category : $catid - $catname - $branname<b></td></tr>
        <!-- bgcolor='".TMPL_tblDataColor2."' <tr><td colspan=2><br></td></tr> -->";

		// get accounts
        $type = strtoupper($type);
        $sql = "SELECT * FROM accounts WHERE acctype='$type' AND catid='$catid' ORDER BY topacc,accnum ASC";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$numrows = pg_numrows ($accRslt);


        if ($numrows < 1) {
			$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2>There are no Accounts in this Category.</td></tr></table>";
			return $OUTPUT;
		}

		# display all invoices
        for ($i=0; $i < $numrows; $i++) {
			$acc = pg_fetch_array ($accRslt, $i);

			#get vars from acc as the are in db
			foreach ($acc as $key => $value) {
				$$key = $value;
			}

		# alternate bgcolor
		$bgColor = TMPL_tblDataColor2;
		$OUTPUT .= "<tr bgcolor='$bgColor'><td width='10%'>$topacc/$accnum</td><td>$accname</td></tr>\n";
	}
	$OUTPUT .= "</table>\n";

	return $OUTPUT;
}
?>

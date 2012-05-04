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

# allcat.php :: view accounts categories and their accounts
##

# get settings
require("../settings.php");
require("../core-settings.php");

# Display default output
$OUTPUT = view();

$OUTPUT .= "<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
</table>";

# get template
require("../template.php");

# Default view
function view(){
	//layout
	$OUTPUT = "<table>
	<tr><th colspan=2><h3>All Categories</h3></th></tr>
	".viewCat("B").viewCat("I").viewCat("E")."
	</table>";

	# Send the stream
	include("temp.xls.php");
	Stream("AllCatAndAccounts", $OUTPUT);
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

	# Set up table to display in
	$view = "<tr><th colspan=2></th></tr>
	<tr><th colspan=2><h3>$name</h3></th></tr>";

	# get categories
	$sql = "SELECT * FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid ASC";
	$catRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account category details from database.", SELF);
	$numrows = pg_numrows ($catRslt);


	if ($numrows < 1) {
		$view = "<tr><th colspan=2><li>There are no Categories under <b>$tab</b></th></tr>";
		return $view;
	}

	# display all categories
	for ($i=0; $i < $numrows; $i++) {
		$cat = pg_fetch_array ($catRslt, $i);
		#get vars from acc as the are in db
		foreach ($cat as $key => $value) {
			$$key = $value;
		}

		$view .= viewacc($type,$catid,$catname);
	}
	return $view;
}

function viewacc($type,$catid,$catname)
{
	# Connect to DB
	core_connect();

	# Set up table to display in
	$OUTPUT = "
	<tr><td colspan=2></td></tr>
	<tr><td colspan=2><b>Category : $catid - $catname<b></td></tr>";

	#  get accounts
	$type = strtoupper($type);
	$sql = "SELECT * FROM accounts WHERE acctype='$type' AND catid='$catid' AND div = '".USER_DIV."' ORDER BY topacc,accnum ASC";
	$accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);


	if ($numrows < 1) {
		$OUTPUT .= "<tr><td colspan=2>There are no Accounts in this Category.</td></tr></table>";
		return $OUTPUT;
	}

	# display all invoices
	for ($i=0; $i < $numrows; $i++) {
		$acc = pg_fetch_array ($accRslt, $i);
		#get vars from acc as the are in db
		foreach ($acc as $key => $value) {
			$$key = $value;
		}

		$OUTPUT .= "<tr><td>$topacc/$accnum</td><td>$accname</td></tr>";
	}

	return $OUTPUT;
}
?>

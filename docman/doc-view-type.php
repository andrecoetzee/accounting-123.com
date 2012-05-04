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
require ("../settings.php");
require ("../core-settings.php");
require_lib("docman");


if(isset($_GET['type']) && isset($_GET['xin'])){
	# show current stock
	$OUTPUT = printDocs ($_GET);
}else{
	$OUTPUT = "<li class=err> - Invalid use of module.";
}

require ("../template.php");

# show stock
function printDocs ($_GET)
{
	extract($_GET);

	# Set up table to display in
	$printDocs = "<center><h3>Documents</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Type</th><th>Ref</th><th>Document</th><th>Date</th><th>Description</th><th>Filename</th></tr>";

	# Connect to database
	db_conn ("yr2");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM documents WHERE typeid = '$type' AND xin = '$xin' AND div = '".USER_DIV."' ORDER BY docname ASC";
    $docRslt = db_exec ($sql) or errDie ("Unable to retrieve Documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		$printDocs .= "<tr class='bg-odd'><td colspan=10>There are no Documents found.</td></tr>";
	}else{
		while($doc = pg_fetch_array ($docRslt)) {
			$printDocs .= "<tr class='".bg_class()."'><td>$doc[typename]</td><td>$doc[docref]</td><td>$doc[docname]</td><td>$doc[docdate]</td><td>$doc[descrip]</td><td>$doc[filename]</td><td><a href='doc-edit.php?docid=$doc[docid]'>Edit</a></td>";
			$printDocs .= "<td><a href='doc-dload.php?docid=$doc[docid]'>Download</a></td><td><a href='doc-rem.php?docid=$doc[docid]'>Remove</a></td></tr>";
			$i++;
		}
	}
	$printDocs .= "</table><p><p>
	<input type=button value=' [X] Close ' onClick='javascript:window.close();'>";

	return $printDocs;
}
?>

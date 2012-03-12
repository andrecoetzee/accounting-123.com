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

# Show current stock
$OUTPUT = printDload($_GET['docid']);

require ("../template.php");

# show stock
function printDload ($docid)
{
	# Connect to database
	db_conn ("cubit");
	//db_conn (YR_DB);

	# Query server
	$i = 0;
    $sql = "SELECT * FROM documents WHERE docid = '$docid' AND div = '".USER_DIV."'";
    $docRslt = db_exec ($sql) or errDie ("Unable to retrieve Documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		return "<li>Document not found";
	}
	$doc = pg_fetch_array ($docRslt);

	$output = doclib_decode($doc['docu']);

	stream($doc['filename'], $output, $doc['mimetype']);
}
?>

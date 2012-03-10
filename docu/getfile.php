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

if (!isset($_REQUEST["id"]) || !is_numeric($_REQUEST["id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("../template.php");
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "doc":
			$OUTPUT = doc();
			break;
	}
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

function stream($filename, $output, $mime){
	header ( "Expires: Mon, 28 Aug 1984 05:00:00 GMT" );
	header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	header ( "Pragma: no-cache" );
	header ( "Content-type: $mime" );
	header ( "Content-Disposition: attachment; filename=$filename" );
	print $output;
	exit();
}

function doc()
{
	extract ($_REQUEST);

	// Retrieve file
	db_conn("cubit");
	$sql = "SELECT * FROM document_files WHERE id='$id'";
	$jf_rslt = db_exec($sql) or errDie("Unable to retrieve jobcard files.");
	$jf_data = pg_fetch_array($jf_rslt);

	stream($jf_data["filename"], base64_decode($jf_data["file"]), $jf_data["type"]);
}
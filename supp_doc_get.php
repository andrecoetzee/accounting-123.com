<?php

require_once ("settings.php");
require("libs/docman.lib.php");
dload($_REQUEST["id"]);




function dload($id)
{

	extract ($_REQUEST);

	# Query server
	$i = 0;
	if (isset($tmp)) {
		$sql = "SELECT * FROM crm.stmp_docs WHERE id='$id'";
	} else {
    	$sql = "SELECT * FROM crm.supplier_docs WHERE id='$id'";
    }
    $docRslt = db_exec ($sql) or errDie ("Unable to retrieve Documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		return "<li>Document not found</li>";
	}
	$doc = pg_fetch_array ($docRslt);

	$output = doclib_decode($doc['file']);

	StreamDOC($doc['real_filename'], $output, $doc['type']);

}

?>
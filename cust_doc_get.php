<?php

require_once ("settings.php");
require("libs/docman.lib.php");
dload($_REQUEST["id"]);




function dload($id)
{

	extract ($_REQUEST);

	if(isset($table) AND (strlen($table) > 0)){
		#use table
	}else {
		$table = "ctmp_docs";
	}

	# Query server
	$i = 0;
	if (isset($tmp)) {
		$sql = "SELECT * FROM crm.$table WHERE id='$id'";
	} else {
    	$sql = "SELECT * FROM crm.customer_docs WHERE id='$id'";
    }
    $docRslt = db_exec ($sql) or errDie ("Unable to retrieve Documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		return "<li>Document not found</li>";
	}
	$doc = pg_fetch_array ($docRslt);

	$output = doclib_decode($doc['file']);

	if (strlen($doc['real_filename']) < 1)
		$doc['real_filename'] = "file";

	StreamDOC($doc['real_filename'], $output, $doc['type']);

}



?>
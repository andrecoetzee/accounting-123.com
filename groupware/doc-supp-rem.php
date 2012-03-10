<?

require ("../settings.php");

if (isset($HTTP_POST_VARS["key"])){
	$OUTPUT = write_remove ();
}else {
	$OUTPUT = confirm_remove ();
}

require ("gw-tmpl.php");


function confirm_remove ()
{

	extract ($_GET);

	if (!isset($docid))
		return "Invalid Use Of Module.";

	db_conn ('crm');

	$get_supp_doc = "SELECT * FROM supplier_docs WHERE id = '$docid' LIMIT 1";
	$run_supp_doc = db_exec($get_supp_doc) or errDie ("Unable to get supplier document information.");
	if (pg_numrows($run_supp_doc) > 0){
		$darr = pg_fetch_array ($run_supp_doc);
	}else {
		return "Invalid Use Of Module. Selected Document Not Found.";
	}

	$display = "
		<h4>Confirm Removal Of Supplier Document</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='docid' value='$docid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>Document Description</td>
			</tr>
			<tr>
				<td>$darr[filename]</td>
			</tr>
			<tr>
				<td>Document File Name</td>
			</tr>
			<tr>
				<td>$darr[real_filename]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Remove'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function write_remove ()
{

	extract ($_POST);

	db_conn ('crm');

	#remove the document
	$rem_sql = "DELETE FROM supplier_docs WHERE id = '$docid'";
	$run_rem = db_exec($rem_sql) or errDie ("Unable to remove supplier document.");

	header ("Location: document_view.php");
	
}


?>
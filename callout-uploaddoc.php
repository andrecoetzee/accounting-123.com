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

require ("settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		default:
			$OUTPUT = get_doc($_GET);
			break;
	}
} else {
        $OUTPUT = get_doc($_GET);
}

require ("template.php");

function get_doc($_GET, $err="") {
	extract ($_GET);

	if (!isset($calloutid) OR (strlen($calloutid) < 1)) {
		return "Invalid use of module";
	}

	$display = "
	<h3>Upload A Scanned Document<h3>
	$err
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='post' name='form' enctype='multipart/form-data'>
		<input type='hidden' name='key' value='confirm'>
		<input type='hidden' name='calloutid' value='$calloutid'>
		<tr>
			<th>Select Document</th>
		</tr>
		<tr>
			<td><input type='file' name='uploaddoc'></td>
		</tr>
		<tr><td><br></td></tr>
		<tr>
			<td><input type='submit' value='Upload'></td>
		</tr>
	</form>
	</table>";

	return $display;
}


function confirm() {
	extract ($_POST);

	if(!isset($calloutid) OR (strlen($calloutid) < 1)){
		return "Invalid use of module";
	}

	if (is_uploaded_file ($_FILES["uploaddoc"]["tmp_name"])) {
		$type = $_FILES["uploaddoc"]["type"];

		// open file in "read, binary" mode
		$img = "";
		$file = fopen($_FILES['uploaddoc']['tmp_name'], "rb");
		while (!feof ($file)) {
			// fread is binary safe
			$img .= fread ($file, 1024);
		}
		fclose ($file);
		# base 64 encoding
		$img = base64_encode($img);

		db_connect();

		#write this doc to Cubit here ...

		#remove any current entries
		$rem_sql = "DELETE FROM callout_docs_scanned WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
			$run_rem = db_exec($rem_sql) or errDie("Unable to remove current scanned entry");

		#add the new one ...
		$write_sql = "INSERT INTO callout_docs_scanned (calloutid,image,image_type,div) VALUES ('$calloutid','$img','$type','".USER_DIV."')";
		$run_write = db_exec($write_sql) or errDie("Unable to add new call out document scanned image");

		header ("Location: callout-view.php");;
	}else {
		return get_doc($_POST,"<li class='err'>Please select a document to upload</li><br>");
	}

}


?>
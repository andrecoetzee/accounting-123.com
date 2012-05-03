<?

require ("settings.php");

if (isset($_POST["key"])){
	$OUTPUT = import_file ($_POST);
}else {
	$OUTPUT = get_file();
}

require ("template.php");




function get_file ($err="")
{

	$display = "
		<h3>Import Email Addresses To Email Marketing Queue</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='import'>
			$err
			<tr>
				<th>Enter Name Of Queue</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' size='40' name='upload_name'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Select File To Upload</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='file' name='upload_file'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Format: 1 email per line</th>
			</tr>
			<tr>
				<th>Eg. </th>
			</tr>
			<tr class='".bg_class()."'>
				<td>test@test.com<br>
				test2@test.com<br>
				test3@test.com</li></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Import'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function import_file ($_POST)
{

	$errorlist = "";
	$counter = 0;
	$newarr = array ();

	extract ($_POST);

	#check if we have a queue name ...
	if (!isset($upload_name) OR strlen($upload_name) < 1)
		return get_file ("<li class='err'>Please enter a valid email queue name.</li><br>");

	pglib_transaction("BEGIN") or errDie ("Unable to start database transaction.");

	#add the group
	$sql1 = "INSERT INTO egroups (grouptitle,groupname) VALUES ('".strtolower($upload_name)."','$upload_name')";
	$run_sql1 = db_exec($sql1) or errDie ("Unable to record new email group information.");

	$file = file($_FILES["upload_file"]["tmp_name"]);

	foreach ($file AS $each){
		$each = trim($each);
		$each = strtolower($each);
		$each = str_replace("'","",$each);
		$each = str_replace("\"","",$each);
		$each = str_replace("\\","",$each);
		$each = str_replace("&","",$each);
		$each = str_replace("#","",$each);
		$each = str_replace("$","",$each);
		$each = str_replace("%","",$each);
		$each = str_replace("^","",$each);
		$each = str_replace("*","",$each);
		$each = str_replace("(","",$each);
		$each = str_replace(")","",$each);
		$each = str_replace("[","",$each);
		$each = str_replace("]","",$each);
		$each = str_replace("(","",$each);
		$each = str_replace("}","",$each);
		$each = str_replace("{","",$each);
		$each = str_replace(")","",$each);
		$each = str_replace(";","",$each);
		$each = str_replace(":","",$each);
		$each = str_replace("~","",$each);
		$each = str_replace("`","",$each);
		$each = str_replace("|","",$each);
		$each = str_replace("/","",$each);
		$each = str_replace("<","",$each);
		$each = str_replace(">","",$each);
		$each = str_replace("?","",$each);
		$each = str_replace("!","",$each);
		$each = str_replace("=","",$each);
		$each = str_replace("+","",$each);
		$each = str_replace(",","",$each);
		$newarr[] = $each;
	}

	$newarr = array_unique ($newarr);

	foreach ($newarr AS $key => $line){
		$line = trim ($line);
		if (strpos($line,"@") != strrpos($line,"@")){
			#2 @'s ???
			$errorlist .= "<li class='err'>Error on line ".(int)($key+1).": ($line)</li>";
			continue;
		}

		#check if we have a @
		if (strpos ($line,"@") === false){
			#not found ...
			$errorlist .= "<li class='err'>Error on line ".(int)($key+1).": ($line)</li>";
		}else {
			$sql2 = "INSERT INTO email_groups (email_group,emailaddress,date_added) VALUES ('".strtolower($upload_name)."','$line','now')";
			$run_sql2 = db_exec($sql2) or errDie ("Unable to record new email address in email group");

			$counter++;
		}
	}

	pglib_transaction ("COMMIT") or errDie ("Unable to commit database transaction.");


	return get_file("<li class='err'>$counter Email Addresses Have Been Imported.</li>$errorlist<br>");
//	$display = "
//		<table ".TMPL_tblDflts.">
//			<tr>
//				<td>$counter Email Addresses Have Been Imported.</td>
//			</tr>
//		</table>";
//	return $display;

}

?>
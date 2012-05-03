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

define("CUBIT_IMPORTCOMP", true);
require ("newsettings.php");
require ("psql_path.php");
require_lib("validate");

if (isset($_FILES["compfile"])) {
	$OUTPUT = importFile ();
} else {
	$OUTPUT = selectFile();
}

require ("newtemplate.php");




function selectFile ()
{

	global $_POST;

	$newcomp = "";
	if (!isset($_SESSION["USER_NAME"]) ) {
		$newcomp .= "
			<h3>Browser Notice</h3>
			<b>Cubit requires Firefox 1.5 or later. Click
			<a class='nav' href='MozillaInstall/firefox.exe'>here</a> to install it.</b>";

		db_conn('cubit');
		$rslt = db_exec("SELECT * FROM companies WHERE status='active'");
		if(pg_numrows($rslt) > 0)
			header("Location: complogin.php");
	}

	if ( ! isset($_POST["compname"]) )
		$_POST["compname"] = "";

	$OUTPUT = "
		<h3>Import Company</h3>
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Options</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name of Company:</td>
				<td><input type='text' name='compname' value='$_POST[compname]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Company File:</td>
				<td><input type='file' name='compfile'></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Import'></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}




function importFile()
{


	global $_FILES, $_POST, $psql_exec;
	extract($_POST);

	$OUTPUT = "<h3>Import Company</h3>";

	$v = & new Validate();
	if ( ! $v->isOk($compname, "string", 1, 250, "") )
		return "<li class='err'>Invalid Company Name</li>".selectFile();

	// generate code
	$code = "aaaa";

	// make sure it 4 chars long by padding with a's
	$code = str_replace(" ", "", $code);
	$code = str_pad($code, 4, 'a', STR_PAD_RIGHT);

	while ( 1 ) {
		// check if the code exists
		db_con("cubit");
		$rslt = db_exec("SELECT * FROM companies WHERE code='$code'");

		// not exist! YAY!!
		if (pg_numrows($rslt) < 1 && !exists_compdb($code)) {
			break;
		}

		// increase
		$code[3] = chr( ord($code[3]) + 1 );
		for ( $i = 3; $i >= 0; $i-- ) {
			if ( ord($code[$i]) > ord('z') ) {
				$code[$i] = 'a';
				if ( $i > 0 )
					$code[$i-1] = chr( ord($code[$i-1]) + 1 );
				if ( substr($code, 0, 3) == "zzz")
					$code = "aaaa";
			}
		}
	}

	require_lib("progress");
	displayProgress("newtemplate.php");

	# Change code to lowercase
	$code = strtolower($code);

	// parse the import file
	if (PLATFORM == "windows") {
		$importfile = cfs::tempnam("cubitimport_");
	} else {
		$importfile = cfs::tempnam("cubitimport_");
	}

	if (!ucfs::valid("compfile")) {
		return "<li class='err'>".ucfs::ferror("compfile")."</li>";
	}

	$fd_in = ucfs::fopen("compfile", "r");
	$fd_out = cfs::fopen($importfile, "w", true);

	if ($fd_in === false) {
		return "<li class='err'>Unable to open import file.</li>";
	}

	if ($fd_out === false) {
		return "<li class='err'>Unable to open temporary file required to import company.</li>";
	}

	$company_ver = "";
	while (! cfs::feof($fd_in)) {
		$buf = cfs::fgets($fd_in, 4096);

		// get the version of imported company if on this line
		$pos = strpos($buf, "-- V'e'r's'i'o'n:");
		if ( $pos !== false && $pos == 0 ) {
			$company_ver = trim(substr($buf, 17));
		}

		// check if it valid platform
		$pos = strpos($buf, "-- P'l'a't'f'o'r'm:");
		if ( $pos !== false && $pos == 0 ) {
			$comp_platform = trim(substr($buf, 19));
			if ( PLATFORM != $comp_platform ) {
				$OUTPUT .= "You cannot import another platform's company!<br>
					Only from Windows to Windows or Linux to Linux.<br><Br>
					Your platform: ".PLATFORM."<Br>
					Proposed Imported Company Platform: $comp_platform<br>";
				return $OUTPUT;
			}
		}

		// parse the create database code variable if on this line
		$pos = strpos($buf, "CREATE DATABASE");
		if ( ($pos !== false) && (strpos($buf,"%c'o'd'e%") > 0) ) {
			$buf = str_replace("%c'o'd'e%", $code, $buf);
		}

		// parse the company code variable if on this line
		$pos = strpos($buf, "\\c ");
		if ( ($pos !== false) && (strpos($buf,"%c'o'd'e%") > 0) ) {
			$buf = str_replace("%c'o'd'e%", $code, $buf);
		}

		fputs($fd_out, $buf);
	}

	fclose($fd_in);
	fclose($fd_out);

	// get the version of current cubit
	db_con("cubit");
	$cubit_ver = CUBIT_VERSION;

	// check if versions are the same (TEMPORARY HACK, ai tog)
	if ( $cubit_ver != $company_ver ) {
		return $OUTPUT . "Versions do not match:<br>
			Proposed Imported Company version is \"$company_ver\"<br>
			Your Cubit version is \"$cubit_ver\"<br>
			<br>
			Check for updates for your Cubit to support the functionality of importing non matching versions.";
	}

        // import
	exec("$psql_exec/".PSQL_EXE." -U postgres template1 < $importfile");

	// insert the company
	db_con("cubit");
	db_exec("INSERT INTO companies (code,name,ver,status) VALUES('$code', '$compname', '$company_ver', 'active')");

	// if only one company in list, we can safely assume this was the first company
	// and forward to the login screen
	$sql = "SELECT * FROM companies";
	$rslt = db_exec($sql);

	if (!isset($_SESSION["USER_ID"]) && $rslt && pg_num_rows($rslt) > 0) {
		$_SESSION["code"] = $code;
		$_SESSION["comp"] = $compname;

		$OUTPUT = "<script>top.document.location.href='doc-index.php';</script>";
		return $OUTPUT;


		header("Location: main.php");
		exit;
	}

	$OUTPUT .= "
	Company has been imported successfully.<br>
	Company Name: $compname<br>
	Company Code: $code<br>";
	return $OUTPUT;

}



?>
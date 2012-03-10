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

# get settings
require ("../settings.php");
require ("../core-settings.php");
require_lib("docman");

# Decide what to do
if (isset ($HTTP_POST_VARS["key"])) {

	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			if(!isset($HTTP_POST_VARS["conf"])){
				$OUTPUT = enter ($HTTP_POST_VARS);
			}else{
				$OUTPUT = confirm ($HTTP_POST_VARS);
			}
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# Display output
require ("../template.php");

# Enter new data
function enter ($VARS = array(), $errors = "")
{
	# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($VARS as $key => $value) {
		$$key = $value;
	}
	if(!isset($typeid)){
		$xin = "";
		$xins = $xin;
		$typeid = "";
		$docref = "";
		$docname = "";
		$day = date("d");
		$mon = date("m");
		$year = date("Y");
		$descrip = "";
	}else{
  		$xin = (isset($xin)) ? $xin : "";
		$xins = $xin;
		$xin = xin($typeid, $xin);
 	}

	# Select Type
	db_conn("yr2");
	$typs= "<select name='typeid' onchange='document.form1.submit();'>";
	# User types
	$sql = "SELECT * FROM doctypes WHERE div = '".USER_DIV."' ORDER BY typename ASC";
	$typRslt = db_exec($sql);
	if(pg_numrows($typRslt) < 1){
		if(strlen($typeid) < 1)
			$typeid = "inv";
		$xin = xin($typeid, $xins);
	}else{
		while($typ = pg_fetch_array($typRslt)){
			$sel = "";
			if($typ['typeid'] == $typeid)
				$sel = "selected";
			$typs .= "<option value='$typ[typeid]' $sel>($typ[typeref]) $typ[typename]</option>";
		}
	}
	# Built-in types
	foreach($DOCLIB_DOCTYPES as $tkey => $val){
		$sel = "";
		if($tkey == $typeid)
			$sel = "selected";
		$typs .= "<option value='$tkey' $sel>$DOCLIB_DOCTYPES[$tkey]</option>";
	}
	$typs .="</select>";

	$enter =
	"<h3>Add Document</h3>
	<form name=form1 ENCTYPE='multipart/form-data' action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr><td colspan=2>$errors</td></tr>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Type</td><td>$typs</td></tr>
	$xin
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Document Name</td><td><input type=text size=20 name=docname value='$docname'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Ref</td><td><input type=text size=10 name=docref value='$docref'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td><input type=text size=2 name=day maxlength=2  value='$day'>-<input type=text size=2 name=mon maxlength=2  value='$mon'>-<input type=text size=4 name=year maxlength=4 value='$year'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>File</td><td><input type=file size=20 name=doc></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Decription</td><td><textarea name=descrip rows=4 cols=18>$descrip</textarea></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit name=conf value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# Confirm new data
function confirm ($HTTP_POST_VARS)
{
	# Get vars
	global $HTTP_POST_FILES, $DOCLIB_DOCTYPES;
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typeid, "string", 1, 20, "Invalid type code.");
	if(isset($xin)){
		$v->isOk ($xin, "string", 1, 20, "Invalid $DOCLIB_DOCTYPES[$typeid] number.");
	}
	$v->isOk ($docname, "string", 1, 255, "Invalid Document name.");
	$v->isOk ($docref, "string", 0, 255, "Invalid Document reference.");
	$date = $day."-".$mon."-".$year;
	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	// $v->isOk ($docname, "string", 1, 255, "Invalid Document name.");
	$v->isOk ($descrip, "string", 0, 255, "Invalid Document Description.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		// $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return enter($HTTP_POST_VARS, $confirm);
	}

	if(!isset($xin)){
		$typRs = get("yr2", "*", "doctypes", "typeid", $typeid);
		$typ = pg_fetch_array($typRs);
		$typename = "($typ[typeref]) $typ[typename]";
		$xinc = "";
	}else{
		$typename = $DOCLIB_DOCTYPES[$typeid];
		$xinc = xinc($typeid, $xin);
	}

	# Deal with uploaded file
	if (empty ($HTTP_POST_FILES["doc"])) {
		return enter($HTTP_POST_VARS, "<li class=err> Please select a document to upload from your hard drive.");
	}
	if (is_uploaded_file ($HTTP_POST_FILES["doc"]["tmp_name"])) {
		$doctyp = $HTTP_POST_FILES["doc"]["type"];
		$filename = $HTTP_POST_FILES["doc"]["name"];

		# Open file in "read, binary" mode
		$docu = "";
		$file = fopen ($HTTP_POST_FILES['doc']['tmp_name'], "rb");
		while (!feof ($file)) {
			# fread is binary safe
			$docu .= fread ($file, 1024);
		}
		fclose ($file);

		# Compress and encode the file
		$docu = doclib_encode($docu, 9);
	} else {
		return enter($HTTP_POST_VARS, "<li class=err> Unable to upload file, Please check file permissions.");
	}

	$confirm =
	"<h3>Confirm Document</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=typeid value='$typeid'>
	<input type=hidden name=docname value='$docname'>
	<input type=hidden name=docref value='$docref'>
	<input type=hidden name=day value='$day'>
	<input type=hidden name=mon value='$mon'>
	<input type=hidden name=year value='$year'>
	<input type=hidden name=descrip value='$descrip'>
 	<input type=hidden name=docu value='$docu'>
	<input type=hidden name=filename value='$filename'>
	<input type=hidden name=doctyp value='$doctyp'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Type</td><td>$typename</td></tr>
	$xinc
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Document Name</td><td>$docname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Ref</td><td>$docref</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td align=center>$date</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>File</td><td>$filename</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td>$descrip</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=right></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# Write new data
function write ($HTTP_POST_VARS)
{
	# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typeid, "string", 1, 20, "Invalid type code.");
	if(isset($xin)){
		$v->isOk ($xin, "string", 1, 20, "Invalid $DOCLIB_DOCTYPES[$typeid] number.");
	}
	$v->isOk ($docname, "string", 1, 255, "Invalid Document name.");
	$v->isOk ($docref, "string", 0, 255, "Invalid Document reference.");
	$date = $year."-".$mon."-".$day;
	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($descrip, "string", 0, 255, "Invalid Document Description.");


	# Display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	if(!isset($xin)){
		$typRs = get("yr2", "*", "doctypes", "typeid", $typeid);
		$typ = pg_fetch_array($typRs);
		$typename = $typ['typename'];
		$xin = 0;
	}else{
		$typename = $DOCLIB_DOCTYPES[$typeid];
	}

	# Connect to db
	db_conn ("yr2");

	# Write to db
	$sql = "INSERT INTO documents(typeid, typename, xin, docref, docdate, docname, filename, mimetype, descrip, docu, div) VALUES ('$typeid', '$typename', '$xin', '$docref', '$date', '$docname', '$filename', '$doctyp', '$descrip', '$docu', '".USER_DIV."')";
	$docRslt = db_exec ($sql) or errDie ("Unable to add $docname to system.", SELF);
	if (pg_cmdtuples ($docRslt) < 1) {
		return "<li class=err>Unable to add $docname to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Document added to system</th></tr>
		<tr class=datacell><td>New Document <b>$docname</b>, has been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

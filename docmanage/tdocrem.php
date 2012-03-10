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
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			if(isset($HTTP_GET_VARS['docid'])){
				$OUTPUT = confirm ($HTTP_GET_VARS);
			}else{
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if(isset($HTTP_GET_VARS['docid'])){
		$OUTPUT = confirm ($HTTP_GET_VARS);
	}else{
		$OUTPUT = view($HTTP_GET_VARS);
	}
}

# display output
require ("../template.php");
# enter new data
function view ($HTTP_GET_VARS)
{
  foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($docid,"num", 1,100, "Invalid num.");

        # display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

  db_conn('cubit');
   $user =USER_NAME;
  # write to db
  $Sql = "SELECT * FROM documents WHERE docid='$docid'";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");
  if(pg_numrows($Rslt) < 1 )
  	{
		return "Document not Found";
	}
  $Data = pg_fetch_array($Rslt);
  
  $view="<h3>Document details</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=id value=$docid>
<tr><th colspan=2>Document details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Typeid</td><td>$Data[typeid]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>TypeName</td><td>$Data[typename]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>xin/td><td>$Data[xin]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>docref</td><td>$Data[docref]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$Data[docdate]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Docname</td><td align=center>$Data[docname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>File</td><td>$Data[filename]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>MimeType</td><td>$Data[mimetype]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td>$Data[descrip]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Doc</td><td>$Data[docu]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Div</td><td>$Data[div]</td></tr> 
	
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tlist-docview.php'>List Removed Documents</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocadd.php'>Add New Document</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>
  ";
  
  db_conn('cubit');

  # write to db
  $Sql = "INSERT INTO document(typeid,typename,xin,docref,docdate,docname,filename,mimetype,descrip,docu,div) VALUES ('$Data[typeid]','$Data[typename]','$Data[xin]','$Data[docref]','$Data[docdate]','$Data[docname]','$Data[filename]','$Data[mimetype]','$Data[descrip]','$Data[docu]','$Data[div]')";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");
  
  return view;
  }
//END NEW

# Enter new data
function confirm ($VARS)
{
	# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($docid, "string", 1, 20, "Invalid document number.");

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
	

	$docRs = get("cubit", "*", "documents", "docid", $docid);
	$doc = pg_feTch_array($docRs);

	# Extra in
	$xin = xinc($doc['typeid'], $doc['xin']);

	$confirm =
	"<h3>Confirm Remove Document</h3>
	<form name=form1 action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=docid value='$docid'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Type</td><td>$doc[typename]</td></tr>
	$xin
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Ref</td><td>$doc[docref]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Document Name</td><td>$doc[docname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td>$doc[docdate]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Decription</td><td>".nl2br($doc['descrip'])."</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocadd.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocview.php'>View Documents</a></td></tr>
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
	$v->isOk ($docid, "string", 1, 20, "Invalid document number.");

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

	# Connect to db
	db_conn ("cubit");

	$docRs = get("cubit", "*", "documents", "docid", $docid);
	$doc = pg_feTch_array($docRs);

	# Write to db
	$sql = "DELETE FROM documents WHERE docid = '$docid' AND div = '".USER_DIV."'";
	$docRslt = db_exec ($sql) or errDie ("Unable to remove $doc[docname] from system.", SELF);
	if (pg_cmdtuples ($docRslt) < 1) {
		return "<li class=err>Unable to remove $doc[docname] from Cubit.";
	}
	/*
	//new
	db_conn('cubit');
	*/
  	# write to db
  	$Sql = "INSERT INTO document(typeid,typename,xin,docref,docdate,docname,filename,mimetype,descrip,docu,div)  VALUES ('$doc[typeid]', '$doc[typename]', '$doc[xin]', '$doc[docref]', '$doc[docdate]', '$doc[docname]', '$doc[filename]', '$doc[mimetype]', '$doc[descrip]', '$doc[docu]', '".USER_DIV."')";
  	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Document removed</th></tr>
		<tr class=datacell><td>Document <b>$doc[docname]</b>, has been successfully removed from the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocadd.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocview.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

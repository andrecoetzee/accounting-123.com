<?
require ("../settings.php");
require_lib("docman");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = con_data ($_POST);
			break;
		case "write":
			$OUTPUT = write_data ($_POST);
			break;
		default:
			$OUTPUT = view_data ($_GET);
	}
} else {
	$OUTPUT = view_data ($_GET);
}
# check department-level access

# display output
require ("../template.php");
# enter new data
function view_data ($_GET)
{
# Get vars
	global $_FILES, $DOCLIB_DOCTYPES;
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($docid, "string", 1, 20, "Invalid document number.");
	
	/*
	$date = $day."-".$mon."-".$year;
	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}*/
	
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
	$date= date("Y-m-d H:i:s");

  db_conn('cubit');
   $user =USER_NAME;
  # write to db
  $Sql = "SELECT * FROM document WHERE docid='$docid'";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");
  if(pg_numrows($Rslt)<1){return "Document not Found";}
  $Data = pg_fetch_array($Rslt);
	$view_data =
"

<h3>Document details</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=docid value='$docid'>

	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-even'><td>Ref</td><td><input type=text size=10 name=docref value='$Data[docref]'></td></tr>
	<tr class='bg-odd'><td>Document Name</td><td><input type=text size=20 name=docname value='$Data[docname]'></td></tr>
	<tr class='bg-odd'><td>Date</td><td>$Data[docdate]</td></tr>
	
	<tr class='bg-even'><td>Decription</td><td><textarea name=descrip rows=4 cols=18>$Data[descrip]</textarea></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit name=conf value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='tlist-docview.php'>List Removed Documents</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";
        return $view_data;
}

# confirm new data
function con_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($docid,"num",0 ,100, "Invalid number.");



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
        $Sql = "DELETE FROM document WHERE docid='$docid'";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");

	$con_data =
"
<h3>Document Removed</h3>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='tlist-docview.php'>List Documents</a></td></tr>
        <tr class='bg-odd'><td><a href='tdocadd.php'>New Document</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>

";
        return $con_data;
}
# write new data
function write_data ($_POST)
{
	# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($docid, "string", 1, 20, "Invalid document number.");
	$v->isOk ($typeid, "string", 1, 20, "Invalid type code.");
	if(isset($xin)){
		$v->isOk ($xin, "num", 1, 20, "Invalid $DOCLIB_DOCTYPES[$typeid] number.");
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
		$typRs = get("cubit", "*", "doctypes", "typeid", $typeid);
		$typ = pg_fetch_array($typRs);
		$typename = $typ['typename'];
		$xin = 0;
	}else{
		$typename = $DOCLIB_DOCTYPES[$typeid];
	}


  db_conn('cubit');

  # write to db
  $Sql = "INSERT INTO documents(typeid, typename, xin, docref, docdate, docname, filename, mimetype, descrip, docu, div) VALUES ('$typeid', '$typename', '$xin', '$docref', '$date', '$docname', '$filename', '$doctyp', '$descrip', '$docu', '".USER_DIV."')";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");


	$write_data =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Document added</th></tr>
<tr class=datacell><td>$docname has been added to Cubit.</td></tr>
</table>

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='tlist-docview.php'>List Documents</a></td></tr>
        <tr class='bg-odd'><td><a href='tdocadd.php'>New Document</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>
";
	return $write_data;
}
?>

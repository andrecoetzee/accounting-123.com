<?
// 11 Januarie 

require ("../settings.php");

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
			$OUTPUT = get_data ($_GET);
	}
} else {
	$OUTPUT = get_data ($_GET);
}

# display output
require ("../template.php");
# enter new data
function get_data ($_GET)
{

# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($_GET as $key => $value) {
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
	


  db_conn('cubit');
   # write to db
  $S1 = "SELECT * FROM document WHERE docname = docname";
  $Ri = db_exec($S1) or errDie ("Unable to access database.");
  if(pg_numrows($Ri)<1){return "Document not Found";
  }
  $Data = pg_fetch_array($Ri);

	$get_data =
"

<h3>Modify Document</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>

<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-even'><td>Ref</td><td><input type=text size=10 name=docref value='$Data[docref]'></td></tr>
	<tr class='bg-odd'><td>Document Name</td><td><input type=text size=20 name=docname value='$Data[docname]'></td></tr>
	<tr class='bg-even'><td>Date</td><td><input type=text size=2 name=day maxlength=2  value='$day'>-<input type=text size=2 name=mon maxlength=2  value='$mon'>-<input type=text size=4 name=year maxlength=4 value='$year'></td></tr>
	
	<tr class='bg-even'><td>Decription</td><td><textarea name=descrip rows=4 cols=18>$Data[descrip]</textarea></td></tr>
	<tr><td><br></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='tlist-docview.php'>List Removed Documents</a></td></tr>
        </table>
";
        return $get_data;
}

# Get Data Errors
function enter_err($_POST, $err="")
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
	$get_data =
"

<h3>Modify Document</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<tr><td>$err<br></td><tr>
<input type=hidden name=key value=confirm>
<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-even'><td>Ref</td><td><input type=text size=10 name=docref value='$docref'></td></tr>
	<tr class='bg-odd'><td>Document Name</td><td><input type=text size=20 name=docname value='$docname'></td></tr>
	<tr class='bg-even'><td>Date</td><td><input type=text size=2 name=day maxlength=2  value='$day'>-<input type=text size=2 name=mon maxlength=2  value='$mon'>-<input type=text size=4 name=year maxlength=4 value='$year'></td></tr>
	
	
	<tr class='bg-even'><td>Decription</td><td><textarea name=descrip rows=4 cols=18>$descrip</textarea></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='tlist-docview.php'>List Removed Documents</a></td></tr>
        </table>
";
        return $get_data;
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
	$v->isOk ($docref, "string", 0, 255, "Invalid Document reference.");
	$v->isOk ($docname, "string", 1, 255, "Invalid Document name.");
	$date = $day."-".$mon."-".$year;
	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($descrip, "string", 0, 255, "Invalid Document Description.");


	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"]."</li>";
		}
		return enter_err($_POST, $confirmCust);
		exit;
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}
	

	$con_data ="<h3>Confirm Document details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key      value=write>
	<input type=hidden name=docref value='$docref'>
	<input type=hidden name=docname value='$docname'>
	<input type=hidden name=day value='$day'>
	<input type=hidden name=mon value='$mon'>
	<input type=hidden name=year value='$year'>
	<input type=hidden name=descrip value='$descrip'>
 	
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Ref</td><td>$docref</td></tr>
	<tr class='bg-even'><td>Document Name</td><td>$docname</td></tr>
	<tr class='bg-even'><td>Date</td><td align=center>$date</td></tr>
	<tr class='bg-even'><td>Description</td><td>$descrip</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='list_consdel.php'>List Documents</a></td></tr>
        </table>";
        return $con_data;
}
# write new data
function write_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($docref, "string", 0, 255, "Invalid Document reference.");
	$v->isOk ($docname, "string", 1, 255, "Invalid Document name.");
	$date = $year."-".$mon."-".$day;
	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($descrip, "string", 0, 255, "Invalid Document Description.");


	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}
		$date= date("Y-m-d H:i:s");
	db_conn('cubit');

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class=err>Unable to edit Document(TB)</li>";
	}

	$S1="SELECT * FROM document WHERE docname=docname";
	$Ri=db_exec($S1) or errDie("Unable to get Document details.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid Document.";
	}

	$cdata=pg_fetch_array($Ri);

	# write to db
	$S1 = "INSERT INTO documents(  docref, docdate, docname, descrip, div) VALUES (  '$docref', '$date', '$docname', '$descrip', '".USER_DIV."')";
	$Ri = db_exec($S1) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Ri);

	

	if (!pglib_transaction("COMMIT")) {
		return "<li class=err>Unable to edit Document. (TC)</li>";
	}

	$write_data =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Document modified</th></tr>
	<tr class=datacell><td>$docname has been modified.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>	
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='tlist-docview.php'>View Removed Documents</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
        </table>";
	return $write_data;
}
?>

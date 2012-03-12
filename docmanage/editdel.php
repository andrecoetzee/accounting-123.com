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

foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	


  db_conn('cubit');
   # write to db
  $S1 = "SELECT * FROM document WHERE docid='$docid' AND docname = docname";
  $Ri = db_exec($S1) or errDie ("Unable to access database.");
  if(pg_numrows($Ri)<1){return "Contact not Found";
  }
  $Data = pg_fetch_array($Ri);

	$get_data =
"

<h3>Modify Contact</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=id value='$id'>
<tr><th colspan=2>Personal details</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td align=center><input type=text size=27 name=name value='$Data[name]'></td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Surname</td><td align=center><input type=text size=27 name=surname value='$Data[surname]'></td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel</td><td align=center><input type=text size=27 name=tel value='$Data[tel]'></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='list_consdel.php'>List contacts</a></td></tr>
        </table>
";
        return $get_data;
}

# Get Data Errors
function enter_err($_POST, $err="")
{
  global $_POST;
	extract($_POST);

		if(!(isset($name))) {
		$name="";
		$surname="";
		$tel="";
	}
	$get_data =
"

<h3>Modify Contact</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<tr><td>$err<br></td><tr>
<input type=hidden name=key value=confirm>
<input type=hidden name=id value='$id'>
<tr><th colspan=2>Personal details</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td align=center><input type=text size=27 name=name value='$name'></td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Surname</td><td align=center><input type=text size=27 name=surname value='$surname'></td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel</td><td align=center><input type=text size=27 name=tel value='$tel'></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='list_consdel.php'>List contacts</a></td></tr>
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
	$v->isOk ($name, "string", 1, 20, "Invalid Name.");
	$v->isOk ($surname, "string", 0, 20, "Invalid Surname.");
   	$v->isOk ($tel, "phone", 0, 10, "Invalid Tel.");


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
	

	$con_data ="<h3>Confirm contact details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key      value=write>
	<input type=hidden name=name  value='$name'>
	<input type=hidden name=surname      value='$surname'>
	<input type=hidden name=tel     value='$tel'>
	<input type=hidden name=id     value='$id'>
	<tr><th colspan=2>Personal details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center>$name</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Surname</td><td align=center>$surname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel</td><td align=center>$tel</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='list_consdel.php'>List contacts</a></td></tr>
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
	$v->isOk ($name, "string", 1, 20, "Invalid Name.");
	$v->isOk ($surname, "string", 0, 20, "Invalid Surname.");
 	$v->isOk ($tel, "phone", 0, 10, "Invalid Tel.");


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
		
	db_conn('cubit');

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class=err>Unable to edit contact(TB)</li>";
	}

	$S1="SELECT * FROM lcon WHERE id='$id'";
	$Ri=db_exec($S1) or errDie("Unable to get contact details.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid contact.";
	}

	$cdata=pg_fetch_array($Ri);

	# write to db
	$S1 = "UPDATE lcon SET name='$name', surname='$surname', tel='$tel' WHERE id='$id'";
	$Ri = db_exec($S1) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Ri);

	

	if (!pglib_transaction("COMMIT")) {
		return "<li class=err>Unable to edit contact. (TC)</li>";
	}

	$write_data =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Contact modified</th></tr>
	<tr class=datacell><td>$name has been modified.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>	
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='list_consdel.php'>View contacts</a></td></tr>
        </table>";
	return $write_data;
}
?>

<?
require ("../settings.php");
# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = con_mail($_POST);
			break;
		case "write":
			$OUTPUT = write_mail ($_POST);
			break;
		default:
			$OUTPUT = get_mail ();
	}
} else {
	$OUTPUT = get_mail ();
}

# display output
require ("../template.php");
# enter new data

function get_mail()
{
        global $_POST;
	    extract($_POST);
		
		if(!(isset($email))) {
		$email = "";
		$msg = "";
		
	}
	/*//db_conn(YR_DB);
	// DataBase
	$S1 = "SELECT * FROM document ORDER BY name";
	$Ri = db_exec($S1) or errDie("Unable to get data.");
	#$MD5_PASS = md5 ("password");
	#print $MD5_PASS;
        $cons = "<table>";
	while($data = pg_fetch_array($Ri))  {
	}
	 $cons .= "</table>";*/
	 
         $get_mail = " 
		<h3>General Message</h3>
		<form method=get action='".SELF."'>
		<input type=hidden name=step value='2'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan=2>Message Details</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To</td><td><input type=text size=27 name=email value=$email></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'>
			<td>Message</td>
			<td><textarea cols=25 rows=6 name=msg value=$msg></textarea></td>
		</tr>
		<tr>
			<td colspan=2 align=center><input type=submit value='Send'></td>
		</tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a 		href='tdocview.php'>View a list of the 		document</a></td></tr>

		</table>
		</form>";
return $get_mail;

}
# Enter new data Error
function enter_err($_POST, $err="")
{
	
        global $_POST;
	    extract($_POST);

		if(!(isset($email))) {
		$email = "";
		$msg = "";
		
	}
	/*
	// DataBase
	$S1 = "SELECT * FROM document ORDER BY name ";
	$Ri = db_exec($S1) or errDie("Unable to get data.");
        $cons = "<table>";

	while($data = pg_fetch_array($Ri))  {

	}
	 $cons .= "</table>";*/

         $get_mail = " 
		<h3>General Message</h3>
		<form method=get action='".SELF."'>
		<input type=hidden name=step value='2'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan=2>Message Details</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>To</td><td><input type=text size=27 name=email value=$email></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'>
			<td>Message</td>
			<td><textarea cols=25 rows=6 name=msg value=$msg></textarea></td>
		</tr>
		<tr>
			<td colspan=2 align=center><input type=submit value='Send'></td>
		</tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a 		href='tdocview.php'>View a list of the 		document</a></td></tr>

		</table>
		</form>";
return $get_mail;

}

function con_mail($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($email, "email", 1, 20, "Invalid email address.");
	$v->isOk ($msg, "string", 0, 1000, "Invalid text of msg max is 1000.");
   	
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
	$con_data=
	"<h3>Confirm contact details</h3>
	<table cellpadding='".TMPL_tblCellPadding."'cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."'method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=email value='$email'>
	<input type=hidden name=msg value='$msg'>
	<tr><th colspan=2>Document Details</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center>$email</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Surname</td><td align=center>$msg</td></tr>
	  
	<tr><td colspan=2 align=right><input type=submit value='Email &raquo;'></td></tr>
		
		</table>
		</form>";

return $con_data;

}
# write new data
function write_mail ($_POST)
{
	//$date = date("Y-m-d  H:i:s");
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($email, "email", 1, 20, "Invalid email address.");
	$v->isOk ($msg, "string", 0, 1000, "Invalid text of msg max is 1000.");
   	
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

	/*db_conn('cubit');
	$S1 = "INSERT INTO document(name,surname,tel,date) VALUES ('$name','$surname','$tel','$date')";
	$Ri = db_exec($S1) or errDie("Unable to insert document.");*/


$write_mail = "<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Document added</th></tr>
	<tr class=datacell><td>$email has been added to Cubit.</td></tr>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	</table>
	 <p><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocview.php'>View a list of the document</a></td></tr>
	</table>";
return $write_mail;
;

}
?>

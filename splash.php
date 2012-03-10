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

if ($HTTP_POST_VARS) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default :
			$OUTPUT = enter ();
        }
}else {
	$OUTPUT = enter ();
}

require ("template.php");

function enter (){

	db_connect ();

	$sql = "SELECT * FROM splash";
	$allow = db_exec($sql) or errDie ("Unable To Read Splash Screen");

	if (pg_numrows($allow) < 1){
		return "<h3>No Splash Screen Found </h3>";
	}

	$splashdata = pg_fetch_array($allow);



	$enter = "	<h3>Enter New Splash Screen Text</h3>
				<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<form action='".SELF."' method=post>
        		<input type=hidden name=key value=confirm>
				<tr>
					<th colspan=2>Splash Screen Details</th>
				</tr>
				<tr>
					<td><TEXTAREA rows=20 cols=100 name='message'>$splashdata[message]</TEXTAREA>
				</tr>
				<tr>
					<td><br></td>
				</tr>
				<tr>
					<td><input type='submit' value='Confirm'></td>
				</tr>
				</form>
				</table>
				<p>
				<tr>
				<table border=0 cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
				</tr>
	";
	return $enter;
}

function confirm (){

	global $HTTP_POST_VARS;

	foreach ($HTTP_POST_VARS as $key => $value){
		$$key = $value;
	}


	$temp = customremval($message);

	if (strlen($temp) > 2000){
		return "<h3>The New Splash Screen Is Too Long</h3>";
	}

	$confirm = "<h3>Confirm New Splash Screen Text</h3>
				<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<form action='".SELF."' method=post>
        		<input type=hidden name=key value=write>
				<input type=hidden name=message value='$temp'>
				<tr>
					<th colspan=2>Splash Screen Details</th>
				</tr>
				<tr bgcolor='".TMPL_tblDataColor2."'>
					<td><pre>$temp</pre></td>
				</tr>
				<tr>
					<td><br></td>
				</tr>
				<tr>
					<td><input type='submit' value='Write'></td>
				</tr>
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
				</tr>
				";
	return $confirm;
}

function write (){

	global $HTTP_POST_VARS;

	foreach ($HTTP_POST_VARS as $key => $value){
		$$key = $value;
	}

	$temp = customremval($message);

	if (strlen($temp) > 2000){
		return "<h3>The New Splash Screen Is Too Long</h3>";
	}

	db_connect ();

	$sql = "UPDATE splash SET message ='$temp'";
	$allow = db_exec($sql) or errDie ("Unable To Update Splash Screen");

	return "<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr>
				<td><h3><h3>Splash Screen Successfully Updated</h3></h3></td>
			</tr>
			</table>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>

	";

}


function customremval ($value)
{

	if(!isset($value)) {return "Invalid use of function";}

	$value = str_replace("[","",$value);
	$value = str_replace("]","",$value);
	$value = str_replace("{","",$value);
	$value = str_replace("}","",$value);
	$value = str_replace("|","",$value);
	$value = str_replace("'","",$value);
	$value = str_replace("`","",$value);
	$value = str_replace("~","",$value);
	$value = str_replace("\\","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace(";","",$value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);

	return $value;

}

?>

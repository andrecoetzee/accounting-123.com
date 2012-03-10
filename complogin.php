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

require ("newsettings.php");

# Decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "login":
			$OUTPUT = login ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctcomp ();
	}
} else {
	$OUTPUT = slctcomp ();
}

require("newtemplate.php");




# Confirms
function slctcomp ($err = "")
{

	// session_start ();
	session_name ("CUBIT_SESSION");
	session_unset ();

	# connect to db
	db_conn("cubit");

	# Get Companies
	$sql = "SELECT * FROM companies WHERE status='active' ORDER BY name ASC";
	$compRslt = db_exec($sql) or die ("Unable to get companies from database.");
	if (pg_numrows ($compRslt) < 1) {
		header("Location: company-new.php");
	}
	$comps = "<select size='1' name='code'>\n";
	while ($comp = pg_fetch_array ($compRslt)) {
		$comps .= "<option value='$comp[code]'>$comp[name]</option>\n";
	}
	$comps .= "</select>\n";

	$slct = "
		<h3>Select Company to log in to</h3>
		<form action=".SELF." method='POST'>
			<input type='hidden' name='key' value='login'>
		<table cellpadding='1' cellspacing='1'>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th colspan='2'>Select Company</th>
			</tr>
			<tr bgcolor='#77AAEE'>
				<td>Company Name</td>
				<td align='center'>$comps</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		<script>
			if (top.mainframe) {
				top.location.href = 'complogin.php';
			}
		</script>
		</table>
		</form>";
	return $slct;

}




# Log in to Company
function login ($HTTP_POST_VARS)
{

	global $HTTP_SESSION_VARS;
	extract($HTTP_POST_VARS);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($code,"string", 1, 5, "Invalid company name.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctcomp($confirm);
	}



	# Change code to lowercase
	$code = strtolower($code);

	if(!db_check("cubit_".$code)){
		return slctcomp("<li class='err'>Invalid Company. Select another company or<br />
			click <a href='company-new.php?key=recover'>here</a> to see if Cubit can recover from this error.</li>");
	}

	# Get Company Name
	db_conn("cubit");

	$sql = "SELECT name FROM companies WHERE code = '$code'";
	$compRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	if(pg_numrows($compRslt) < 1){
		$comp['name'] = "";
	}else{
		$comp = pg_fetch_array($compRslt);
	}

	$HTTP_SESSION_VARS["code"] = $code;
	$HTTP_SESSION_VARS["comp"] = $comp['name'];

	header("Location: doc-index.php");

}



?>
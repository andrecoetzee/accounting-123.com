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

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			if (isset($HTTP_GET_VARS['calloutpid'])){
				$OUTPUT = rem ($HTTP_GET_VARS['calloutpid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
		if (isset($HTTP_GET_VARS['calloutpid'])){
			$OUTPUT = rem ($HTTP_GET_VARS['calloutpid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function rem($calloutpid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($calloutpid, "num", 1, 50, "Invalid Call Out Person id.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM calloutpeople WHERE calloutpid = '$calloutpid' AND div = '".USER_DIV."'";
        $salespRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($salespRslt) < 1){
                return "<li> Invalid Call Out Person ID.";
        }else{
                $calloutp = pg_fetch_array($salespRslt);
        }

	$enter = "
			<h3>Confirm Remove Call Out Person</h3>
			<form action='".SELF."' method='post'>
			<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='calloutpid' value='$calloutp[calloutpid]'>
				<input type='hidden' name='calloutp' value='$calloutp[calloutp]'>
				<input type='hidden' name='telno' value='$calloutp[telno]'>
				<tr><th>Field</th><th>Value</th></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td>Call Out Person</td><td>$calloutp[calloutp]</td></tr>
				<tr bgcolor='".TMPL_tblDataColor2."'><td>Contact Number</td><td>$calloutp[telno]</td></tr>
				<tr><td><br></td></tr>
				<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
			</table></form>
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='calloutp-view.php'>View Call Out Person</a></td></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
			</table>";

	return $enter;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($calloutpid, "num", 1, 50, "Invalid Call Out Person id.");

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

	# connect to db
	db_conn ("exten");

	# write to db
	$sql = "DELETE FROM calloutpeople WHERE calloutpid = '$calloutpid' AND div = '".USER_DIV."'";
	$calloutpRslt = db_exec ($sql) or errDie ("Unable to remove Sales Person from system.", SELF);
	if (pg_cmdtuples ($calloutpRslt) < 1) {
		return "<li class=err>Unable to remove Call Out Person from database.";
	}

	$write = "
			<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Call Out Person Removed</th></tr>
				<tr class=datacell><td>Call Out Person <b>$calloutp</b>, has been removed.</td></tr>
			</table>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='calloutp-view.php'>View Call Out Persons</a></td></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
			</table>";

	return $write;
}

?>

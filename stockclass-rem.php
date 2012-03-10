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
require ("settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
			case "write":
            	$OUTPUT = write($HTTP_POST_VARS);
				break;

			default:
				if (isset($HTTP_GET_VARS['clasid'])){
					$OUTPUT = rem ($HTTP_GET_VARS['clasid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($HTTP_GET_VARS['clasid'])){
			$OUTPUT = rem ($HTTP_GET_VARS['clasid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("template.php");

function rem($clasid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification id.");

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
		db_connect();
		$sql = "SELECT * FROM stockclass WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
        $clasRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($clasRslt) < 1){
                return "<li> Invalid Category ID.";
        }else{
                $clas = pg_fetch_array($clasRslt);
        }

	$enter =
	"<h3>Remove Classification</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=clasid value='$clas[clasid]'>
	<input type=hidden name=classname value='$clas[classname]'>
	<input type=hidden name=classcode value='$clas[classcode]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Classification Code</td><td align=center>$clas[classcode]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Classification</td><td align=center>$clas[classname]</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	<tr><td><br></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stockclass-view.php'>View Classifications</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($classname, "string", 1, 255, "Invalid Classification name.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification id.");

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
	db_connect ();

	# write to db
	$sql = "DELETE FROM stockclass WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec ($sql) or errDie ("Unable to remove classification from system.", SELF);
	if (pg_cmdtuples ($clasRslt) < 1) {
		return "<li class=err>Unable to remove classification.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Classification Removed</th></tr>
	<tr class=datacell><td>Classification <b> ($classcode) $classname</b>, has been removed from Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stockclass-view.php'>View Classifications</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

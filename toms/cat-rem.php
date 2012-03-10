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
				if (isset($HTTP_GET_VARS['catid'])){
					$OUTPUT = rem ($HTTP_GET_VARS['catid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($HTTP_GET_VARS['catid'])){
			$OUTPUT = rem ($HTTP_GET_VARS['catid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function rem($catid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($catid, "num", 1, 50, "Invalid category id.");

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
		$sql = "SELECT * FROM categories WHERE catid = '$catid'AND div = '".USER_DIV."'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid Category ID.";
        }else{
                $cat = pg_fetch_array($catRslt);
        }

	$enter =
	"<h3>Confirm Remove Category</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=catid value='$cat[catid]'>
	<input type=hidden name=category value='$cat[category]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Category</td><td align=center>$cat[category]</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cat-view.php'>View Categories</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($catid, "num", 1, 50, "Invalid category id.");

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
	$sql = "DELETE FROM categories WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec ($sql) or errDie ("Unable to remove category from system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class=err>Unable to remove category from database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Category Removed</th></tr>
	<tr class=datacell><td>Category <b>$category</b>, has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cat-view.php'>View Categories</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

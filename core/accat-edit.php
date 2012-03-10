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
            case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;

			case "write":
            	$OUTPUT = write($HTTP_POST_VARS);
				break;

			default:
				if (isset($HTTP_GET_VARS['catid'])){
					$OUTPUT = edit ($HTTP_GET_VARS['catid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($HTTP_GET_VARS['catid'])){
			$OUTPUT = edit ($HTTP_GET_VARS['catid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("template.php");

function edit($catid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($catid, "string", 1, 50, "Invalid Account Category number.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

		switch(substr($catid,0,1)){
				case "I":
						$tab = "Income";
						break;
				case "E":
						$tab = "Expenditure";
						break;
				case "B":
						$tab = "Balance";
						break;
				default:
						return "<li>Invalid Category type";
		}

		# Select Stock
		core_connect();
		$sql = "SELECT * FROM $tab WHERE catid = '$catid' AND div = '".USER_DIV."'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid Account Category number.";
        }else{
                $cat = pg_fetch_array($catRslt);
        }

		# length of input box
		$size = strlen($cat['catname']);
		if($size < 20){
			$size = 20;
		}

		$edit = "<h3>Edit Account Category</h3>
		<form action='".SELF."' method=post>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<input type=hidden name=key value=confirm>
			<input type=hidden name=catid value='$cat[catid]'>
			<input type=hidden name=tab value='$tab'>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Category Type</td><td>$tab</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Category Number</td><td valign=center>$catid</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Category Name</td><td><input type=text size=$size name=catname value='$cat[catname]'></td></tr>
			<tr><td><br></td></tr>
			<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='accat-view.php'>View Account Categories</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $edit;
}

function edit_err($HTTP_POST_VARS, $error = "")
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}

		switch(substr($catid,0,1)){
				case "I":
						$tab = "Income";
						break;
				case "E":
						$tab = "Expenditure";
						break;
				case "B":
						$tab = "Balance";
						break;
				default:
						return "<li>Invalid Category type";
		}

		# Select Stock
		core_connect();
		$sql = "SELECT * FROM $tab WHERE catid = '$catid' AND div = '".USER_DIV."'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid Account Category number.";
        }else{
                $cat = pg_fetch_array($catRslt);
        }

		# length of input box
		$size = strlen($cat['catname']);
		if($size < 20){
			$size = 20;
		}

		$edit = "<h3>Edit Account Category</h3>
		<form action='".SELF."' method=post>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<input type=hidden name=key value=confirm>
			<input type=hidden name=catid value='$cat[catid]'>
			<input type=hidden name=tab value='$tab'>
			<tr><td colspan=2>$error</td></tr>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Category Type</td><td>$tab</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Category Number</td><td valign=center>$catid</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Category Name</td><td><input type=text size=$size name=catname value='$catname'></td></tr>
			<tr><td><br></td></tr>
			<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='accat-view.php'>View Account Categories</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $edit;
}

# confirm new data
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($catid, "string", 1, 50, "Invalid category number.");
	$v->isOk ($catname, "string", 1, 255, "Invalid category name.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return edit_err($HTTP_POST_VARS, $confirm);
	}

	# Check category name
	core_connect();
	$sql = "SELECT * FROM $tab WHERE lower(catname) = '".strtolower($catname)."' AND catid != '$catid' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account Category details from database.");
	if (pg_numrows($checkRslt) > 0) {
		$confirm = "<li class=err> Account Category name already exist.";
		return edit_err($HTTP_POST_VARS, $confirm);
	}

	$confirm = "<h3>Confirm edit Account Category</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=write>
		<input type=hidden name=catid value='$catid'>
		<input type=hidden name=catname value='$catname'>
		<input type=hidden name=tab value='$tab'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Category Type</td><td>$tab</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Category Number</td><td>$catid</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Category Name</td><td>$catname</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='accat-view.php'>View Account Categories</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
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
	$v->isOk ($catid, "string", 1, 50, "Invalid category number.");
	$v->isOk ($catname, "string", 1, 255, "Invalid category name.");

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
	core_connect ();

	# write to db
	$sql = "UPDATE $tab SET catname = '$catname' WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec ($sql) or errDie ("Unable to add edit account no system.", SELF);

	// Layout
	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Account Category edited</th></tr>
		<tr class=datacell><td>Account Category No.<b>$catid</b>, has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='accat-view.php'>View Account Categories</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

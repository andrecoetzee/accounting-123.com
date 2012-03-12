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
require("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
            	$OUTPUT = write($_POST);
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# Get template
require("template.php");

# Default view
function view()
{
		//layout
        $view = "<h3>Add Stock Category</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        ".frmupdate_passon()."
        <tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Category code</td><td><input type=text size=20 name='catcod'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>".REQ."Category name</td></td><td><input type=text size=20 name='cat'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Description</td><td><textarea cols=18 rows=5 name='descript'></textarea></textarea></td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add &raquo'></td></tr>
        </table>
		<P>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        <tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </form>
        </table>";

        return $view;
}

# confirm
function confirm($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($catcod, "string", 0, 50, "Invalid category code.");
		$v->isOk ($cat, "string", 1, 255, "Invalid stock category name.");
		$v->isOk ($descript, "string", 0, 100, "Invalid stock category descripting.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>$e[msg]</li>";
			}
			return $confirm."</li><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
					<P>
					<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
					<tr><th>Quick Links</th></tr>
						<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</form>
					</table>";
		}

		# check stock code
		db_connect();
		$sql = "SELECT catcod FROM stockcat WHERE lower(catcod) = lower('$catcod') AND div = '".USER_DIV."'";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class=err> A Category with code : <b>$catcod</b> already exists.</li>";
			$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $error;
		}

		// Layout
		$confirm =
		"<h3>Add Stock Category</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		".frmupdate_passon()."
		<input type=hidden name=key value=write>
		<input type=hidden name=catcod value='$catcod'>
		<input type=hidden name=cat value='$cat'>
		<input type=hidden name=descript value='$descript'>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Category Code</td><td>$catcod</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Category Name</td></td><td>$cat</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Description</td><td><pre>$descript</pre></td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Confirm &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $confirm;
}

# write
function write($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($catcod, "string", 0, 50, "Invalid category code.");
	$v->isOk ($cat, "string", 1, 255, "Invalid stock category name.");
	$v->isOk ($descript, "string", 0, 100, "Invalid stock category descripting.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		$confirm .= "</li><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
				<P>
				<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
				<tr><th>Quick Links</th></tr>
					<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</form>
				</table>";

		return $confirm;
	}

	# check stock code
	db_connect();
	$sql = "SELECT catcod FROM stockcat WHERE lower(catcod) = lower('$catcod') AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err> A Category with code : <b>$catcod</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		//return $error;
	}

	// insert into stock
	db_connect();
	$sql = "INSERT INTO stockcat(catcod, cat, descript, div) VALUES('$catcod', '$cat', '$descript', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to insert stock category to Cubit.",SELF);

	if (frmupdate_passon()) {
		$newlst = new dbSelect("stockcat", "cubit", grp(
			m("cols", "catid, catcod, cat"),
			m("where", "div='".USER_DIV."'"),
			m("order", "cat ASC")
		));
		$newlst->run();

		$a = array();
		if ($newlst->num_rows() > 0) {
			while ($row = $newlst->fetch_array()) {
				$a[$row["catid"]] = "($row[catcod]) $row[cat]";
			}
		}

		$js = frmupdate_exec(array($a), true);
	} else {
		$js = "";
	}

	$write ="
	$js
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>New Stock Category added to database</th></tr>
		<tr class=datacell><td>New Stock Category, $cat ($catcod) has been successfully added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

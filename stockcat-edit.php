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
require("core-settings.php");

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
				if (isset($_GET['catid'])){
					$OUTPUT = edit ($_GET['catid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['catid'])){
			$OUTPUT = edit ($_GET['catid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# get template
require("template.php");


 # confirm
function edit($catid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($catid, "num", 1, 50, "Invalid stock category id.");

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
		$sql = "SELECT * FROM stockcat WHERE catid = '$catid' AND div = '".USER_DIV."'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $cat = pg_fetch_array($catRslt);
        }

		# get stock vars
		foreach ($cat as $key => $value) {
			$$key = $value;
		}

		// layout
		$edit =
		"<h3>Edit Stock Category</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=45%>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=confirm>
		<input type=hidden name=catid value='$catid'>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr class='bg-odd'><td>Category code</td><td><input type=text size=20 name='catcod' value='$catcod'></td></tr>
		<tr class='bg-even'><td>Category name</td></td><td><input type=text size=20 name='cat' value='$cat'></td></tr>
		<tr class='bg-odd'><td valign=top>Description</td><td><textarea cols=18 rows=5 name='descript'>$descript</textarea></td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Edit &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='#88BBFF'><td><a href='stockcat-add.php'>Add Stock Category</a></td></tr>
			<tr bgcolor='#88BBFF'><td><a href='stock-view.php'>View Stock Categories</a></td></tr>
   			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $edit;
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
		$v->isOk ($catid, "num", 1, 50, "Invalid stock category id.");
		$v->isOk ($catcod, "string", 0, 50, "Invalid category code.");
		$v->isOk ($cat, "string", 1, 255, "Invalid stock category name.");
		$v->isOk ($descript, "string", 0, 50, "Invalid stock category descripting.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
					return $confirm;
		}

		# check stock code
		db_connect();
		$sql = "SELECT catcod FROM stockcat WHERE lower(catcod) = lower('$catcod') AND catid != '$catid' AND div = '".USER_DIV."'";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class=err> A Category with code : <b>$catcod</b> already exists.</li>";
			$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $error;
		}

		// Layout
		$confirm =
		"<h3>Edit Stock Category</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=write>
		<input type=hidden name=catid value='$catid'>
		<input type=hidden name=catcod value='$catcod'>
		<input type=hidden name=cat value='$cat'>
		<input type=hidden name=descript value='$descript'>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
			<tr class='bg-odd'><td>Category Code</td><td>$catcod</td></tr>
			<tr class='bg-even'><td>Category Name</td></td><td>$cat</td></tr>
			<tr class='bg-odd'><td valign=top>Description</td><td><pre>$descript</pre></td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Confirm &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-even'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
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
	$v->isOk ($catid, "num", 1, 50, "Invalid stock category id.");
	$v->isOk ($catcod, "string", 0, 50, "Invalid category code.");
	$v->isOk ($cat, "string", 1, 255, "Invalid stock category name.");
	$v->isOk ($descript, "string", 0, 50, "Invalid stock category descripting.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# check stock code
	db_connect();
	$sql = "SELECT catcod FROM stockcat WHERE lower(catcod) = lower('$catcod') AND catid != '$catid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err> A Category with code : <b>$catcod</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Insert the customer
	db_connect();
	$sql = "UPDATE stockcat SET catcod = '$catcod', cat = '$cat', descript = '$descript' WHERE catid = '$catid'";
	$rslt = db_exec($sql) or errDie("Unable to update stock category in Cubit.",SELF);

	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Stock Category edited</th></tr>
	<tr class=datacell><td>Stock Category, $cat ($catcod) has been successfully edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='stockcat-add.php'>Add Stock Category</a></td></tr>
		<tr class='bg-even'><td><a href='stockcat-view.php'>View Stock Categories</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

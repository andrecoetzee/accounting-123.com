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
				if (isset($_GET['div'])){
					$OUTPUT = edit ($_GET['div']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['div'])){
			$OUTPUT = edit ($_GET['div']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# get template
require("template.php");


 # confirm
function edit($div)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($div, "num", 1, 50, "Invalid branch id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return $confirm;
	}

		# Select Branch
	db_connect();
	$sql = "SELECT * FROM branches WHERE div = '$div'";
        $branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.";
        }else{
		$bran = pg_fetch_array($branRslt);
        }

	// layout
	$edit =
	"<h3>Edit Branch Branch</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=div value='$div'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Branch code</td><td><input type=text size=20 name='brancod' value='$bran[brancod]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>".REQ."Branch name</td></td><td><input type=text size=20 name='branname' value='$bran[branname]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Details</td><td><textarea cols=18 rows=5 name='brandet'>$bran[brandet]</textarea></td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='admin-branview.php'>View Branches</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($div, "num", 1, 50, "Invalid branch id.");
	$v->isOk ($brancod, "string", 0, 50, "Invalid branch code.");
	$v->isOk ($branname, "string", 1, 255, "Invalid branch name.");
	$v->isOk ($brandet, "string", 0, 255, "Invalid details .");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return $confirm.edit($div);
	}

	// Layout
	$confirm =
	"<h3>Edit Branch</h3>
	<h4>Confirm entry</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=div value='$div'>
	<input type=hidden name=brancod value='$brancod'>
	<input type=hidden name=branname value='$branname'>
	<input type=hidden name=brandet value='$brandet'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Branch Code</td><td>$brancod</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch Name</td></td><td>$branname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Details</td><td><pre>$brandet</pre></td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='admin-branview.php'>View Branches</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
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
	
	if(isset($back)) {
		return edit($div);
	}
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($div, "num", 1, 50, "Invalid branch id.");
	$v->isOk ($brancod, "string", 0, 50, "Invalid branch code.");
	$v->isOk ($branname, "string", 1, 255, "Invalid branch name.");
	$v->isOk ($brandet, "string", 0, 255, "Invalid details.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	// Update branch
	db_connect();
	$sql = "UPDATE branches SET brancod = '$brancod', branname = '$branname', brandet = '$brandet' WHERE div = '$div'";
	$rslt = db_exec($sql) or errDie("Unable to update branch in Cubit.",SELF);

	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Branch edited</th></tr>
	<tr class=datacell><td>Branch, $branname ($brancod) has been successfully edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='admin-branview.php'>View Branches</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

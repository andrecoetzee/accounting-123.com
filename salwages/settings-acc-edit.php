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

require ("../settings.php");

if ($_POST) {
	switch ($_POST["key"]) {
		case  "confirm":
			$OUTPUT = confirmSettings ($_POST);
			break;
		case "write":
			$OUTPUT = writeSettings ($_POST);
			break;
		default:
			$OUTPUT = editSettings ();
	}
} else {
	# print form for data entry
	$OUTPUT = editSettings ();
}



require ("../template.php");

##
# Functions
##

# form to enter new data
function editSettings ()
{
	# connect to db
	db_connect ();

	/* static settings for dumb sars thing... you might as well just make your percentage 0 */
	$i = 0;
	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	if (!isset($sdlpayable)) $sdlpayable = getCSetting("SDLPAYABLE");
	if (!isset($emploan_int)) $emploan_int = getCSetting("EMPLOAN_INT");

	$settings = "";
	$settings .= "
	<script>
	help_msgs = new Array();

	help_msgs[0] =
		 'When the total annual salaries being paid out by your company is less than '
		+'R500 000, SARS will notify your company that no SDL needs to be paid. In the event '
		+'this amount does go above R500 000, you will again be notified otherwise.';

	help_msgs[1] =
		 'This value is used as the default interest to be used when granting a loan to '
		+'an employee. At the time of granting, it is possible to change it again if '
		+'needed. Note that this is not and does not change the Official interest rate of 8%. '
		+'If the interest you charge on the loan is less than the official rate, '
		+'a fringe benefit equal to the difference of the interest you charged and '
		+'the possible interest at the official rate will be added to the employee\'s '
		+'taxable income for PAYE calculation purposes.';

	function showhelp(obj, item) {
		XPopupShow(help_msgs[item], obj);
	}
	</script>
	<tr bgcolor='$bgColor'>
		<!--<td><a href='#top'>Top</a> | <a href='#bottom'>Bottom</a></td>//-->
		<td align='center'>SDL Payable [<a href='#' onClick='javascript:showhelp(this, 0);'>about</a>]</td>
		<td>
			<select name='sdlpayable'>
				<option value='y' ".($sdlpayable != 'n' ? "selected" : "").">Yes</option>
				<option value='n' ".($sdlpayable == 'n' ? "selected" : "").">No</option>
			</select>
		</td>
	</tr>
	<tr bgcolor='$bgColor'>
		<!--<td><a href='#top'>Top</a> | <a href='#bottom'>Bottom</a></td>//-->
		<td align='center'>Default Interest on Employee Loans [<a href='#' onClick='javascript:showhelp(this, 1);'>about</a>]</td>
		<td>
			<input type='text' name='emploan_int' value='$emploan_int'>
		</td>
	</tr>";

	# select editable settings from db
	$menu = "";
	$sql = "SELECT * FROM settings WHERE type='accounting' AND (readonly='f'::bool) ORDER BY label";
	$setRslt = db_exec ($sql) or errDie ("Unable to select settings from database.");
	$num_settings = pg_numrows ($setRslt);
	if ($num_settings < 1) {
		errDie ("No settings found in database!");
	}
	while ($mySet = pg_fetch_array ($setRslt)) {
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		//$menu .= (($i+1) % 4) ? "<a href='#$mySet[constant]' class=nav>$mySet[label]</a> | " : "<a href='#$mySet[constant]' class=nav>$mySet[label]</a><p>\n";
		$settings .= "
		<tr bgcolor='$bgColor'>
			<!--<td><a href='#top'>Top</a> | <a href='#bottom'>Bottom</a></td>//-->
			<td align=center><a name='$mySet[constant]'></a>$mySet[label]</td>
			<td><input type=text size=20 name='$mySet[constant]' value='$mySet[value]'></td>
		</tr>\n";
		$i++;
	}

	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

	$Sl="SELECT * FROM salset";
	$Ri=db_exec($Sl);

	if(pg_num_rows($Ri)>0) {
		$set="selected";
		$set2="";
	} else {
		$set="";
		$set2="selected";
	}

	$sets="<select name='set'>
	<option value='Yes' $set>Yes</option>
	<option value='No' $set2>No</option>
	</select>";


	# Set up table & form
	$enterSettings =
"<h3>Edit accounting settings</h3>
<a name=top></a>
$menu
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<tr><th>Description</th><th>Value</th></tr>
$settings
<tr><td colspan=3 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	)."
<a name=bottom></a>
";
	return $enterSettings;
}

# Confirm entered info
function confirmSettings ($_POST)
{
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	# get settings
	$settings = "";
	$hidden_fields = "";
	$i = 0;

	$hidden_fields .= "<input type=hidden name='sdlpayable' value='$sdlpayable'>";
	$bgColor = ($i++ % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

	$settings .= "
	<tr bgcolor='$bgColor'>
		<td>SDL Payable</td>
		<td>".($sdlpayable == 'y' ? "Yes" : "No")."</td>
	</tr>";

	$hidden_fields .= "<input type='hidden' name='emploan_int' value='$emploan_int'>";
	$bgColor = ($i++ % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	$settings .= "
	<tr bgcolor='$bgColor'>
		<td>Default Interest on Employee Loans</td>
		<td>$emploan_int %</td>
	</tr>";

	db_connect ();
	$sql = "SELECT * FROM settings WHERE type='accounting' AND (readonly='f'::bool) ORDER BY label";
	$setRslt = db_exec ($sql) or errDie ("Unable to select settings from database.");
	$num_settings = pg_numrows ($setRslt);
	if ($num_settings < 1) {
		errDie ("No settings found in database!");
	}
	while ($mySet = pg_fetch_array ($setRslt)) {
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		if($mySet['label']=="Currency symbol") {
			$$mySet["constant"]=legal($$mySet["constant"]);
		} else  {
			$v->isOk ($$mySet["constant"], $mySet["datatype"], 1, 255, "Invalid setting: $mySet[label].");
		}
		$settings .= "
		<tr bgcolor='$bgColor'>
			<td>$mySet[label]</td>
			<td>".$$mySet["constant"]."</td>
		</tr>\n";
		$hidden_fields .= "<input type=hidden name='$mySet[constant]' value='".$$mySet["constant"]."'>\n";
		$i++;
	}

	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

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

	# display settings & new vals

	$confirmSet =
"
	<h3>Edit accounting settings</h3>

	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	$hidden_fields
	<tr><th>Field</th><th>Value</th></tr>
	$settings
	<td align=left></td><td valign=left><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmSet;
}

# Confirm entered info
function writeSettings ($_POST)
{
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	# get settings
	$sql_update = "";
	$i = 0;
	db_connect ();
	$sql = "SELECT * FROM settings WHERE type='accounting' AND (readonly='f'::bool) ORDER BY label";
	$setRslt = db_exec ($sql) or errDie ("Unable to select settings from database.");
	$num_settings = pg_numrows ($setRslt);
	if ($num_settings < 1) {
		errDie ("No settings found in database!");
	}
	while ($mySet = pg_fetch_array ($setRslt)) {
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		if($mySet['label']=="Currency symbol") {
			$$mySet["constant"]=legal($$mySet["constant"]);
		} else  {
			$v->isOk ($$mySet["constant"], $mySet["datatype"], 1, 255, "Invalid setting: $mySet[label].");
		}
		$i++;
	}

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

	# get these again for insertion
	$sql = "SELECT * FROM settings WHERE type='accounting' AND (readonly='f'::bool)";
	$setRslt = db_exec ($sql) or errDie ("Unable to select settings from database.");
	$num_settings = pg_numrows ($setRslt);
	if ($num_settings < 1) {
		errDie ("No settings found in database!");
	}
	while ($mySet = pg_fetch_array ($setRslt)) {
		# if setting is already set, skip iteration
		if ($mySet["value"] == $$mySet["constant"]) {
			continue;
		}
		$sql = "UPDATE settings SET value='".$$mySet["constant"]."' WHERE constant='$mySet[constant]'";
		$setRslt = db_exec ($sql) or errDie ("Unable to write new setting for $mySet[constant]");
		if (pg_cmdtuples ($setRslt) < 1) {
			errDie ("Unable to write new setting for $mySet[constant]");
		}
	}

	$sql = "UPDATE settings SET value='$sdlpayable' WHERE constant='SDLPAYABLE'";
	$rslt = db_exec($sql) or errDie("Error updating settings.");

	$sql = "UPDATE settings SET value='$emploan_int' WHERE constant='EMPLOAN_INT'";
	$rslt = db_exec($sql) or errDie("Error updating settings.");

	/*if($set=="Yes") {
		$Sl="INSERT INTO salset(name) VALUES ('CON')";
		$Ri=db_exec($Sl);
	} else {
		$Sl="DELETE FROM salset";
		$Ri=db_exec($Sl);
	}*/

	$writeSettings =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Accounting settings changed</th></tr>
<tr class=datacell><td>New accounting settings successfully committed to database.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeSettings;
}

function legal ($value)
{
	if(!isset($value)) {return "Invalid use of function";}
	$value = str_replace("!","",$value);
	$value = str_replace("=","",$value);
	$value = str_replace("#","",$value);
	$value = str_replace("%","",$value);
	$value = str_replace("*","",$value);
	$value = str_replace("^","",$value);
	$value = str_replace("?","",$value);
	$value = str_replace("[","",$value);
	$value = str_replace("]","",$value);
	$value = str_replace("{","",$value);
	$value = str_replace("}","",$value);
	$value = str_replace("|","",$value);
	$value = str_replace(":","",$value);
	$value = str_replace("+","",$value);
	$value = str_replace("'","",$value);
	$value = str_replace("`","",$value);
	$value = str_replace("~","",$value);
	$value = str_replace("\\","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace(";","",$value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);
	$value = str_replace("(","",$value);
	$value = str_replace(")","",$value);
	return $value;
}
?>

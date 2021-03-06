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

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "rem":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['div'])){
					$OUTPUT = confirm ($_GET['div']);
			} else {
					$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
        if (isset($_GET['div'])){
                $OUTPUT = confirm ($_GET['div']);
        } else {
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# get template
require("template.php");

# confirm
function confirm($div)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($div, "num", 1, 50, "Invalid Branch number.");

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
		$sql = "SELECT * FROM branches WHERE div = '$div'";
        $branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($branRslt) < 1){
			return "<li> Invalid Branch ID.";
        }else{
			$bran = pg_fetch_array($branRslt);
        }

		// Layout
		$confirm =
		"<h3>Remove Branch</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=rem>
		<input type=hidden name=div value='$div'>
			<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
			<tr class='bg-odd'><td>Branch Code</td><td>$bran[brancod]</td></tr>
			<tr class='bg-even'><td>Branch Name</td></td><td>$bran[branname]</td></tr>
			<tr class='bg-odd'><td valign=top>Details</td><td><pre>$bran[brandet]</pre></td></tr>
			<tr><td><br></td></tr>
			<tr><td align=right></td><td align=left><input type=submit value='Confirm &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='admin-branadd.php'>Add Branch</a></td></tr>
			<tr class='bg-odd'><td><a href='admin-branview.php'>View Branches</a></td></tr>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

		return $confirm;
}

# write
function rem($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($div, "num", 1, 50, "Invalid branch number.");

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

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM branches WHERE div = '$div'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Get stock vars
	foreach ($bran as $key => $value) {
		$$key = $value;
	}

	// Remove Branch
	db_connect();
	$sql = "DELETE FROM branches WHERE div = '$div'";
	$rslt = db_exec($sql) or errDie("Unable to remove branch from Cubit.",SELF);

	// Remove Branch Accounts and Categories
	core_connect();
	$remcore = array("accounts", "trial_bal", "income", "balance", "expenditure");
	foreach($remcore as $key => $table){
		rembran($table, $div);
	}

	// Remove other data
	db_connect();
	$remcubit = array("seq");
	foreach($remcubit as $key => $table){
		rembran($table, $div);
	}


	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Branch deleted</th></tr>
	<tr class=datacell><td>Branch, $branname ($brancod) has been successfully removed from Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='admin-branadd.php'>Add Branch</a></td></tr>
		<tr class='bg-odd'><td><a href='admin-branview.php'>View Branches</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}

function rembran($table, $div){
	$sql = "DELETE FROM $table WHERE div = '$div'";
	$rslt = db_exec($sql) or errDie("Unable to remove branch from Cubit.",SELF);
}
?>

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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
            	$OUTPUT = write($_POST);
				break;

			default:
				if (isset($_GET['deptid'])){
					$OUTPUT = rem ($_GET['deptid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['deptid'])){
			$OUTPUT = rem ($_GET['deptid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("../template.php");

function rem($deptid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($deptid, "num", 1, 50, "Invalid Department id.");

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
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
        $deptRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($deptRslt) < 1){
                return "<li> Invalid Department ID.";
        }else{
                $dept = pg_fetch_array($deptRslt);
        }

		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$dept[incacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$accinc = pg_fetch_array($accRslt);

		# get debtors account name
		$sql = "SELECT accname FROM accounts WHERE accid = '$dept[debtacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$accdebt = pg_fetch_array($accRslt);

		# get creditors account name
		$sql = "SELECT accname FROM accounts WHERE accid = '$dept[credacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acccred = pg_fetch_array($accRslt);


		$rem =
		"<h3>Confirm Remove Department</h3>
		<form action='".SELF."' method=post>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=write>
		<input type=hidden name=deptid value='$dept[deptid]'>
		<input type=hidden name=deptname value='$dept[deptname]'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Dept No</td><td>$dept[deptno]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$dept[deptname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Income Account</td><td>$accinc[accname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Debtors Control Account</td><td>$accdebt[accname]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Creditors Control Account</td><td>$acccred[accname]</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='dept-view.php'>View Department</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

	return $rem;
}

# write new data
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 50, "Invalid Department id.");

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
	$sql = "DELETE FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql) or errDie ("Unable to remove Department from system.", SELF);
	if (pg_cmdtuples ($deptRslt) < 1) {
		return "<li class=err>Unable to remove Department from database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Department Removed</th></tr>
	<tr class=datacell><td>Department <b>$deptname</b>, has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='dept-view.php'>View Departments</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

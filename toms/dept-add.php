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
require("../core-settings.php");
# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("../template.php");

# enter new data
function enter ()
{

	# connect to db
	core_connect ();
	$depacc= "<select name='incacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype = 'I' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Income accounts yet in Cubit.";
		}else{
				while($acc = pg_fetch_array($accRslt)){
				if(isb($acc['accid'])) {
					continue;
				}
						$depacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$depacc .="</select>";

	$debtacc= "<select name='debtacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Balance accounts yet in Cubit.";
		}else{
				while($acc = pg_fetch_array($accRslt)){
					$debtacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$debtacc .="</select>";

	$credacc= "<select name='credacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Balance accounts yet in Cubit.";
		}else{
				while($acc = pg_fetch_array($accRslt)){
						$credacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$credacc .="</select>";
	
	//Get account for Point of sale income account
	$pias= "<select name='pia'>";
		$sql = "SELECT accid,accname FROM accounts WHERE acctype = 'I' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Income accounts in Cubit.";
		}else{
				while($acc = pg_fetch_array($accRslt)){
				if(isb($acc['accid'])) {
					continue;
				}
						$pias .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$pias .="</select>";
	
	//Get account for Point of Sale Cash on hand account
	$pcas= "<select name='pca'>";
		$sql = "SELECT accid,accname FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Income accounts in Cubit.";
		}else{
				while($acc = pg_fetch_array($accRslt)){
						$pcas .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$pcas .="</select>";
	
	$enter =
	"<h3>Add Department</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Dept Number</td><td><input type=text size=10 name=deptno></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td><input type=text size=20 name=deptname></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Income Account</td><td>$depacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Debtors Control Account</td><td>$debtacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Creditors Control Account</td><td>$credacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Point of Sale: Cash on hand account</td><td>$pcas</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Point of Sale Income Account</td><td>$pias</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='dept-view.php'>View Departments</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../core/acc-new2.php'>Add Account</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
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
	$v->isOk ($deptno, "num", 1, 10, "Invalid Department number.");
	$v->isOk ($deptname, "string", 1, 255, "Invalid Department name.");
	$v->isOk ($incacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($debtacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($credacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($pia, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($pca, "num", 1, 20, "Invalid Account number.");


	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# get income account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$incacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$accinc = pg_fetch_array($accRslt);

	# get debtors account name
	$sql = "SELECT accname FROM accounts WHERE accid = '$debtacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$accdebt = pg_fetch_array($accRslt);

	# get creditors account name
	$sql = "SELECT accname FROM accounts WHERE accid = '$credacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acccred = pg_fetch_array($accRslt);
	
	# get POS income account name
	$sql = "SELECT accname FROM accounts WHERE accid = '$pia' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$posinc = pg_fetch_array($accRslt);

	# get POS cash on hand account name
	$Sl = "SELECT accname FROM accounts WHERE accid = '$pca' AND div = '".USER_DIV."'";
	$Rs = db_exec($Sl);
	$poscash = pg_fetch_array($Rs);
	
	$confirm =
	"<h3>Confirm Department</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=deptno value='$deptno'>
	<input type=hidden name=deptname value='$deptname'>
	<input type=hidden name=incacc value='$incacc'>
	<input type=hidden name=debtacc value='$debtacc'>
	<input type=hidden name=credacc value='$credacc'>
	<input type=hidden name=pia     value='$pia'>
	<input type=hidden name=pca     value='$pca'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Dept Number</td><td>$deptno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$deptname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Income Account</td><td>$accinc[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Debtors Control Account</td><td>$accdebt[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Creditors Control Account</td><td>$acccred[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Point of Sale: Cash on hand account</td><td>$poscash[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Point of Sale Income Account</td><td>$posinc[accname]</td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='dept-view.php'>View Department</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../core/acc-new2.php'>Add Account</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($deptno, "num", 1, 10, "Invalid Department number.");
	$v->isOk ($deptname, "string", 1, 255, "Invalid Department name.");
	$v->isOk ($incacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($debtacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($credacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($pia, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($pca, "num", 1, 20, "Invalid Account number.");

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
	$sql = "INSERT INTO departments(deptno, deptname, incacc, debtacc, credacc, pia, pca, div) VALUES ('$deptno', '$deptname', '$incacc', '$debtacc', '$credacc', '$pia', '$pca', '".USER_DIV."')";
	$deptRslt = db_exec ($sql) or errDie ("Unable to add deparment to system.", SELF);
	if (pg_cmdtuples ($deptRslt) < 1) {
		return "<li class=err>Unable to add deparment to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Department added to system</th></tr>
	<tr class=datacell><td>New Department <b>$deptname</b>, has been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='dept-view.php'>View Departments</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../core/acc-new2.php'>Add Account</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

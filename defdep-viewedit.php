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

require ("settings.php");


if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "edit":
			if(isset($HTTP_POST_VARS["accounts"])){
				$OUTPUT = accounts ($HTTP_POST_VARS);
			}else{
				$OUTPUT = edit ($HTTP_POST_VARS);
			}
			break;

		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;

		default:
			$OUTPUT = slctDep ();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slctDep();
}

require ("template.php");


# Default view
function slctDep()
{

# Set up table to display in
	$printDep = "<h3>Company Types For Default Accounts</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=edit>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Company Types</th></tr>";

	# connect to database
	core_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM defdep ORDER BY depname ASC";
    $depRslt = db_exec ($sql) or errDie ("Unable to retrieve default company types from database.");
	if (pg_numrows ($depRslt) < 1) {
		return "<li>There are default company types in Cubit.";
	}
	$printDep .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><select name=depid size=5>";

	while ($dep = pg_fetch_array ($depRslt)) {
		# get number of accounts
		$sql = "SELECT count(accname) FROM defacc WHERE depid = '$dep[depid]'";
		$cRslt = db_exec($sql);
		$count = pg_fetch_array($cRslt);

		# view in a select mode
		$printDep .= "<option value='$dep[depid]'>$dep[depname] ($count[count])</option>";
		$i++;
	}

	$printDep .= "</select></td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'><input type=submit value='Edit &raquo'><input type=submit name=accounts value='View Accounts &raquo'></td></tr>
	</table></form>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printDep;
}

# show stock
function edit ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($depid, "num", 1, 50, "Invalid Department ID.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$confirm = "<center>
    <h3>Edit Company Type</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=write>
	<input type=hidden name=depid value='$depid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Field</th><th>Value</th></tr>";

		# connect to database
		core_connect ();

		# Query server
		$sql = "SELECT * FROM defdep WHERE depid = '$depid'";
		$depRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($depRslt) < 1) {
			return "<li> Invalid Company Type ID.";
		}
		$dep = pg_fetch_array ($depRslt);

	$confirm .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td><input type=text name=depname size=30  value='$dep[depname]'></td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($depid, "num", 1, 50, "Invalid Company type ID.");
	$v->isOk ($depname, "string", 1, 255, "Invalid Company type.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

		# begin sql transaction
		core_connect();

		# Write to db
        $sql = "UPDATE defdep SET depname = '$depname' WHERE depid = '$depid'";
		$depRslt = db_exec ($sql) or errDie ("Unable to update defdep on Database.");

		return slctDep();
}

// View accounts
function accounts ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($depid, "num", 1, 50, "Invalid Department ID.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$confirm = "<center>
    <h3>Company Type Default Accounts</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><td align=center><h4>Income Accounts</h4></td><td align=center><h4>Expenditure Accounts</h4></td><td align=center><h4>Balance Sheet Accounts</h4></td></tr>
	<tr><td valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Account Number</th><th>Account Name</th></tr>";

		# connect to database
		core_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_INC." AND topacc <= ".MAX_INC." ORDER BY accname ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no stock items in the selected category.";
		}
		while ($acc = pg_fetch_array ($accRslt)) {

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$confirm .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td></tr>";
			$i++;
		}

		$confirm .= "
		</table>
		</td><td valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Account Number</th><th>Account Name</th></tr>";

		# connect to database
		core_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_EXP." AND topacc <= ".MAX_EXP." ORDER BY accname ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no stock items in the selected category.";
		}
		while ($acc = pg_fetch_array ($accRslt)) {

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$confirm .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td></tr>";
			$i++;
		}

		$confirm .= "
		</table>
	</td><td valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Account Number</th><th>Account Name</th></tr>";

		# connect to database
		core_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_BAL." AND topacc <= ".MAX_BAL." ORDER BY accname ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no stock items in the selected category.";
		}
		while ($acc = pg_fetch_array ($accRslt)) {

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$confirm .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td></tr>";
			$i++;
		}

		$confirm .= "
		</table>
	<td></td></tr>
	<tr><td></td><td align=center><input type=button value='&laquo Back' onClick='javascript:history.back();'></td></tr>
	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}
?>

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
//dont know why this is here ... just causes quick links not to work ...
i_am_the_true_xml();

$OUTPUT = printUsers ();

require ("template.php");



function printUsers ()
{

	$OUTPUT = "
		<h3>View current users</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Username</th>
				<th>Employee</th>
				<th>Branch</th>
				<th colspan='2'>Options</th>
			</tr>";

	$sql = "SELECT username, div, empnum FROM cubit.users";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve employee.");

	while ($row = pg_fetch_array($user_rslt)) {
		$sql = "SELECT enum, sname, fnames FROM cubit.employees
				WHERE empnum='$row[empnum]'";
		$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employee.");
		$emp_data = pg_fetch_array($emp_rslt);
		
		$employee = "$emp_data[sname] $emp_data[fnames] - $emp_data[enum]";

		$bran = qryBranch($row["div"]);

		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$row[username]</td>
				<td>$employee</td>
				<td>$bran[branname]</td>
				<td><a href='admin-usredit.php?username=$row[username]'>Edit</a></td>
				<td><a href='admin-usrrem.php?username=$row[username]'>Remove</a></td>
			</tr>";
	}

	$OUTPUT .= "</table>";

//	$OUTPUT .= mkQuickLinks(
//		ql ("","Add User")
//	);
	return $OUTPUT;

}


?>
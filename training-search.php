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
# admin-employee-rem.php :: Remove employees from db
##

require ("settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "search":
			$OUTPUT = do_search();
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
} else {
	$OUTPUT = get_search ();
}

require ("template.php");

##
# Functions
##

function get_search ()
{

	$display = "
			<h2>Search for Training</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post>
				<input type=hidden name=key value='search'>
				<tr>
					<th>Search String</th>
				</tr>
				<tr>
					<td><input type=text name=search_string size='50'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type=submit value='Search'></td>
				</tr>
			</form>
			</table>
		";
	return $display;

}


function do_search ()
{

	global $_POST;
	extract ($_POST);

	db_connect ();

	$search_sql = "SELECT * FROM training WHERE course_name LIKE '%$search_string%' OR other_details LIKE '%$search_string%' LIMIT 25";
	$run_search = db_exec($search_sql);
	if(pg_numrows($run_search) < 1){
		$results = "";
	}else {
		$results = "";
		$i = 0;
		while ($tarr = pg_fetch_array($run_search)){

			$empval = $tarr['empnum'];
			$tarr['empnum'] = $empval + 0;

			#get this employee name
			$get_emp = "SELECT fnames,sname FROM employees WHERE empnum = '$tarr[empnum]' LIMIT 1";
			$run_emp = db_exec($get_emp);
			if(pg_numrows($run_emp) < 1){
				$employee_name = "Unknown";
			}else {
				$earr = pg_fetch_array($run_emp);
				$employee_name = "$earr[fnames] $earr[sname]";
			}

			$results .= "
					<tr class='".bg_class()."'>
						<td>$employee_name</td>
						<td>$tarr[course_name]</td>
						<td>$tarr[date_date]</td>
						<td>$tarr[commence_date]</td>
						<td>$tarr[completed_date]</td>
						<td>$tarr[competent_date]</td>
						<td>".nl2br($tarr['other_details'])."</td>
					</tr>";
			$i++;
		}
	}

	$display = "
			<h2>Search Results</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
				<tr>
					<th>Employee</th>
					<th>Course Name</th>
					<th>Enter Date</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Competent Date</th>
					<th>Other Details</th>
				</tr>
				$results
			</table>
		";
	return $display;

}











?>

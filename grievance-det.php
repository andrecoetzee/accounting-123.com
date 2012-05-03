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


$OUTPUT = show_grievance ();


require ("template.php");

##
# Functions
##

function show_grievance ($err = "")
{

	global $_GET;

	if(!isset($_GET["grievnum"])){
		return "Invalid selected grievance.";
	}


	# Connect to db
	db_connect ();

	#get grievance info
	$get_griev = "SELECT * FROM grievances WHERE grievnum = '$_GET[grievnum]' LIMIT 1";
	$run_griev = db_exec($get_griev);
	if(pg_numrows($run_griev) < 1){
		return "gfrievance information not found";
	}else {
		$garr = pg_fetch_array($run_griev);

		extract ($garr);
	}

	# Get employees from db
	$employees = "";
	$i = 0;

	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' AND empnum = '$empnum' LIMIT 1";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Employee not found in database.<p>
	        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        	<tr><th>Quick Links</th></tr>
	        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}else {
		$myEmp = pg_fetch_array($empRslt);
	}

	$employee = "$myEmp[sname], $myEmp[fnames]";


	# Set up table & form
	$enterEmp = "
			<h2>View Grievance</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post>
				<tr>
					<th>Staff Member</th>
				</tr>
				<tr class='bg-odd'>
					<td>$employee</td>
				</tr>
				<tr><td><br></td></tr>
				<tr><th colspan='2'>Date First Recorded</th></tr>
				<tr class='bg-odd'><td colspan='2'>$first_rec_date</td></tr>
				<tr><th colspan='2'>Details of grievance</th></tr>
				<tr class='bg-odd'><td colspan='2'>".nl2br($griev_details)."</td></tr>
				<tr><th colspan='2'>Status Of Grievance</th></tr>
				<tr class='bg-odd'><td>Company: </td><td>$company_date</td></tr>
				<tr class='bg-even'><td>CCMA: </td><td>$ccma_date</td></tr>
				<tr class='bg-odd'><td>CCMA Appeal: </td><td>$ccma_app_date</td></tr>
				<tr class='bg-even'><td>Court: </td><td>$court_date</td></tr>
				<tr class='bg-odd'><td>Court Appeal: </td><td>$court_app_date</td></tr>
				<tr><td><br></td></tr>";

				#get the inputs for this grievances
				$get_inp = "SELECT * FROM grievance_items WHERE grievnum ='$grievnum' AND div = '".USER_DIV."'";
				$run_inp = db_exec ($get_inp);
				if(pg_numrows($run_inp) < 1){
					#do nothing
				}else {
					$glisting = "";
					$i = 0;

					#prepare enteremp for new table with list
					$enterEmp .= "
							</table>
							<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
							<tr><th>Input</th><th>Date Added</th></tr>";
					while ($garr2 = pg_fetch_array($run_inp)){
						$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
						$enterEmp .= "<tr bgcolor='$bgColor'><td>".nl2br($garr2['input'])."</td><td>$garr2[date_added]</td></tr>";
						$i++;
					}
					$enterEmp .= "</table><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>";
				}

				$enterEmp .= "
						<tr><td><br></td></tr>
						<tr><td><input type='button' onClick=\"window.location='grievances-view.php'\" value='Back'></td></tr>
			</form>
			</table>";

	return $enterEmp;


}

?>

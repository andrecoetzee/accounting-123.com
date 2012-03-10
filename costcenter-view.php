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

# show current stock
$OUTPUT = printCenter ();

$OUTPUT .= "
				<p>
				<table border='0' cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-add.php'>Add Cost Center</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='costcenter-view.php'>View Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='projects-edit.php'>Manage Project Cost Centers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>
			";

require ("template.php");

# show stock
function printCenter ()
{
	# Set up table to display in
	$printCenter = "
				<h3>Current Cost Centers</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Cost Center Code</th>
						<th>Cost Center Name</th>
						<th colspan='2'>Options</th>
					</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM costcenters WHERE div = '".USER_DIV."' ORDER BY centername ASC";
    $ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost Centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'>There are no Cost Centers in Cubit.</li>";
	}
	while ($cc = pg_fetch_array ($ccRslt)) {

		$flag = TRUE;
		#check if cost center has any trans
		for ($x=1;$x<=14;$x++){
			db_conn($x);
			$get_check = "SELECT * FROM cctran WHERE ccid = '$cc[ccid]' LIMIT 1";
			$run_check = db_exec($get_check) or errDie("Unable to get cost center information.");
			if(pg_numrows($run_check) > 0){
				$flag = FALSE;
			}
		}


		if($flag == TRUE){
			$showrem = "<a href='costcenter-rem.php?ccid=$cc[ccid]'>Remove</a>";
		}else {
			$showrem = "";
		}


		$printCenter .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$cc[centercode]</td>
						<td>$cc[centername]</td>
						<td><a href='costcenter-edit.php?ccid=$cc[ccid]'>Edit</a>
						<td>$showrem</td>
					</td>";

		/*
		db_conn(PRD_DB);
		$sql = "SELECT * FROM cctran WHERE ccid='$cc[ccid]'";
    	$ccsRslt = db_exec ($sql) or ereDie ("Unable to retrieve Cost center entries from database.");
		if (pg_numrows ($ccsRslt) < 1) {
			$printCenter .= "<td><a href='costcenter-rem.php?ccid=$cc[ccid]'>Remove</a></td></tr>";
		}else{
			$printCenter .= "</tr>";
		}
		*/

		$printCenter .= "</tr>";
		$i++;
	}

	$printCenter .= "</table>";

	return $printCenter;
}
?>

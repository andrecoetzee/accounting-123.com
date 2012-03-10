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

require("settings.php");
require("libs/crm.lib.php");

	$OUTPUT=list_teams();

	$OUTPUT .= "
					<p>
					<table border=0 cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='team-add.php'>Add Cubt Team</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='team-list.php'>View Cubit Teams</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='index.php'>My Business</a></td>
						</tr>
					</table>";

require("template.php");




function list_teams()
{

	db_conn('crm');

	$Sl="SELECT * FROM teams WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to list teams.");

	if(pg_numrows($Ry)<1) {
		dt();
		$Sl="SELECT * FROM teams WHERE div='".USER_DIV."' ORDER BY name";
		$Ry=db_exec($Sl) or errDie("Unable to list teams.");
	}

	$out = "
				<h3>Cubit Teams Listing</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Name</th>
						<th>Description</th>
						<th colspan='4'>Options</th>
					</tr>";

	$i=0;

	while($teamdata=pg_fetch_array($Ry)) {
		$i++;

		$out .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$teamdata[name]</td>
						<td>$teamdata[des]</td>
						<td><a href='team-alloc.php?id=$teamdata[id]'>Allocate Users</a></td>
						<td><a href='team-links.php?id=$teamdata[id]'>Select Links</a></td>
						<td><a href='team-edit.php?id=$teamdata[id]'>Edit</a></td>
						<td><a href='team-rem.php?id=$teamdata[id]'>Remove</a></td>
					</tr>";

	}

	$out .= "</table>";
	return $out;

}



?>
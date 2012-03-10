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

require("../settings.php");

if(isset($HTTP_POST_VARS["delete"])) {
	$OUTPUT = write($HTTP_POST_VARS);
} elseif(isset($HTTP_GET_VARS["id"])) {
	$OUTPUT = confirm($HTTP_GET_VARS);
} else {
	$OUTPUT = "Invalid.";
}

$OUTPUT .= "
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='import-settings.php'>Statement Import Settings</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='../main.php'>Main Menu</a></td>
		</tr>
	</table>";

require("../template.php");



function confirm($HTTP_GET_VARS)
{

	extract($HTTP_GET_VARS);
	
	$id += 0;
	
	db_conn('cubit');
	
	$Sl = "SELECT * FROM statement_refs WHERE id='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");
	
	$rd = pg_fetch_array($Ri);
	
	$out = "
		<h3>Delete description</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='delete' value='no'>
			<input type='hidden' name='id' value='$id'>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td>$rd[ref]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>+/-</td>
				<td>$rd[pn]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td colspan=2 align=right><input type=submit value='Delete &raquo;'></td></tr>
		</form>
		</table>";
	return $out;

}



function write($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);
	
	$id += 0;
	
	db_conn('cubit');
	
	$Sl = "DELETE FROM statement_refs WHERE id='$id'";
	$Ri = db_exec($Sl) or errDie("Unable to delete data.");
	
	$out = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Done</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description deleted</td>
			</tr>
		</table>";
	return $out;
	
}



?>
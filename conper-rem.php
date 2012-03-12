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

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "rem":
			$OUTPUT = rem($_POST);
			break;
		default:
			$OUTPUT ="Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT =  "Invalid .";
}

require("template.php");

function enter($_GET) {
	extract($_GET);

	$id+=0;

	db_conn('cubit');
	$Sl="SELECT * FROM conpers WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get con info.");

	if(pg_num_rows($Ry)<1) {
		return "Invalid contact.";
	}

	$data=pg_fetch_array($Ry);

	$Sl="SELECT * FROM cons WHERE id='$data[con]'";
	$Ry=db_exec($Sl) or errDie("Unable to get con info.");

	if(pg_num_rows($Ry)<1) {
		return "Invalid contact.";
	}

	$cdata=pg_fetch_array($Ry);
	$mainname=$cdata['surname'];

	extract($data);

	$out ="<h3>Delete Contact at $mainname</h3>
	<br>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=rem>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Personal details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Main Contact</td><td>$mainname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td align=center>$name</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Position</td><td align=center>$pos</td></tr>
	<tr><th colspan=2>Contact details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone</td><td align=center>$tell</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Cellphone</td><td align=center>$cell</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Facsimile</td><td align=center>$fax</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td align=center>$email</td></tr>
	<tr><th colspan=2>Notes</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><pre>$notes</pre></td></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Delete &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='list_cons.php'>List contacts</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

        return $out;
}

function rem($_POST) {
	extract($_POST);

	$id+=0;

	db_conn('cubit');
	$Sl="DELETE FROM conpers WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unabel to update contact.");

	$out ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Deleted contact person</th></tr>
	<tr class=datacell><td>Contact has been deleted from Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $out;
	}

?>
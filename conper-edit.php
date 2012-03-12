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
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT ="Invalid";
	}
} elseif(isset($_GET["id"])&&isset($_GET["type"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT =  "Invalid .";
}

require("template.php");

function enter($_GET) {
	extract($_GET);

	$id+=0;

	if(isset($type)) {

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

	} else {
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

	}


	$out ="<h3>Edit Contact at $mainname</h3>
	<br>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Personal details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Main Contact</td><td>$mainname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td align=center><input type=text size=27 name=name value='$name'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Position</td><td align=center><input type=text size=27 name=pos value='$pos'></td></tr>
	<tr><th colspan=2>Contact details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone</td><td align=center><input type=text size=27 name=tell value='$tell'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Cellphone</td><td align=center><input type=text size=27 name=cell value='$cell'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Facsimile</td><td align=center><input type=text size=27 name=fax value='$fax'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td align=center><input type=text size=27 name=email value='$email'></td></tr>
	<tr><th colspan=2>Notes</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><TEXTAREA name=notes rows=4 cols=35>$notes</TEXTAREA></td></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
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

function confirm($_POST) {
	extract($_POST);

	$id+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($name, "string", 1, 100, "Invalid name.");
	$v->isOk ($pos, "string", 0, 100, "Invalid position.");
	$v->isOk ($tell, "string", 0, 100, "Invalid tel.");
	$v->isOk ($cell, "string", 0, 100, "Invalid cel.");
	$v->isOk ($fax, "string", 0, 100, "Invalid fax.");
	$v->isOk ($email, "email", 0, 100, "Invalid email.");
	$v->isOk ($notes, "string", 0, 200, "Invalid notes.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return $confirm."</li>".enter($_POST);
	}

	$out ="<h3>Edit Contact</h3>
	<br>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Personal details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td align=center><input type=hidden name=name value='$name'>$name</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Position</td><td align=center><input type=hidden name=pos value='$pos'>$pos</td></tr>
	<tr><th colspan=2>Contact details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone</td><td align=center><input type=hidden name=tell value='$tell'>$tell</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Cellphone</td><td align=center><input type=hidden name=cell value='$cell'>$cell</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Facsimile</td><td align=center><input type=hidden name=fax value='$fax'>$fax</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td align=center><input type=hidden name=email value='$email'>$email</td></tr>
	<tr><th colspan=2>Notes</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><input type=hidden name=notes value='$notes'><pre>$notes</pre></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
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

function write($_POST) {
	extract($_POST);

	$id+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($name, "string", 1, 100, "Invalid name.");
	$v->isOk ($pos, "string", 0, 100, "Invalid position.");
	$v->isOk ($tell, "string", 0, 100, "Invalid tel.");
	$v->isOk ($cell, "string", 0, 100, "Invalid cel.");
	$v->isOk ($fax, "string", 0, 100, "Invalid fax.");
	$v->isOk ($email, "email", 0, 100, "Invalid email.");
	$v->isOk ($notes, "string", 0, 200, "Invalid notes.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return $confirm."</li>".enter($_POST);
	}

	db_conn('cubit');
	$Sl="UPDATE conpers SET name='$name',pos='$pos',tell='$tell',cell='$cell',fax='$fax',email='$email',notes='$notes' WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unabel to update contact.");

	$out ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Contact update</th></tr>
	<tr class=datacell><td>$name has been updated in Cubit.</td></tr>
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












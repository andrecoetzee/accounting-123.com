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



 if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = view_cons ($_POST);
			break;
		default:
			$OUTPUT = get_data ();
	}
} else {
	$OUTPUT = get_data ();
}

require ("template.php");

 function get_data ()
{
 $Sorts=" <select size=1 name=Sort>\n;
       <option value='name'>Name</option>
       <option value='surname'>Surname</option>
        <option value='comp'>Company</option>
       <option value='ref'>Refrence</option>
       <option value='tell'>Telephone</option>
       <option value='cell'>Cellphone</option>
       <option value='fax'>Fax</option>
       </select>";


	$get_data =
"

<h3>Find Contact</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<tr><th colspan=2>Search for</th></tr>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td align=center>$Sorts</td><td align=center><input type=text size=27 name=flag></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $get_data;
}

function view_cons ($_POST)
{


        foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        require_lib("validate");
	$v = new  validate ();

        $v->isOk ($Sort,"string", 1,100, "Invalid field.");
        $v->isOk ($flag,"string", 0,100, "Invalid value.");

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


        // Connect to database
        db_conn('cubit');
        $user=USER_NAME;

	$Sql = "SELECT name,surname,comp,ref,id FROM cons WHERE (($Sort='$flag')  AND div = '".USER_DIV."' and ((con='Yes' and by='$user') or(con='No'))) AND div = '".USER_DIV."' ORDER BY $Sort";
	$Rslt = db_exec ($Sql) or errDie ("Unable to access database");
	$numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		$OutPut = "Contact not found.<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr class='bg-odd'><td><a href='find_con.php'>Find other</a></td></tr>
	<tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	} else {


		$OutPut = "
		<h3>Contact List</h3>

		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Name</th><th>Surname</th><th>Company</th><th>Refrence</th> <th colspan=3>Options</th></tr>
		";


		for ($i=0; $i < $numrows; $i++) {
			$Data = pg_fetch_array($Rslt);
			$OutPut .= "<tr class='".bg_class()."'><td>$Data[name]</td><td>$Data[surname]</td><td>$Data[comp]</td><td>$Data[ref]</td><td><a href='view_con.php?id=$Data[id]'>View</a></td><td><a href='mod_con.php?id=$Data[id]'>Edit</a></td><td><a href='rem_con.php?id=$Data[id]'>Remove</td></tr>\n";
		}
		$OutPut .= "</table>\n

                <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr class='bg-odd'><td><a href='find_con.php'>Find other</a></td></tr>
	<tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";
	}

	// call template to display the info and die
	return $OutPut;
}

?>

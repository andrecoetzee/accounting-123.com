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
# get settings
require ("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
            	$OUTPUT = write($_POST);
				break;

			default:
				if (isset($_GET['id'])){
					$OUTPUT = edit ($_GET['id']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($_GET['id'])){
			$OUTPUT = edit ($_GET['id']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("template.php");

function edit($id)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 50, "Invalid Classification id.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM vatcodes WHERE id = '$id'";
		$clasRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($clasRslt) < 1){
			return "<li> Invalid code.";
		}else{
			$clas = pg_fetch_array($clasRslt);
		}

// 		if($clas['zero']=="Yes") {
// 			$ch="checked=yes";
// 		} else {
// 			$ch="";
// 		}

//	<tr bgcolor='".TMPL_tblDataColor1."'><td>Zero VAT</td><td align=center><input type=checkbox name=zero $ch></td></tr>
	$enter =
	"<h3>Edit VAT Code</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$clas[id]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Code</td><td align=center><input type=text size=20 name=code value='$clas[code]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td align=center><input type=text size=20 name=description value='$clas[description]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Percentage</td><td align=center><input type=text size=10 name=vat_amount value='$clas[vat_amount]'></td></tr>

	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
 	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='vatcodes-view.php'>View VAT Codes</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($code, "string", 1, 255, "Invalid vat code.");
	$v->isOk ($description, "string", 1, 255, "Invalid description");
	$v->isOk ($id, "num", 1, 50, "Invalid id.");
	$v->isOk ($vat_amount, "float", 1, 255, "Invalid VAT percentage.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	if($vat_amount == "0" OR ($vat_amount == "0.00")) {
		$zero="Yes";
	} else {
		$zero="No";
	}
	
// 	if(isset($zero)) {
// 		$zero="Yes";
// 	} else {
// 		$zero="No";
// 	}

	# check stock code
	db_connect();
	$sql = "SELECT code FROM vatcodes WHERE lower(code) = lower('$code') AND id != '$id'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err> A code with code : <b>$code</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

//	<tr bgcolor='".TMPL_tblDataColor1."'><td>Zero VAT</td><td><input type=hidden name=zero value='$zero'>$zero</td></tr>

	$confirm =
	"<h3>Confirm Edit VAT Code</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=code value='$code'>
	<input type=hidden name=description value='$description'>
	<input type=hidden name=vat_amount value='$vat_amount'>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=zero value='$zero'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Code</td><td align=center>$code</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td align=center>$description</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Percentage</td><td>$vat_amount</td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='vatcodes-view.php'>View VAT Codes</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($code, "string", 1, 255, "Invalid code.");
	$v->isOk ($description, "string", 1, 255, "Invalid description.");
	$v->isOk ($id, "num", 1, 50, "Invalid id.");
	$v->isOk ($vat_amount, "float", 1, 255, "Invalid VAT percentage.");

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

	$zero=remval($zero);

	# connect to db
	db_connect ();

	# write to db
	$sql = "UPDATE vatcodes SET code = '$code', description = '$description', zero='$zero', vat_amount = '$vat_amount' WHERE id = '$id'";
	$clasRslt = db_exec ($sql) or errDie ("Unable to edit classification on system.", SELF);
	if (pg_cmdtuples ($clasRslt) < 1) {
		return "<li class=err>Unable to edit vat code.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>VAT Code edited</th></tr>
	<tr class=datacell><td>VAT Code <b>$code</b>, has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='vatcodes-view.php'>View VAT Codes</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>

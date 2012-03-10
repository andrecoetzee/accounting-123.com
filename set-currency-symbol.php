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

if ($HTTP_POST_VARS) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter();
        }
} else {
	$OUTPUT = enter();
}

require ("template.php");

##
# functions
##

# Enter settings
function enter()
{
	# Connect to db
	$enter = "
		<h3>Cubit Settings</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Set Currency Symbol</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' size='20' name='cur' value='rand' checked='yes'></td>
				<td>(RSA) Rand - R</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' size='20' name='cur' value='dollar'></td>
				<td>(USA) Dollar - $</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' size='20' name='cur' value='pound'></td>
				<td>(UK) Pound - &#163</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' size='20' name='cur' value='euro'></td>
				<td>(EU) Euro - &#8364</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' size='20' name='cur' value='other'></td>
				<td><input type='text' size='4' name='ocur'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right' colspan='2'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $enter;

}



# confirm entered info
function confirm($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	// $v->isOk ($typ, "string", 1, 50, "Invalid Vat type Selection.");

	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class='err' colspan='2'>$theseErrors</td></tr>
		<tr><td colspan='2'><br></td></tr>";
		return entererr($accc, $Errors);
	}

	if($cur == "other"){
		$cur = $ocur;
	}

	switch ($cur) {
		case "rand":
			$showcur = "R";
			break;
		case "dollar":
			$showcur = "$";
			break;
		case "pound":
			$showcur = "&#163";
			break;
		case "euro":
			$showcur = "&#8364";
			break;
		default:
		case "other":
			$showcur = $ocur;
	}

	$confirm = "
		<h3>Cubit Settings</h3>
		<h4>Confirm</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='cur' value='$cur'>
			<tr>
				<th colspan>Currency Symbol</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$showcur</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right' colspan='2'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write to db
function write ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	// $v->isOk ($typ, "string", 1, 50, "Invalid Vat type Selection.");

	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class='err' colspan='2'>$theseErrors</td></tr>
		<tr><td colspan=2><br></td></tr>";
		return entererr($accc, $Errors);
	}

	# Connect to db
	db_connect ();

// ISO-8859-1
// UTF-8
// cp1251
// Windows-1252


//<td>(RSA) Rand - R</td>
//<td>(USA) Dollar - $</td>
//<td>(UK) Pound - &#163</td>
//<td>(EU) Euro - &#8364</td>
//<td><input type='text' size='4' name='ocur'></td>

	switch ($cur) {
		case "rand":
			$writecur = "R";
			break;
		case "dollar":
			$writecur = "$";
			break;
		case "pound":
			$writecur = "&#163";
			break;
		case "euro":
			$writecur = "&#8364";
			break;
		default:
		case "other":
			$writecur = $ocur;
	}

	# Check if setting exists
	$sql = "SELECT constant FROM settings WHERE constant='CURRENCY'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE settings SET value='$writecur' WHERE constant='CURRENCY'";
	}else{
		$Sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div
			) VALUES (
				'CURRENCY', 'Currency symbol', '$writecur', 'accounting', 'allstring', 1, 100, 0
			)";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert settings to Cubit.");

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Cubit Settings</th>
			</tr>
			<tr class='datacell'>
				<td>Setting have been successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<tr>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $write;
}
?>

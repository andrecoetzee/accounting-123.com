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

if ($_POST) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
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
		<h3>Add Currency</h3>
		<script>
			function setSymbolOther() {
				radio = document.curform.cur;
				len = radio.length;

				for(i = 0; i < len; i++) {
					if(radio[i].value == 'other') {
						radio[i].checked = true;
					} else {
						radio[i].checked = false;
					}
				}
			}
		</script>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='curform'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Currency Symbol</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='cur' value='rand' checked='yes'></td>
				<td>(ZAR) Rand - R</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='cur' value='dollar'></td>
				<td>(USD) Dollar - $</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='cur' value='pound'></td>
				<td>(GBP) Pound - &#163</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='cur' value='euro'></td>
				<td>(EUR) Euro - &#8364</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' size='20' name='cur' value='other'></td>
				<td><input type='text' size='4' name='ocur' onKeyDown='setSymbolOther();'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr class='".bg_class()."'>
				<td>Currency Name</td>
				<td><input type='text' size='20' maxlength='20' name='descrip'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Currency Code</td>
				<td><input type='text' size='20' maxlength='20' name='curcode'></td>
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
			<tr class='".bg_class()."'>
				<td><a href='currency-view.php'>View Currency</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $enter;

}



# confirm entered info
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($descrip, "string", 1, 20, "Invalid Currency name.");
	$v->isOk ($curcode, "string", 0, 3, "Invalid Currency code.");

	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

// 	if($cur == "other"){
// 		$cur = $ocur;
// 	}

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

	db_connect ();

	$Sl = "SELECT * FROM currency WHERE symbol='$showcur' OR descrip='$descrip'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$data = pg_fetch_array($Ri);

		return "
			<li class='err'>The following currency is already in Cubit. Symbol:$data[symbol] Description: $data[descrip]</li><p>
			<tr>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='".SELF."'>Add Currency</a></td>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='currency-view.php'>View Currency</a></td>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

	}

	$confirm = "
		<h3>Confirm Currency</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='curcode' value='$curcode'>
			<input type='hidden' name='cur' value='$cur'>
			<input type='hidden' name='descrip' value='$descrip'>
			<tr>
				<th colspan>Currency Symbol</th>
			</tr>
			<tr class='".bg_class()."'><td align='center'>$showcur</td></tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan>Currency Name</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$descrip</td>
			</tr>
			<tr>
				<th colspan>Currency Code</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$curcode</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td align='right' colspan='2'><input type='submit' value='Confirm &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='currency-view.php'>View Currency</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}



# write to db
function write ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($descrip, "string", 1, 20, "Invalid Currency name.");
	$v->isOk ($curcode, "string", 0, 3, "Invalid Currency code.");

	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
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

	# Connect to db
	db_connect ();

	$Sl = "SELECT * FROM currency WHERE symbol='$showcur' OR descrip='$descrip' OR curcode='$curcode'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$data = pg_fetch_array($Ri);
		return "
			<li class='err'>The following currency is already in Cubit. Symbol:$data[symbol] Description: $data[descrip]</li><p>
			<tr>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='".SELF."'>Add Currency</a></td>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='currency-view.php'>View Currency</a></td>
				</tr>
				<tr bgcolor='#88BBFF'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}



	$Sql = "
		INSERT INTO currency (
			symbol, curcode, descrip, rate
		) VALUES (
			'$showcur', '$curcode', '$descrip', '0'
		)";
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert currency to Cubit.");

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Currency added</th>
			</tr>
			<tr class='datacell'>
				<td>Currency $descrip  $showcur has been successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<tr>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='".SELF."'>Add Currency</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='currency-view.php'>View Currency</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>

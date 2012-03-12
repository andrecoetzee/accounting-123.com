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
			if (isset($_GET['fcid'])){
				$OUTPUT = edit ($_GET['fcid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['fcid'])){
		$OUTPUT = edit ($_GET['fcid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

require ("template.php");

##
# functions
##

# Enter settings
function edit($fcid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fcid, "num", 1, 20, "Invalid Currency.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Select Stock
	db_connect();

	$sql = "SELECT * FROM currency  WHERE fcid = '$fcid'";
	$curRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($curRslt) < 1){
		return "<li> Invalid Carrency.</li>";
	}else{
		$cur = pg_fetch_array($curRslt);
	}
	$r = "";
	$d = "";
	$p = "";
	$e = "";
	$o = "";
	$ocur = "";
	switch($cur['symbol']){
		case "R" :
			$r = "checked=yes";
			break;
		case "$" :
			$d = "checked=yes";
			break;
		case "&#163" :
			$p = "checked=yes";
			break;
		case "&#8364" :
			$e = "checked=yes";
			break;
		default:
			$o = "checked=yes";
			$ocur = $cur['symbol'];
	}


	# Connect to db
	$enter = "
		<h3>Edit Currency</h3>
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
			<input type='hidden' name='fcid' value=$cur[fcid]>
			<input type='hidden' name='key' value='confirm'>
		<tr>
			<th colspan='2'>Currency Symbol</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='radio' size='20' name='cur' value='rand' $r></td>
			<td>(RSA) Rand - R</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='radio' size='20' name='cur' value='dollar' $d></td>
			<td>(USA) Dollar - $</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='radio' size='20' name='cur' value='pound' $p></td>
			<td>(UK) Pound - &#163</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='radio' size='20' name='cur' value='euro' $e></td>
			<td>(EU) Euro - &#8364</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='radio' size='20' name='cur' value='other' $o></td>
			<td><input type='text' size='4' name='ocur' value='$ocur' onKeyDown='setSymbolOther();'></td>
		</tr>
		<tr><td><br></td></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Currency Name</td>
			<td><input type='text' size='20' maxlength='20' name='descrip' value='$cur[descrip]'></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Currency Code</td>
			<td><input type='text' size='20' maxlength='20' name='curcode' value='$cur[curcode]'></td>
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
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='currency-view.php'>View Currency</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";
	return $enter;

}



# confirm entered info
function confirm($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fcid, "num", 1, 20, "Invalid Currency.");
	$v->isOk ($descrip, "string", 1, 20, "Invalid Currency name.");
	$v->isOk ($curcode, "string", 0, 3, "Invalid Currency code.");

	# display errors, if any
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

	$Sl = "SELECT * FROM currency WHERE(symbol='$showcur' OR descrip='$descrip') AND fcid!='$fcid'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri)>0) {
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

	$confirm = "
		<h3>Confirm Edit Currency</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='fcid' value=$fcid>
			<input type='hidden' name='cur' value='$cur'>
			<input type='hidden' name='descrip' value='$descrip'>
			<input type='hidden' name='curcode' value='$curcode'>
			<tr>
				<th colspan>Currency Symbol</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$showcur</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan>Currency Name</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$descrip</td>
			</tr>
			<tr>
				<th colspan>Currency Code</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$curcode</td>
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
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='currency-view.php'>View Currency</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
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
	$v->isOk ($fcid, "num", 1, 20, "Invalid Currency.");
	$v->isOk ($descrip, "string", 1, 20, "Invalid Currency name.");
	$v->isOk ($curcode, "string", 0, 3, "Invalid Currency code.");

	# display errors, if any
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

	db_connect ();

	$Sl = "SELECT * FROM currency WHERE (symbol='$showcur' OR curcode='$curcode' OR descrip='$descrip') AND fcid!='$fcid'";
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


	# Connect to db
	db_connect ();

	$Sql = "UPDATE currency SET symbol = '$showcur', curcode='$curcode', descrip = '$descrip' WHERE fcid = '$fcid'";
	$setRslt = db_exec ($Sql) or errDie ("Unable to update currency to Cubit.");

	# status report
	$write = "
	<table ".TMPL_tblDflts." width='50%'>
		<tr>
			<th>Currency Edited</th>
		</tr>
		<tr class='datacell'>
			<td>Currency $descrip  $showcur has been edited in Cubit.</td>
		</tr>
	</table>
	<p>
	<tr>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
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

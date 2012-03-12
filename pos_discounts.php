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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("template.php");

function enter()
{
	$OUTPUT = "<h3>Pos Discounts</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm'>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan='2'>Disable Pos User Discounts</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td align='center'>
				<select name='posdisc'>
					<option value='yes'>Yes</option>
					<option value='no'>No</option>
				</select>
			</td>
			<td align='right'>
				<input type='submit' value='Confirm &raquo'>
			</td>
		</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function confirm($_POST)
{
	extract ($_POST);
	
	require_lib("validate");
	$v = new validate;
	$v->isOk($posdisc, "string", 1, 3, "Invalid pos discount selection.");
	
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
	}
	
	$OUTPUT = "<h3>Pos Discounts</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write'>
	<input type='hidden' name='posdisc' value='$posdisc'>
	<table border='0' cellpadding='".TMPL_tblDataColor1."' cellspacing='".TMPL_tblDataColor2."'>
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>Pos User Discounts</td>
			<td>".ucfirst($posdisc)."</td>
		</tr>
		<tr>
			<td colspan='2' align='right'><input type='submit' value='Write &raquo'></td>
		</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function write($_POST)
{
	extract ($_POST);
	
	require_lib("validate");
	$v = new validate;
	$v->isOk($posdisc, "string", 1, 3, "Invalid pos discount selection.");
	
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
	}
	
	db_conn("cubit");
	$sql = "UPDATE settings SET value='$posdisc' WHERE constant='POS_USER_DISCOUNTS'";
	$rslt = db_exec($sql) or errDie("Unable to save the pos user discount setting to Cubit.");
	
	$OUTPUT = "<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>Successfully updated the information to Cubit.</td>
		</tr>
	</table>";
	
	return $OUTPUT;
}

?>
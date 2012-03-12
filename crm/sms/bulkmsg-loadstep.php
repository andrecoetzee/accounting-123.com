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

require("../../settings.php");
require("../https_urlsettings.php");

if ( isset($_POST) && is_array($_POST) ) {
	foreach($_POST as $key => $value) {
		$_GET[$key] = $value;
	}
}

if ( ! isset($_GET["step"]) ) {
	$OUTPUT = "<li class=err>Invalid use of module</li>";
	require("../../template.php");
}

$OUTPUT = choose_step();

require("../../template.php");

function choose_step() {
	global $_GET;
	extract($_GET);

	require_lib("validate");
	$v = & new Validate();

	switch($step) {
	case "0":
		if ( ! isset($msg) ) $msg = "";
		$OUTPUT = "$msg";
		break;

	case "1":
		$OUTPUT = enter("");
		break;

	case "2":
		$contact_form = "";
		$custs_list = "";

		// create the list of customers
		if ( ! isset($send_all) && isset($custs) && is_array($custs) ) {
			foreach ($custs as $key => $value) {
				if ( ! $v->isOk($value, "num", 1, 9, "") )
					continue;

				db_conn("cubit");
				$rslt = db_exec("SELECT * FROM customers WHERE cusnum = '$value'")
					or errDie("Error reading buyer.");

				if ( pg_num_rows($rslt) < 1 )
					continue;

				$row = pg_fetch_array($rslt);
				$contact_form .= "<input type=hidden name='custs[$key]' value='$value'>";
				$custs_list .= "$row[cusname] $row[surname]<br>";
			}
		}

		if ( isset($send_all) ) {
			$contact_form = "<input type=hidden name=send_all value=true>";
			$custs_list = "All Customers";
		}

		if ( empty($contact_form) ) {
			$OUTPUT = enter("<li class=err>Please select a valid customer from the list.</li>");
			return $OUTPUT;
		}

		$OUTPUT = "<h3>General Message</h3>
			<form name=msgform method=post action='".SELF."'>
			<input type=hidden name=msg value='$msg'>
			<input type=hidden name=step value=3>
			$contact_form
			<table width=700 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr bgcolor='".TMPL_tblDataColor1."'>
				<td valign=top>
				$msg
				</td>
				<td valign=top>
					<center><h3>Format Characters</h3></center>
					@name - Customer name<br>
					@surname - Customer surname (Blank with Buyers/Possible Tenants)<br>
					@balance - Customer balance (Blank with Possible Tenants)<br>
					<br>&nbsp;
				</td>
			</tr>
			<tr bgcolor='".TMPL_tblDataColor2."'>
				<td colspan=2>
				<table width=100%>
				<tr>
					<td><b>Customers to send message to:</b></td>
				</tr>
				<tr>
					<td nowrap>
					$custs_list
					</td>
				</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td colspan=2 align=center><input type=submit value='Send'></td>
			</tr>
			</table>
			</form>";
		break;

	case "3":
		$OUTPUT = "
		<form method=post name=dataform action='".BULKMSGS_URL."?".sendhash()."'>";

		$i = 0;
		if ( isset($send_all) ) {
			db_conn("cubit");
			$sql = "SELECT * FROM customers";
			$rslt = db_exec($sql) or errDie("Error reading customers list.");

			$custs = Array();
			while ( $row = pg_fetch_array($rslt) ) {
				$custs[] = "$row[cusnum]";
			}
		} else if ( ! isset($custs) || ! is_array($custs) ) {
			$custs = Array();
		}

		// buyers
		foreach ( $custs as $key => $value ) {
			db_conn("cubit");
			$sql = "SELECT * FROM customers WHERE cusnum='$value'";
			$rslt = db_exec($sql) or errDie("Error reading customers list.");

			while ( $row = pg_fetch_array($rslt) ) {
				if ( ! empty($row["cellno"]) ) {
					$cusbalance = "R " . sprint($row["balance"]);

					$smsg = $msg;
					$smsg = str_replace("@name", $row["cusname"], $smsg);
					$smsg = str_replace("@surname", $row["surname"], $smsg);
					$smsg = str_replace("@balance", "$cusbalance", $smsg); ;
					$smsg = str_replace("=", "|", base64_encode($smsg));

					$OUTPUT .= "
						<input type=hidden name='cust[$i]' value='$row[cellno]'>
						<input type=hidden name='msg[$i]' value='$smsg'>";

					$i++;
				}
			}
		}

		$OUTPUT .= "</form>
		<script>document.dataform.submit();</script>";
		break;
	}

	return $OUTPUT;
}

function enter($err){
	global $_GET;
	extract($_GET);

	if ( ! isset($msg) ) $msg = "";
	if ( ! isset($custs) || ! is_array($custs) ) $custs = Array();

	if ( isset($send_all) )
		$send_all_selected = "checked";
	else
		$send_all_selected = "";

	$contact_found = false;

	// create the buyer selection
	db_conn("cubit");
	$rslt = db_exec("SELECT * FROM customers ORDER BY surname,cusname")
		or errDie("Error fetching customer list.");

	if ( pg_num_rows($rslt) < 1 ) {
		$custs_select = "No customers in Cubit.";
	} else {
		$custs_select = "<select name=custs[] multiple size=5>";
		while ( $row = pg_fetch_array($rslt) ) {
			if ( in_array($row["cusnum"], $custs) )
				$selected = "selected";
			else
				$selected = "";

			$custs_select .= "<option value=$row[cusnum] $selected>$row[surname], $row[cusname]</option>";
		}
		$custs_select .= "</select>";
	}

	// create the output
	$OUTPUT = "<h3>General Messages</h3>
	$err
	Enter a message to send to specified contacts via SMS. Use the format characters to customize the message
	even more. To use format character simply click on one of them to add it to the message,
	and they will be replaced with the specified value when the message is sent.<br>
	Example: With customer Mr. Charles Prince, and balance R5400 the following message:<Br>
	<br>
	Dear @name @surname, we wish to inform you of your outstanding balance of @balance and ask ...<br>
	<br>
	will result in this:<br>
	<br>
	Dear Charles Prince, we wish to inform you of your outstanding balance of R5400 and ask ...<br>
	<br>
	<script>
		function addFormat(addValue) {
			document.msgform.msg.value += addValue;
			document.msgform.msg.focus();
		}
	</script>

	<form name=msgform method=post action='".SELF."'>
	<input type=hidden name=step value='2'>
	<table width=700 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td valign=top align=center>
		<font size=2><b>Message</b></font><br><br>
		<textarea rows=5 cols=50 name=msg>$msg</textarea>
		</td>
		<td valign=top>
			<center><font size=2><b>Format Characters</b></font></center><br>
			<a href='javascript: addFormat(\"@name\");'>@name</a> - Customer name<br>
			<a href='javascript: addFormat(\"@surname\");'>@surname</a> - Customer surname<br>
			<a href='javascript: addFormat(\"@balance\");'>@balance</a> - Customer balance<br>
			<br>&nbsp;
		</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td colspan=2>
		Please select the customers you wish to send a message to.<br>
		Hold CTRL key and click to select more than one.<br>
		<table width=100%>
		<tr>
			<td><b>Customers:</b></td>
		</tr>
		<tr>
			<td nowrap>
			$custs_select
			</td>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td align=center colspan=3>
				<input type=checkbox name=send_all $send_all_selected> <b>Send to Everybody</b>
			</td>
		</tr>
		</table>
	</tr>
	<tr>
		<td colspan=2 align=center><input type=submit value='Confirm'></td>
	</tr>
	</table>
	</form>";

	return $OUTPUT;
}

?>

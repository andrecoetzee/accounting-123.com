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
require("https_urlsettings.php");

if ( isset($_POST) && is_array($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_GET[$key] = $value;
	}
}

if ( isset($_GET["key"]) ) {
	switch ( $_GET["key"] ) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
		default:
			$OUTPUT = enter("");
	}
} else {
	$OUTPUT = enter("");
}

require("../template.php");

function enter($err) {
	global $_GET;
	extract($_GET);

	// get the previous settings
	db_conn("cubit");
	$sql = "SELECT * FROM cubitnet_sitesettings where div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error reading previous property site settings.");

	if ( pg_num_rows($rslt) < 1 ) {
		$fields["cn_username"] = "";
		$fields["cn_password"] = "";
		$fields["cn_name"] = "";
		$fields["cn_tel"] = "";
		$fields["cn_cell"] = "";
		$fields["cn_email"] = "";
	} else {
		$fields = pg_fetch_array($rslt);
	}

	foreach ($fields as $key => $value) {
		if ( ! isset($$key) ) $$key = $value;
	}

	$OUTPUT = "
	<h3>Cubit Internet Settings</h3>
	$err
	<form enctype='multipart/form-data' method=post action='".SELF."'>
	<input type=hidden name=key value=confirm>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th colspan=2>Company Details</th>
	</tr>
	<tr class='bg-odd'>
		<td>Surname/Company Name</td>
		<td><input type=text name=cn_name value='$cn_name'></td>
	</tr>
	<tr class='bg-even'>
		<td>Telephone</td>
		<td><input type=text name=cn_tel value='$cn_tel'></td>
	</tr>
	<tr class='bg-odd'>
		<td>Cellphone</td>
		<td><input type=text name=cn_cell value='$cn_cell'></td>
	</tr>
	<tr class='bg-even'>
		<td>E-mail</td>
		<td><input type=text name=cn_email value='$cn_email'></td>
	</tr>
	<tr>
		<th colspan=2>Cubit.co.za Details</th>
	</tr>
	<tr class='bg-odd'>
		<td>Username(Any Username)</td>
		<td><input type=text name=cn_username value='$cn_username'></td>
	</tr>
	<tr class='bg-even'>
		<td>Password(Any Password)</td>
		<td><input type=password name=cn_password value='$cn_password'></td>
	</tr>
	<tr>
		<td colspan=2 align=center>
			<input type=submit value='Confirm'>
		</td>
	</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm() {
	global $_GET, $_FILES;
	extract($_GET);

	require_lib("validate");
	$v = & new Validate();

	$v->isOk($cn_username, "string", 1, 9, "Invalid Cubit.co.za Username entered.");
	$v->isOk($cn_password, "string", 1, 9, "Invalid Cubit.co.za Password entered.");
	$v->isOk($cn_name, "string", 1, 100, "Invalid surname/company name entered.");
	$v->isOk($cn_tel, "string", 1, 100, "Invalid tel name entered.");
	$v->isOk($cn_cell, "string", 1, 100, "Invalid cell name entered.");
	$v->isOk($cn_email, "email", 1, 255, "Invalid email entered.");

	if ( $v->isError() ) {
		$err = "";

		foreach ( $v->getErrors() as $key => $value ) {
			$err .= "<li class=err>$value[msg]</li>";
		}

		return enter($err);
	}

	$OUTPUT = "
	<h3>Cubit.co.za Settings</h3>
	<form method=POST action='".SELF."'>
	<input type=hidden name=key value=write>";

	foreach($_GET as $key => $value) {
		if ( $key != "key" ) $OUTPUT .= "<input type=hidden name='$key' value='$value'>";
	}

	$OUTPUT .= "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th colspan=2>Agency Details</th>
	</tr>
	<tr class='bg-odd'>
		<td>Surname/Company Name</td>
		<td>$cn_name</td>
	</tr>
	<tr class='bg-even'>
		<td>Telephone</td>
		<td>$cn_tel</td>
	</tr>
	<tr class='bg-odd'>
		<td>Cellphone</td>
		<td>$cn_cell</td>
	</tr>
	<tr class='bg-even'>
		<td>E-mail</td>
		<td>$cn_email</td>
	</tr>
	<tr>
		<th colspan=2>Cubit.co.za Details</th>
	</tr>
	<tr class='bg-odd'>
		<td>Username:</td>
		<td>$cn_username</td>
	</tr>
	<tr class='bg-even'>
		<td>Password:</td>
		<td>( censored )</td>
	</tr>
	<tr>
		<td colspan=2 align=center>
			<input type=submit value='Write'>
		</td>
	</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write() {
	global $_GET;
	extract($_GET);

	require_lib("validate");
	$v = & new Validate();

	$v->isOk($cn_username, "string", 1, 9, "Invalid Fat Fish Username entered.");
	$v->isOk($cn_password, "string", 1, 9, "Invalid Fat Fish Password entered.");
	$v->isOk($cn_name, "string", 1, 100, "Invalid surname/company name entered.");
	$v->isOk($cn_tel, "string", 1, 100, "Invalid tel name entered.");
	$v->isOk($cn_cell, "string", 1, 100, "Invalid cell name entered.");
	$v->isOk($cn_email, "email", 1, 255, "Invalid email entered.");

	if ( $v->isError() ) {
		$err = "";

		foreach ( $v->getErrors() as $key => $value ) {
			$err .= "<li class=err>$value[msg]</li>";
		}

		return enter($err);
	}

	db_conn("cubit");
	pglib_transaction("BEGIN");
	// remove the previous settings if any
	$sql = "DELETE FROM cubitnet_sitesettings WHERE div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error saving settings (DEL).");

	// write the settings
	$sql = "INSERT INTO cubitnet_sitesettings (cn_username, cn_password, cn_name, cn_tel, cn_cell, cn_email, div)
		VALUES('$cn_username', '$cn_password', '$cn_name', '$cn_tel', '$cn_cell', '$cn_email', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Error saving settings (WRITE).");

	pglib_transaction("COMMIT") or errDie("Error saving settings (TRAN).");

	// check to see if the property HASH has been created yet, if not create it
	db_conn("cubit");
	$sql = "SELECT setting_value FROM cubitnet_settings WHERE setting_name='cubitnet_hash'";
	$rslt = db_exec($sql) or errDie("Error checking for Fat Fish hash.");

	if ( pg_num_rows($rslt) < 1 ) {
		// generate it
		srand(time());
		$cubitnet_hash = md5(COMP_DB.rand().$cn_username.$cn_password) . md5(COMP_NNAME.rand());

                db_conn("cubit");
		$sql = "INSERT INTO cubitnet_settings (setting_name, setting_value)
			VALUES('cubitnet_hash', '$cubitnet_hash')";
		$rslt = db_exec($sql) or errDie("Error creating new hash value.");
	} else {
		$cubitnet_hash = pg_fetch_result($rslt, 0, 0);
	}

	$OUTPUT = "
	<h3>Saved settings. Uploading data to server.</h3>
	<form method=post name=data_form action='".SETTINGS_URL."'>
	<input type=hidden name='cn_username' value='$cn_username'>
	<input type=hidden name='cn_password' value='$cn_password'>
	<input type=hidden name='cn_hash' value='$cubitnet_hash'>
	<input type=hidden name='cn_name' value='$cn_name'>
	<input type=hidden name='cn_tel' value='$cn_tel'>
	<input type=hidden name='cn_cell' value='$cn_cell'>
	<input type=hidden name='cn_email' value='$cn_email'>
	</form>
	<script>document.data_form.submit();</script>";

	return $OUTPUT;
}

?>

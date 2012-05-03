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
		default:
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

function enter($errors="")
{
	global $_POST;
	extract($_POST);

	require ("locale_codes.php");

	// Retrieve list of locales from the locales directory
	db_conn("cubit");
	$sql = "SELECT locale FROM users WHERE username='".USER_NAME."'";
	$localeRslt = db_exec($sql) or errDie("Unable to retrieve user locale settings from Cubit.");
	$locale_user = pg_fetch_result($localeRslt, 0);

	define("LOCALE_DIR", "./locale");
	$h_dir = opendir(LOCALE_DIR);
	$ar_dir = array();
	while (false !== ($dir = readdir($h_dir))) {
		$ar_dir[] = $dir;
	}
	$locale_sel = "<select name='locale' style='width: 180px'>";
	foreach ($ar_dir as $locale_code) {
		if (is_dir(LOCALE_DIR ."/". $locale_code) && preg_match("/[a-z]{2,2}_[A-Z]{2,2}/", $locale_code)) {
			if ($locale_code == $locale_user) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$ar_locale = explode("_", $locale_code);

			// Retrieve the name of the langauge
			foreach ($ar_languages as $lang_name=>$lang_code) {
				if ($ar_locale[0] == $lang_code) {
					$language = $lang_name;
				}
			}

			// Retrieve the name of the country
			foreach ($ar_countries as $country_name=>$country_code) {
				if ($ar_locale[1] == $country_code) {
					$country = $country_name;
				}
			}

			$locale_sel .= "<option value='$locale_code' $selected>$language ($country)</option>";
		}
	}
	$locale_sel .= "</select>";

	// Admin Settings
	db_conn("cubit");
	$sql = "SELECT admin FROM users WHERE username='".USER_NAME."'";
	$admRslt = db_exec($sql) or errDie("Unable to retrieve user information from Cubit.");
	$adm = pg_fetch_result($admRslt, 0);

	if ($adm == 1) {
		// Retrieve a list of all of the current company's usernames
		db_conn("cubit");
		$sql = "SELECT * FROM users ORDER BY username ASC";
		$usersRslt = db_exec($sql) or errDie("Unable to retrieve a list of usernames from Cubit.");

		$usernames = "<select name='username' style='width: 180px'>";
		while ($usrData = pg_fetch_array($usersRslt)) {
			if ($usrData["username"] == USER_NAME) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$usernames .= "<option value='$usrData[userid]' $selected>$usrData[username]</option>";
		}
		$usernames .= "</select>";

		// Create a dropdown from the list of locales
		//$ar_dir = scandir(LOCALE_DIR);

		$ar_dir = array();
		$h_localdir = opendir(LOCALE_DIR);
		while (false !== ($file = readdir($h_localdir))) {
			$ar_dir[] = $file;
		}

		$dlocale_sel = "<select name='dlocale' style='width: 180px'>";
		$defloc = getCSetting("LOCALE_DEFAULT");
		foreach ($ar_dir as $dlocale_code) {
			if (is_dir(LOCALE_DIR ."/". $dlocale_code) && preg_match("/[a-z]{2,3}_[A-Z]{2,3}/", $dlocale_code)) {
				$ar_dlocale = explode("_", $dlocale_code);

				// Retrieve the name of the langauge
				foreach ($ar_languages as $lang_name=>$lang_code) {
					if ($ar_dlocale[0] == $lang_code) {
						$dlanguage = $lang_name;
					}
				}

				// Retrieve the name of the country
				foreach ($ar_countries as $country_name=>$country_code) {
					if ($ar_dlocale[1] == $country_code) {
						$dcountry = $country_name;
					}
				}

				if ($defloc == $dlocale_code) {
					$selected = "selected";
				} else {
					$selected = "";
				}

				$dlocale_sel .= "<option value='$dlocale_code' $selected>$dlanguage ($dcountry)</option>";
			}
		}
		$dlocale_sel .= "</select>";

		/* timezone setting */
		$timezone = getCSetting("LOCALE_TIMEZONE");
		$tzs = qryTimezone(false, "timezone, continent AS optgroup");
		$tzlist = db_mksel($tzs, "timezone", $timezone, "#timezone", "#timezone");

		$adm_settings = "
		<tr class='".bg_class()."'>
		  <td>Username</td>
		  <td>$usernames</td>
		</tr>
		<tr class='".bg_class()."'>
		  <td>".COMP_NNAME."'s default locale</td>
		  <td>$dlocale_sel</td>
		</tr>
		<tr class='".bg_class()."'>
		    <td>Timezone</td>
		    <td>$tzlist</td>
		</tr>";
	}

	/* locale enabled ? */
	db_conn("cubit");
	$sql = "SELECT locale_enable FROM users WHERE username='".USER_NAME."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve locale setting.");
	$locale_enable = pg_fetch_result($rslt, 0);

	if ($locale_enable != "disabled") {
		$locen_che = "checked";
	} else {
		$locen_che = "";
	}

	// Layout
	$OUTPUT = "
	<h3>Locale Settings</h3>
	$errors
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm'>
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan=2>Settings</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Locale Enabled</td>
		<td><input type='checkbox' name='enable' value='enabled' $locen_che></td>
	</tr>
	<tr class='".bg_class()."'>
	<td>User's Locale</td>
		<td>$locale_sel</td>
	</tr>
	$adm_settings
	<tr>
		<td colspan=2 align=right><input type=submit value='Confirm &raquo'></td>
	</tr>
	</table>
	</form>"
	.mkQuickLinks(
		ql("locale-translate.php", "Translate Cubit"),
		ql("locale-settings.php", "Locale Settings")
	);

	return $OUTPUT;
}

function confirm($_POST)
{
	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($locale, "string", 1, 80, "Invalid locale selection.");
	$v->isOk($timezone, "string", 1, 80, "Invalid timezone selection.");
	if ($v->isError()) {
		$confirm = $v->genErrors();
		return enter($confirm);
	}

	require ("locale_codes.php");

	$ar_locale = explode("_", $locale);

	// Retrieve the name of the langauge
	foreach ($ar_languages as $lang_name=>$lang_code) {
		if ($ar_locale[0] == $lang_code) {
			$language = $lang_name;
		}
	}

	// Retrieve the name of the country
	foreach ($ar_countries as $country_name=>$country_code) {
		if ($ar_locale[1] == $country_code) {
			$country = $country_name;
		}
	}

	// Retrieve admin info from Cubit
	db_conn("cubit");
	$sql = "SELECT admin FROM users WHERE username='".USER_NAME."'";
	$admRslt = db_exec($sql) or errDie("Unable to retrieve user admin info from Cubit.");
	$adm = pg_fetch_result($admRslt, 0);

	if ($adm == 1) {
		// Retrieve the username
		db_conn("cubit");
		$sql = "SELECT username FROM users WHERE userid='$username'";
		$usrRslt = db_exec($sql) or errDie("Unable to retrieve username information from Cubit.");
		$usr = pg_fetch_result($usrRslt, 0);

		$ar_dlocale = explode ("_", $dlocale);

		// Retrieve the name of the langauge
		foreach ($ar_languages as $lang_name=>$lang_code) {
			if ($ar_dlocale[0] == $lang_code) {
				$dlanguage = $lang_name;
			}
		}

		// Retrieve the name of the country
		foreach ($ar_countries as $country_name=>$country_code) {
			if ($ar_dlocale[1] == $country_code) {
				$dcountry = $country_name;
			}
		}

		$adm_settings = "<input type=hidden name=username value='$username'>
		<input type=hidden name=dlocale value='$dlocale'>
		<tr class='".bg_class()."'>
			<td>Username</td>
			<td>$usr</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>".COMP_NNAME." default locale</td>
			<td>$dlanguage ($dcountry)</td>
		</tr>
		<tr class='".bg_class()."'>
		    <td>Timezone</td>
			<td>$timezone</td>
		</tr>";
	} else {
		$adm_settings = "";
	}

	if (!isset($enable)) {
		$enable = "disabled";
	}

	$OUTPUT = "
	<h3>Locale Settings</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='locale' value='$locale' />
	<input type='hidden' name='enable' value='$enable' />
	<input type='hidden' name='timezone' value='$timezone' />
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan=2>Confirm</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Enable Locale</td>
		<td>$enable</td>
	</tr>
	<tr class='".bg_class()."'>
	<td>User's Locale</td>
		<td>$language ($country)</td>
	</tr>
	$adm_settings
	<tr>
		<td colspan=2 align=right>
			<input type=submit name=key value='&laquo; Correction'>
			<input type=submit value='Write &raquo;'>
		</td>
	</tr>
	</table>
	</form>"
	.mkQuickLinks(
		ql("locale-translate.php", "Translate Cubit"),
		ql("locale-settings.php", "Locale Settings")
	);

	return $OUTPUT;
}

function write($_POST) {
	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($locale, "string", 1, 80, "Invalid language selection.");
	$v->isOk($timezone, "string", 1, 80, "Invalid timezone selection.");

	if ($v->isError()) {
		$confirm = $v->genErrors();
		return enter($confirm);
	}

	// Retrieve user admin info
	db_conn("cubit");
	$sql = "SELECT admin FROM users WHERE username='".USER_NAME."'";
	$admRslt = db_exec($sql) or errDie("Unable to retrieve user admin information from Cubit.");
	$adm = pg_fetch_result($admRslt, 0);

	if (!isset($enable)) {
		$enable = "disabled";
	}

	if ($adm == 1) {
		// Username
		db_conn("cubit");
		$sql = "UPDATE users SET locale='$locale', locale_enable='$enable' WHERE userid='$username'";
		$usrRslt = db_exec($sql) or errDie("Unable to update user locale settings to Cubit.");

		if (pg_affected_rows($usrRslt) > 0) {
			$msg = "<tr class='bg-odd'>
			  <td><li>Successfully updated user locale settings</li></td>
			</tr>";
		} else {
			$msg = "<tr><td><li class=err>Failed to update user locale settings.</li></td></tr>";
		}

		// Default
		db_conn("cubit");
		$sql = "UPDATE cubit.settings SET value='$locale' WHERE constant='LOCALE_DEFAULT'";
		$defRslt = db_exec($sql) or errDie("Unable to update the default locale setting to Cubit.");

		if (pg_affected_rows($defRslt) > 0) {
			$msg .= "<tr class='bg-odd'>
			  <td><li>Successfully updated the default locale setting.</li></td>
			</tr>";
		} else {
			$msg .= "<tr class='bg-odd'>
			  <td><li class=err>Failed to update the default locale setting</li></td>
			</tr>";
		}

		/* timezone */
		db_conn("cubit");
		$sql = "UPDATE cubit.settings SET value='$timezone' WHERE constant='LOCALE_TIMEZONE'";
		$defRslt = db_exec($sql) or errDie("Unable to update the timezone setting to Cubit.");

		if (pg_affected_rows($defRslt) > 0) {
			$msg .= "<tr class='bg-odd'>
			  <td><li>Successfully updated the timezone selecion.</li></td>
			</tr>";
		} else {
			$msg .= "<tr class='bg-odd'>
			  <td><li class=err>Failed to update the timezone selecion</li></td>
			</tr>";
		}

	} else {
		db_conn("cubit");
		$sql = "UPDATE users SET locale='$locale' WHERE username='".USER_NAME."'";
		$localeRslt = db_exec($sql) or errDie("Unable to update user locale settings to Cubit.");

		$msg = "<tr class='bg-odd'>
		  <td><li>Successfully updated user locale settings</li></td>
		</tr>";
	}
	$OUTPUT = "<h3>Locale Settings</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		$msg
	</table>";

	return $OUTPUT;
}

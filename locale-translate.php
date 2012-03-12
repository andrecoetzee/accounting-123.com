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

error_reporting(E_ALL);

define ("OFFSET_SIZE", 20);

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "translate":
			$OUTPUT = translate();
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
	require ("locale_codes.php");

	// Retrieve current user's locale
	db_conn("cubit");
	$sql = "SELECT locale FROM users WHERE username='".USER_NAME."'";
	$localeRslt = db_exec($sql)
		or errDie(ct("Unable to retrieve the current user's locale from Cubit."));
	$locale = pg_fetch_result($localeRslt, 0);
	$locale = explode("_", $locale);

	// Languages dropdown
	$language_sel = "<select name='language' style='width: 180px'>";
	foreach ($ar_languages as $lang_name=>$lang_code) {
		if ($locale[0] == $lang_code) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$language_sel .= "<option name='language' value='$lang_code' $selected>
						      $lang_name
						  </option>";
	}
	$language_sel .= "</select>";

	// Countries dropdown
	$country_sel = "<select name='country' style='width: 180px'>";
	foreach ($ar_countries as $country_name=>$country_code) {
		if (isset($locale[1])) {
			if ($locale[1] == $country_code) {
				$selected = "selected";
			} else {
				$selected = "";
			}
		}
		$country_sel .= "<option name='country' value='$country_code' $selected>
						     $country_name
						 </option>";
	}
	$country_sel .= "</select>";

	// Encodings dropdown
	$encoding_sel = "<select name=encoding style='width: 180px'>";
	foreach ($ar_encodings as $location=>$val) {
		foreach ($ar_encodings[$location] as $charset) {
			if ($charset == "ISO-8859-1") {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$encoding_sel .= "<option value='$charset' $selected>
							      $location ($charset)
							  </option>";
		}
	}
	$encoding_sel .= "</select>";

	$OUTPUT = "<h3>".ct("Translate Cubit")."</h3>
	$errors
	<form method='post' action='".SELF."'>
	<input type='hidden' name='create' value='true'>
	<input type='hidden' name='key' value='translate'>
	<table ".TMPL_tblDflts.">
	  <tr>
	    <th colspan=2>".ct("Language Info")."</th>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Language")."</td>
	    <td>$language_sel</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Country")."</td>
	    <td>$country_sel</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Character Encoding")."</td>
	    <td>$encoding_sel</td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right>
	    	<input type=submit value='".ct("Translate &raquo")."'>
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

function translate($offset = 1) {
	global $_POST;
	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($language, "string", 1, 2, "Invalid language code.");
	$v->isOk($country, "string", 2, 2, "Invalid country code.");
	$v->isOk($encoding, "string", 1, 255, "Invalid character encoding.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return enter($confirm);
	}

	$fields = array();
	$fields["untrans"] = "";
	$fields["search"] = "";
	$fields["replace"] = "";
	$fields["sr_inf"] = "";

	extract ($fields, EXTR_SKIP);

	if (!isset($untrans)) $untrans = "";

	$locale = $language."_".$country;

	$sz_msgdir = "locale/$locale/LC_MESSAGES";
	$sz_msgpath = "locale/$locale/LC_MESSAGES/messages.po";

	// Create the directory if it does not already exist
	if (!cfs::is_dir($sz_msgdir)) {
		cfs::mkdir($sz_msgdir);
	}

	// Open the pot file
	if (!cfs::is_file($sz_msgpath) || cfs::filesize($sz_msgpath) == 0) {
		$ar_messages = cfs::get_contents("messages.po");
		cfs::put_contents($sz_msgpath, $ar_messages);
	}

	// Retrieve the translation text
	$tl = "";
	$ar_pot_file = cfs::file($sz_msgpath);

	if (!empty($untrans)) {
		$ar_pot_file = removeTranslated($ar_pot_file);
	}

	if (isset($srchrep)) {
		$sr_ar = searchReplace($ar_pot_file, $search, $replace, $locale);
		$ar_pot_file = $sr_ar["file"];
		$sr_inf = "Replaced: <b>$sr_ar[count]</b>";
	}
	// Retrieve the amount of msgid's for the offset
	$n_count = 0;
	for ($i = 0; $i < count($ar_pot_file); $i++) {
		if (!isset($ar_pot_file[$i])) {
			continue;
		}

		if (preg_match("/(^msgid \")([^\"]*)(\")/", $ar_pot_file[$i], $ar_matches)
			&& $ar_matches[2] != "") {

			$n_count++;
		}
	}

	// Calculate the page numbers
	$current_page = intval(($offset / OFFSET_SIZE)) + 1;
	$total_pages = intval(($n_count / OFFSET_SIZE)) + 1;

	// Calculate the starting value of the next and previous buttons
	$n_next = $offset + OFFSET_SIZE;
	$n_prev = $offset - OFFSET_SIZE;

	// Decide which buttons to display
	if ($n_next > $n_count) {
		$sz_next = "<input type=submit value='".ct("Translate")."'>";
	} else {
		$sz_next = "<input type=submit name='next' value='".ct("Next &raquo")."'>";
	}

	if ($n_prev < 0) {
		$sz_prev = "";
	} else {
		$sz_prev = "<input type=submit name='prev' value='".ct("&laquo Previous")."'>";
	}

	// POT file is empty
	if (!count($ar_pot_file)) {
		$OUTPUT = "<li class='err'>Unable to load translation text.</li>"
		.mkQuickLinks(
			ql("locale-translate.php", "Translate Cubit"),
			ql("locale-settings.php", "Locale Settings")
		);
		return $OUTPUT;
	}

	// Start reading each line of the translation within the offset
	$n_count2 = 0;
	foreach ($ar_pot_file as $i=>$value) {
		if (preg_match("/(^msgid \")([^\"]*)(\")/", $ar_pot_file[$i], $ar_msgid)
			&& $ar_msgid[2] != "") {

			$n_count2++;
			if ($n_count2 >= $offset && $n_count2 < $n_next) {
				if (preg_match("/(^msgstr \")([^\"]*)(\")/", $ar_pot_file[($i+1)],
				$ar_msgstr)) {

					$tl .= "<tr bgcolor='".bgcolorg()."'>
					<td><b>".htmlspecialchars($ar_msgid[2])."</b></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
					<td align=center>
						<input type=text style='width: 495px;
						background:'".bgcolorg()."' name='tl[".($i+1)."]'
						value='$ar_msgstr[2]'>
					</td>
					</tr>";
				}
			}
		}
	}

	require ("locale_codes.php");

	// Retrieve the name of the language
	if (isset($ar_languages) && is_array($ar_languages)) {
		$lang_out = "";
		foreach ($ar_languages as $lang_name=>$lang_code) {
			if ($lang_code == $language) {
				$lang_out = $lang_name;
			}
		}
	} else {
		$OUTPUT = "<li class='err'>Unable to load language codes.</li>"
		.mkQuickLinks(
			ql("locale-translate.php", "Translate Cubit"),
			ql("locale-settings.php", "Locale Settings")
		);
		return $OUTPUT;
	}

	// Retrieve the name of the country
	if (isset($ar_countries) && is_array($ar_countries)) {
		$country_out = "";
		foreach ($ar_countries as $country_name=>$country_code) {
			if ($country_code == $country) {
				$country_out = $country_name;
			}
		}
	} else {
		$OUTPUT = "<li class='err'>Unable to load country codes.</li>"
		.mkQuickLinks(
			ql("locale-translate.php", "Translate Cubit"),
			ql("locale-settings.php", "Locale Settings")
		);
		return $OUTPUT;
	}

	$OUTPUT = "<center>
	<h3>".ct("Translate Cubit")."</h3>
	<form method=post action='".SELF."' name='form'>
	<input type=hidden name=key value='write'>
	<input type=hidden name=loffset value='$offset'>
	<input type=hidden name=language value='$language'>
	<input type=hidden name=country value='$country'>
	<input type=hidden name=encoding value='$encoding'>
	<input type='hidden' name='total_pages' value='$total_pages' />
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
      <tr>
        <th colspan=2>".ct("Translation Info")."</th>
      </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Language")."</td>
	    <td>$lang_out</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Country")."</td>
	    <td>$country_out</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Character Encoding")."</td>
	    <td>
	      $encoding
	    </td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>".ct("Page")."</td>
	    <td>
	    	<input type='text' name='page_txt' size='3' value='$current_page'
	    	style='text-align: center'> of $total_pages
	    	<input type='submit' name='page_btn' value='Goto' />
	    </td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	  	<td colspan='2' align='center'>
	  		<input type='checkbox' name='untrans' value='checked' $untrans
	  		onchange='javascript:document.form.submit();' />
	  		Display Only Untranslated Sentences
	  	</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	  	<td colspan='2'>
	  		<table ".TMPL_tblDflts." width='100%'>
	  			<tr>
	  				<th colspan='5'>Search and Replace</th>
	  			</tr>
	  			<tr bgcolor='".bgcolorg()."'>
	  				<td>Search</td>
	  				<td>
	  					<input type='text' name='search' value='$search'
	  					style='width: 100%' />
	  				</td>
	  				<td>Replace</td>
	  				<td>
	  					<input type='text' name='replace' value='$replace'
	  					style='width: 100%' />
	  				</td>
	  				<td>
	  					<input type='submit' name='srchrep' value='Search & Replace'
	  					style='width: 100%' />
	  				</td>
	  			</tr>
	  			<tr>
	  				<td colspan='5' align='center'>$sr_inf</td>
	  			</tr>
	  		</table>
	  	</td>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td colspan=2>
	      Some of the translation sentences includes special words and characters
	      such as `".htmlspecialchars("&laquo")."', `".htmlspecialchars("&raquo")."', `<', `<<', ect... Please include these words
	      and characters in your translation sentences as well.<p>
	      After clicking <i>".ct("Next")."</i> or <i>".ct("Previous")."</i> the current state of the translation is automatically saved.
	  </tr>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
	  <tr>
	    <th>".ct("Translate")."</th>
	  </tr>
	  <tr bgcolor='".bgcolorg()."'>
	    <td>
	      <table border=0 cellpadding=0 cellspacing=0 width=500>
	        <tr bgcolor='".bgcolorg()."'>
	          <td width=50% align=left>$sz_prev</td>
	          <td width=50% align=right>$sz_next</td>
	        </tr>
	      </table>
	    </td>
	  </tr>
	  $tl
	  <tr bgcolor='".bgcolorg()."'>
	    <td>
	      <table border=0 cellpadding=0 cellspacing=0 width=500>
	        <tr bgcolor='".bgcolorg()."'>
	          <td width=50% align=left>$sz_prev</td>
	          <td width=50% align=right>$sz_next</td>
	        </tr>
	      </table>
	    </td>
	  </tr>
	</table>
	</form>
	</center>"
	.mkQuickLinks(
		ql("locale-translate.php", "Translate Cubit"),
		ql("locale-settings.php", "Locale Settings")
	);

	return $OUTPUT;
}

function write($_POST)
{
	extract ($_POST);

	// Make sure the page number is within a valid range
	if ($page_txt <= 0) $page_txt = 1;
	if ($page_txt > $total_pages) $page_txt = $total_pages;

	if (isset($next)) $loffset += OFFSET_SIZE;
	if (isset($prev)) $loffset -= OFFSET_SIZE;
	if (isset($page_btn)) $loffset = ($page_txt * OFFSET_SIZE) - OFFSET_SIZE;

	$locale = $language."_".$country;

	// Replace the lines with the translated sentences
	$ar_pot_file = file(DOCROOT."/locale/$locale/LC_MESSAGES/messages.po");
	if (isset($tl) && is_array($tl)) {
		foreach ($tl as $line_nr=>$val) {
			$ar_pot_file[$line_nr] = "msgstr \"$val\"\n";

		}
	} else {
		$OUTPUT = "<li class='err'>Unable to load <b>$locale</b> locale</li>";
		require ("template.php");
	}

	// Charset
	foreach ($ar_pot_file as $i=>$value) {
		if (preg_match("/([^;]*; charset=)([^\"]*)(\")/", $ar_pot_file[$i])) {
			$ar_pot_file[$i] = preg_replace("/([^;]*; charset=)([^\"]*)(\")/", "\\1$encoding\\3", $ar_pot_file[$i]);
			break;
		}
	}
	if (!dir("./locale/$locale/LC_MESSAGES")) {
		mkdir("$locale");
		mkdir("LC_MESSAGES");
	}

	$h_file_out = fopen("./locale/$locale/LC_MESSAGES/messages.po.tmp", "w");
	fwrite($h_file_out, implode("", $ar_pot_file));
	fclose($h_file_out);

// 	system("msgfmt ./locale/$locale/LC_MESSAGES/messages.po -o ./locale/$locale/LC_MESSAGES/messages.mo");

	return translate($loffset);
}

function removeTranslated($pot_file_ar)
{
	$new_pot_ar = array();
	for ($i = 0; $i < count($pot_file_ar); $i++) {
		if (preg_match("/^msgid/", $pot_file_ar[$i])) {
			$j = $i+1;
			if (preg_match("/^msgstr \"\"/", $pot_file_ar[$j])) {
				$new_pot_ar[$i] = $pot_file_ar[$i];
				$new_pot_ar[$j] = $pot_file_ar[$j];
			}
		}
	}

	return $new_pot_ar;
}

function searchReplace($pot_file_ar, $search, $replace, $locale)
{
	global $_POST;

	$count = 0;
	foreach ($pot_file_ar as $key=>$value) {
		if (preg_match("/$search/", $pot_file_ar[$key]) &&
			preg_match("/^msgstr/", $pot_file_ar[$key])) {
			$pot_file_ar[$key] = preg_replace("/$search/", $replace, $pot_file_ar[$key]);
			$count++;
		}
	}

	$return_ar = array("count"=>$count, "file"=>$pot_file_ar);

	if (!dir("./locale/$locale/LC_MESSAGES")) {
		mkdir("$locale");
		mkdir("LC_MESSAGES");
	}

	$h_file_out = fopen("./locale/$locale/LC_MESSAGES/messages.po.tmp", "w");
	fwrite($h_file_out, implode("", $pot_file_ar));
	fclose($h_file_out);

	return $return_ar;
}
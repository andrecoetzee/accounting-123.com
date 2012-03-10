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

//require ("locale/I18Nv2/I18Nv2.php");

// Decide which function we should use for the translation
function ct($sz_string) {
// 	if (is_file("disable_locale")
// 		|| is_file("../disable_locale")
// 		|| is_file("../../disable_locale")
// 		|| is_file("../../../disable_locale")
// 		|| is_file("../../../../disable_locale")
// 		|| is_file("../../../../../disable_locale")
// 		|| is_file("../../../../../../disable_locale")
// 		|| is_file("../../../../../../../disable_locale")) {
// 		return alt_($sz_string);
// 	}
//
//  	if (!I18Nv2::setLocale($locale)) {
//  		// Load the alternative (slower) translation function
//    		return alt_($sz_string);
//  	} else {
//  		bindtextdomain("messages", locdir());
//  		textdomain("messages");
//    		return _($sz_string);
//  	}

 	if (!defined("LOCALE")) {
 		define("LOCALE", "disabled");
 	}

 	if (!defined("USER_NAME")) {
 		define("LOCALE", "disabled");
 	}

	if (LOCALE != "disabled") {
		return alt_($sz_string);
	} else {
		return $sz_string;
	}
}

// Alternative translation function
function alt_($sz_string)
{
	global $ar_messages;

	static $ar_messages = array();
	if (count($ar_messages) == 0) {
		// Retrieve the contents from the messages.po file
		$file = DOCROOT."/locale/".LOCALE."/LC_MESSAGES/messages.po";
		$ar_po = file($file);
// 		if (is_file("./$file")) {
// 			print "./";
// 			$ar_po = file("./$file");
// 		} elseif (is_file("../$file")) {
// 			print "../";
// 			$ar_po = file("../$file");
// 		} elseif (is_file("../../$file")) {
// 			print "../../";
// 			$ar_po = file ("../../$file");
// 		} elseif (is_file("../../../$file")) {
// 			print "../../../";
// 			$ar_po = file ("../../../$file");
// 		} else {
// 			print "no translation";
// 			return $sz_string; // Don't continue trying to translate
// 		}

		// Rebuild the msgid's for msgids spanning multiple lines
		if (count($ar_messages) == 0) {
			$sz_msgid = "";
			$sz_msgstr = "";
			for ($i = 0; $i < count($ar_po); $i++) {
				if (preg_match("/^msgid \"([^\"]*)\"/", $ar_po[$i], $ar_msgid)) {
					$sz_msgid = $ar_msgid[1];
					$curr = "msgid";
				} elseif (preg_match("/^msgstr \"([^\"]*)\"/", $ar_po[$i], $ar_msgstr)) {
					$sz_msgstr = $ar_msgstr[1];
					$curr = "msgstr";
				} elseif (preg_match("/^\"([^\"]*)\"/", $ar_po[$i], $ar_multiline)) {
					if ($curr == "msgid") {
						$sz_msgid .= $ar_multiline[1];
					} elseif ($curr == "msgstr") {
						$sz_msgstr .= $ar_multiline[1];
					}
				}
				if (!empty($sz_msgid)) {
					$ar_messages[$sz_msgid] = $sz_msgstr;
				}
			}
		}
	}

	// Return the translated message
	if (!empty($ar_messages[$sz_string])) {
		return $ar_messages[$sz_string];
	} else {
		return $sz_string;
	}
}
?>

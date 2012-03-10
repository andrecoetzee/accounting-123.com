<?

/* doc root */
define("INDEX_XUL", true);
require("_defineroot.php");

/* the rest */
require("settings.php");

/* three xul files
	p = no menu
	m = minimul menus (print, save, email, etc...)
	f = full menus
*/

// parse/modify the xul
if (USER_TYPE == "security" && !isset($_GET["lp"])) {
	$script = "picking_slips/dispatch.php";
	$wxul = "m";
} else if (USER_TYPE == "P" && !isset($_GET["lp"])) {
	$script = "pos-invoice-new.php";
	$wxul = "m";
} else if (USER_TYPE == "S" && !isset($_GET["lp"])) {
	$script = "pos-invoice-speed.php";
	$wxul = "m";
} else if (isset($_GET["lp"])) {
	$script = $_GET["lp"];
	unset($_GET["lp"]);
	$script = "$script?".array2get($_GET);

	$wxul = "m";
} else if (isset($_GET["p"])) {
	$script = $_GET["p"];
	unset($_GET["p"]);
	$script = "$script?".array2get($_GET);

	$wxul = "f";
} else {
	$script = "main.php";
	$wxul = "f";
}

$script = preg_replace("/&/", "&amp;", $script);

if (DEBUG > 0) {
	$dbgtitle = "- $_SESSION[code]";
} else {
	$dbgtitle = "";
}

// make the title
$title = TMPL_title." [ $HTTP_SESSION_VARS[comp] - $HTTP_SESSION_VARS[BRAN_NAME] - $HTTP_SESSION_VARS[USER_NAME] $dbgtitle]";

$xulcontent = file_get_contents("index.$wxul.xul");

if (strlen($xulcontent) == 0) {
	exit;
}

// replace into xul
$xulcontent = preg_replace("/%%PAGETITLE%%/", $title, $xulcontent);
$xulcontent = preg_replace("/%%ENTRYPAGE%%/", $script, $xulcontent);

/* replace in module menu items */
global $CUBIT_MODULES;
$menus = array();
foreach ($CUBIT_MODULES as $modname) {
	if (is_file(DOCROOT."/$modname/menu.php")) {
		include(DOCROOT."/$modname/menu.php");
		
		foreach ($MODULE_MENUS as $mn => $mi) {
			if (!isset($menus[$mn])) {
				$menus[$mn] = $mi;
			} else {
				$menus[$mn] .= $mi;
			}
		}
	}
}

foreach ($menus as $mn => $mi) {
	/* find the tag */
	if (!preg_match("/<cubitmodule([^>]+)menu=\"".preg_quote($mn, "/")."\"([^>]+)\/>/", $xulcontent, $m)) {
		continue;
	} else {
		/* full tag to replace */
		$modtag = preg_quote($m[0], "/");
		$modrep = "$mi";
		$sep = "bottom";
		
		/* parse it's options */
		$opts = array($m[1], $m[2]);
		foreach ($opts as $o) {
			if (preg_match("/separator=\"([^\"]+)\"/", $o, $mt)) {
				$sep = $mt[1];
			}
		}
		
		/* handle the options */
		switch ($sep) {
			case "top": 
				$modrep = "<menuseparator />$modrep";
				break;
			case "bottom":
				$modrep = "$modrep<menuseparator />";
				break;
			case "both":
				$modrep = "<menuseparator />$modrep<menuseparator />";
			default:
		}
	
		$xulcontent = preg_replace("/$modtag/", $modrep, $xulcontent);
	}
}

// stream
header("Content-Type: application/vnd.mozilla.xul+xml");
print $xulcontent;
exit;

?>

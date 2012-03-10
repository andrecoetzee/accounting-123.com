<?
/**
 * Generally used functions/constants, login logic also
 * @package Cubit
 * @subpackage ErrorHandling
 */
if (!defined("ERROR_LIB")) {
	define("ERROR_LIB", true);

global $ERRNET_ERRORS;
global $ERRNET_ENABLED;
$ERRNET_ERRORS = array();

/**
 * generates the report message
 */
function errorNetReport($errid) {
	$errlink_save = "<input type='button' value='Save Error Report'
		onClick='document.location.href=\"".relpath("geterror.php")."?id=$errid\";' />";
	$errlink_send = "<input type='button' value='Send Error Report'
		onClick='document.location.href=\"".relpath("geterror.php")."?id=$errid&send=t\";' />";

	$OUTPUT = "
	<h3>An Unexpected Error has Occurred</h3>
	Cubit has encountered an unexpected error. Please send us the error report by
	clicking the 'Send Error Report' button or saving the report and emailing
	it to us at <a href='mailto:".ERRORNET_EMAIL."'>".ERRORNET_EMAIL."</a>.
	Thank you.<br /><br />
	$errlink_save $errlink_send";

	return $OUTPUT;
}

/**
 * flags an error
 *
 * @param string $errstr
 */
function flagError($errstr) {
	user_error($errstr, E_USER_ERROR);
}

/**
 * flags a warning
 *
 * @param string $errstr
 */
function flagWarning($errstr) {
	user_error($errstr, E_USER_WARNING);
}

/**
 * flags an notice
 *
 * @param string $errstr
 */
function flagNotice($errstr) {
	user_error($errstr, E_USER_NOTICE);
}

/**
 * disable errornet error handling.
 *
 * do this when you want to do something that may cause a "safe" error, ex.
 * executing queries like "DROP TABLE x" where table x might not exist.
 *
 */
function disableErrorNet() {
	global $ERRNET_ENABLED;
	$ERRNET_ENABLED = false;
}

/**
 * enable errornet after disabling it
 *
 */
function enableErrorNet() {
	global $ERRNET_ENABLED;
	$ERRNET_ENABLED = false;
}

function errorNet($errno, $errmsg, $file, $line, $vars) {
	global $XMLNS;
	global $ERRNET_ERRORS;
	global $ERRNET_ENABLED;
	global $SQL_EXEC_NUM;

	$errortype = array (
            E_ERROR           => "Error",
            E_WARNING         => "Warning",
            E_PARSE           => "Parsing Error",
            E_NOTICE          => "Notice",
            E_CORE_ERROR      => "Core Error",
            E_CORE_WARNING    => "Core Warning",
            E_COMPILE_ERROR   => "Compile Error",
            E_COMPILE_WARNING => "Compile Warning",
            E_USER_ERROR      => "User Error",
            E_USER_WARNING    => "User Warning",
            E_USER_NOTICE     => "User Notice",
            E_STRICT          => "Runtime Notice"
	);


	$usererr = array(
		E_USER_ERROR      => true,
		E_USER_WARNING    => true,
		E_USER_NOTICE     => true
	);

	if (defined("DEBUG")) {
		switch (DEBUG) {
			case 1:
				if ($errno == E_NOTICE) {
					break;
				}
			case 2:
				print $errortype[$errno].": $errmsg<Br>File: $file ($line)";

				if (isset($usererr[$errno])) {
					print " <u>CUBIT FLAGGED ERROR, filename/line number may be inaccurate</u>";
				}

				print "<br><br>";
		}
	}

	if ($ERRNET_ENABLED == false) {
		return;
	}

	if ($errno == E_NOTICE) {
		return;
	}

	if (!defined("ERRORNET_OCCURED")) {
		define("ERRORNET_OCCURED", true);
	}

	$xmlns = "xmlns=\"$XMLNS[errornet]\"";

	/* error details */
	$OUT = "<errorinfo $xmlns errno=\"$errno\" file=\"$file\" line=\"$line\" sql=\"$SQL_EXEC_NUM\">".xmldata($errmsg)."</errorinfo>\n";

	/* record global arrays */
	$ars = array(
		"GLOBALS",
		"_SERVER",
		"_GET",
		"_POST",
		"_COOKIE",
		"_FILES",
		"_ENV",
		"_REQUEST",
		"_SESSION"
	);

	if (false) {
		foreach ($ars as $arname) {
			global $$arname;
			$OUT .= "<vararray $xmlns name=\"$arname\">\n";
			$OUT .= array2xml("data", "desc", "$xmlns", $$arname);
			$OUT .= "</vararray>\n";
		}
	}

	/* variable dump */
	if (count($vars) > 0) {
		$OUT .= "<symboltable $xmlns>\n";
		$OUT .= array2xml("data", "desc", "$xmlns", $vars);
		$OUT .= "</symboltable>\n";
	}

	/* constants dump */
	$OUT .= "<constants $xmlns>\n";
	$OUT .= array2xml("data", "desc", "$xmlns", get_defined_constants(true));
	$OUT .= "</constants>";

	/* extensions dump */
	$OUT .= "<extensions $xmlns>\n";
	$OUT .= array2xml("data", "desc", "$xmlns",  get_loaded_extensions());
	$OUT .= "</extensions>";

	/* functions dump */
	$OUT .= "<functions $xmlns>\n";
	$OUT .= array2xml("data", "desc", "$xmlns", get_defined_functions());
	$OUT .= "</functions>";

	/* defined vars dump */
	//$OUT .= "<vars $xmlns>\n";
	//$OUT .= array2xml("data", "desc", "$xmlns",  get_defined_vars());
	//$OUT .= "</vars>";

	$ERRNET_ERRORS[] = $OUT;
}

function errorNetSave() {
	global $XMLNS;
	global $ERRNET_ERRORS;
	global $SQL_EXEC;
	global $CUBIT_MODULES;

	if (!defined("ERRORNET_OCCURED")) {
		return -1;
	}

	$xmlns = "xmlns=\"$XMLNS[errornet]\"";

	$OUT = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$OUT .= "<cubiterror version=\"0.1\" time=\"".date("l dS \of F Y h:i:s A")."\"/>\n";

	/* cubit info */
	$OUT .= "<cubit $xmlns\n>\n";
	$OUT .= "<cdata $xmlns desc=\"version\">".CUBIT_VERSION."</cdata>\n";
	$OUT .= "<cdata $xmlns desc=\"build\">".CUBIT_BUILD."</cdata>\n";
	$OUT .= "<cdata $xmlns desc=\"platform\" value=\"".PLATFORM."\">".xmldata(php_uname())."</cdata>\n";
	$OUT .= "<cdata $xmlns desc=\"phpversion\">".phpversion()."</cdata>\n";
	$OUT .= "<cdata $xmlns desc=\"cengineversion\">".phpversion("cengine")."</cdata>\n";
	$OUT .= "<cdata $xmlns desc=\"debugmode\">".(defined("DEBUG") && DEBUG == 1?"true":"false")."</cdata>\n";
	$OUT .= "<cmodules $xmlns>\n";
	$OUT .= array2xml("cdata", "num", "$xmlns", $CUBIT_MODULES);
	$OUT .= "</cmodules>\n";
	$OUT .= "</cubit>";

	/* sql queries */
	$OUT .= "<sql $xmlns>\n";
	$OUT .= array2xml("data", "num", "$xmlns", $SQL_EXEC);
	$OUT .= "</sql>";

	/* php configuration */
	$OUT .= "<phpconf $xmlns>\n";
	$OUT .= array2xml("data", "name", "$xmlns", ini_get_all());
	$OUT .= "</phpconf>\n";

	/* error details */
	$errnum = 1;
	foreach ($ERRNET_ERRORS as $k => $v) {
		$OUT .= "<error $xmlns num=\"$errnum\">\n";
		$OUT .= "$v\n";
		$OUT .= "</error>\n";

		++$errnum;
	}

	$errdata = base64_encode($OUT);

	db_con("cubit");
	pglib_transact("ROLLBACK");
	$sql = "INSERT INTO errordumps (errtime, errdata)
			VALUES(CURRENT_TIMESTAMP, '$errdata')";
	$rslt = db_exec($sql) or errDie("Error storing error dump report.");

	return pglib_lastid("errordumps", "id");
}

if (defined("ERRORNET") && ERRORNET == 1) {
	$ERRNET_ENABLED = true;
	set_error_handler("errorNet");
}
} /* END OF LIB */

?>

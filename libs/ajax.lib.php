<?
/**
 * Generally used functions/constants related to date/time
 *
 * @package Cubit
 * @subpackage AJAX
 */

if (!defined("AJAX_LIB")) {
	define("AJAX_LIB", true);

/**
 * checks if any ajax output should be streamed and streams it
 *
 * if no ajax should be streamed, this function does nothing
 * this function is automatically called by template.php so simply requiring
 * template.php in all cases (AJAX and NOT) will give desired effects.
 *
 * the AJAX constant (settings.php) is used to determine whether or not
 * the output is requested with AJAX or not
 *
 * @see AJAX
 * @param string $OUT html output
 */
function AJAX_OUT($OUT) {
	if (AJAX) {
		header("Content-Type: application/xml");
		print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		print "<div xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		print $OUT;
		print "</div>";
		exit;
	}
}

global $JS_AJAX;
addglobals("JS_AJAX");

/**
 * Ajax requests: javascript functionality.
 *
 * include this variable in your output if you need to make AJAX requests.
 * if you are simply returning output requested by AJAX this is not necesary.
 */
$JS_AJAX = "
<script type=\"application/x-javascript\">
var AJAX_SET = 1;
var AJAX_ADD = 2;
var AJAX_OBJ = 4;
var AJAX_CLS = 8;
var AJAX_EXE = 16;

var AJAX_RSPTXT = '__ajax_resp';

// args:
// 1: page
// 2: layername
// 3: action (above)
// 4: gets vars (optional)
// 5: exec function
// 6: exec function arg1
// 7: exec function arg2
// 8: exec function arg3
function ajaxRequest() {
	var argv = ajaxRequest.arguments;

	if (document.getElementById) {
		var x = (window.ActiveXObject) ? new ActiveXObject('Microsoft.XMLHTTP') : new XMLHttpRequest();
	}

	if (x) {
		x.reqLayer = argv[1];
		x.reqWindow = window;
		x.reqAction = argv[2];
		if (argv[4]) x.reqFunction = argv[4];
		if (argv[5]) x.reqFuncARG1 = argv[5];
		if (argv[6]) x.reqFuncARG2 = argv[6];
		if (argv[7]) x.reqFuncARG3 = argv[7];
		x.onreadystatechange = function() {
			if (x.readyState == 4 && x.status == 200) {
				if (x.reqAction & AJAX_ADD) {
					ajaxLayerAdd(x.reqLayer, x.responseText);
				}

				if (x.reqAction & AJAX_OBJ) {
					ajaxObjLayerSet(x.reqLayer, x.responseText);
				}

				if (x.reqAction & AJAX_SET) {
					ajaxLayerSet(x.reqLayer, x.responseText);
				}

				if (x.reqAction & AJAX_CLS) {
					x.reqWindow.close();
				}

				if (x.reqAction & AJAX_EXE) {
					if (x.reqFuncARG1 == AJAX_RSPTXT) {
						x.reqFuncARG1 = x.responseText;
					}
					x.reqFunction(x.reqFuncARG1, x.reqFuncARG2, x.reqFuncARG3);
				}
			}
		}

		if (argv[3]) {
			url = argv[0] + '?' + argv[3] + '&AJAX=true';
		} else {
			url = argv[0] + '?AJAX=true';
		}
		x.open(\"GET\", url, true);
		x.send(null);
	} else {
		return false;
	}

	return true;
}

function ajaxObjLayerSet(l, content) {
	l.innerHTML = content;
}

function ajaxLayerSet(l, content) {
	getObj(l).innerHTML = content;
}

function ajaxLayerAdd(l, content) {
	lcontent = getObj(l).innerHTML;
	ajaxLayerSet(l, lcontent + content);
}

function getObj(id) {
	if (document.all)
		return document.all[id];

	return document.getElementById(id);
}
</script>";

} /* LIB END */

?>
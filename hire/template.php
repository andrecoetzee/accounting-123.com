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

##
# template.php :: Template, including CSS for display
##

# global settings
//require_once ("settings.php");

# If this script is called by itself, abort
if (SELF == "template.php") {
	exit;
}

/* template already executed */
if (!defined("TEMPLATE_EXECUTED")) {
	define("TEMPLATE_EXECUTED", true);
} else {
	/* stop the progress bar when script/output is finished */
	if (defined("PROGRESS_BAR")) {
		stopProgress();
	}

	/* completing last part of template */
	if (defined("TEMPLATE_PARTIAL")) {
		print "
			</body>
			</html>";
	}

	exit;
}

global $scGLOB;
eval($scGLOB);

if (isset($GLOBALS["_CUBIT_XML"]) && !defined("CUBIT_XML")) {
	define("CUBIT_XML", true);
}

if (($errid = errorNetSave()) > 0) {
	$OUTPUT = errorNetReport($errid);
}

AJAX_OUT($OUTPUT);

$reload = ""; # temporary : sometimes menu refreshes over and over
if(isset($HTTP_POST_VARS["stkidss"]) || isset($HTTP_POST_VARS["SCROLL"])){
	$bod = "<body onLoad='scrolldown();'>";
	$exb = "window.scroll(1,8000)";
}elseif (isset($HTTP_POST_VARS["setfocus"])){
	$bod = "<body onLoad=\"document.form1.$HTTP_POST_VARS[setfocus].focus();\">";
	$exb = "";
} else if (defined("JS_ONLOAD")) {
	$bod = "<body onLoad='".JS_ONLOAD."'>";
	$exb = "";
}else{
	$bod = "<body onLoad='setLoginFocus();'>";
	$exb = "";
}

$bgColor = TMPL_bgColor;

	$Out="";
	$date=date("Y-m-d");
	$time=date("Hi");

	$notice="";

if (isset ($HTTP_POST_VARS["login_user"]) && isset ($HTTP_POST_VARS["login_pass"]) && isset ($HTTP_POST_VARS["login"]) && !defined("LOGIN_SUCCESSFUL") && !defined("LOGIN_SUCCESSFUL_NOROUTE")) {
	checkLogin ($HTTP_POST_VARS["login_user"], md5 ($HTTP_POST_VARS["login_pass"]), $HTTP_POST_VARS["div"], isset($HTTP_POST_VARS["noroute"]));
} elseif (empty ($HTTP_SESSION_VARS["USER_NAME"]) || empty ($HTTP_SESSION_VARS["USER_ID"])) {$Out="";}
else {
		// Files and directories which shouldn't display the previous peroid warning
		$no_prevprd = array (
			"/groupware/",
			"/diary/"
		);

		$tf = FALSE;
		foreach ($no_prevprd as $val) {
			if (strpos($HTTP_SERVER_VARS["PHP_SELF"],$val) > 0) {
				$tf = TRUE;
				break;
			}
		}

		/*	if you dont want a file to show the warning, put this after you require settings.php
				define("PRD_STATE_NOWARN", true);
			if you dont want a whole subdirectory to display the warning add it in the array above
		*/
		if ((!defined("PRD_STATE_NOWARN")) && defined("PRD_STATE") && PRD_STATE == "py" && $tf == FALSE) {
			if (is_file("./set-period-use.php")) $yrfile = "./set-period-use.php";
			if (is_file("../set-period-use.php")) $yrfile = "../set-period-use.php";
			if (is_file("../../set-period-use.php")) $yrfile = "../../set-period-use.php";
			if (is_file("../../../set-period-use.php")) $yrfile = "../../../set-period-use.php";

			$notice = "<center><li class=err>You are currently working in previous financial year:
				".substr(YR_NAME, 1).". Only journal entry transactions are allowed to be entered
				in the previous financial year. Click <a href='$yrfile'>here</a> to change to
				current financial year: ".((int)substr(YR_NAME, 1) + 1).".</li></center>";
		}

		$user=USER_NAME;
		$Out="";

		db_conn("cubit");
		$Sl = "SELECT id,des,time,datefor FROM die WHERE ((remtime<='$time' AND remdate='$date') OR remdate<'$date' AND (userfor='$user' OR userfor='global')) AND rem<3 ORDER BY remdate,time";
		$Rs = db_exec($Sl) or $OUTPUT ="Unable to access database.";
		$numrows = pg_numrows ($Rs);
		if ($numrows > 0)
		{
			if ($numrows>1) {$s="s";} else {$s="";}
			$Out = "
			<h3>You have the following reminder$s</h3>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Date</th><th>Time</th><th>Appointment</th><th>Options</th></tr>";
			while($Tp = pg_fetch_array($Rs))
			{
				$AppTime=substr($Tp['time'],0,2).":".substr($Tp['time'],2,2);
				$AppDate=$Tp['datefor'];
				$Appointment=$Tp['des'];
				$Out .="<tr bgcolor='".TMPL_tblDataColor1."'><td>$AppDate</td><td>$AppTime</td><td>$Appointment</td><td></td></tr>";
				$Sl = "UPDATE die SET rem=rem+1 WHERE id='$Tp[id]'";
				$Rss = db_exec($Sl) or $OUTPUT ="Unable to access database.";
			}
			$Out .="</table><br>";
		}
	}

	if (SELF == "top_menu.php") {
	$Out="";
	$notice="";
	}

	if (SELF == "pos.php" ) {
	$bod = "<body onLoad='setPosFocus()'>";
	}

	if (SELF == "pos-invoice-new.php" ) {
	$bod = "<body onLoad='setSaleFocus()'>";
	}

	if (SELF == "pos-invoice-speed.php" ) {
	$bod = "<body onLoad='setSaleFocus()'>";
	}

	if (SELF == "cust-credit-stockinv.php" ) {
	$bod = "<body onLoad='setFilterFocus()'>";
	}


if ( !isset($HTTP_SESSION_VARS["BRAN_NAME"]) ) $HTTP_SESSION_VARS["BRAN_NAME"]="";
if ( !isset($HTTP_SESSION_VARS["USER_NAME"]) ) $HTTP_SESSION_VARS["USER_NAME"]="";

if ( defined("CUBIT_MENU_PAGE") ) {
	$js_hide_menu = "";
} else {
	$js_hide_menu = "
		<script type=\"application/x-javascript\">
			function hideMenu() {
				if ( top.cubitmenuItemMouseOut )
					top.cubitmenuItemMouseOut();
			}

			function hideMenuImmediate() {
				if ( top.cubitmenuItemMouseOutImmed )
					top.cubitmenuItemMouseOutImmed();
			}

			//document.captureEvents(Event.MOUSEMOVE | Event.MOUSEDOWN | Event.MOUSEUP);
			//document.onmousedown = hideMenuImmediate;
			//document.captureEvents(Event.MOUSEMOVE);
			//document.onmousemove = hideMenu;
		</script>";
}

$CC_USE = "";
$SC_USE = "";
$NC_USE = "";
if(CC_USE == 'use'){
	$CC_USE = "if (ccwin = window.open(prif + 'ccpopup.php?type=' + type + '&typename=' + typename + '&edate=' + edate + '&descrip=' + descrip + '&amount=' + amount,'ccwin', 'width=400, height=400, scrollbars=auto, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no')) ccwin.focus();";
	$SC_USE = "if (ccwin = window.open(prif + 'scpopup.php?type=' + type + '&typename=' + typename + '&edate=' + edate + '&descrip=' + descrip + '&amount=' + amount + '&cdescrip=' + cdescrip + '&cosamt=' + cosamt,'ccwin', 'width=400, height=400, scrollbars=auto, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no')) ccwin.focus();";
	$NC_USE = "if (ccwin = window.open(prif + 'ncpopup.php?type=' + type + '&typename=' + typename + '&edate=' + edate + '&descrip=' + descrip + '&amount=' + amount + '&cdescrip=' + cdescrip + '&cosamt=' + cosamt,'ccwin', 'width=400, height=400, scrollbars=auto, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no')) ccwin.focus();";
}

$OUT = "";
if (defined("CUBIT_XML")) {
	$OUT = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<!DOCTYPE html [
		<!ENTITY nbsp   \"&#160;\">
		<!ENTITY logon.number1 \"logan nommer 1 broe\">
		<!ENTITY logon.number2 \"logan nommer 2 broe\">
	]>
	<html xmlns=\"http://www.w3.org/1999/xhtml\" style='height: 100%; width: 100%;'>";
} else {
	$OUT .= "<html>";
}

$OUT .= "
	<head>";

	if ( defined("DOC_TITLE") ) {
		$OUT .= "<title>".DOC_TITLE."</title>";
	} else if ( isset($HTTP_SESSION_VARS["comp"]) && isset($HTTP_SESSION_VARS["BRAN_NAME"]) && isset($HTTP_SESSION_VARS["USER_NAME"]) ) {
		$OUT .= "<title>".TMPL_title." [ $HTTP_SESSION_VARS[comp] - $HTTP_SESSION_VARS[BRAN_NAME] - $HTTP_SESSION_VARS[USER_NAME] ]</title>";
	} else {
		$OUT .= "<title>Cubit Accounting</title>";
	}

if (defined("ONTHESPOT")) {
	list($ots_script, $ots_layer, $ots_vars) = explode("|", ONTHESPOT);

	$js_onthespot = "
	<html>
	<script type=\"application/x-javascript\">
		layerobj = window.opener.parent.mainframe.document.getElementById('$ots_layer');
		ajaxRequest('$ots_script', layerobj, AJAX_OBJ | AJAX_CLS, '$ots_vars');
		setTimeout('self.close()', 1000);
	</script>
	</html>";
} else {
	$js_onthespot = "";
}

$OUT .=  "
	<style type=\"text/css\" media=\"all\">
		html {
			background: $bgColor;
		}

		body {
			font-family: ".TMPL_fntFamily.";
			background-color: $bgColor;
			font-size: ".TMPL_fntSize."pt;
			color: ".TMPL_fntColor.";
			left: 0;
			margin: 10px;
		}

		td, p, .text {
			font-family: ".TMPL_fntFamily.";
			font-size: ".TMPL_fntSize."pt;
			color: ".TMPL_fntColor2.";
		}

		a {
			color: ".TMPL_lnkColor.";
			text-decoration: none;
		}

		a:hover {
			color: ".TMPL_lnkHvrColor.";
			text-decoration: underline;
		}

		a.nav {
			color: ".TMPL_navLnkColor.";
		}

		a:hover.nav {
			color: ".TMPL_navLnkHvrColor.";
		}

		a#xpopup_cls {
			color: ".TMPL_lnkColor.";
			text-decoration: none;
		}

		a:hover#xpopup_cls {
			color: ".TMPL_lnkHvrColor.";
			text-decoration: none;
		}

		.text {
			background: ".TMPL_tblDataColor1.";
		}

		.quicklinks td {
			background: ".TMPL_tblDataColor1.";
			text-align: center;
		}

		h2, .h2
		{
			font-size: ".TMPL_h2FntSize."pt;
			color: ".TMPL_h2Color.";
		}
		h3, .h3
		{
			font-size: ".TMPL_h3FntSize."pt;
			color: ".TMPL_h3Color.";
		}
		h4, .h4
		{
			font-size: ".TMPL_h4FntSize."pt;
			color: ".TMPL_h4Color.";
		}

		.datacell
		{
			background-color: ".TMPL_tblDataColor1.";
		}
		.datacell2
		{
			background-color: ".TMPL_tblDataColor2.";
		}

		th
		{
			background-color: ".TMPL_tblHdngBg.";
			font-size: ".TMPL_fntSize."pt;
			color: ".TMPL_tblHdngColor.";
		}
		th.plain
		{
			background-color: ".TMPL_bgColor.";
			font-size: ".TMPL_fntSize."pt;
		}
		input, textarea, select
		{
			font-size: 10pt;
			border: 1px solid #000000;
			padding: 2px;
			color: #000000;
		}
		.right
		{
			text-align: right;
		}
		.frmerr {
			border: 2px solid red;
		}
		.err
		{
			color: #FF0000;
			background-color: #FFFFFF;
			border: 2px solid ".TMPL_tblHdngBg.";
		}
		hr
		{
			color: #000000;
		}
		.white
		{
			color: #FFFFFF;
		}
		.tot
		{
			border-top: 2px solid #000000;
			border-bottom: 2px solid #000000;
		}
		.select
		{
			width: 100%;
		}

		a#calNotices
		{
			color: ".TMPL_calNoticesLink_a."
		}
		a:hover#calNotices
		{
			color: ".TMPL_calNoticesLink_h."
		}

		a#calSmallMonthOMLink
		{
			color: ".TMPL_calSmallMonthOMLink_a."
		}
		a:hover#calSmallMonthOMLink
		{
			color: ".TMPL_calSmallMonthOMLink_h."
		}
		a#calSmallMonthCMLink
		{
			color: ".TMPL_calSmallMonthCMLink_a."
		}
		a#calSmallMonthCMLink:hover
		{
			color: ".TMPL_calSmallMonthCMLink_h."
		}
		a#calSmallMonthCMLinkToday
		{
			color: ".TMPL_calSmallMonthCMLinkToday_a."
		}
		a#calSmallMonthCMLinkToday:hover
		{
			color: ".TMPL_calSmallMonthCMLinkToday_h."
		}
		a#calSmallMonthCMLinkSelected
		{
			color: ".TMPL_calSmallMonthCMLinkSelected_a."
		}
		a#calSmallMonthCMLinkSelected:hover
		{
			color: ".TMPL_calSmallMonthCMLinkSelected_h."
		}

		#a_notify_msgs {
			font-size: 14px;
			color: #FFFFFF;
		}

		a#a_notify_msgs, a#a_notify_msgs:visited {
			color: #FFFFFF;
			text-decoration: none;
		}

		a#a_notify_msgs:hover {
			color: #FFFFFF;
			text-decoration: underline;
		}

		a.maildef, a.maildef:visited {
			color: #000000;
			text-decoration: none;
		}

		a.maildef:hover {
			color: #FFFFFF;
			text-decoration: underline;
		}

		a.mailtree, a.mailtree:visited {
			color: $bgColor;
			text-decoration: none;
		}

		a.mailtree:hover {
			color: $bgColor;
			text-decoration: underline;
		}
		a#calLargeMonthOMLink
                {
                        color: ".TMPL_calLargeMonthOMLink_a."
                }
                a:hover#calLargeMonthOMLink
                {
                        color: ".TMPL_calLargeMonthOMLink_h."
                }
                a#calLargeMonthCMLink
                {
                        color: ".TMPL_calLargeMonthCMLink_a."
                }
                a#calLargeMonthCMLink:hover
                {
                        color: ".TMPL_calLargeMonthCMLink_h."
                }
                a#calLargeMonthCMLinkToday
                {
                        color: ".TMPL_calLargeMonthCMLinkToday_a."
                }
		a#calLargeMonthCMLinkToday:hover
                {
                        color: ".TMPL_calLargeMonthCMLinkToday_h."
                }
                a#calLargeMonthCMLinkSelected
                {
                        color: ".TMPL_calLargeMonthCMLinkSelected_a."
                }
                a#calLargeMonthCMLinkSelected:hover
                {
                        color: ".TMPL_calLargeMonthCMLinkSelected_h."
                }
	.required
	{
		color: #920000;
		font-weight: bold;
	}
	-->
	</style>
	<script type=\"application/x-javascript\">
		function getQuicklinkSpecial() {
			if (window.opener) {
				return '<tr class=\"quicklinks\"><td><a href=\"javascript: window.close();\">Close Window</a></td></tr>';
			} else {
				return '<tr class=\"quicklinks\"><td><a href=\"".relpath("main.php")."\">Main Menu</a></td></tr>';
			}
		}

		function closeWin() {
			window.close();
		}

		function popupOpen(url,name) {
			argv = popupOpen.arguments;
			if (argv[2]) {
				opt = argv[2];
			} else {
				opt = 'scrollbars=yes, statusbar=no';
			}
			if (newwin = window.open(url,name,opt))
				newwin.focus();
		}

		function popupSized(url,name,width,height) {
			argv = popupSized.arguments;
			if (argv[4]) {
				opt = argv[4];
			} else {
				opt = 'scrollbars=yes, statusbar=no';
			}
			opt += ', width=' + width + ', height=' + height;

			popupOpen(url,name,opt);
		}

		function crmPopup(url) {
			popupSized(url, 'crmwindow', 750, 550, '');
		}

		function imgSwop (img_name, new_img_src) {
			document[img_name].src = new_img_src;
		}
		function openwindow(url){
			window.open(url,\"stkdet\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=400, height=500\")
		}
		function openSmallWindow(url){
			window.open(url,\"smwin\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=400, height=300\")
		}
		function openwindowbg(url){
			window.open(url,\"bg\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=700, height=500\")
		}
		function url2minimul(url) {
			page = '".relpath("index.xul.php")."';

			/* split the url by .php? */
			url = url.split(/\.php\?/, 2);

			/* build the new url */
			url = page + '?lp=' + url[0] + '.php&' + url[1];

			return url;
		}
		function printer(url){
			url = url2minimul(url);
			if (newwin = window.open(url,\"Printer\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=\" + screen.width + \", height=\" + screen.height+ \", left=0,top=0\"))
				newwin.focus();
		}
		function printer2(url){
			url = url2minimul(url);
			if (newwin = window.open(url,\"Printer\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=\" + screen.width + \", height=\" + screen.height+ \", left=0,top=0\"))
				newwin.focus();
		}
		function nhprinter(url,name){
			url = url2minimul(url);
			if (newwin = window.open(url,name,\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=\" + screen.width + \", height=\" + screen.height+ \",left=0,top=0\"))
				newwin.focus();
		}
		function openPrintWin(url){
			url = url2minimul(url);
			if (newwin = window.open(url,\"stkdet\",\"toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=no, width=800, height=600\"))
				newwin.focus();
		}
		function spmove(url) {
			if (window.opener) {
				window.close();
			} else {
				move(url);
			}
		}
		tim = 0;
		function move (url) {
			document.location.href=url;
		}
		function print_move(url) {
			move(url);
		}
		function openAccWin(url){
			window.open(url,\"accwin\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=400, height=400\")
		}

		function predict() {
			clearTimeout(tim);
			tim=setTimeout('document.form.submit()',1000);
		}
		function setFilterFocus(){
			if (document.form) {
				if (document.form.ria)
					document.form.ria.focus();
				if (document.form.qtemp)
					document.form.qtemp.focus()
			}
		}
		function scrolldown(){
			window.scroll(1,8000)
		}

		function setLoginFocus(){
			if ( document.log && document.log.login_user )
				document.log.login_user.focus()
		}

		function setPosFocus(){
			document.form.me.focus()
		}
		function setSaleFocus(){
			document.form.bar.focus()
			$exb
		}

		// returns the object from it's id
		function getObjectById (id) {
			if (document.all)
				return document.all[id];

			return document.getElementById (id);
		}

		function getObject(id) {
			return getObjectById(id);
		}

		function loadMainFrame(url) {
			parent.mainframe.location.href=url;
		}

		function loadTopMenu(section) {
	//		parent.location.href = 'index.php?section=' + section;
		}

		function loadTopFrame(section) {
			//alert('kazzoooooooga 101');
		}

		function loadCurrentFrame(url) {
			location.href=url;
		}

		function emailPopup() {
			if (emailwin = window.open('groupware/index.php','email_window', 'scrollbars=no, width=750, height=550'))
				emailwin.focus();
		}

		// contacts scripts
		function changeContactRowColor(obj, tocolor) {
			getObjectById(obj).style.background=tocolor;
		}

		function viewContact(id) {
			popupOpen('view_con.php?id=' + id,'contact_popup','scrollbars=yes,width=400,height=350');
		}

		// Cost Centers function
		function CostCenter(type, typename, edate, descrip, amount, prif){
			$CC_USE
		}
		// Sales Cost Centers function
		function sCostCenter(type, typename, edate, descrip, amount, cdescrip, cosamt, prif){
			$SC_USE
		}
		function nCostCenter(type, typename, edate, descrip, amount, cdescrip, cosamt, prif){
			$NC_USE
		}

		checkMsgsTimer = false;
		function checkMsgs() {
			ajaxRequest('".relpath("checkmsgs.php")."', false, AJAX_EXE,
				'key=intcheck', checkMsgsAlert, AJAX_RSPTXT);
		}

		function checkMsgsAlert(rsptext) {
			if (!rsptext.match(/NO MESSAGES/)) {
				mc = rsptext.replace(/.*MSGS: ([0-9]+).*/, 'You have $1 new message(s).');
				XPopupShow(mc + '<br />'
					+ 'Click <a href=\"".relpath("checkmsgs.php")."\">here</a> '
					+ 'to view them.', getObject('check_msgs'));
			} else {
				checkMsgsTimer = setTimeout('checkMsgs()', ".MSGS_CHECKTIME.");
			}
		}

		".(!in_array(SELF, $MSGS_NOALERT) && defined("USER_ID")?"checkMsgsTimer = setTimeout('checkMsgs()', 5000);":"")."
	</script>
	$JS_XPOPUP
	$JS_AJAX
	$js_hide_menu
	</head>
	$notice
	$Out
	$bod
	<div id='doc_layer'>
	$OUTPUT
	<div id='x_popup' onMouseMove='XPopupNoHide();' style='visibility: hidden; position: absolute;'></div>
	$js_onthespot
	</div>
	<span id='check_msgs' style='position: fixed; height: 0px; width: 0px; top: 0px; left: 0px;'></span>";

if (!defined("EMAIL_PAGE_DISABLED")) {
	$emailpage = relpath("emailsave_page.php");

	$OUT .= "
	<script type=\"application/x-javascript\">
		function emailPage() {
			document.emailsave_frm.emailsavepage_action.value = 'email';
			document.emailsave_frm.submit();
		}

		function savePage() {
			document.emailsave_frm.emailsavepage_action.value = 'save';
			document.emailsave_frm.submit();
		}
	</script>

	<form action='$emailpage' name='emailsave_frm' method='post'>
	<input type='hidden' name='emailsavepage_action' value='' />
	<input type='hidden' name='emailsavepage_key' value='content_supplied' />
	<input type='hidden' name='emailsavepage_name' value='".SELF."' />
	<input type='hidden' name='emailsavepage_content' value='".base64_encode($OUTPUT)."' />
	</form>";
}

if (!(defined("TEMPLATE_NODIE") || defined("TEMPLATE_PARTIAL"))) {
	$OUT .= "
		</body>
		</html>";
}

if (defined("CUBIT_XML")) {
	header("Content-Type: application/xml");
	$OUT = preg_replace("/<script[^>]*>/", "<script type=\"application/x-javascript\"><![CDATA[", $OUT);
	$OUT = preg_replace("/<\/script>/", "]]></script>", $OUT);
}

print $OUT;
flush();

if (!(defined("TEMPLATE_NODIE") || defined("TEMPLATE_PARTIAL"))) {
	exit;
} else if (defined("TEMPLATE_PARTIAL")) {
	function partialOut($OUT) {
		print $OUT;
		flush();
	}
}
?>

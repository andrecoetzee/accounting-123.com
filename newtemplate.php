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
//require_once ("newsettings.php");

if (defined("CONSOLE")) {
	$OUTPUT = preg_replace("/<br[^>]*>/", "\n", $OUTPUT);
	print "$OUTPUT\n\n";
	exit(1);
}

# If this script is called by itself, abort
if (SELF == "newtemplate.php") {
	exit;
}

/* template already executed */
if (!defined("TEMPLATE_EXECUTED")) {
	define("TEMPLATE_EXECUTED", true);
} else {
	/* stop the progress when output is finished */
	if (defined("PROGRESS_BAR")) {
		stopProgress();
	}

	/* completing second part of template */
	if (defined("TEMPLATE_PARTIAL")) {
		print "
			$OUTPUT
			</body>
			</html>";
	}

	exit;
}

if ( defined("CUBIT_MENU_PAGE") ) {
	$js_hide_menu = "";
} else {
	$js_hide_menu = "
		<script>
			document.captureEvents(Event.MOUSEMOVE);
			//document.onmousemove = top.theframe.cubitmenuItemMouseOut;
		</script>";
}

print "
<html>
<head>
<META HTTP-EQUIV=Expires CONTENT='Sun, 22 Mar 1998 16:18:35 GMT'>
<title>".TMPL_title."</title>
<link rel=\"stylesheet\" type=\"text/css\" href=\"".relpath("css/style.css")."\" />
<script language='JavaScript' type='text/javascript'>
	function imgSwop (img_name, new_img_src) {
		document[img_name].src = new_img_src;
	}
	function openwindow(url){
		window.open(url,\"stkdet\",\"toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=400, height=500\")
	}
	function openPrintWin(url){
		window.open(url,\"stkdet\",\"toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=no, width=800, height=600\")
	}
	function scrolldown(){
		window.scroll(1,8000)
	}
	function setLoginFocus(){
		if ( document.log )
			document.log.login_pass.focus()
	}
	function setPosFocus(){
		document.form.me.focus()
	}
	function setSaleFocus(){
		document.form.bar.focus();
	}

	// returns the object from it's id
	function getObjectById (id) {
		if (document.all)
			return document.all[id];

		return document.getElementById (id);
	}

	function loadMainFrame(url) {
		parent.mainframe.location.href=url;
	}

	function loadTopMenu(section) {
		parent.location.href = 'index.php?section=' + section;
	}

	function loadTopFrame(section) {
		//alert('kazzoooooooga 101');
	}

	function loadCurrentFrame(url) {
		location.href=url;
	}

	function popupOpen(url,name,opt) {
		newwin = window.open(url,name,opt);

		newwin.focus();
	}

	// contacts scripts
	function changeContactRowColor(obj, tocolor) {
		getObjectById(obj).style.background=tocolor;
	}

	function viewContact(id) {
		popupOpen('view_con.php?id=' + id,'contact_popup','scrollbars=yes,width=250,height=250');
	}
</script>
$js_hide_menu
</head>
<html>
<body>
$OUTPUT
<div id='cubit_userid' style='display:none;'>".@$_SESSION["USER_ID"]."</div>";

flush();

if (!(defined("TEMPLATE_NODIE") || defined("TEMPLATE_PARTIAL"))) {
	print "
		</body>
		</html>";

	exit;
} else if (defined("TEMPLATE_PARTIAL")) {
	function partialOut($OUT) {
		print $OUT;
		flush();
	}
}
?>

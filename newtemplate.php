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
<style type='text/css'>
<!--
	body
	{
		font-family: ".TMPL_fntFamily.";
		background-color: ".TMPL_bgColor.";
		font-size: ".TMPL_fntSize."pt;
		color: ".TMPL_fntColor.";
	}
	td, p, .text
	{
		font-family: ".TMPL_fntFamily.";
		font-size: ".TMPL_fntSize."pt;
		color: ".TMPL_fntColor2.";
	}
	a
	{
		color: ".TMPL_lnkColor.";
		text-decoration: none;
	}
	a:hover
	{
		color: ".TMPL_lnkHvrColor.";
		text-decoration: underline;
	}
	a.nav
	{
		color: ".TMPL_navLnkColor.";
	}
	a:hover
	{
		color: ".TMPL_navLnkHvrColor.";
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
		color: #FFFFFF;
		text-decoration: none;
	}

	a.maildef:hover {
		color: #FFFFFF;
		text-decoration: underline;
	}
	.require {
		color: #920000;
		font-weight: bold;
	}
-->
</style>
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

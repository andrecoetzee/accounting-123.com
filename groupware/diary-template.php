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

if (AJAX) {
	header("Content-Type: application/xml");
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	print "<div xmlns=\"http://www.w3.org/1999/xhtml\">\n";
	print $OUTPUT;
	print "</div>";
	exit;
}


$js_ajax = "
<script>
var AJAX_SET = 1;
var AJAX_ADD = 2;
function ajaxRequest() {
	var argv = ajaxRequest.arguments;

	if (document.getElementById) {
		var x = (window.ActiveXObject) ? new ActiveXObject('Microsoft.XMLHTTP') : new XMLHttpRequest();
	}

	if (x) {
		x.reqLayer = argv[1];
		x.reqAction = argv[2];
		x.onreadystatechange = function() {
			if (x.readyState == 4 && x.status == 200) {
				switch (x.reqAction) {
				case AJAX_ADD:
					ajaxLayerAdd(x.reqLayer, x.responseText);
					break;
				case AJAX_SET:
				default:
					ajaxLayerSet(x.reqLayer, x.responseText);
					break;
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

function ajaxLayerSet(l, content) {
	getObject(l).innerHTML = content;
}

function ajaxLayerAdd(l, content) {
	lcontent = getObject(l).innerHTML;
	ajaxLayerSet(l, lcontent + content);
}
</script>";

print "
	<html>
	<head>
	<META HTTP-EQUIV=Expires CONTENT='Sun, 22 Mar 1998 16:18:35 GMT'>
	<style type='text/css'>
	<!--
		body
		{
			font-family: ".TMPL_fntFamily.";
			margin: 0px;
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
		a:hover.nav
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
			color: ".TMPL_bgColor.";
			text-decoration: none;
		}

		a.mailtree:hover {
			color: ".TMPL_bgColor.";
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
	<script language='JavaScript' type='text/javascript'>
		function popupOpen(url,name,opt) {
			newwin = window.open(url,name,opt);
			newwin.focus();
		}

		function popupSized(url,name,width,height,opt) {
			if ( opt == '' ) opt = 'scrollbars=yes, statusbar=no';
			opt += ', width=' + width + ', height=' + height;

			popupOpen(url,name,opt);
		}

		function crmPopup(url) {
			popupSized(url, 'crmwindow', 750, 550, '');
		}

		function imgSwop (img_name, new_img_src) {
			document[img_name].src = new_img_src;
		}

		function scrolldown(){
			window.scroll(1,8000)
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

		function emailPopup() {
			emailwin = window.open('groupware/index.php','email_window', 'scrollbars=no, width=750, height=550');
			emailwin.focus();
		}

	</script>
	<script>
		function findPosXY(obj) {
			obj.x = findPosX;
			obj.y = findPosY;
			return true;
		}

		function findPosX(eElement) {
			if (!eElement && this) {
				eElement = this;
			}

			var DL_bIE = document.all ? true : false;

			var nLeftPos = eElement.offsetLeft;
			var eParElement = eElement.offsetParent;

			while (eParElement != null) {
				if(DL_bIE) {
					if( (eParElement.tagName != 'TABLE') && (eParElement.tagName != 'BODY') ) {
					nLeftPos += eParElement.clientLeft;
					}
				} else {
					if(eParElement.tagName == 'TABLE') {
					var nParBorder = parseInt(eParElement.border);
					if(isNaN(nParBorder)) {
						var nParFrame = eParElement.getAttribute('frame');
						if(nParFrame != null) {
							nLeftPos += 1;
						}
					}
					else if(nParBorder > 0) {
						nLeftPos += nParBorder;
					}
					}
				}
				nLeftPos += eParElement.offsetLeft;
				eParElement = eParElement.offsetParent;
			}
			return nLeftPos;
		}

		function findPosY(eElement) {
			if (!eElement && this) {
				eElement = this;
			}

			var DL_bIE = document.all ? true : false;

			var nTopPos = eElement.offsetTop;
			var eParElement = eElement.offsetParent;

			while (eParElement != null) {
				if(DL_bIE) {
					if( (eParElement.tagName != 'TABLE') && (eParElement.tagName != 'BODY') ) {
					nTopPos += eParElement.clientTop;
					}
				} else {
					if(eParElement.tagName == 'TABLE') {
					var nParBorder = parseInt(eParElement.border);
					if(isNaN(nParBorder)) {
						var nParFrame = eParElement.getAttribute('frame');
						if(nParFrame != null) {
							nTopPos += 1;
						}
					} else if(nParBorder > 0) {
						nTopPos += nParBorder;
					}
					}
				}

				nTopPos += eParElement.offsetTop;
				eParElement = eParElement.offsetParent;
			}
			return nTopPos;
		}

		// Temporary variables to hold mouse x-y pos.s
		var mouseX = 0
		var mouseY = 0

		// Main function to retrieve mouse x-y pos.s
		var bleh = 0;
		function getMouseXY(e) {
			mouseX = e.pageX
			mouseY = e.pageY

			// catch possible negative values in NS4
			if (mouseX < 0) {
				mouseX = 0
			}
			if (mouseY < 0) {
				mouseY = 0
			}

			return true
		}

		// obj = object we are moving over, content is html to fill it with
		XPopupHideTimer = false;
		XPopupActive = false;
		XPopupObject = false;
		XPopupContent = false;
		XPopupShowTimer = false;
		function XPopupShow(content, object) {
			XPopupNoHide();

			if (XPopupShowTimer == false && XPopupContent != content) {
				XPopupContent = content;
				if (object) {
					XPopupObject = object;
				}
				XPopupShowTimer = setTimeout('XPopupShowAct()', 50);
			} else if (XPopupContent != content) {
				clearTimeout(XPopupShowTimer);
				XPopupShowTimer = false;
			}
		}

		function XPopupShowAct() {
			if (XPopupActive != false) return;

			xp = document.getElementById('x_popup');
			xp.innerHTML =
				'<table bgcolor=\"#fdeb89\" style=\"border: 1px dashed black\">'
				+'<tr><td align=\"right\">[<a href=\"javascript: XPopupHideAct()\">Close</a>]</td></tr>'
				+'<tr><td>'
					+ XPopupContent +
				'</td></tr>'
				+'</table>';

			if (XPopupObject) {
				// get the object we clicked on
				o = XPopupObject;
				findPosXY(o);

				// calculate a position where popup will be 100% visible
				propTop = o.y() + o.offsetHeight;
				propLeft = o.x();
			} else {
				propTop = mouseY;
				propLeft = mouseX;
			}

			if ((toomuch = (propTop + xp.offsetHeight) - window.innerHeight) > 0) {
				//propTop -= window.innerHeight - xp.offsetHeight;
			}

			if ((toomuch = (propLeft + xp.offsetWidth) - window.innerWidth) > 0) {
				//propLeft -= toomuch;
			}

			// now set the position
			xp.style.top = propTop;
			xp.style.left = propLeft;

			// max width/height
			if (xp.offsetWidth > 300) {
				xp.style.width = 300;
			}

			xp.style.visibility = 'visible';
			XPopupShowTimer = false;
		}

		function XPopupHide() {
			if (XPopupHideTimer == false) {
				XPopupHideTimer = setTimeout('XPopupHideAct()', 500);
			}
		}

		function XPopupNoHide() {
			if (XPopupHideTimer != false) {
				clearTimeout(XPopupHideTimer);
				XPopupHideTimer = false;
			}
		}

		function XPopupHideAct() {
			xp = document.getElementById('x_popup');
			xp.style.visibility = 'hidden';
			xp.innerHTML = '';

			XPopupHideTimer = false;
			XPopupActive = false;
			XPopupContent = false;
			XPopupObject = false;

			if (XPopupShowTimer != false) {
				clearTimeout(XPopupShowTimer);
				XPopupShowTimer = false;
			}
		}
	</script>
	$js_ajax
	</head>
	$OUTPUT
	<div id='x_popup' onMouseMove='XPopupNoHide();' style='visibility: hidden; position: absolute;'></div>
	</body>
	</html>";

	exit;
?>

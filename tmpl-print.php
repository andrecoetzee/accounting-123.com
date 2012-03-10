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

//require_once ("settings.php");

# If this script is called by itself, abort
if (SELF == "template.php") {
	exit;
}

if (($errid = errorNetSave()) > 0) {
	$OUTPUT = errorNetReport($errid);
}

$CC_USE = "";
$SC_USE = "";
$NC_USE = "";
if(CC_USE == 'use'){
//	$CC_USE = "ccwin = window.open(prif + 'ccpopup.php?type=' + type + '&typename=' + typename + '&edate=' + edate + '&descrip=' + descrip + '&amount=' + amount,'ccwin', 'width=400, height=400, scrollbars=auto, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no');
//	ccwin.focus();";
//	$SC_USE = "ccwin = window.open(prif + 'scpopup.php?type=' + type + '&typename=' + typename + '&edate=' + edate + '&descrip=' + descrip + '&amount=' + amount + '&cdescrip=' + cdescrip + '&cosamt=' + cosamt,'ccwin', 'width=400, height=400, scrollbars=auto, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no');
//	ccwin.focus();";
//	$NC_USE = "ccwin = window.open(prif + 'ncpopup.php?type=' + type + '&typename=' + typename + '&edate=' + edate + '&descrip=' + descrip + '&amount=' + amount + '&cdescrip=' + cdescrip + '&cosamt=' + cosamt,'ccwin', 'width=400, height=400, scrollbars=auto, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no');
//	ccwin.focus();";
	$CC_USE = "popupSized(prif + 'ccpopup.php?type=' + type
	                                + '&typename=' + typename
	                                + '&edate=' + edate
	                                + '&descrip=' + descrip
	                                + '&amount=' + amount,
	                        genPopupName(), 400, 400,
	                        'scrollbars=yes, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no');";
	$SC_USE = "popupSized(prif + 'scpopup.php?type=' + type
	                                + '&typename=' + typename
	                                + '&edate=' + edate
	                                + '&descrip=' + descrip
	                                + '&amount=' + amount
	                                + '&cdescrip=' + cdescrip
	                                + '&cosamt=' + cosamt,
	                        genPopupName(), 400, 400,
	                        'scrollbars=yes, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no');";
	$NC_USE = "popupSized(prif + 'ncpopup.php?type=' + type
	                                + '&typename=' + typename
	                                + '&edate=' + edate
	                                + '&descrip=' + descrip
	                                + '&amount=' + amount
	                                + '&cdescrip=' + cdescrip
	                                + '&cosamt=' + cosamt,
	                        genPopupName(), 400, 400,
	                        'scrollbars=yes, toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no');";
}


print "
<html>
<head>
<title>".TMPL_title." : Press CTRL + P to Print, Then close this window</title>
<style type=\"text/css\">
<!--
	body {
		font-family: ".TMPL_fntFamily.";
		background-color: #FFFFFF;
		font-size: 10pt;
		color: #000000;
	}

	td, p {
		font-family: ".TMPL_fntFamily.";
		font-size: 10pt;
	}

	a {
		color: ".TMPL_lnkColor.";
		text-decoration: underline;
	}

	a:hover {
		color: ".TMPL_lnkColor.";
		text-decoration: underline;
	}

	h3, .h3 {
		font-size: ".TMPL_h3FntSize."pt;
	}

	h4, .h4 {
		font-size: ".TMPL_h4FntSize."pt;
	}

	.datacell {
		background-color: #FFFFFF;
	}

	.datacell2 {
		background-color: #FFFFFF;
	}

	th {
		background-color: #FFFFFF;
		color: #000000;
		font-size: 10pt;
		text-align: center;
	}

	th.plain {
		background-color: #FFFFFF;
		font-size: 10pt;
		text-align: center;
	}

	.balsheet_cats, .cashflow_cats {
		text-align: left;
	}

	.thkborder_left {
		border-left: 2px black solid;
	}

	.thkborder {
		border: 2px black solid;
	}

	.thkborder_right {
		border-right: 2px black solid;
	}

	.border, .border th, .border td {
		border: 1px black solid;
	}
-->
</style>
<script language='JavaScript' type='text/javascript'>
	popupCounter = 0;
	
	/* generates a unique popup name */
	function genPopupName() {
	        a = 'Snap Crackle Popup ' + (++popupCounter) + ' at ' + new Date();
	        return a;
	}
	function popupOpen(url,name) {
	        argv = popupOpen.arguments;
	        if (argv[2]) {
	                opt = argv[2];
	        } else {
	                opt = 'scrollbars=yes, statusbar=no';
	        }
	        if (!name) name = genPopupName();
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
</script>
</head>
<body>$OUTPUT";

if (isset($_REQUEST["printpage"]) && getCSetting("PRINT_DIALOG") == "y") {
	print "<script>parent.mainframe.print();</script>";
}

if (!defined("EMAIL_PAGE_DISABLED")) {
	$emailpage = relpath("emailsave_page.php");

	print "
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

print "
</body>
</html>
";
exit;
?>

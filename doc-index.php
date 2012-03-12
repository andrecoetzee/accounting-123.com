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

/*
 * index.php :: Frames
 */

require ("settings.php");
define("PRD_STATE_NOWARN", true);

#do the browser check
require ("browser_detection.php");
	if(browser_detection( 'browser' ) != "moz")
		print "
			<script>
 				window.open('browser_version.php','browser_version','height=160,width=430');
			</script>";



if (isset($_SESSION["USER_ID"])) {
		#we cant use this with a print from the browser check...
        //header("Location: index.xul.php");
        
        #so rather redirect using some javascript
        print "
 			<script>
 				document.location='index.xul.php';
 			</script>";
        exit;
}

// check to see if we should dock or undock the menu first, do so, and move on
if ( isset($_GET["action"]) ) {
	switch ( $_GET["action"] ) {
		case "dock":
			db_conn("cubit");
			$rslt = db_exec("UPDATE users SET services_menu='T' ");
			$services_menu_left = false;
			break;
		case "undock":
			db_conn("cubit");
			$rslt = db_exec("UPDATE users SET services_menu='L' ");
			$services_menu_left = true;
			break;
		default:
			break;
	}
}

// moving on
require ("menus/top_menu.php");

// was a section specified? then load the section's body
if ( isset($_GET["section"]) ) {
	switch ($_GET["section"]) {
		case "accounting":
			$section_file = "index-accounts.php";
			break;
		case "sales":
			$section_file = "index-sales.php";
			break;
		case "stock":
			$section_file = "index-stock.php";
			break;
		case "salaries":
			$section_file = "index-salaries.php";
			break;
		case "purchases":
			$section_file = "index-pchs.php";
			break;
		case "debtors":
			$section_file = "index-debtors.php";
			break;
		case "creditors":
			$section_file = "index-creditors.php";
			break;
		case "manual":
			$section_file = "help/help_general.php";
			break;
		default:
			$section_file = "main.php";
	}
} else {
	$section_file = "main.php";
}

// create the services menu cell if it should be here (user setting users->services_menu=='L")
if ( $services_menu_left == true ) {
	require ("menus/left_menu.php");

	// $LEFT_MENU was initially set in above required file, transform to cell
	$LEFT_MENU = "
	<td width=89 align=center valign=top>
		$LEFT_MENU
		<div id='notify'></div>
	</td>";
} else {
	$LEFT_MENU="";
}

// start the output
$OUTPUT = "
<html>
<head>
<title>".TMPL_title." [ $_SESSION[comp] - $_SESSION[BRAN_NAME] - $_SESSION[USER_NAME] ]</title>

<link rel=\"stylesheet\" href=\"menus/lefttheme.css\" type=\"text/css\">
<link rel=\"stylesheet\" href=\"menus/toptheme.css\" type=\"text/css\">

</head>

<body style='margin: 0px; scrolling: no;'>
<script type=\"text/javascript\" src=\"menus/cubitmenu.js\"></script>
<table width='99%' height='100%'>
<tr>
	<td height=20 valign=top align=left>
		$TOP_MENU
	</td>
	<td width=0 align=right valign=middle height=20>
		<div id='notify_msgs' style='visibility: hidden;'>
			<a id='a_notify_msgs' href='view_req.php' target='mainframe'
				onClick='javascript: getObjectById(\"notify_msgs\").style.visibility=\"hidden\";'>New Messages</a>
		</div>
		<div style='visibility: hidden; height: 0px; width: 0px;'>
			<iframe height='0px' width='0px' scrolling=none src='interval_checker.php'></iframe>
		</div>
	</td>
</tr>

<tr>
	<td height='100%' colspan=2>
		<table height='100%' width='100%'>
			<tr>
				$LEFT_MENU
				<td height='100%'>
					<iframe marginheight='0' marginwidth='0' vspace='0' name='mainframe' src='$section_file'
						height='100%' frameborder='0' width='100%' scrolling='auto'></iframe>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>

</body>

<html>";

require("template.php");

?>
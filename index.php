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

$getvars = "doc-index.php?";
if (isset($HTTP_GET_VARS)) {
	foreach($HTTP_GET_VARS as $key => $val) {
		$getvars .= "&$key=$val";
	}
}

require("_platform.php");

?>

<html>
<head>
  <title>Cubit Accounting</title>
  	<style>
		body
		{
			font-family: 'arial';
			background-color: #4477BB;
			font-size: 10pt;
			color: #FFFFFF;
		}
		h3, .h3
		{
			font-size: 12pt;
			color: #FFFFFF;
		}
		h4, .h4
		{
			font-size: 10pt;
			color: #FFFFFF;
		}
	</style>
</head>

<body onLoad='loadcubit();'>
<center>
<table border=0 height='90%' width='90%'>
	<tr>
		<td align=center><a href'#' onClick='opened=1; loadcubit();'><img src='images/newcubitlogo.jpg' border=0 alt='' title=''></a></td>
	</tr>
	<tr><td><br></td></tr>
	<tr>
		<td align=center>
			<table width='40%'>
				<tr><td><font style='font-size: 17; font-weight: bold;'>Basic Instructions:</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>To Start using cubit Click the 'Start Cubit'</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>button. If Cubit does not work, please try</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>restarting your computer or contact us for</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>assistance.</font></td></tr>
				<tr><td><br></td></tr>
				<tr><td><font style='font-size: 17; font-weight: bold;'>If you are experiencing difficulties:</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>Support does not cost anything additional</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>so please just send e-mail to <u>support@cubit.co.za</u></font></td></tr>
				<tr><td><br></td></tr>
				<tr><td><font style='font-size: 17; font-weight: bold;'>Suggestions:</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>You are our client. Without you we will not</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>have a business. Please tell us if you are</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>dissatisfied or if you think we can improve</font></td></tr>
				<tr><td><font style='font-size: 11; font-weight: bold;'>the Cubit system in any way.</font></td></tr>
				<tr><td><br></td></tr>
			</table>
		</td>
	</tr>
	<tr><td><br></td></tr>
	<tr><td align=center><input type=button onClick='opened=1; loadcubit();' value='Start Cubit'></td></tr>
<script language="javascript">
//	document.captureEvents(Event.MOUSEMOVE);
//	document.onmousemove = loadcubit;

	var opened; opened = 0;
	function loadcubit() {
		if ( window.opener == null ) {
			opened++;

			if ( opened == 2 ) {
				fullscreen = window.open('<?=$getvars?>','theframe', 'menubar=no,toolbar=no,scrolling=no,resizable=yes, top=0, left=0, width='+screen.width+',height='+screen.height);
				fullscreen.setFocus();
			}
		} else {
			top.location.href = "<?=$getvars?>";
		}
	}
</script>
</table>
</center>
</body>

</html>

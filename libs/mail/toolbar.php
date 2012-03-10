<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

# get settings

require ("../settings.php");

// remove all '
if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_POST_VARS[$key] = str_replace("'", "", $value);
	}
}
if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

// only ADMIN see the ALL accounts link
if ( $user_admin )
	$all_accounts = "&nbsp; &nbsp;<a class='maildef' href='accounts.php?aid=0' target='rightframe'>
		<b>All Accounts</b></a>

			&nbsp; &nbsp;<a class='maildef' href='newaccount.php' target='rightframe'>
		New Account</a>";
else
	$all_accounts = "";

$OUTPUT = "
<style>
body {
	margin: 0px;
	background: url('./toolbar_bg.jpg');
}
</style>
<table height='100%' width='100%' cellspacing=0 cellpadding=0>
<tr><td align=left>
	<table width=300 cellspacing=0 cellpadding=0>
	<tr><td>
                &nbsp; &nbsp;
		<a class='maildef' href='newmessage.php' target='rightframe'>
			<img border=0 src='btn_newmsg.gif' width=29 height=29 alt='New Mail Message' title='New Mail Message'></a>
		&nbsp; &nbsp;
		<a class='maildef' href='getmessages.php' target='rightframe'>
			<img border=0 src='btn_receivemsg.gif' width=29 height=29 alt='Receive Messages' title='Receive Messages'></a>
		&nbsp; &nbsp;
	</td></tr>
	</table>
</td><td align=right>
	<table width=300 cellspacing=0 cellpadding=0>
	<tr><td align=right>
		<a class='maildef' href='accounts.php' target='rightframe'>My Accounts</a>
		$all_accounts
	</td></tr>
	</table>
</td></tr>
</table>";

require ("../template.php");
?>

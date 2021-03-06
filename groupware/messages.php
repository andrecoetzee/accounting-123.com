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
require_once ("../settings.php");

// remove all '
if ( isset($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_POST[$key] = str_replace("'", "", $value);
	}
}
if ( isset($_GET) ) {
	foreach ( $_GET as $key => $value ) {
		$_GET[$key] = str_replace("'", "", $value);
	}
}

if (isset($_GET["key"])) frameset();

if ( isset($_GET["fid"]) ) {
	$fid = $_GET["fid"];
} else {
	// now folder was selected, let's show the inbox folder of the first account on the list, if any
	$rslt = db_exec("SELECT fid_inbox FROM mail_accounts,mail_account_settings
		WHERE mail_accounts.account_id=mail_account_settings.account_id
			AND ( username='".USER_NAME."' OR \"public\"='1')");

	if ( pg_num_rows($rslt) > 0 ) {
		$fid = pg_fetch_result($rslt, 0, 0);
	} else {
		$fid = 0;
	}
}

$_GET["fid"] = $fid;

// create the frames
//$messages = "<iframe class='message_frameset' src='messages.php?key=frameset&fid=$fid'></iframe>";

if (isset($_GET["print"]) && $_GET["print"]) {
	frameset();
}

function frameset()
{
	extract ($_GET);

	print "
		<frameset rows='200,*' border=3>
			<frame name='msglist' scrolling='auto' src='msglist.php?fid=$fid'>
			<frame name='viewmessage' scrolling='auto' src='viewmessage.php?fid=$fid'>
		</frameset>";
}

?>

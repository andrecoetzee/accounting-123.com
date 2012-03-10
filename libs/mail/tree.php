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

require ("object_foldertree.php");
require ("jscripts.php");

$OUTPUT = "<style> body { margin: 0px; background: #FFFFFF; } </style>
	<table height='100%' width='100%' cellpadding='0' cellspacing='0'><td height='100%' valign=top>";
$JSCRIPT .= "<script>"; // attach a script tag to it, so we can continue adding the variable lists to it
$tree = & new clsFolderTree;

extract($HTTP_POST_VARS);

if ( isset($newfolder_name) ) {
	$newfolder_name = str_replace("'", "", $newfolder_name);
	$newfolder_account += 0;

	db_conn("cubit");
	$sql = "INSERT INTO mail_folders(parent_id, account_id, icon_open, icon_closed, name, username, public)
		VALUES('0', '$newfolder_account', 'icon_inboxopen.gif', 'icon_inboxclosed.gif', '$newfolder_name',
			'".USER_NAME."', '0')";
	$rslt = db_exec($sql) or errDie("Error creating folder.");

	$OUTPUT = "<script>document.location.href='".SELF."';</script>";
	require("../template.php");
}

// make a list of all the accounts this user owns, which is public,
// and for which u have privileges
$sql = "SELECT account_id,account_name
			 FROM mail_accounts WHERE username='".USER_NAME."' OR \"public\"='1'

		UNION
		SELECT mail_accounts.account_id,account_name
			FROM mail_accounts,mail_priv_accounts
			WHERE mail_accounts.account_id = mail_priv_accounts.account_id
				AND priv_owner = '".USER_NAME."' ";

$rslt = db_exec($sql);

// go through each account and start to generate the tree
$OUTPUT .= "<table><tr><td valign=middle width=10><img src='icon_account.gif'></td>
		<td valign=middle>Accounts</td></tr></table>";

$account_count = pg_num_rows($rslt);
$account_num = 1;
if ( $account_count > 0 ) {
	while ( $row = pg_fetch_array($rslt) ) {
		$tree->reset_tree( "account", $row["account_id"] , $row["account_name"],
			$account_num++, $account_count );
		$tree->generate_tree();
		$OUTPUT .= $tree->fetch_html();
		$JSCRIPT .= $tree->fetch_java();
	}
}
/*
// create the misc folders tree
$OUTPUT .= "<table><tr><td valign=middle width=10><img src='icon_folderopen.gif'></td>
		<td valign=middle>Folders</td></tr></table>";

// create the privileged folders sub tree (folders for which u have privileges)
$tree->reset_tree( "privileged", 0, 0, 1, 2 );
$tree->generate_tree();
$OUTPUT .= $tree->fetch_html();
$JSCRIPT .= $tree->fetch_java();

// create the public folders sub tree
$tree->reset_tree( "public", 0, 0, 2, 2 );
$tree->generate_tree();
$OUTPUT .= $tree->fetch_html();
$JSCRIPT .= $tree->fetch_java();*/

// finish the output
$JSCRIPT .= "</script>";
$OUTPUT .= "</td></tr>
<tr>
	<td bgcolor='#555555'>";

db_conn("cubit");
$sql = "SELECT account_id,account_name FROM mail_accounts WHERE username='".USER_NAME."'";
$rslt = db_exec($sql) or errDie("Error fetching account list.");

if ( pg_num_rows($rslt) > 0 ) {
	$OUTPUT .= "
	<form method=post action='".SELF."'>
		<font color=white>New Folder:</font><br>
		<input type=text name=newfolder_name size=15 value=''><br>
		<font color=white>under account</font><br>
		<select name=newfolder_account>";

	while ( $row=pg_fetch_array($rslt) ) {
		$OUTPUT .= "<option value='$row[account_id]'>$row[account_name]</option>";
	}

	$OUTPUT .= "
		</select><input type=submit value='Create'>
	</form>";
}

$OUTPUT .= "
	</td>
</tr>
</table> $JSCRIPT";

require ("../template.php");
?>

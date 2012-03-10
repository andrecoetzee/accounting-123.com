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

foreach ($_POST as $key=>$value) {
	$_GET[$key] = $value;
}

foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

if (!isset($_GET["key"])) {
	$_GET["key"] = "frameset";
}

require ("object_foldertree.php");

switch ($_GET["key"]) {
case "content":
	if (!defined("SETTINGS_PHP")) {
		require_once ("../settings.php");
	}
	$OUTPUT = tree_content();
	require("gw-tmpl.php");
	break;
case "frameset":
default:
	if (isset($_GET["disp"]) && $_GET["disp"]) {
		if (!defined("SETTINGS_PHP")) {
			require_once ("../settings.php");
		}
		$OUTPUT = tree_frameset();
		require ("gw-tmpl.php");
	} else {
		$tree = tree_frameset();
	}
	break;
}

function tree_frameset() {
	$i = 0;
	$get = "";
	foreach ($_GET as $key=>$value) {

		if ($key != "key") {
			$get .= "&$key=$value";
		}
	}

	$OUT = "<iframe class='tree_frameset' src='tree.php?key=content$get'></iframe>";
	return $OUT;
}

function tree_content() {

	$OUTPUT = "
		<table height='100%' width='100%' cellpadding='0' cellspacing='0'
		style='border: 1px solid #000; background: #e4edff'><td height='100%' valign=top>";

	$JSCRIPT = "
	<script>
		var objdata = new Array();
		var ocbtndata_open = new Array();
		var ocbtndata_close = new Array();
		var ocicondata_open = new Array();
		var ocicondata_close = new Array()
	
		// shows and hides the tree
		function nodeShowHide(type,obj) {
			name_children = type + '_children_' + obj;
			name_ocbtn = type + '_ocbtn_' + obj;
			name_ocicon = type + '_ocicon_' + obj;
	
			childlayer = getObjectById(name_children); // layer which gets closed/opened
			ocbtnlayer = getObjectById(name_ocbtn); // open close button for nodes
			ociconlayer = getObjectById(name_ocicon); // open close icon next to folder name
	
	
			if ( childlayer.innerHTML == '' ) {
				childlayer.innerHTML = objdata[name_children]; // restore the data previously stored in array for this layer
				ocbtnlayer.src = ocbtndata_open[name_ocbtn].src // change the image of the node open/close btn
			} else {
				objdata[name_children] = childlayer.innerHTML; // store the data of layer in array
				childlayer.innerHTML = ''; // clear the array
				ocbtnlayer.src = ocbtndata_close[name_ocbtn].src; // change the image of the node open/close btn
			}
		}";
		
	$tree = & new clsFolderTree;

	extract($_POST);

	if ( isset($newfolder_name) && !empty($newfolder_name)) {
		$newfolder_name = str_replace("'", "", $newfolder_name);
		$newfolder_account += 0;

		db_conn("cubit");
		$sql = "INSERT INTO mail_folders(parent_id, account_id, icon_open, icon_closed, name, username, public)
			VALUES('0', '$newfolder_account', 'icon_inboxopen.gif', 'icon_inboxclosed.gif', '$newfolder_name',
				'".USER_NAME."', '0')";
		$rslt = db_exec($sql) or errDie("Error creating folder.");

// 		$OUTPUT = "<script>document.location.href='".SELF."';</script>";
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
		<td bgcolor='#777777'>";

	db_conn("cubit");
	$sql = "SELECT account_id,account_name FROM mail_accounts WHERE username='".USER_NAME."'";
	$rslt = db_exec($sql) or errDie("Error fetching account list.");

	if ( pg_num_rows($rslt) > 0 ) {
		$OUTPUT .= "
		<form method=post action='".SELF."'>
			<input type='hidden' name='disp' value='1'>
			<span class='new_folder'>New Folder:</span><br>
			<input type=text name=newfolder_name size=15 value=''><br>
			<span class='new_folder'>under account</font><br>
			<select name='newfolder_account'>";

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
	</table>
	$JSCRIPT";

	return $OUTPUT;
}
?>

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

// only ADMIN see the ALL accounts link
if ( $user_admin )
	$all_accounts = "&nbsp; &nbsp;<a class='maildef' href='javascript:ajaxLink(\"iframe.php?script=accounts.php\", \"aid=0\")'>All Accounts</a>

			&nbsp; &nbsp;<a class='maildef' href='javascript:ajaxLink(\"iframe.php?script=newaccount.php\", \"\");'>New Account</a>";
else
	$all_accounts = "";

// Mouse over


$toolbar = "
<table height='100%' width='100%' cellspacing='0' cellpadding='0'>
<tr><td align='left'>
	<table cellspacing='2' cellpadding='2' class='menu'>
		<tr>
			<td width='1'>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=diary-index.php\")'>
					Navigate
				</a>
			</td>
		</tr>
	</table>
</td><td align='right'>
	<table cellspacing='2' cellpadding='2' class='menu'>
		<tr>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=newmessage.php\")'>
					Compose Mail
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=messages.php?key=frameset&fid=0\")'>
					Inbox
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=req_gen.php\")'>
					New Message
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=view_req.php\")'>
					View Messages
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=new_con.php\")'>
					New Contact
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=list_cons.php\")'>
					List Contacts
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=todo.php\")'>
					Todo
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=document_save.php\")'>
					New Document
				</a>
			</td>
			<td>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=document_view.php\")'>
					View Documents
				</a>
			</td>
			<td width='1'>
				<a href='javascript:ajaxLink(\"iframe.php\", \"script=dashboard.php\")'>
					Today
				</a>
			</td>
		</tr>
	</table>
</td></tr>
</table>
	<!--
	<td width='35'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=newmessage.php\");'>
			<img border=0 src='btn_newmsg.gif' width=29 height=29 alt='New Mail Message' title='New Mail Message'>
		</a>
	</td>
	<td width='10%' class='tb_link'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=newmessage.php\");'>Write Mail</a>
	</td>
	<td width='35'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=getmessages.php\");'>
			<img border=0 src='btn_receivemsg.gif' width=29 height=29 alt='Receive Messages' title='Receive Messages'>
		</a>
	</td>
	<td width='10%' class='tb_link'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=getmessages.php\");'>Check Mail</a>
	</td>
	<td width='10%' align='center' class='tb_link'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=doc-add.php\");'>Add Document</a>
	</td>
	<td width='10%' align='center' class='tb_link'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=doc-view.php\");'>View Documents</a>
	</td>
	<td width='10%' align='center' class='tb_link'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=todo.php\");'>Todo</a>
	</td>
	<td align=right class='tb_link'>
		<a class='maildef' href='javascript:ajaxLink(\"iframe.php\", \"script=accounts.php\");'>My Accounts</a>
		$all_accounts
	</td>
	-->
</tr>
</table>";

// require ("../template.php");
?>

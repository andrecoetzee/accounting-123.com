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

/*****
A menu is created in the following way. The table is started with the layer in the first cell.
Then the start of the menu's script is posted.
Checks are gone through to determine which menus the user will see, and the
script is added on until the whole menu is added.
Then the menu is finished, and the Draw function is called to create the menu
******/

require ("../../settings.php");

// create the start of the menu
$OUTPUT = "
<script src='cubitmenu.js'></script>
<table cellpadding='2' cellpadding='2' width='100%'>
<tr>
	<td align='right' valign=center>
		<div id=\"cubit_menu\" style=\"height=28; border-left: 1px solid #FFFFFF\"></div>
	</td>
</tr>
</table>

<script>
	var cubitMenu = [ ";

	// Email
$OUTPUT .= "
	[null, 'Email', null, null, null,
		[null, 'Check Mail', 'getmessages.php', 'mainframe', null],
		[null, 'Compose Mail', 'newmessage.php', 'mainframe', null],
		[null, 'Inbox', 'messages.php?key=frameset&fid=0', 'mainframe', null],
		[null, 'New Account', 'newaccount.php', 'mainframe', null],
		[null, 'View Accounts', 'accounts.php', 'mainframe', null],
	],";

	// Contacts
$OUTPUT .= "
	[null, 'Contacts', null, null, null,
		[null, 'Add New Contact', 'new_con.php', 'mainframe', null],
		[null, 'View Contacts', 'list_cons.php', 'mainframe', null],
	],";

	// Diary
$OUTPUT .= "
	[null, 'Diary', null, null, null,
		[null, 'Day View', 'diary-index.php', 'mainframe', null],
		[null, 'Monthly View', 'diary-index.php?key=month', 'mainframe', null],
	],";

	// Messages
$OUTPUT .= "
	[null, 'Messages', null, null, null,
		[null, 'New Message', 'req_gen.php', 'mainframe', null],
		[null, 'View Messages', 'view_req.php', 'mainframe', null],
	],";

	// Documents
$OUTPUT .= "
	[null, 'Documents', null, null, null,
		[null, 'Add New Document', 'document_save.php', 'mainframe', null],
		[null, 'View Documents', 'document_view.php', 'mainframe', null],
		cubitmenuSplit,
		[null, 'Add Document Type', 'doc_type_save.php', 'mainframe', null],
		[null, 'View Document Types', 'doc_type_view.php', 'mainframe', null],
		cubitmenuSplit,
		[null, 'Add Document Department', 'doc_dep_save.php', 'mainframe', null],
		[null, 'View Document Departments', 'doc_dep_view.php', 'mainframe', null],
		cubitmenuSplit,
		[null, 'Document Movement Report', 'document_movement.php', 'mainframe', null],
	],";
// end the output
$OUTPUT .= "
];
	cubitmenuDraw (cubitMenu, 'cubit_menu', 'hv', cubitmenuObject, 'top');
</script>";

$toolbar = $OUTPUT;
print $toolbar;
require ("../gw-tmpl.php");
?>

<?

	require ("settings.php");
	
	$OUTPUT = show_listing ();
	
	require ("template.php");
	
function show_listing ()
{

	$display = "
	<center>
	<font size='5' style='color:white'><b>Cubit Documentation</b></font> <br><br>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<td align='center'>
				<font size='4' style='color:white'><u>Introduction</u></font> <br>
				<br>
				<a style='color:white' href='documents/intro2cubit.pdf'>Intro to Cubit</a> <br>
				<a style='color:white' href='documents/commonsupport.pdf'>Common Support</a> <br>
				<br>
				<font size='4' style='color:white'><u>About</u></font> <br>
				<br>
				<a style='color:white' href='documents/cubitcosts.pdf'>Cubit Costs</a> <br>
				<a style='color:white' href='documents/Cubit Full Manual.pdf'>Full Cubit Manual</a> <br>
				<a style='color:white' href='documents/cubitperformance.pdf'>Cubit Performance</a> <br>
				<br>
				<font size='4' style='color:white'><u>Sales</u></font> <br>
				<br>
				<a style='color:white' href='documents/Invoicing.pdf'>Invoicing</a> <br>
				<a style='color:white' href='documents/Sales Menu.pdf'>Sales Menu</a> <br>
				<a style='color:white' href='documents/Sales.pdf'>Sales</a> <br>
				<a style='color:white' href='documents/Sales person.pdf'>Sales People</a> <br>
				<br>
				<font size='4' style='color:white'><u>Purchases</u></font> <br>
				<br>
				<a style='color:white' href='documents/Purchases and orders.pdf'>Purchases and Orders</a> <br>
				<a style='color:white' href='documents/Petty Cash Book.pdf'>Petty Cash Book</a> <br>
				<a style='color:white' href='documents/Purchase person.pdf'>Purchase Person</a> <br>
			</td>
			<td align='center'>
				<br>
				<font size='4' style='color:white'><u>User Types</u></font> <br>
				<br>
				<a style='color:white' href='documents/Creditors Users.pdf'>Creditors Users </a><br>
				<a style='color:white' href='documents/Debtors Users.pdf'>Debtor Users</a> <br>
				<a style='color:white' href='documents/Salary user.pdf'>Salary User</a> <br>
				<a style='color:white' href='documents/stock user.pdf'>Stock User</a> <br>
				<a style='color:white' href='documents/Debtors and Creditors.pdf'>Debtors and Creditors</a> <br>
				<br>
				<font size='4' style='color:white'><u>Standard Functionality</u></font> <br>
				<br>
				<a style='color:white' href='documents/Financial Reports.pdf'>Financial Reports</a> <br>
				<a style='color:white' href='documents/Journal Entry Doc.pdf'>Journal Entries</a> <br>
				<a style='color:white' href='documents/Ledgers.pdf'>Ledgers</a> <br>

				<a style='color:white' href='documents/Monthly procedure.pdf'>Monthly Procedure</a> <br>
				<a style='color:white' href='documents/All_Departments.pdf'>All Departments</a> <br>
				<a style='color:white' href='documents/Audit.pdf'>Audit</a> <br>
				<a style='color:white' href='documents/Book keeper.pdf'>Book Keeper</a> <br>
				<a style='color:white' href='documents/Budgeting and cost centers.pdf'>Budgeting and Cost Centers</a> <br>
				<a style='color:white' href='documents/Cash_Book_Entry.pdf'>Cash Book Entry</a> <br>
				<a style='color:white' href='documents/Salaries on cubit.pdf'>Salaries on Cubit</a> <br>
				<a style='color:white' href='documents/Service Menu and contacts.pdf'>Service Menu and Contacts</a> <br>
				<a style='color:white' href='documents/Stock on Cubit.pdf'>Stock on Cubit</a> <br>
				<a style='color:white' href='documents/takeon.pdf'>Take On</a> <br>
				<a style='color:white' href='documents/Teams and Leads.pdf'>Teams and Leads</a> <br>
			</td>
			<td align='center' valign='top'>
				<br>
				<font size='4' style='color:white'><u>Settings</u></font> <br>
				<br>
				<a style='color:white' href='documents/Accounting Settings.pdf'>Accounting Settings</a> <br>
				<a style='color:white' href='documents/Admin Settings.pdf'>Admin Settings</a> <br>
				<a style='color:white' href='documents/Business Settings.pdf'>Business Settings</a> <br>
				<a style='color:white' href='documents/Cubit Settings.pdf'>Cubit Settings</a> <br>
				<a style='color:white' href='documents/Locale settings.pdf'>Locale Settings</a> <br>
				<a style='color:white' href='documents/Salary Settings.pdf'>Salary Settings</a> <br>
				<a style='color:white' href='documents/Sales Settings.pdf'>Sales Settings</a> <br>
				<a style='color:white' href='documents/Stock Settings.pdf'>Stock Settings</a> <br>
				
			</td>
		</tr>
	</table><br><br>".
	mkQuickLinks(

	).
	"</center>";
	return $display;

}
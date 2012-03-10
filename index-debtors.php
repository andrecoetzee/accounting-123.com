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

require ("settings.php");

Db_Connect ();

	$OUTPUT = "<center><h3>Debtors</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
	<td valign=top align=center width='33.33%'><a href=customers-new.php target=mainframe class=nav onMouseOver='imgSwop(\"ac\", \"images/addcustomersh.gif\");' onMouseOut='imgSwop(\"ac\", \"images/addcustomer.gif\");'><img src='images/addcustomer.gif' border=0 alt='New Customer' title='New Customer' name=ac><br>New Customer</a></td>
        <td valign=top align=center width='33.33%'><a href=customers-view.php target=mainframe class=nav onMouseOver='imgSwop(\"vcus\", \"images/viewcustomerssh.gif\");' onMouseOut='imgSwop(\"vcus\", \"images/viewcustomers.gif\");'><img src='images/viewcustomers.gif' border=0 alt='View Customers' title='View Customers' name=vcus><br>View Customers</a></td>
	<td valign=top align=center width='33.33%'><a href=customers-find.php target=mainframe class=nav onMouseOver='imgSwop(\"fvcus\", \"images/viewcustomerssh.gif\");' onMouseOut='imgSwop(\"fvcus\", \"images/viewcustomers.gif\");'><img src='images/viewcustomers.gif' border=0 alt='Find Customers' title='Find Customer' name=fvcus><br>Find Customer</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

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

	$OUTPUT = "<center><h3>Consignment Orders</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='20%'><a href=corder-new.php target=mainframe class=nav onMouseOver='imgSwop(\"cnewsord\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"cnewsord\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='New Consignment Order' title='New Consignment Order' name=cnewsord><br>Add Consignment Order</a></td>
		<td valign=top align=center width='20%'><a href=corder-view.php target=mainframe class=nav onMouseOver='imgSwop(\"cviewsord\", \"images/viewquotesh.gif\");' onMouseOut='imgSwop(\"cviewsord\", \"images/viewquote.gif\");'><img src='images/viewquote.gif'  border=0 alt='View Consignment Orders' title='View Consignment Orders' name=cviewsord><br>View Consignment Orders</a></td>
		<td valign=top align=center width='20%'><a href=corder-unf-view.php target=mainframe class=nav onMouseOver='imgSwop(\"cincsord\", \"images/incompletequotesh.gif\");' onMouseOut='imgSwop(\"cincsord\", \"images/incompletequote.gif\");'><img src='images/incompletequote.gif' border=0 alt='View Incomplete Consignment Orders' title='View Incomplete Consignment Orders' name=cincsord><br>View Incomplete Consignment Orders</a></td>
		<td valign=top align=center width='20%'><a href=corder-canc-view.php target=mainframe class=nav onMouseOver='imgSwop(\"ccansord\", \"images/cancelledquotesh.gif\");' onMouseOut='imgSwop(\"ccansord\", \"images/cancelledquote.gif\");'><img src='images/cancelledquote.gif' border=0 alt='View Cancelled Consignment Orders' title='View Cancelled Consignment Orders' name=ccansord><br>View Cancelled Consignment Orders</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

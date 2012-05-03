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

	$OUTPUT = "<center><h3>Orders</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='20%'><a href=sorder-new.php target=mainframe class=nav onMouseOver='imgSwop(\"newsord\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"newsord\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='New Sales Order' title='New Sales Order' name=newsord><br>Add Sales Order</a></td>
		<td valign=top align=center width='20%'><a href=sorder-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewsord\", \"images/viewquotesh.gif\");' onMouseOut='imgSwop(\"viewsord\", \"images/viewquote.gif\");'><img src='images/viewquote.gif'  border=0 alt='View Sales Orders' title='View Sales Orders' name=viewsord><br>View Sales Orders</a></td>
		<td valign=top align=center width='20%'><a href=sorder-unf-view.php target=mainframe class=nav onMouseOver='imgSwop(\"incsord\", \"images/incompletequotesh.gif\");' onMouseOut='imgSwop(\"incsord\", \"images/incompletequote.gif\");'><img src='images/incompletequote.gif' border=0 alt='View Incomplete Sales Orders' title='View Incomplete Sales Orders' name=incsord><br>View Incomplete Sales Orders</a></td>
		<td valign=top align=center width='20%'><a href=sorder-canc-view.php target=mainframe class=nav onMouseOver='imgSwop(\"cansord\", \"images/cancelledquotesh.gif\");' onMouseOut='imgSwop(\"cansord\", \"images/cancelledquote.gif\");'><img src='images/cancelledquote.gif' border=0 alt='View Cancelled Sales Orders' title='View Cancelled Sales Orders' name=cansord><br>View Cancelled Sales Orders</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

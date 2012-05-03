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

	$OUTPUT = "<center><h3>Quotes</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='20%'><a href=quote-new.php target=mainframe class=nav onMouseOver='imgSwop(\"newquo\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"newquo\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='New Quote' title='New Quote' name=newquo><br>Add Quote</a></td>
		<td valign=top align=center width='20%'><a href=quote-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewquo\", \"images/viewquotesh.gif\");' onMouseOut='imgSwop(\"viewquo\", \"images/viewquote.gif\");'><img src='images/viewquote.gif'  border=0 alt='View Quotes' title='View Quotes' name=viewquo><br>View Quotes</a></td>
		<td valign=top align=center width='20%'><a href=quote-unf-view.php target=mainframe class=nav onMouseOver='imgSwop(\"incquo\", \"images/incompletequotesh.gif\");' onMouseOut='imgSwop(\"incquo\", \"images/incompletequote.gif\");'><img src='images/incompletequote.gif' border=0 alt='View Incomplete Quotes' title='View Incomplete Quotes' name=incquo><br>View Incomplete Quotes</a></td>
		<td valign=top align=center width='20%'><a href=quote-canc-view.php target=mainframe class=nav onMouseOver='imgSwop(\"canquo\", \"images/cancelledquotesh.gif\");' onMouseOut='imgSwop(\"canquo\", \"images/cancelledquote.gif\");'><img src='images/cancelledquote.gif' border=0 alt='View Cancelled Quotes' title='View Cancelled Quotes' name=canquo><br>View Cancelled Quotes</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

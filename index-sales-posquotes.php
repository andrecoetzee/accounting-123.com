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
		<td valign=top align=center width='20%'><a href=pos-quote-new.php target=mainframe class=nav onMouseOver='imgSwop(\"posnewquo\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"posnewquo\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='New POS Quote' title='New POS Quote' name=posnewquo><br>Add POS Quote</a></td>
		<td valign=top align=center width='20%'><a href=pos-quote-view.php target=mainframe class=nav onMouseOver='imgSwop(\"posviewquo\", \"images/viewquotesh.gif\");' onMouseOut='imgSwop(\"posviewquo\", \"images/viewquote.gif\");'><img src='images/viewquote.gif'  border=0 alt='View POS Quotes' title='View POS Quotes' name=posviewquo><br>View POS Quotes</a></td>
		<td valign=top align=center width='20%'><a href=pos-quote-unf-view.php target=mainframe class=nav onMouseOver='imgSwop(\"posincquo\", \"images/incompletequotesh.gif\");' onMouseOut='imgSwop(\"posincquo\", \"images/incompletequote.gif\");'><img src='images/incompletequote.gif' border=0 alt='View Incomplete POS Quotes' title='View Incomplete POS Quotes' name=posincquo><br>View Incomplete POS Quotes</a></td>
		<td valign=top align=center width='20%'><a href=pos-quote-canc-view.php target=mainframe class=nav onMouseOver='imgSwop(\"poscanquo\", \"images/cancelledquotesh.gif\");' onMouseOut='imgSwop(\"poscanquo\", \"images/cancelledquote.gif\");'><img src='images/cancelledquote.gif' border=0 alt='View Cancelled POS Quotes' title='View Cancelled POS Quotes' name=poscanquo><br>View Cancelled POS Quotes</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

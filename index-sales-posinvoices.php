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

	$OUTPUT = "<center><h3>Point of Sale Invoices</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='20%'><a href=pos-invoice-new.php target=mainframe class=nav onMouseOver='imgSwop(\"pnewinv\", \"images/invoicesh.gif\");' onMouseOut='imgSwop(\"pnewinv\", \"images/invoice.gif\");'><img src='images/invoice.gif' border=0 alt='New Invoice' title='New Invoice' name=pnewinv><br>New Point of Sale Invoice(Cash)</a></td>
		<td valign=top align=center width='20%'><a href=pos-invoice-list.php target=mainframe class=nav onMouseOver='imgSwop(\"pviewinv\", \"images/viewinvoicesh.gif\");' onMouseOut='imgSwop(\"pviewinv\", \"images/viewinvoice.gif\");'><img src='images/viewinvoice.gif' border=0 alt='View Unprinted Invoices' title='View Unprinted Invoices' name=pviewinv><br>View Unprinted Point of Sale Invoices(Cash)</a></td>
		<td valign=top align=center width='20%'><a href=pos-invoice-view-prd.php target=mainframe class=nav onMouseOver='imgSwop(\"qpviewinv\", \"images/viewinvoicesh.gif\");' onMouseOut='imgSwop(\"qpviewinv\", \"images/viewinvoice.gif\");'><img src='images/viewinvoice.gif' border=0 alt='View Printed Invoices' title='View Printed Invoices' name=qpviewinv><br>View Printed Point of Sale Invoices(Cash)</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

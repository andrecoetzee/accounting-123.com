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

	$OUTPUT = "<center><h3>Non Stock Invoices</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='20%'><a href=nons-invoice-new.php target=mainframe class=nav onMouseOver='imgSwop(\"nonsnewinv\", \"images/invoicesh.gif\");' onMouseOut='imgSwop(\"nonsnewinv\", \"images/invoice.gif\");'><img src='images/invoice.gif' border=0 alt='New Non Stock Invoice' title='New Non Stock Invoice' name=nonsnewinv><br>New Non Stock Invoice</a></td>
		<td valign=top align=center width='20%'><a href=nons-invoice-view.php target=mainframe class=nav onMouseOver='imgSwop(\"nonsviewinv\", \"images/viewinvoicesh.gif\");' onMouseOut='imgSwop(\"nonsviewinv\", \"images/viewinvoice.gif\");'><img src='images/viewinvoice.gif' border=0 alt='View Unprinted Invoices' title='View Non Stock Invoices' name=nonsviewinv><br>View Non Stock Invoices</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

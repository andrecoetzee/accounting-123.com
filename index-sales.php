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


db_conn('cubit');
$Sl="SELECT * FROM users WHERE username='".USER_NAME."'";
$Ri=db_exec($Sl);

$data=pg_fetch_array($Ri);

if($data['help']=="P") {
	header("Location: pos-invoice-new.php");
	exit;
} else if($data['help']=="S") {
	header("Location: pos-invoice-speed.php");
	exit;
}

Db_Connect ();

	$OUTPUT = "<center><h3>Sales</h3>
	<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
	<tr>
		<td valign=top align=center width='20%'><a href=cust-credit-stockinv.php target=mainframe class=nav onMouseOver='imgSwop(\"new\", \"images/invoicesh.gif\");' onMouseOut='imgSwop(\"new\", \"images/invoice.gif\");'><img src='images/invoice.gif' border=0 alt='New Invoice' title='New Invoice' name=new><br>New Invoice</a></td>
		<td valign=top align=center width='20%'><a href=invoice-view.php target=mainframe class=nav onMouseOver='imgSwop(\"viewinv\", \"images/viewinvoicesh.gif\");' onMouseOut='imgSwop(\"viewinv\", \"images/viewinvoice.gif\");'><img src='images/viewinvoice.gif' border=0 alt='View Invoices' title='View Invoices' name=viewinv><br>View Invoices</a></td>
		<td valign=top align=center width='20%'><a href=invoice-view-prd.php target=mainframe class=nav onMouseOver='imgSwop(\"ppviewinv\", \"images/viewinvoicesh.gif\");' onMouseOut='imgSwop(\"ppviewinv\", \"images/viewinvoice.gif\");'><img src='images/viewinvoice.gif' border=0 alt='View Paid Invoices' title='View Paid Invoices' name=ppviewinv><br>View Paid Invoices</a></td>
		<td valign=top align=center width='20%'><a href=invoice-unf-view.php target=mainframe class=nav onMouseOver='imgSwop(\"pincinv\", \"images/incompleteinvoicesh.gif\");' onMouseOut='imgSwop(\"pincinv\", \"images/incompleteinvoice.gif\");'><img src='images/incompleteinvoice.gif' border=0 alt='View Incomplete Invoices' title='View Incomplete Invoices' name=pincinv><br>View Incomplete Invoices</a></td>
		<td valign=top align=center width='20%'><a href=invoice-canc-view.php target=mainframe class=nav onMouseOver='imgSwop(\"caninv\", \"images/cancelledinvoicesh.gif\");' onMouseOut='imgSwop(\"caninv\", \"images/cancelledinvoice.gif\");'><img src='images/cancelledinvoice.gif' border=0 alt='View Cancelled Invoices' title='View Cancelled Invoices' name=caninv><br>View Cancelled Invoices</a></td>
	</tr>
 	<tr>
		<td valign=top align=center width='20%'><a href=find-num.php target=mainframe class=nav onMouseOver='imgSwop(\"vcusc\", \"images/viewcustomerssh.gif\");' onMouseOut='imgSwop(\"vcusc\", \"images/viewcustomers.gif\");'><img src='images/viewcustomers.gif' border=0 alt='View Temp/Invoice number' title='View Temp/Invoice number' name=vcusc><br>View Temp/Invoice number</a></td>
		<td valign=top align=center width='20%'><a href=index-sales-posinvoices.php target=mainframe class=nav onMouseOver='imgSwop(\"pnewinv\", \"images/invoicesh.gif\");' onMouseOut='imgSwop(\"pnewinv\", \"images/invoice.gif\");'><img src='images/invoice.gif' border=0 alt='POS Invoices' title='POS Invoices' name=pnewinv><br>Point of Sale Invoices (Cash)</a></td>
		<td valign=top align=center width='20%'><a href=index-sales-nonsinvoices.php target=mainframe class=nav onMouseOver='imgSwop(\"nonsnewinv\", \"images/invoicesh.gif\");' onMouseOut='imgSwop(\"nonsnewinv\", \"images/invoice.gif\");'><img src='images/invoice.gif' border=0 alt='Non Stock Invoices' title='Non Stock Invoices' name=nonsnewinv><br>Non Stock Invoices</a></td>
		<td valign=top align=center width='20%'><a href=index-sales-quotes.php target=mainframe class=nav onMouseOver='imgSwop(\"newquo\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"newquo\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='Quotes' title='Quotes' name=newquo><br>Quotes</a></td>
		<td valign=top align=center width='20%'><a href=index-sales-posquotes.php target=mainframe class=nav onMouseOver='imgSwop(\"posnewquo\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"posnewquo\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='POS Quotes' title='POS Quotes' name=posnewquo><br>POS Quotes</a></td>
	<tr>
		<td valign=top align=center width='20%'><a href=invoice-note-view-prd.php target=mainframe class=nav onMouseOver='imgSwop(\"creditnote\", \"images/invoicesh.gif\");' onMouseOut='imgSwop(\"creditnote\", \"images/invoice.gif\");'><img src='images/invoice.gif' border=0 alt='View Credit Notes' title='View Credit Notes' name=creditnote><br>View Credit Notes</a></td>
		<td valign=top align=center width='20%'><a href=index-sales-orders.php target=mainframe class=nav onMouseOver='imgSwop(\"newsord\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"newsord\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='New Sales Order' title='New Sales Order' name=newsord><br>Orders</a></td>
		<td valign=top align=center width='20%'><a href=index-sales-consignment.php target=mainframe class=nav onMouseOver='imgSwop(\"cnewsord\", \"images/quotesh.gif\");' onMouseOut='imgSwop(\"cnewsord\", \"images/quote.gif\");'><img src='images/quote.gif'  border=0 alt='New Consignment Order' title='New Consignment Order' name=cnewsord><br>Consignment Orders</a></td>
		<td valign=top align=center width='20%'><a href=sales-reports.php target=mainframe class=nav onMouseOver='imgSwop(\"rep\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"rep\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Reports' title='Reports' name=rep><br>Reports</a></td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

        require ("template.php");
?>

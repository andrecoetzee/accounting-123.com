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

$OUTPUT = "<center><h3>Purchases</h3><br>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
<td valign=top align=center width='25%'><a href='purchase-new.php' target=mainframe class=nav onMouseOver='imgSwop(\"purch\", \"images/newpurchasesh.gif\");' onMouseOut='imgSwop(\"purch\", \"images/newpurchase.gif\");'><img src='images/newpurchase.gif' border=0 alt='New Purchase' title='New Purchase' name=purch><br>New Purchase</a></td>
<td valign=top align=center width='25%'><a href='purchase-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vpurch\", \"images/viewpurchasesh.gif\");' onMouseOut='imgSwop(\"vpurch\", \"images/viewpurchase.gif\");'><img src='images/viewpurchase.gif' border=0 alt='View Purchases' title='View Purchases' name=vpurch><br>View Purchases</a></td>
<td valign=top align=center width='25%'><a href='purchase-view-prd.php' target=mainframe class=nav onMouseOver='imgSwop(\"rvpurch\", \"images/viewpurchasesh.gif\");' onMouseOut='imgSwop(\"rvpurch\", \"images/viewpurchase.gif\");'><img src='images/viewpurchase.gif' border=0 alt='View Purchases' title='View Received Purchases' name=rvpurch><br>View Received Purchases</a></td>
<td valign=top align=center width='25%'><a href='purch-canc-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"cancelpurch\", \"images/newpurchasesh.gif\");' onMouseOut='imgSwop(\"cancelpurch\", \"images/newpurchase.gif\");'><img src='images/newpurchase.gif' border=0 alt='View Cancelled Purchases' title='View Cancelled Purchases' name=cancelpurch><br>View Cancelled Purchases</a></td>
</tr>
<tr>
<td valign=top align=center width='25%'><a href='purch-int-new.php' target=mainframe class=nav onMouseOver='imgSwop(\"inpurch\", \"images/newpurchasesh.gif\");' onMouseOut='imgSwop(\"inpurch\", \"images/newpurchase.gif\");'><img src='images/newpurchase.gif' border=0 alt='Add International Purchases' title='Add International Purchase' name=inpurch><br>Add International Purchase</a></td>
<td valign=top align=center width='25%'><a href='purch-int-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vinpurch\", \"images/viewstockordersh.gif\");' onMouseOut='imgSwop(\"vinpurch\", \"images/viewstockorder.gif\");'><img src='images/viewstockorder.gif'  border=0 alt='View International Purchases' title='View International Purchases' name=vinpurch><br>View International Purchases</a></td>
<td valign=top align=center width='25%'><a href='purch-int-view-prd.php' target=mainframe class=nav onMouseOver='imgSwop(\"rvinpurch\", \"images/viewstockordersh.gif\");' onMouseOut='imgSwop(\"rvinpurch\", \"images/viewstockorder.gif\");'><img src='images/viewstockorder.gif'  border=0 alt='View Received International Purchases' title='View Received International Purchases' name=rvinpurch><br>View Received International Purchases</a></td>
<td valign=top align=center width='25%'><a href=pchs-reports.php target=mainframe class=nav onMouseOver='imgSwop(\"rep\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"rep\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Reports' title='Reports' name=rep><br>Reports</a></td>
</tr>
<tr>
<td valign=top align=center width='25%'><a href='nons-purchase-new.php' target=mainframe class=nav onMouseOver='imgSwop(\"ppurch\", \"images/newpurchasesh.gif\");' onMouseOut='imgSwop(\"ppurch\", \"images/newpurchase.gif\");'><img src='images/newpurchase.gif' border=0 alt='New Non Stock Purchase' title='New Non Stock Purchase' name=ppurch><br>New Non Stock Purchase</a></td>
<td valign=top align=center width='25%'><a href='nons-purchase-view.php' target=mainframe class=nav onMouseOver='imgSwop(\"vvpurch\", \"images/viewpurchasesh.gif\");' onMouseOut='imgSwop(\"vvpurch\", \"images/viewpurchase.gif\");'><img src='images/viewpurchase.gif' border=0 alt='View Purchases' title='View Purchases' name=vvpurch><br>View Non Stock Purchases</a></td>
<td valign=top align=center width='25%'><a href='nons-purchase-view-prd.php' target=mainframe class=nav onMouseOver='imgSwop(\"nrvpurch\", \"images/viewpurchasesh.gif\");' onMouseOut='imgSwop(\"nrvpurch\", \"images/viewpurchase.gif\");'><img src='images/viewpurchase.gif' border=0 alt='View Non Stock Purchases' title='View Received Non Stock Purchases' name=nrvpurch><br>View Received Non Stock Purchases</a></td>

</tr>
<tr>
</tr>

</table>
<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
</table></center>";

	require ("template.php");
?>

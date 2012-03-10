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

/*
 * index-accnt.php :: accounts index
 */

require ("settings.php");

$OUTPUT = "
<br>
<center>
<h3>Accounting</h3>
<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>
<tr>
	<td valign=top align=center width='25%'><a href=company-new.php target=mainframe class=nav onMouseOver='imgSwop(\"companyadd\", \"images/banksh.gif\");' onMouseOut='imgSwop(\"company\", \"images/bank.gif\");'><img src='images/bank.gif' border=0 alt='Add Company' title='Add Company' name=companyadd><br>Add Company</a></td>
	<td valign=top align=center width='25%'><a href=company-view.php target=mainframe class=nav onMouseOver='imgSwop(\"companyview\", \"images/viewbanksh.gif\");' onMouseOut='imgSwop(\"companyview\", \"images/viewbank.gif\");'><img src='images/viewbank.gif' border=0 alt='View Company' title='View Company' name=companyview><br>View Company</a></td>
	<td valign=top align=center width='25%'><a href=admin-branadd.php target=mainframe class=nav onMouseOver='imgSwop(\"branadd\", \"images/banksh.gif\");' onMouseOut='imgSwop(\"branadd\", \"images/bank.gif\");'><img src='images/bank.gif' border=0 alt='Add Branch' title='Add Branch' name=branadd><br>Add Branch</a></td>
	<td valign=top align=center width='25%'><a href=admin-branview.php target=mainframe class=nav onMouseOver='imgSwop(\"branview\", \"images/viewbanksh.gif\");' onMouseOut='imgSwop(\"branview\", \"images/viewbank.gif\");'><img src='images/viewbank.gif' border=0 alt='Add Branch' title='View Branch' name=branview><br>View Branch</a></td>
</tr>
<tr>

	<td valign=top align=center width='25%'><a href='company-export.php' target=mainframe class=nav onMouseOver='imgSwop(\"companyexport\", \"images/viewmultibatchsh.gif\");' onMouseOut='imgSwop(\"companyexport\", \"images/viewmultibatch.gif\");'><img src='images/viewmultibatch.gif' border=0 alt='Export Company' title='Export Company' name=companyexport><br>Export Company</a></td>
	<td valign=top align=center width='25%'><a href='company-import.php' target=mainframe class=nav onMouseOver='imgSwop(\"companyimport\", \"images/batchsh.gif\");' onMouseOut='imgSwop(\"companyimport\", \"images/batch.gif\");'><img src='images/batch.gif' border=0 alt='Import Company' title='Import Company' name=companyimport><br>Import Company</a></td>
	<td valign=top align=center width='25%'><a href=reporting/index-multi-reports.php target=mainframe class=nav onMouseOver='imgSwop(\"reports\", \"images/reportsh.gif\");' onMouseOut='imgSwop(\"reports\", \"images/report.gif\");'><img src='images/report.gif' border=0 alt='Reports' title='Reports' name=reports><br>Reports</a></td>
</tr>
<tr>

</tr>
</table>
<table border=0 cellpadding='2' cellspacing='1' width=15%>
<tr><td>
<br>
</td></tr>
<tr><th>Quick Links</th></tr>
<tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
</center>
</table>


";

require ("template.php");
?>

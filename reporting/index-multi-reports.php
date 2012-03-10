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

require ("../settings.php");

//<tr><td align=center><b><a href='multi-vat-report.php' class=nav>View Vat Report</a></b></td></tr>

$OUTPUT = "<center>
<h3>Head Office Reports</h3>
<table border=0 width='90%'><tr>
<td valign=top width='33%'><table width='90%'>
<tr><td align=center><h3>Banking</h3></td></tr>
<tr><td align=center><b><a href='multi-not-banked.php' class=nav>List Outstanding Bank Payments/Receipts</a></b></td></tr>
<tr><td align=center><b><a href='multi-banked.php' class=nav>Cash Book Analysis of Payments/Receipts</a></b></td></tr>
<tr><td align=center><b><a href='multi-bank-recon-saved.php' class=nav>View Saved Bank Reconciliations</a></b></td></tr>
</table></td>
<td valign=top width='33%'>
<table border=0 width='90%'>
<tr><td align=center><h3>Other</h3></td></tr>
<tr><td align=center><b><a href='reports-vat.php' class=nav>View Vat Report</a></b></td></tr>
<tr><td align=center><b><a href='../core/period-view.php' class=nav>View Current Period</a></b></td></tr>
</table></td>
<td valign=top width='33%'>
<table width='90%'>
<tr><td align=center><h3>Debtors & Creditors</h3></td></tr>
<tr><td align=center><b><a href='multi-debt-age-analysis.php' class=nav>Debtors Age Analysis</a></b></td></tr>
<tr><td align=center><b><a href='multi-cred-age-analysis.php' class=nav>Creditors Age Analysis</a></b></td></tr>
</table></td>

</tr>

<tr>
<td valign=top width='33%'><table width='90%'>
<tr><td align=center><h3>Accounts</h3></td></tr>
<tr><td align=center><b><a href='multi-allcat.php' class=nav>ALL Categories and Related Accounts</a></b></td></tr>
</table></td>
<td valign=top width='33%'>
<table border=0 width='90%'>
<tr><td align=center><h3>Financial Statements</h3></td></tr>
<tr><td align=center><b><a href='trial_bal.php' class=nav>Generate Trial Balance</a></b></td></tr>
<tr><td align=center><b><a href='trial_bal-view.php' class=nav>View Saved Trial Balances</a></b></td></tr>
<tr><td align=center><b><a href='income-stmnt.php' class=nav>Generate Income Statement</a></b></td></tr>
<tr><td align=center><b><a href='income-stmnt-view.php' class=nav>View Saved Income Statements</a></b></td></tr>
<!--<tr><td align=center><b><a href='../core/set-bal-sheet-edit.php' class=nav>Edit Balance Sheet</a></b></td></tr>-->
<tr><td align=center><b><a href='bal-sheet.php' class=nav>Generate Balance Sheet</a></b></td></tr>
<tr><td align=center><b><a href='bal-sheet-view.php' class=nav>View Saved Balance Sheets</a></b></td></tr>

</table></td>
<td valign=top width='33%'>
<table width='90%'>
<tr><td align=center><h3>Journals</h3></td></tr>
<tr><td align=center><b><a href='multi-alltrans.php' class=nav>All Journal Entries</a></b></td></tr>
<tr><td align=center><b><a href='multi-alltrans-prd.php' class=nav>All Journal Entries (Period Range)</a></b></td></tr>

<!--
<tr><td align=center><b><a href='multi-acc-trans.php' class=nav>Journal Entries Per Account</a></b></td></tr>
<tr><td align=center><b><a href='multi-acc-trans-prd.php' class=nav>Journal Entries Per Account (Period Range)</a></b></td></tr>
<tr><td align=center><b><a href='multi-accsub-trans.php' class=nav>Journal Entries Per Main Account</a></b></td></tr>
<tr><td align=center><b><a href='multi-cat-trans.php' class=nav>Journal Entries Per Category</a></b></td></tr>
-->

</table>
</td>
</tr>
</table>
<p>
<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
</table>
</center>";

require ("../template.php");
?>

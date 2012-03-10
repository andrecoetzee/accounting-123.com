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

$OUTPUT = "<center>
<table border=0 width='100%'><tr><td align=center>
<table border=0 width='100%'>
<tr><td align=center><h3>Custom Financial Statements</h3></td></tr>
<tr><td align=center><b><a href='gen-trial-balance.php' class=nav>Generate Trial Balance</a></b></td></tr>
<tr><td align=center><b><a href='set-trial-balance.php' class=nav>Set Trial Balance</a></b></td></tr>
<tr><td align=center><b><a href='gen-income-stmnt.php' class=nav>Generate Income Statement</a></b></td></tr>
<tr><td align=center><b><a href='set-income-stmnt.php' class=nav>Set Income Statement</a></b></td></tr>
<tr><td align=center><b><a href='gen-balance-sheet.php' class=nav>Generate Balance Sheet</a></b></td></tr>
<tr><td align=center><b><a href='set-balance-sheet.php' class=nav>Set Balance Sheet</a></b></td></tr>
</table>
</td>
</tr>
</table>
<p>
<table border=0 cellpadding='2' cellspacing='1'>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</table>";

require ("../template.php");
?>

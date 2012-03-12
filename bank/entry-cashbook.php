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

require("../settings.php");

if(isset($_POST["key"])) {


} else {
	$OUTPUT = enter($_POST);
}

require("../template.php");

function enter($_POST) {
	extract($_POST);

	$ledgers="<select name=ledger>
	<option value='sel'>Select Ledger</option>
	<option value='Customer Ledger'>Customer Ledger</option>
	<option value='Employee Ledger>Employee Ledger</option>
	<option value='General Ledger'>General Ledger</option>
	<option value='Supplier Ledger'>Supplier Ledger</option>
	</select>";

	$out="<h3>Cashbook Entry</h3>
	<table border=0 cellpadding=1 cellspacing=1>
	<form action='".SELF."' method=post>
	<tr><th>Ledger</th><th>Account</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>$ledgers</td></tr>";

}






























?>
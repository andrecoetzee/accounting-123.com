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

# get settings
require ("../settings.php");

$OUTPUT = entries($_GET);

require("../template.php");

function entries($_GET) {

	extract($_GET);

	db_conn('cubit');

	$i=0;

	$Sl="SELECT * FROM cashbook WHERE trantype='$trantype' AND amount='$amount' AND banked='no' AND rid!=333";
	$Ri=db_exec($Sl);

	$data="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Date</th><th>Description</th><th>Amount</th></tr>";

	while($cd=pg_fetch_array($Ri)) {

		if($cd['trantype']!="deposit") {
			$cd['amount']=-$cd['amount'];
		}

		$data.="<tr bgcolor=$bgcolor><td>$cd[date]</td><td>$cd[descript]</td><td align=right>R $cd[amount]</td></tr>";

	}

	$data.="</table>";

	return $data;

}





?>


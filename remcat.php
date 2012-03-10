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

require("settings.php");

$OUTPUT = remove();


require("template.php");

function remove() {

	db_conn('core');

	$Sl="SELECT * FROM income";
	$Ri=db_exec($Sl);

	while($ad=pg_fetch_array($Ri)) {

		$Sl="SELECT * FROM accounts WHERE catid='$ad[catid]'";
		$Rp=db_exec($Sl);

		if(pg_num_rows($Rp)<1) {
			$Sl="DELETE FROM income WHERE catid='$ad[catid]'";
			$Rp=db_exec($Sl);
		}
	}

	$Sl="SELECT * FROM balance";
	$Ri=db_exec($Sl);

	while($ad=pg_fetch_array($Ri)) {

		$Sl="SELECT * FROM accounts WHERE catid='$ad[catid]'";
		$Rp=db_exec($Sl);

		if(pg_num_rows($Rp)<1) {
			$Sl="DELETE FROM balance WHERE catid='$ad[catid]'";
			$Rp=db_exec($Sl);
		}
	}

	$Sl="SELECT * FROM expenditure";
	$Ri=db_exec($Sl);

	while($ad=pg_fetch_array($Ri)) {

		$Sl="SELECT * FROM accounts WHERE catid='$ad[catid]'";
		$Rp=db_exec($Sl);

		if(pg_num_rows($Rp)<1) {
			$Sl="DELETE FROM expenditure WHERE catid='$ad[catid]'";
			$Rp=db_exec($Sl);
		}
	}

	return "Done
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	}


?>
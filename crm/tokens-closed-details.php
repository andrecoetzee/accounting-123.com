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

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		default:
			$OUTPUT = "Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "Invalid";
}

require("template.php");

function details($_GET) {

	extract($_GET);
	$id+=0;

	db_conn('crm');

	$Sl="SELECT * FROM closedtokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data.");

	if(pg_numrows($Ry)<1) {
		return "Invalid.";
	}

	$tokendata=pg_fetch_array($Ry);
	
	$id=$tokendata['tid'];
	
	$Sl="SELECT * FROM teams WHERE id='$tokendata[teamid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get query actions from db.");
	$teamdata=pg_fetch_array($Ry);

	db_conn('cubit');

	if($tokendata['csct']=="Customer") {
		$Sl="SELECT accno,balance FROM customers WHERE cusnum='$tokendata[csc]'";
		$Ry=db_exec($Sl) or errDie("Unable to get customer details.");

		if(pg_numrows($Ry)<1) {
			$balance="<li class=err>Invalid Customer</li>";
			$ex1="";
			$ex2="";
			$accnum="";
		} else {
			$cusdata=pg_fetch_array($Ry);
			$balance=$cusdata['balance'];
			$accnum=$cusdata['accno'];
			$ex1="<tr><td colspan=2 align=center><input type=button value='View Customer Details' onclick='openwindow(\"../cust-det.php?cusnum=$tokendata[csc]\")'></td></tr>";
			$ex2="<tr><td colspan=2 align=center><input type=button value='Print Customer Statement' onclick='openwindow(\"../cust-stmnt.php?cusnum=$tokendata[csc]\")'></td></tr>";

		}
	} elseif ($tokendata['csct']=="Supplier") {
		$Sl="SELECT supno,balance FROM suppliers WHERE supid='$tokendata[csc]'";
		$Ry=db_exec($Sl) or errDie("Unable to get customer details.");

		if(pg_numrows($Ry)<1) {
			$balance="<li class=err>Invalid Customer</li>";
			$ex1="";
			$ex2="";
			$accnum="";
			$accnum="";
		} else {
			$supdata=pg_fetch_array($Ry);
			$balance=$supdata['balance'];
			$accnum=$supdata['supno'];
			$ex1="<tr><td colspan=2 align=center><input type=button value='View Supplier Details' onclick='openwindow(\"../supp-det.php?supid=$tokendata[csc]\")'></td></tr>";
			$ex2="<tr><td colspan=2 align=center><input type=button value='Print Supplier Statement' onclick='openwindow(\"../supp-stmnt.php?supid=$tokendata[csc]\")'></td></tr>";

		}
	} elseif ($tokendata['csct']=="Contact") {
		$balance="0.00";
		$accnum="";
		$ex1="<tr><td colspan=2 align=center><input type=button value='View Contact Details' onclick='openwindow(\"../view_con.php?id=$tokendata[csc]\")'></td></tr>";
		$ex2="";
	} else {
		return "Invalid.";
	}


	$i=0;

	db_conn('crm');

	$i=0;
	$pactions="";
	$Sl="SELECT donedate,donetime,action,doneby FROM closed_token_actions WHERE token='$id' ORDER BY id DESC";
	$Ry=db_exec($Sl) or errDie("Unable to get query actions from system.");

	while($pdata=pg_fetch_array($Ry)) {
		$i++;

		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
		
		$pactions.="<tr bgcolor='$bgcolor'><td>$pdata[donedate], ".substr($pdata['donetime'],0,5)."</td><td>$pdata[action]</td><td>$pdata[doneby]</td></tr>";
		
	}
	
	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=750>
	<tr>
		<td colspan=4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr>
			<td align=center><h3>QUERY: $id</h3></td>
		</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td width='22%' valign=top align=center>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='80%'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='index.php'>My Business</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</a></td></tr>
		</table>
		</td>
		<td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr bgcolor='".TMPL_tblDataColor1."'><th>Query Category</th><td>$tokendata[cat]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><th>SUBJECT/SUMMARY:</th><td>$tokendata[sub]</td></tr>
		<tr><th colspan=2>Query Notes</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><pre>$tokendata[notes]</pre></td></tr>
		</table>
		</td>
		<td colspan=2 valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><th colspan=2>Query Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Team & User</td><td>$teamdata[name], $tokendata[username]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Created</td><td>$tokendata[opendate] By: $tokendata[openby]</td></tr>
		$ex1
		$ex2
		</table>
		</td>
	</tr>
	<tr>
		<td rowspan=2 valign=top colspan=2>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><td colspan=3 align=center><h4>Actions</h4></td></tr>
		<tr><th>Date</th><th>Action</th><th>Done By</th></tr>
		$pactions
		</table>
		</td>
	</tr>
	<tr>
		<td valign=top></td>

		<td align=right>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><th colspan=2>$tokendata[csct] Information</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Acc Num</td><td>$accnum</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td>$tokendata[name]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Contact</td><td>$tokendata[con]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel</td><td>$tokendata[tel]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Cell</td><td>$tokendata[cell]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Fax</td><td>$tokendata[fax]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td>$tokendata[email]</td></tr>
		<tr><th colspan=2>Address</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2 align=center><pre>$tokendata[address]</pre></td></tr>
		</table>
		</td>
	</tr>
	</table>";

	return $out;

}

?>

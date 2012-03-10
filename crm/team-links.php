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

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "update":
			$OUTPUT = update($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
} elseif(isset($HTTP_GET_VARS["id"])) {
	$OUTPUT = allocate($HTTP_GET_VARS);
} else {
	$OUTPUT = "Invalid.";
}

$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='team-add.php'>Add Team</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='team-list.php'>View Teams</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function allocate($HTTP_POST_VARS){
	extract($HTTP_POST_VARS);
	$id+=0;

	db_conn('crm');

	$Sl="SELECT * FROM teams WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get team.");

	if(pg_numrows($Ry)<1) {
		return "Invalid team.";
	}

	$teamdata=pg_fetch_array($Ry);

	$Sl="SELECT * FROM links ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get links from system.");

	if(pg_numrows($Ry)<1) {
		$Sl="INSERT INTO links (name,script) VALUES ('Add Client','../customers-new.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('View Client','../customers-view.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('New Invoice','../cust-credit-stockinv.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('Find Invoice','../invoice-search.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('View Stock','../stock-view.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('Add Supplier','../supp-new.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('View Suppliers','../supp-view.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('New Purchase','../purchase-new.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('View Purchases','../purchase-view.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('Add Quote','../quote-new.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('View Invoices','../invoice-view.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('View Quotes','../quote-view.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('Debtors Age Analysis','../reporting/debt-age-analysis.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('Creditors Age Analysis','../reporting/cred-age-analysis.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="INSERT INTO links (name,script) VALUES ('Bank Reconciliation','../reporting/bank-recon.php')";
		$Ry=db_exec($Sl) or errDie("Unable to insert link.");
		$Sl="SELECT * FROM links ORDER BY name";
		$Ry=db_exec($Sl) or errDie("Unable to get links from system.");
	}

	$i=1;

	$out = "
		<h3>Select Team Links for: $teamdata[name]</h3>
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='id' value='$id'>
			<tr>
				<th>Link</th>
				<th>Order</th>
				<th>Selected</th>
			</tr>";

	db_conn('crm');

	while($linkdata=pg_fetch_array($Ry)) {
		$bgcolor = ($i % 2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$lid=$linkdata['id'];
		
		$Sl="SELECT * FROM teamlinks WHERE team='$id' AND name='$linkdata[name]'";
		$Rt=db_exec($Sl) or errDie("Unable to get data from system.");

		if(pg_numrows($Rt)>0) {
			$teamlinkdata=pg_fetch_array($Rt);
			$ch="checked";
			$num=$teamlinkdata['num'];
		} else {
			$ch="";
			$num="";
		}

		$out .= "
			<tr bgcolor='$bgcolor'>
				<td>$linkdata[name]</td>
				<td><input type='text' size='2' name=order[$lid] value='$num'></td>
				<td><input type='checkbox' name=sel[$lid] $ch></td>
			</tr>";
	}

	$out .= "
			<tr>
				<td coslpan='3' align='right'><input type='submit' value='Update &raquo;'></td>
			</tr>
		</form>
		</table>";

	return $out;

}

function update($HTTP_POST_VARS) {
	extract($HTTP_POST_VARS);

	$id+=0;

	db_conn('crm');

	$Sl="SELECT * FROM teams WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get team.");

	if(pg_numrows($Ry)<1) {
		return "Invalid team.";
	}

	$teamdata=pg_fetch_array($Ry);

	$Sl="DELETE FROM teamlinks WHERE team='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to update data.");

	$Sl="SELECT * FROM links ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get links from system.");

	if(pg_numrows($Ry)<1) {
		return allocate($HTTP_POST_VARS);
	}

	$i=1;
	
	while($linkdata=pg_fetch_array($Ry)) {
		$lid=$linkdata['id'];
		//$out.="<tr bgcolor='$bgcolor'><td>$linkdata[name]</td><td><input type=text size=2 name=order[$lid]></td><td><input type=checkbox name=sel[$lid]></td></tr>";

		if(isset($sel[$lid])) {

			$order[$lid]+=0;

			$Sl="INSERT INTO teamlinks (team,num,name,script) VALUES ('$id','$order[$lid]','$linkdata[name]','$linkdata[script]')";
			$Rt=db_exec($Sl) or errDie("Unable to insert data into system.");
		}
	}

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>System updated</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Team link has been set.</td></tr>
	</table>";

	return $out;
}

?>







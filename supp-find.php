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
require ("libs/ext.lib.php");
require_lib("validate");

if ( isset($_GET['addcontact']) ) {
	$OUTPUT = AddContact();
	$OUTPUT .= printSupp ($_GET);
} else {
	# show current stock
	$OUTPUT = printSupp ($_GET);
}

require ("template.php");

# show stock
function printSupp ($_GET)
{
	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}

	if(isset($filter) && !isset($all)){
		$sqlfilter = " AND lower($filter) LIKE lower('%$fval%')";
		$show=true;
	}else{
		$show=false;
		$filter = "";
		$fval = "";
		$sqlfilter = "";
	}
	
	if(isset($all)) {
		$show=true;
	}

	$filterarr = array("supname" => "Supplier Name", "supno" => "Account Number");
	$filtersel = extlib_cpsel("filter", $filterarr, $filter);

	# Set up table to display in
	$printSupp = "
	<h3>Find Supplier</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=get>
	<tr><th>.: Filter :.</th><th>.: Value :.</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>$filtersel</td><td><input type=text size=20 name=fval value='$fval'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td align=center><input type=submit name=all value='View All'></td><td align=center><input type=submit value='Apply Filter'></td></tr>
	</form>
	</table>";
	
	if($show) {
	
		$printSupp .= "<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Department</th><th>Supp No.</th><th>Supplier Name</th><th>Type</th><th>Curr</th><th>Contact Name</th><th>Tel No.</th><th>Fax No.</th><th colspan=2>Balance</th><th colspan=6>Options</th></tr>";
	
		# connect to database
		db_connect();
	
		# Query server
		$i = 0;
		$tot=0;
		$sql = "SELECT * FROM suppliers WHERE (div = '".USER_DIV."' OR ddiv = '".USER_DIV."') $sqlfilter ORDER BY supname ASC";
		$suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
		if (pg_numrows ($suppRslt) < 1) {
			$printSupp .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=20><li>There are no Suppliers in Cubit.</td></tr>";
		}else{
			while ($supp = pg_fetch_array ($suppRslt)) {
				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$deptname = "<li class=err>Department not Found.";
				}else{
					$dept = pg_fetch_array($deptRslt);
					$deptname = $dept['deptname'];
				}
				$supp['balance']=sprint($supp['balance']);
	
				# Check if record can be removed
				db_connect();
				$sql = "SELECT * FROM cashbook WHERE banked = 'no' AND supid = '$supp[supid]' AND div = '".USER_DIV."'";
				$rs = db_exec($sql) or errDie("Unable to get cashbook entries.",SELF);
				if(pg_numrows($rs) < 1 && $supp['balance'] == 0){
					$rm="<a href='supp-rem.php?supid=$supp[supid]'>Remove</a>";
				}else{
					$rm = "";
				}
				#if($supp['balance']==0) {$rm="<a href='supp-rem.php?supid=$supp[supid]'>Remove</a>";} else {$rm="";}
	
				// check if supplier can be added to contact list
				$addcontact = "<td><a href='conper-add.php?type=supp&id=$supp[supid]'>Add Contact</a></td>";
	
				$tot = $tot + $supp['balance'];
	
				# Locations drop down
				$locs = array("loc"=>"Local", "int"=>"International", "" => "");
				$loc = $locs[$supp['location']];
	
				$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
	
				$fbal = "$sp4--$sp4";
				$trans = "<a href='core/supp-trans.php?supid=$supp[supid]'>Transaction</a>";
				if($supp['location'] == 'int'){
					$fbal = "$sp4 $supp[currency] $supp[fbalance]";
					$trans = "<a href='core/intsupp-trans.php?supid=$supp[supid]'>Transaction</a>";
				}
	
				# Alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$printSupp .= "<tr bgcolor='$bgColor'><td>$deptname</td><td>$supp[supno]</td><td align=center>$supp[supname]</td>
				<td align=center>$loc</td><td align=center>$supp[currency]</td><td>$supp[contname]</td><td>$supp[tel]</td>
				<td>$supp[fax]</td><td align=right>$sp4 ".CUR." $supp[balance]</td><td align=right>$fbal</td><td><a href='supp-det.php?supid=$supp[supid]'>Details</a></td>
				<td><a href='#' onclick='openPrintWin(\"supp-stmnt.php?supid=$supp[supid]\")'>Statement</a></td>
				<td>$trans</td>
				<td><a href='supp-edit.php?supid=$supp[supid]'>Edit</a></td>";
	
				if($supp['blocked'] == 'yes'){
					$printSupp .= "<td><a href='supp-unblock.php?supid=$supp[supid]'>Unblock</a></td>";
				}else{
					$printSupp .= "<td><a href='supp-block.php?supid=$supp[supid]'>Block</a></td>";
				}
	
				$printSupp .= "<td>$rm</td>$addcontact</tr>";
				$i++;
			}
			if ($i>1){$s="s";} else {$s="";}
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$tot=sprint($tot);
				$printSupp .= "<tr bgcolor='$bgColor'><td colspan=8>Total Amount Owed, to $i supplier$s </td><td align=right>".CUR." $tot</td></tr>";
		}
		$printSupp .= "</table>";
	}

	$printSupp .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='supp-new.php'>Add Supplier</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printSupp;
}

// add's the supplier to the contact list
function AddContact() {
	global $_GET;

	$v = & new Validate();
	if ( ! $v->isOk($_GET["addcontact"], "num", 1, 9, "") )
		return "Invalid Supplier Number";

	// check if supplier can be added to contact list
	$rslt = db_exec("SELECT * FROM cons WHERE supp_id='$_GET[addcontact]'");
	if ( pg_numrows($rslt) >= 1 ) {
		return "Supplier Already Added as a Contact<br>";
	}

	// get it from the db
	$sql = "SELECT * FROM suppliers WHERE supid='$_GET[addcontact]'";
	$rslt = db_exec($sql) or errDie("Unable to add supplier to contact list. (RD)", SELF);
	if ( pg_numrows($rslt) < 1 )
		return "Unable to add supplier to contact list. (RD2)";

	$data = pg_fetch_array($rslt);

	extract($data);

	// put it in the db
	db_connect();
	$sql = "INSERT INTO cons (name,surname,comp,ref,tell,cell,fax,email,hadd,padd,date,supp_id,con,by,div)
		VALUES ('$contname','$supname','','Supplier','$tel','','$fax','$email','$supaddr','',CURRENT_DATE,
			'$supid', 'No', '".USER_NAME."','".USER_DIV."')";
	$rslt = db_exec($sql) or errDie ("Unable to add supplier to contact list.", SELF);

	if ( pg_cmdtuples($rslt) < 1 ) {
		return "<li class=err>Unable to add supplier to contact list.</li>";
	}
}
?>

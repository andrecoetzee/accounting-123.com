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
	$OUTPUT = AddContact($_GET);
	$OUTPUT .= printCust($_GET);
} else {
	# show current stock
	$OUTPUT = printCust($_GET);
}

require ("template.php");

# show stock
function printCust ($_GET)
{

	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}

	if(isset($filter) && !isset($all)){
		$sqlfilter = " AND lower($filter) LIKE lower('%$fval%')";
		$show=true;
	}else{
		$filter = "";
		$fval = "";
		$sqlfilter = "";
		$show=false;
	}
	
	if(isset($all)) {
		$show=true;
	}

	$filterarr = array("surname" => "Company/Name", "init" => "Initials", "accno" => "Account Number", "deptname" => "Department");
	$filtersel = extlib_cpsel("filter", $filterarr, $filter);

	# Set up table to display in
	$printCust = "
	<h3>Find Customer</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=get>
	<tr><th>.: Filter :.</th><th>.: Value :.</th></tr>
	<tr class='bg-odd'><td>$filtersel</td><td><input type=text size=20 name=fval value='$fval'></td></tr>
	<tr class='bg-even'><td align=center><input type=submit name=all value='View All'></td><td align=center><input type=submit value='Apply Filter'></td></tr>
	</form>
	</table>
	<p>";
	
	
	if($show) {
	
		
		$printCust .= "<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Acc no.</th><th>Company/Name</th><th>Type</th><th>Curr</th><th>Tel</th><th>Category</th><th>Class</th><th colspan=2>Balance</th><th>Overdue</th><th colspan=8>Options</th></tr>";
	
		# connect to database
		db_connect();
	
		# Query server
		$tot = 0;
		$totoverd = 0;
		$i = 0;
		$sql = "SELECT * FROM customers WHERE (div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') $sqlfilter ORDER BY surname ASC";
		$custRslt = db_exec ($sql) or errDie ("Unable to retrieve Customers from database.");
		if (pg_numrows ($custRslt) < 1) {
			$printCust .= "<tr class='bg-odd'><td colspan=20><li>There are no Customers in Cubit.</td></tr>";
		}else{
			while ($cust = pg_fetch_array ($custRslt)) {
				
				# Check type of age analisys
				if(div_isset("DEBT_AGE", "mon")){
					$overd = ageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
				}else{
					$overd = age($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
				}
				$totoverd += $overd;
				
				# Check if record can be removed
				db_connect();
				$sql = "SELECT * FROM cashbook WHERE banked = 'no' AND cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
				$rs = db_exec($sql) or errDie("Unable to get cashbook entries.",SELF);
				if(pg_numrows($rs) < 1 && $cust['balance'] == 0){
					$rm = "<td><a href='cust-rem.php?cusnum=$cust[cusnum]'>Remove</a></td>";
				}else{
					$rm = "<td></td>";
				}
	
				if(strlen(trim($cust['bustel']))<1) {
					$cust['bustel']=$cust['tel'];
				}
	
				$cust['balance'] = sprint($cust['balance']);
				$tot=$tot+$cust['balance'];
	
				$inv = "";
				$inv = "<td><a href='pdf/invoice-pdf-cust.php?cusnum=$cust[cusnum]' target=_blank>Print Invoices</a></td>";
	
				# Locations drop down
				$locs = array("loc"=>"Local", "int"=>"International", "" => "");
				$loc = $locs[$cust['location']];
	
				$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
	
				$fbal = "$sp4--$sp4";
				$ocurr = CUR;
				$trans = "<td><a href='core/cust-trans.php?cusnum=$cust[cusnum]'>Transaction</a></td>";
				if($cust['location'] == 'int'){
					$fbal = "$sp4 $cust[currency] $cust[fbalance]";
					$ocurr = $cust['currency'];
					$trans = "<td><a href='core/intcust-trans.php?cusnum=$cust[cusnum]'>Transaction</a></td>";
				}
	
				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$printCust .= "<tr bgcolor='$bgColor'><td>$cust[accno]</td><td>$cust[surname]</td><td align=center>$loc</td><td align=center>$cust[currency]</td><td>$cust[bustel]</td><td>$cust[catname]</td><td>$cust[classname]</td><td align=right>".CUR." $cust[balance]</td><td align=right>$fbal</td><td align=right>$ocurr $overd</td><td><a href='cust-det.php?cusnum=$cust[cusnum]'>Details</a></td>";
				$printCust .= "<td><a href='cust-edit.php?cusnum=$cust[cusnum]'>Edit</a></td><td><a href='#' onclick='openPrintWin(\"cust-stmnt.php?cusnum=$cust[cusnum]\")'>Statement</a></td>$trans $inv";
				if($cust['blocked'] == 'yes'){
					$printCust .= "<td><a href='cust-unblock.php?cusnum=$cust[cusnum]'>Unblock</a></td>";
				}else{
					$printCust .= "<td><a href='cust-block.php?cusnum=$cust[cusnum]'>Block</a></td>";
				}
				$printCust .= "$rm <td><a href='conper-add.php?type=cust&id=$cust[cusnum]'>Add Contact</a></td></tr>";
	
				$i++;
			}
			if ($i > 1){$s = "s";} else {$s = "";}
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$tot = sprint($tot);
			$totoverd = sprint($totoverd);
			$printCust .= "<tr bgcolor='$bgColor'><td colspan=7>Total Amount Outstanding, from $i client$s </td><td align=right>".CUR." $tot</td><td></td><td align=right>".CUR." $totoverd</td></tr>";
		}
		$printCust .= "</table>";
	}

	$printCust .= "
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='customers-new.php'>Add Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printCust;
}

// adds the customer to the contact list
function AddContact() {
	global $_GET;

	$v = & new Validate();
	if ( ! $v->isOk($_GET["addcontact"], "num", 1, 9, "") )
		return "Invalid Customer Number";

	// check if supplier can be added to contact list
	$rslt = db_exec("SELECT * FROM cons WHERE cust_id='$_GET[addcontact]'");
	if ( pg_numrows($rslt) >= 1 ) {
		return "Customer Already Added as a Contact<br>";
	}

	// get it from the db
	$sql = "SELECT * FROM customers WHERE cusnum='$_GET[addcontact]'";
	$rslt = db_exec($sql) or errDie("Unable to add customer to contact list. (RD)", SELF);
	if ( pg_numrows($rslt) < 1 )
		return "Unable to add customer to contact list. (RD2)";

        $data = pg_fetch_array($rslt);

	extract($data);

	if ( isset($_GET["addcontact_as"]) && $_GET["addcontact_as"] == "Company" ) {
		$company = "$surname";
		$surname = "";
	} else {
		$company = "";
	}

	// put it in the db
	$sql = "INSERT INTO cons (name,surname,comp,ref,tell,cell,fax,email,hadd,padd,date,cust_id,con,by,div)
		VALUES ('$cusname','$surname','$company','Customer','$bustel','$cellno','$fax','$email','$addr1',
			'$paddr1',CURRENT_DATE,'$cusnum','No','".USER_NAME."','".USER_DIV."')";

	$rslt = db_exec($sql) or errDie("Unable to add customer to contact list", SELF);

	if (pg_cmdtuples($rslt) < 1) {
		return "<li class=err>Unable to add customer to contact list.";
	}
}

function age($cusnum, $days, $loc){
	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate < '".extlib_ago($days)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND odate < '".extlib_ago($days)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum'] );
}

function ageage($cusnum, $age, $loc){
	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age > '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND age > '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);
}
?>

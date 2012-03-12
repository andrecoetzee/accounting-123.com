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
require ("settings.php");
# decide what to do

if (isset($_GET['cusnum'])){
	$OUTPUT = view ($_GET['cusnum']);
} else {
	$OUTPUT = "<li> - Invalid use of module";
}

# display output
require ("template.php");




function view($cusnum)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li> Invalid Customer ID.";
	}else{
		$cust = pg_fetch_array($custRslt);
		# get vars
		foreach ($cust as $key => $value) {
			$$key = $value;
		}
	}

	
	$Sl = "SELECT DISTINCT invoices.invid,invoices.invnum,odate FROM invoices,inv_items WHERE invoices.invid=inv_items.invid AND invoices.cusnum='$cusnum'AND invnum!=0 AND del<qty ";//AND del!=0
	$Ri = db_exec($Sl);
	
	$invs = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Invoice</th>
				<th>Date</th>
				<th>Options</th>
			</tr>";
	
	$i = 0;
	
	while($id = pg_fetch_array($Ri)) {
		$invs .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$id[invnum]</td>
				<td>$id[odate]</td>
				<td><a target='_blank' href='delnote-out.php?invid=$id[invid]'>Outstanding</a></td>
			</tr>";
		
		$i++;
	}
	
	
	
	for($prd = 1;$prd < 13;$prd++) {
		db_conn($prd);
		
		$Sl = "SELECT DISTINCT invoices.invid,invoices.invnum,odate FROM invoices,inv_items WHERE invoices.invid=inv_items.invid AND invoices.cusnum='$cusnum'AND invnum!=0 AND inv_items.del<qty ";//AND inv_items.del!=0
		$Ri = db_exec($Sl);
		
		while($id = pg_fetch_array($Ri)) {
			$invs .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$id[invnum]</td>
					<td>$id[odate]</td>
					<td><a target='_blank' href='delnote-out.php?invid=$id[invid]&prd=$prd'>Outstanding</a></td>
				</tr>";
			$i++;
		}
	}
	
	$invs .= "</table>";

	// layout
	$view = "
		<h3>Outstanding Stock</h3>
		<table cellpadding=0 cellspacing=0>
			<tr>
				<th colspan='2'>Customer Details</th>
			</tr>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>Acc No</td>
							<td>$accno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Surname/Company</td>
							<td>$surname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Title</td>
							<td>$title</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Initials</td>
							<td>$init</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Contact Name</td>
							<td>$contname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Business Tel.</td>
							<td>$bustel</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>Home Tel.</td>
							<td>$tel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cell No.</td>
							<td>$cellno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Fax No.</td>
							<td>$fax</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<p>
		$invs
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-view.php'>View Customers</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}



?>
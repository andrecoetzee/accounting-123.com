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
require("settings.php");

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "rem":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['stkid'])){
					$OUTPUT = confirm ($_GET['stkid']);
			} else {
					$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
        if (isset($_GET['stkid'])){
                $OUTPUT = confirm ($_GET['stkid']);
        } else {
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# get template
require("template.php");

# confirm
function confirm($stkid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
			return $confirm;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
        	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}

		# get all done allocated invoices
		db_connect();
		$sql = "SELECT invid,cusnum FROM invoices WHERE printed = 'n' AND done = 'y' AND div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);

		$alloc = "";
		$i = 0;
		while($inv = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM inv_items WHERE stkid = '$stkid' AND invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			$all = pg_fetch_array($allRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$cust['cusname'] = "<li class=err> Not found";
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<li class=err> Not found";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$alloc .= "
				<tr bgcolor='".TMPL_tblDataColor1."'>
					<td>$dept[deptname]</td>
					<td>$cust[cusname] $cust[surname]</td>
					<td>$inv[invid]</td>
					<td>$all[sum] x $stk[suom]</td>
				</tr>";
				$i++;
			}
		}
		if($i < 1){
			$alloc = "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4>No Invoices Allocated</td></tr>";
		}

		# get all undone allocated invoices
		db_connect();
		$sql = "SELECT invid,cusnum FROM invoices WHERE printed = 'n' AND done = 'n' AND div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);

		$nalloc = "";
		$i = 0;
		while($inv = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM inv_items WHERE stkid = '$stkid' AND invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			$all = pg_fetch_array($allRslt);
			
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$cust['cusname'] = "<li class=err> Not found";
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<li class=err> Not found";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$nalloc .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$dept[deptname]</td><td>$cust[cusname] $cust[surname]</td><td>$inv[invid]</td><td>$all[sum] x $stk[suom]</td></tr>";
				$i++;
			}
		}
		if($i < 1){
			$nalloc = "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4>No Incomplete Invoices Allocated</td></tr>";
		}
		
		# get all pos invoices
		db_connect();
		$sql = "SELECT invid,cusnum,deptid,cusname FROM pinvoices WHERE div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);

		$palloc = "";
		$i = 0;
		while($inv = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM pinv_items WHERE stkid = '$stkid' AND invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			$all = pg_fetch_array($allRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$cust['cusname'] = $inv['cusname'];
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<li class=err> Not found";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$palloc .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$dept[deptname]</td><td>$cust[cusname] $cust[surname]</td><td>$inv[invid]</td><td>$all[sum] x $stk[suom]</td></tr>";
				$i++;
			}
		}

		# Get all allocated sales orders
		db_connect();
		$sql = "SELECT sordid,cusnum FROM sorders WHERE accepted != 'y' AND div = '".USER_DIV."'";
		$sordRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);

		$sordalloc = "";
		$q = 0;
		while($sord = pg_fetch_array($sordRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM sorders_items WHERE stkid = '$stkid' AND sordid = '$sord[sordid]' AND div = '".USER_DIV."'";
			$qallRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			$all = pg_fetch_array($qallRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$sord[cusnum]'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$cust['cusname'] = "<li class=err> Not found";
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<li class=err> Not found";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$sordalloc .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$dept[deptname]</td><td>$cust[cusname] $cust[surname]</td><td>$sord[sordid]</td><td>$all[sum] x $stk[suom]</td></tr>";
				$q++;
			}
		}
		if($q < 1){
			$sordalloc = "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4>No Sales Orders Allocated</td></tr>";
		}

		# Get all allocated consignment orders
		db_connect();
		$sql = "SELECT sordid,cusnum FROM corders WHERE div = '".USER_DIV."'";
		$cordRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);

		$cordalloc = "";
		$q = 0;
		while($cord = pg_fetch_array($cordRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM corders_items WHERE stkid = '$stkid' AND sordid = '$cord[sordid]' AND div = '".USER_DIV."'";
			$qallRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			$all = pg_fetch_array($qallRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$cord[cusnum]'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					$cust['cusname'] = "<li class=err> Not found";
					$cust['surname'] = "";
				}else{
					$cust = pg_fetch_array($custRslt);
				}
				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					$dept['deptname'] = "<li class=err> Not found";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$cordalloc .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$dept[deptname]</td><td>$cust[cusname] $cust[surname]</td><td>$cord[sordid]</td><td>$all[sum] x $stk[suom]</td></tr>";
				$q++;
			}
		}
		if($q < 1){
			$cordalloc = "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4>No Consignment Orders Allocated</td></tr>";
		}

		// Layout
		$confirm =
		"<center>
		<h3>Stock Allocation</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><td colspan=4><h4>Unprinted Invoices</h4></td></tr>
			<tr>
				<th>Department</th>
				<th>Customer</th>
				<th>Invoice No.</th>
				<th>Quantity Allocated</th>
			</tr>
			$alloc
			".TBL_BR."
			<tr>
				<td colspan='4'><h4>Incomplete Invoices</h4></td>
			</tr>
			<tr>
				<th>Department</th>
				<th>Customer</th>
				<th>Invoice No.</th>
				<th>Quantity Allocated</th>
			</tr>
			$nalloc
			".TBL_BR."
			<tr>
				<td colspan='4'><h4>Incomplete POS Invoices</h4></td>
			</tr>
			<tr>
				<th>Department</th>
				<th>Customer</th>
				<th>Invoice No.</th>
				<th>Quantity Allocated</th>
			</tr>
			$palloc
			".TBL_BR."
			<tr><td colspan=4><h4>Sales Orders</h4></td></tr>
			<tr><th>Department</th><th>Customer</th><th>Sales Order No.</th><th>Quantity Allocated</th></tr>
			$sordalloc
			".TBL_BR."
			<tr><td colspan=4><h4>Consignment Orders</h4></td></tr>
			<tr><th>Department</th><th>Customer</th><th>Sales Order No.</th><th>Quantity Allocated</th></tr>
			$cordalloc
		</table>
		<p>
		<input type=button value='[X] Close' onClick='javascript:window.close();'>";

		return $confirm;
}
?>

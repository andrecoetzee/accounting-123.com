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

# Get settings
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
					$OUTPUT = "<li class='err'> - Invalid use of module</li>";
			}
	}
} else {
	if (isset($_GET['stkid'])){
		$OUTPUT = confirm ($_GET['stkid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module</li>";
	}
}

# Get template
require("template.php");




# Confirm
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
				$confirm .= "<li class='err'>-".$e["msg"]."</li>";
			}
			return $confirm;
		}



		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}

		# get stock vars
		extract ($stk);

		if($ordered > 0){
			# get all done allocated invoices
			db_connect();
			$sql = "SELECT purid FROM purchases WHERE received = 'n' AND subtot > 0 AND div = '".USER_DIV."'";
			$purRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

			if(pg_numrows($purRslt) > 0){
				$deliveries = "
					<tr>
						<td colspan='2' align='center'><h3>Expected Deliveries</h3></td>
					</tr>
					<tr>
						<th>Date</th>
						<th>Number</th>
					</tr>";

				$i = 0;
				while($pur = pg_fetch_array($purRslt)){
					# get all items that are outstanding
					$sql = "SELECT ddate, qty FROM pur_items WHERE stkid = '$stkid' AND purid = '$pur[purid]' AND qty > 0 AND div = '".USER_DIV."'";
					$itRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

					while($it = pg_fetch_array($itRslt)){
						# delivery date
						$ddate = explode("-", $it['ddate']);
						$ddate = $ddate[2]."-".$ddate[1]."-".$ddate[0];

						$deliveries .= "
									<tr class='".bg_class()."'>
										<td>$ddate</td>
										<td>".sprint3($it['qty'])." x $suom</td>
									</tr>";
					}
				}
			}else{
				$deliveries = "";
			}



			# get all done allocated invoices
			db_connect();
			$sql = "SELECT purid FROM purch_int WHERE received = 'n' AND subtot > 0 AND div = '".USER_DIV."'";
			$ipurRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

			if(pg_numrows($ipurRslt) > 0){
				$intdeliveries = "
								<tr>
									<td colspan='2' align='center'><h3>Expected International Deliveries</h3></td>
								</tr>
								<tr>
									<th>Date</th>
									<th>Number</th>
								</tr>";

				$i = 0;
				while($pur = pg_fetch_array($ipurRslt)){
					# get all items that are outstanding
					$sql = "SELECT ddate, qty FROM purint_items WHERE stkid = '$stkid' AND purid = '$pur[purid]' AND qty > 0 AND div = '".USER_DIV."'";
					$itRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

					while($it = pg_fetch_array($itRslt)){
						# delivery date
						$ddate = explode("-", $it['ddate']);
						$ddate = $ddate[2]."-".$ddate[1]."-".$ddate[0];

						$intdeliveries .= "
										<tr class='".bg_class()."'>
											<td>$ddate</td>
											<td>".sprint3($it['qty'])." x $suom</td>
										</tr>";
					}
				}
			}else{
				$intdeliveries = "";
			}
		}else{
			$intdeliveries = "";
			$deliveries = "";
		}

		if ($units >= 0 && $alloc >= 0) {
			$avstk = ($units - $alloc);
		} else {
			$avstk = ($units + $alloc);
		}

		// Layout
		$confirm = "
					<center>
					<h3>Stock Details</h3>
					<table ".TMPL_tblDflts." width='350'>
						<tr>
							<th width='40%'>Field</th>
							<th width='60%'>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Category</td>
							<td>$catname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Stock code</td>
							<td>$stkcod</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Stock description</td>
							<td>".nl2br($stkdes)."</pre></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>On Hand</td>
							<td>".sprint3($units)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Allocated</td>
							<td>".sprint3($alloc)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Available</td>
							<td>".sprint3($avstk)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>On Order</td>
							<td>".sprint3($ordered)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Location</td>
							<td>Shelf : $shelf - Row : $row</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Minimum level</td>
							<td>$minlvl</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Maximum level</td>
							<td>$maxlvl</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling price per selling unit</td>
							<td>".CUR." ".sprint($selamt)."</td>
						</tr>
						$deliveries
						<tr><td><br><br></td><tr>
						$intdeliveries
					</table>";

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
        	$stkRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}




		# get all done allocated invoices
		db_connect();
		$sql = "SELECT invid,cusnum FROM invoices WHERE printed = 'n' AND done = 'y' AND div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$alloc = "";
		$i = 0;
		while($inv = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM inv_items WHERE stkid = '$stkid' AND invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
			$all = pg_fetch_array($allRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					return details($_POST);
				}
				$cust = pg_fetch_array($custRslt);

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					return details($_POST);
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$alloc .= "
							<tr class='".bg_class()."'>
								<td>$dept[deptname]</td>
								<td>$cust[cusname] $cust[surname]</td>
								<td>$inv[invid]</td>
								<td>$all[sum] x $stk[suom]</td>
							</tr>";
				$i++;
			}
		}
		if($i < 1){
			$alloc = "
						<tr class='".bg_class()."'>
							<td colspan='4'>No Invoices Allocated</td>
						</tr>";
		}



		# get all undone allocated invoices
		db_connect();
		$sql = "SELECT invid,cusnum FROM invoices WHERE printed = 'n' AND done != 'y' AND div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$nalloc = "";
		$i = 0;
		while($inv = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM inv_items WHERE stkid = '$stkid' AND invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
			$all = pg_fetch_array($allRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					return details($_POST);
				}
				$cust = pg_fetch_array($custRslt);

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					return details($_POST);
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$nalloc .= "
							<tr class='".bg_class()."'>
								<td>$dept[deptname]</td>
								<td>$cust[cusname] $cust[surname]</td>
								<td>$inv[invid]</td>
								<td>$all[sum] x $stk[suom]</td>
							</tr>";
				$i++;
			}
		}
		if($i < 1){
			$nalloc = "
						<tr class='".bg_class()."'>
							<td colspan='4'>No Incomplete Invoices Allocated</td>
						</tr>";
		}



		# get all undone allocated invoices
		db_connect();
		$sql = "SELECT * FROM pinvoices WHERE printed = 'n' AND done != 'y' AND div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$pall = "";
		$i = 0;
		while($inv = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM pinv_items WHERE stkid = '$stkid' AND invid = '$inv[invid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
			$all = pg_fetch_array($allRslt);
			if($all['sum'] > 0){
				# Get selected customer info

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					return details($_POST);
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$pall .= "
							<tr class='".bg_class()."'>
								<td>$dept[deptname]</td>
								<td>$inv[cusname]</td>
								<td>$inv[invid]</td>
								<td>$all[sum] x $stk[suom]</td>
							</tr>";
				$i++;
			}
		}
		if($i < 1){
			$pall = "
					<tr class='".bg_class()."'>
						<td colspan='4'>No unprinted POS Invoices</td>
					</tr>";
		}



		# get all allocated quotes
		db_connect();
		$sql = "SELECT sordid,cusnum FROM sorders WHERE accepted = 'n' AND div = '".USER_DIV."'";
		$sordRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$sordalloc = "";
		$q = 0;
		while($sord = pg_fetch_array($sordRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM sorders_items WHERE stkid = '$stkid' AND sordid = '$sord[sordid]' AND div = '".USER_DIV."'";
			$qallRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
			$all = pg_fetch_array($qallRslt);
			if($all['sum'] > 0){
				# Get selected customer info
				db_connect();
				$sql = "SELECT * FROM customers WHERE cusnum = '$sord[cusnum]' AND div = '".USER_DIV."'";
				$custRslt = db_exec ($sql) or errDie ("Unable to get customer information");
				if (pg_numrows ($custRslt) < 1) {
					return details($_POST);
				}
				$cust = pg_fetch_array($custRslt);

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
					return details($_POST);
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$sordalloc .= "
								<tr class='".bg_class()."'>
									<td>$dept[deptname]</td>
									<td>$cust[cusname] $cust[surname]</td>
									<td>$sord[sordid]</td>
									<td>$all[sum] x $stk[suom]</td>
								</tr>";
				$q++;
			}
		}
		if($q < 1){
			$sordalloc = "
						<tr class='".bg_class()."'>
							<td colspan='4'>No Sales Orders Allocated</td>
						</tr>";
		}



		# get all incomplete consignment orders
		db_connect();
		$sql = "SELECT * FROM corders WHERE div = '".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$call = "";
		$c = 0;
		while($cord = pg_fetch_array($invRslt)){
			db_connect();
			$sql = "SELECT sum(qty) FROM corders_items WHERE stkid = '$stkid' AND sordid = '$cord[sordid]' AND div = '".USER_DIV."'";
			$allRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
			$all = pg_fetch_array($allRslt);
			if($all['sum'] > 0){

				# get department
				db_conn("exten");
				$sql = "SELECT * FROM departments WHERE deptid = '$cord[deptid]' AND div = '".USER_DIV."'";
				$deptRslt = db_exec($sql);
				if(pg_numrows($deptRslt) < 1){
				//	return details($_POST);
					return "<li class='err'>Unable to get department information. (Consignment Orders)</li>";
				}else{
					$dept = pg_fetch_array($deptRslt);
				}
				$call .= "
							<tr class='".bg_class()."'>
								<td>$dept[deptname]</td>
								<td>$cord[surname]</td>
								<td>$cord[sordid]</td>
								<td>$all[sum] x $stk[suom]</td>
							</tr>";
				$c++;
			}
		}
		if($c < 1){
			$call = "
					<tr class='".bg_class()."'>
						<td colspan='4'>No Consignment Orders</td>
					</tr>";
		}

		// Layout
		$confirm .= "
					<center>
					<h3>Stock Allocation</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<td colspan='4'><h4>Unprinted Invoices</h4></td>
						</tr>
						<tr>
							<th>Department</th>
							<th>Customer</th>
							<th>Invoice No.</th>
							<th>Quantity Allocated</th>
						</tr>
						$alloc
						<tr><td><br></td></tr>
						<tr>
							<td colspan='4'><h4>Unprinted POS Invoices</h4></td>
						</tr>
						<tr>
							<th>Department</th>
							<th>Customer</th>
							<th>Invoice No.</th>
							<th>Quantity Allocated</th>
						</tr>
						$pall
						<tr><td><br></td></tr>
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
						<tr><td><br></td></tr>
						<tr>
							<td colspan='4'><h4>Sales Orders</h4></td>
						</tr>
						<tr>
							<th>Department</th>
							<th>Customer</th>
							<th>Sales Order No.</th>
							<th>Quantity Allocated</th>
						</tr>
						$sordalloc
						<tr><td><br></td></tr>
						<tr>
							<td colspan='4'><h4>Consignment Orders</h4></td>
						</tr>
						<tr>
							<th>Department</th>
							<th>Customer</th>
							<th>Consignment Order No.</th>
							<th>Quantity Allocated</th>
						</tr>
						$call
					</table>
					<p>
					<input type='button' value='[X] Close' onClick='javascript:window.close();'>";
		return $confirm;

}

?>

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
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$_GET["done"] = '';
	$_GET["client"] = '';
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "details":
				$OUTPUT = details($_POST);
				break;
			case "update":
				$OUTPUT = write($_POST);
				break;
            default:
				$OUTPUT = details($_POST);
			}
	} else {
		$OUTPUT = details($_POST);
	}
}

# get templete
require("template.php");



# select department
function view()
{

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT deptid,deptname FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class=err>There are no Departments found in Cubit.";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}


	// Layout
	$view = "
				<br><br>
				<form action='".SELF."' method='POST' name='form'>
				<table ".TMPL_tblDflts." width='400'>
					<input type='hidden' name='key' value='details'>
					<tr>
						<th colspan='2'>New Point of Sale Invoice(Cash)</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'><
						td>Select Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td></td>
						<td valign='center'><input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='pos-invoice-list.php'>View Point of Sale Invoices</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='customers-new.php'>New Customer</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
	return $view;

}




# Default view
function view_err($_POST, $err = "")
{

	# get vars
	extract ($_POST);

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
				<br><br>
				<form action='".SELF."' method='POST' name='form'>
				<table ".TMPL_tblDflts." width='400'>
					<input type='hidden' name='key' value='details'>
					<tr>
						<th colspan='2'>New Invoice</th>
					</tr>
					<tr>
						<td colspan='2'>$err</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Select Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td></td>
						<td valign='center'><input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='invoice-view.php'>View Invoices</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='customers-new.php'>New Customer</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
	return $view;

}




# create a dummy invoice
function create_dummy($deptid)
{

	db_connect();
	# Dummy Vars
	$cusnum = 0;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;

	// $invid = divlastid('pinv', USER_DIV);

	# insert invoice to DB
	$sql = "INSERT INTO pinvoices(deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, username, printed, done, prd, div)";
	$sql .= " VALUES('$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', '$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."', 'n', 'n', '".PRD_DB."', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	$invid = lastinvid();
	return $invid;

}




# Details
function details($_POST, $error="")
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($invid)){
		$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	}else{
		$client="";
		$deptid=2;
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
	}

	$fil="";
	if(!isset($fcode)) {
		$fcode="";
	}

	if(!isset($fdes)) {
		$fdes="";
	}

	if($fcode!="") {
		$fil.="AND lower(stkcod) LIKE lower('%$fcode%')";
	}

	if($fdes!="") {
		$fil.="AND lower(stkdes) LIKE lower('%$fdes%')";
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	if(!isset($invid)){
		$invid = create_dummy($deptid);
		$stkerr = "0,0";
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class='err'>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	if(!isset($pass)) {
		$pass="";
	} else {

		$pass=remval($pass);

		db_conn('cubit');
		$Sl="SELECT * FROM users WHERE password=md5('$pass') AND abo=1000";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)>0) {
			$ped=true;
		} else {
			$ped=false;
		}
	}

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}


	# Get selected Customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' AND location != 'int' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		db_connect();
		# Query server for customer info   AND lower(surname) LIKE lower('$letters%')
		$sql = "SELECT cusnum,cusname,surname FROM customers WHERE location != 'int' AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($custRslt) < 1) {
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			                        $customers .= "<option value='0' selected>Select Customer</option>";
						                        while($cust = pg_fetch_array($custRslt)){
									                                $customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
													                        }
																                        $customers .= "</select>";
		}else{
			$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
			$customers .= "<option value='0' selected>Select Customer</option>";
			while($cust = pg_fetch_array($custRslt)){
				$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
			}
			$customers .= "</select>";
		}
		# Take care of the unset vars
		$cust['addr1'] = "";
		$cust['cusnum'] = "";
		$cust['vatnum'] = "";
		$cust['accno'] = "";
	}else{
		$cust = pg_fetch_array($custRslt);

		$sql = "SELECT cusnum, cusname, surname FROM customers WHERE deptid = '$inv[deptid]' AND location != 'int' AND blocked != 'yes' AND div = '".USER_DIV."' ORDER BY surname";
		$cusRslt = db_exec ($sql) or errDie ("Unable to view customers");
		# Moarn if customer account has been blocked   AND lower(surname) LIKE lower('$letters%')
		if($cust['blocked'] == 'yes'){
			$error .= "<li class=err>Error : Selected customer account has been blocked.";
		}

		// $customers = "<input type=hidden name=cusnum value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
		$cusnum = $cust['cusnum'];

		$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
		$customers .= "<option value='0' selected>Select Customer</option>";
			// $customers .= "<option value='-S' selected>Select Customer</option>";
			while($cus = pg_fetch_array($cusRslt)){
				$sel = "";
				if($cust['cusnum'] == $cus['cusnum']){
					$sel = "selected";
				}
				$customers .= "<option value='$cus[cusnum]' $sel>$cus[cusname] $cus[surname]</option>";
			}
		$customers .= "</select>";
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class=err> There are no Stores found in Cubit.";
	}else{
		$whs .= "<option value='-S' disabled selected>Select Store</option>";
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .="</select>";

	# get sales people
	db_conn("exten");
	$sql = "SELECT * FROM salespeople WHERE div = '".USER_DIV."' ORDER BY salesp ASC";
	$salespRslt = db_exec ($sql) or errDie ("Unable to get sales people.");
	if (pg_numrows ($salespRslt) < 1) {
		return "<li class=err> There are no Sales People found in Cubit.";
	}else{
		$salesps = "<select name='salespn'>";
		while($salesp = pg_fetch_array($salespRslt)){
			if($salesp['salesp'] == $inv['salespn']){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$salesps .= "<option value='$salesp[salesp]' $sel>$salesp[salesp]</option>";
		}
		$salesps .= "</select>";
	}


	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$chin = "checked=yes";
		$chex = "";
		$chno = "";
	}elseif($inv['chrgvat'] == "exc"){
		$chin = "";
		$chex = "checked=yes";
		$chno = "";
	}else{
		$chin = "";
		$chex = "";
		$chno = "checked=yes";
	}

	# format date
	list($oyear, $omon, $oday) = explode("-", $inv['odate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>STORE</th>
							<th>ITEM NUMBER</th>
							<th>VAT CODE</th>
							<th>SERIAL NO.</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>AMOUNT</th>
							<th>Remove</th>
						<tr>";

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$stkd['account']+=0;
		if($stkd['account']!=0) {
			# Keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			db_conn('core');
			$Sl="SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
			$Ri=db_exec($Sl) or errDie("Unable to get accounts.");

			$Accounts = "
							<select name='accounts[]'>
								<option value='0'>Select Account</option>";

			while($ad=pg_fetch_array($Ri)) {
				if($ad['accid']==$stkd['account']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Accounts.="<option value='$ad[accid]' $sel>$ad[accname]</option>";
			}

			$Accounts.="</select>";

			$sernos = "
				<input type='hidden' name='sernos_ss[]' value='$stkd[ss]'>
				<input type='hidden' name='sernos[]' value='$stkd[serno]'>";

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";

			$viewcost = "<input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'>";

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
							<select name='vatcodes[]'>
								<option value='0'>Select</option>";

			while($vd=pg_fetch_array($Ri)) {
				if($stkd['vatcode']==$vd['id']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
			}

			$Vatcodes.="</select>";

			//print "fo";

			# Put in product
			$products .= "
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='2'>$Accounts<input type='hidden' name='whids[]' value='$stkd[whid]'></td>
								<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'>$Vatcodes</td>
								<td></td>
								<td><input type='text' size='20' name='descriptions[]' value='$stkd[description]'> $sernos</td>
								<td>$qtyin</td>
								<td>$viewcost</td>
								<input type='hidden' name='disc[]' value='$stkd[disc]'>
								<input type='hidden' name='discp[]' value='$stkd[discp]'>
								<td><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
								<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
							</tr>";
			$key++;
		}else{

			# keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			if($stk['units']<=$stk['minlvl'] &&$stk['minlvl']!=0) {
				$error .= "<li class=err>$stk[stkcod] is below minimum level, please notify stock controller.</li>";
			}

			# Serial number
			if($stk['serd'] == 'yes' && ($inv['serd'] == 'n' || $stkd["serno"] == "")){
				$sers = ext_getavserials($stkd['stkid']);
				$sernos = "<select class='width : 15' name='sernos[]'>";
				foreach($sers as $skey => $ser){
					$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
				}
				$sernos .= "</select>
							<input type='hidden' name='sernos_ss[]' value='*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*' />";
			}else{
				$sernos = "
					<input type='hidden' name='sernos_ss[$key]' value='$stkd[ss]' />
					<input type='hidden' name='sernos[]' value='$stkd[serno]'>$stkd[ss]";
			}

			# Input qty if not serialised
			$qtyin = "<input type='text' size='3' name='qtys[]' value='$stkd[qty]'>";
			if($stk['serd'] == 'yes'){
				$qtyin = "<input type='hidden' size='3' name='qtys[]' value='$stkd[qty]'>$stkd[qty]";
			}

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes ORDER BY code";
			$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "
							<select name='vatcodes[]'>
								<option value='0'>Select</option>";

			while($vd=pg_fetch_array($Ri)) {
				if($stkd['vatcode']==$vd['id']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
			}

			$Vatcodes.="</select>";

			if($ped) {
				$editp="<input type='text' size='8' name='unitcost[]' value='$stkd[unitcost]'>";
			} else {
				$editp="<input type='hidden' size='8' name='unitcost[]' value='$stkd[unitcost]'>$stkd[unitcost]";
			}

			# put in product
			$products .= "
							<input type='hidden' name='accounts[]' value='0'>
							<input type='hidden' name='descriptions[]' value=''>
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
								<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
								<td>$Vatcodes</td>
								<td>$sernos</td>
								<td>".extlib_rstr($stk['stkdes'], 30)."</td>
								<td>$qtyin</td>
								<td>$editp</td>
								<input type='hidden' size='4' name='disc[]' value='$stkd[disc]'>
								<input type='hidden' size='4' name='discp[]' value='$stkd[discp]' maxlength=5>
								<td><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
								<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
							</tr>";
			$key++;
		}
	}

	# Look above(remprod keys)
	$keyy = $key;

	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}else{
		$SCROLL = "yes";
	}

	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S"){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get selected stock in this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				if($stk['serd'] == 'yes'){
					$sers = ext_getavserials($stkidss[$key]);
					$sernos = "<select class='width : 15'name='sernos[]' onChange='javascript:document.form.submit();'>";
					foreach($sers as $skey => $ser){
						$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
					}
					$sernos .= "</select>
								<input type='hidden' name='sernos_ss[]' value='*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*' />";
				}else{
					$sernos = "
							<input type='hidden' name='sernos[]' value=''>
							<input type='hidden' name='sernos_ss[]' value=''>";
				}

				/* -- Start Some Checks -- */
				# check if they are selling too much
				if(($stk['units'] - $stk['alloc']) < $qtyss[$key]){
					if(!in_array($stk['stkid'], explode(",", $stkerr))){
						if($stk['type'] != 'lab'){
							$stkerr .= ",$stk[stkid]";
							$error .= "<li class='err'>Warning :  Item number <b>$stk[stkcod]</b> does not have enough items available.</li>";
						}
					}
				}
				/* -- End Some Checks -- */

				# Calculate the Discount discount
				if($discs[$key] < 1){
					if($discps[$key] > 0){
						$discs[$key] = round((($discps[$key]/100) * $stk['selamt']), 2);
					}
				}else{
					$discps[$key] = round((($discs[$key] * 100) / $stk['selamt']), 2);
				}

				# Calculate amount
				$amt[$key] = ($qtyss[$key] * ($stk['selamt'] - $discs[$key]));

				# Input qty if not serialised
				$qtyin = "<input type=text size=3 name=qtys[] value='$qtyss[$key]'>";
				if($stk['serd'] == 'yes'){
					$qtyin = "<input type=hidden size=3 name=qtys[] value='$qtyss[$key]'>$qtyss[$key]";
				}

				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes ORDER BY code";
				$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
								<select name='vatcodes[]'>
									<option value='0'>Select</option>";

				while($vd=pg_fetch_array($Ri)) {
					if($stk['vatcode']==$vd['id']) {
						$sel="selected";
					} else {
						$sel="";
					}
					$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
				}

				$Vatcodes.="</select>";

				$amt[$key] = sprint ($amt[$key]);
				$stk['selamt'] = sprint ($stk['selamt']);
				
				# put in selected warehouse and stock
				$products .= "
								<input type='hidden' name='accounts[]' value='0'>
								<input type='hidden' name='descriptions[]' value=''>
								<tr bgcolor='".bgcolorg()."'>
									<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
									<td><input type='hidden' name='stkids[]' value='$stk[stkid]'><a href='#bottom' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
									<td>$Vatcodes</td>
									<td>$sernos</td>
									<td>".extlib_rstr($stk['stkdes'], 30)."</td>
									<td>$qtyin</td>
									<td><input type='hidden' size='8' name='unitcost[]'  value='$stk[selamt]'>$stk[selamt]</td>
									<input type='hidden' size='4' name='disc[]' value='$discs[$key]'>
									<input type='hidden' size='4' name='discp[]' value='$discps[$key]' maxlength='5'>
									<td><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
									<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
								</tr>";
				$keyy++;
			}elseif(isset($accountss[$key]) && $accountss[$key] != "0" ){

				db_conn('core');
				$Sl="SELECT * FROM accounts WHERE accid='$accountss[$key]'";
				$Ri=db_exec($Sl) or errDie("Unable to get account data.");

				if(pg_num_rows($Ri)<1) {
					return "invalid.";
				}

				$ad=pg_fetch_array($Ri);

				# Calculate amount
				$amt[$key] =sprint($qtyss[$key] * ($unitcosts[$key]));

				# Input qty if not serialised
				//$qtyin = "<input type=text size=3 name=qtemp value='$qtyss[$key]'>";
				$qtyin = "<input type='text' size='3' name='qtys[]' value='$qtyss[$key]'>";

				# Check permissions
				$viewcost = "<input type='text' size='8' name='unitcost[]' value='$unitcosts[$key]'>";

				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes ORDER BY code";
				$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes = "
								<select name='vatcodes[]'>
									<option value='0'>Select</option>";

				while($vd=pg_fetch_array($Ri)) {
					if($vatcodess[$key]==$vd['id']) {
						$sel="selected";
					} else {
						$sel="";
					}
					$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
				}

				$Vatcodes.="</select>";

				$viewcost = sprint ($viewcost);
				$amt[$key] = sprint ($amt[$key]);

				# Put in selected warehouse and stock
				$products .= "
								<tr bgcolor='".bgcolorg()."'>
									<td colspan='2'>$ad[accname]<input type='hidden' name='accounts[]' value='$accountss[$key]'><input type='hidden' name='whids[]' value='0'></td>
									<td>$Vatcodes<input type='hidden' name='stkids[]' value='0'></td>
									<td></td>
									<td><input type='text' size='20' name='descriptions[]' value='$descriptionss[$key]'></td>
									<td>$qtyin</td>
									<td>$viewcost</td>
									<input type='hidden' name='disc[]' value='0'><input type='hidden' name='discp[]' value='0'>
									<td><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
									<td><input type='checkbox' name='remprod[]' value='$keyy'></td>
								</tr>";
				$keyy++;
			}else{
				if(!isset($diffwhBtn)){
					# skip if not selected
					if($whid == "-S"){
						continue;
					}

					if(!isset($addnon)) {

						# get warehouse name
						db_conn("exten");
						$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
						$whRslt = db_exec($sql);
						$wh = pg_fetch_array($whRslt);

						# get stock on this warehouse
						db_connect();
						$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' $fil ORDER BY stkcod ASC";
						$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
						if (pg_numrows ($stkRslt) < 1) {
							$error .= "<li class=err>There are no stock items in the selected warehouse.";
							continue;
						}
						$stks = "<select class='width : 15'name='stkidss[]' onChange='javascript:document.form.submit();'>";
						$stks .= "<option value='-S' disabled selected>Select Number</option>";
						$count = 0;
						while($stk = pg_fetch_array($stkRslt)){
							$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
						}
						$stks .= "</select> ";

						# put in drop down and warehouse
						$products .= "
										<input type='hidden' name='accountss[]' value='0'>
										<input type='hidden' name='descriptionss[]' value=''>
										<tr bgcolor='".bgcolorg()."'>
											<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
											<td>$stks</td>
											<td> </td>
											<td> </td>
											<td> </td>
											<td><input type='text' size='3' name='qtyss[]'  value='1'></td>
											<td> </td>
											<input type='hidden' size='4' name='discs[]' value='0'>
											<input type='hidden' size='4' name='discps[]' value='0' maxlength='5'>
											<td><input type='hidden' name='amts[]' value='0.00'>".CUR." 0.00</td>
											<td></td>
										</tr>";
					}else{

						db_conn('core');
						$Sl="SELECT accid,topacc,accnum,accname FROM accounts WHERE acctype='I' ORDER BY accname";
						$Ri=db_exec($Sl) or errDie("Unable to get accounts.");

						$Accounts = "
										<select name='accountss[]' onChange='javascript:document.form.submit();'>
											<option value='0'>Select Account</option>";

						while($ad=pg_fetch_array($Ri)) {
							$Accounts.="<option value=$ad[accid]>$ad[accname]</option>";
						}

						$Accounts.="</select>";

						db_conn('cubit');
						$Sl="SELECT * FROM vatcodes ORDER BY code";
						$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

						$Vatcodes="<select name=vatcodess[]>
						<option value='0'>Select</option>";

						while($vd=pg_fetch_array($Ri)) {
							if($vd['del']=="Yes") {
								$sel="selected";
							} else {
								$sel="";
							}
							$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
						}

						$Vatcodes.="</select>";


						$products .= "
										<tr bgcolor='".bgcolorg()."'>
											<td colspan='2'>$Accounts<input type='hidden' name='whidss[]' value='1'></td>
											<inpu type='hidden' name='stkidss[]' value=''>
											<td>$Vatcodes</td>
											<td></td>
											<td><input type='text' size='20' name='descriptionss[]'></td>
											<td><input type='text' size='3' name='qtyss[]' value='1'></td>
											<td><input type='text' name='unitcosts[]' size='7'></td>
											<td></td>
											<td>".CUR." 0.00</td>
											<input type='hidden' name='discs[]' value='0'>
											<input type='hidden' name='discps[]' value='0' >
										</tr>";
					}
				}
			}
		}
	}else{
		if(!isset($diffwhBtn)){
			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
				if(isset($wtd)&&$wtd!=0){$whid=$wtd;}
    			# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND blocked = 'n' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!(isset($err))){$err="";}
					$err .= "<li>There are no stock items in the selected store.</li>";
				}
				$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
				$products .= "
								<tr bgcolor='".bgcolorg()."'>
									<input type='hidden' name='accountss[]' value='0'>
									<input type='hidden' name='descriptionss[]' value=''>
									<input type='hidden' name='vatcodess[]' value=''>
					 				<td>
					 					<input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
					 					<td>$stks</td>
					 					<td> </td>
					 					<td> </td>
					 					<td> </td>
					 					<td><input type='text' size='3' name='qtyss[]' value='1'></td>
					 					<td> </td>
					 					<input type='hidden' size='4' name='discs[]' value='0'>
					 					<input type='hidden' size='4' name='discps[]' value='0' maxlength='5'>
					 					<td>".CUR." 0.00</td>
					 					<td></td>
					 				</tr>";
			}else{
				$products .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$whs</td>
									<td></td>
									<td></td>
									<td> </td>
									<td> </td>
									<td> </td>
									<td> </td>
									<input type='hidden' size='4' name='discs[]' value='0'>
									<input type='hidden' size='4' name='discps[]' value='0' maxlength='5'>
									<td>".CUR." 0.00</td>
									<td></td>
								</tr>";
			}
		}
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		$products .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$whs</td>
							<td></td>
							<td> </td>
							<td> </td>
							<td> </td>
							<td> </td>
							<input type='hidden' size='4' name='discs[]' value='0'>
							<input type='hidden' size='4' name='discps[]' value='0' maxlength='5'>
							<td>".CUR." 0.00</td>
							<td></td>
						</tr>";
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = sprint(($inv['traddisc']/100) * ($SUBTOT + $inv['delchrg']));
	}else{
		$traddiscm = "0.00";
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	if(!(isset($done))) {
		$done="";
	}

/* --- End Some calculations --- */

	if($inv['terms']==1) {
		$tc1="";
		$tc2="checked";
	} else {
		$tc1="checked";
		$tc2="";
	}

	db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='PSALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sc="checked";
	} else {
		$sc="";
	}

	$sales="<td>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$salesps</td>
				<td>Print</td>
				<td><input type='checkbox' name='printsales' $sc></td>
			</tr>
		</table>
		</td>";

	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $inv['deptid']){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM users WHERE username='".USER_NAME."'";
	$Ri=db_exec($Sl);

	$data=pg_fetch_array($Ri);

	if($data['help']!="S") {
		$save="|<input type=submit name='saveBtn' value='Save'>";
	} else {
		$save="";
	}

	if($inv['rounding']>0) {
		$due=sprint($inv['total']-$inv['rounding']);
		$rd = "
				<tr bgcolor='".bgcolorg()."'>
					<td>Rounding</td>
					<td align='right'>R $inv[rounding]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<th>Amount Due</th>
					<td align='right'>R $due</td>
				</tr>";

	} else {
		$rd="";
	}

	$inv['delvat']+=0;

	if($inv['delvat']==0) {
		$Sl="SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		$inv['delvat']=$vd['id'];
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<input type=hidden name=delvat value=0>";

// 	while($vd=pg_fetch_array($Ri)) {
// 		if($vd['id']==$inv['delvat']) {
// 			$sel="selected";
// 		} else {
// 			$sel="";
// 		}
// 		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
// 	}
//
// 	$Vatcodes.="</select>";

	if($inv['cusnum']==0) {
		$cd = "
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer</td>
					<td valign='center'><input type='text' size='20' name='client' value='$client'></td>
				</tr>";
		$pc = "<input type='hidden' name='pcredit' value='0'>";
	} else {
		$cd = "
				<tr bgcolor='".bgcolorg()."'>
					<td valign='top'>Customer Address</td>
					<td valign='center'>".nl2br($cust['addr1'])."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Customer VAT Number</td>
					<td>$cust[vatnum]</td>
				</tr>";
		$pc = "
				<tr bgcolor='".bgcolorg()."'>
					<td>Amount On Credit</td>
					<td><input size='12' type='text' name='pcredit' value='$inv[pcredit]'></td>
				</tr>";
	}

	$sales=USER_NAME;

	if($inv['pcash']==0) {
		$inv['pcash']="";
	}

	if($inv['pcheque']==0) {
		$inv['pcheque']="";
	}

	if($inv['pcc']==0) {
		$inv['pcc']="";
	}



/* -- Final Layout -- */
	$details = "
					<center>
					<h3>Speed POS</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='invid' value='$invid'>
						<input type='hidden' name='stkerr' value='$stkerr'>
						<input type='hidden' name='user' value='".USER_NAME."'>
						<input type='hidden' name='salespn' value='".USER_ID."'>
					<table ".TMPL_tblDflts." width='95%'>
 						<tr>
 							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Department</td>
										<td valign='center'>$depts</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer</td>
										<td valign='center'>$customers</td>
									</tr>
									$cd
									<input type='hidden' size='10' name='cordno' value='$inv[cordno]'>
									<tr>
										<th colspan='2'>Point of Sale</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Barcode</td>
										<td><input type='text' size='13' name='bar' value=''></td>
									</tr>
									<tr>
										<th colspan='2'>Search</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>By Stock Code</td>
										<td><input type='text' size='13' name='fcode' value='$fcode'><input type='submit' name='upBtn' value='Search'></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>By Stock Description</td>
										<td><input type='text' size='13' name='fdes' value='$fdes'><input type='submit' name='upBtn' value='Search'></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Invoice Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Invoice No.</td>
										<td valign='center'>$inv[invid]</td>
									</tr>
									<input type='hidden' size='5' name='ordno' value='$inv[ordno]'>
									<tr bgcolor='".bgcolorg()."'>
										<td>Invoice Date</td>
										<td>
											<input type='hidden' size='2' name='oday' maxlength='2' value='$oday'>$oday-
											<input type='hidden' size='2' name='omon' maxlength='2' value='$omon'>$omon-
											<input type='hidden' size='4' name='oyear' maxlength='4' value='$oyear'>$oyear
										</td>
									</tr>
									<input type='hidden' size='7' name='chrgvat' value='inc'>
									<tr bgcolor='".bgcolorg()."'>
										<td>Sales Person</td>
										<td>$sales</td>
									</tr>
									<input type='hidden' size='5' name='traddisc' value='$inv[traddisc]'>
									<input type='hidden' size='7' name='delchrg' value='$inv[delchrg]'>$Vatcodes
									<tr>
										<th colspan='2'>Payment Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Amount Paid Cash</td>
										<td><input size='12' type='text' name='pcash' value='$inv[pcash]'></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Amount Paid Cheque</td>
										<td><input size='12' type='text' name='pcheque' value='$inv[pcheque]'></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Amount Paid Credit Card</td>
										<td><input size='12' type='text' name='pcc' value='$inv[pcc]'></td>
									</tr>
									$pc
									<tr bgcolor='".bgcolorg()."'>
										<td>POS Manager Password</td>
										<td>
											<input type='password' size='10' name='pass' value='$pass'>
											<input type='submit' value='Continue'>
										</td>
									</tr>
									<tr><td colspan=2>$done</td></tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2'>$products</td>
						</tr>
						<tr>
							<td width='70%' valign='top'>
								<table ".TMPL_tblDflts." width='100%'>
									<input type='hidden' name='comm' value='$inv[comm]'>
									<tr>
										<td>$error</td>
									</tr>
								</table>
							</td>
							<td align='right' valign='top' width='30%'>
								<table ".TMPL_tblDflts." width='100%'>
									<tr bgcolor='".bgcolorg()."'>
										<td>SUBTOTAL</td>
										<td align='right'>".CUR." <input type='hidden' name='SUBTOT' value='$SUBTOT'>$SUBTOT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><b>VAT @ $VATP%</b>
									</td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>".CUR." $TOTAL</td>
									</tr>
									$rd
								</table>
							</td>
						</tr>
						<tr>
							<td align='right'></td>
							<td><input type='submit' name='upBtn' value='Update'></td>
						</tr>
					</table>
					<a name='bottom'>
					</form>
					</center>";
	return $details;

}




# details
function write($_POST)
{

	#get vars
	extract ($_POST);

	$pass=remval($pass);

	$pcredit+=0;

	$pcash+=0;
	$pcheque+=0;
	$pcc+=0;

	$deptid+=0;

	db_conn('cubit');

	if(isset($printsales)) {

		$Sl="SELECT * FROM settings WHERE constant='PSALES'";
		$Ri=db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri)<1) {
			$Sl="INSERT INTO settings (constant,value,div) VALUES ('PSALES','Yes','".USER_DIV."')";
			$Ri=db_exec($Sl);
		} else {
			$Sl="UPDATE settings SET value='Yes' WHERE constant='PSALES' AND div='".USER_DIV."'";
			$Ri=db_exec($Sl);
		}
	} else {
		$Sl="UPDATE settings SET value='No' WHERE constant='PSALES' AND div='".USER_DIV."'";
		$Ri=db_exec($Sl);
	}

	//$it+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(isset($client)) {
		$v->isOk ($client, "string", 0, 20, "Invalid Customer.");
	} else {
		$client="";
	}
	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($ordno, "string", 0, 20, "Invalid sales order number.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($salespn, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($oday, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($omon, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($oyear, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $oyear."-".$omon."-".$oday;
	if(!checkdate($omon, $oday, $oyear)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	if($traddisc > 100){
		$v->isOk ($traddisc, "float", 0, 0, "Error : Trade Discount cannot be more than 100 %.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");
	$odate = $oyear."-".$omon."-".$oday;
	if(!checkdate($omon, $oday, $oyear)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}

	# used to generate errors
	$error = "asa@";

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# check is serai no was selected
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			# check if serial is selected
			if(ext_isSerial("stock", "stkid", $stkid) && !isset($sernos[$keys])){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}elseif(ext_isSerial("stock", "stkid", $stkid) && !(strlen($sernos[$keys]) > 0)){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$discp[$keys] += 0;
			$disc[$keys] += 0;

			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount for product number : <b>".($keys+1)."</b>.");
			if($disc[$keys] > $unitcost[$keys]){
				$v->isOk ($disc[$keys], "float", 0, 0, "Error : Discount for product number : <b>".($keys+1)."</b> is more than the unitcost.");
			}
			$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

	$cusnum+=0;

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}
	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($_POST, $err);
	}




	if(strlen($client)<1) {$client="Cash Sale";}
	$_POST['client']=$client;
	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li>- Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	$nitems=0;

	# insert invoice to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
			# get selected stock in this invoice
			$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);

			while($stkt = pg_fetch_array($stktRslt)){
				# update stock(alloc + qty)
				$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

				if(strlen($stkt['serno']) > 0)
					ext_unresvSer($stkt['serno'], $stkt['stkid']);

				$nitems--;
			}

			# remove old items
			$sql = "DELETE FROM pinv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);
		/* -- End remove old items -- */
		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				/* set the serial ss field for serials selected from list */
				if ($sernos_ss[$keys] == "*_*_*CUBIT_SERIAL_SELECT_BOX*_*_*") {
					$sernos_ss[$keys] = $sernos[$keys];
				}
				
				if(isset($remprod)&&in_array($keys, $remprod)){
					if ($sernos[$keys] == $sernos_ss[$keys] && $sernos_ss[$keys] != "") {
						$chr = substr($sernos[$keys], strlen($sernos[$keys])-1, 1);

						$tab = "ss$chr";
							
						/* mark barcoded item as unavailable */
						$sql = "UPDATE ".$tab." SET active='yes' WHERE code = '$sernos[$keys]' AND div = '".USER_DIV."'";
						db_exec($sql);
					}
				}elseif(isset($accounts[$keys])&&$accounts[$keys]!=0){
					$accounts[$keys]+=0;
					# Get selamt from selected stock
					db_conn('core');
					$Sl="SELECT * FROM accounts WHERE accid='$accounts[$keys]'";
					$Ri=db_exec($Sl) or errDie("Unable to get account data.");

					$ad=pg_fetch_array($Ri);

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys]));

					db_conn('cubit');
					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return details($_POST, "<li class=err>Please select the vatcode for all your items.</li>");
					}

					$vd=pg_fetch_array($Ri);

					# Check Tax Excempt
					if($vd['zero']=="Yes"){
						$taxex += $amt[$keys];
						$exvat="y";
					} else {
						$exvat="n";
					}

					//$newvat+=vatcalc($amt[$keys],$chrgvat,$exvat,$traddisc);
					$vatcodes[$keys]+=0;
					$accounts[$keys]+=0;
					$descriptions[$keys]=remval($descriptions[$keys]);
					$wtd = $whids[$keys];
					# insert invoice items
					$nitems++;
					$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, 
								disc, discp, ss, serno, div,vatcode,description,account) 
							VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', 
								'$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', 
								'$disc[$keys]', '$discp[$keys]', '', '','".USER_DIV."',
								'$vatcodes[$keys]','$descriptions[$keys]',
								'$accounts[$keys]')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

				}else{
					# get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					# Calculate the Discount discount
					if($disc[$keys] < 1){
						if($discp[$keys] > 0){
							$disc[$keys] = (($discp[$keys]/100) * $unitcost[$keys]);
						}
					}else{
						$discp[$keys] = (($disc[$keys] * 100) / $unitcost[$keys]);
					}

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($unitcost[$keys] - $disc[$keys]));

					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return details($_POST, "<li class=err>Please select the vatcode for all your items.</li>");
					}
					$vd=pg_fetch_array($Ri);

					# Check Tax Excempt
					if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
						$taxex += $amt[$keys];
						$exvat="y";
					} else {
						$exvat="n";
					}


					$wtd = $whids[$keys];
					# insert invoice items
					$nitems++;
					$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, 
								disc, discp, ss, serno, div,vatcode) 
							VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', 
								'$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', 
								'$disc[$keys]', '$discp[$keys]', '$sernos_ss[$keys]', 
								'$sernos[$keys]','".USER_DIV."','$vatcodes[$keys]')";
					// $sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div) VALUES('$invid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]','$amt[$keys]', '$disc[$keys]', '$discp[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

					if(strlen($sernos[$keys]) > 0)
						ext_resvSer($sernos[$keys], $stk['stkid']);

					# update stock(alloc + qty)
					$sql = "UPDATE stock SET alloc = (alloc + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
				# everything is set place done button
				$_POST["done"] = "<input name=doneBtn type=submit value='Process'>";
			}
		}else{
			$_POST["done"] = "";
		}

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$delvat' AND zero='Yes'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)>0) {
			$taxex += $delchrg;
		}

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

		if($chrgvat == "exc"){
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(($subtotal-$taxex)*$VATP/100);
			$SUBTOT = $sub;
			$TOTAL=sprint($subtotal+$VAT);
			$delexvat=sprint($delchrg);

		}elseif($chrgvat == "inc"){
			$ot=$taxex;
			$taxex=sprint($taxex-($taxex*$traddisc/100));
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(($subtotal-$taxex)*$VATP/(100+$VATP));
			$SUBTOT=sprint($sub);
			$TOTAL=sprint($subtotal);
			$delexvat=sprint(($delchrg));
			$traddiscmt=sprint($traddiscmt);

		} else {
			$subtotal=sprint($sub+$delchrg);
			$traddiscmt=sprint($subtotal*$traddisc/100);
			$subtotal=sprint($subtotal-$traddiscmt);
			$VAT=sprint(0);
			$SUBTOT=$sub;
			$TOTAL=$subtotal;
			$delexvat=sprint($delchrg);
		}

		/* --- ----------- Clac --------------------- */
		##----------------------END----------------------

/* --- ----------- Clac ---------------------

		# calculate subtot
		$SUBTOT = 0.00;
		if(isset($amt))
			$SUBTOT = array_sum($amt);

		$SUBTOT -= $taxex;

		# duplicate
		$SUBTOTAL = $SUBTOT;

		$VATP = TAX_VAT;
		if($chrgvat == "exc"){
			$SUBTOTAL = $SUBTOTAL;
			$delexvat= ($delchrg);
		}elseif($chrgvat == "inc"){
			$SUBTOTAL = sprint(($SUBTOTAL * 100)/(100 + $VATP));
			$delexvat = sprint(($delchrg * 100)/($VATP + 100));
		}else{
			$SUBTOTAL = ($SUBTOTAL);
			$delexvat = ($delchrg);
		}

		$SUBTOT = $SUBTOTAL;
		$EXVATTOT = $SUBTOT;
		$EXVATTOT += $delexvat;

		# Minus trade discount from taxex
		if($traddisc > 0){
			$traddiscmtt = (($traddisc/100) * $taxex);
		}else{
			$traddiscmtt = 0;
		}
		$taxext = ($taxex - $traddiscmtt);

		if($traddisc > 0) {
			$traddiscmt = ($EXVATTOT * ($traddisc/100));
		}else{
			$traddiscmt = 0;
		}
		$EXVATTOT -= $traddiscmt;
		// $EXVATTOT -= $taxex;

		$traddiscmt = sprint($traddiscmt  + $traddiscmtt);

		if($chrgvat != "nov"){
			$VAT = sprint($EXVATTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
		$SUBTOT += $taxex;

/* --- ----------- Clac --------------------- */

		$Sl="SELECT * FROM posround";
		$Ri=db_exec($Sl);

		$data=pg_fetch_array($Ri);

		if($data['setting']=="5cent") {
			if(sprint(floor(sprint($TOTAL/0.05)))!=sprint($TOTAL/0.05)) {
				$otot=$TOTAL;
				$nTOTAL=sprint(sprint(floor($TOTAL/0.05))*0.05);
				$rounding=($otot-$nTOTAL);
			} else {
				$rounding=0;
			}
		} else {
			$rounding=0;
		}

		//print sprint(floor($TOTAL/0.05));

		# insert invoice to DB
		$sql = "UPDATE pinvoices SET pcredit='$pcredit',cusnum='$cusnum',delvat='$delvat',rounding='$rounding',pcash='$pcash',pcheque='$pcheque',pcc='$pcc',deptid='$deptid',deptname = '$dept[deptname]', cusname = '$client', cordno = '$cordno', ordno = '$ordno',
		chrgvat = '$chrgvat', salespn = '$salespn',odate = '$odate', traddisc = '$traddisc', delchrg = '$delchrg', subtot = '$SUBTOT',
		vat = '$VAT', total = '$TOTAL', balance = '$TOTAL', comm = '$comm', discount='$traddiscmt', delivery='$delexvat'
		WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# remove old data
		$sql = "DELETE FROM pinv_data WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

		# put in new data
		$sql = "INSERT INTO pinv_data(invid, dept, customer, div) VALUES('$invid', '$dept[deptname]', '$client', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

	if (strlen($bar)>0)
	{
		/* check if there a stock item with global barcode matching input barcode */
		$sql = "SELECT * FROM stock WHERE bar='$bar' AND div = '".USER_DIV."'";
		$barRslt = db_exec($sql);

		if (pg_num_rows($barRslt) <= 0) {
			/* fetch last character of barcode */
			$chr = substr($bar, strlen($bar)-1, 1);
			
			/* invalid barcode */
			if (!is_numeric($chr)) {
				return details($_POST,"The code you selected is invalid");
			}

			/* which barcode table to scan for stock id */
			$tab = "ss$chr";
			$stid = barext_dbget($tab, 'code', $bar, 'stock');
			
			$stab = "serial$chr";
			$sstid = serext_dbget($stab, 'serno', $bar, 'stkid');	

			/* non-existing barcode, check for serial number */
			if ($stid <= 0) { 
				if ($sstid <= 0) {			
					return details($_POST,"<li class='err'>The serial nubmer/bar code you selected is not in the system or is not available.</li>");
				}
				
				if (serext_dbnum($stab, 'serno', $bar, 'stkid') > 1) {
					return details($_POST,"<li class='err'>Duplicate serial numbers found, please scan barcode or select stock item.</li>");
				}
				
				/* mark barcoded item as unavailable */
				$sql = "UPDATE ".$stab." SET rsvd='y' WHERE serno='$bar'";
				db_exec($sql);
				
				$serno_bar = "$bar";
				
				$stid = $sstid;
			} else {
				if ($sstid > 0) {
					return details($_POST,"<li class='err'>A serial and barcode with same value, please scan other value or select product manually.</li>");
				}
					
				/* mark barcoded item as unavailable */
				$sql = "UPDATE ".$tab." SET active='no' WHERE code='$bar' AND div='".USER_DIV."'";
				db_exec($sql);
				
				$serno_bar = "$bar";
			}
			
			/* fetch stock row for selected item */
			$sql = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$barRslt = db_exec($sql);
		} else {
			$serno_bar = "";
		}
		
		$s = pg_fetch_array($barRslt);
		
		/* allocate stock item */
		$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$s[stkid]' AND div = '".USER_DIV."'";
		db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		
		$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, 
					disc, discp, ss, serno, div, vatcode) 
				VALUES('$invid', '$s[whid]', '$s[stkid]', '1','$s[selamt]',
					'$s[selamt]','0','0','$bar', '$serno_bar', '".USER_DIV."', '2')";
		db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	
	if($nitems!=0) {
		$_POST["fcode"]="";
		$_POST["fdes"]="";
	}

/* --- Start button Listeners --- */
	if(isset($doneBtn)){

		# check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM pinv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class=err> Error : Invoice number has no items.";
			return details($_POST, $error);
		}

		$TOTAL=sprint($TOTAL-$rounding);

		if(($pcash+$pcheque+$pcc+$pcredit)<$TOTAL) {

			return details($_POST, "<li class=err>The total of all the payments is less than the invoice total</li>");

		}

		$change=sprint(sprint($pcash+$pcheque+$pcc+$pcredit)-sprint($TOTAL));

		$pcash=sprint($pcash-$change);

		if($pcash<0) {
			$pcash=0;
		}

		if(sprint($pcash+$pcheque+$pcc+$pcredit)!=sprint($TOTAL)) {

			return details($_POST, "<li class='err'>The total of all the payments is not equal to the invoice total.<br>
			(You can only overpay with cash)</li>");

		}


		# insert quote to DB
		$sql = "UPDATE pinvoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);

		//move('pos-invoice-speed.php');
		# print the invoice
		$OUTPUT = "
					<script>
						printer('pos-invoice-print.php?invid=$invid');
					</script>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><input type='button' value='Create Speed POS Invoice' onClick=\"javascript:window.location='pos-invoice-speed.php'\"></td>
						</tr>
					</table>
				";
		require("template.php");


	}elseif(isset($saveBtn)){

		// Final Laytout
		$write = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>New Point of Sale Invoice Saved</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Invoice for <b>$client</b> has been saved.</td>
						</tr>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='pos-invoice-list.php'>View Point of Sale Invoices</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
		return $write;
	}elseif(isset($cancel)){

		// Final Laytout
		$write = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>New Point of Sale Invoice Saved</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice for <b>$client</b> has been saved.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pos-invoice-list.php'>View Point of Sale Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $write;
	}else{
		if(isset($wtd)){
			$_POST['wtd']=$wtd;
		}
		return details($_POST);
	}
/* --- End button Listeners --- */
}


?>
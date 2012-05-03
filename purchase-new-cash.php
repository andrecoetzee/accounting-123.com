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
if (isset($_GET["purid"]) && isset($_GET["cont"])) {
	$_GET['done'] = "";
	$OUTPUT = details($_GET);
}elseif (isset($_GET["deptid"])){
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
				$OUTPUT = view();
			}
	} else {
		$OUTPUT = view();
	}
}

# get templete
require("template.php");


# Default view
function view()
{
	# Query server for depts
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
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
						<th colspan='2'>New Order</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Select Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
						<td valign='center'><input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='purchase-view.php'>View Orders</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='supp-new.php'>New Supplier</a></td>
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

	//layout
	$view = "
				<br><br>
				<form action='".SELF."' method=post name=form>
				<table ".TMPL_tblDflts." width='400'>
					<input type='hidden' name='key' value='details'>
					<input type='hidden' name='cussel' value='cussel'>
					<tr>
						<th colspan='2'>New Order</th>
					</tr>
					<tr>
						<td colspan='2'>$err</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Select Department</td>
						<td valign='center'>$depts</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td>
						<td valign='center'><input type='submit' value='Continue &raquo'></td>
					</tr>
				</table>
				</form>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='purchase-view.php'>View Orders</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='customers-new.php'>New Customer</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
        return $view;
}



# Starting dummy
function create_dummy($deptid)
{

	db_connect();

	# Dummy Vars
	$supid = 0;
	$remarks = "";
	$supname = "";
	$supaddr = "";
	$supno = "";
	$terms = "0";
	$total = 0;
	$subtot = 0;
	$pdate = date("Y-m-d");
	$ddate = date("Y-m-d");
	$shipchrg = "0.00";

	if(getSetting("PURCH_APPRV") == 'napprv'){
		$apprv = 'y';
	}else{
		$apprv = 'n';
	}

	$purnum = divlastid('pur', USER_DIV);

	# Insert purchase to DB
	$sql = "INSERT INTO purchases(deptid, supid, supname, supaddr, supno, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, remarks, received, done, prd, cash, div, purnum, apprv)";
	$sql .= " VALUES('$deptid', '$supid', '$supname', '$supaddr', '$supno', '$terms', '$pdate', '$ddate', '$shipchrg', '$subtot', '$total', '$total', 'yes', '0', '$remarks', 'n', 'n', '".PRD_DB."', 'y', '".USER_DIV."', '$purnum', '$apprv')";
	$rslt = db_exec($sql) or errDie("Unable to insert Purchase to Cubit.",SELF);

	# Get next ordnum
	//$purid = pglib_lastid ("purchases", "purid");
	$purid = lastpurid();
	return $purid;

}



# details
function details($_POST, $error="")
{
	
	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($purid)){
		$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	}else{
		$v->isOk ($deptid, "num", 1, 20, "Invalid department number.");
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

	if(!isset($purid)){
		$purid = create_dummy($deptid);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$deptid = $pur['deptid'];

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order no. $pur[purnum] has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	if(!(isset($ordernum))) {$ordernum='';}
	if(!(isset($supinv))) {$supinv='';}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	$supname = $pur['supname'];
	$supaddr = $pur['supaddr'];
	$supno = $pur['supno'];


/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class='err'> There are no Stores found in Cubit.</li>";
	}else{
			$whs .= "<option value='-S' disabled selected>Select Store</option>";
			while($wh = pg_fetch_array($whRslt)){
					$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
	}
	$whs .="</select>";

	# days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($p_year, $p_month, $p_day) = explode("-", $pur['pdate']);

	# keep the charge vat option stable
	if($pur['vatinc'] == "yes"){
		$chy = "checked=yes";
		$chn = "";
		$chv = "";
	}elseif($pur['vatinc'] == "no"){
		$chy = "";
		$chn = "checked=yes";
		$chv = "";
	}else{
		$chy = "";
		$chn = "";
		$chv = "checked=yes";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>STORE</th>
							<th>ITEM NUMBER</th>
							<th>VAT CODE</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>PRICE PER UNIT</th>
							<th>DELIVERY DATE</th>
							<th>AMOUNT</th>
							<th>VAT</th>
							<th>Remove</th>
						<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

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

		list($d_year[$i], $d_month[$i], $d_day[$i]) = explode("-", $stkd['ddate']);

		$stkd['amt'] = sprint($stkd['amt']);

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd=pg_fetch_array($Ri);

		if($pur['vatinc'] == 'no' && $stk['exvat'] != 'yes'){
			$vunitamt = sprint($stkd['unitcost']);
		}else{
			$vunitamt = sprint($stkd['unitcost']);
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

		$tip = "&nbsp;&nbsp;&nbsp;";
		if(isset($vatc[$key])){
			$tip = "<font color=red>#</font>";
			$error = "<div class=err> $tip&nbsp;&nbsp;=&nbsp;&nbsp; Vat amount is different from amount calculated by cubit. To allow cubit to recalculate the vat amount, please delete the vat amount from the input box.";
		}

		# put in product
		$products .= "
						<tr class='".bg_class()."'>
							<td><input type='hidden' name='whids[]' value='$stkd[whid]'>$wh[whname]</td>
							<td><input type='hidden' name='stkids[]' value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
							<td>$Vatcodes</td>
							<td>$stk[stkdes]</td>
							<td><input type='text' size='3' name='qtys[]' value='$stkd[qty]'></td>
							<td>".CUR." <input type='text' size='8' name='unitcost[]' value='$vunitamt'></td>
							<td>".mkDateSelecta("d",$key,$d_year[$i],$d_month[$i],$d_day[$i])."</td>
							<td align='right'><input type='hidden' name='amt[]' value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
							<td>$tip <input type='text' name='svat[]' size='9' value='$stkd[svat]'></td>
							<td><input type='checkbox' name='remprod[]' value='$key'><input type='hidden' name='SCROLL' value='yes'></td>
						</tr>";
		$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
$l = $i++;
	# check if stock warehouse was selected
	if(isset($whidss)){
		foreach($whidss as $key => $whid){
			if(isset($stkidss[$key]) && $stkidss[$key] != "-S"){
				# skip if not selected
				if($whid == "-S"){
					continue;
				}

				# Get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# Get selected stock in this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE stkid = '$stkidss[$key]' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				# Calculate amount
				$amt[$key] = sprint($qtyss[$key] * 0);

				db_conn('cubit');
				$Sl="SELECT * FROM vatcodes ORDER BY code";
				$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

				$Vatcodes="<select name=vatcodes[]>
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

				# Put in selected warehouse and stock
				$products .= "
								<tr class='".bg_class()."'>
									<td><input type='hidden' name='whids[]' value='$whid'>$wh[whname]</td>
									<td><input type='hidden' name='stkids[]' value='$stk[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td>
									<td>$Vatcodes</td>
									<td>$stk[stkdes]</td>
									<td><input type='text' size='3' name='qtys[]' value='$qtyss[$key]'></td>
									<td><input type='hidden' name='novat[$keyy]' value='1'>".CUR." <input type='text' size='8' name='unitcost[]' value='0'></td>
									<td>".mkDateSelecta("d",$key,$d_year[$l],$d_month[$l],$d_day[$l])."</td>
									<td align='right'><input type='hidden' name='amt[]' value='$amt[$key]'> ".CUR." $amt[$key]</td>
									<td> </td>
									<td><input type='checkbox' name='remprod[]' value='$keyy'><input type='hidden' name='SCROLL' value='yes'></td>
								</tr>";
				$key++;
			}else{
				if(!isset($diffwhBtn)){
					# Skip if not selected
					if($whid == "-S"){
						continue;
					}

					# Get warehouse name
					db_conn("exten");
					$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);

					# Get stock on this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
					$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
					if (pg_numrows ($stkRslt) < 1) {
						$error .= "<li class='err'>There are no stock items in the selected warehouse.</li>";
						continue;
					}
					$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
					$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
					$count = 0;
					while($stk = pg_fetch_array($stkRslt)){
						$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
					}
					$stks .= "</select> ";

					# Put in drop down and warehouse
					$products .= "
									<tr class='".bg_class()."'>
										<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
										<td>$stks</td>
										<td> </td>
										<td><input type='text' size='3' name='qtyss[]'  value='1'></td>
										<td> </td>
										<td>".mkDateSelecta("d",$key,$d_year[$l],$d_month[$l],$d_day[$l])."</td>
										<td align='right'><input type='hidden' name='amts[]' value='0.00'>".CUR." 0.00</td>
										<td> </td>
										<td> </td>
									</tr>";
				}
			}
			$l++;
		}
	}else{
		if(!isset($diffwhBtn)){
			# take todays date
			list($date_year, $date_month, $date_day) = explode("-", $pur['pdate']);

			# check if setting exists
			db_connect();
			$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
			$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
			if (pg_numrows ($Rslt) > 0) {
				$set = pg_fetch_array($Rslt);
				$whid = $set['value'];
				if(isset($wtd)){$whid=$wtd;}
				# get selected warehouse name
				db_conn("exten");
				$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				# get stock on this warehouse
				db_connect();
				$sql = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
				$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
				if (pg_numrows ($stkRslt) < 1) {
					if(!(isset($err))) {$err="";}
					$err .= "<li>There are no stock items in the selected warehouse.</li>";
				}
				$stks = "<select name='stkidss[]' onChange='javascript:document.form.submit();'>";
				$stks .= "<option value='-S' disabled selected>Select Item Number</option>";
				$count = 0;
				while($stk = pg_fetch_array($stkRslt)){
					$stks .= "<option value='$stk[stkid]'>$stk[stkcod] (".($stk['units'] - $stk['alloc']).")</option>";
				}
				$stks .= "</select> ";
				$products .= "
								<tr class='".bg_class()."'>
									<td><input type='hidden' name='whidss[]' value='$whid'>$wh[whname]</td>
									<td>$stks</td>
									<td> </td>
									<td> </td>
									<td><input type='text' size='3' name='qtyss[]' value='1'></td><td> </td>
									<td>".mkDateSelecta("d","",$date_year,$date_month,$date_day)."</td>
									<td>".CUR." 0.00</td>
									<td> </td>
									<td></td>
								</tr>";
			}else {
				$products .= "
								<tr class='".bg_class()."'>
									<td>$whs</td>
									<td> </td>
									<td> </td>
									<td> </td>
									<td> </td>
									<td> </td>
									<td>".mkDateSelecta("d","",$date_year,$date_month,$date_day)."</td>
									<td></td>
								</tr>";
			}
		}
	}

/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		# take todays date
		list($date_year, $date_month, $date_day) = explode("-", $pur['pdate']);
		$products .= "
					<tr class='".bg_class()."'>
						<td>$whs</td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td>".mkDateSelecta("d","",$date_year,$date_month,$date_day)."</td>
						<td>".CUR." 0.00</td>
						<td></td>
					</tr>";
	}

/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

	# Shipping Charges
	$pur['shipchrg'] = sprint($pur['shipchrg']);

	$pur['delvat']+=0;

	if($pur['delvat']==0) {
		$Sl="SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$vd=pg_fetch_array($Ri);

		$pur['delvat']=$vd['id'];
	}

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name='delvat'>
	<option value='0'>Select</option>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['id']==$pur['delvat']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}

	$Vatcodes.="</select>";

/* --- End Some calculations --- */

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

/* -- Final Layout -- */
	$details = "
					<center>
					<h3>New Order</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='update'>
						<input type='hidden' name='purid' value='$purid'>
						<input type='hidden' name='deptid' value='$deptid'>
					<table ".TMPL_tblDflts." width='95%'>
					 	<tr>
					 		<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Supplier Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Department</td>
										<td valign='center'>$dept[deptname]</td></tr>
									<tr class='".bg_class()."'>
										<td>Supplier</td>
										<td valign='center'><input type='text' size='20' name='supname' value='$supname'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier No</td>
										<td valign='center'><input type='text' size='20' name='supno' value='$supno'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td valign='top'>Supplier Address</td>
										<td valign='center'><textarea name='supaddr' rows=4 cols='18'>$supaddr</textarea></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th colspan='2'> Order Details </th>
									</tr>
									<tr class='".bg_class()."'>
										<td>Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Order No.</td>
										<td valign='center'><input type='text' size='10' name='ordernum' value='$pur[ordernum]'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Supplier Inv</td>
										<td><input type='text' name='supinv' size='10' value='$pur[supinv]'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Terms</td>
										<td valign='center'>$termssel Days</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Date</td>
										<td valign='center'>".mkDateSelect("p",$p_year,$p_month,$p_day)."</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>Yes <input type='radio' size='7' name='vatinc' value='yes' $chy> No<input type='radio' size='7' name='vatinc' value='no' $chn> No Vat<input type='radio' size='7' name='vatinc' value='novat' $chv></td>
										</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges</td>
										<td valign='center'><input type='text' size='7' name='shipchrg' value='$pur[shipchrg]'></td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges VAT Code</td>
										<td valign='center'>$Vatcodes</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2'>$products</td>
						</tr>
						<tr>
							<td>
								<p>
								<table ".TMPL_tblDflts.">
									<tr>
										<th width='25%'>Quick Links</th>
										<th width='25%'>Remarks</th>
										<td rowspan='5' valign='top' width='50%'>$error</td>
									</tr>
									<tr>
										<td class='".bg_class()."'><a href='purchase-view.php'>View Orders</a></td>
										<td class='".bg_class()."' rowspan='4' align='center' valign='top'><textarea name='remarks' rows='4' cols='20'>$pur[remarks]</textarea></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr class='".bg_class()."'>
										<td>SUBTOTAL</td>
										<td align='right'>".CUR." <input type=hidden name=subtot value='$SUBTOT'>$SUBTOT</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>Delivery Charges</td>
										<td align='right'>".CUR." $pur[shipping]</td>
									</tr>
									<tr class='".bg_class()."'>
										<td>VAT $vat14</td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr class='".bg_class()."'>
										<th>GRAND TOTAL</th>
										<td align='right'>".CUR." <input type='hidden' name='total' value='$TOTAL'>$TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'> | <input name=diffwhBtn type=submit value='Different Store'> |</td><td><input type=submit name='upBtn' value='Update'>$done</td>
						</tr>
					</table>
					</form>
					</center>";

	return $details;
}



# details
function write($_POST)
{

	#get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 9, "Invalid Order ID");
	$v->isOk ($supname, "string", 1, 255, "Invalid Supplier Name.");
	$v->isOk ($supno, "string", 0, 255, "Invalid Supplier No.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid Supplier Address.");
	$v->isOk ($ordernum, "string", 0, 20, "Invalid order number.");
	$v->isOk ($supinv, "string", 0, 50, "Invalid supplier inv.");
	$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
	$v->isOk ($p_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($p_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($p_year, "num", 1, 5, "Invalid Date year.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$pdate = $p_year."-".$p_month."-".$p_day;
	if(!checkdate($p_month, $p_day, $p_year)){
    	$v->isOk ($date, "num", 1, 1, "Invalid Date.");
    }

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");

			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}

			if(!isset($novat[$keys])){
				$v->isOk ($svat[$keys], "float", 0, 10, "Invalid vat amount. Product number : <b>".($keys+1)."</b>");
			}

			# Validate ddate[]
			$v->isOk ($d_day[$keys], "num", 1, 2, "Invalid Delivery Date day.");
			$v->isOk ($d_month[$keys], "num", 1, 2, "Invalid Delivery Date month.");
			$v->isOk ($d_year[$keys], "num", 1, 5, "Invalid Delivery Date year.");
			$ddate[$keys] = $d_year[$keys]."-".$d_month[$keys]."-".$d_day[$keys];
			if(!checkdate($d_month[$keys], $d_day[$keys], $d_year[$keys])){
				$v->isOk ($ddate[$keys], "num", 1, 1, "Invalid Delivery Date.");
			}
		}
	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

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
		$_POST['done'] = "";
		return details($_POST, $err);
	}

	
	
	
	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li> - Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$pur[purnum]</b> has already been received.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	$supid = 0;

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Vat percantege
	$VATP = TAX_VAT;

	# Fix those nasty zeros
	$shipchrg += 0;

	# insert purchase to DB
	db_connect();

	$showvat = TRUE;

# Begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
			# get selected stock in this purchase
			db_connect();
			$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);

			while($stkt = pg_fetch_array($stktRslt)){
				# update stock(ordered - qty)
				$sql = "UPDATE stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}

			# remove old items
			$sql = "DELETE FROM pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		/* -- End remove old items -- */
		$taxex = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod)&&in_array($keys, $remprod)){
/*				if(isset($remprod)){
					if(in_array($keys, $remprod)){
						# skip product (wonder if $keys still align)
						$amt[$keys] = 0;
						continue;
					}else{
						# get selamt from selected stock
						$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
						$stkRslt = db_exec($sql);
						$stk = pg_fetch_array($stkRslt);

						# Calculate amount
						$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

						if(isset($novat[$keys])){
							# Check Tax Excempt
							if($stk['exvat'] != 'yes'){
								# If vat is not included
								if($vatinc == "no"){
									$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
								}else{
									$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
								}
							}else{
								$vat[$keys] = 0;
							}
						}elseif(isset($svat[$keys]) && strlen($svat[$keys]) < 1){
							# Check Tax Excempt
							if($stk['exvat'] != 'yes'){
								# If vat is not included
								if($vatinc == "no"){
									$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
								}else{
									$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
								}
							}else{
								$vat[$keys] = 0;
							}
						}elseif($vatinc == "novat"){
							$vat[$keys] = 0;
						}else{
							if($stk['exvat'] != 'yes'){
								$vat[$keys] = $svat[$keys];
							}else{
								$vat[$keys] = 0;
							}
						}

						if($vatinc != "novat"){
							# Track Vat Changes
							if($stk['exvat'] != 'yes'){
								# If vat is not included
								if($vatinc == "no"){
									$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
								}else{
									$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
								}
							}else{
								$vatc[$keys] = 0;
							}
							if($vat[$keys] <> $vatc[$keys]){
								$_POST["vatc"][$keys] = "yes";
							}
						}

						# format ddate
						$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";

						$wtd=$whids[$keys];
						# insert purchase items
						$sql = "INSERT INTO pur_items(purid, whid, stkid, qty, unitcost, amt, ddate, svat, div) VALUES('$purid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$ddate[$keys]', '$vat[$keys]', '".USER_DIV."')";
						$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

						# update stock(ordered + qty)
						$sql = "UPDATE stock SET ordered = (ordered + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
						$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
					}*/
				}else{
					# get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					db_conn('cubit');
					$Sl="SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return "Please select the vatcode for all your stock.";
					}

					$vd=pg_fetch_array($Ri);
					$VATP = $vd['vat_amount'];

					if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
						$showvat = FALSE;
					}

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

					if(isset($novat[$keys])){
						# Check Tax Excempt
						if($stk['exvat'] != 'yes'){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}else{
								$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
							}
						}else{
							$vat[$keys] = 0;
						}
					}elseif(isset($svat[$keys]) && strlen($svat[$keys]) < 1){
						# Check Tax Excempt
						if($stk['exvat'] != 'yes'){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}else{
								$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
							}
						}else{
							$vat[$keys] = 0;
						}
					}elseif($vatinc == "novat"){
						$vat[$keys] = 0;
					}else{
						if($stk['exvat'] != 'yes'){
							$vat[$keys] = $svat[$keys];
						}else{
							$vat[$keys] = 0;
						}
					}

					if($vatinc != "novat"){
						# Track Vat Changes
						if($stk['exvat'] != 'yes'){
							# If vat is not included
							if($vatinc == "no"){
								$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}else{
								$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
							}
						}else{
							$vatc[$keys] = 0;
						}
						if($vat[$keys] <> $vatc[$keys]){
							$_POST["vatc"][$keys] = "yes";
						}
					}

					# ddate
					$ddate[$keys] = "$d_year[$keys]-$d_month[$keys]-$d_day[$keys]";

					$wtd=$whids[$keys];
					# insert purchase items
					$sql = "INSERT INTO pur_items(purid, whid, stkid, qty, unitcost, amt, ddate, svat, vatcode, div) VALUES('$purid', '$whids[$keys]', '$stkids[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$ddate[$keys]', '$vat[$keys]','$vatcodes[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

					# update stock(ordered + qty)
					$sql = "UPDATE stock SET ordered = (ordered + '$qtys[$keys]') WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
				}
				# everything is set place done button
				$_POST["done"] = " | <input name=doneBtn type=submit value='Done'>";
			}
		}else{
			$_POST["done"] = "";
		}

	/* --- Clac --- */

		# calculate subtot
		if(isset($amt)){
			$SUBTOT = array_sum($amt);
		}else{
			$SUBTOT = 0.00;
		}

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$delvat'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			$Sl="SELECT * FROM vatcodes";
			$Ri=db_exec($Sl);
		}

		$vd=pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$_POST['showvat'] = $showvat;

		# If vat is not included (delchrg)
	//	$VATP = TAX_VAT;
		if($vatinc == "no"){
			$svat = sprint(($VATP/100) * $shipchrg);
			$shipexvat = $shipchrg;
		}elseif($vatinc == "yes"){
			$svat = sprint(($shipchrg/($VATP+100)) * $VATP);
			$shipexvat = ($shipchrg - $svat);
		}else{
			$svat = 0;
			$shipexvat  = $shipchrg;
		}

		# Total
		$TOTAL = ($SUBTOT + $shipexvat);

		# If there vatable items
		if(isset($vat)){
			$VAT = array_sum($vat);
		}else{
			$VAT = 0;
		}

		# If vat is not included
		if($vatinc == "no"){
			$TOTAL = ($TOTAL + $VAT + $svat);
		}else{
			$TOTAL = ($TOTAL + $svat);
			$SUBTOT -= ($VAT);
		}

		$VAT += $svat;

	/* --- End Clac --- */


		# Insert purchase to DB
		$sql = "UPDATE purchases SET delvat='$delvat', supid = '$supid', supname = '$supname', supaddr = '$supaddr', supno = '$supno', terms = '$terms', pdate = '$pdate', shipchrg = '$shipchrg', subtot = '$SUBTOT', total = '$TOTAL', balance = '$TOTAL', vatinc = '$vatinc', vat = '$VAT', shipping = '$shipexvat', ordernum='$ordernum', remarks = '$remarks', supinv='$supinv' WHERE purid = '$purid' ";
		$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(!isset($doneBtn)){
		if(isset($wtd)){$_POST['wtd']=$wtd;}
		return details($_POST);
	}else{
		# insert purchase to DB
		$sql = "UPDATE purchases SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		// Final Laytout
		$write = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>New Order</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Order to Supplier <b>$pur[supname]</b> has been recorded.</td>
						</tr>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='purchase-view.php'>View Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
		return $write;
	}

}


?>
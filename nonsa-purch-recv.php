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
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($_GET["purid"])) {
	$OUTPUT = slct($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "update":
				$OUTPUT = write($_POST);
				break;

			case "slct":
				$OUTPUT = details($_POST);
				break;

            default:
				$OUTPUT = "<li class=err> - Invalid use of module.";
			}
	} else {
		$OUTPUT = "<li class=err> - Invalid use of module.";
	}
}

# get templete
require("template.php");

# Details
function slct($_GET, $err = "")
{

	# Get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	db_connect();
	$sql = "SELECT * FROM suppliers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);
	$sups = "<select name=supid>";
	if(pg_numrows($supRslt) < 1) $sups .= "<option value='-S'></option>";
	while($sup = pg_fetch_array($supRslt)){
		$sups .= "<option value='$sup[supid]'>$sup[supno] $sup[supname]</option>";
	}
	$sups .= "</select>";

	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	$depts = "<select name=deptid>";
	if(pg_numrows($deptRslt) < 1) $depts .= "<option value='-S'></option>";
	while($dept = pg_fetch_array($deptRslt)){
		$depts .= "<option value=$dept[deptid]>$dept[deptname]</option>";
	}
	$depts .= "</select>";

	// Option removed
	// <tr bgcolor='".TMPL_tblDataColor1."' ".ass("Select when tranferring goods between Departments or Stores")."><td colspan=2><input type=radio name=ctyp value='c' checked=yes>Accounts Order</td></tr>

	$details = "<center>
	<h3>Non-Stock Asset Order received</h3>
	<h4>Supplier Details</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=slct>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
	<tr><td colspan=2>$err</td></tr>
 	<tr><th colspan=2> Order Details </th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."' ".ass("Select when purchasing non stock goods from your suppliers")."><td><input type=radio name=ctyp value='s' checked=yes> Select Supplier</td><td>$sups</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."' ".ass("Select when the Order of non stock goods is a cash Order")."><td><input type=radio name=ctyp value='c'>Cash Order</td><td>$depts</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><input type=radio name=ctyp value='p'>Petty Cash Order</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=center><input type=submit value='Continue &raquo;'></td></tr>
	</table>";

	return $details;
}

# Details
function details($_POST, $error="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($supid, "num", 1, 20, "Invalid supplier account number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		return slct($_POST, $error);
		$confirm = "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	$supacc = "<select name='supacc'>";
		core_connect();
		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.";
		}
		while($acc = pg_fetch_array($accRslt)){
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;
			$supacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}
	$supacc .= "</select>";

	# Get selected supplier info
	db_connect();
	$hide = "";
	if(isset($ctyp) && $ctyp == 's'){
		$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
		if (pg_numrows ($supRslt) < 1) {
			$error = "<li class=err> Supplier not Found.";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$sup = pg_fetch_array($supRslt);
			$pur['supplier'] = $sup['supname'];
			$pur['supaddr'] = $sup['supaddr'];
			$supacc = $sup['supno'];
			$hide = "<input type=hidden name=supid value='$supid'><input type=hidden name=ctyp value='$ctyp'>";
		}
	}elseif(isset($ctyp) && $ctyp == 'c'){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class=err> Department not Found.";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = "$dept[deptname] - Cash on Hand";
			$hide = "<input type=hidden name=deptid value='$deptid'><input type=hidden name=ctyp value='$ctyp'>";
		}
	}elseif(isset($ctyp) && $ctyp == 'p'){
		core_connect();
        # Get Petty cash account
		$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $cashacc);
		if(pg_numrows($accRslt) < 1){
			return "<li class=err> Petty Cash Account not found.";
		}
		$acc = pg_fetch_array($accRslt);

		$supacc = "$acc[topacc]/$acc[accnum] - $acc[accname]";
		$hide = "<input type=hidden name=supacc value='$cashacc'><input type=hidden name=ctyp value='$ctyp'>";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY RECEIVED</th><th>UNIT PRICE</th><th>DELIVERY DATE</th><th>AMOUNT</th><tr>";

	# get selected stock in this Order
	db_connect();
	$sql = "SELECT *, (qty - rqty) as qty FROM nons_pur_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden size=4 name=cod[] value='$stkd[cod]'>$stkd[cod]</td><td>$stkd[des]</td><td><input type=hidden name=qts[] value='$stkd[qty]'><input type=text size=5 name=qtys[] value='$stkd[qty]'></td><td><input type=hidden size=4 name=unitcost[] value='$stkd[unitcost]'>$stkd[unitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
		$key++;
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

	db_conn('cubit');
	$Sql = "SELECT * FROM assets WHERE (id = '$pur[assid]' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){
		return "<li class=err> - Asset not Found";
	}
	$asset = pg_fetch_array($Rslt);


/* -- Final Layout -- */
	$details = "<center><h3>Non-Stock Asset Order received</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	$hide
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier</td><td valign=center>$pur[supplier]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Supplier Address</td><td valign=center><pre>$pur[supaddr]</pre></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."' ".ass("Select the account you wish to Credit")."><td>Account</td><td>$supacc</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Order Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Asset</td><td valign=center>$asset[des]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Non-Stock Order No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Ref No.</td><td valign=center><input type=text name=refno size=10 value='$pur[refno]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms</td><td valign=center>$pur[terms] Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td valign=center><input type=text size=2 name=pday maxlength=2 value='$pday'>-<input type=text size=2 name=pmon maxlength=2 value='$pmon'>-<input type=text size=4 name=pyear maxlength=4 value='$pyear'> DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Inclusive</td><td valign=center>$pur[vatinc]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charges</td><td valign=center>".CUR." <input type=text name=shipchrg size=10 value='$pur[shipchrg]'></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='nons-purchase-new.php'>New Order</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-purchase-view.php'>View Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>

		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charges</td><td align=right>".CUR." $pur[shipping]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $pur[vat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input type=submit name='upBtn' value='Write'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	#get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	if(!isset($supid) && !isset($deptid)){
		$v->isOk ($supacc, "num", 1, 10, "Invalid Supplier Account number.");
	}
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");

	$pdate = $pyear."-".$pmon."-".$pday;
	if(!checkdate($pmon, $pday, $pyear)){
    	$v->isOk ($date, "num", 1, 1, "Invalid Date.");
    }

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qty > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "Error : Quantity for product number : <b>".($keys+1)."</b> is more that Qty Orderd");
			}
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($_POST, $err);
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$pur['pdate'] = $pyear."-".$pmon."-".$pday;

	# Get selected supplier info
	db_connect();
	if(isset($supid)){
		$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
		$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
		if (pg_numrows ($supRslt) < 1) {
			$error = "<li class=err> Supplier not Found.";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$sup = pg_fetch_array($supRslt);
			$pur['supplier'] = $sup['supname'];
			$pur['supaddr'] = $sup['supaddr'];

			# Get department info
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$sup[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class=err>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}
			$supacc = $dept['credacc'];
		}
	}elseif(isset($deptid)){
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class=err> Department not Found.";
			$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$dept = pg_fetch_array($deptRslt);
			$supacc = $dept['pca'];
		}
	}

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	db_connect();
	$Sql = "SELECT * FROM assets WHERE (id = '$pur[assid]' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){
		return "<li class=err> - Asset not Found";
	}
	$asset = pg_fetch_array($Rslt);

	# Get group
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$asset[grpid]' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	$stkacc = $grp['costacc'];

	# Insert Order to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		if(isset($qtys)){
			# amount of stock in
			$totstkamt = array();
			$resub = 0;
			# Get subtotal
			foreach($qtys as $keys => $value){
				# Skip zeros
				if($qtys[$keys] < 1){
					continue;
				}
				$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);
			}
			$SUBTOTAL = array_sum($amt);
			$revat = 0;
			foreach($qtys as $keys => $value){
				# Get selected stock line
				$sql = "SELECT * FROM nons_pur_items WHERE cod = '$cod[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				# Calculate cost amount bought
				$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

				/* delivery charge */

					# Calculate percentage from subtotal
					$perc[$keys] = (($amt[$keys]/$SUBTOTAL) * 100);

					# Get percentage from shipping charges
					$shipc[$keys] = (($perc[$keys] / 100) * $shipchrg);

					# add delivery charges
					$amt[$keys] += $shipc[$keys];

				/* end delivery charge */

				# the subtotal + delivery charges
				$resub += $amt[$keys];

				# calculate vat
				$svat[$keys] = svat($amt[$keys], $stkd['amt'], $stkd['svat']);

				# received vat
				$revat += $svat[$keys];

				# make amount vat free
				if($pur['vatinc'] == "yes"){
					$amt[$keys] = ($amt[$keys] - $svat[$keys]);
				}

				# Update Order items
				$sql = "UPDATE nons_pur_items SET rqty = (rqty + '$qtys[$keys]') WHERE cod = '$cod[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

				# keep records for transactions
				if(isset($totstkamt[$stkacc[$keys]])){
					$totstkamt[$stkacc] += $amt[$keys];
				}else{
					$totstkamt[$stkacc] = $amt[$keys];
				}

				# check if there are any outstanding items
				$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				# if none the set to received
				if(pg_numrows($stkdRslt) < 1){
					# update surch_int(received = 'y')
					$sql = "UPDATE nons_purchases SET received = 'y', supplier = '$pur[supplier]', supaddr = '$pur[supaddr]' WHERE purid = '$purid' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update international Orders in Cubit.",SELF);
				}
			}
		}

		# Update Order on the DB
		if($pur['part'] == 'y'){
			# Update Order on the DB
			$sql = "UPDATE nons_purchases SET shipchrg = (shipchrg + '$shipchrg'), refno = '$refno', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
		}else{
			# Update Order on the DB
			$sql = "UPDATE nons_purchases SET shipchrg = '$shipchrg', refno = '$refno', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
		}

/* Transactions */

	$refnum = getrefnum(date("d-m-Y"));

/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

/* - End Hooks - */
		$detadd = "";
		if(isset($supid)){
			$detadd = " from Supplier $sup[supname]";
		}

		$sdate = $pur['pdate'];
		$tpp=0;
		$ccamt = 0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Debit Stock and Credit Suppliers control
			writetrans($stkacc, $supacc, date("d-m-Y"), $refnum, $wamt, "Non-Stock Order No. $pur[purnum] Received $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Order No. $pur[purnum] Received $detadd.", $wamt, "Cash Order");
			// if(cc_TranTypeAcc($stkacc, $supacc) != false){
				// $ccamt += $wamt;
			// }
		}

		# vat
 		$vatamt = $revat;

		# Add vat if not included
		if($pur['vatinc'] == 'no'){
			$retot = ($resub + $vatamt);
		}elseif($pur['vatinc'] == "novat") {
			$retot = ($resub);
			$vatamt = 0;
		}else{
			$retot = ($resub);
		}

		if(isset($supid)){
			# Ledger Records
			$DAte = $pur['pdate'];
			suppledger($sup['supid'], $stkacc, $DAte, $pur['purid'], "Non-Stock Order No. $pur[purnum] received.", $retot, 'c');
		}

		if($vatamt <> 0){
			# Debit bank and credit the account involved
			writetrans($vatacc, $supacc, date("d-m-Y"), $refnum, $vatamt, "Non-Stock Order Vat paid on Non-Stock Order No. $pur[purnum] $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Order No. $pur[purnum] Received $detadd.", $vatamt, "Cash Order Vat");

			# Record the payment on the statement
			db_connect();
			$sdate = $pur['pdate'];
		}

		if(isset($supid)){
			$DAte = $pur['pdate'];

			db_connect();
			# update the supplier (make balance more)
			$sql = "UPDATE suppliers SET balance = (balance + '$retot') WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, amount, descript,ref,ex,div) VALUES('$sup[supid]','$DAte', '$dept[credacc]', '$retot','Non Stock Order Received : $pur[purnum] $pur[remarks]', '$refnum', '$pur[purnum]','".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			db_connect();

			# update the supplier age analysis (make balance less)
			/* Make transaction record for age analysis */
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$sup[supid]', '$pur[purnum]', '$DAte', '$retot', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}
		/*elseif(isset($bankid)){
			$DAte=$pur['pdate'];
			# Record the payment record
			db_connect();
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div) VALUES ('$bankid', 'withdrawal', '$DAte', 'Cash Order', 'Non stock Order No. $pur[purnum]', '0', '$retot', 'no', '$stkacc', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
		}*/

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* End Transactions */

/* Start moving if Order received */

		# Get Order info
		db_connect();
		$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li>- Order Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y"){
			# copy Order
			db_conn($pur['prd']);
			$sql = "INSERT INTO nons_purchases(purid, deptid, supplier, supaddr, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, remarks, refno, received, done, div, purnum)";
			$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supplier]',  '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]')";
			$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);


			db_connect();
			# Get selected stock
			$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert Order items
				db_conn($pur['prd']);
				$sql = "INSERT INTO nons_pur_items(purid, cod, des, qty, unitcost, amt, ddate, div) VALUES('$purid', '$stktc[cod]', '$stktc[des]', '$stktc[qty]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[ddate]', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
			}

			db_connect();
			# Remove the Order from running DB
			$sql = "DELETE FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

			# Remove those Order items from running DB
			$sql = "DELETE FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
		}

/* End moving Order received */

	$cc = "<script> CostCenter('ct', 'Non-Stock Purchase', '$pur[pdate]', 'Non Stock Purchase No.$pur[purnum]', '".($retot-$vatamt)."', ''); </script>";

	// Final Layout
	$write = "$cc
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Non-Stock Asset Order received</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Non-Stock Asset Order receipt has been recorded.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-purchase-view.php'>View Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}

function vats($amt, $inc){
	# If vat is not included
	$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "novat") {
		$ret = ($amt);
	}else{
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}
	return $ret;
}

function svat($amt, $samt, $svat){
	$perc = ($amt/$samt);
	$rvat = sprint($perc * $svat);
	return $rvat;
}

function vat($amt, $inc){
	# If vat is not included
	$VATP = TAX_VAT;
	if($inc == "no"){
		$VAT = sprint(($VATP/100) * $amt);
	}else{
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
	}
	return $VAT;
}
?>

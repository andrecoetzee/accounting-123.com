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
	$OUTPUT = details($_GET);
//				break;
	//$OUTPUT = slct($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "update":
				$OUTPUT = write($_POST);
				break;

			case "slct":


            default:
				$OUTPUT = "<li class=err> Invalid use of module.";
			}
	} else {
		$OUTPUT = "<li class=err> Invalid use of module.";
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
	$v->isOk ($prd, "num", 1, 20, "Invalid prd number.");

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

	# get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
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

	//Removed option
	// <tr><th colspan=2> Purchase Details </th></tr>
	// <tr class='bg-odd' ".ass("Select when tranferring goods between Departments or Stores")."><td colspan=2><input type=
	//radio name=ctyp value='c' checked=yes>Accounts Purchase</td></tr>

	$details = "<center>
	<h3>Non-Stock Purchase received</h3>
	<h4>Supplier Details</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=slct>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
	<tr><td colspan=2>$err</td></tr>
 	<tr><th colspan=2> Purchase Details </th></tr>
	<tr class='bg-even' ".ass("Select when purchasing non stock goods from your suppliers")."><td><input type=radio name=ctyp value='s' checked> Select Supplier</td><td>$sups</td></tr>
	<tr class='bg-odd' ".ass("Select when the purchase of non stock goods is a cash purchase")."><td><input type=radio name=ctyp value='c'>Cash Order</td><td>$depts</td></tr>
	<tr class='bg-even'><td colspan=2><input type=radio name=ctyp value='p'>Petty Cash Purchase</td></tr>
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
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Purchase number.");
	$v->isOk ($prd, "num", 1, 20, "Invalid prd number.");
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

	# Get purchase info
	db_conn($prd);
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed

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

	# get selected stock in this purchase
	db_conn($prd);
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
		$products .="<tr class='bg-odd'><td><input type=hidden size=4 name=cod[] value='$stkd[cod]'>$stkd[cod]</td><td>$stkd[des]</td><td><input type=hidden name=qts[] value='$stkd[qty]'><input type=text size=5 name=qtys[] value='$stkd[qty]'></td><td><input type=hidden size=4 name=unitcost[] value='$stkd[unitcost]'>$stkd[unitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
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

/* -- Final Layout -- */
	$details = "<center><h3>Return Non-Stock Purchase</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<input type=hidden name=shipchrg value='0'>
	$hide
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr class='bg-odd'><td>Supplier</td><td valign=center>$pur[supplier]</td></tr>
			<tr class='bg-even'><td>Supplier Address</td><td valign=center><pre>$pur[supaddr]</pre></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Purchase Details </th></tr>
			<tr class='bg-odd'><td>Non-Stock Purchase No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr class='bg-odd'><td>VAT Inclusive</td><td valign=center>$pur[vatinc]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td class='bg-odd'><a href='nons-purchase-new.php'>New purchase</a></td><td class='bg-odd' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td></tr>
			<tr class='bg-odd'><td><a href='nons-purchase-view.php'>View purchases</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr class='bg-even'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr class='bg-odd'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $pur[vat]</td></tr>
			<tr class='bg-even'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
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

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");
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
				$v->isOk ($qty, "num", 0, 0, "Error : Quantity for product number : <b>".($keys+1)."</b> is more that Qty Purchased");
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

	# Get purchase info
	db_conn();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
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

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Insert purchase to DB
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

				/* ?: refer to :Code Insert:
				# keep records for transactions
				if(isset($totstkamt[$stkacc[$keys]])){
					$totstkamt[$stkacc[$keys]] += $amt[$keys];
				}else{
					$totstkamt[$stkacc[$keys]] = $amt[$keys];
				}
				*/

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

		# Update purchase on the DB
		if($pur['part'] == 'y'){
			# Update purchase on the DB
			$sql = "UPDATE nons_purchases SET shipchrg = (shipchrg + '$shipchrg'), refno = '$refno', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);
		}else{
			# Update purchase on the DB
			$sql = "UPDATE nons_purchases SET shipchrg = '$shipchrg', refno = '$refno', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);
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

		/* ?:refer to :Code Insert:
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Debit Stock and Credit Suppliers control
			writetrans($stkacc, $supacc, date("d-m-Y"), $refnum, $wamt, "Non-Stock Purchase No. $pur[purnum] Received $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $pur[purnum] Received $detadd.", $wamt, "Cash Purchase");
		}*/

		# Calc Vat amount on (subtot + delchrg)
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

		# get warehouse name
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		if($vatamt <> 0){
			# Debit bank and credit the account involved
			writetrans($vatacc, $supacc, date("d-m-Y"), $refnum, $vatamt, "Non-Stock Purchase Vat paid on Non-Stock Purchase No. $pur[purnum] $detadd.");
			pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $pur[purnum] Received $detadd.", $vatamt, "Cash Purchase Vat");

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

			$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, amount, descript,ref,ex,div) VALUES('$sup[supid]','$DAte', '$dept[credacc]', '$retot','Non-Stock Purchase No. $pur[purnum] Received', '$refnum', '$pur[purnum]','".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			suppledger($sup['supid'], $wh['stkacc'], $DAte, $pur['purid'], "Non-Stock Purchase No. $pur[purnum] received.", $retot, 'c');


			db_connect();

			# update the supplier age analysis (make balance less)
			/* Make transaction record for age analysis */
			$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$sup[supid]', '$pur[purnum]', '$DAte', '$retot', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	/* End Transactions */

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* Update items found in ther linked purchase */

		# Get purchase info
		db_connect();
		$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li>- purchase Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);


		db_conn($pur['spurprd']);
		$stab = ($pur['spurtype'] == "int") ? "purch_int" : "purchases";
		$itab = ($pur['spurtype'] == "int") ? "purint_items" : "pur_items";

		# Get purchase info
		$sql = "SELECT * FROM $stab WHERE purnum = '$pur[spurnum]' AND div = '".USER_DIV."'";
		$spurRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
		if (pg_numrows ($spurRslt) < 1) {
			return "<li> - purchase Not Found</li>";
		}
		$spur = pg_fetch_array($spurRslt);

		db_conn($pur['spurprd']);
		# Get selected stock
		$sql = "SELECT * FROM $itab WHERE purid = '$spur[purid]' AND div = '".USER_DIV."'";
		$sstkdRslt = db_exec($sql);
		while($sstk = pg_fetch_array($sstkdRslt)){
			if($pur['spurtype'] == "int"){
				$csamt = sprint(($sstk['amt']/$spur['subtot']) * ($retot - $vatamt));
			}else{
				if($spur['vatinc'] == "yes"){
					$csamt = sprint((($sstk['amt'] - $sstk['svat'])/$spur['subtot']) * ($retot - $vatamt));
				}else{
					$csamt = sprint((($sstk['amt'])/$spur['subtot']) * ($retot - $vatamt));
				}
			}

			db_connect();
			# get selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);
			$stkt = pg_fetch_array($stktRslt);

			/* Code insert */
				# get warehouse name
				db_conn("exten");
				$sql = "SELECT * FROM warehouses WHERE whid = '$stkt[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);

				writetrans($wh['stkacc'], $supacc, $DAte, $refnum, $csamt, "Non-Stock Purchase No. $pur[purnum] Received $detadd.");
			/* End code insert */

			db_connect();
			if($stkt['units'] <> 0){
				$sql = "UPDATE stock SET csamt = (csamt + '$csamt'), csprice = (csamt/units) WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}else{
				$sql = "UPDATE stock SET csamt = (csamt + '$csamt') WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
			}
			$sdate = $pur['pdate'];
			stockrec($stkt['stkid'], $stkt['stkcod'], $stkt['stkdes'], 'dt', $sdate, 0, $csamt, "Cost Increased with Non Stock Purchase No. $pur[purnum]");

			# Just wanted to fix the xxx.xxxxxxe-x value
			# get selected stock
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
			$stktRslt = db_exec($sql);
			$stkt = pg_fetch_array($stktRslt);

			# $csprice = round(($stk['csamt']/$stk['units']), 2);
			if($stkt['units'] > 0){
				$csprice = round(($stkt['csamt']/$stkt['units']), 2);
			}else{
				$csprice = round($stkt['csprice'], 2);
			}

			# update stock(csprice = (csamt/units))
			$sql = "UPDATE stock SET csprice = '$csprice' WHERE stkid = '$sstk[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

/* End Update items found in ther linked purchase */


/* Start moving if purchase received */

		# Get purchase info
		db_connect();
		$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
		if (pg_numrows ($purRslt) < 1) {
			return "<li>- purchase Not Found</li>";
		}
		$pur = pg_fetch_array($purRslt);

		if($pur['received'] == "y"){
			# copy purchase
			db_conn($pur['prd']);
			$sql = "INSERT INTO nons_purchases(purid, deptid, supplier, supaddr, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, remarks, refno, received, done, div, purnum)";
			$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supplier]',  '$pur[supaddr]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]')";
			$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Purchase to Cubit.",SELF);


			db_connect();
			# Get selected stock
			$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$stktcRslt = db_exec($sql);

			while($stktc = pg_fetch_array($stktcRslt)){
				# Insert purchase items
				db_conn($pur['prd']);
				$sql = "INSERT INTO nons_pur_items(purid, cod, des, qty, unitcost, amt, ddate, div) VALUES('$purid', '$stktc[cod]', '$stktc[des]', '$stktc[qty]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[ddate]', '".USER_DIV."')";
				$rslt = db_exec($sql) or errDie("Unable to insert purchase items to Cubit.",SELF);
			}

			db_connect();
			# Remove the purchase from running DB
			$sql = "DELETE FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

			# Remove those purchase items from running DB
			$sql = "DELETE FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}

/* End moving purchase received */

	$cc = "<script> CostCenter('ct', 'Non-Stock Purchase', '$DAte', 'Non Stock Purchase No.$pur[purnum]', '".($retot-$vatamt)."', ''); </script>";

	// Final Layout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Non-Stock Purchase received</th></tr>
		<tr class='bg-even'><td>Non-Stock Purchase receipt has been recorded.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='nons-purchase-view.php'>View purchases</a></td></tr>
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

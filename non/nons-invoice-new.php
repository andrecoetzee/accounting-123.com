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
	$_GET["done"] = "";
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
			$_GET["done"] = "";
			$OUTPUT = details($_GET);
		}
	} else {
		$_GET["done"] = "";
		$OUTPUT = details($_GET);
	}
}

# get templete
require("template.php");

# Starting dummy
function create_dummy($deptid){

	db_connect();
	# Insert purchase to DB
	$sql = "INSERT INTO nons_invoices(cusname, cusaddr, cusvatno, chrgvat, sdate, subtot, balance, vat,
			total, done, username, prd, invnum, div)";
	$sql .= " VALUES('', '', '', 'yes', CURRENT_DATE, 0, 0, 0, 0, 'n', '".USER_NAME."', '".PRD_DB."',
			0,'".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

	# Get next ordnum
	$invid = lastinvid();

	return $purid;
}

# details
function details($_POST, $error="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if( isset($invid) ){
		$v->isOk ($invid, "num", 1, 20, "Invalid Non-Stock Invoice number.");
	} else {
		$invid = create_dummy(0);
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invoice Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if invoice has been printed
	if($inv['done'] == "y"){
		$error = "<li class=err> Error : invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

/* --- Start Drop Downs --- */

	# format date
	list($syear, $smon, $sday) = explode("-", $inv['sdate']);

	# keep the charge vat option stable
	if($inv['chrgvat'] == "yes"){
		$chy = "checked=yes";
		$chn = "";
	}else{
		$chy = "";
		$chn = "checked=yes";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr>
		<th>DESCRIPTION</th>
		<th>QTY</th>
		<th>UNIT PRICE</th>
		<th>AMOUNT</th>
		<th>Remove</th>
	<tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;

	while ($stkd = pg_fetch_array($stkdRslt)) {
		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];

		$stkd['amt'] = round($stkd['amt'], 2);

		# put in product
		$products .="<tr class='bg-odd'>
			<td align=center><input type=text size=50 name=des[] value='$stkd[description]'></td>
			<td align=center><input type=text size=3 name=qtys[] value='$stkd[qty]'></td>
			<td align=center><input type=text size=8 name=unitcost[] value='$stkd[unitcost]'></td>
			<td><input type=hidden name=amt[] value='$stkd[amt]'> ".CUR." $stkd[amt]</td>
			<td><input type=checkbox name=remprod[] value='$i'><input type=hidden name=SCROLL value=yes></td>
		</tr>";

  		$i++;
	}

	# Look above(remprod keys)
	$keyy = $i;

	# look above(if i = 0 then there are no products)
	if( $i == 0 ) {
		$done = "";
	}

	if ( $i == 0 || isset($diffwhBtn) ) {
		# add one
		$products .= "<tr class='bg-odd'>
			<td align=center><input type=text size=50 name=des[] value=''></td>
			<td align=center><input type=text size=3 name=qtys[] value='1'></td>
			<td align=center><input type=text size=8 name=unitcost[]></td>
			<td>".CUR." 0.00</td>
			<td>&nbsp;</td>
		</tr>";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = $inv['subtot'];

	# Get Total
	$TOTAL = sprint($inv['total']);

	# Get vat
	$VAT = sprint($inv['vat']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "<center><h3>New Non-Stock Invoices</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=invid value='$invid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
 	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr class='bg-odd'><td>Supplier</td><td valign=middle><input type=text name=cusname value='$inv[cusname]'></td></tr>
			<tr class='bg-even'><td valign=top>Customer Address</td><td valign=middle><textarea name=cusaddr cols=18 rows=3>$inv[cusaddr]</textarea></td></tr>
			<tr class='bg-odd'><td valign=top>Customer VAT No.</td><td valign=middle><input type=text name=cusvatno value='$inv[cusvatno]'></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Invoice Details </th></tr>
			<tr class='bg-odd'><td>Non-Stock Invoice No.</td><td valign=center>TI $inv[invid]</td></tr>
			<tr class='bg-even'><td>Date</td><td valign=center><input type=text size=2 name=sday maxlength=2 value='$sday'>-<input type=text size=2 name=smon maxlength=2 value='$smon'>-<input type=text size=4 name=syear maxlength=4 value='$syear'> DD-MM-YYYY</td></tr>
			<tr class='bg-odd'><td>VAT Inclusive</td><td valign=center>Yes <input type=radio size=7 name=chrgvat value='yes' $chy> No<input type=radio size=7 name=chrgvat value='no' $chn></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td class='bg-odd'><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td><td class='bg-odd' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$inv[remarks]</textarea></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>".CUR." <input type=hidden name=subtot value='$SUBTOT'>$SUBTOT</td></tr>
			<tr class='bg-odd'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $inv[vat]</td></tr>
			<tr class='bg-even'><th>GRAND TOTAL</th><td align=right>".CUR." <input type=hidden name=total value='$TOTAL'>$TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input name=diffwhBtn type=submit value='Add Item'> |</td><td><input type=submit name='upBtn' value='Update'>$done</td></tr>
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
	$sdate = $syear."-".$smon."-".$sday;
	if( !checkdate($smon, $sday, $syear) ){
		$v->addError($sdate, "Invalid Date.");
	}

	# used to generate errors
	$error = "asa@";

        // check the invoice details
	$v->isOK($cusname, "string", 1, 100, "Invalid customer name");
	$v->isOK($cusaddr, "string", 1, 100, "Invalid customer address");
	$v->isOK($cusvatno, "string", 1, 50, "Invalid customer vat number");

	if ( $chrgvat != "yes" && $chrgvat != "no" )
		$v->addError($chrgvat, "Invalid vat option");

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($des[$keys], "string", 1, 255, "Invalid Description.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 10, "Invalid  Amount, please enter all details.");
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$_POST['done'] = "";
		return details($_POST, $err);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($invRslt) < 1) {
		return "<li>- invoices Not Found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if purchase has been printed
	if($inv['done'] == "y"){
		$error = "<li class=err> Error : invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# insert purchase to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
			# remove old items
		$sql = "DELETE FROM nons_inv_items WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice items in Cubit.",SELF);

		/* -- End remove old items -- */

		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod)){
					if(in_array($keys, $remprod)){
						# skip product (wonder if $keys still align)
						$amt[$keys] = 0;
						continue;
					} else {

						# Calculate amount
						$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

						# format ddate
						$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";

						# insert purchase items
						$sql = "INSERT INTO nons_inv_items(invid, qty, amt, unitcost, description, div)
							VALUES('$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]', '".USER_DIV."')";
						$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
					}
				} else {
					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

					# insert purchase items
					$sql = "INSERT INTO nons_inv_items(invid, qty, amt, unitcost, description, div)
						VALUES('$invid', '$qtys[$keys]', '$amt[$keys]', '$unitcost[$keys]', '$des[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
				}
				# everything is set place done button
				$_POST["done"] = " | <input name=doneBtn type=submit value='Done'>";
			}
		}else{
			$_POST["done"] = "";
		}

		/* --- Clac --- */
		# calculate subtot
		if( isset($amt) ){
			$TOTAL = array_sum($amt);
		}else{
			$TOTAL = 0.00;
		}

		# if vat is not included
		$VATP = TAX_VAT;
		if($chrgvat == "yes"){
			$SUBTOT = sprintf("%0.2f", $TOTAL * 100 / (100 + $VATP) );
		}else{
			$SUBTOT = $TOTAL;
		}

		// compute the sub total (total - vat), done this way because the specified price already includes vat
		$VAT = $TOTAL - $SUBTOT;

		/* --- End Clac --- */


		# insert purchase to DB
		$sql = "UPDATE nons_invoices SET
				cusname = '$cusname', cusaddr = '$cusaddr', cusvatno = '$cusvatno', chrgvat = '$chrgvat', sdate = '$sdate',
				subtot = '$SUBTOT', vat = '$VAT', total = '$TOTAL', remarks = '$remarks'
			WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if( !isset($doneBtn) ){
		return details($_POST);
	} else {
		$rslt = db_exec($sql) or errDie("Unable to update invoices status in Cubit.",SELF);

		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>New Non-Stock Invoices</th></tr>
			<tr class='bg-even'><td>Non-Stock Invoices for Customer <b>$cusname</b> has been recorded.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='nons-invoice-view.php'>View Non-Stock Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

		return $write;
	}
}
?>

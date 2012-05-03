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
	# Dummy Vars
	$remarks = "";
	$supaddr = "";
	$terms = "0";
	$total = 0;
	$subtot = 0;
	$pdate = date("Y-m-d");
	$ddate = date("Y-m-d");
	$shipchrg = "0.00";

	$purnum = divlastid ("pur", USER_DIV);

	# Insert Order to DB
	$sql = "INSERT INTO nons_purchases(deptid, supplier, supaddr, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, remarks, received, done, prd, div, purnum)";
	$sql .= " VALUES('$deptid', '',  '$supaddr', '$terms', '$pdate', '$ddate', '$shipchrg', '$subtot', '$total', '$total', 'yes', '0', '$remarks', 'n', 'n', '".PRD_DB."', '".USER_DIV."', '$purnum')";
	$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);

	# Get next ordnum
	$purid = pglib_lastid ("nons_purchases", "purid");

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
	if(isset($purid)){
		$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
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

	if(!isset($purid)){
		$purid = create_dummy(0);
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

	if(!(isset($ordernum))) {$ordernum='';}	

/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	# keep the charge vat option stable
	if($pur['vatinc'] == "yes"){
		$chy = "checked=yes";
		$chn = "";
		$chnv = "";
	} else if ($pur['vatinc'] == 'novat') {
		$chy = "";
		$chn = "";
		$chnv = "checked=yes";
	}else{
		$chy = "";
		$chn = "checked=yes";
		$chnv = "";
	}

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>DELIVERY DATE</th><th>AMOUNT</th><th>VAT</th><th>Remove</th><tr>";

	# get selected stock in this Order
	db_connect();
	$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$stkd['amt'] = round($stkd['amt'], 2);
		
		$tip = "&nbsp;&nbsp;&nbsp;";
		if(isset($vatc[$key])){
			$tip = "<font color=red>#</font>";
			$error = "<div class=err> $tip&nbsp;&nbsp;=&nbsp;&nbsp; Vat amount is different from amount calculated by cubit. To allow cubit to recalculate the vat amount, please delete the vat amount from the input box.";
		}

		# put in product
		$products .="<tr class='bg-odd'><td align=center><input type=text size=10 name=cod[] value='$stkd[cod]'></td><td align=center><input type=text size=20 name=des[] value='$stkd[des]'></td><td align=center><input type=text size=3 name=qtys[] value='$stkd[qty]'></td><td align=center><input type=text size=8 name=unitcost[] value='$stkd[unitcost]'></td><td align=center><input type=text size=2 name=dday[] maxlength=2 value='$sday'>-<input type=text size=2 name=dmon[] maxlength=2 value='$smon'>-<input type=text size=4 name=dyear[] maxlength=4 value='$syear'></td><td><input type=hidden name=amt[] value='$stkd[amt]'> ".CUR." $stkd[amt]</td><td>$tip <input type=text name=vat[] size=9 value='$stkd[svat]'></td><td><input type=checkbox name=remprod[] value='$key'><input type=hidden name=SCROLL value=yes></td></tr>";
		$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
		list($year, $mon, $day) = explode("-", date("Y-m-d"));
		# add one
		$products .= "<tr class='bg-odd'><td align=center><input type=text size=10 name=cod[] value=''></td><td align=center><input type=text size=20 name=des[] value=''></td><td align=center><input type=text size=3 name=qtys[] value='1'></td><td align=center><input type=text size=8 name=unitcost[]></td><td align=center><input type=text size=2 name=dday[] maxlength=2 value='$day'>-<input type=text size=2 name=dmon[] maxlength=2 value='$mon'>-<input type=text size=4 name=dyear[] maxlength=4 value='$year'></td><td>".CUR." 0.00</td><td><input type=hidden name=novat[] value='1'></td><td> </td></tr>";
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		list($year, $mon, $day) = explode("-", date("Y-m-d"));
		$products .= "<tr class='bg-odd'><td align=center><input type=text size=10 name=cod[] value=''></td><td align=center><input type=text size=20 name=des[] value=''></td><td align=center><input type=text size=3 name=qtys[] value='1'></td><td align=center><input type=text size=8 name=unitcost[]></td><td align=center><input type=text size=2 name=dday[] maxlength=2 value='$day'>-<input type=text size=2 name=dmon[] maxlength=2 value='$mon'>-<input type=text size=4 name=dyear[] maxlength=4 value='$year'></td><td>".CUR." 0.00</td><td><input type=hidden name=novat[$key] value='1'></td><td> </td></tr>";
		$key++;
	}

	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = $pur['subtot'];

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "<center><h3>New Non-Stock Order</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
 	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr class='bg-odd'><td>Supplier</td><td valign=center><input type=text name=supplier value='$pur[supplier]'></td></tr>
			<tr class='bg-even'><td valign=top>Supplier Address</td><td valign=center><textarea name=supaddr cols=18 rows=3>$pur[supaddr]</textarea></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Order Details </th></tr>
			<tr class='bg-odd'><td>Non-Stock Order No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr class='bg-even'><td>Order No.</td><td valign=center><input type=text size=10 name=ordernum value='$ordernum'></td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign=center>$termssel Days</td></tr>
			<tr class='bg-even'><td>Date</td><td valign=center><input type=text size=2 name=pday maxlength=2 value='$pday'>-<input type=text size=2 name=pmon maxlength=2 value='$pmon'>-<input type=text size=4 name=pyear maxlength=4 value='$pyear'> DD-MM-YYYY</td></tr>
			<tr class='bg-odd'><td>VAT Inclusive</td><td valign=center>Yes <input type=radio size=7 name=vatinc value='yes' $chy> No<input type=radio size=7 name=vatinc value='no' $chn> No VAT<input type=radio size=7 name=vatinc value='novat' $chnv></td></tr>
			<tr class='bg-even'><td>Delivery Charges</td><td valign=center><input type=text size=7 name=shipchrg value='$pur[shipchrg]'></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td class='bg-odd'><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td><td class='bg-odd' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>".CUR." <input type=hidden name=subtot value='$SUBTOT'>$SUBTOT</td></tr>
			<tr class='bg-odd'><td>Delivery Charges</td><td align=right>".CUR." $pur[shipping]</td></tr>
			<tr class='bg-odd'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $pur[vat]</td></tr>
			<tr class='bg-even'><th>GRAND TOTAL</th><td align=right>".CUR." <input type=hidden name=total value='$TOTAL'>$TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input name=diffwhBtn type=submit value='Add Item'> |</td><td><input type=submit name='upBtn' value='Update'>$done</td></tr>
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
	$v->isOk ($purid, "num", 1, 9, "Invalid Order ID");
	$v->isOk ($ordernum, "string", 0, 20, "Invalid order number.");
	$v->isOk ($supplier, "string", 1, 255, "Invalid Supplier name.");
	$v->isOk ($supaddr, "string", 0, 255, "Invalid Supplier address.");
	$v->isOk ($terms, "num", 1, 5, "Invalid terms days.");
	$v->isOk ($pday, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($pmon, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($pyear, "num", 1, 5, "Invalid Date year.");
	$v->isOk ($vatinc, "string", 1, 5, "Invalid VAT Inclusion Option.");
	$v->isOk ($shipchrg, "float", 0, 20, "Invalid Delivery Charges.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
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
			$v->isOk ($unitcost[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($des[$keys], "string", 1, 255, "Invalid Description.");
			$v->isOk ($cod[$keys], "string", 0, 255, "Invalid Item Code.");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}

			# Validate ddate[]
			$v->isOk ($dday[$keys], "num", 1, 2, "Invalid Delivery Date day.");
			$v->isOk ($dmon[$keys], "num", 1, 2, "Invalid Delivery Date month.");
			$v->isOk ($dyear[$keys], "num", 1, 5, "Invalid Delivery Date year.");
			$ddate[$keys] = $dyear[$keys]."-".$dmon[$keys]."-".$dday[$keys];
			if(!checkdate($dmon[$keys], $dday[$keys], $dyear[$keys])){
				$v->isOk ($ddate[$keys], "num", 1, 1, "Invalid Delivery Date.");
			}
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
			$err .= "<li class=err>".$e["msg"];
		}
		$_POST['done'] = "";
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

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : Order number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# fix those nasty zeros
	$shipchrg += 0;

	# insert Order to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
			# remove old items
			$sql = "DELETE FROM nons_pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		/* -- End remove old items -- */
		$VATP = TAX_VAT;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				if(isset($remprod)){
					if(in_array($keys, $remprod)){
						# skip product (wonder if $keys still align)
						$amt[$keys] = 0;
						continue;
					}else{

						# Calculate amount
						$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

						if(isset($novat[$keys]) || strlen($vat[$keys]) < 1){
							# If vat is not included
							if($vatinc == "no"){
								$vat[$keys] = sprint(($VATP/100) * $amt[$keys]);
							}elseif($vatinc == "yes"){
								$vat[$keys] = sprint(($amt[$keys]/(100 + $VATP)) * $VATP);
							}else{
								$vat[$keys] = 0;
							}
						}
						if($vatinc == "novat"){
							$vat[$keys] = 0;
						}
						
						if($vatinc != "novat"){
							# If vat is not included
							if($vatinc == "no"){
								$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
							}else{
								$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
							}
							if($vat[$keys] <> $vatc[$keys]){
								$_POST["vatc"][$keys] = "yes";
							}
						}
						
						# format ddate
						$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";

						# insert Order items
						$sql = "INSERT INTO nons_pur_items(purid, cod, des, qty, unitcost, amt, svat, ddate, div) VALUES('$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$vat[$keys]', '$ddate[$keys]', '".USER_DIV."')";
						$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
					}
				}else{
					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * $unitcost[$keys]);

					if(isset($novat[$keys]) || strlen($vat[$keys]) < 1){
						# If vat is not included
						if($vatinc == "no"){
							$vat[$keys] = sprint(($VATP/100) * $amt[$keys]);
						}elseif($vatinc == "yes"){
							$vat[$keys] = sprint(($amt[$keys]/(100 + $VATP)) * $VATP);
						}else{
							$vat[$keys] = 0;
						}
					}
					if($vatinc == "novat"){
						$vat[$keys] = 0;
					}
					
					if($vatinc != "novat"){
						# If vat is not included
						if($vatinc == "no"){
							$vatc[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
						}else{
							$vatc[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
						}
						if($vat[$keys] <> $vatc[$keys]){
							$_POST["vatc"][$keys] = "yes";
						}
					}

					# ddate
					$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";

					# insert Order items
					$sql = "INSERT INTO nons_pur_items(purid, cod, des, qty, unitcost, amt, svat, ddate, div) VALUES('$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$vat[$keys]', '$ddate[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
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

		# If vat is not included (delchrg)
		$VATP = TAX_VAT;
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

		# If there vatable items
		if(isset($vat)){
			$VAT = array_sum($vat);
		}else{
			$VAT = 0;
		}

		# Total
		$TOTAL = ($SUBTOT + $shipexvat);

		# If vat is not included
		if($vatinc == "no"){
			$TOTAL = ($TOTAL + $VAT + $svat);
		}else{
			$TOTAL = ($TOTAL + $svat);
			$SUBTOT -= ($VAT);
		}

		$VAT += $svat;

	/* --- End Clac --- */

		/* --- Clac ---
		# calculate subtot
		if(isset($amt)){
			$SUBTOT = array_sum($amt);
		}else{
			$SUBTOT = 0.00;
		}

		# If vat is not included (delchrg)
		$VATP = TAX_VAT;
		if($vatinc == "no"){
			$svat = sprint(($VATP/100) * $shipchrg);
		}else{
			$svat = sprint(($shipchrg/($VATP+100)) * $VATP);
		}

		# Total
		$TOTAL = ($SUBTOT + $shipchrg);

		# If there vatable items
		if(isset($vat)){
			$VAT = array_sum($vat);
		}else{
			$VAT = 0;
		}

		# If vat is not included
		if($vatinc == "no"){
			$TOTAL = ($TOTAL + $VAT + $svat);
		}elseif($vatinc == "novat"){
			$VAT = 0;
			$svat = 0;
		}else{
			$SUBTOT -= $VAT;
		}

		$VAT += $svat;

		/* --- End Clac --- */

		$VAT += 0;

		# insert Order to DB
		$sql = "UPDATE nons_purchases SET supplier = '$supplier', supaddr = '$supaddr', terms = '$terms', pdate = '$pdate', shipchrg = '$shipchrg', subtot = '$SUBTOT', total = '$TOTAL', balance = '$TOTAL', vatinc = '$vatinc', vat = '$VAT',ordernum='$ordernum', remarks = '$remarks', shipping = '$shipexvat' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(!isset($doneBtn)){
		return details($_POST);
	}else{
		# insert Order to DB
		$sql = "UPDATE nons_purchases SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>New Non-Stock Order</th></tr>
			<tr class='bg-even'><td>Non-Stock Order from Supplier <b>$supplier</b> has been recorded.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='nons-purchase-view.php'>View Non-Stock Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

		return $write;
	}
}
?>

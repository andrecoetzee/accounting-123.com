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
if (isset($HTTP_GET_VARS["purid"]) && isset($HTTP_GET_VARS["cont"])) {
	$HTTP_GET_VARS["done"] = "";
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
            case "search":
				$OUTPUT = search($HTTP_POST_VARS);
				break;

			case "update":
				$OUTPUT = write($HTTP_POST_VARS);
				break;

            default:
				$OUTPUT = slct();
			}
	} else {
		$OUTPUT = slct();
	}
}

# get templete
require("template.php");

# Default view
function slct($HTTP_GET_VARS = array(), $err = "")
{
	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}

	$purnum = (isset($purnum)? $purnum : "");

	//layout
	$slct = "
	<h3>New Linked Non Stock Purchase<h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=200>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=search>
		<tr><td>$err</td></tr>
		<tr><th>Stock Purchase Number To Link To</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=text size=10 name=purnum value='$purnum'></td></tr>
		<tr><td><br></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td align=center><input type=submit value='Enter'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $slct;
}

# Starting dummy
function create_dummy($deptid, $spurnum, $spurtype, $spurprd){

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

	# Insert purchase to DB
	$sql = "INSERT INTO nons_purchases(deptid, supplier, supaddr, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, remarks, received, done, prd, div, purnum, spurnum, spurtype, spurprd)";
	$sql .= " VALUES('$deptid', '',  '$supaddr', '$terms', '$pdate', '$ddate', '$shipchrg', '$subtot', '$total', '$total', 'yes', '0', '$remarks', 'n', 'n', '".PRD_DB."', '".USER_DIV."', '$purnum', '$spurnum', '$spurtype', '$spurprd')";
	$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Purchase to Cubit.",SELF);

	# Get next ordnum
	$purid = pglib_lastid ("nons_purchases", "purid");

	return $purid;
}

function search($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purnum, "string", 1, 20, "Invalid Purchase number.");

	# display errors, if any
	$error = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		return slct($HTTP_POST_VARS, $error);
	}

	$purs=explode(",",$purnum);

	foreach($purs as $pur) {
		print $pur."<br>";

	}



	# Send search squad
	db_connect ();
	$sql = "SELECT * FROM purchases WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$srchRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($srchRslt) > 0) {
		$purid = create_dummy(0, $purnum, 'loc', 'cubit');
		$send['purid'] = $purid;
		return details($send);
	}

	$sql = "SELECT * FROM purch_int WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$srchRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($srchRslt) > 0) {
		$purid = create_dummy(0, $purnum, 'int', 'cubit');
		$send['purid'] = $purid;
		return details($send);
	}

	$sql = "SELECT * FROM movpurch WHERE purnum = '$purnum' AND div = '".USER_DIV."'";
	$srchRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");
	if (pg_numrows ($srchRslt) > 0) {
		$res = pg_fetch_array($srchRslt);
		$purid = create_dummy(0, $purnum, $res['purtype'], $res['prd']);
		$send['purid'] = $purid;
		return details($send);
	}

	return slct($HTTP_POST_VARS, "<li class=err> - Purchase No. $purnum not found.");
}

# details
function details($HTTP_POST_VARS, $error="")
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	if(!(isset($ordernum))) {$ordernum='';}

/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("0"=>"0", "7"=>"7", "30"=>"30","60"=>"60","90"=>"90","120"=>"120");
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

		# put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=text size=10 name=cod[] value='$stkd[cod]'></td><td align=center><input type=text size=20 name=des[] value='$stkd[des]'></td><td align=center><input type=text size=3 name=qtys[] value='$stkd[qty]'></td><td align=center><input type=text size=8 name=unitcost[] value='$stkd[unitcost]'></td><td align=center><input type=text size=2 name=dday[] maxlength=2 value='$sday'>-<input type=text size=2 name=dmon[] maxlength=2 value='$smon'>-<input type=text size=4 name=dyear[] maxlength=4 value='$syear'></td><td><input type=hidden name=amt[] value='$stkd[amt]'> ".CUR." $stkd[amt]</td><td><input type=text name=vat[] size=9 value='$stkd[svat]'></td><td><input type=checkbox name=remprod[] value='$key'><input type=hidden name=SCROLL value=yes></td></tr>";
		$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
		list($year, $mon, $day) = explode("-", date("Y-m-d"));
		# add one
		$products .= "<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=text size=10 name=cod[] value=''></td><td align=center><input type=text size=20 name=des[] value=''></td><td align=center><input type=text size=3 name=qtys[] value='1'></td><td align=center><input type=text size=8 name=unitcost[]></td><td align=center><input type=text size=2 name=dday[] maxlength=2 value='$day'>-<input type=text size=2 name=dmon[] maxlength=2 value='$mon'>-<input type=text size=4 name=dyear[] maxlength=4 value='$year'></td><td>".CUR." 0.00</td><td><input type=hidden name=novat[] value='1'></td><td> </td></tr>";
	}

	/* -- start Listeners -- */

	if(isset($diffwhBtn)){
		list($year, $mon, $day) = explode("-", date("Y-m-d"));
		$products .= "<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><input type=text size=10 name=cod[] value=''></td><td align=center><input type=text size=20 name=des[] value=''></td><td align=center><input type=text size=3 name=qtys[] value='1'></td><td align=center><input type=text size=8 name=unitcost[]></td><td align=center><input type=text size=2 name=dday[] maxlength=2 value='$day'>-<input type=text size=2 name=dmon[] maxlength=2 value='$mon'>-<input type=text size=4 name=dyear[] maxlength=4 value='$year'></td><td>".CUR." 0.00</td><td><input type=hidden name=novat[$key] value='1'></td><td> </td></tr>";
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
	$details = "<center><h3>New Non-Stock Purchase</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<input type=hidden name=shipchrg value='0'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
 	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier</td><td valign=center><input type=text name=supplier value='$pur[supplier]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Supplier Address</td><td valign=center><textarea name=supaddr cols=18 rows=3>$pur[supaddr]</textarea></td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Non-Stock Purchase Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Linked Purchase No.</td><td valign=center>$pur[spurnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Non-Stock Purchase No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order No.</td><td valign=center><input type=text size=10 name=ordernum value='$ordernum'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Terms</td><td valign=center>$termssel Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center><input type=text size=2 name=pday maxlength=2 value='$pday'>-<input type=text size=2 name=pmon maxlength=2 value='$pmon'>-<input type=text size=4 name=pyear maxlength=4 value='$pyear'> DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive</td><td valign=center>Yes <input type=radio size=7 name=vatinc value='yes' $chy> No<input type=radio size=7 name=vatinc value='no' $chn> No VAT<input type=radio size=7 name=vatinc value='novat' $chnv></td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='nons-purchase-view.php'>View Non-Stock Purchases</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>SUBTOTAL</td><td align=right>".CUR." <input type=hidden name=subtot value='$SUBTOT'>$SUBTOT</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $pur[vat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>".CUR." <input type=hidden name=total value='$TOTAL'>$TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input name=diffwhBtn type=submit value='Add Item'> |</td><td><input type=submit name='upBtn' value='Update'>$done</td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($HTTP_POST_VARS)
{

	#get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 9, "Invalid Purchase ID");
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
		$HTTP_POST_VARS['done'] = "";
		return details($HTTP_POST_VARS, $err);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# fix those nasty zeros
	$shipchrg += 0;

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# insert purchase to DB
		db_connect();

		/* -- Start remove old items -- */
			# remove old items
			$sql = "DELETE FROM nons_pur_items WHERE purid='$purid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update purchase items in Cubit.",SELF);

		/* -- End remove old items -- */

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

					# ddate
					$ddate[$keys] = "$dyear[$keys]-$dmon[$keys]-$dday[$keys]";

					# insert Order items
					$sql = "INSERT INTO nons_pur_items(purid, cod, des, qty, unitcost, amt, svat, ddate, div) VALUES('$purid', '$cod[$keys]', '$des[$keys]', '$qtys[$keys]', '$unitcost[$keys]', '$amt[$keys]', '$vat[$keys]', '$ddate[$keys]', '".USER_DIV."')";
					$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
				}
				# everything is set place done button
				$HTTP_POST_VARS["done"] = " | <input name=doneBtn type=submit value='Done'> | <input name=print  type=submit value='Receive'>";
			}
		}else{
			$HTTP_POST_VARS["done"] = "";
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

		$VAT +=0;

		# insert purchase to DB
		$sql = "UPDATE nons_purchases SET supplier = '$supplier', supaddr = '$supaddr', terms = '$terms', pdate = '$pdate', shipchrg = '$shipchrg', subtot = '$SUBTOT', total = '$TOTAL', balance = '$TOTAL', vatinc = '$vatinc', vat = '$VAT',ordernum='$ordernum', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(isset($print)) {
		$sql = "UPDATE nons_purchases SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);

		# Print the invoice
		header("Location: lnons-purch-recv.php?purid=$purid");
		exit;

	}elseif(!isset($doneBtn)){
		return details($HTTP_POST_VARS);
	}else{
		# insert purchase to DB
		$sql = "UPDATE nons_purchases SET done = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update purchase status in Cubit.",SELF);

		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>New Non-Stock Purchase</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Non-Stock Purchase from Supplier <b>$supplier</b> has been recorded.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-purchase-view.php'>View Non-Stock Purchases</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $write;
	}
}
?>

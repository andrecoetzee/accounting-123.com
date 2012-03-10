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
if (isset($HTTP_GET_VARS["quoid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get quote info
	db_connect();
	$sql = "SELECT * FROM quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

	# Keep the charge vat option stable
	if($quo['chrgvat'] == "inc"){
		$quo['chrgvat'] = "Yes";
	}elseif($quo['chrgvat'] == "exc"){
		$quo['chrgvat'] = "No";
	}else{
		$quo['chrgvat'] = "Non Vat";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>WAREHOUSE</th>
							<th>ITEM NUMBER</th>
							<th>DESCRIPTION</th>
							<th>QTY</th>
							<th>UNIT PRICE</th>
							<th>UNIT DISCOUNT</th>
							<th>AMOUNT</th>
						<tr>";
		# get selected stock in this quote
		db_connect();
		$sql = "SELECT * FROM quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			if($stkd['account']==0) {

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
			} else {
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			$vd=pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			# put in product
			$products .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$wh[whname]</td>
								<td>$stk[stkcod]</td>
								<td>$stk[stkdes]</td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td>".CUR." $stkd[disc] &nbsp;&nbsp; OR &nbsp;&nbsp; $stkd[discp]%</td>
								<td nowrap>".CUR." $stkd[amt]</td>
							</tr>";
	}
	$products .= "</table>";

/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($quo['subtot']);

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($quo['subtot']);
 	$VAT = sprint($quo['vat']);
	$TOTAL = sprint($quo['total']);

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
					<h3>Quote Details</h3>
					<table ".TMPL_tblDflts." width='95%'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts." width='40%'>
									<tr>
										<th colspan='2'> Customer Details </th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Department</td>
										<td valign='center'>$quo[deptname]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer</td>
										<td valign='center'>$quo[cusname] $quo[surname]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td valign=top>Customer Address</td>
										<td valign='center'>".nl2br($quo['cusaddr'])."</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer Order number</td>
										<td valign='center'>$quo[cordno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Customer Vat Number</td>
										<td>$quo[cusvatno]</td>
									</tr>
									<tr>
										<th colspan='2' valign='top'>Comments</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td colspan='2' align='center'>".nl2br($quo['comm'])."</pre></td>
									</tr>
								</table>
							</td>
							<td valign='top' align='right'>
								<table ".TMPL_tblDflts.">
									<tr><th colspan='2'> Quote Details </th>
								</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Quote No.</td>
										<td valign='center'>$quo[quoid]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Order No.</td>
										<td valign='center'>$quo[ordno]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>VAT Inclusive</td>
										<td valign='center'>$quo[chrgvat]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Terms</td>
										<td valign='center'>$quo[terms] Days</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Sales Person</td>
										<td valign='center'>$quo[salespn]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Quote Date</td>
										<td valign='center'>$quo[odate]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Trade Discount</td>
										<td valign='center'>$quo[traddisc]%</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charge</td>
										<td valign='center'>$quo[delchrg]</td>
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
								<table ".TMPL_tblDflts.">
									<p>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='quote-new.php'>New Quote</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='quote-view.php'>View Quotes</a></td>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
								</table>
							</td>
							<td align='right'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr bgcolor='".bgcolorg()."'>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>".CUR." $SUBTOT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Trade Discount</td>
										<td align='right' nowrap>".CUR." $quo[discount]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td>Delivery Charge</td>
										<td align='right' nowrap>".CUR." $quo[delivery]</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><b>VAT $vat14</b></td>
										<td align='right' nowrap>".CUR." $VAT</td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<th>GRAND TOTAL</th>
										<td align='right' nowrap>".CUR." $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
					</center>";
	return $details;

}


?>
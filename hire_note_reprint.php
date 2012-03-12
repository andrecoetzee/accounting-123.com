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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

// Required for the pdf_lstr function
require ("../pdf-settings.php");

# decide what to do
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");



# details
function details($_GET)
{

	$showvat = TRUE;

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	updateTotals($invid);

	# Get invoice info
	$sql = "SELECT *, extract('epoch' from timestamp) AS e_time
				FROM hire.reprint_invoices
				WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found[1]</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found[2]</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	$sql = "SELECT asset_id FROM hire.reprint_invitems WHERE invid = '$inv[invid]'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has no items.</li>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	$sql = "SELECT * FROM hire.reprint_invitems  WHERE invid = '$invid'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# get selected stock in this warehouse
		db_connect();
		$sql = "SELECT * FROM assets WHERE id = '$stkd[asset_id]'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";

		# keep track of discounts
		$disc += $stkd['disc'];

		if($stkd['account']!=0) {
			$stk['stkcod']=$stkd['description'];
			$stk['stkdes']="";
		}

		$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		# put in product
		$stkd['unitcost']=$stkd['unitcost']-$stkd['disc'];

		$return = returnDate($stkd["item_id"]);
		$hired = hiredDate($stkd["item_id"]);

		$products .= "
				<tr valign=top>
					<td>".getSerial($stk["id"], 1)." $stk[des] ($stkd[collection])</td>
					<td>$stkd[qty]</td>
					<td>$hired</td>
					<td>$return</td>
					<td align=right>".sprint($stkd["amt"])."</td>
				</tr>";


		$client_collect = 0;
		$collect = 0;
		$deliver = 0;

		if (preg_match("/(Client Collect|collect)/", $stkd["collection"])) {
			$client_collect = 1;
		}
		if (preg_match ("/(^Collect|, Collect)/", $stkd["collection"])) {
			$collect = 1;
		}
		if (preg_match ("/Deliver/", $stkd["collection"])) {
			$deliver = 1;
		}
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	$traddiscm = $inv["discount"];
// 	if(strlen($inv['traddisc']) > 0){
// 		$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
// 	}else{
// 		$traddiscm = "0.00";
// 	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	# Update number of prints
// 	$inv['prints']++;
// 	db_conn($prd);
// 	$Sql = "UPDATE hire.reprint_invitems SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
// 	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	if(strlen($inv['comm'])>0){
		$Com = "
				<table>
					<tr>
						<td>".nl2br($inv['comm'])."</td>
					</tr>
				</table>";
	} else {
		$Com="";
	}


	$time = date("G:i:s", $inv["e_time"]);

	if(isset($cccc)) {
		$cc = "<script> sCostCenter('dt', 'Sales', '$date', 'Invoice No.$inv[invnum] for Customer $inv[cusname] $inv[surname]', '".($TOTAL-$VAT)."', 'Cost Of Sales for Invoice No.$inv[invnum]', '$tcosamt', ''); </script>";
	} else {
		$cc="";
	}
	 db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='PSALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sp="<tr><td>Sales Person</td><td>$inv[salespn]</td></tr>";
	} else {
		$sp="";
	}

	$Sl="SELECT * FROM pc WHERE inv='$inv[invnum]'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		//$pc="<tr><td>Change</td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
		$pc = "";

		$change=$pd['amount'];
	} else {
		$pc="";
		$change=0;
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cash'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pd['amount']=sprint($pd['amount']+$change);

		$pcash="<!--<tr><td>Paid Cash</td><td align=right><b>".CUR." $pd[amount]</b></td></tr>-->";
	} else {
		$pcash="";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Cheque'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pcheque="<tr><td>Paid Cheque</td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcheque="";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit Card'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pcc="<tr><td>Paid Credit Card</td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcc="";
	}

	$Sl="SELECT * FROM payrec WHERE inv='$inv[invnum]' AND method='Credit'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		$pd=pg_fetch_array($Ri);

		$pcc.="<tr><td>On Credit</td><td align=right><b>".CUR." $pd[amount]</b></td></tr>";
	} else {
		$pcc.="";
	}


// 	$Sl="SELECT * FROM varrec WHERE inv='$inv[invnum]'";
// 	$Ri=db_exec($Sl);
//
// 	if(pg_num_rows($Ri)>0) {
// 		$rd=pg_fetch_array($Ri);
//
// 		$rounding="<tr><td>Rounding</td><td align=right>".CUR." $rd[amount]</td></tr>";
// 	} else {
// 		$rounding="";
// 	}
//
	if($inv['rounding']>0) {
		$due=sprint($inv['total']-$inv['rounding']);
		$rounding = "
				<tr>
					<td>Rounding</td>
					<td align='right'>".CUR." $inv[rounding]</td>
				</tr>
				<tr>
					<td>Amount Due</td>
					<td align='right'>".CUR." $due</td>
				</tr>";
	} else {
		$rounding="";
	}


	$cusinfo = "";
	if($inv['cusnum']>0) {
		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");
		$cd=pg_fetch_array($Ri);

		$inv['cusname'] = $cd['surname']." (VAT No. $cd[vatnum])";
		$cusinfo .= "<p>".hireAddress($inv["invid"])."</p>";
		$cusinfo .= "Tel: $cd[bustel]<br />";
		$cusinfo .= "Order No: $inv[cordno]";
	}else {
		if(strlen($inv['vatnum']) > 1){
			$inv['cusname'] = "$inv[cusname] (VAT No. $inv[vatnum])<br />";
			$cusinfo .= "Order No: $inv[cordno]";
		}
	}

	db_conn('cubit');

	$Sl="SELECT img2 FROM compinfo";
	$Ri=db_exec($Sl);

	$id=pg_fetch_array($Ri);

	if(strlen($id['img2'])>0) {
		$logo="<tr>
			<td valign='top' width='100%' align=center><img src='compinfo/getimg2.php' width='230' height='47'></td>
		</tr>";
	} else {
		$logo="";
	}

	$sql = "SELECT value FROM cubit.settings WHERE constant='CONTRACT_TEXT'";
	$contract_rslt = db_exec($sql) or errDie("Unable to retrieve contract.");
	$contract_text = nl2br(base64_decode(pg_fetch_result($contract_rslt, 0)));

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$sql = "SELECT text FROM hire.thanks_text";
	$thanks_rslt = db_exec($sql) or errDie("Unable to retrieve thank you text.");
	$thank_you = pg_fetch_result($thanks_rslt, 0);

	$details = "<center>
	<style>
		h2 {
			font-size: 2em;
			padding: 0;
			margin: 0;
		}
		th {
			font-weight: bold;
			text-align: left;
		}
		.print_input {
			font-family: monospace;
			font-weight: bold;
		}
	</style>
	<table cellpadding='0' cellspacing='1' border=1 width='97%'>
	<tr><td>
		<table cellpadding='5' cellspacing='0' width='100%'>
			<tr>
				<td><img src='../compinfo/getimg.php' width='230' height='47' /></td>
				<td align='right'><h2>HIRE NOTE</h2></td>
			</tr>
		</table>
	</tr></td>
	<tr><td>
		<table border='1' width='100%'>
		<tr>
			<td valign=top width='50%'>
				".COMP_NAME."<br />
				".COMP_ADDRESS."<br />
				TEL: ".COMP_TEL."<br />
				FAX: ".COMP_FAX."<br />
				Registration Number: ".COMP_REGNO."<br />
				VAT Registration Number: ".COMP_VATNO."<br />
			</td>
			<td valign='top' width='50%'>
				$inv[cusname]<br />
				$cusinfo
			</td>
		</tr>
		</table>
	</td></tr>
	<tr><td>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td align='left' width='25%'>
					Note: H$inv[invnum]". rrev($inv["invid"])."
				</td>
				<td align='center' width='25%'>CASHIER: $inv[username]</td>
				<td width='25%' align='center'>Time: $time</td>
				<td width='25%' align='right'>$inv[odate]</td>
			</tr>
		</table>
	</td></tr>
	<tr><td>
	<table cellpadding='5' cellspacing='0' border='0' width='100%' bordercolor='#000000'>
		<tr>
			<th>CODE</th>
			<th>QTY</th>
			<th>HIRE DATE</th>
			<th>RETURN</th>
			<th style='text-align: right'>TOTAL</th>
		<tr>
		$products
	</table>
	</td></tr>
	<tr>
		<td valign='top'>
		<table width='100%'>
		<tr><td valign='top'>
		<table cellpadding='2' cellspacing='0' width='80%'>
			<tr><td colspan='2'>$thank_you</td></tr>
			<tr><td>$Com</td></tr>
			<tr>
				<td>
				<font style='font-size: 0.75em'>".nl2br($inv["custom_txt"])."<br />
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
		</td>
		<td align=right valign='top'>
		<table cellpadding='2' cellspacing='0' width='50%'>
			<tr>
				<td>Delivery Charge</td>
				<td align='right' nowrap>".CUR." $inv[delchrg]</td>
			</tr>
			<tr>
				<td>Trade Discount</td>
				<td align='right' nowrap>".CUR." $traddiscm</td>
			</tr>
			<tr>
				<td>SUBTOTAL</td>
				<td align='right' nowrap>".CUR." $SUBTOT</td>
			</tr>
			<tr>
				<td>VAT $vat14</td>
				<td align='right' nowrap>".CUR." $VAT</td>
			</tr>
			<tr>
				<td nowrap>GRAND TOTAL</td>
				<td align='right' nowrap><b>".CUR." $TOTAL</b></td>
			</tr>
			$sp
		</table>
	</td></tr>
		<table cellpadding='2' cellspacing='0' width='100%' border='1'>
			<tr><td colspan='2'>
			<div style='font-size: .80em;'>".stripslashes($contract_text)."</div></div>
			</td></tr>
			<tr><td colspan='2'>&nbsp;</td></tr>
			<tr>
				<td>Full Names (Print) ____________________________</td>
				<td>Identity Number ____________________________</td>
			</tr>
			<tr><td colspan='2'>&nbsp;</td></tr>
			<tr>
				<td>Signature - Customer ____________________________</td>
				<td>Signature - Authorized Agent ____________________________</td>
			</tr>
		</table>
	</td></tr>
	</td></tr>
	</table>
	</table>
	</center>";

	$OUTPUT = $details;

	require("../tmpl-print.php");
}

function cash_receipt()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM hire.reprint_invoices WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve note.");
	$inv = pg_fetch_array($inv_rslt);

	// Retrieve customer account
	$sql = "SELECT accid FROM core.accounts WHERE topacc='6400' AND accnum='000'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$cust_acc = pg_fetch_array($acc_rslt);

	// Retrieve company details
	$sql = "SELECT * FROM cubit.compinfo WHERE compname='".COMP_NAME."'";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company details.");
	$comp_data = pg_fetch_array($comp_rslt);

	$OUTPUT = "<table ".TMPL_tblDflts." style='border: 1px solid #000'>
		<tr>
			<td align='center'>
				<b>CASH RECEIPT</b>
			</td>
		</tr>
		<tr>
			<td align='center'><b>$comp_data[compname]</b></td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr1]</td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr2]</td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr3]</td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr4]</td>
		</tr>
		<tr>
			<td align='center'>Tel: $comp_data[tel]</td>
		</tr>
		<tr>
			<td style='border-top: 1px solid #000'>Hire No: H$inv[invnum]".rrev($inv["invid"])."</td>
		</tr>
		<tr>
			<td>Order No.$inv[ordno]</td>
		</tr>
		<tr>
			<td>Hire Date. $inv[odate]</td>
		</tr>
		<tr>
			<td style='border-top: 1px solid #000'
				>Cash Amount Received<br /> From $cust_data[cusname] $cust_data[surname]: ".CUR."$inv[deposit_amt]</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>By: $inv[username]</td>
		</tr>
		<tr>
			<td><br /><br /></td>
		</tr>
	</table>";

	require ("../tmpl-print.php");
}

?>

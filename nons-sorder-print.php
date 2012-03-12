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

	# get vars
	extract ($_GET);

	$showvat = TRUE;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

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

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# format date
	$inv['odate'] = explode("-", $inv['odate']);
	$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];


	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

        # Put in product
	while($stk = pg_fetch_array($stkdRslt)){


		if($stk['vatex'] == 'y'){
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri=db_exec($Sl);

		$vd=pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$excluding="y";
		} else {
			$excluding="";
		}

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$products .= "
						<tr valign='top'>
							<td>$ex $stk[description]</td>
							<td>$stk[qty]</td>
							<td nowrap>".CUR." $stk[unitcost]</td>
							<td nowrap>".CUR." $stk[amt]</td>
						</tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/

	/* --- Updates ---- */
	db_connect();

	# Get selected stock in this invoice
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	/* -- Format the remarks boxlet -- */
	$inv["remarks"] = "
						<table border='1'>
							<tr>
								<td>Remarks:<br>$inv[remarks]</td>
							</tr>
						</table>";

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
					<h2>Sales Order</h2>
					<table cellpadding='0' cellspacing='4' border=0 width='750'>
						<tr>
							<td valign='top' width='30%'>
								<table ".TMPL_tblDflts.">
									<tr>
										<td>$inv[cusname]</td>
									</tr>
									<tr>
										<td>".nl2br($inv['cusaddr'])."</td>
									</tr>
									<tr>
										<td>(Vat No. $inv[cusvatno])</td>
									</tr>
									<tr>
										<td>Order No.  $inv[cusordno]</td>
									</tr>
								</table>
							</td>
							<td valign='top' width='30%'>
								".COMP_NAME."<br>
								".COMP_ADDRESS."<br>
								".COMP_TEL."<br>
								".COMP_FAX."<br>
								Reg No. ".COMP_REGNO."<br>
				                VAT No. ".COMP_VATNO."<br>
							</td>
							<td width='20%'><img src='compinfo/getimg.php' width='230' height='47'></td>
							<td valign='bottom' align='right' width='20%'>
								<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
									<tr>
										<td><b>Sales Order No.</b></td>
										<td valign='center'>$inv[invid]</td>
									</tr>
									<tr>
										<td><b>Sales Order Date</b></td>
										<td valign='center'>$inv[odate]</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='4'>
								<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
									<tr>
										<th width='65%'>DESCRIPTION</th>
										<th width='10%'>QTY</th>
										<th width='10%'>UNIT PRICE</th>
										<th width='10%'>AMOUNT</th>
									<tr>
									$products
								</table>
							</td>
						</tr>
						<tr>
							<td>$inv[remarks]</td>
							<td align='right' colspan='3'>
								<table cellpadding='5' cellspacing='0' border='1' width=50% bordercolor='#000000'>
									<tr>
										<th><b>SUBTOTAL</b></th>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
									<tr>
										<th><b>VAT $vat14</b></th>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr>
										<th><b>GRAND TOTAL<b></th>
										<td align='right'>".CUR." $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td>
								<table cellpadding='2' cellspacing='0' border=1>
									<tr>
										<td colspan='2'>VAT Exempt indicator = #</td>
									</tr>
								</table>
							</td>
							<td><br></td>
						</tr>
					</table>
					</center>";
	$OUTPUT = $details;
	require("tmpl-print.php");

}


?>
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
if (isset($HTTP_GET_VARS["purid"])) {
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
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");

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




	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
					<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
						<tr>
							<td>ITEM NUMBER</td>
							<td>DESCRIPTION</td>
							<td>QTY OUTSTANDING</td>
							<td>UNIT PRICE</td>
							<td>DELIVERY DATE</td>
							<td>AMOUNT</td>
						<tr>";
		# get selected stock in this purchase
		db_connect();
		$sql = "SELECT * FROM nons_pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){

			# format date
			list($dyear, $dmon, $dday) = explode("-", $stkd['ddate']);

			db_conn('cubit');
			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);
	
			$vd=pg_fetch_array($Ri);
	
			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			# put in product
			$products .= "
							<tr>
								<td>$stkd[cod]</td>
								<td>$stkd[des]</td>
								<td>$stkd[qty]</td>
								<td nowrap>".CUR." $stkd[unitcost]</td>
								<td>$dday-$dmon-$dyear</td>
								<td nowrap>".CUR." $stkd[amt]</td>
							</tr>";
	}
	$products .= "</table>";

	if($pur['ctyp']=="s") {
		db_connect ();
		#get supplier accno
		$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supplier]' AND div = '".USER_DIV."'";
		$purRslt = db_exec($sql);
		if(pg_numrows($purRslt) < 1){
			$pur['supplier'] = "";
			$pur['supno'] = "";
		}else{
			$suparr = pg_fetch_array($purRslt);
			$pur['supplier'] = $suparr['supname'];
			$pur['supno'] = $suparr['supno'];
			$pur['supaddr'] = $suparr['supaddr'];
		}
	}

	if(!isset($pur['supno']))
		$pur['supno'] = "";

 	/* --- Start Some calculations --- */

	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

	/* --- End Some calculations --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	// format the vat inclusive variable for nicer display
	if ( $pur['vatinc'] == "novat")
		$pur['vatinc'] = "No Vat";

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
					<h3>Order Details</h3>
					<table cellpadding='0 cellspacing='4' width=750 border=0>
						<tr>
							<td valign='top'>
								<table cellpadding='2' cellspacing='0' border=1>
									<tr>
										<td colspan=2> Supplier Details </td>
									</tr>
									<tr>
										<td>Supplier</td>
										<td valign='center'>$pur[supplier]</td>
									</tr>
									<tr>
										<td>Account number</td>
										<td valign='center'>$pur[supno]</td>
									</tr>
									<tr>
										<td valign='top'>Supplier Address</td>
										<td valign='center'>".nl2br($pur['supaddr'])."</td>
									</tr>
								</table>
							</td>
							<td valign='top' width='30%'>
								".COMP_NAME."<br>
								".COMP_ADDRESS."<br>
								".COMP_PADDR."<br>
								".COMP_TEL."<br>
								".COMP_FAX."<br>
								Reg No. ".COMP_REGNO."<br>
								VAT No. ".COMP_VATNO."<br>
							</td>
							<td valign='top' align='right'>
								<table cellpadding='2' cellspacing='0' border=1>
									<tr>
										<td colspan='2'> Non-Stock Purchase Details </td>
									</tr>
									<tr>
										<td>Linked Purchase No.</td>
										<td valign='center'>$pur[spurnum]</td>
									</tr>
									<tr>
										<td>Non-Stock Purchase No.</td>
										<td valign='center'>$pur[purnum]</td>
									</tr>
									<tr>
										<td>Order No.</td>
										<td valign='center'>$pur[ordernum]</td>
									</tr>
									<tr>
										<td valign='top'>Supplier Invoice No</td>
										<td valign='center'>$pur[supinv]</td>
									</tr>
									<tr>
										<td>Delivery Ref No.</td>
										<td valign='center'>$pur[refno]</td>
									</tr>
									<tr>
										<td>Terms</td>
										<td valign='center'>$pur[terms] Days</td>
									</tr>
									<tr>
										<td>Date</td>
										<td valign='center'>$pday-$pmon-$pyear</td>
									</tr>
									<tr>
										<td>VAT Inclusive</td>
										<td valign='center'>$pur[vatinc]</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='3'>$products</td>
						</tr>
						<tr>
							<td colspan='2'>
				                <table cellpadding='2' cellspacing='0' border=1>
				                        <tr>
				                        	<td>$pur[remarks]</td>
				                        </tr>
				                </table>
							</td>
							<td align='right'>
								<table cellpadding='2' cellspacing='0' border=1 width=80%>
									<tr>
										<td>SUBTOTAL</td>
										<td align='right' nowrap>".CUR." $pur[subtot]</td>
									</tr>
									<tr>
										<td>VAT $vat14</td>
										<td align='right' nowrap>".CUR." $pur[vat]</td>
									</tr>
									<tr>
										<td>GRAND TOTAL</td>
										<td align='right' nowrap>".CUR." $pur[total]</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
					</center>";
	$OUTPUT = $details;

	require("tmpl-print.php");

}


?>
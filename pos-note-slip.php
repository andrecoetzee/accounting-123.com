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
if (isset($_GET["noteid"])) {
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

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($noteid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk ($prd, "num", 1, 2, "Invalid prd.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}




	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM inv_notes WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$noteRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($noteRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$note = pg_fetch_array($noteRslt);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$note[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	db_conn($prd);
	# get selected stock in this invoice
	$sql = "SELECT * FROM inv_note_items  WHERE noteid = '$noteid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		if(strlen($stkd['description'])<1) {

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

			$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
			$tcosamt += $cosamt;

			$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";
			# Check Tax Excempt
			if($stk['exvat'] == 'yes'){
				$ex = "#";
			}else{
				$ex = "&nbsp;&nbsp;";
			}
		} else {
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
				$stk['selamt']= sprint($stkd['amt']/$stkd['qty']);
			}

		$stkd['unitcost'] = sprint($stkd['amt']/$stkd['qty']);

		# put in product

		$products .="
		<tr valign=top>
			<td><font size='1'>&nbsp;&nbsp;$stk[stkcod]</font></td>
			<td><font size='1'>$stkd[unitcost]</font></td>
			<td><font size='1'>$stkd[qty]</font></td>
			<td align='right'><font size='1'>$stkd[amt]</font></td>
		</tr>
		<tr>
			<td colspan='4'><font size='1'>$stk[stkdes]</font></td>
		</tr>";
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($note['subtot']);

	# Calculate tradediscm
	if(strlen($note['traddisc']) > 0){
		$traddiscm = sprint((($note['traddisc']/100) * $SUBTOT));
	}else{
		$traddiscm = "0.00";
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($note['subtot']);
 	$VAT = sprint($note['vat']);
	$TOTAL = sprint($note['total']);
	$note['delchrg'] = sprint($note['delchrg']);

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	if(strlen($note['comm'])>0){
		$Com="<table><tr><td>".nl2br($note['comm'])."</td></tr></table>";
	} else {$Com="";}


	$time = date("H:i");

	if(isset($cccc)) {
		$cc = "<script> nCostCenter('ct', 'Credit Note', '$note[odate]', 'Credit Note No.$note[notenum] for Customer $note[cusname] $note[surname]', '".($note['total']-$note['vat'])."', 'Credit Note No.$note[notenum]', '$tcosamt', ''); </script>";
	} else {
		$cc="";
	}





	db_conn('cubit');

	$Sl="SELECT * FROM pcnc WHERE note='$note[notenum]'";
	$Ri=db_exec($Sl);

	$data=pg_fetch_array($Ri);

	$round=$data['amount'];

	$round+=0;

	$round = sprint ($round);

	if($round>0) {
		$due=sprint($TOTAL-$round);
		$rounding = "
						<tr>
							<td>Rounding</td>
							<td align='right'>".CUR." $round</td>
						</tr>
						<tr>
							<td>Amount</td>
							<td align='right'>".CUR." $due</td>
						</tr>";
	} else {
		$rounding="";
	}

	$cusinfo = "";
	if($note['cusnum']>0) {
		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$note[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$cd=pg_fetch_array($Ri);

		$note['cusname'] = $cd['surname']." (VAT No. $cd[vatnum])<br>";
		$cusinfo .= "Tel: $note[telno]<br>";
		$cusinfo .= "Order No: $note[cordno]";
	}else {
		if(!isset($note['vatnum']))
			$note['vatnum'] = "";

		if(strlen($note['vatnum']) > 1){
			$note['cusname'] = "$note[cusname] (VAT No. $note[vatnum])<br>";
			$cusinfo .= "Order No: $note[cordno]";
		}
	}

	db_conn('cubit');

	$Sl="SELECT img2 FROM compinfo";
	$Ri=db_exec($Sl);

	$id=pg_fetch_array($Ri);

	if(strlen($id['img2'])>0) {
		$logo = "
					<tr>
						<td valign=top width='100%' align=center><img src='compinfo/getimg2.php' width='230' height='47'></td>
					</tr>
				";
	} else {
		$logo = "";
	}
	
	if (($posmsg = nl2br(getCSetting("POSMSG"))) === false) {
		$posmsg = "THANK YOU FOR YOUR PURCHASE";
	}
	
	$nb_top = "border-top: none;";
	$nb_left = "border-left: none;";
	$nb_right = "border-right: none;";
	$nb_bot = "border-bottom: none;";

	$details = "
					$cc
					<table cellpadding='0' cellspacing='1' border='0' width='220'>
						<tr>
							<td><hr style='border: 1px solid black; $nb_bot'></td>
						</tr>
						<tr>
							<td align='center'><font size='1'>CREDIT NOTE</font></td>
						</tr>
						<tr>
							<td><hr style='border: 1px solid black; $nb_top'></td>
						</tr>
						$logo
						<tr>
							<td valign='top' width='100%'>
								<font size='1'>".COMP_NAME."</font><br>
								<font size='1'>".COMP_ADDRESS."</font><br>
								<br>
								<font size='1'>TEL: ".COMP_TEL."</font><br>
								<font size='1'>FAX: ".COMP_FAX."</font><br>
								<br>
								<font size='1'>Registration Number: ".COMP_REGNO."</font><br>
								<font size='1'>VAT Registration Number: ".COMP_VATNO."</font><br>
							</td>
						</tr>
						<tr>
							<td><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td>
						</tr>
						<tr>
							<td>
								<table ".TMPL_tblDflts." width='100%'>
									<tr>
										<td align='center'><font size='1'>$note[cusname]</font></td>
									</tr>
									<tr>
										<td align='left'><font size='1'>$cusinfo</font></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td>
								<table ".TMPL_tblDflts." width='100%'>
									<tr>
										<td align='left' width='33.33%'><font size='1'>Inv: $note[invnum]</font></td>
										<td width='33.33%'><font size='1'>$time</font></td>
										<td width='33.33%' align='right'><font size='1'>$note[odate]</font></td>
									</tr>
									<tr>
										<td align='left' colspan='3'><font size='1'>Credit Note: $note[notenum]</font></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td>
						</tr>
						<tr>
							<td>
								<table cellpadding='4' cellspacing='0' border=0 width='100%' bordercolor='#000000'>
									<tr>
										<td><font size='1'>CODE</font></td>
										<td><font size='1'>UNIT PRICE</font></td>
										<td><font size='1'>QTY</font></td>
										<td><font size='1'>TOTAL</font></td>
									<tr>
									$products
								</table>
							</td>
						</tr>
						<tr>
							<td align='right'>
								<table cellpadding='2' cellspacing='0' border=0 width='100%' bordercolor='#000000'>
									<tr>
										<td colspan='2'><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td>
									</tr>
									<tr>
										<td><font size='1'>SUBTOTAL</font></td>
										<td align='right'><font size='1'>".CUR." $SUBTOT</font></td>
									</tr>
									<tr>
										<td><font size='1'>Trade Discount</font></td>
										<td align=right><font size='1'>".CUR." $note[traddisc]</font></td>
									</tr>
									<tr>
										<td><font size='1'>Delivery Charge</font></td>
										<td align=right><font size='1'>".CUR." $note[delchrg]</font></td>
									</tr>
									<tr>
										<td><font size='1'>VAT @ $VATP%</font></td>
										<td align=right><font size='1'>".CUR." $VAT</font></td>
									</tr>
									<tr>
										<td><font size='1'>GRAND TOTAL</font></td>
										<td align='right'><font size='1'><b>".CUR." $TOTAL</b></font></td>
									</tr>
									$rounding
									<tr>
										<tr><td colspan='2'><hr style='border: 1px solid black; $nb_bot $nb_left $nb_right'></td></tr>
									</tr>
									<tr>
										<tr><td colspan='2'><hr style='border: 1px solid black; $nb_bot'></td></tr>
									</tr>
									<tr>
										<td colspan='2' align='center'>
											<table cellpadding='2' cellspacing='0'>
												<tr>
													<td width='50%' align='right'><font size='1'>CASHIER:</font></td>
													<td width='50%'><font size='1'>".USER_NAME."</font></td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<tr><td colspan='2'><hr style='border: 1px solid black; $nb_top'></td></tr>
									</tr>
									<tr>
										<td colspan='2'><font size='1'>$posmsg</font></td>
									</tr>
									<tr>
										<td colspan='2'><font size='1'>$Com</font></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>";
	$OUTPUT = $details;
	require("tmpl-print.php");

}



?>
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
if (isset($_GET["calloutid"])) {
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
	$v->isOk ($calloutid, "num", 1, 20, "Invalid Call Out number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get callout info
	db_connect();
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$calloutRslt = db_exec ($sql) or errDie ("Unable to get call out information");
	if (pg_numrows ($calloutRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$callout = pg_fetch_array($calloutRslt);

	# format date
	$callout['odate'] = explode("-", $callout['odate']);
	$callout['odate'] = $callout['odate'][2]."-".$callout['odate'][1]."-".$callout['odate'][0];


	/* --- Start some checks --- */

	# Check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT stkid FROM callout_docs_items WHERE calloutid = '$callout[calloutid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Call Out number <b>$calloutid</b> has no items.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this callout
	db_connect();
	$sql = "SELECT * FROM callout_docs_items  WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){

		if($stkd['account']==0) {

			# Get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);
		} else {
			$wh['whname']="";
			$stk['stkcod']="";
			$stk['stkdes']=$stkd['description'];
		}

		# Keep track of discounts
		$disc += $stkd['disc'];

		if (strlen($stkd['qty']) < 1)
			$stkd['qty'] = "____";

		if (strlen($stkd['unitcost']) < 1){
			$stkd['unitcost'] = "_________";
		}else {
			$stkd['unitcost'] = sprint($stkd['unitcost']);
		}

		# Put in product
		$products .= "
				<tr valign='top'>
					<td>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td>$stkd[qty]</td>
					<td>$stkd[unitcost]</td>
				</tr>";
	}

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	/* -- Final Layout -- */
	$details = "
		<center>
			<table cellpadding='0' cellspacing='4' border=0 width=770>
				<tr>
					<td valign=top width=30%>
						".COMP_NAME."<br>
						Reg No. ".COMP_REGNO."<br>
						Vat No. ".COMP_VATNO."<br>
						".COMP_ADDRESS."<br>
						".COMP_TEL."<br>
						".COMP_FAX."<br>
					</td>
				</tr>
				<tr><td colspan='3' width='100%'><hr></td></tr>
				<tr><td colspan='3' align='center'><font size='4'><b>Job Number : $callout[calloutid]</b></font></td></tr>
				<tr><td colspan='3' width='100%'><hr></td></tr>
				<tr>
					<td valign='top' width='30%'>
						<table ".TMPL_tblDflts.">
							<tr><td>$callout[surname]</td></tr>
							<tr><td>".nl2br($callout['cusaddr'])."</td></tr>
							<tr><td>(Vat No. $callout[cusvatno])</td></tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign='top' align='center' width='100%'>
						<table ".TMPL_tblDflts.">
							<tr>
								<td width='30%'>Call Out Rate: ".CUR." $callout[def_travel]</td>
								<td width='30%'>Labour Rate: ".CUR." $callout[def_labour]</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td colspan='3' width='100%'><hr></td></tr>
				<tr><td>Serviced By : $callout[calloutp]</td></tr>
				<tr><td colspan='2'>Time arrived at <b>$callout[surname]: _________________</b></td>
				<tr><td>&nbsp;</td></tr>
				<tr><td colspan='2'>Time Completed: _________________</td></tr>
				<tr><td colspan='3'><hr></td></tr>
				<tr><td><br></td></tr>
				<tr><td><br></td></tr>
				<tr><td><b><font size='3'>Stock Used:</font></b></td></tr>
				<tr>
					<td colspan=4>
						<table cellpadding='5' cellspacing='0' border='0' width=100% bordercolor='#000000'>
							<tr><td><b>ITEM NUMBER</b></td><td width=45%><b>Description of stock used</b></td><td><b>QTY</b></td><td><b>Price</b></td><tr>
							<tr><td colspan='4'><hr></td></tr>
							$products
						</table>
					</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td>Total Distance (If Relevant): _____________</td>
					<td>Travel Price : __________________</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td>Total amount or units of labour: __________</td>
					<td>Labour Price : __________________</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><b>Estimated</b> total bill for this job: ___________</td>
				</tr>
				".TBL_BR."
				<tr>
					<td colspan='2'><b>Description of Callout:</b></td>
				</tr>
				<tr>
					<td colspan='2'>$callout[calloutdescrip]</td>
				</tr>
				".TBL_BR."
				<tr>
					<td colspan='2'><b>Comments</b></td>
				</tr>
				<tr>
					<td colspan='2'>$callout[comm]</td>
				</tr>
				".TBL_BR."
				<tr>
					<td colspan='2'>$callout[sign]</td>
				</tr>
				".TBL_BR."
				".TBL_BR."
				".TBL_BR."
				<tr>
					<td>____________________________</td>
				</tr>
				<tr>
					<td>Customer's Signature :</td>
				</tr>
				<tr>
					<td>Date: ____ / ________ / 20___</td>
				</tr>
			</table>
		</center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}
?>

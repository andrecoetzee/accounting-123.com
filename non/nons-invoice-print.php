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
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("template.php");

# details
function details($HTTP_GET_VARS)
{

	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

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

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)

	/* --- End some checks --- */

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
		$products .="<tr valign=top><td>$stk[description]</td><td>$stk[qty]</td><td>$stk[unitcost]</td><td>".CUR." $stk[amt]</td></tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint("%0.2f",$inv['subtot']);
	$VAT = sprint("%0.2f",$inv['vat']);
	$TOTAL = sprint("%0.2f",$inv['total']);

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

	# Begin updates
 	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$real_invid = divlastid('inv', USER_DIV);
		$sql = "UPDATE nons_invoices SET done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# get selected stock in this invoice
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	/* -- Format the remarks boxlet -- */
	$inv["remarks"] = "<table border=1><tr><td>Remarks:<br>$inv[remarks]</td></tr></table>";

	/* -- Final Layout -- */
	$details = "<center><h2>Tax Invoice</h2>
	<table cellpadding='0' cellspacing='4' border=0 width=750>
	<tr><td valign=top width=30%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><td>$inv[cusname]</td></tr>
			<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
			<tr><td>(Vat No. $inv[cusvatno])</td></tr>
		</table>
	</td><td valign=top width=30%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
	</td><td width=20%>
		<img src='compinfo/getimg.php' width=230 height=47>
	</td><td valign=bottom align=right width=20%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Invoice No.</b></td><td valign=center>$inv[invnum]</td></tr>
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[sdate]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr>
			<th width='5%'>#</th>
			<th width='65%'>DESCRIPTION</th>
			<th width='10%'>QTY</th>
			<th width='10%'>UNIT PRICE</th>
			<th width='10%'>AMOUNT</th>
		<tr>
		$products
	</table>
	</td></tr>
	<tr><td>
	$inv[remarks]
	</td><td align=right colspan=3>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>VAT @ $".TAX_VAT."%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><th><b>GRAND TOTAL<b></th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
        	<tr><th>VAT No.</th><td align=center>".COMP_VATNO."</td></tr>
        </table>
	</td><td><br></td></tr>
	</table></center>";

	$OUTPUT = $details;
	require("tmpl-print.php");
}

# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
function writetrans($dtacc, $ctacc, $date, $refnum, $amount, $details)
{
        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($date, "date", 1, 14, "Invalid date.");
        $v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Insert the records into the transect table
		db_conn(PRD_DB);
		$sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details, div) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '$details', '".USER_DIV."')";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Update the balances by adding appropriate values to the trial_bal Table
		core_connect();
		$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc' AND div = '".USER_DIV."'";
		$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc' AND div = '".USER_DIV."'";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

	# commit sql transaction
	# pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	ledgerCT($ctacc, $dtacc, $date, 'CREDIT', $details, $amount);
	ledgerDT($dtacc, $ctacc, $date, 'DEBIT', $details, $amount);
}
?>

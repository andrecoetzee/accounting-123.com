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

require ("settings.php");
require ("core-settings.php");
require_lib("docman");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printInv($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct();
}

$OUTPUT .= mkQuickLinks(
	ql("invoice-canc-view.php", "View Cancelled Invoices"),
	ql("invoice-unf-view.php", "View Incomplete Invoices"),
	ql("cust-credit-stockinv.php", "New Invoice"),
	ql("customers-view.php", "Add Customer")
);

require ("template.php");






# Default view
function slct()
{

	extract($_GET);

	db_connect();
	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class='err'>There are no Customers in Cubit.</li>";
	}
	$custs = "<select name='cusnum' style='width: 100%'>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";


	//layout
	$slct = "
			<h3>View Invoices<h3>
			<table ".TMPL_tblDflts." width='580'>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='view'>
				".(isset($mode)?"<input type='hidden' name='mode' value='$mode'>":"")."
				<tr>
					<th colspan='2'>By Date Range</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align=center  colspan=2>
						".mkDateSelect("from",date("Y"),date("m"),"01")."
						&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
						".mkDateSelect("to")."
					</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Select Customer</td>
					<td>$custs</td>
					<td valign='bottom'><input type='submit' value='Search'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='2' align='center'>OR</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Input customer account number</td>
					<td><input type='text' name='accnum' size='10'></td>
					<td valign='bottom'><input type='submit' value='View'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>All Customers</td>
					<td><input type='submit' name='all' value='List All &raquo;'></td>
				</tr>
			</form>
			</table>";
	return $slct;

}


# Show invoices
function printInv ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	$accnum = remval($accnum);

	if(strlen($accnum) > 0) {
		db_conn('cubit');

		$Sl = "SELECT * FROM customers WHERE lower(accno)=lower('$accnum')";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "<li class='err'>Invalid account number</li>".slct();
		}

		$cd = pg_fetch_array($Ri);

		$cusnum = $cd['cusnum'];
	}

	/* make named r2s snapshop */
	r2sListSet("invoice_stk_view");

	# Set up table to display in
	$printInv = "
		<h3>View invoices. Date Range $fromdate to $todate</h3>
		<form action='invoice-proc.php' method='GET'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th>No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Customer Order No</th>
				<th>Grand Total</th>
				<th colspan='2'>Balance</th>
				<th>Documents</th>
				<th colspan='6'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1 = 0;
	$tot2 = 0;

	if(isset($all)){
    	$sql = "SELECT * FROM invoices WHERE done = 'y' AND odate>='$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."' ORDER BY invid DESC";
	} else {
		$sql = "SELECT * FROM invoices WHERE done = 'y' AND odate>='$fromdate' AND odate <= '$todate' AND cusnum = $cusnum AND div = '".USER_DIV."' ORDER BY invid DESC";
	}
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");

	// Retrieve the reprint setting
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='reprints' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li class='err'> No Outstanding Invoices found for the selected customer.</li><br>";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {

			$inv['total'] = sprint($inv['total']);
			$inv['balance'] = sprint($inv['balance']);
			$tot1 = $tot1 + $inv['total'];
			$tot2 = $tot2 + $inv['balance'];

			# Get documents
			$docs = doclib_getdocs("inv", $inv['invnum']);

			# Format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];
			if($inv['printed'] == "n"){$Dis="TI $inv[invid]";} else {$Dis="$inv[invnum]";}

			$det = "invoice-details.php";
			$print = "invoice-print.php";
			$edit = "cust-credit-stockinv.php";
			$reprint = "invoice-reprint.php";
			if (isset($mode) && $mode == "creditnote") {
				$note = "<input type='button' onClick='document.location.href=\"invoice-note.php?invid=$inv[invid]\";' value='Credit Note'>";
			} else {
				$note = "<a href='invoice-note.php?invid=$inv[invid]'>Credit Note</a>";
			}

			if ($template == "default") {
				$template = "invoice-pdf-reprint.php";
			} elseif ($template == "new") {
				$template = "pdf-tax-invoice.php";
			}

			$pdfreprint = $template;
			$chbox = "<input type=checkbox name='invids[]' value='$inv[invid]' checked=yes>";
			if($inv['location'] == 'int'){
				$det = "intinvoice-details.php";
				$print = "intinvoice-print.php";
				$edit = "intinvoice-new.php";
				$reprint = "intinvoice-reprint.php";
				if (isset($mode) && $mode == "creditnote") {
					$note = "<input type='button' onClick='document.location.href=\"intinvoice-note.php?invid=$inv[invid]\";' value='Credit Note'>";
				} else {
					$note = "<a href='intinvoice-note.php?invid=$inv[invid]'>Credit Note</a>";
				}

				if ($template == "default") {
					$template = "intinvoice-pdf-reprint.php";
				} elseif ($template == "new") {
					$template = "pdf-tax-invoice.php";
				}
				$pdfreprint = $template;
				$chbox = "<br>";
			}
			if($inv['serd'] == 'n')
				$chbox = "";

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
			$fbal = "$sp4--$sp4";
			$bcurr = CUR;
			if($inv['location'] == 'int'){
				$fbal = "$sp4 $inv[currency] $inv[fbalance]";
				$bcurr = $inv['currency'];
			}
			//<a href='invoice-email.php?invid=$inv[invid]'>Email</a>
			$printInv .= "
				<tr class='".bg_class()."'>
					<td>$inv[deptname]</td>
					<td>$Dis</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>$inv[ordno]</td>
					<td align='right'>$inv[cordno]</td>
					<td align='right' nowrap>$bcurr $inv[total]</td>
					<td align='right' nowrap>".CUR." $inv[balance]</td>
					<td align='right' nowrap> $fbal</td>
					<td>$docs</td>
					<td><a href='$det?invid=$inv[invid]'>Details</a></td>
					<td><input type='checkbox' name='evs[$inv[invid]]'></td>";

			if($inv['printed'] == "n"){
				$printInv .= "
						<td><a href='$edit?invid=$inv[invid]&cont=1&letters='>Edit</a></td>
						<td><a target='_blank' href='$print?invid=$inv[invid]'>Process</a></td>
						<td align='center'>$chbox</td>
						<td>&nbsp</td>
					</tr>";
			}else{
				db_conn($inv["prd"]);
				$sql = "SELECT * FROM inv_notes WHERE invid='$inv[invid]'";
				$note_rslt = db_exec($sql) or errDie("Unable to retrieve credit notes from Cubit.");

				if (!pg_num_rows($note_rslt)) {
					$delnote = "<td><a target='_blank' href='invoice-delnote.php?invid=$inv[invid]'>Delivery Note</a></td>";
				} else {
					$delnote = "<td>&nbsp;</td>";
				}

				if(round($inv['total'], 0) != round($inv['nbal'], 0)){
					$printInv .= "
							<td>$note</td>
							<td><a target='_blank' href='$reprint?invid=$inv[invid]&type=invreprint'>Reprint</a></td>
							<td><a href='pdf/$pdfreprint?invid=$inv[invid]&type=invreprint' target='_blank'>Reprint in PDF</a></td>
							$delnote
						</tr>";
				}else{
					$printInv .= "
							<td>Settled</td>
							<td><a target='_blank' href='$reprint?invid=$inv[invid]&type=invreprint'>Reprint</a></td>
							<td><a href='pdf/$pdfreprint?invid=$inv[invid]&type=invreprint' target='_blank'>Reprint in PDF</a></td>
							$delnote
						</tr>";
				}
			}
			$i++;
		}
	}

	$tot1 = sprint($tot1);
	$tot2 = sprint($tot2);
//	$bgColor = bgcolor($i);
	// Layout
	if($i > 0) {
		$printInv .= "
				<tr class='".bg_class()."'>
					<td colspan='6'>Totals:$i</td>
					<td align='right' nowrap>".CUR." $tot1</td>
					<td align='right' nowrap>".CUR." $tot2</td>
					<td colspan='3'><br></td>
					<td colspan='3' align='right'><input type='submit' value='Email Selected' name='email'>
					</td><td colspan='10' align='right'><input type='submit' value='Process Selected' name='proc'></td>
				</tr>
			</table>";
	}

	$printInv .= "
		</table>
		</form>";
	return $printInv;

}


?>
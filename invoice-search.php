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
require ("libs/ext.lib.php");
require_lib("docman");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "view":
			$OUTPUT = printInv($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
    # Display default output
    $OUTPUT = slct();
}

require ("template.php");




# Default view
function slct()
{

	//layout
	$slct = "
		<h3>Find Invoice<h3>
		<table ".TMPL_tblDflts." width='200'>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th>Invoice Number</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='5' name='invnum'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
		<script>
			document.form1.invnum.focus();
		</script>";
	return $slct;

}




# show invoices
function printInv ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	$invnum = trim ($invnum);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($invnum, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm.slct();
	}

	#get us a matching invoice plz
	$invdata = find_invoice($invnum);

	if (strlen($invdata) < 1){
		#nothing found ...
		$invdata = "<li class='err'>No Matching Invoices Found.</li>";
	}

	$display = "
		<table ".TMPL_tblDflts.">
			$invdata
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td><a href='".SELF."'>Find Invoice</a></td>
			</tr>
			<tr class='datacell'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $display;

}


function find_invoice ($invnum)
{

	if (!isset($invnum) OR strlen ($invnum) < 1){
		$invnum = 0;
	}else {
		$invnum = trim ($invnum);
	}

###############################################[ NORMAL INVOICES ]#############################################
	$normalInv = "
		<h3>Invoices</h3>
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

   	$sql = "SELECT * FROM invoices WHERE done = 'y' AND invnum = '$invnum' AND div = '".USER_DIV."' ORDER BY invid DESC";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");

	// Retrieve the reprint setting
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='reprints' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if (pg_numrows ($invRslt) < 1) {
		$normalInv = "<li class='err'> No Outstanding Invoices found for the selected customer.</li><br>";
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
			if($inv['printed'] == "n"){$Dis = "TI $inv[invid]";} else {$Dis = "$inv[invnum]";}

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
			$normalInv .= "
				<tr bgcolor='".bgcolorg()."'>
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
					<td><a target='_blank' href='$det?invid=$inv[invid]'>Details</a></td>";

			if($inv['printed'] == "n"){
				$normalInv .= "
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
					$normalInv .= "
							<td>$note</td>
							<td><a target='_blank' href='$reprint?invid=$inv[invid]&type=invreprint'>Reprint</a></td>
							<td><a href='pdf/$pdfreprint?invid=$inv[invid]&type=invreprint' target='_blank'>Reprint in PDF</a></td>
							$delnote
						</tr>";
				}else{
					$normalInv .= "
							<td>Settled</td>
							<td><a target='_blank' href='$reprint?invid=$inv[invid]&type=invreprint'>Reprint</a></td>
							<td><a href='pdf/$pdfreprint?invid=$inv[invid]&type=invreprint' target='_blank'>Reprint in PDF</a></td>
							$delnote
						</tr>";
				}
			}
			$i++;
		}
		return $normalInv;
	}
##############################################[ /NORMAL INVOICES ]####################################################



################################################[ PAID INVOICES ]#####################################################
	# Set up table to display in
	$paidInv = "
		<h3>Paid Invoices</h3>
		<tr>
			<th>Department</th>
			<th>Invoice No.</th>
			<th>Proforma Inv No.</th>
			<th>Invoice Date</th>
			<th>Customer Name</th>
			<th>Order No</th>
			<th>Customer Order No</th>
			<th>Grand Total</th>
			<th>Documents</th>
			<th colspan='5'>Options</th>
		</tr>";

	// Retrieve template setting
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$repr = "invoice-reprint-prd.php";
	} else {
		$repr = $template;
	}
	
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='reprints'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);
	
	if ($template == "new") {
		$pdf_repr = "pdf/pdf-tax-invoice.php";
	} else {
		$pdf_repr = "pdf/invoice-pdf-reprint-prd.php";
	}

	# Query server
	$i = 0;
	$tot1 = 0;
	$tot2 = 0;
		

	# Connect to database
	db_connect();

	$queries = array();
	for ($i = 1; $i <= 12; $i++) {
		$schema = (int)$i;
		
		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".invoices WHERE done = 'y' AND invnum = '$invnum' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY invid DESC";

	$invRslt = db_exec ($query) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$paidInv = "<li class='err'>No previous finished invoices found.</li>";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {
			$prd = $inv["query_schema"];
		
			$inv['total'] = sprint($inv['total']);
			$inv['balance'] = sprint($inv['balance']);
			$tot1 = $tot1 + $inv['total'];
			$tot2 = $tot2 + $inv['balance'];
			# format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			# Get documents
			$docs = doclib_getdocs("inv", $inv['invnum']);

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
			$bcurr = CUR;
			$det = "invoice-details-prd.php";
			$reprint="<td><a target='_blank' href='$repr?type=invpaidreprint&invid=$inv[invid]&prd=$prd'>Reprint</a></td>";
			$note="<td><a target='_blank' href='invoice-note-prd.php?invid=$inv[invid]&prd=$prd'>Credit Note</a></td>";
			if($inv['location'] == 'int'){
				$bcurr = $inv['currency'];
				$det = "intinvoice-details-prd.php";
				$reprint = "<td><a target='_blank' href='intinvoice-reprint-prd.php?invid=$inv[invid]&prd=$prd'>Reprint</a></td>";
				$note = "";
			}
			$delnote = "<td><a target='_blank' href='invoice-delnote-prd.php?invid=$inv[invid]&prd=$prd'>Delivery Note</a></td>";
			$paidInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[deptname]</td>
					<td>$inv[invnum]</td>
					<td>$inv[docref]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>$inv[ordno]</td>
					<td align='right'>$inv[cordno]</td>
					<td align='right' nowrap>$bcurr $inv[total]</td>
					<td>$docs</td>
					<td><a target='_blank' href='$det?invid=$inv[invid]&prd=$prd'>Details</a></td>
					</td>$reprint</td>
					<td><a href='$pdf_repr?invid=$inv[invid]&prd=$prd&type=invpaidreprint' target='_blank'>Reprint in PDF</a></td>
					$note
					$delnote
				</tr>";
				$i++;
		}
		return $paidInv;
	}
################################################[ /PAID INVOICES ]####################################################



#############################################[ INCOMPLETE INVOICES ]##################################################
	# Set up table to display in
	$unfInv = "
		<h3>Incomplete Invoices</h3>
		<tr>
			<th>Username</th>
			<th>Department</th>
			<th>Sales Person</th>
			<th>Invoice No.</th>
			<th>Invoice Date</th>
			<th>Customer Name</th>
			<th>Order No</th>
			<th>Grand Total</th>
			<th colspan='4'>Options</th>
		</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$totgrd = 0;
	$sql = "SELECT * FROM invoices WHERE done = 'n' AND printed ='n' AND invnum = '$invnum' AND div = '".USER_DIV."' ORDER BY invid DESC";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$unfInv = "<li>No Incomplete Invoices Found.</li>";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {

			# format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			$cont = "cust-credit-stockinv.php";
			if($inv['location'] == 'int'){
				$cont = "intinvoice-new.php";
			}

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
			$bcurr = CUR;
			if($inv['location'] == 'int'){
				$bcurr = $inv['currency'];
			}
			
			if (isset($button) && $button == "selall") {
				$checked = "checked='checked'";
			} else {
				$checked = "";
			}
			
			$inv['total'] = sprint($inv['total']);
			$unfInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[username]</td>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>TI $inv[invid]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>$inv[ordno]</td>
					<td>$bcurr $inv[total]</td>
					<td><a href='$cont?invid=$inv[invid]&cont=true&letters=&done='>Continue</a></td>
					<td><a href='invoice-unf-cancel.php?invid=$inv[invid]'>Cancel</a></td>
				</tr>";
			$totgrd += $inv['total'];
			$i++;
		}
		return $unfInv;
	}
############################################[ /INCOMPLETE INVOICES ]##################################################






#############################################[ UNPROCESSED POS INVOICES ]###################################################
	# Set up table to display in
	$unposInv = "
		<h3>Unprocessed Point of Sale Invoices</h3>
		<tr>
			<th>Department</th>
			<th>Sales Person</th>
			<th>Inv No.</th>
			<th>Invoice Date</th>
			<th>Customer</th>
			<th>Grand Total</th>
			<th colspan='4'>Options</th>
			<th>&nbsp;</th>	
		</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot1 = 0;

	$sql = "SELECT invid, total, odate, deptname, salespn, cusname, printed, balance,cusnum FROM pinvoices WHERE invnum = '$invnum' OR invid = '$invnum' AND div = '".USER_DIV."' ORDER BY invid DESC";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$unposInv = "<li class='err'>No Point of Sale Invoices found for the selected date range.</li>".slct();
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {

			$inv['total'] = sprint($inv['total']);
			$tot1 = $tot1 + $inv['total'];
			# format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			if($inv['cusnum'] != "0"){
				#overwrite the default cusname
				$get_cust = "SELECT surname FROM customers WHERE cusnum = '$inv[cusnum]' LIMIT 1";
				$run_cust = db_exec($get_cust) or errDie("Unable to get customer information.");
				if(pg_numrows($run_cust) == 1){
					$arr = pg_fetch_array($run_cust);
					$inv['cusname'] = $arr['surname'];
				}
			}

			$unposInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>TP $inv[invid]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname]</td>
					<td align=right>".CUR." $inv[total]</td>
					<td><a target='_blank' href='pos-invoice-details.php?invid=$inv[invid]'>Details</a></td>";

			if($inv['printed'] == "n"){
				$unposInv .= "
					<td><a href='pos-invoice-new.php?invid=$inv[invid]&cont=1'>Edit</a></td>
					<td><a href='?invid=$inv[invid]&key=delete_confirm'>Delete</a></td>
					<td><a target='_blank' href='pos-invoice-print.php?invid=$inv[invid]'>Process</a></td>";
			}else{
				$unposInv .= "
					<td></td>
					<td>
						<a target='_blank' href='pos-invoice-reprint.php?invid=$inv[invid]'>Reprint</a>
					</td>";
			}
			if (isset($button) && $button == "allsel") {
				$checked = "checked='checked'";
			} else {
				$checked = "";
			}

			$unposInv .= "
				</tr>";
			$i++;
		}
		return $unposInv;
	}
#############################################[ /UNPROCESSED POS INVOICES ]###################################################




#############################################[ PROCESSED POS INVOICES ]###################################################
	# Set up table to display in
	$posInv = "
		<h3>Processed Point of Sale Invoices</h3>
    	<tr>
    		<th>Department</th>
    		<th>Sales Person</th>
    		<th>Invoice No.</th>
    		<th>Invoice Date</th>
    		<th>Customer Name</th>
    		<th>Grand Total</th>
    		<th colspan='4'>Options</th>
    	</tr>";

	# Connect to database
	db_connect();

	$queries = array();
	for ($i = 1; $i <= 12; $i++) {
		$schema = (int)$i;

		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".pinvoices WHERE done = 'y' AND invnum = '$invnum' OR invid = '$invnum' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY invnum DESC";

	# Query server
	$i = 0;
	$tot1 = 0;
	$tot2 = 0;

    $invRslt = db_exec ($query) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$posInv = "<li>No previous finished invoices.</li>";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {
			$prd = $inv["query_schema"];

			$inv['total'] = sprint($inv['total']);
			$inv['balance'] = sprint($inv['balance']);
			$tot1 = $tot1 + ($inv['total'] - $inv['rounding']);
			$tot2 = $tot2 + $inv['balance'];
			# format date
			$inv['odate'] = explode("-", $inv['odate']);
			$inv['odate'] = $inv['odate'][2]."-".$inv['odate'][1]."-".$inv['odate'][0];

			if($inv['cusnum'] != "0"){
				#then get the actual customer
				db_connect ();
				$get_cus = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' LIMIT 1";
				$run_cus = db_exec($get_cus) or errDie("Unable to get customer information");
				if(pg_numrows($run_cus) < 1){
					#do nothing
				}else {
					$carr = pg_fetch_array($run_cus);
					$inv['cusname'] = "$carr[cusname] $carr[surname]";
				}
			}

			$total = sprint($inv['total'] - $inv['rounding']);

			$posInv .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>$inv[invnum]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>".CUR." $total</td>";

			if(round($inv['total'], 0) != round($inv['nbal'], 0)){
				$posInv .= "
					<td><a href='pos-invoice-note.php?invid=$inv[invid]&prd=$prd'>Credit Note</a></td>";
			}else{
				$posInv .= "
					<td><br></td>";
			}

			$posInv .= "
					<td><a target='_blank' href='pos-invoice-details-prd.php?invid=$inv[invid]&prd=$prd'>Details</a></td>
					<td><a target='_blank' href='pos-invoice-reprint-prd.php?invid=$inv[invid]&prd=$prd'>Reprint</a></td>
					<td><a target='_blank' href='pos-slip.php?invid=$inv[invid]&prd=$prd'>Slip</a></td>
				</tr>";
			$i++;
		}
		return $posInv;
	}
#############################################[ /PROCESSED POS INVOICES ]###################################################





#############################################[ /NON STOCK INVOICES ]###################################################
	# Set up table to display in
	$nonsOrd = "
		<h3>Non-Stock Invoices</h3>
		<tr>
			<th>Invoice Num</th>
			<th>Proforma Inv No.</th>
			<th>Invoice Date</th>
			<th>Customer</th>
			<th>Total</th>
			<th>Documents</th>
			<th colspan=6>Options</th>
		</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;
	
	$sql = "SELECT * FROM nons_invoices WHERE typ = 'inv' AND invnum = '$invnum' OR invid = '$invnum' AND div = '".USER_DIV."' AND balance > 0 ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		$nonsOrd = "<li class='err'> No Non Stock Invoices Found.</li>";
	}

	// Retrieve the PDF reprints
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='reprints' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		// compute the totals
		if ($nonstks["xrate"] == 0.00) {
			$tot_subtot += $nonstks["subtot"];
			$tot_total += $nonstks["total"];
		} else {
			$tot_subtot += $nonstks["subtot"] * $nonstks["xrate"];
			$tot_total += $nonstks["total"] * $nonstks["xrate"];
		}

		# calculate the Sub-Total
		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
		}

		$det = "nons-invoice-det.php";
		$edit = "nons-invoice-new.php";
		$print = "nons-invoice-print.php";
		$reprint = "nons-invoice-reprint.php";
		$note = "nons-invoice-note.php";

		if ($template == "default") {
			$template = "nons-invoice-pdf-reprint.php";
		} elseif ($template == "new") {
			$template = "pdf-tax-invoice.php";
		}
		$reprpdf = $template;
		$cur = CUR;
		if($nonstks['location'] == 'int'){
			$det = "nons-intinvoice-det.php";
			$edit = "nons-intinvoice-new.php";
			$print = "nons-intinvoice-print.php";
			$note = "nons-intinvoice-note.php";
			if ($template == "default") {
				$template = "nons-intinvoice-pdf-reprint.php";
			} elseif ($template == "new") {
				$template = "pdf-tax-invoice.php";
			}
			$reprpdf = $template;
			$note = "nons-intinvoice-note.php";
			$cur = $nonstks['currency'];
		}

		# Get documents
		$docs = doclib_getdocs("ninv", $nonstks['invnum']);

		if($nonstks['accepted'] == " " && $nonstks['done'] != "y") {
			$chbox = "<input type='checkbox' name='evs[$nonstks[invid]]' value='$nonstks[invid]' checked='yes'>";
		} else {
			$chbox = "";
		}

		$nonsOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$nonstks[invnum]</td>
				<td>$nonstks[docref]</td>
				<td>$nonstks[odate]</td>
				<td>$nonstks[cusname]</td>
				<td align='right'>$cur $nonstks[total]</td>
				<td>$docs</td>
				<td>$chbox</td>
				<td><a href='$det?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$nonsOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$nonsOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
					<td><a href=# onClick=printer('$print?invid=$nonstks[invid]&type=nons')>Process</a></td>
				</tr>";
		} else {
			$cn = "";
			if($nonstks['accepted'] != "note")
				if (isset($mode) && $mode == "creditnote") {
					$cn = "<input type='button' onClick=\"printer('$note?invid=$nonstks[invid]&type=nonsnote');\" value='Credit Note'>";
				} else {
					$cn = "<a href='#' onClick=printer('$note?invid=$nonstks[invid]&type=nonsnote')>Credit Note</a>";
				}

			$nonsOrd .= "
					<td>$cn</td>
					<td><a href='#' onClick=printer('$reprint?invid=$nonstks[invid]&type=nonsreprint')>Reprint</a></td>
					<td><a href='pdf/$reprpdf?invid=$nonstks[invid]&type=nonsreprint' target='_blank'>Reprint in PDF</a></td>
				</tr>";
		}
		return $nonsOrd;
	}
#############################################[ /NON STOCK INVOICES ]###################################################




############################################[ PAID NON STOCK INVOICES ]################################################
	$paidnonsOrd = "
		<h3>Paid Non-Stock Invoices</h3>
		<tr>
			<th>Invoice Num</th>
			<th>Proforma Inv No.</th>
			<th>Invoice Date</th>
			<th>Customer</th>
			<th>Total</th>
			<th>Documents</th>
			<th colspan='6'>Options</th>
		</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;

	$sql = "SELECT * FROM nons_invoices WHERE typ = 'inv' AND invnum = '$invnum' OR invid = '$invnum' AND div = '".USER_DIV."' AND balance = 0 AND done = 'y' ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		$paidnonsOrd = "<li> There are no non stock invoices found.</li>";
	}
	
	// Retrieve the PDF reprints
	db_conn("cubit");

	$sql = "SELECT filename FROM template_settings WHERE template='reprints' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		# date format
		$date = explode("-", $nonstks['odate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		if ($nonstks["xrate"] == 0.00) {
			$tot_subtot += $nonstks["subtot"];
			$tot_total += $nonstks["total"];
		} else {
			$tot_subtot += $nonstks["subtot"] * $nonstks["xrate"];
			$tot_total += $nonstks["total"] * $nonstks["xrate"];
		}

		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
		}

		$det = "nons-invoice-det.php";
		$edit = "nons-invoice-new.php";
		$print = "nons-invoice-print.php";
		$reprint = "nons-invoice-reprint.php";
		$note = "nons-invoice-note.php";

		if ($template == "default") {
			$template = "nons-invoice-pdf-reprint.php";
		} elseif ($template == "new") {
			$template = "pdf-tax-invoice.php";
		}
		$reprpdf = $template;
		$cur = CUR;
		if($nonstks['location'] == 'int'){
			$det = "nons-intinvoice-det.php";
			$edit = "nons-intinvoice-new.php";
			$print = "nons-intinvoice-print.php";
			$note = "nons-intinvoice-note.php";
			if ($template == "default") {
				$template = "nons-intinvoice-pdf-reprint.php";
			} elseif ($template == "new") {
				$template = "pdf-tax-invoice.php";
			}
			$reprpdf = $template;
			$note = "nons-intinvoice-note.php";
			$cur = $nonstks['currency'];
		}

		# Get documents
		$docs = doclib_getdocs("ninv", $nonstks['invnum']);
		
		if($nonstks['accepted']==" " &&$nonstks['done'] != "y") {
			$chbox = "<input type=checkbox name='evs[$nonstks[invid]]' value='$nonstks[invid]' checked=yes>";
		} else {
			$chbox = "";
		}
		
		$paidnonsOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$nonstks[invnum]</td>
				<td>$nonstks[docref]</td>
				<td>$date</td>
				<td>$nonstks[cusname]</td>
				<td align=right>$cur $nonstks[total]</td>
				<td>$docs</td>
				<td>$chbox</td>
				<td><a href='$det?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$paidnonsOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$paidnonsOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
					<td><a href=# onClick=printer('$print?invid=$nonstks[invid]&type=nons')>Process</a></td>
				</tr>";
		} else {
			$cn = "";

			$paidnonsOrd .= "
					<td>$cn</td>
					<td><a target='_blank' href='$reprint?invid=$nonstks[invid]&type=nonsreprint'>Reprint</a></td>
					<td><a href='pdf/$reprpdf?invid=$nonstks[invid]&type=nonsreprint' target='_blank'>Reprint in PDF</a></td>
					<td><input type='checkbox' name='evs[$nonstks[invid]]'></td>
				</tr>";
		}
		return $paidnonsOrd;
	}
############################################[ /PAID NON STOCK INVOICES ]################################################



##########################################[ INCOMPLETE NON STOCK INVOICES ]#############################################
	$unfnonsOrd = "
		<h3>Incomplete Non-Stock Invoices</h3>
		<tr>
			<th>Invoice Num</th>
			<th>Proforma Inv No.</th>
			<th>Invoice Date</th>
			<th>Customer</th>
			<th>Total</th>
			<th colspan='2'>Options</th>
		</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;

	$sql = "SELECT * FROM nons_invoices WHERE typ = 'inv' AND div = '".USER_DIV."' AND invnum = '$invnum' OR invid = '$invnum' AND done = 'n' ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		$unfnonsOrd = "<li> There are no incomplete non stock invoices found.</li>";
	}

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		# date format
		$date = explode("-", $nonstks['sdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		if ($nonstks["xrate"] == 0.00) {
			$tot_subtot += $nonstks["subtot"];
			$tot_total += $nonstks["total"];
		} else {
			$tot_subtot += $nonstks["subtot"] * $nonstks["xrate"];
			$tot_total += $nonstks["total"] * $nonstks["xrate"];
		}

		# calculate the Sub-Total

		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
		}

		if (isset($nonstks['multiline']) AND ($nonstks['multiline'] == "yes"))
			$edit = "nons-multiline-invoice-new.php";
		else 
			$edit = "nons-invoice-new.php";

		$cur = CUR;

		$unfnonsOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$nonstks[invnum]</td>
				<td>$nonstks[docref]</td>
				<td>$date</td>
				<td>$nonstks[cusname]</td>
				<td align='right'>$cur $nonstks[total]</td>
				<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Continue</a></td>
			</tr>";
		return $unfnonsOrd;
	}

##########################################[ /INCOMPLETE NON STOCK INVOICES ]############################################

}

?>
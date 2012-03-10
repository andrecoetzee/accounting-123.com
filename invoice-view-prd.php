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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "view":
			$OUTPUT = printInv($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slct();
	}
} else {
	$OUTPUT = slct();
}

require ("template.php");



# Default view
function slct()
{

	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year</li>";
	}
	$Prds = "<select name='prd' style='width: 100%'>";
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$Prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$Prds .= "</select>";

	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY surname ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);
	if(pg_numrows($cusRslt) < 1){
		return "<li class='err'> There are no Customers in Cubit.</li>";
	}

	$custs = "<select name='cusnum' style='width: 100%'>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";


	//layout
	$slct = "
		<h3>View Paid Invoices<h3>
		<table ".TMPL_tblDflts." width='500'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;TO&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Customer</td>
				<td>$custs</td>
				<td><input type='submit' value='Search'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>OR</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Input customer account number</td>
				<td><input type='text' name='accnum' size='10'></td>
				<td valign='bottom'><input type='submit' value='View'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>All Customers</td>
				<td><input type='submit' name='all' value='List All &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slct;

}


# show invoices
function printInv ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
        return $confirm;
	}



	$accnum = remval($accnum);

	if(strlen($accnum) > 0) {
		db_conn('cubit');
		
		$Sl = "SELECT * FROM customers WHERE lower(accno)=lower('$accnum')";
		$Ri = db_exec($Sl);
		
		if(pg_num_rows($Ri) < 1) {
			return "<li class='err'>Invalid account number</li>".slct();
		}
		
		$cd = pg_fetch_array($Ri);
		
		$cusnum = $cd['cusnum'];
	}

	# Set up table to display in
	$printInv = "
		<h3>Paid Invoices</h3>
		<table ".TMPL_tblDflts.">
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
		
	if(isset($all)){
		# Connect to database
		db_connect();
	
		$queries = array();
		for ($i = 1; $i <= 12; $i++) {
			$schema = (int)$i;
			
			$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".invoices WHERE done = 'y' AND odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."'";
		}
		$query = implode(" UNION ", $queries);
		$query .= " ORDER BY invid DESC";
	}else{
		# Connect to database
		db_connect();
	
		$queries = array();
		for ($i = 1; $i <= 12; $i++) {
			$schema = (int)$i;
			
			$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".invoices WHERE done = 'y' AND cusnum = '$cusnum' AND odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."'";
		}
		$query = implode(" UNION ", $queries);
		$query .= " ORDER BY invid DESC";
	}
	$invRslt = db_exec ($query) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li class='err'>No previous finished invoices found.</li>";
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
			$reprint="<td><a target=_blank href='$repr?type=invpaidreprint&invid=$inv[invid]&prd=$prd'>Reprint</a></td>";
			$note="<td><a target=_blank href='invoice-note-prd.php?invid=$inv[invid]&prd=$prd'>Credit Note</a></td>";
			if($inv['location'] == 'int'){
				$bcurr = $inv['currency'];
				$det = "intinvoice-details-prd.php";
				$reprint="<td><a target='_blank' href='intinvoice-reprint-prd.php?invid=$inv[invid]&prd=$prd'>Reprint</a></td>";
				$note="";
			}
			$delnote="<td><a target='_blank' href='invoice-delnote-prd.php?invid=$inv[invid]&prd=$prd'>Delivery Note</a></td>";
			$printInv .= "
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
					<td><a href='$det?invid=$inv[invid]&prd=$prd'>Details</a></td>
					</td>$reprint</td>
					<td><a href='$pdf_repr?invid=$inv[invid]&prd=$prd&type=invpaidreprint' target='_blank'>Reprint in PDF</a></td>
					$note
					$delnote
				</tr>";
				$i++;
		}
	}

	$tot1 = sprint($tot1);
	$tot2 = sprint($tot2);

	// Layout
	if($tot1 > 0){
		$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7'>Totals:$i</td>
				<td align='right'>$tot1</td>
				<td align='right' colspan='6'></td>
			</tr>";
	}

	$printInv .= "
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			".TBL_BR."
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-canc-view.php'>View Cancelled Invoices</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='invoice-unf-view.php'>View Incomplete Invoices</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='cust-credit-stockinv.php'>New Invoice</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printInv;

}


?>
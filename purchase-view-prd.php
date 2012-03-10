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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "view":
			$OUTPUT = printPurch($HTTP_POST_VARS);
			break;
		case "export":
			$OUTPUT = export($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
	$OUTPUT = slct();
}

$OUTPUT .= mkQuickLinks(
	ql("purchase-new.php", "New Order"),
	ql("stock-report.php", "Stock Control Reports"),
	ql("stock-view.php", "View Stock")
);

require ("template.php");



/* select date range */
function slct()
{

	db_connect ();

	$supplier_drop = "<select name='supplier'>";
	$supplier_drop .= "<option value='0'>All Suppliers</option>";
	$get_supps = "SELECT * FROM suppliers WHERE blocked IS NULL or blocked != 'yes' ORDER BY supname";
	$run_supps = db_exec ($get_supps) or errDie ("Unable to get supplier information");
	if (pg_numrows ($run_supps) > 0){
		while ($sarr = pg_fetch_array ($run_supps)){
			$supplier_drop .= "<option value='$sarr[supid]'>$sarr[supname]</option>";
		}
	}
	$supplier_drop .= "</select>";

	$OUT = "
		<h3>View Received Stock Orders</h3>
		<table ".TMPL_tblDflts." width='500'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' colspan='2' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr>
				<th>Supplier(s)</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$supplier_drop</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='View &raquo'></td>
			</tr>
		</form>
		</table>";
	return $OUT;

}



/* print the purchase list */
function printPurch($HTTP_POST_VARS, $pure = false)
{

	extract($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	$fromdate = $from_year . "-" . $from_month . "-" . $from_day;
	$todate = $to_year . "-" . $to_month . "-" . $to_day;

	if (!checkdate($from_month, $from_day, $from_year)) {
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}

	if (!checkdate($to_month, $to_day, $to_year)) {
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	if ($v->isError()) {
        return $v->genErrors();
	}



	require_lib("docman");
	
	$OUT = "";

	if (!$pure) {
		$OUT .= "
		<center>
		<h3>Received Stock Orders</h3>";
	}
	
	$OUT .= "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Purchase No.</th>
				<th>Order No.</th>
				<th>Supp Inv No.</th>
				<th>Order Date</th>
				<th>Received Date</th>
				<th>Supplier</th>
				<th>Sub Total</th>
				<th>Delivery Charges</th>
				<th>VAT</th>
				<th>Total</th>
				<th>Delivery Reference No.</th>
				<th>Documents</th>
				<th colspan='5'>Options</th>
			</tr>";

	$i = 0;
	$tot1 = 0;
	$tot2 = 0;
	$tot3 = 0;
	$tot4 = 0;

	$supsql = "";
	if (isset ($supplier) AND $supplier != 0){
		$supsql = " AND supid = '$supplier'";
	}

	/* build the sql */
	$queries = array();
	for ($i = 1; $i <= 12; $i++) {
		$schema = (int)$i;

		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".purchases WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND done = 'y' AND div = '".USER_DIV."' $supsql";
	}
	$sql = implode(" UNION ", $queries) . " ORDER BY pdate DESC";

	$qry = new dbQuery(DB_SQL, $sql);
	$qry->run();
	if ($qry->num_rows() < 1) {
		return "<li class='err'> No Received Stock Orders found.</li><br>";
	}
	while ($stkp = $qry->fetch_array()){
		$prd = $stkp["query_schema"];

		/* calculate the subtotal */
		$stkp['total'] = sprint($stkp['total']);
		$stkp['shipchrg'] = sprint($stkp['shipping']);
		$subtot = ($stkp['subtot']);
		$subtot = sprint($subtot);

		/* add the totals */
		$tot1 = sprint($tot1 + $subtot);
		$tot2 = sprint($tot2 + $stkp['shipchrg']);
		$tot3 = sprint($tot3 + $stkp['total']);
		$tot4 = sprint($tot4 + $stkp["vat"]);
		$docs = doclib_getdocs("pur", $stkp['purnum']);
		$docs .= "&nbsp;<a href='#' onClick=\"printer('purch-recv.php?key=recv_print&purid=$stkp[purid]');\">GRN</a>";

		$OUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stkp[purnum]</td>
				<td>$stkp[ordernum]</td>
				<td>$stkp[supinv]</td>
				<td>$stkp[pdate]</td>
				<td>$stkp[ddate]</td>
				<td>$stkp[supname]</td>
				<td align='right' nowrap>".CUR." $subtot</td>
				<td align='right' nowrap>".CUR." $stkp[shipchrg]</td>
				<td align='right' nowrap>".CUR." $stkp[vat]</td>
				<td align='right' nowrap>".CUR." $stkp[total]</td>
				<td>$stkp[refno]</td>
				<td>$docs</td>";

		if ($stkp['returned'] != 'y') {
			$OUT .= "<td><a href='purch-return.php?purid=$stkp[purid]&prd=$prd'>Return</a></td>";
		} else {
			$OUT .= "<td>&nbsp;</td>";
		}

		if($stkp['rsubtot'] > 0){
			$OUT .= "<td><a href='purch-recnote.php?purid=$stkp[purid]&prd=$prd'>Record Credit Note</a></td>";
		} else {
			$OUT .= "<td>&nbsp;</td>";
		}

		$OUT .= "
				<td><a href='purch-det-prd.php?purid=$stkp[purid]&prd=$prd'>Details</a></td>
				<td><a target='_blank' href='purch-recv-print.php?purid=$stkp[purid]&prd=$prd'>Print</a></td>
			</tr>";
	}

	$OUT .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='6'>Totals</td>
			<td align='right' nowrap>".CUR." $tot1</td>
			<td align='right' nowrap>".CUR." $tot2</td>
			<td align='right' nowrap>".CUR." $tot4</td>
			<td align='right' nowrap>".CUR." $tot3</td>
		</tr>";

	if (!$pure) {
		$OUT .= "
			".TBL_BR."
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='export' />
				<input type='hidden' name='prd' value='$prd' />
				<input type='hidden' name='from_day' value='$from_day' />
				<input type='hidden' name='from_month' value='$from_month' />
				<input type='hidden' name='from_year' value='$from_year' />
				<input type='hidden' name='to_day' value='$to_day' />
				<input type='hidden' name='to_month' value='$to_month' />
				<input type='hidden' name='to_year' value='$to_year' />
				<tr>
					<td colspan='4'><input type='submit' value='Export to Spreadsheet'></td>
				</tr>
			</form>";
	}
	
	$OUT .= "</table>";
	return $OUT;

}



/* spreadsheet function */
function export($HTTP_POST_VARS) {
	$OUT = printPurch($HTTP_POST_VARS, true);
	$OUT = clean_html($OUT);

	require_lib("xls");
	Stream("Orders Received", $OUT);
}



/* checks if a purchase is returnable */
function returnable($purnum){
	$sers = ext_getPurSerials($purnum);

	$ret = true;
	if(count($sers) > 0){
		foreach($sers as $key => $ser){
			if(ext_findAvSer($ser['serno'], $ser['stkid']) == false)
				return false;
		}
	}
	return $ret;
}


?>

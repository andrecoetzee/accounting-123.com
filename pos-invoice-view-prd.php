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

require ("template.php");





# Default view
function slct()
{

	db_conn(YR_DB);

	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year.</li>";
	}
	$Prds = "<select name='prd'>";
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$Prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$Prds .= "</select>";

    //layout
	$slct = "
		<h3>View Printed Point of Sale Invoices<h3>
		<table ".TMPL_tblDflts." width='400'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center' colspan='2' nowrap='t'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Search'></td>
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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
        return $confirm;
	}


	# Set up table to display in
	$printInv = "
		<h3>Printed Point of Sale Invoices</h3>
        <table ".TMPL_tblDflts.">
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

		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".pinvoices WHERE done = 'y' AND odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY invnum DESC";

	# Query server
	$i = 0;
	$tot1 = 0;
	$tot2 = 0;

    $invRslt = db_exec ($query) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li>No previous finished invoices.</li>";
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

			$printInv .= "
				<tr class='".bg_class()."'>
					<td>$inv[deptname]</td>
					<td>$inv[salespn]</td>
					<td>$inv[invnum]</td>
					<td align='center'>$inv[odate]</td>
					<td>$inv[cusname] $inv[surname]</td>
					<td align='right'>".CUR." $total</td>";

			if(round($inv['total'], 0) != round($inv['nbal'], 0)){
				$printInv .= "
					<td><a href='pos-invoice-note.php?invid=$inv[invid]&prd=$prd'>Credit Note</a></td>";
			}else{
				$printInv .= "
					<td><br></td>";
			}

			$printInv .= "
					<td><a href='pos-invoice-details-prd.php?invid=$inv[invid]&prd=$prd'>Details</a></td>
					<td><a target='_blank' href='pos-invoice-reprint-prd.php?invid=$inv[invid]&prd=$prd'>Reprint</a></td>
					<td><a target='_blank' href='pos-slip.php?invid=$inv[invid]&prd=$prd'>Slip</a></td>
				</tr>";
			$i++;
		}
	}
	$tot1 = sprint($tot1);
	$tot2 = sprint($tot2);

	// Layout
	if($tot1 > 0){
		$printInv .= "
			<tr class='".bg_class()."'>
				<td colspan='5'>Totals:$i</td>
				<td align='right'>".CUR." $tot1</td>
				<td align='right' colspan='4'></td>
			</tr>";
	}

	$printInv .= "
		</table>
        <p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printInv;

}


?>
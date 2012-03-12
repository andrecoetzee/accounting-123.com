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
			$OUTPUT = printNote($_POST);
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
		<h3>View Credit notes<h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' colspan='2'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
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
function printNote ($_POST)
{

	# Get vars
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

	# Mix dates
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

	# Set up table to display in
	$printNote = "
		<h3>Credit notes</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Credit Note No.</th>
				<th>Invoice No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
				<th>Documents</th>
				<th colspan='3'>Options</th>
			</tr>";

	# Connect to database
	db_connect();
	
	$month = (int)$from_month;

	$yearcheck = $from_year;

	$date_arr = array ();
	$flag = TRUE;
	while ($flag){

		if($month == 13){
			$month = 1;
			$yearcheck++;
		}
		#add month to array
		$date_arr[] = $month;
		if ($month == $to_month && $yearcheck == $to_year)
			$flag = FALSE;

		$month++;
	}

	$date_arr = array_unique($date_arr);

	$queries = array();
#	this is no good ... to_month CAN be less than from_month
#	for ($i = $from_month; $i <= $to_month; $i++) {
	foreach ($date_arr as $i){
		$schema = (int)$i;
		$queries[] = "SELECT *,'$schema' AS query_schema FROM \"$schema\".inv_notes WHERE odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."'";
	}
	$query = implode(" UNION ", $queries);
	$query .= " ORDER BY noteid DESC";

	# Query server
	$i = 0;
	$tot1 = 0;
	$sql = " ORDER BY noteid DESC";
	$noteRslt = db_exec ($query) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($noteRslt) < 1) {
		$printNote = "<li class='err'>No previous credit notes found.</li>";
	}else{
		while ($note = pg_fetch_array ($noteRslt)) {
			$prd = $note["query_schema"];

			$note['total'] = sprint($note['total']);
			$tot1 = $tot1 + $note['total'];

			# Format date
			$note['odate'] = explode("-", $note['odate']);
			$note['odate'] = $note['odate'][2]."-".$note['odate'][1]."-".$note['odate'][0];

			# Get documents
			$docs = doclib_getdocs("note", $note['notenum']);

			$rep = "invoice-note-reprint.php";
			$curr = CUR;
			if($note['location'] == 'int'){
				$rep = "intinvoice-note-reprint.php";
				$curr = $note['currency'];
			}
			$printNote .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$note[deptname]</td>
					<td>$note[salespn]</td>
					<td>$note[notenum]</td>
					<td>$note[invnum]</td>
					<td align='center'>$note[odate]</td>
					<td>$note[surname]</td>
					<td align='right'>$note[ordno]</td>
					<td align='right'>$curr $note[total]</td>
					<td>$docs</td>
					<td><a href='$rep?noteid=$note[noteid]&prd=$prd&reprint=yes' target='_blank'>Details</a></td>
				</tr>";
			$i++;
		}
	}

	$tot1 = sprint($tot1);
	// Layout
	if($tot1 > 0){
		$printNote .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7'>Totals:$i</td>
				<td align='right'>".CUR." $tot1</td>
				<td colspan='2'>&nbsp;</td>
			</tr>";
	}

	$printNote .= "
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printNote;

}



?>
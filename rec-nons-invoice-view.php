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
			$OUTPUT = printInvoice ($_POST);
			break;
		default:
			$OUTPUT = slct ();
			break;
	}
} else {
	# Display default output
	$OUTPUT = slct ();
}

require ("template.php");




# Default view
function slct($err="")
{

	//layout
	$slct = "
		<h3>View Recurring Non-Stock Invoices</h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			$err
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='rec-nons-invoice-new.php'>New Recurring Non Stock Invoice</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slct;

}




# show
function printInvoice ($_POST)
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



	# Set up table to display in
	$printOrd = "
		<center>
		<h3>View Recurring Non-Stock Invoices</h3>
		<table ".TMPL_tblDflts.">
		<form action='nons-rec-invoice-proc.php' method='GET'>
			<tr>
				<th>Num</th>
				<th>Date</th>
				<th>Customer</th>
				<th>Total</th>
				<th colspan='5'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;

	$sql = "SELECT * FROM rnons_invoices WHERE typ = 'inv' AND sdate >= '$fromdate' AND sdate <= '$todate' AND div = '".USER_DIV."' ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return slct("<li class='err'>No non stock recurring invoices found.</li><br>");
	}

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		# date format
		$date = explode("-", $nonstks['sdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		$tot_subtot += $nonstks["subtot"];
		$tot_total += $nonstks["total"];

		# calculate the Sub-Total

		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
		}

		$det = "rec-nons-invoice-det.php";
		$edit = "rec-nons-invoice-new.php";
		$print = "nons-invoice-print.php";
		$reprint = "nons-invoice-reprint.php";
		$reprpdf = "nons-invoice-pdf-reprint.php";
		$note = "nons-invoice-note.php";
		$cur = CUR;

		if (isset($selnum) AND $counter < 1000){
			$ch = "checked";
		}else {
			if(isset($all)) {
				$ch = "checked";
			} else {
				$ch = "";
			}
		}

		$printOrd .= "
			<tr class='".bg_class()."'>
				<td>$nonstks[invnum]</td>
				<td>$date</td>
				<td>$nonstks[cusname]</td>
				<td align='right'>$cur $nonstks[total]</td>
				<td><a href='$det?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$printOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='rec-nons-invoice-rem.php?invid=$nonstks[invid]'>Delete</a></td>
					<td><input type=checkbox name='invids[]' value='$nonstks[invid]' $ch></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$printOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='rec-nons-invoice-rem.php?invid=$nonstks[invid]'>Delete</a></td>
					<td><input type='checkbox' name='invids[]' value='$nonstks[invid]' $ch></td>
				</tr>";
		} else {
			$cn = "";
			if($nonstks['balance'] <> 0)
				$cn = "<a href='#' onClick=printer('$note?invid=$nonstks[invid]')>Credit Note</a>";
				
				

			$printOrd .= "
					<td>$cn</td>
					<td><a target='_blank' href='$reprint?invid=$nonstks[invid]'>Reprint</a></td>
					<td><a href='pdf/$reprpdf?invid=$nonstks[invid]' target='_blank'>Reprint in PDF</a></td>
					<td><input type='checkbox' name='evs[$nonstks[invid]]' $ch></td>
				</tr>";
		}
		$i++;
	}
	
	$tot_total = sprint($tot_total);

	$printOrd .= "
		<tr class='".bg_class()."'>
			<td colspan='3'>Totals: $i</td>
			<td align='right'>".CUR." $tot_total</td>
			<td colspan='6' align='right'><input type='submit' name='edit' value='Edit Item Prices On Selected'> <input type='submit' value='Process Selected'></td>
		</tr>";

	$printOrd .= "
		<tr><td><br></td></tr></form>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='view'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>
			<input type='hidden' name='all' value=''>
			<tr class='".bg_class()."'>
				<td colspan='6'></td>
				<td colspan='10'><input type='submit' value='Select All' name='f'> &nbsp; <input type='submit' value='Select 1000' name='selnum'></td>
			</tr>
		</form>";
	
	$printOrd .= "
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='rec-nons-invoice-new.php'>New Recurring Non-Stock Invoice</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printOrd;

}


?>

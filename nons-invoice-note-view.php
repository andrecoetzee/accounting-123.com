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
				<tr bgcolor='".bgcolorg()."'>
					<td valign='bottom' colspan='2' align='center'><input type='submit' value='Search'></td>
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
			$confirm .= "<li class='err'>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

		# Set up table to display in
		$printNote = "
					<h3>Non Stock Credit notes</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Credit Note No.</th>
							<th>Invoice No.</th>
							<th>Date</th>
							<th>Customer Name</th>
							<th>Grand Total</th>
							<th>Documents</th>
							<th colspan='3'>Options</th>
						</tr>";

		# connect to database
		db_connect();

		# Query server
		$i = 0;
		$tot1=0;
		$sql = "SELECT * FROM nons_inv_notes WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY noteid DESC";
		$noteRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
		if (pg_numrows ($noteRslt) < 1) {
			$printNote = "<li>No previous credit notes.";
		}else{
			while ($note = pg_fetch_array ($noteRslt)) {
				# alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				$note['total']=sprint($note['total']);
				$tot1=$tot1+$note['total'];

				# Format date
				$note['date'] = explode("-", $note['date']);
				$note['date'] = $note['date'][2]."-".$note['date'][1]."-".$note['date'][0];

				$cur = CUR;
				if($note['location'] == 'int')
					$cur = $note['currency'];

				# Get documents
				$docs = doclib_getdocs("note", $note['notenum']);

				$printNote .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$note[notenum]</td>
								<td>$note[invnum]</td>
								<td align='center'>$note[date]</td>
								<td>$note[cusname]</td>
								<td align='right'>$cur $note[total]</td>
								<td>$docs</td>
								<td><a href='nons-invoice-note-det.php?noteid=$note[noteid]'>Details</a></td>
								<td><a href='nons-invoice-note-reprint.php?noteid=$note[noteid]' target=_blank>Reprint</a></td>
							</tr>";
				$i++;
			}
		}
		$tot1 = sprint($tot1);

		// Layout
		if($tot1>0){
			$printNote .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='4'>Totals:$i</td>
							<td align='right'>$cur $tot1</td>
							<td align='right'></td>
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
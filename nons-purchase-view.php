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
       case "export":
			$OUTPUT = export ($HTTP_POST_VARS);
			break;
		 case "view":
			$OUTPUT = printPurch ($HTTP_POST_VARS);
			break;

		default:
			$OUTPUT = slct ();
			break;
	}
} else {
	# Display default output
	$OUTPUT = slct ();
}

$OUTPUT .= "<br>".
		mkQuickLinks(
			ql("nons-purchase-new.php", "New Non-Stock Purchase"),
			ql("nons-purchase-view.php", "View Non-Stock Purchase")
		);

require ("template.php");

# Default view
function slct()
{
	//layout
	$slct = "
	<h3>View Non-Stock Orders</h3>
	<table ".TMPL_tblDflts." width='460'>
	<form action='".SELF."' method='POST' name='form'>
		<input type='hidden' name='key' value='view'>
		<tr>
			<th colspan='2'>By Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center' nowrap='t'>
				".mkDateSelect("from",date("Y"),date("m"),"01")."
				&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
				".mkDateSelect("to")."
			</td>
			<td valign='bottom'><input type='submit' value='Search'></td>
		</tr>
	</form>
	</table>";

	return $slct;
}

# show
function printPurch ($HTTP_POST_VARS, $pure = false)
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
			$confirm .= "<li class='err'>-".$e["msg"]."<br>";
		}
        return $confirm;
	}


	require_lib("docman");

	# Set up table to display in
	$printOrd = "";
	
	if (!$pure) {
		$printOrd = "
		<center>
		<h3>View Non-Stock Orders</h3>";
	}
	
	$printOrd .= "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Purchase No.</th>
							<th>Supplier Inv</th>
							<th>Purchase Date</th>
							<th>Supplier</th>
							<th>Sub Total</th>
							<th>Delivery Charges</th>
							<th>Vat</th><th>Total</th>
							<th>Documents</th>
							<th colspan='5'>Options</th>
						</tr>";

	# connect to database
	db_connect ();
	
	$Sl="SELECT supid,supname FROM suppliers";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	while($data=pg_fetch_array($Ri)) {
		$sn[$data['supid']]=$data['supname'];
	}

	# Query server
	$i = 0;
	$tot1=0;
	$tot2=0;
	$tot3=0;
	$tot4=0;

	$sql = "SELECT * FROM nons_purchases WHERE pdate >= '$fromdate' AND pdate <= '$todate' AND div = '".USER_DIV."' ORDER BY pdate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve Orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "<li class='err'> No Non Stock Purchases Could Be Found For The Selected Period.</li>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {

		# date format
		$date = explode("-", $stkp['pdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		# calculate the Sub-Total
		$stkp['total']=sprint($stkp['total']);
		$stkp['shipchrg']=sprint($stkp['shipping']);	
		$subtot = ($stkp['subtot']);
		$subtot=sprint($subtot);
		$vat=sprint($stkp['vat']);
		$tot1=sprint(($tot1+$subtot));
		$tot2=sprint(($tot2+$stkp['shipchrg']));
		$tot3=sprint(($tot3+$stkp['total']));
		$tot4=sprint($tot4+$vat);
		# Get documents
		$docs = doclib_getdocs("npur", $stkp['purnum']);

		$edit = "nons-purchase-new.php";
		$det = "nons-purch-det.php";
		$recv = "nons-purch-recv.php";
		if($stkp['spurnum'] > 0){
			$edit = "lnons-purch-new.php";
			$det = "lnons-purch-det.php";
			$recv = "lnons-purch-recv.php";
		}
		if($stkp['assid'] > 0){
			$edit = "nonsa-purchase-new.php";
			$det = "nonsa-purch-det.php";
			$recv = "nonsa-purch-recv.php";
		}
		
		if($stkp['ctyp']=="s"&&isset($sn[$stkp['supplier']])) {
			$stkp['supplier']=$sn[$stkp['supplier']];
		}


		$printOrd .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$stkp[purnum]</td>
					<td>$stkp[supinv]</td>
					<td>$date</td>
					<td>$stkp[supplier]</td>
					<td align='right'>".CUR." $subtot</td>
					<td align='right'>".CUR." $stkp[shipchrg]</td>
					<td align='right'>".CUR." $vat</td>
					<td align='right'>".CUR." $stkp[total]</td>
					<td>$docs</td>
					<td><a href='$det?purid=$stkp[purid]'>Details</a></td>
					<td><a href='javascript: printer(\"nons-purch-print.php?purid=$stkp[purid]\");'>Print</a></td>";

		if($stkp['received'] != "y" && $subtot == 0){
			$printOrd .= "
					<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
					<td></td>
					<td><a href='nons-purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
				</tr>";
		}elseif($stkp['received'] != "y"){
			$printOrd .= "
					<td><a href='$edit?purid=$stkp[purid]&cont=1'>Edit</a></td>
					<td><a href='$recv?purid=$stkp[purid]'>Received</a></td>
					<td><a href='nons-purch-cancel.php?purid=$stkp[purid]'>Cancel</a></td>
				</tr>";
		}else{
			$printOrd .= "
					<td colspan='3'><br></td>
				</tr>";
		}
		$i++;
	}

	$printOrd .= "
	<tr bgcolor='".bgcolorg()."'>
		<td colspan='4'>Totals</td>
		<td align='right'>".CUR." $tot1</td>
		<td align='right'>".CUR." $tot2</td>
		<td align='right'>".CUR." $tot4</td>
		<td align='right'>".CUR." $tot3</td>
	</tr>";
	
	if (!$pure) { 
		$printOrd .= "
		".TBL_BR."
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>
			<tr>
				<td colspan='4'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</form>";
	}
	
	$printOrd .= "
	</table>";

	return $printOrd;
}


function export ($HTTP_POST_VARS) {
	$OUT = printPurch($HTTP_POST_VARS, true);
	$OUT = clean_html($OUT);

	require_lib("xls");
	Stream("Orders Received", $OUT);
}
?>

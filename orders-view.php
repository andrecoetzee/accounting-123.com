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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "view":
			$OUTPUT = printOrd($_POST);
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
		<h3>View Stock Orders</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td width='80%' align='center'>
					<input type='text' size='2' name='fday' maxlength='2'>-
					<input type='text' size='2' name='fmon' maxlength='2' value='".date("m")."'>-
					<input type='text' size='4' name='fyear' maxlength='4' value='".date("Y")."'>
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					<input type='text' size='2' name='today' maxlength='2' value='".date("d")."'>-
					<input type='text' size='2' name='tomon' maxlength='2' value='".date("m")."'>-
					<input type='text' size='4' name='toyear' maxlength='4' value='".date("Y")."'>
				</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='order-new.php'>New Order</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-report.php'>Stock Control Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
  		</table>";
        return $slct;

}



# show stock
function printOrd ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
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
		<h3>View Stock Orders</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Order Date</th>
				<th>Delivery Date</th>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Supplier</th>
				<th>Ordered Buying units</th>
				<th>Ordered Selling units</th>
				<th>Cost Amount</th>
				<th>Delevery Reference No.</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;

	$sql = "SELECT * FROM orders WHERE orddate >= '$fromdate' AND orddate <= '$todate' ORDER BY orddate DESC";
	$stkpRslt = db_exec ($sql) or errDie ("Unable to retrieve stock orders from database.");
	if (pg_numrows ($stkpRslt) < 1) {
		return "
			<li>There are no previous Stock orders.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='order-new.php'>New Order</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-report.php'>Stock Control Reports</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-view.php'>View Stock</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}
	while ($stkp = pg_fetch_array ($stkpRslt)) {

		# date format
		$date = explode("-", $stkp['orddate']);
		$date = $date[2]."-".$date[1]."-".$date[0];
		$deldate = explode("-", $stkp['deldate']);
		if(count($deldate) > 2){
			$deldate = $deldate[2]."-".$deldate[1]."-".$deldate[0];
		}else{
			$deldate = $stkp['deldate'];
		}

		$sql = "SELECT stkcod,stkdes,prdcls,buom,suom FROM stock WHERE stkid = '$stkp[stkid]'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if(pg_numrows($stkRslt) >0){
			$stk = pg_fetch_array($stkRslt);
			if($stkp['recved'] != "c"){
				$printOrd .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$date</td>
						<td>$deldate</td>
						<td>$stk[stkcod]</td>
						<td align='center'>$stk[stkdes]</td>
						<td>$stkp[supplier]</td>
						<td align='right'>$stkp[buom] x $stk[buom]</td>
						<td align='right'>$stkp[suom] x $stk[suom]</td>
						<td align='right'>".CUR." $stkp[csamt]</td>
						<td align='right'>$stkp[refno]</td>
						<td><a href='order-det.php?ordnum=$stkp[ordnum]'>Details</a></td>";

				if($stkp['recved'] == "no"){
					$printOrd .= "
							<td><a href='order-recv.php?ordnum=$stkp[ordnum]'>Received</a></td>
							<td><a href='order-cancel.php?ordnum=$stkp[ordnum]'>Cancel</a></td>
						</tr>";
				}else{
					$printOrd .= "</tr>";
				}
			}
		}else{
			$printOrd .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$date</td>
					<td></td>
					<td align='center'>Removed Stock</td>
					<td></td>
					<td>$stkp[supplier]</td>
					<td align='right'>$stkp[buom] x $stk[buom]</td>
					<td align='right'>$stkp[suom] x $stk[suom]</td>
					<td align='right'>".CUR." $stkp[csamt]</td>
				</tr>";
		}
		$i++;
	}

	$printOrd .= "
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='order-new.php'>New Order</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".bgcolorg()."'><td><a href='stock-report.php'>Stock Control Reports</a></td></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<tr bgcolor='".bgcolorg()."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";
	return $printOrd;

}


?>

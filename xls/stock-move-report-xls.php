<?

# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

require ("../settings.php");
require ("../libs/ext.lib.php");

if (isset($_GET["stkid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "view":
				$OUTPUT = printStk($_POST);
				break;
			case "report":
				$OUTPUT = report($_POST);
				break;
			default:
				$OUTPUT = slct();
				break;
		}
	} else {
		# Display default output
		$OUTPUT = slct();
	}
}

require ("../template.php");




# Default view
function slct()
{

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whid'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "There are no Warehouses found in Cubit.";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# Select the stock category
	db_connect();

	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
		}
	}
	$cats .= "</select>";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .= "</select>";

	//layout
	$view = "
		<h3>Stock Movement Report</h3>
		<table cellpadding='5'>
			<tr>
				<td>
					<table ".TMPL_tblDflts." width='400'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='view'>
						<tr>
							<th colspan='2'>Store</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center' colspan='2'>$whs</td>
						</tr>
						<tr><td><br></td></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center' colspan='2'>
								<input type='text' size='2' name='fday' maxlength='2' value='1'>-
								<input type='text' size='2' name='fmon' maxlength='2'  value='".date("m")."'>-
								<input type='text' size='4' name='fyear' maxlength='4' value='".date("Y")."'>
								&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
								<input type='text' size='2' name='today' maxlength='2' value='".date("d")."'>-
								<input type='text' size='2' name='tomon' maxlength='2' value='".date("m")."'>-
								<input type='text' size='4' name='toyear' maxlength='4' value='".date("Y")."'>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>All Categories and Classifications</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center' colspan='2'><input type='submit' name='all' value='View All'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}

# show stock
function printStk ($_POST)
{

	# get vars
	extract ($_POST);

//	print "$from_year-$from_month-$from_day -- $to_year-$to_month-$to_day";
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");
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



	# Get Stock
	db_connect ();
	$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "
			<li class='err'> There are no stock items found.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='sales-reports.php'>Sales Reports</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}

	db_conn("exten");
	# Get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	$totprof = 0;
	$totqty = 0;
	$totpqty = 0;
	$totcsprice = 0;
	$items = array();
	$movement = array();
	$totinc = 0;
	$totdec = 0;

	while ($stk = pg_fetch_array ($stkRslt)) {
		# Get all relevant records
		db_connect();
		$sql = "SELECT sum(qty) as qty, sum(csprice) as csprice, sum(csamt) as csamt FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stk[stkid]' AND trantype = 'invoice' AND div = '".USER_DIV."'";
		$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$rec = pg_fetch_array($recRslt);

		$sql = "SELECT sum(qty) as qty, sum(csprice) as csprice, sum(csamt) as csamt FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stk[stkid]' AND trantype = 'dec' AND div = '".USER_DIV."'";
		$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$dec = pg_fetch_array($recRslt);

		# Get all relevant records
		db_connect();
		$sql = "SELECT sum(qty) as qty, sum(csprice) as csprice, sum(csamt) as csamt FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stk[stkid]' AND trantype = 'note' AND div = '".USER_DIV."'";
		$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$nrec = pg_fetch_array($recRslt);

		# Get all relevant records
		db_connect();
		$sql = "SELECT sum(qty) as qty, sum(csprice) as csprice, sum(csamt) as csamt FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stk[stkid]' AND trantype = 'purchase' AND div = '".USER_DIV."'";
		$precRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$prec = pg_fetch_array($precRslt);

		$sql = "SELECT sum(qty) as qty, sum(csprice) as csprice, sum(csamt) as csamt FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stk[stkid]' AND trantype = 'inc' AND div = '".USER_DIV."'";
		$precRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$inc = pg_fetch_array($precRslt);

		$dec['qty'] += 0;
		$inc['qty'] += 0;

		$totinc += $inc['qty'];
		$totdec += $dec['qty'];

		$rec['csprice'] += $dec['csprice'] ;
		$rec['csamt'] += $dec['csamt'] ;

		# less credit notes
		$rec['qty'] -= $nrec['qty'];
		$rec['csprice'] -= $nrec['csprice'];
		$rec['csamt'] -= $nrec['csamt'];

		# zeros
		$rec['qty'] += 0;
		$rec['csprice'] += 0;
		$rec['csamt'] += 0;

		$prec['csprice'] += $inc['csprice'];
		$prec['csamt'] += $inc['csamt'];

		# zeros
		$prec['qty'] += 0;
		$prec['csprice'] += 0;
		$prec['csamt'] += 0;

		# Calculate profit
		$prof = ($rec['csprice'] - $rec['csamt']);
		$totprof += $prof;
		$totcsprice += $rec['csprice'];
		$totqty += $rec['qty'];
		$totpqty += $prec['qty'];

		# Limit to 30 chars
		$stk['stkdes'] = extlib_rstr($stk['stkdes'], 30);

		$item['stkcod'] = $stk['stkcod'];
		$item['stkdes'] = $stk['stkdes'];
		$item['pqty'] = $prec['qty'];
		$item['qty'] = $rec['qty'];
		$item['inc'] = $inc['qty'];
		$item['dec'] = $dec['qty'];
		$item['csprice'] = sprint($rec['csprice']);
		$item['profit'] = sprint($prof);
		$items[] = $item;

		if(isset($r_type)){

			if($prec['qty'] != 0){
				#calculate the ratio
				$ratio = ($rec['qty'] / $prec['qty']) * 100;
				$ratio = round($ratio,1);
			}else {
				$ratio = "0";
			}

			#make array for movement tracking
			$move['stkdes'] = $stk['stkdes'];
			$move['pqty'] = $prec['qty'];
			$move['qty'] = $rec['qty'];
			$move['ratio'] = $ratio;
			$movement[] = $move;
		}
	}

	# bubble sorting
	$sortarr = & $items; // where $out = array name to sort
	for ( $j = 0; $j < count($sortarr); $j++ ) {
		for ( $i = 0; $i < count($sortarr) - 1; $i++ ) {
			if ( $sortarr[$i]['qty'] < $sortarr[$i + 1]['qty'] ) {
				$buf = $sortarr[$i];
				$sortarr[$i] = $sortarr[$i + 1];
				$sortarr[$i + 1] = $buf;
			}
		}
	}

	if(isset($r_type)){
		# bubble sorting for movement
		$sortarr2 = & $movement; // where $out = array name to sort
		for ( $j = 0; $j < count($sortarr2); $j++ ) {
			for ( $i = 0; $i < count($sortarr2) - 1; $i++ ) {
				if(isset($r_type) AND ($r_type == 'fast')){
					if ( $sortarr2[$i]['qty'] < $sortarr2[$i + 1]['qty'] ) {
						$buf = $sortarr2[$i];
						$sortarr2[$i] = $sortarr2[$i + 1];
						$sortarr2[$i + 1] = $buf;
					}
				}else {
					if ( $sortarr2[$i]['qty'] > $sortarr2[$i + 1]['qty'] ) {
						$buf = $sortarr2[$i];
						$sortarr2[$i] = $sortarr2[$i + 1];
						$sortarr2[$i + 1] = $buf;
					}
				}
			}
		}

		if(isset($r_type) AND ($r_type == 'fast')){
			$moveheader = "Fast Moving Stock";
		}else {
			$moveheader = "Slow Moving Stock";
		}
	}

	$totprof=sprint($totprof);
	$totcsprice=sprint($totcsprice);

	// Layout
	$report = "
		<h3>Stock Movement Report</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Purchased</th>
				<th>Sold</th>
				<th>Increase</th>
				<th>Decrease</th>
				<th>Total Selling Price</th>
				<th>Gross Profit</th>
			</tr>";

	foreach($items as $key => $item){
		$report .= "
			<tr>
				<td>$item[stkcod]</td>
				<td>$item[stkdes]</td>
				<td align='right'>".sprint3($item['pqty'])."</td>
				<td align='right'>".sprint3($item['qty'])."</td>
				<td align='right'>".sprint3($item['inc'])."</td>
				<td align='right'>".sprint3($item['dec'])."</td>
				<td align='right'>".CUR." $item[csprice]</td>
				<td align='right'>".CUR." $item[profit]</td>
			</tr>";
	}

	$report .= "
		<tr><td><br></td></tr>
		<tr>
			<td colspan='2'><b>Totals</b></td>
			<td align='right'>".sprint3($totpqty)."</td>
			<td align='right'>".sprint3($totqty)."</td>
			<td align='right'>".sprint3($totinc)."</td>
			<td align='right'>".sprint3($totdec)."</td>
			<td align='right'>".CUR." $totcsprice</td>
			<td align='right'>".CUR." $totprof</td>
		</tr>";

	if(isset($r_type)){
		$report .= "
		<tr><td colspan='8'><br></td></tr>
		<tr><th colspan='8'>$moveheader</th></tr>
		<tr>
			<th>Stock</th>
			<th>Available</th>
			<th>Decrease</th>
			<th colspan='2'>Ratio</th>
		</tr>";

		foreach($movement as $key => $move){
			$report .= "
				<tr>
					<td>$move[stkdes]</td>
					<td align='right'>".sprint3($move['pqty'])."</td>
					<td align='right'>".sprint3($move['qty'])."</td>
					<td align='right' colspan='2'>$move[ratio] %</td>
				</tr>";
		}
	}

	$report .= "</table>";

	include("temp.xls.php");
	Stream("Report", $report);

	return $report;

}



?>

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
require ("libs/ext.lib.php");

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
			case "export":
				$OUTPUT = export($_POST);
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

require ("template.php");



# Default view
function slct($err = "")
{

	$defwhid = getSetting("DEF_WH");

	$warehouses = qryWarehouse();
	$whs = db_mksel($warehouses, "whid", $defwhid, "#whid", "(#whno) #whname");

	$stockcats = qryStockCat();
	$cats = db_mksel($stockcats, "catid", false, "#catid", "(#catcod) #cat");

	$stockclass = qryStockClass();
	$class = db_mksel($stockclass, "clasid", false, "#clasid", "#classname");

	$view = "
				<h3>Stock Sales Report</h3>
				$err
				<table cellpadding='5'>
					<tr>
						<td>
							<table ".TMPL_tblDflts.">
								<form action='".SELF."' method=post name=form>
								<input type=hidden name=key value=view>
								<tr>
									<th colspan='2'>Store</th>
								</tr>
								<tr class='".bg_class()."'>
									<td align=center colspan=2>$whs</td>
								</tr>
								".TBL_BR."
								<tr>
									<th colspan='2'>By Category</th>
								</tr>
								<tr class='".bg_class()."'>
									<td align='center'>$cats</td>
									<td valign='bottom'><input type=submit name=cat value='View'></td>
								</tr>
								".TBL_BR."
								<tr>
									<th colspan='2'>By Classification</th>
								</tr>
								<tr class='".bg_class()."'>
									<td align='center'>$class</td>
									<td valign='bottom'><input type='submit' name='class' value='View'></td>
								</tr>
								".TBL_BR."
								<tr>
									<th colspan='2'>All Categories and Classifications</th>
								</tr>
								<tr class='".bg_class()."'>
									<td align='center' colspan='2'><input type='submit' name='all' value='View All'></td>
								</tr>
								</form>
							</table>
						</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1' width='15%'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='stock-add.php'>Add Stock</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $view;

}



# show stock
function printStk($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND catid = '$catid' ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND prdcls = '$clasid' ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' ORDER BY stkcod ASC";
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



	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "<li class='err'> There are no stock items found.</li>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-view.php'>Back</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	}

	# select Stock
	$stock = "
				<select name='stkids[]' style='width: 120' multiple size='10'>
					<option value='all'> All</option>";

	$send="";

	while ($stk = pg_fetch_array ($stkRslt)) {
		$stock .= "<option value='$stk[stkid]'>$stk[stkcod]</option>";
		$send .= "<input type='hidden' name='alls[]' value='$stk[stkid]'>";
	}
	$stock .= "</select>";

	# Set up table to display in
	$printStk = "
					<h3>Stock Sales Report</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='report'>
						$send
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Select Stock Item</th>
						</tr>
						<tr>
							<td colspan='2' class='".bg_class()."' align='center'>$stock</td>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center' colspan='2'>
								".mkDateSelect("from",date("Y"),date("m"),"01")."
								&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
								".mkDateSelect("to")."
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
							<td valign='center'><input type='submit' value='Continue &raquo'></td>
						</tr>
					</table>
					</form>
					<p>
					<table ".TMPL_tblDflts." width='15%'>
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='stock-add.php'>Add Stock</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $printStk;

}



function report($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new  validate ();
	//$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}

	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	if (!isset($stkids)) {
		$v->addError("", "Please select at least on stock item.");
	}

	if ($v->isError()) {
		return slct($v->genErrors());
	}

	$freport="";

	if (in_array('all',$stkids)) {
		$stkids = $alls;
	}

	$ss="";

	foreach ($stkids as $stkid) {
		$ss .= "<input type='hidden' name='stkids[]' value='$stkid'>";

		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid'";
		$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if (pg_numrows($stkRslt) < 1) {
			return "<li> Invalid Stock ID.</li>";
		} else {
			$stk = pg_fetch_array($stkRslt);
		}

		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		db_connect();
		$sql = "SELECT * FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stkid' AND (trantype = 'invoice' OR trantype='note') ORDER BY edate DESC";
		$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$records = "";
		$totprof = 0;
		$totprice = 0;
		$totqty = 0;

		while($rec = pg_fetch_array($recRslt)){
			$rec['edate'] = explode("-", $rec['edate']);
			$rec['edate'] = $rec['edate'][2]."-".$rec['edate'][1]."-".$rec['edate'][0];

			if ($rec['trantype']=="note") {
				$rec['qty']=-$rec['qty'];
				$rec['csprice']=-$rec['csprice'];
				$rec['csamt']=-$rec['csamt'];
			}

			$totqty += $rec['qty'];
			$prof = ($rec['csprice'] - $rec['csamt']);
			$totprof += $prof;
			$totprice += $rec['csprice'];

			$records .= "
			<tr class='".bg_class()."'>
				<td>$rec[edate]</td>
				<td>$rec[details]</td>
				<td>".sprint3($rec['qty'])."</td>
				<td>" . CUR . sprint($rec["csprice"]) . "</td>
				<td>" . CUR . sprint($prof) . "</td>
			</tr>";
		}

		$totprice = sprint($totprice);
		$totprof = sprint($totprof);
		$totqty = sprint3($totqty);

		$freport .= "
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'>Details</th>
							</tr>
							<tr class='".bg_class()."'>
								<td>Warehouse</td>
								<td>$wh[whname]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Stock code</td>
								<td>$stk[stkcod]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Stock description</td>
								<td>".nl2br($stk['stkdes'])."</pre></td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Category</td>
								<td>$stk[catname]</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Category</td>
								<td>$stk[classname]</td>
							</tr>
						</table>
						<p>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Date</th>
								<th>Details</th>
								<th>Quantity</th>
								<th>Selling Price</th>
								<th>Gross Profit</th>
							</tr>
							$records
							<tr class='".bg_class()."'>
								<td colspan='2'><b>Totals</b></td>
								<td>$totqty</td>
								<td>".CUR." $totprice</td>
								<td>".CUR." $totprof</td>
							</tr>
							".TBL_BR;
	}

	$report = "
				<h3>Stock Sales Report</h3>
				<table ".TMPL_tblDflts.">
					$freport
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='export'>
					$ss
					<input type='hidden' name='fday' value='$from_day'>
					<input type='hidden' name='fmon' value='$from_month'>
					<input type='hidden' name='fyear' value='$from_year'>
					<input type='hidden' name='today' value='$to_day'>
					<input type='hidden' name='tomon' value='$to_month'>
					<input type='hidden' name='toyear' value='$to_year'>
					<tr>
						<td colspan='2'><input type='submit' value='Export to Spreadsheet'></td>
					</tr>
				</table>"
				.mkQuickLinks(
					ql("stock-sales-rep.php", "Sales Report")
				);
	return $report;

}




function export($_POST)
{

	# get stock vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	//$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
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



	if(in_array('all',$stkids)) {
		$stkids=$alls;
	}

	$report="<h3>Stock Sales Report</h3>";

	foreach($stkids as $stkid) {

		$stkid+=0;
		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid'";
		$stkRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.</li>";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}

		db_conn("exten");
		# get warehouse
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# Get all relevant records
		db_connect();
		$sql = "SELECT * FROM stockrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND stkid = '$stkid' AND (trantype = 'invoice' OR trantype='note') ORDER BY edate DESC";
		$recRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		$records = "";
		$totprof = 0;
		$totprice = 0;
		$totqty = 0;
		while($rec = pg_fetch_array($recRslt)){
			# format date
			$rec['edate'] = explode("-", $rec['edate']);
			$rec['edate'] = $rec['edate'][2]."-".$rec['edate'][1]."-".$rec['edate'][0];

			if($rec['trantype']=="note") {
				$rec['qty']=-$rec['qty'];
				$rec['csprice']=-$rec['csprice'];
				$rec['csamt']=-$rec['csamt'];
			}

			# recods
			$totqty += $rec['qty'];
			$prof = ($rec['csprice'] - $rec['csamt']);
			$totprof += $prof;
			$totprice += $rec['csprice'];

			$records .= "
							<tr>
								<td>$rec[edate]</td>
								<td>$rec[details]</td>
								<td>$rec[qty]</td>
								<td>".CUR." $rec[csprice]</td>
								<td>".CUR." $prof</td>
							</tr>";
		}

		// Layout
		$report .= "
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='2'>Details</th>
							</tr>
							<tr>
								<td>Warehouse</td>
								<td>$wh[whname]</td>
							</tr>
							<tr>
								<td>Stock code</td>
								<td>$stk[stkcod]</td>
							</tr>
							<tr>
								<td>Stock description</td>
								<td>".nl2br($stk['stkdes'])."</pre></td>
							</tr>
							<tr>
								<td>Category</td>
								<td>$stk[catname]</td>
							</tr>
							<tr>
								<td>Category</td>
								<td>$stk[classname]</td>
							</tr>
						</table>
						<p>
						<table ".TMPL_tblDflts." width='70%'>
							<tr>
								<th>Date</th>
								<th>Details</th>
								<th>Quantity</th>
								<th>Selling Price</th>
								<th>Gross Profit</th>
							</tr>
							$records
							<tr>
								<td colspan='2'><b>Totals</b></td>
								<td>$totqty</td>
								<td>".CUR." $totprice</td>
								<td>".CUR." $totprof</td>
							</tr>
						</table>";
		$report .="<tr><td><br></td></tr>";
	}



	$OUTPUT=$report;

	include("xls/temp.xls.php");
	Stream("Report", $OUTPUT);

	return $report;

}


?>

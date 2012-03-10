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

if (isset($HTTP_GET_VARS["stkid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "view":
				$OUTPUT = printStk($HTTP_POST_VARS);
				break;
			case "report":
				$OUTPUT = report($HTTP_POST_VARS);
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
function slct()
{

// 	db_connect ();
// 
// 	// Retrieve stores
// 	$sql = "SELECT whid, whno, whname FROM exten.warehouses ORDER BY whno ASC";
// 	$stores_rslt = db_exec($sql) or errDie("Unable to retrieve stores.");
	
// 	$stores_sel = "
// 		<select name='whid' style='width: 100%' />
// 			<option value='0'>[All]</option>";
// 	while ($stores_data = pg_fetch_array($stores_rslt)) {
// 		if ($stores_data["whid"] == $whid){
// 			$stores_sel .= "<option value='$stores_data[whid]' selected>($stores_data[whno]) $stores_data[whname]</option>";
// 		}else {
// 			$stores_sel .= "<option value='$stores_data[whid]'>($stores_data[whno]) $stores_data[whname]</option>";
// 		}
// 	}
// 	$stores_sel .= "</select>";

	// Create categories dropdown
// 	$sql = "SELECT catid, catcod, cat FROM cubit.stockcat ORDER BY cat ASC";
// 	$categories_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");
	
// 	$categories_sel = "
// 		<select name='catid' style='width: 100%'>
// 			<option value='0'>[All]</option>";
// 	while ($categories_data = pg_fetch_array($categories_rslt)) {
// 		if ($categories_data["catid"] == $catid) {
// 			$sel = "selected='selected'";
// 		} else {
// 			$sel = "";
// 		}
// 		$categories_sel .= "<option value='$categories_data[catid]' $sel>($categories_data[catcod]) $categories_data[cat]</option>";
// 	}
// 	$categories_sel .= "</select>";

	// Create classifications dropdown
// 	$sql = "SELECT clasid, classname FROM cubit.stockclass ORDER BY classname ASC";
// 	$classifications_rslt = db_exec($sql) or errDie("Unable to retrieve classifications.");
	
// 	$classifications_sel = "
// 		<select name='clasid' style='width: 100%'>
// 			<option value='0'>[All]</option>";
// 	while ($classifications_data = pg_fetch_array($classifications_rslt)) {
// 		if ($classifications_data["clasid"] == $clasid) {
// 			$sel = "selected='selected'";
// 		} else {
// 			$sel = "";
// 		}
// 		$classifications_sel .= "<option value='$classifications_data[clasid]' $sel>$classifications_data[classname]</option>";
// 	}
// 	$classifications_sel .= "</select>";

// 	$sql = "SELECT surname FROM cubit.customers ORDER BY cusname ASC";
// 	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

// 	$cust_sel = "
// 		<select name='cust_name' style='width: 100%'>
// 			<option value='[All]'>[All]</option>";
// 	while ($cust_data = pg_fetch_array($cust_rslt)) {
// 		if ($cust_name == $cust_data["surname"]) {
// 			$sel = "selected";
// 		} else {
// 			$sel = "";
// 		}
// 		$cust_sel .= "<option value='$cust_data[surname]' $sel>$cust_data[surname]</option>";
// 	}
// 	$cust_sel .= "</select>";

// 			<tr>
// 				<th>Store</th>
// 				<th>Category</th>
// 			</tr>
// 			<tr bgcolor='".bgcolorg()."'>
// 				<td>$stores_sel</td>
// 				<td>$categories_sel</td>
// 			</tr>
// 			<tr>
// 				<th>Classification</th>
// 				<th>Customer</th>
// 			</tr>
// 			<tr bgcolor='".bgcolorg()."'>
// 				<td>$classifications_sel</td>
// 				<td>$cust_sel</td>
// 			</tr>

	//layout
	$view = "
		<P><P>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>Stock Sales Report</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' colspan='2'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' colspan='2'><input type='submit' value='View'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='sales-reports.php'>Sales Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}




# show stock
function printStk ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
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



	# Get all stock sales
	db_connect();

	$sql = "SELECT sum(vat) as totvat, sum(total) as tot, sum(total - vat) as totexc FROM salesrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND typ = 'stk' AND div = '".USER_DIV."'";
	$recRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	$rec = pg_fetch_array($recRslt);

	$tot = sprint($rec['tot']);
	$totvat = sprint($rec['totvat']);
	$totexc = sprint($rec['totexc']);

	# Get all stock credit notes
	db_connect();
	$sql = "SELECT sum(vat) as ctotvat, sum(total) as ctot, sum(total - vat) as ctotexc FROM salesrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND typ = 'nstk' AND div = '".USER_DIV."'";
	$recRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	$rec = pg_fetch_array($recRslt);

	$ctot = sprint($rec['ctot']);
	$ctotvat = sprint($rec['ctotvat']);
	$ctotexc = sprint($rec['ctotexc']);

	# Get all non-stock sales
	db_connect();

	$sql = "SELECT sum(vat) as ntotvat, sum(total) as ntot, sum(total - vat) as ntotexc FROM salesrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND typ = 'non' AND div = '".USER_DIV."'";
	$recRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	$rec = pg_fetch_array($recRslt);

	$ntot = sprint($rec['ntot']);
	$ntotvat = sprint($rec['ntotvat']);
	$ntotexc = sprint($rec['ntotexc']);

	# Get all non-stock credit notes
	db_connect();

	$sql = "SELECT sum(vat) as cntotvat, sum(total) as cntot, sum(total - vat) as cntotexc FROM salesrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND typ = 'nnon' AND div = '".USER_DIV."'";
	$recRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	$rec = pg_fetch_array($recRslt);

	$cntot = sprint($rec['cntot']);
	$cntotvat = sprint($rec['cntotvat']);
	$cntotexc = sprint($rec['cntotexc']);


	# actual stock sales
	$stktot = sprint($tot - $ctot);
	$stktotvat = sprint($totvat - $ctotvat);
	$stktotexc = sprint($totexc - $ctotexc);

	# actual non-stock sales
	$nontot = sprint($ntot - $cntot);
	$nontotvat = sprint($ntotvat - $cntotvat);
	$nontotexc = sprint($ntotexc - $cntotexc);

	# total actual sales
	$ttot = sprint($nontot + $stktot);
	$ttotvat = sprint($nontotvat + $stktotvat);
	$ttotexc = sprint($nontotexc + $stktotexc);

	# connect to database
	db_connect ();

	// Layout
	$report = "
		<h3>Total Sales $fromdate TO $todate</h3>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
				<td><h3>Stock</h3></td>
			</tr>
			<tr>
				<td colspan='2'></td>
				<th>VAT</th>
				<th>SubTotal</th>
				<th>Total</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total Stock Sales</b></td>
				<td>".CUR." $totvat</td>
				<td>".CUR." $totexc</td>
				<td>".CUR." $tot</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total Stock Credit Notes</b></td>
				<td>".CUR." $ctotvat</td>
				<td>".CUR." $ctotexc</td>
				<td>".CUR." $ctot</td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total Stock Sales after Credit Notes</b></td>
				<td>".CUR." $stktotvat</td>
				<td>".CUR." $stktotexc</td>
				<td>".CUR." $stktot</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr>
				<td><h3>Non-Stock</h3></td>
			</tr>
			<tr>
				<td colspan='2'></td>
				<th>VAT</th>
				<th>SubTotal</th>
				<th>Total</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total Non-Stock Sales</b></td>
				<td>".CUR." $ntotvat</td>
				<td>".CUR." $ntotexc</td>
				<td>".CUR." $ntot</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total Non-Stock Credit Notes</b></td>
				<td>".CUR." $cntotvat</td>
				<td>".CUR." $cntotexc</td>
				<td>".CUR." $cntot</td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Total Non-Stock Sales after Credit Notes</b></td>
				<td>".CUR." $nontotvat</td>
				<td>".CUR." $nontotexc</td>
				<td>".CUR." $nontot</td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>Actual Sales</b></td>
				<td>".CUR." $ttotvat</td>
				<td>".CUR." $ttotexc</td>
				<td>".CUR." $ttot</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='sales-reports.php'>Sales Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
				</tr>
		</table>";
	return $report;

}


?>

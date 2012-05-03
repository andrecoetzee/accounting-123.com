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
			$OUTPUT = printStk($_POST);
			break;
		case "export":
			$OUTPUT = export();
			break;
		case "remove":
			$OUTPUT = remove ($_POST);
			break;
		case "confirmremove":
			$OUTPUT = confirmremove ($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
	$OUTPUT = slct();
}

require ("template.php");





function slct()
{

	db_conn('cubit');

	$sql = "SELECT count(stkid) FROM stock WHERE div='".USER_DIV."'";
	$Rx = db_exec($sql) or errDie("Unable to get stock from db.");

	db_conn("exten");

	$whs = "
		<select name='whid[]' multiple size='5'>
			<option value='0'>All</option>";
	$warehouses = qryWarehouse();
	if ($warehouses->num_rows() < 1) {
		return "There are no Warehouses found in Cubit.";
	} else {
		while($wh = $warehouses->fetch_array()){
			$whid = $wh['whid'];
			$whs .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	db_connect();

	$cats = "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if (pg_numrows($catRslt) < 1) {
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		$cats .= "<option value='0'>All</option>";
		while($cat = pg_fetch_array($catRslt)){
			$catid = $cat['catid'];
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
		$class .= "<option value='0'>All</option>";
		while($clas = pg_fetch_array($clasRslt)){
			$classid = $clas['clasid'];
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .= "</select>";

	if($warehouses->num_rows() == 1) {
		$Sl = "SELECT stkid FROM stock";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 50) {
			$_POST["whid"] = 0;
			$_POST["catid"] = 0;
			$_POST["clasid"] = 0;
			$_POST["key"] = "view";
			return printStk($_POST);
		}
	}

	//layout
	$view = "
		<h3>View Stock</h3>
		<table cellpadding='5'>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='view'>
						<tr>
							<th>Store</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$whs</td>
						</tr>
						".TBL_BR."
						<tr>
							<th>By Category</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$cats</td>
						</tr>
						".TBL_BR."
						<tr>
							<th>By Classification</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$class</td>
						</tr>
						".TBL_BR."
						<tr>
							<th>Sort By</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='sortby' value='normal' checked='yes'> Normal</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='sortby' value='cat'> Category</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='sortby' value='class'> Classification</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='right'><input type='submit' name='all' value='View &raquo;'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>
		<script>
			
		</script>";
	return $view;

}



function printStk ($_POST,$errs="")
{

	extract($_POST);

	$fields = array();
	$fields["search_val"] = "[_BLANK_]";

	extract ($fields, EXTR_SKIP);

	if(!isset($whid) OR (count($whid) < 1))
		return slct ();

	if(!is_array($whid)){
		$temp = $whid;
		$whid = array();
		$whid[] = $temp;
	}

	if(!isset($sortby))
		$sortby = "normal";

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
	$v->isOk ($sortby, "string", 1, 10, "Invalid Sort Selection.");

	foreach ($whid as $temp){
		$v->isOk ($temp, "num", 1, 50, "Invalid Warehouse.");
	}

	$Whe = "";
	if($catid != 0){
		$Whe .= " AND catid = '$catid'";
	}
	if($clasid != 0){
		$Whe .= " AND prdcls = '$clasid'";
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



	if(!isset($sortby)){
		$sortby = "";
	}
	$sel1 = "";
	$sel2 = "";
	$sel3 = "";
	if($sortby == "cat"){
		$sel2 = "checked='yes'";
	}elseif($sortby == "class"){
		$sel3 = "checked='yes'";
	}else {
		$sel1 = "checked='yes'";
	}

	$whids = "";
	foreach($whid as $temp){
		$whids .= "<input type='hidden' name='whid[]' value='$temp'>";
	}

	if ($key == "export") {
		$pure = true;
	} else {
		$pure = false;
	}

	$Whe .= " AND ((lower(stkcod) LIKE lower('%$search_val%')) OR (lower(stkdes) LIKE lower('%$search_val%')))";

	if ($search_val == "[_BLANK_]") $search_val = "";

	# Set up table to display in
	if ($pure) {
		$OUT = "<table ".TMPL_tblDflts.">";
	} else {
		$OUT = "
			<h3>View Stock</h3>
			$errs
			<table ".TMPL_tblDflts." width='30%'>
			<form action='".SELF."' method='POST' name='form1'>
				<input type='hidden' name='key' value='view'>
				<input type='hidden' name='catid' value='$catid'>
				<input type='hidden' name='clasid' value='$clasid'>
				<input type='hidden' name='search_val' value='$search_val'>
				$whids
				<tr>
					<th>Sort By:</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>
						<input type='radio' name='sortby' $sel1 value='normal' onChange='javascript:document.form1.submit();'> Normal
						<input type='radio' name='sortby' $sel2 value='cat' onChange='javascript:document.form1.submit();'> Category
						<input type='radio' name='sortby' $sel3 value='class' onChange='javascript:document.form1.submit();'> Classification
					</td>
				</tr>
				".TBL_BR."
				<tr>
					<th>Search</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>
						<input type='text' size='25' name='search_val' value='$search_val'> 
						<input type='submit' value='Search'>
					</tr>
				".TBL_BR."
			</form>
			</table>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form2'>
				<input type='hidden' name='key' value='remove'>";
	}

	#search parms
	if($sortby == "cat"){
		$Ord = "catname,stkcod";
	}elseif ($sortby == "class"){
		$Ord = "classname,stkcod";
	}else {
		$Ord = "stkcod";
	}

	$stores = array();
	if($whid != "0"){
		foreach($whid as $temp){
			if ($temp != 0)
				$stores[] = " whid = '$temp'";
		}
		if (count($stores) > 0) {
			$stores = implode(" OR ", $stores);
		} else {
			$stores = "true";
		}
	}else {
		$stores = "true";
	}

	# connect to database
	db_connect ();

	if (!isset ($offset))
		$offset = 0;
	if(isset($next))
		$offset = $offset + 100;
	if(isset($prev))
		$offset = $offset - 100;
	if($offset < 0)
		$offset = 0;

	if($offset != 0){
		$prev_but = "<input type='submit' name='prev' value='Previous'>";
	}else {
		$prev_but = "";
	}

	if (isset($complete)) {
		$limit = "";
	} else {
		$limit = "LIMIT 100 OFFSET $offset";
	}

	// Retrieve store name from the database
	db_conn("exten");

//	$sql = "SELECT whname FROM warehouses WHERE whid='$stk[whid]'";
//	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve warehouses from Cubit.");
//	$whname = pg_fetch_result($wh_rslt, 0);


	$sql = "SELECT whid, whname FROM warehouses";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve warehouses from Cubit.");
	if (pg_numrows($wh_rslt) < 1){
		#no warehouses found ???
		$wharr = array ();
	}else {
		while ($arr = pg_fetch_array ($wh_rslt)){
			$wharr[$arr['whid']] = $arr['whname'];
		}
	}

	db_connect ();

	# Query server
	$i = 0;
	$searchs = "SELECT * FROM stock WHERE ($stores) AND div = '".USER_DIV."' $Whe ORDER BY $Ord ASC $limit";
	$stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");

	if (pg_numrows ($stkRslt) < 1) {
		$OUT .= "
			<tr>
				<li class='err'> No Stock Items Found. Please enter the first few letters of the stock item</li></td>
			</tr>";

//		return "
//			<li class='err'> There are no stock items.</li>
//			<p>
//			<table ".TMPL_tblDflts." width='15%'>
//				".TBL_BR."
//				<tr><th>Quick Links</th></tr>
//				<tr class='".bg_class()."'>
//					<td><a href='stock-view.php'>Back</a></td>
//				</tr>
//				<tr class='".bg_class()."'>
//					<td><a href='stock-add.php'>Add Stock</a></td>
//				</tr>
//				<tr class='".bg_class()."'>
//					<td><a href='main.php'>Main Menu</a></td>
//				</tr>
//			</table>";
	}

	if ((pg_numrows($stkRslt) > 0) AND (pg_numrows($stkRslt) == 100)){
		$next_but = "<input type='submit' name='next' value='Next'>";
	}else {
		$next_but = "";
	}

	$heading = "";
	$showheading = "";

	$tot_unit = 0;
	$tot_amt = 0;
	$tot_aloc = 0;
	$tot_order = 0;

	while ($stk = pg_fetch_array ($stkRslt)) {

		$serd = ($stk['serd'] == 'yes') ? ($stk['units'] > 0) ? "<a href='stock-serials.php?stkid=$stk[stkid]'>Allocate Serial No.</a>" : "<br>" : "<br>";

		$stk['selamt'] = sprint($stk['selamt']);

		if($sortby == "cat"){
			if($stk['catname'] == $heading){
				$showheading = "";
			}else {
				$showheading = "
					<tr>
						<td><font size='3' color='white'><b>$stk[catname]</b></font></td>
					</tr>
					<tr>
						<th>Store</th>
						<th>Code</th>
						<th>Description</th>
						<th>Class</th>
						<th>Category</th>
						<th>On Hand</th>
						<th>Retail Price</th>
						<th>Allocated</th>
						<th>On order</th>
						".($pure?"":"<th colspan='10'>Options</th><th>Remove</th>")."
					</tr>";
			}
		}elseif($sortby == "class"){
			if($stk['classname'] == $heading){
				$showheading = "";
			}else {
				$showheading = "
					<tr>
						<td><font size='3' color='white'><b>$stk[classname]</b></font></td>
					</tr>
					<tr>
						<th>Store</th>
						<th>Code</th>
						<th>Description</th>
						<th>Class</th>
						<th>Category</th>
						<th>On Hand</th>
						<th>Retail Price</th>
						<th>Allocated</th>
						<th>On order</th>
						".($pure?"":"<th colspan='10'>Options</th><th>Remove</th>")."
					</tr>";
			}
		}else {
			if($heading == "normal"){
				$showheading = "";
			}else {
				$showheading = "
					<tr>
						<th>Store</th>
						<th>Code</th>
						<th>Description</th>
						<th>Class</th>
						<th>Category</th>
						<th>On Hand</th>
						<th>Retail Price</th>
						<th>Allocated</th>
						<th>On order</th>
						".($pure?"":"<th colspan='10'>Options</th><th>Remove</th>")."
					</tr>";
			}
		}

		if (key_exists($stk['whid'], $wharr))
			$whname = $wharr[$stk['whid']];
		else 
			$whname = "";

			$OUT .= $showheading;
			$OUT .= "
				<tr class='".bg_class()."'>
					<td>$whname</td>
					<td>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td>$stk[classname]</td>
					<td>$stk[catname]</td>
					<td align='right'>".sprint3($stk['units'])."</td>
					<td align='right' nowrap>".CUR." $stk[selamt]</td>
					<td align='right'>".sprint3($stk['alloc'])."</td>
					<td align='right'>".sprint3($stk['ordered'])."</td>";

			#calculate some totals
			if ($stk['units'] > 0)
				$tot_unit += $stk['units'];
			if ($stk['selamt'] > 0)
				$tot_amt += ($stk['units'] * $stk['selamt']);
			if ($stk['alloc'] > 0)
				$tot_aloc += $stk['alloc'];
			if ($stk['ordered'] > 0)
				$tot_order += $stk['ordered'];

			if (!$pure) {
				// Check if we've got a recipe
				$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$stk[stkid]'";
				$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

				// Create a link if neccessary
				if (pg_num_rows($recipe_rslt)) {
					$manu_href = "<a href='manu_stock.php?m_stock_id=$stk[stkid]&key=manuout'>Manufacture</a>";
					$unmanu_href = "<a href='manu_stock.php?m_stock_id=$stk[stkid]&key=unmanuout'>Disassemble</a>";
				} else {
					$manu_href = "";
					$unmanu_href = "<a href='manu_stock.php?m_stock_id=$stk[stkid]&key=unmanuout'>Disassemble</a>";
				}

				$OUT .= "
					<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>Report</a></td>
					<td><a href='stock-det.php?stkid=$stk[stkid]'>Details</a></td>
					<td><a href='stock-edit.php?stkid=$stk[stkid]'>Edit</a></td>
					<td><a href='stock-balance.php?stkid=$stk[stkid]'>Transaction</a></td>
					<td>$serd</td>
					<td><a href='pos.php?id=$stk[stkid]'>Barcode</a></td>
					<td>$manu_href</td>
					<td>$unmanu_href</td>";

				if($stk['blocked'] == 'y'){
					$OUT .= "<td><a href='stock-unblock.php?stkid=$stk[stkid]'>Unblock</a></td>";
				}else{
					$OUT .= "<td><a href='stock-block.php?stkid=$stk[stkid]'>Block</a></td>";
				}

				if(($stk['units'] < 1) && ($stk['alloc'] < 1) && ($stk['lcsprice'] == 0) && ($stk['csprice'] == 0)){
					$OUT .= "
							<td><a href='stock-rem.php?stkid=$stk[stkid]'>Remove</a></td>
							<td><input type='checkbox' name='remids[]' value='$stk[stkid]'></td>
						</tr>";
				}elseif($stk['alloc'] > 0){
					$OUT .= "
							<td><a href='#' onclick='openwindow(\"stock-alloc.php?stkid=$stk[stkid]\")'>View Allocation</a></td>
							<td></td>
						</tr>";
				}else{
					$OUT .= "
							<td></td>
							<td></td>
						</tr>";
				}
			}

			if($sortby == "cat"){
				$heading = $stk['catname'];
			}elseif($sortby == "class"){
				$heading = $stk['classname'];
			}else {
				$heading = "normal";
			}

	}
	
	$OUT .= "
		<tr class='".bg_class()."'>
			<td colspan='5'>Totals: (Only Positive Amounts)</td>
			<td nowrap align='right'>".sprint3($tot_unit)."</td>
			<td nowrap align='right'>".CUR." ".sprint ($tot_amt)."</td>
			<td nowrap align='right'>".sprint3($tot_aloc)."</td>
			<td nowrap align='right'>".sprint3($tot_order)."</td>
		</tr>";

	r2sListSet("stock_view");

	if (!$pure) {
		$OUT .= "
				<tr>
					<td colspan='20' align='right'><input type='submit' value='Remove Selected'></td>
				</tr>
			</form>

			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='view'>
				$whids
				<input type='hidden' name='offset' value='$offset'>
				<input type='hidden' name='catid' value='$catid'>
				<input type='hidden' name='clasid' value='$clasid'>
				<input type='hidden' name='sortby' value='$sortby'>
				<input type='hidden' name='search_val' value='$search_val'>
				<tr>
					<td>$prev_but</td>
					<td colspan='3'></td>
					<td>$next_but</td>
				</tr>
			</form>

			<form action ='".SELF."' method='POST'>
				<input type='hidden' name='key' value='export'>
				<input type='hidden' name='catid' value='$catid'>
				<input type='hidden' name='clasid' value='$clasid'>
				<input type='hidden' name='sortby' value='$sortby'>
				$whids
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Export to Spreadsheet'></td>
				</tr>
			</form>

			<form method='post' action='".SELF."'>
				<input type='hidden' name='key' value='view' />
				$whids
				<input type='hidden' name='offset' value='$offset' />
				<input type='hidden' name='catid' value='$catid' />
				<input type='hidden' name='clasid' value='$clasid' />
				<input type='hidden' name='sortby' value='$sortby' />
				<input type='hidden' name='search_val' value='$search_val' />
				<tr>
					<td><input type='submit' name='complete' value='Display All' /></td>
				</tr>
			</form>
			</table>
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
			</table>
			<script>
				document.form1.search_val.focus();
			</script>";
	}else {
		$OUT .= "
			</form>
			</table>";
	}
	return $OUT;

}



function remove ($_POST)
{

	extract ($_POST);

	if(!isset($remids) OR !is_array($remids)){
		return printStk ($_POST,"<li class='err'>Please Select At Least 1 Item To Remove.</li>");
	}

	db_connect ();

	$listing = "";
	$passon = "";

	foreach ($remids AS $each){
		
		$passon .= "<input type='hidden' name='remids[]' value='$each'>";
		
		$get_stock = "SELECT * FROM stock WHERE stkid = '$each' LIMIT 1";
		$run_stock = db_exec($get_stock) or errDie("Unable to get stock information");
		if(pg_numrows($run_stock) < 1){
			$listing .= TBL_BR;
		}else {
			$sarr = pg_fetch_array($run_stock);
			$sarr['selamt'] = sprint ($sarr['selamt']);
			$listing .= "
				<tr class='".bg_class()."'>
					<td>$sarr[stkcod]</td>
					<td>$sarr[stkdes]</td>
					<td>".CUR." $sarr[selamt]</td>
				</tr>";
		}
	}


	$display = "
		<h2>Confirm Entries To Remove</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirmremove'>
			$passon
			<tr>
				<td><h4>The Following Stock Items Will Be Removed</h4></th>
			<td>
			<tr>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Selling Amount</td>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Remove'></td>
			</tr>
		</form>
		</table>".
		mkQuickLinks(
			ql("stock-add.php", "Add Stock"),
			ql("stock-view.php", "View Stock")
		);
	return $display;

}



function confirmremove ($_POST)
{

	extract ($_POST);

	if(!isset($remids) OR !is_array($remids)){
		return printStk ($_POST,"<li class='err'>Please Select At Least 1 Item To Remove.</li>");
	}

	db_connect ();

	$listing = "";
	$passon = "";

	foreach ($remids AS $each){
		$get_stock = "DELETE FROM stock WHERE stkid = '$each'";
		$run_stock = db_exec($get_stock) or errDie("Unable to remove stock information");
	}


	$display = "
		<h3>Stock Item(s) Have Been Removed</h3>".
		mkQuickLinks(
			ql("stock-add.php", "Add Stock"),
			ql("stock-view.php", "View Stock")
		);
	return $display;

}



# show stock
function export ()
{

	global $_POST;

	$OUT = printStk($_POST);
	$OUT = clean_html($OUT);

	require_lib("xls");
	StreamXLS("Stock", $OUT);

}


?>

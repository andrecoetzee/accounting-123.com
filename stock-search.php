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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printStk($_POST);
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

require ("template.php");




# Default view
function slct($errors="")
{

	# check if setting exists
	db_connect();

	$sql = "SELECT value FROM set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$set = pg_fetch_array($Rslt);
		$whid = $set['value'];
	}else{
		$whid = 0;
	}

	# Select warehouse
	db_conn("exten");

	$whs = "<select name='whid'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "There are no Stores found in Cubit.";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			if($wh['whid'] == $whid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$whs .= "<option value='$wh[whid]' $sel>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";


	# drop downs
	$fldsarr = array(
		"catname"=>"Category",
		"classname"=>"Classification",
		"supstkcod"=>"Supplier Stock Code",
		"stkcod"=>"Stock Code",
		"stkdes"=>"Stock Description",
	);
	$flds = extlib_mksel("fld", $fldsarr);

	//layout
	$slct = "
		<h3>Search Stock</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<td colspan='2'$errors</td>
			</tr>
			<tr>
				<th colspan='2'>Key Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Store</td>
				<td valign='center'>$whs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Search By</td>
				<td valign='center'>$flds</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Search Key</td>
				<td valign='center'><input type='text' name='skey' size='20'></td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<th>Sort By</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='sortby' value='normal' checked='yes'> Normal</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='sortby' value='cat'> Category</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='sortby' value='class'> Classification</td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='View &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
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
function printStk ()
{

	extract($_POST);

	if(!isset($sortby))
		$sortby = "normal";

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");
	$v->isOk ($fld, "string", 1, 50, "Invalid Search Field.");
	$v->isOk ($skey, "string", 0, 50, "Invalid Search Key.");
	$v->isOk ($sortby, "string", 1, 10, "Invalid Sort Selection.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
        return slct($confirm);
	}

	if(!isset($sortby)){
		$sel1 = "";
		$sel2 = "";
		$sel3 = "";
	}elseif($sortby == "cat"){
		$sel1 = "";
		$sel2 = "checked='yes'";
		$sel3 = "";
	}elseif($sortby == "class"){
		$sel1 = "";
		$sel2 = "";
		$sel3 = "checked='yes'";
	}else {
		$sel1 = "checked='yes'";
		$sel2 = "";
		$sel3 = "";
	}

	if ($key == "export") {
		$pure = true;
	} else {
		$pure = false;
	}

	# Set up table to display in
	$OUT = "";
	if (!$pure) {
		$OUT .= "
			<h3>View Stock</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form1'>
				<input type='hidden' name='key' value='view'>
				<input type='hidden' name='fld' value='$fld'>
				<input type='hidden' name='skey' value='$skey'>
				<input type='hidden' name='whid' value='$whid'>
				<tr>
					<th>Sort By:</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>
						<input type='radio' name='sortby' $sel1 value='normal' onChange='javascript:document.form1.submit();'> Normal
						<input type='radio' name='sortby' $sel2 value='cat' onChange='javascript:document.form1.submit();'> Category
						<input type='radio' name='sortby' $sel3 value='class' onChange='javascript:document.form1.submit();'> Classification
					</td>
				</tr>
			</form>
			</table>";
	}

	$OUT .= "<table ".TMPL_tblDflts.">";

	#search parms
	if($sortby == "cat"){
		$Ord = "catname,stkcod";
	}elseif ($sortby == "class"){
		$Ord = "classname,stkcod";
	}else {
		$Ord = "stkcod";
	}

	# connect to database
	db_connect ();
	
	# Query server
	$i = 0;
	switch ($fld) {
		default:
			$sql = "
				SELECT * FROM stock 
				WHERE whid = '$whid' AND lower($fld) ILIKE '%$skey%' AND div = '".USER_DIV."' 
				ORDER BY $Ord ASC";
    		break;
		case "supstkcod":
			$sql = "
				SELECT DISTINCT stock.stkid FROM cubit.suppstock 
					LEFT JOIN cubit.stock ON suppstock.stkid=stock.stkid 
				WHERE suppstock.stkcod ILIKE '$skey%'";
			$supcod_rslt = db_exec($sql) or errDie("Unable to retrieve supplier stock codes.");

			$stkids = array();
			while ($supcod_data = pg_fetch_array($supcod_rslt)) {
				$stkids[] = "stkid='$supcod_data[stkid]'";
			}
			$stkids = implode(" OR ", $stkids);

			if (!empty($stkids)) {
				$stkids = "AND ($stkids)";
			} else {
				$stkids = "AND stkid='-12345'";
			}


			$sql = "SELECT * FROM cubit.stock WHERE whid='$whid' AND div='".USER_DIV."' $stkids ORDER BY $Ord ASC";
			break;
    }
	$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stock.");
	if (pg_numrows ($stkRslt) < 1) {
		$confirm = "<li class='err'>No Stock Found.</li>";
		return slct($confirm);
	}


	$tc = 0;
	$tu = 0;
	$tot1 = 0;
	$tot2 = 0;
	$tot3 = 0;
	$tot4 = 0;

	$heading = "";
	$showheading = "";

	while ($stk = pg_fetch_array ($stkRslt)) {
		$stk['csamt'] = sprint($stk['csamt']);

		if($sortby == "cat"){
			if($stk['catname'] == $heading){
				$showheading = "";
			}else {
				$showheading = "
					<tr>
						<td><font size='3' color='white'><b>$stk[catname]</b></font></td>
					</tr>
					<tr>
						<th>Code</th>
						<th>Description</th>
						<th>Class</th>
						<th>On Hand</th>
						<th>Cost of all goods on hand</th>
						<th>Cost per Unit</th>
						<th>Selling Price</th>
						<th>Last Cost Price</th>
						<th>Allocated</th>
						<th>On Order</th>
						<th>Min Lev</th>
						<th>Max Lev</th>
						<th>Measure Unit</th>
						".($pure?"":"<th colspan='8'>Options</th>")."
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
						<th>Code</th>
						<th>Description</th>
						<th>Class</th>
						<th>On Hand</th>
						<th>Cost of all goods on hand</th>
						<th>Cost per Unit</th>
						<th>Selling Price</th>
						<th>Last Cost Price</th>
						<th>Allocated</th>
						<th>On Order</th>
						<th>Min Lev</th>
						<th>Max Lev</th>
						<th>Measure Unit</th>
						".($pure?"":"<th colspan='8'>Options</th>")."
					</tr>";
			}
		}else {
			if($heading == "normal"){
				$showheading = "";
			}else {
				$showheading = "
					<tr>
						<th>Code</th>
						<th>Description</th>
						<th>Class</th>
						<th>On Hand</th>
						<th>Cost of all goods on hand</th>
						<th>Cost per Unit</th>
						<th>Selling Price</th>
						<th>Last Cost Price</th>
						<th>Allocated</th>
						<th>On Order</th>
						<th>Min Lev</th>
						<th>Max Lev</th>
						<th>Measure Unit</th>
						".($pure?"":"<th colspan='8'>Options</th>")."
					</tr>";
			}
		}

		$OUT .= $showheading;

		$OUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>$stk[classname]</td>
				<td align='right'>".sprint3($stk['units'])."</td>
				<td align='right' nowrap>".CUR." ".sprint($stk['csamt'])."</td>
				<td align='right' nowrap>".CUR." ".sprint($stk["csprice"])."</td>
				<td align='right' nowrap>".CUR." ".sprint($stk["selamt"])."</td>
				<td align='right' nowrap>".CUR." ".sprint($stk["lcsprice"])."</td>
				<td align='right'>".sprint3($stk['alloc'])."</td>
				<td align='right'>".sprint3($stk['ordered'])."</td>
				<td align='right'>$stk[minlvl]</td>
				<td align='right'>$stk[maxlvl]</td>
				<td>$stk[suom]</td>";

		if (!$pure) {
			$OUT .= "
				<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>View Report</a></td>
				<td><a href='stock-det.php?stkid=$stk[stkid]'>Details</a></td>
				<td><a href='stock-edit.php?stkid=$stk[stkid]'>Edit</a></td>
				<td><a href='stock-balance.php?stkid=$stk[stkid]'>Transaction</a></td>";

			if($stk['blocked'] == 'y'){
				$OUT .= "<td><a href='stock-unblock.php?stkid=$stk[stkid]'>Unblock</a></td>";
			}else{
				$OUT .= "<td><a href='stock-block.php?stkid=$stk[stkid]'>Block</a></td>";
			}

			if(($stk['units'] < 1) && ($stk['alloc'] < 1)){
				$OUT .= "<td><a href='stock-rem.php?stkid=$stk[stkid]'>Remove</a></td>";
			}elseif($stk['alloc'] > 0){
				$OUT .= "<td><a href='#' onclick='openwindow(\"stock-alloc.php?stkid=$stk[stkid]\")'>View Allocation</a></td></tr>";
			}else{
				$OUT .= "<td></td></tr>";
			}
		}

		if($sortby == "cat"){
			$heading = $stk['catname'];
		}elseif($sortby == "class"){
			$heading = $stk['classname'];
		}else {
			$heading = "normal";
		}

		$tc += $stk['csamt'];
		$tu += $stk['units'];

		$tot1 += $stk['csamt'];
		$tot2 += $stk['csprice'];
		$tot3 += $stk['selamt'];
		$tot4 += $stk['lcsprice'];
		

	}

	$t = sprint($tc);

	$OUT .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>Totals</td>
			<td align='right' nowrap>".CUR." ".sprint ($tot1)."</td>
			<td align='right' nowrap>".CUR." ".sprint($tot2)."</td>
			<td align='right' nowrap>".CUR." ".sprint ($tot3)."</td>
			<td align='right' nowrap>".CUR." ".sprint($tot4)."</td>
		</tr>";

	if (!$pure) {
		$OUT .= "
			".TBL_BR."
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='export'>
				<input type='hidden' name='whid' value='$whid'>
				<input type='hidden' name='fld' value='$fld'>
				<input type='hidden' name='skey' value='$skey'>
				<input type='hidden' name='sortby' value='$sortby'>
				<tr><td><input type='submit' value='Export to Spreadsheet'></td></tr>
			</form>
			</table>"
			.mkQuickLinks(
				ql("stock-add.php", "Add New Stock Item")
			);
	}
	return $OUT;

}




function export ()
{

	$OUT = printStk();
	$OUT = clean_html($OUT);

	require_lib("xls");
	StreamXLS("Stock", $OUT);

}




?>
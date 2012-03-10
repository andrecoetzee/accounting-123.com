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
require("core-settings.php");
require ("libs/ext.lib.php");

if (isset($HTTP_GET_VARS["stkid"])) {
	$OUTPUT = details($HTTP_GET_VARS["stkid"]);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "view":
				$OUTPUT = printStk($HTTP_POST_VARS);
				break;
			case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;
			case "write":
				$OUTPUT = write($HTTP_POST_VARS);
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

	db_connect ();

	$get_defwh = "SELECT * FROM set WHERE label = 'DEF_WH' LIMIT 1";
	$run_defwh = db_exec($get_defwh) or errDie("Unable to get default store information");
	if(pg_numrows($run_defwh) < 1){
		$defwhid = "";
	}else {
		$darr = pg_fetch_array($run_defwh);
		$defwhid = $darr['value'];
	}

	# Select warehouse
	db_conn("exten");

	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li class='err'>There are no Stores found in Cubit.</li>";
	}else{
		$whs = "<select name='whid'>";
		while($wh = pg_fetch_array($whRslt)){
			if($defwhid == $wh['whid']){
				$whs .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
			}else {
				$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
		}
		$whs .= "</select>";
	}


	# Select the stock category
	db_connect();

	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		$cats = "<select name='catid'>";
		while($cat = pg_fetch_array($catRslt)){
			$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
		}
		$cats .= "</select>";
	}


	# Select classification
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		$class = "<select name='clasid' style='width: 167'>";
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
		$class .= "</select>";
	}

	//layout
	$view = "
		<h3>Stock Transfer</h3>
		<table cellpadding='5'>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='view'>
						<tr>
							<th colspan='2'>Store</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center' colspan='2'>$whs</td>
						</tr>
						".TBL_BR."
						<tr>
							<th colspan='2'>By Category</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$cats</td>
							<td valign='bottom'><input type='submit' name='cat' value='View'></td>
						</tr>
						".TBL_BR."
						<tr>
							<th colspan='2'>By Classification</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$class</td>
							<td valign='bottom'><input type='submit' name='class' value='View'></td>
						</tr>
						".TBL_BR."
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
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}



# Show stock
function printStk ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);



	define ("DISPLAY_LIMIT", 25);

	if (!isset ($offset)) 
		$offset = 0;
	if (isset ($next)) 
		$offset += DISPLAY_LIMIT;
	if (isset ($back)) 
		$offset -= DISPLAY_LIMIT;
	if ($offset < 0) 
		$offset = 0;

	db_connect ();

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat) AND strlen ($cat) > 0){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "
			SELECT * FROM stock 
			WHERE whid = '$whid' AND catid = '$catid' AND div = '".USER_DIV."' 
			ORDER BY stkcod ASC OFFSET $offset LIMIT ".DISPLAY_LIMIT;
		$total = pg_fetch_result (db_exec ("SELECT count(stkid) FROM stock WHERE whid = '$whid' AND catid = '$catid' AND div = '".USER_DIV."'"),0,0);
	}elseif(isset($class) AND strlen ($class) > 0){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "
			SELECT * FROM stock 
			WHERE whid = '$whid' AND prdcls = '$clasid' AND div = '".USER_DIV."' 
			ORDER BY stkcod ASC OFFSET $offset LIMIT ".DISPLAY_LIMIT;
		$total = pg_fetch_result (db_exec ("SELECT count(stkid) FROM stock WHERE whid = '$whid' AND prdcls = '$clasid' AND div = '".USER_DIV."'"),0,0);
	}elseif(isset($all) AND strlen ($all) > 0){
		$searchs = "
			SELECT * FROM stock 
			WHERE whid = '$whid' AND div = '".USER_DIV."' 
			ORDER BY stkcod ASC OFFSET $offset LIMIT ".DISPLAY_LIMIT;
		$total = pg_fetch_result (db_exec ("SELECT count(stkid) FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."'"),0,0);
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
	$printStk = "
		<h3>Current Stock</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='$key'>
			<input type='hidden' name='offset' value='$offset'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='clasid' value='$clasid'>
			<input type='hidden' name='cat' value='$cat'>
			<input type='hidden' name='all' value='$all'>
			<input type='hidden' name='class' value='$class'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Class</th>
				<th>On Hand</th>
				<th>Cost Amount</th>
				<th>Allocated</th>
				<th>On Order</th>
				<th>Unit</th>
				<th>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "
			<li class='err'> No Stock Items Found.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				".TBL_BR."
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-view.php'>Back</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-add.php'>Add Stock</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}

	while ($stk = pg_fetch_array ($stkRslt)) {

		$stk['csamt'] = sprint($stk['csamt']);

		$printStk .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>$stk[classname]</td>
				<td align='right'>".sprint3 ($stk['units'])."</td>
				<td align='right'>".CUR." $stk[csamt]</td>
				<td align='right'>".sprint3 ($stk['alloc'])."</td>
				<td align='right'>".sprint3 ($stk['ordered'])."</td>
				<td>$stk[suom]</td>";

		# If there is stock on hand
		if(($stk['units'] - $stk['alloc']) > 0){
			$printStk .= "
					<td>&nbsp;&nbsp;<a href='stock-transfer.php?stkid=$stk[stkid]'>Transfer</a>&nbsp;&nbsp;</td>
				</tr>";
		}else{
			$printStk .= "<td><br></td></tr>";
		}
		$i++;
	}

	if ($offset != 0){
		$prevbutton = "<input type='submit' name='back' value='Back'>";
	}
	if ($offset+DISPLAY_LIMIT < $total) {
		$nextbutton = "<input type='submit' name='next' value='Next'>";
	}

	$printStk .= "
			<tr>
				<td>$prevbutton</td>
				<td>$nextbutton</td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			".TBL_BR."
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printStk;

}



# Confirm
function details($stkid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# get stock vars
	extract ($stk);

	db_conn("exten");

	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# Select the stock warehouse
	$sql = "SELECT whid,whname,whno FROM warehouses WHERE whid != '$whid' AND div = '".USER_DIV."' ORDER BY whname ASC";
	$swhRslt = db_exec($sql);
	if(pg_numrows($swhRslt) < 1){
		return "
			<li>There are no other stores in Cubit.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				".TBL_BR."
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-add.php'>Add Stock</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}else{
		$whs = "<select name='whid'>";
		while($swh = pg_fetch_array($swhRslt)){
			$whs .= "<option value='$swh[whid]'>($swh[whno]) $swh[whname]</option>";
		}
		$whs .= "</select>";
	}


	# available stock units
	$avstk = ($units - $alloc);
	
	explodeDate(DATE_STD, $d_year, $d_month, $d_day);

	// Layout
	$details = "
		<center>
		<h3>Transfer Stock</h3>
		<h4>Stock Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='stkid' value='$stkid'>
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Warehouse</td>
				<td>$wh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category</td>
				<td>$catname</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock code</td>
				<td>$stkcod</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock description</td>
				<td>".nl2br($stkdes)."</pre></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Hand</td>
				<td>".sprint3($units)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>".sprint3($alloc)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>".sprint3($avstk)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>".sprint3($ordered)."</td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='2'>Transfer to</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td nowrap='t'>".mkDateSelect("d", $d_year, $d_month, $d_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Store </td>
				<td>$whs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of units</td>
				<td><input type='text' size='7' name='tunits' value='1'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Remark</td>
				<td><textarea cols='35' rows='4' name='remark'>$remark</textarea></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			".TBL_BR."
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $details;

}



# Confirm
function confirm($HTTP_POST_VARS)
{

	# get stock vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "float", 1, 15, "Invalid number of units.");
	$date = mkdate($d_year, $d_month, $d_day);
	$v->isOk($date, "date", 1, 1, "Invalid transfer date.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	db_conn("exten");

	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);

	$serials = "";
	if($stk['serd'] == 'yes'){
		$sers = ext_getavserials($stkid);

		$serials = "<tr><th colspan='2'>Units Serial Numbers</th></tr>";

		$sernos = "<select name='sernos[]'>";
		foreach($sers as $skey => $ser){
			$sernos .= "<option value='$ser[serno]'>$ser[serno]</option>";
		}
		$sernos .= "</select>";

		for($i = 0; $i < $tunits; $i++){
			$serials .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2' align='center'>$sernos</td>
				</tr>";
		}
	}

	# Get stock from selected warehouse
	db_connect();

	$sql = "SELECT * FROM stock WHERE whid = '$whid' AND lower(stkcod) = lower('$stk[stkcod]') AND div = '".USER_DIV."'";
	$sstkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sstkRslt) < 1){
		$sstk = $stk;
		$head = "New Stock";
		$data = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Location</td>
				<td>Shelf <input type='text' size='5' name='shelf'> Row <input type='text' size='5' name='row'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Level</td>
				<td>Minimum <input type='text' size='5' name='minlvl' value='$stk[minlvl]'> Maximum <input type='text' size='5' name='maxlvl' value='$stk[maxlvl]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Selling price per unit</td>
				<td>".CUR." <input type='hidden' name='selamt' value='$stk[selamt]'>$stk[selamt]</td>
			</tr>";
	}else{
		$sstk = pg_fetch_array($sstkRslt);
		$data = "";
		$head = "";
	}

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	// Layout
	$confirm = "
		<center>
		<h3>Transfer Stock</h3>
		<h4>Confirm Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='stkid' value='$stkid'>
			<input type='hidden' name='sstkid' value='$sstk[stkid]'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='tunits' value='$tunits'>
			<input type='hidden' name='d_year' value='$d_year' />
			<input type='hidden' name='d_month' value='$d_month' />
			<input type='hidden' name='d_day' value='$d_day' />
			<input type='hidden' name='remark' value='$remark' />
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Warehouse</td>
				<td>$wh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category</td>
				<td>$stk[catname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock code</td>
				<td>$stk[stkcod]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock description</td>
				<td>".nl2br($stk['stkdes'])."</pre></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Hand</td>
				<td>".sprint3($stk['units'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>".sprint3($stk['alloc'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>".sprint3($avstk)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>".sprint3($stk['ordered'])."</td>
			</tr>
			".TBL_BR."
			$serials
			".TBL_BR."
			<tr>
				<th colspan='2'>Transfer to $head</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Store </td>
				<td>$swh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock code</td>
				<td>$sstk[stkcod]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock description</td>
				<td>".nl2br($sstk['stkdes'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of units</td>
				<td>".sprint3($tunits)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Remark</td>
				<td>$remark</td>
			</tr>
			$data
			".TBL_BR."
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Transfer &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			".TBL_BR."
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}



# Write
function write($HTTP_POST_VARS)
{

	# get stock vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sstkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "float", 1, 15, "Invalid number of units.");
	$date = mkdate($d_year, $d_month, $d_day);
	$v->isOk($date, "date", 1, 1, "Invalid transfer date.");
	if($stkid == $sstkid){
		$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
		$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
		$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
		$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.");
		$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
	}

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
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



	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to get stock information.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	if($stkid == $sstkid){
		$sstk = $stk;
		$head = "New Stock";
		$data = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Location</td>
				<td>Shelf : <input type='hidden' name='shelf' value='$shelf'>$shelf - Row : <input type='hidden' name='row' value='$row'>$row</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Level</td>
				<td>Minimum : <input type='hidden' name='minlvl' value='$minlvl'>$minlvl -  Maximum : <input type='hidden' name='maxlvl' value='$maxlvl'>$maxlvl</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Selling price per unit</td>
				<td>".CUR." <input type='hidden' name='selamt' value='$stk[selamt]'>$stk[selamt]</td>
			</tr>";
	}else{
		$sql = "SELECT * FROM stock WHERE stkid = '$sstkid' AND div = '".USER_DIV."'";
		$sstkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($sstkRslt) < 1){
			return "<li> Invalid Stock ID.</li>";
		}else{
			$sstk = pg_fetch_array($sstkRslt);
		}
		$head = "";
		$data = "";
	}

	db_conn("exten");

	# get warehouse
	$sql = "SELECT whid, whname, stkacc FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT whid, whname, stkacc FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);




	/* Start Stock transfering */
	pglib_transaction ("BEGIN") or errDie("Could Not Start Transaction.");


	db_connect();
	$csamt = ($tunits * $stk['csprice']);
	$sdate = $date;
	if($stkid == $sstkid){
		# Create new stock item on the other hand
		$sql = "
			INSERT INTO stock (
				stkcod, serno, stkdes, prdcls, classname, csamt, 
				units, buom, suom, rate, shelf, row, minlvl, maxlvl, 
				csprice, selamt, catid, catname, whid, blocked, type, alloc, 
				com, serd, div, vatcode
			) VALUES (
				'$sstk[stkcod]', '$sstk[serno]', '$sstk[stkdes]', '$sstk[prdcls]', '$sstk[classname]', '$csamt',  
				'$tunits', '$sstk[buom]', '$sstk[suom]', '$sstk[rate]', '$shelf', '$row', '$minlvl', '$maxlvl', 
				'$sstk[csprice]', '$sstk[selamt]', '$sstk[catid]', '$sstk[catname]', '$whid', 'n', '$sstk[type]', '0', 
				'0', '$sstk[serd]', '".USER_DIV."', '$sstk[vatcode]'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		$sstkid = pglib_lastid("stock", "stkid");

		db_conn(date("n"));

		$sql = "
			INSERT INTO stkledger (
				stkid, stkcod, stkdes, trantype, edate, qty, csamt, 
				balance, bqty, details, div, yrdb
			) VALUES (
				'$sstkid', '$sstk[stkcod]', '$sstk[stkdes]', 'bal', '$date', '0', '0', 
				'0', '0', 'Balance', '".USER_DIV."', '".YR_DB."'
			)";
		$Ro = db_exec($sql);
		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		stockrec($sstkid, $sstk['stkcod'], $sstk['stkdes'], 'dt', $sdate, $tunits, $csamt, "Stock Transferred from Store : $wh[whname]", FALSE);

		db_connect();
		# Reduce on the other hand
		$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);
		
//		db_conn('audit');
//		for ($i = 1; $i <= 12; ++$i) {
//			db_conn($i);


//			$sql = "INSERT INTO stkledger(stkid,stkcod,stkdes,trantype,edate,qty,csamt,balance,
//						bqty,details,div,yrdb) 
//					VALUES ('$data[stkid]','$data[stkcod]','$data[stkdes]','bal','$date',
//						'$data[units]','$data[csamt]','$data[csamt]','$data[units]',
//						'Balance','".USER_DIV."','".YR_DB."')";
//			$Ro=db_exec($sql);

// doesnt make sense ???
// 			$sql = "
// 				INSERT INTO stkledger (
// 					stkid, stkcod, stkdes, trantype, edate, qty, csamt, 
// 					balance, bqty, details, div, yrdb
// 				) VALUES (
// 					'$sstk[stkid]', '$sstk[stkcod]', '$sstk[stkdes]', 'bal', '$date', '$sstk[units]', '$sstk[csamt]', 
// 					'$sstk[csamt]', '$sstk[units]', 'Balance', '".USER_DIV."', '".YR_DB."'
// 				)";

//		}

		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $sdate, $tunits, $csamt, "Stock Transferred to Store : $swh[whname]");
//		db_connect();
	}else{

		db_connect();

		# Move units and csamt
		$sql = "UPDATE stock SET units = (units + '$tunits'), csamt = (csamt + '$csamt') WHERE stkid = '$sstkid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);

		$sdate = date("Y-m-d");
		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		stockrec($sstk['stkid'], $sstk['stkcod'], $sstk['stkdes'], 'dt', $sdate, $tunits, $csamt, "Stock Transferred from Store : $wh[whname]", FALSE);
		db_connect();

		# Reduce on the other hand
		$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);

		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $sdate, $tunits, $csamt, "Stock Transferred to Store : $swh[whname]");

	}

	# todays date
	$refnum = getrefnum($date);

	db_connect ();

	$ins_sql = "
		INSERT INTO stock_transfer (
			stkid, whid_from, whid_to, units, reference, remark, location_shelf, location_row, level_min, level_max, transfer_date
		) VALUES (
			'$stkid', '$wh[whid]', '$swh[whid]', '$tunits', '$refnum', '$remark', '$shelf', '$row', '$minlvl', '$maxlvl', '$date'
		)";
	$run_ins = db_exec ($ins_sql) or errDie ("Unable to record stock transfer information.");

	$serials = "";
	# Move serial number,using functions
	if(isset($sernos)){
		$serials = "
			<tr>
				<th colspan='2'>Units Serial Numbers</th>
			</tr>";
		foreach($sernos as $skey => $serno){
			ext_invSer($serno, $stkid);
			ext_unInvSer($serno, $sstkid);
			$serials .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2' align='center'>$serno</td>
				</tr>";
		}
	}

	# dt(cos) ct(stock)
	writetrans($swh['stkacc'], $wh['stkacc'], $date, $refnum, $csamt, "Stock Transfer");


	/* End Stock transfering */
	pglib_transaction("COMMIT") or errDie("Unable To Commit Transaction.");


	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	# return
	$write = "
		<h3> Stock has been Transfered </h3>
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Warehouse</td>
				<td>$wh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category</td>
				<td>$stk[catname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock code</td>
				<td>$stk[stkcod]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock description</td>
				<td>".nl2br($stk['stkdes'])."</pre></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Hand</td>
				<td>".sprint3($stk['units'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>".sprint3($stk['alloc'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>".sprint3($avstk)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>".sprint3($stk['ordered'])."</td>
			</tr>
			<tr><td><br></td></tr>
			$serials
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfered to $head</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Store </td>
				<td>$swh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock code</td>
				<td>$sstk[stkcod]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock description</td>
				<td>".nl2br($sstk['stkdes'])."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of units transfered</td>
				<td>".sprint3($tunits)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Remark</td>
				<td>$remark</td>
			</tr>
			$data
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transfer.php'>New Stock Transfer</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>

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
			case "details2":
				$OUTPUT = details2($HTTP_POST_VARS);
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
		return "<li>There are no stock categories in Cubit.";
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
		return "<li>There are no Classifications in Cubit.";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .= "</select>";

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
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>By Category</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$cats</td>
							<td valign='bottom'><input type='submit' name='cat' value='View'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>By Classification</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'>$class</td>
							<td valign='bottom'><input type='submit' name='class' value='View'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>All Categories and Classifications</th></tr>
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
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}




# Show stock
function printStk ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND catid = '$catid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND prdcls = '$clasid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
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
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Class</th>
				<th>On Hand</th>
				<th>Cost Amount</th>
				<th>Allocated</th>
				<th>On order</th>
				<th>Unit</th>
			</tr>";

	# Connect to database
	db_connect ();

	# Query server
	$i = 0;
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
					<td><a href='stock-view.php'>Back</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-add.php'>Add Stock</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}

	while ($stk = pg_fetch_array ($stkRslt)) {


		$printStk .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>$stk[classname]</td>
				<td align='right'>$stk[units]</td>
				<td align='right'>".CUR." $stk[csamt]</td>
				<td align='right'>$stk[alloc]</td>
				<td align='right'>$stk[ordered]</td>
				<td>$stk[suom]</td>";

		# If there is stock on hand
		if(($stk['units'] - $stk['alloc']) > 0){
			$printStk .= "<td>&nbsp;&nbsp;<a href='stock-transfer-bran.php?stkid=$stk[stkid]'>Transfer</a>&nbsp;&nbsp;</td></tr>";
		}else{
			$printStk .= "<td><br></td></tr>";
		}
		$i++;
	}

	$printStk .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
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

	# Get stock vars
	extract ($stk);

	db_conn("exten");

	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	db_connect();

	# Select the stock warehouse
	$brans= "<select name='sdiv'>";
	$sql = "SELECT * FROM branches WHERE div != '$div' ORDER BY branname ASC";
	$branRslt = db_exec($sql);
	if(pg_numrows($branRslt) < 1){
		return "
			<li>There are no other branches in Cubit.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-add.php'>Add Stock</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}else{
		while($bran = pg_fetch_array($branRslt)){
			$brans .= "<option value='$bran[div]'>$bran[branname]</option>";
		}
	}
	$brans .= "</select>";

	# available stock units
	$avstk = ($units - $alloc);

	// Layout
	$details = "
		<center>
		<h3>Transfer Stock</h3>
		<h4>Stock Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details2'>
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
				<td>$units</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>$alloc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>$avstk</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>$ordered</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfer to</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Branch</td>
				<td>$brans</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $details;

}




# Confirm
function details2($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");

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

	db_connect();

	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$sdiv'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	db_conn("exten");

	# Select the stock warehouse
	$whs = "<select name='whid'>";
	$sql = "SELECT whid,whname,whno FROM warehouses WHERE div = '$sdiv' ORDER BY whname ASC";
	$swhRslt = db_exec($sql);
	if(pg_numrows($swhRslt) < 1){
		return "
			<li>There are no stores on the seleted branch: <b>$sbran[branname]</b>.
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='stock-add.php'>Add Stock</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}else{
		while($swh = pg_fetch_array($swhRslt)){
			$whs .= "<option value='$swh[whid]'>($swh[whno]) $swh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	// Layout
	$details = "
		<center>
		<h3>Transfer Stock</h3>
		<h4>Stock Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='stkid' value='$stkid'>
			<input type='hidden' name='sdiv' value='$sdiv'>
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Branch</td>
				<td>$bran[branname]</td>
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
				<td>$stk[units]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>$stk[alloc]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>$avstk</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>$stk[ordered]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfer to</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Branch</td>
				<td>$sbran[branname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Store </td>
				<td>$whs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of units</td>
				<td><input type='text' size='7' name='tunits' value='1'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $details;

}



# Confirm
function confirm($HTTP_POST_VARS)
{

	# Get stock vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "num", 1, 50, "Invalid number of units.");

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
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '$sdiv'";
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
			$serials .= "<tr bgcolor='".bgcolorg()."'><td colspan='2' align='center'>$sernos</td></tr>";
		}
	}

	db_connect();

	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$sdiv'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	/*
	# Get stock from selected warehouse
	db_connect();
	$sql = "SELECT * FROM stock WHERE whid = '$whid' AND lower(stkcod) = lower('$stk[stkcod]') AND div = '$sdiv'";
	$sstkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sstkRslt) < 1){
		$sstk = $stk;
		$head = "New Stock";
		$data = "<tr bgcolor='".TMPL_tblDataColor1."'><td>Location</td><td>Shelf <input type=text size=5 name='shelf'> Row <input type=text size=5 name='row'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Level</td><td>Minimum <input type=text size=5 name='minlvl' value='$stk[minlvl]'> Maximum <input type=text size=5 name='maxlvl' value='$stk[maxlvl]'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Selling price per unit</td><td>".CUR." <input type=hidden name='selamt' value='$stk[selamt]'>$stk[selamt]</td></tr>";
	}else{
		$sstk = pg_fetch_array($sstkRslt);
		$data = "";
		$head = "";
	}
	*/


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
			<input type='hidden' name='sdiv' value='$sdiv'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='tunits' value='$tunits'>
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Branch</td>
				<td>$bran[branname]</td>
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
				<td>$stk[units]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>$stk[alloc]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>$avstk</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>$stk[ordered]</td>
			</tr>
			<tr><td><br></td></tr>
			$serials
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfer to</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Branch</td>
				<td>$sbran[branname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Store </td>
				<td>$swh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of units</td>
				<td>$tunits</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='transfer &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transit-view.php'>View Stock in transit</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}




# Write
function write($HTTP_POST_VARS)
{

	# Get stock vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "num", 1, 50, "Invalid number of units.");

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ("##", "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
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
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	db_conn("exten");

	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$whid' AND div = '$sdiv'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);

	/* Start Stock transfering */

		db_connect();
		$csamt = ($tunits * $stk['csprice']);

		# Reduce on the other hand
		$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);

		# Insert ithe stock into transit
		$sql = "INSERT INTO transit (trandate, stkid, sdiv, swhid, tunits, cstamt, div) VALUES (now(), '$stkid', '$sdiv', '$whid', '$tunits', '$csamt', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert stock into transit.",SELF);

		$tid = pglib_lastid("transit", "id");

		if(isset($sernos)){
			foreach($sernos as $skey => $serno){
				# Insert the stock serial into transit serials
				$sql = "INSERT INTO transerial (tid, stkid, serno) VALUES ('$tid', '$stkid', '$serno')";
				$rslt = db_exec($sql) or errDie("Unable to insert stock into transit.",SELF);
				ext_invSer($serno, $stkid);
			}
		}

		# todays date
		$date = date("d-m-Y");

		$refnum = getrefnum($date);

		# dt(conacc) ct(stkacc)
		# writetrans($wh['conacc'], $wh['stkacc'], $date, $refnum, $csamt, "Stock Transfer", USER_DIV);

	/* End Stock transfering */

	db_connect();

	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$sdiv'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
	}else{
			$stk = pg_fetch_array($stkRslt);
	}

	# Available stock units
	$avstk = ($stk['units'] - $stk['alloc']);

	# Return
	$write = "
		<h3>Stock has been taken to transit</h3>
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Branch</td>
				<td>$bran[branname]</td>
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
				<td>$stk[units]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Allocated</td>
				<td>$stk[alloc]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available</td>
				<td>$avstk</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>On Order</td>
				<td>$stk[ordered]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfered to</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Branch</td>
				<td>$sbran[branname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>To Store </td>
				<td>$swh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number of units transfered</td>
				<td>$tunits</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-transit-view.php'>View Stock in transit</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>
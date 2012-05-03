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

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			if (isset($_GET['id'])){
					$OUTPUT = confirm ($_GET['id']);
			} else {
					$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
        if (isset($_GET['id'])){
                $OUTPUT = confirm ($_GET['id']);
        } else {
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# Get template
require("template.php");



# Confirm
function confirm($id)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid transit number.");

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

	$sql = "SELECT * FROM transit WHERE id = '$id' AND div = '".USER_DIV."'";
	$tranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($tranRslt) < 1){
		return "<li> Invalid transit number.</li>";
	}else{
		$tran = pg_fetch_array($tranRslt);
	}

	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$tran[stkid]' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	$serials = "";
	$sRs = undget("cubit", "*", "transerial", "tid", $id);
	if(pg_numrows($sRs) > 0){
		$serials = "<tr><th colspan='2'>Units Serial Numbers</th></tr>";
		while($ser = pg_fetch_array($sRs)){
			$serials .= "<tr class='".bg_class()."'><td colspan='2' align='center'>$ser[serno]</td></tr>";
		}
	}

	# Original Branch
	$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$bran = pg_fetch_array($branRslt);
	}

	# Selected Branch
	$sql = "SELECT * FROM branches WHERE div = '$tran[sdiv]'";
	$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sbranRslt) < 1){
		return "<li> Invalid Branch ID.</li>";
	}else{
		$sbran = pg_fetch_array($sbranRslt);
	}

	db_conn("exten");

	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	# get warehouse
	$sql = "SELECT * FROM warehouses WHERE whid = '$tran[swhid]' AND div = '$tran[sdiv]'";
	$swhRslt = db_exec($sql);
	$swh = pg_fetch_array($swhRslt);

	# Get stock from selected warehouse
	db_connect();
	$sql = "SELECT * FROM stock WHERE whid = '$tran[swhid]' AND lower(stkcod) = lower('$stk[stkcod]') AND div = '$tran[sdiv]'";
	$sstkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($sstkRslt) < 1){
		$sstk = $stk;
		$head = "New Stock";
		$data = "
			<tr class='".bg_class()."'>
				<td>Location</td>
				<td>Shelf <input type='text' size='5' name='shelf'> Row <input type='text' size='5' name='row'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Level</td>
				<td>Minimum <input type='text' size='5' name='minlvl' value='$stk[minlvl]'> Maximum <input type='text' size='5' name='maxlvl' value='$stk[maxlvl]'></td>
			</tr>
			<tr class='".bg_class()."'>
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
		<h3>Transfer Stock Delivery</h3>
		<h4>Confirm Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='stkid' value='$tran[stkid]'>
			<input type='hidden' name='sstkid' value='$sstk[stkid]'>
			<input type='hidden' name='sdiv' value='$tran[sdiv]'>
			<input type='hidden' name='whid' value='$tran[swhid]'>
			<input type='hidden' name='tunits' value='$tran[tunits]'>
		<table ".TMPL_tblDflts." width='350'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Branch</td>
				<td>$bran[branname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Warehouse</td>
				<td>$wh[whname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Category</td>
				<td>$stk[catname]</td>
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
				<td>On Hand</td>
				<td>$stk[units]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Allocated</td>
				<td>$stk[alloc]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Available</td>
				<td>$avstk</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>On Order</td>
				<td>$stk[ordered]</td>
			</tr>
			<tr><td><br></td></tr>
			$serials
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfer to $head</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>To Branch</td>
				<td>$sbran[branname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>To Store </td>
				<td>$swh[whname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Stock code</td>
				<td>$sstk[stkcod]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Stock description</td>
				<td>".nl2br($sstk['stkdes'])."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Number of units</td>
				<td>$tran[tunits]</td>
			</tr>
			$data
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Transfer &raquo'></td>
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
				<td><a href='stock-transit-view.php'>View Stock in transit</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# Write
function write($_POST)
{

	# get stock vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 50, "Invalid transit number.");
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sstkid, "num", 1, 50, "Invalid stock number.");
	$v->isOk ($sdiv, "num", 1, 50, "Invalid branch number.");
	$v->isOk ($whid, "num", 1, 50, "Invalid warehouse number.");
	$v->isOk ($tunits, "num", 1, 50, "Invalid number of units.");
	if($stkid == $sstkid){
		$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
		$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
		$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
		$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.");
		$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
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

	if($stkid == $sstkid){
		$sstk = $stk;
		$head = "New Stock";
		$data = "
			<tr class='".bg_class()."'>
				<td>Location</td>
				<td>Shelf : <input type='hidden' name='shelf' value='$shelf'>$shelf - Row : <input type='hidden' name='row' value='$row'>$row</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Level</td>
				<td>Minimum : <input type='hidden' name='minlvl' value='$minlvl'>$minlvl -  Maximum : <input type='hidden' name='maxlvl' value='$maxlvl'>$maxlvl</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Selling price per unit</td>
				<td>".CUR." <input type='hidden' name='selamt' value='$stk[selamt]'>$stk[selamt]</td>
			</tr>";
	}else{
		$sql = "SELECT * FROM stock WHERE stkid = '$sstkid' AND div = '$sdiv'";
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
		if($stkid == $sstkid){
			# Create new stock item on the other hand
			$sql = "
				INSERT INTO stock (
					stkcod, stkdes, prdcls, classname, csamt, units, 
					buom, suom, rate, shelf, row, minlvl, 
					maxlvl, csprice, selamt, catid, catname, whid, 
					blocked, type, alloc, com, serd, div
				) VALUES (
					'$sstk[stkcod]', '$sstk[stkdes]', '$sstk[prdcls]', '$sstk[classname]', '$csamt',  '$tunits', 
					'$sstk[buom]', '$sstk[suom]', '$sstk[rate]', '$shelf', '$row', '$minlvl', 
					'$maxlvl', '$sstk[csprice]', '$sstk[selamt]', '$sstk[catid]', '$sstk[catname]', '$whid', 
					'n', '$sstk[type]', '0', '0', '$sstk[serd]', '$sdiv'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

			/*
			# Reduce on the other hand
			$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);
			*/
		}else{
			# Move units and csamt
			$sql = "UPDATE stock SET units = (units + '$tunits'), csamt = (csamt + '$csamt') WHERE stkid = '$sstkid' AND div = '$sdiv'";
			$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);

			/*
			# Reduce on the other hand
			$sql = "UPDATE stock SET units = (units - '$tunits'), csamt = (csamt - '$csamt') WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock in Cubit.",SELF);
			*/
		}

		$serials = "";
		$sRs = undget("cubit", "*", "transerial", "tid", $id);
		if(pg_numrows($sRs) > 0){
			$serials = "<tr><th colspan=2>Units Serial Numbers</th></tr>";
			while($ser = pg_fetch_array($sRs)){
				$serials .= "<tr class='".bg_class()."'><td colspan='2' align='center'>$ser[serno]</td></tr>";
				ext_uninvSer($ser['serno'], $sstkid);
			}
		}

		# Remove stock from transit
		$sql = "DELETE FROM transit WHERE id = '$id' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove stock from transit.",SELF);

		# todays date
		$date = date("d-m-Y");

		$refnum = getrefnum($date);

		# dt(conacc) ct(stkacc)
		// writetransdiv($wh['conacc'], $wh['stkacc'], $date, $refnum, $csamt, "Stock Transfer", USER_DIV);

		// writetransdiv($swh['stkacc'], $swh['conacc'], $date, $srefnum, $csamt, "Stock Transfer", $sdiv);

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
			<tr class='".bg_class()."'>
				<td>Branch</td>
				<td>$bran[branname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Warehouse</td>
				<td>$wh[whname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Category</td>
				<td>$stk[catname]</td>
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
				<td>On Hand</td>
				<td>$stk[units]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Allocated</td>
				<td>$stk[alloc]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Available</td>
				<td>$avstk</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>On Order</td>
				<td>$stk[ordered]</td>
			</tr>
			<tr><td><br></td></tr>
			$serials
			<tr><td><br></td></tr>
			<tr>
				<th colspan='2'>Transfered to $head</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>To Branch</td>
				<td>$sbran[branname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>To Store </td>
				<td>$swh[whname]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Stock code</td>
				<td>$sstk[stkcod]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Stock description</td>
				<td>".nl2br($sstk['stkdes'])."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Number of units transfered</td>
				<td>$tunits</td>
			</tr>
			$data
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-transit-view.php'>View Stock in transit</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
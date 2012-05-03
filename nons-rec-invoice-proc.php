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

# get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "process":
			$OUTPUT = process($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "update_prices":
			$OUTPUT = update_prices ($_POST);
			break;
		default:
			# decide what to do
			if (isset($_GET["invids"])) {
				$OUTPUT = details($_GET);
			} else {
				$OUTPUT = "<li class='err'>Invalid use of module.</li>";
			}
		}
} else {
	# decide what to do
	if (isset($_GET["invids"])) {
		if (isset($_GET["edit"]))
			$OUTPUT = edit_items ($_GET);
		else 
			$OUTPUT = details($_GET);
	} else {
		$OUTPUT = "<li class='err'>Please select at least one invoice</li><br><input type='button' onClick=\"document.location='rec-nons-invoice-view.php';\" value='Back'>";
	}
}

# get templete
require("template.php");




# details
function details($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	$invs = "";
	$i = 0;
	$dids = array();
	foreach($invids as $key => $invid){
		# Get recuring invoice info
		db_connect();

		$sql = "SELECT * FROM rnons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		$dids[] = $i;

		$inv['total'] = sprint($inv['total']);
		$inv['balance'] = sprint($inv['balance']);

		# Format date
		//list($oyear, $omon, $oday) = explode("-", date("Y-m-d"));

		$invs .= "
			<input type='hidden' name='invids[$i]' value='$inv[invid]' />
			<tr class='".bg_class()."'>
				<td>RI $inv[invid]</td>
				<td valign='center' nowrap='t'>
					".mkDateSelectA("o",$i)."
				</td>
				<td>$inv[cusname]</td>
				<td align='right'>".CUR." $inv[total]</td>
			</tr>";
		$i++;
	}

	$printInv = "
		<h3>Confirm Non-stock Invoice Process</h3>
		<script>
			function updateAllDates(obj) {
				alert(obj.value);
			}
		</script>
		<form action='nons-rec-invoice-proc.php' method='POST'>
			<input type='hidden' name='key' value='process' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='6' class='err'>Please Note : This process might take long depending on the number of invoices. It is best to run it overnight.</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>".mkDateSelectB("o", implode(",", $dids), "Select Date for All Invoices")."</td>
			</tr>
			<tr>
				<th>Invoice No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Grand Total</th>
			</tr>
			$invs
			<tr class='".bg_class()."'>
				<td colspan='6' align='right'>Totals Invoices : $i</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='6' align='right'><input type='submit' value='Process &gt;' /></td>
			</tr>
			</form></table>"
			.mkQuickLinks(
				ql("rec-nons-invoice-new.php", "New Recurring Non Stock Invoice"),
				ql("rec-nons-invoice-view.php", "View Recurring Non Stock Invoices")
			);
	return $printInv;

}




# Create the company
function process ($_POST)
{

	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
		$odate[$key] = mkdate($o_year[$key], $o_month[$key], $o_day[$key]);
		$v->isOk ($odate[$key], "date", 1, 1, "Invalid Invoice Date for invoice: $invid.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError()) {
		$err = $v->genErrors();
		return $err;
	}



	$postvars = "";
	foreach($invids as $key => $invid){
		$postvars .= "<input type='hidden' name='invids[$key]' value='$invid'>";
		$postvars .= "<input type='hidden' size='2' name='o_day[$key]' maxlength='2' value='$o_day[$key]'>";
		$postvars .= "<input type='hidden' size='2' name='o_month[$key]' maxlength='2' value='$o_month[$key]'>";
		$postvars .= "<input type='hidden' size='2' name='o_year[$key]' maxlength='2' value='$o_year[$key]'>";
	}

	$OUTPUT = "
		<form action='".SELF."' method='POST' name='postvars'>
			<input type='hidden' name='key' value='write'>
			$postvars
		</form>
		<table width='100%' height='100%'>
			<tr>
				<td align='center' valign=middle>
					<font size='2' color='white'>
					Please wait while the invoices are being processed. This may take several minutes.</font><br>
					<div id='wait_bar_parent' style='border: 1px solid black; width:100px'>
						<div id='wait_bar' style='font-size: 15pt'>...</div>
					</div>
				</td>
			</tr>
		</table>

		<script>
			wait_bar = getObjectById('wait_bar')
			function moveWaitBar() {
				if ( wait_bar.innerHTML == '...................')
					wait_bar.innerHTML = '.';
				else
					wait_bar.innerHTML = wait_bar.innerHTML + '.';
	
				setTimeout('moveWaitBar()', 50);
			}
	
			setTimeout('moveWaitBar()', 100);
	
			document.postvars.submit();
		</script>";
	return $OUTPUT;

}




# Details
function write($_POST)
{

	# Set mas execution time to 12 hours
	ini_set("max_execution_time", 43200);

	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
		$odate[$key] = mkdate($o_year[$key], $o_month[$key], $o_day[$key]);
		$v->isOk ($odate[$key], "date", 1, 1, "Invalid Invoice Date for invoice: $invid.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError()) {
		$err = $v->genErrors();
		return $err;
	}



	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$i = 0;
	$recinv = new dbSelect("rnons_invoices", "cubit");
	$recinv_i = new dbSelect("rnons_inv_items", "cubit");

	$newinv = new dbUpdate("nons_invoices", "cubit");
	$newinv_i = new dbUpdate("nons_inv_items", "cubit");

	foreach ($invids as $key => $invid) {
		/* fetch recurring invoice info */
		$recinv->setOpt(grp(
			m("where", "invid='$invid' AND div='".USER_DIV."'")
		));
		$recinv->run();

		if ($recinv->num_rows() <= 0) {
			continue;
		}

		$inv = $recinv->fetch_array();

		/* create new invoice from recurring invoice */
		$cols = grp(
			m("accepted", " "),
			m("sdate", raw("CURRENT_DATE")),
			m("typ", "inv"),
			m("cusid", $inv["cusid"]),
			m("cusname", $inv["cusname"]),
			m("cusaddr", $inv["cusaddr"]),
			m("cusvatno", $inv["cusvatno"]),
			m("cordno", $inv["cordno"]),
			m("chrgvat", $inv["chrgvat"]),
			m("terms", $inv["terms"]),
			m("odate", $odate[$key]),
			m("subtot", $inv["subtot"]),
			m("vat", $inv["vat"]),
			m("total", $inv["total"]),
			m("balance", $inv["total"]),
			m("done", "n"),
			m("prd", PRD_DB),
			m("div", USER_DIV),
			m("ctyp", $inv["ctyp"]),
			m("tval", $inv["tval"]),
			m("jobid", $invid),
			m("remarks", $inv["remarks"])
		);

		$newinv->setOpt($cols);
		$newinv->run(DB_INSERT);

		/* fetch last invoice id */
		$invid = lastinvid();

		/* fetch recurring invoice items */
		$recinv_i->setOpt(grp(
			m("where", "invid='$inv[invid]' AND div='".USER_DIV."'")
		));
		$recinv_i->run();

		/* add items to new non stock invoice */
		while($stkd = $recinv_i->fetch_array()){
			$cols = grp(
				m("invid", $invid),
				m("qty", $stkd["qty"]),
				m("unitcost", $stkd["unitcost"]),
				m("amt", $stkd["amt"]),
				m("accid", $stkd["account"]),
				m("description", $stkd["description"]),
				m("vatex", $stkd["vatex"]),
				m("div", USER_DIV)
			);

			$newinv_i->setOpt($cols);
			$newinv_i->run(DB_INSERT);
		}
	}

	pglib_transaction("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$OUT = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Recurring Non-stock Invoices Processed</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>New non-stock Invoices have been created from Recurring Invoices</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $OUT;

}

function edit_items ($_POST,$err="")
{

	extract ($_POST);

	db_connect ();

	$search_arr = array ();
	$send_invids = "";
	foreach ($invids AS $each){
		$search_arr[] = "invid='$each'";
		$send_invids .= "<input type='hidden' name='invids[]' value='$each'>\n";
	}

	$search_string = implode (" OR ",$search_arr);

	#get a list of all the items for these selected recurring invoices ....
	$get_invs = "SELECT distinct(description) FROM rnons_inv_items WHERE $search_string";
	$run_invs = db_exec($get_invs) or errDie ("Unable to get invoice information.");
	if (pg_numrows($run_invs) < 1){
		#no entries found ?
	}else {
		while ($iarr = pg_fetch_array ($run_invs)){

			//price we get from the recurring invoice
			$r_sql = "SELECT unitcost,description,qty FROM rnons_inv_items WHERE description = '$iarr[description]' AND ($search_string) LIMIT 1";
			$run_r = db_exec($r_sql) or errDie ("Unable to get stock unit cost information.");
			if (pg_numrows($run_r) < 1){
				$unitcost = 0;
				$stock_desc = "";
				$qty = 1;
			}else {
				$unitcost = sprint (pg_fetch_result ($run_r,0,0));
				$stock_desc = pg_fetch_result ($run_r,0,1);
				$qty = pg_fetch_result ($run_r,0,2);
			}

			#compile a list of the items ...
			$listing .= "
				<tr class='".bg_class()."'>
					<td width='50%'>$stock_desc <input type='hidden' size='10' name='description[]' value='$iarr[description]'></td>
					<td>$qty</td>
					<td>".CUR." $unitcost</td>
					<td>".CUR." <input type='text' size='10' name='unitcost[]' value='$unitcost'></td>
				</tr>";
		}
	}




	$display = "
		<h4>Change Price On Selected Non Stock Invoices</h4>
		<table ".TMPL_tblDflts." width='60%'>
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='update_prices'>
			$send_invids
			<tr>
				<th>Stock Item</th>
				<th>Qty</th>
				<th>Current Amount</th>
				<th>New Amount</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Update'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function update_prices ($_POST)
{

	extract ($_POST);

	if (!isset($description) OR !is_array ($description))
		return edit_items($_POST,"<li class='err'>No Items To Update.</li><br>");

	$search_arr = array ();
	foreach ($invids AS $each){
		$search_arr[] = "invid='$each'";
	}

	$inv_search = implode (" OR ",$search_arr);

	db_connect ();

	#go through all stock items ... and update the items ...
	foreach ($description AS $key => $value){
		$upd_sql = "UPDATE rnons_inv_items SET unitcost = '$unitcost[$key]', amt = qty * '$unitcost[$key]' WHERE description = '$value' AND ($inv_search)";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update recurring invoice item information.");
	}

	db_connect ();

	foreach ($invids AS $invid){

		#new invoice ... new totals ...
		$total = 0;
		$vat = 0;

		#update the totals of each ... based on its 'new' items ...
		$get_inv = "SELECT * FROM rnons_invoices WHERE invid = '$invid' LIMIT 1";
		$run_inv = db_exec($get_inv) or errDie ("Unable to get recurring invoice information.");
		if (pg_numrows($run_inv) > 0){
			#found!
			$inv_arr = pg_fetch_array ($run_inv);
			#ok .. incl ... go through each item ... checking its vat code ...
			$get_items = "SELECT * FROM rnons_inv_items WHERE invid = '$inv_arr[invid]'";
			$run_items = db_exec ($get_items) or errDie ("Unable to get received items information.");
			if (pg_numrows ($run_items) > 0){
				while ($item_arr = pg_fetch_array ($run_items)){
					#get vatcode for this item ...
					$get_vat = "SELECT vat_amount FROM vatcodes WHERE id = '$item_arr[vatex]' LIMIT 1";
					$run_vat = db_exec($get_vat) or errDie ("Unable to get vat amount for invoice item.");
					if (pg_numrows($run_vat) > 0){
						#vat code found ... 
						$vat_perc = sprint (pg_fetch_result ($run_vat,0,0));
					}else {
						$vat_perc = 0;
					}
					#add amount to total ..
					if ($vat_perc == 0){
						$total += $item_arr['amt'];
					}else {
						if ($inv_arr['chrgvat'] == "no"){
							$total += $item_arr['amt'];
							$vat += sprint(($item_arr['amt'] / 100) * $vat_perc);
						}else {
							$vat_amt = sprint (($item_arr['amt']) - ($item_arr['amt'] / (($vat_perc/100)+1)));
							$vat = $vat + $vat_amt;
							$total = $total + ($item_arr['amt'] - $vat_amt);
						}
					}
				}
			}

		}

		$subtot = sprint ($total);
		$total = sprint ($total+$vat);
		$vat = sprint ($vat);

		#update this invoice with the new totals ...
		$upd_sql = "UPDATE rnons_invoices SET subtot = '$subtot', vat = '$vat', total = '$total', balance = '$total' WHERE invid = '$invid'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update recurring invoices information.");

	}

	header ("Location: rec-invoice-view.php");

}



?>
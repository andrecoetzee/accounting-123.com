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

$pure = isset($_GET["xls"]);
# show asset ledger
if (isset($_GET["type"]) && $_GET["type"] == "p") {
	$OUTPUT = prevAssetLedg($pure);
} else {
	$OUTPUT = AssetLedg ($pure);
}

if (isset($_GET["xls"])) {
	require_lib("xls");
	$OUTPUT = clean_html($OUTPUT);
	StreamXLS("Assets", $OUTPUT);
}

require ("template.php");

# show stock
function AssetLedg ($pure = false) {

	extract ($_REQUEST);

	$fields = array();
	$fields["group_id"] = 0;
	$fields["type_id"] = 0;

	extract ($fields, EXTR_SKIP);

	# Set up table to display in
	$Assets = "";

	if (!$pure) {
		$sql = "SELECT grpid, grpname FROM cubit.assetgrp ORDER BY grpname ASC";
		$group_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");

		$group_sel = "
		<select name='group_id' style='width: 100%' onchange='javascript:document.form.submit();'>
			<option value='0'>[All]</option>";
		while ($group_data = pg_fetch_array($group_rslt)) {
			$sel = ($group_id == $group_data["grpid"]) ? "selected='t'" : "";

			$group_sel .= "
			<option value='$group_data[grpid]'>
				$group_data[grpname]
			</option>";
		}
		$group_sel .= "</select>";

		$sql = "SELECT id, name FROM cubit.asset_types ORDER BY name ASC";
		$type_rslt = db_exec($sql) or errDie("Unable to retrieve asset types.");

		$type_sel = "
		<select name='type_id' style='width: 100%' onchange='javascript:document.form.submit();'>
			<option value='0'>[All]</option>";
		while ($type_data = pg_fetch_array($type_rslt)) {
			$sel = ($type_id == $type_data["id"]) ? "selected='t'" : "";

			$type_sel .= "
			<option value='$type_data[id]'>
				$type_data[name]
			</option>";
		}
		$type_sel .= "</select>";

		$Assets .= "
		<h3>Asset Ledger</h3>
		<style>
			td, th {font-size: 0.65em;}
		</style>
		<form method='post' action='".SELF."' name='form'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Group</th>
				<th>Type</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$group_sel</td>
				<td>$type_sel</td>
			</tr>
		</table>
		</form>";
	}

	$Assets .= "
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Group</th>
		<th>Serial</th>
		<th>Location</th>
		<th>Description</th>
		<th>Date Received/Purchased</th>
		<th>Date Added</th>
		<th>Cost Amount</th>
		<th>Units</th>
		<th>Net Value</th>
		<th>Accumulated Depreciation</th>
		<th>Monthly Depreciation</th>";

	if (!$pure) {
		$Assets .= "
		<th colspan='5'>Options</th>";
	}

	$Assets .= "
	</tr>";

	db_connect();

	$where = array();
	if ($group_id) {
		$where[] = "grpid='$group_id'";
	}
	if ($type_id) {
		$where[] = "type_id='$type_id'";
	}
	$where = implode(" AND ", $where);
	if (!empty($where)) {
		$where = " AND $where";
	}

	$i = 0;
	$tot=0;
	$totnet=0;
	$totunit = 0;
	$Sl = "SELECT id, serial, des, locat, amount FROM assets
		   WHERE remaction IS NULL AND div = '".USER_DIV."' $where";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Asset Ledger from database.");
	if (pg_numrows ($Rs) < 1) {
		unset($_GET["xls"]);
		return "
					<li>There are no Assets recorded on Cubit. </li>
			        <table border=0 cellpadding='2' cellspacing='1'>
				        <tr>
				        	<th>Quick Links</th>
				        </tr>
				        <tr bgcolor='".bgcolorg()."'>
							<td><a href='asset-new.php'>New Asset</a></td>
				        </tr>
				        <script>document.write(getQuicklinkSpecial());</script>
			        </table>";
	}
	while ($Led1 = pg_fetch_array ($Rs))
	{

		#get details
		$get_full = "SELECT * FROM assets
					 WHERE id = '$Led1[id]' AND des = '$Led1[des]' AND
					 	locat = '$Led1[locat]' AND amount = '$Led1[amount]'
					 	AND remaction IS NULL
					 LIMIT 1";
		$run_full = db_exec($get_full) or errDie("Unable to get asset information.");
		if(pg_numrows($run_full) < 1){
			return "
					<li>There are no Assets recorded on Cubit. </li>
			        <table border=0 cellpadding='2' cellspacing='1'>
				        <tr>
				        	<th>Quick Links</th>
				        </tr>
				        <tr bgcolor='".bgcolorg()."'>
				        	<td><a href='asset-new.php'>New Asset</a></td>
				        </tr>
				        <script>document.write(getQuicklinkSpecial());</script>
			        </table>";
		}
		$Led = pg_fetch_array($run_full);

		#get total
		$get_count = "SELECT id FROM assets
					  WHERE des = '$Led1[des]' AND locat = '$Led1[locat]' AND
					  	amount = '$Led1[amount]' AND serial='$Led1[serial]' AND
					  	remaction IS NULL";
		$run_count = db_exec($get_count) or errDie("Unable to get asset information.");
		$asset_amount = pg_numrows($run_count);

		$netval = sprint($Led['amount'] - $Led['accdep']);
		$Led['amount'] = sprint($Led['amount']);

		# Get group
		db_conn("cubit");
		$sql = "SELECT * FROM assetgrp WHERE grpid = '$Led[grpid]' AND div = '".USER_DIV."'";
		$grpRslt = db_exec($sql);
		$grp = pg_fetch_array($grpRslt);

		// Should we allow this asset to be removed?
 		db_conn("cubit");
 		$sql = "SELECT * FROM assetledger WHERE assetid='$Led[id]'";
 		$rem_rslt = db_exec($sql) or errDie("Unable to retrieve the assetledger from Cubit.");

 		if (!pg_num_rows($rem_rslt)) {
 			$rem = "<td><a href='asset-rem.php?id=$Led[id]'>Remove</a></td>";
 		} else {
 			$rem = "<td>&nbsp;</td>";
 		}
//		$rem = "<td><a href='asset-rem.php?id=$Led[id]'>Remove</a></td>";

//		if($Led['nonserial'] == "1"){
//			$asset_amount = $Led['split_from'];
//		}else {
//			$asset_amount = "1";
//		}

		$Led['amount'] = sprint ($Led['amount'] * $asset_amount);
		$netval = sprint ($netval * $asset_amount);

		if ($Led["serial"] == "Not Serialized") {
			$quantity = $Led["serial2"];
		} else {
			$quantity = $asset_amount;
		}

		$Assets .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$grp[grpname]</td>
							<td>$Led[serial]</td>
							<td>$Led[locat]</td>
							<td>$Led[des]</td>
							<td>$Led[bdate]</td>
							<td>$Led[date]</td>
							<td align='right' nowrap>".CUR." $Led[amount]</td>
							<td>$quantity</td>
							<td align='right' nowrap>".CUR." $netval</td>
							<td align='right' nowrap>
								".CUR." ".sprint($Led["accdep"])."
							</td>
							<td align='right' nowrap>
								".CUR." ".sprint($Led["dep_month"])."
							</td>";

		if (!$pure) {
			$Assets .= "
							<td><a href='asset-edit.php?id=$Led[id]'>Edit</a></td>
							<td><a href='asset-dep.php?id=$Led[id]'>Depreciation</a></td>
							<td><a href='asset-app.php?id=$Led[id]'>Appreciation</a></td>
							<td><a href='asset-rep.php?id=$Led[id]'>Report</a></td>
							$rem";
		}

		$Assets .= "
						</tr>";
						$i++;

		$tot = $tot + $Led['amount'];
		$totnet = $totnet + $netval;
		$totunit = $totunit + $quantity;
	}

	$tot = sprint($tot);
	$totnet = sprint($totnet);
	$Assets .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='6'>Total Assets: $i </td>
						<td align='right' nowrap>".CUR." $tot</td>
						<td align='right'>$totunit</td>
						<td align='right' nowrap>".CUR." $totnet</td>
					</tr>";

	$Assets .= "</table>";

	if (!$pure) {
		$Assets .= "
						<br>
						<form action='".SELF."' method='GET' name='form'>
							<input type='submit' name='xls' value='Export to spreadsheet'>
						</form>
						<p>
						<table ".TMPL_tblDflts." width='15%'>
					        <tr><td><br></td></tr>
							<tr>
								<th>Quick Links</th>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>";
	}
	return $Assets;

}


function prevAssetLedg ($pure = false)
{

	# Set up table to display in
	$Assets = "";

	if (!$pure) {
		$Assets .= "
		<h3>Asset Ledger</h3>";
	}

	$Assets .= "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Group</th>
							<th>Serial</th>
							<th>Qty</th>
							<th>Location</th>
							<th>Description</th>
							<th>Date Bought</th>
							<th>Date Added</th>
							<th>Cost Amount</th>
							<th>Net Value</th>
							<th>Removed/Sold</th>
							<th>Sale Date</th>
							<th>Sale Price</th>
							<th>Sale Profit</th>";

	if (!$pure) {
		$Assets .= "
							<th colspan='5'>Options</th>";
	}

	$Assets .= "
						</tr>";

	db_connect();

	$i = 0;
	$tot=0;
	$totnet=0;

	$Sl = "SELECT * FROM assets_prev WHERE div = '".USER_DIV."' ORDER BY saledate DESC";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Asset Ledger from database.");
	if (pg_numrows ($Rs) < 1) {
		unset($_GET["xls"]);
		return "
			<li>There are no Previously removed Assets recorded on Cubit. </li>
	        <table border='0' cellpadding='2' cellspacing='1'>
		        <tr>
		        	<th>Quick Links</th>
		        </tr>
		        <tr bgcolor='".bgcolorg()."'>
		        	<td><a href='asset-new.php'>New Asset</a></td>
		        </tr>
		        <script>document.write(getQuicklinkSpecial());</script>
	        </table>";
	}
	while ($Led = pg_fetch_array ($Rs))
	{
		$netval = sprint($Led['amount'] - $Led['accdep']);
		$Led['amount'] = sprint($Led['amount']);

		# Get group
		db_conn("cubit");
		$sql = "SELECT * FROM assetgrp WHERE grpid = '$Led[grpid]' AND div = '".USER_DIV."'";
		$grpRslt = db_exec($sql);
		$grp = pg_fetch_array($grpRslt);

		if ($Led["remaction"] == "Sale") {
			$salecols = "
				<td>$Led[saledate]</td>
				<td>".CUR." $Led[saleamt]</td>
				<td>".CUR." $Led[profit]</td>";
		} else {
			$salecols = "
				<td colspan='3' align='center'>N/A</td>";
		}

		$tot = $tot + $Led['amount'];
		$totnet = $totnet + $netval;

		if ($Led["nonserial"] == 1) {
			$qty = $Led["serial2"];
		} else {
			$qty = 1;
		}

		$Assets .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$grp[grpname]</td>
							<td>$Led[serial]</td>
							<td>$qty</td>
							<td>$Led[locat]</td>
							<td>$Led[des]</td>
							<td>$Led[bdate]</td>
							<td>$Led[date]</td>
							<td align='right'>".CUR." $Led[amount]</td>
							<td align='right'>".CUR." $netval</td>
							<td align='center'>$Led[remaction]</td>
							$salecols";

		if (!$pure) {
			$Assets .= "
							<td><a href='asset-rep.php?id=$Led[asset_id]'>Report</a></td>";
		}

		$Assets .= "
						</tr>";
						$i++;
	}

	$tot = sprint($tot);
	$totnet = sprint($totnet);
	$Assets .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='7'>Total Assets: $i </td>
						<td align='right'>".CUR." $tot</td>
						<td align='right'>".CUR." $totnet</td>
					</tr>";

	$Assets .= "</table>";

	if (!$pure) {
		$Assets .= "
						<br>
						<form action='".SELF."' method='GET' name='form'>
							<input type='hidden' name='type' value='p' />
							<input type='submit' name='xls' value='Export to spreadsheet'>
						</form>
						<p>
						<table ".TMPL_tblDflts." width='15%'>
					        <tr><td><br></td></tr>
					        <tr>
					        	<th>Quick Links</th>
					        </tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>";
	}
	return $Assets;

}


?>

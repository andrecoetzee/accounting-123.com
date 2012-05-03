<?

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "get_file":
			$OUTPUT = get_file();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = import_file();
			break;
	}
} else {
	$OUTPUT = get_file();
}

require ("template.php");



function get_file ()
{
	$display = "
		<h3>Import Assets</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<td><li class='err'>The File To Imported Should Be	Delimited By a Pipe \"|\" And Have No Text Qualifier</li></td>
			</tr>
			<tr>
				<td><li class='err'>File Must Contain The Following Fields:</li></td>
			</tr>
			<tr>
				<td><li class='err'>
				ProductCode|SerialNumber|Description|VatCode|Barcode|QuantityAvailable|QuantityTotal|Category|SubCategory|Price1|<br>
				UnitOfMeasure1|Price2|UnitOfMeasure2|Price3|UnitOfMeasure3|Price4|UnitOfMeasure4|Price5|UnitOfMeasure5|StockUnit|<br>
				HireDaysBetweenService1|DateOfLastService1|ServiceType1|HireDaysBetweenService2|DateOfLastService2|ServiceType2|<br>
				HireDaysBetweenService3|DateOfLastService3|ServiceType3|SupplierName|CostPrice|HireItem|DateBought|OtherBranch|<br>
				OtherBranchSerialNumber|EngineNumber|EngineSerial|EngineType|DateModified|DepreciationType|DepreciationValue|<br>
				DepreciationPercentage|DepreciatedValue|InWorkshop|UserDefined1|UserDefined2|UserDefined3|UserDefined4|UserDefined5|<br>
				Deleted|ServiceType|ServiceItem</li></td>
			</tr>
			<tr>
				<th>Select File To Import</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='file' name='filename'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Upload'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function confirm()
{

	extract ($_REQUEST);

	$sql = "DROP TABLE cubit.import_assets";
	db_exec($sql);

	$sql = "CREATE TABLE cubit.import_assets (
				id serial,
				serial varchar,
				locat varchar,
				des varchar,
				date date,
				bdate date,
				amount numeric default 0,
				div numeric default 2,
				grpid numeric default 0,
				accdep numeric default 0,
				dep_perc numeric default 0,
				dep_month varchar,
				serial2 varchar,
				team_id numeric default 0,
				puramt numeric default 0,
				conacc numeric default 0,
				saledate date,
				saleamt numeric default 0,
				invid numeric default 0,
				autodepr_date date,
				sdate date,
				temp_asset varchar default 'n',
				nonserial varchar,
				type_id numeric default 0,
				split_from numeric default 1,
				days numeric default 0,
				on_hand numeric default 0,
				svdate date,
				price numeric(16,2) default 0,
				per_day numeric(16,2) default 0,
				per_hour numeric(16,2) default 0,
				per_week numeric(16,2) default 0
			)";
	@db_exec($sql);

	$sql = "DELETE FROM import_assets";
	db_exec($sql) or errDie("Unable to clear import table.");

	$lines = file($_FILES["filename"]["tmp_name"]);

	$counter = 0;
	$items_out = "";
	foreach ($lines as $line) {
		$line_arr = explode("|", trim($line));

		$sql = "
			INSERT INTO import_assets (
				serial, locat, des, date, bdate, 
				amount, div, grpid, accdep, dep_perc, dep_month, 
				serial2, team_id, puramt, conacc, saledate, saleamt, 
				invid, autodepr_date, sdate, temp_asset, nonserial, type_id, 
				split_from, days, on_hand, svdate, price, per_day, 
				per_hour, per_week
			) VALUES (
				'$line_arr[1]', '', '$line_arr[2]', '$line_arr[38]', '$line_arr[32]', 
				'$line_arr[30]', '2', '4', '0', '$line_arr[41]', 'no', 
				'$line_arr[36]', '0', '$line_arr[30]', '0', 'now', '0.00', 
				'0', 'now', 'now', 'n', '0', '0', 
				'1', '0', '0', 'now', '$line_arr[9]', '$line_arr[9]', 
				'$line_arr[13]', '$line_arr[11]'
			)";
		db_exec($sql) or errDie("Unable to add asset $line_arr[1] $line_arr[2].");
		$line_id = pglib_lastid("cubit.import_assets", "id");

		// Convert the date from YYYYMMDD to YYYY-MM-DD
		if (strlen($line_arr[32]) == 8 && !preg_match("/\-/", $line_arr["32"])) {
			$line_arr[32] = substr($line_arr['32'],0,4) . "-" .
						substr($line_arr['32'],4,2) . "-" .
						substr($line_arr['32'],6,2);
		}
		
		if (strlen($line_arr[38]) == 8 && !preg_match("/\-/", $line_arr["32"])) {
			$line_arr[38] = substr($line_arr['38'],0,4) . "-" .
						substr($line_arr['38'],4,2) . "-" .
						substr($line_arr['38'],6,2);
		}

		if (empty($line_arr[1])) {
			$line_arr[36] = $line_arr[6];
		}

		$items_out .= "
			<tr class='".bg_class()."'>
				<td><input type='hidden' name='serial[]' value='$line_arr[1]' />$line_arr[1]</td>
				<td><input type='hidden' name='locat[]' value='' /></td>
				<td><input type='hidden' name='des' value='$line_arr[2]' />$line_arr[2]</td>
				<td><input type='hidden' name='date' value='$line_arr[38]' />$line_arr[38]</td>
				<td><input type='hidden' name='bdate' value='$line_arr[32]' />$line_arr[32]</td>
				<td align='right'><input type='hidden' name='puramt' value='$line_arr[30]' />".sprint($line_arr[30])."</td>
				<td align='right'><input type='text' name='per_hour[$line_id]' value='".sprint($line_arr[13])."' size='5' /></td>
				<td align='right'><input type='text' name='per_day[$line_id]' value='".sprint($line_arr[9])."' size='5' /></td>
				<td align='right'><input type='text' name='per_week[$line_id]' value='".sprint($line_arr[11])."' size='5' /></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Import Assets</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Serial</th>
				<th>Location</th>
				<th>Description</th>
				<th>Date Received/Purchased</th>
				<th>Date Added</th>
				<th>Cost Amount</th>
				<th>Per Hour</th>
				<th>Per Day</th>
				<th>Per Week</th>
			</tr>
			$items_out
		</table>
			<input type='submit' value='Write &raquo' />
		</form>
		</center>";
	return $OUTPUT;

}



function import_file()
{

	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.import_assets";
	$import_rslt = db_exec($sql) or errDie("Unable to retrieve import assets.");

	$counter = 0;
	while ($row = pg_fetch_array($import_rslt)) {
		$sql = "
			INSERT INTO cubit.assets (
				serial, locat, des, date, bdate, 
				amount, div, grpid, accdep, dep_perc, 
				dep_month, serial2, team_id, puramt, conacc, 
				saledate, saleamt, invid, autodepr_date, 
				sdate, temp_asset, nonserial, type_id, 
				split_from, days, on_hand, svdate
			) VALUES (
				'$row[serial]', '$row[locat]', '$row[des]', '$row[date]', '$row[bdate]', 
				'$row[amount]', '$row[div]', '$row[grpid]', '$row[accdep]', '$row[dep_perc]', 
				'$row[dep_month]', '$row[serial2]', '$row[team_id]', '$row[puramt]', '$row[conacc]', 
				'$row[saledate]', '$row[saleamt]', '$row[invid]', '$row[autodepr_date]', 
				'$row[sdate]', '$row[temp_asset]', '$row[nonserial]', '$row[type_id]', 
				'$row[split_from]', '$row[days]', '$row[on_hand]', '$row[svdate]'
			)";
		db_exec($sql) or errDie("Unable to add asset.");
		$asset_id = pglib_lastid("cubit.assets", "id");

		$sql = "
			INSERT INTO hire.basis_prices (
				assetid, per_day, 
				per_hour, per_week
			) VALUES (
				'$asset_id', '".$per_day[$row["id"]]."', 
				'".$per_hour[$row["id"]]."', '".$per_week[$row["id"]]."'
			)";
		db_exec($sql) or errDie("Unable to add price.");
		$counter++;
	}

	return "$counter Assets Have Been Imported.";

}


?>
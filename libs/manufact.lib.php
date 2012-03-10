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


/* use like:
	$jobcards = new getManufact("jobcards", array("order"=>"id DESC"));

	while ($row = $jobcards->fetch_array()) {
		...
	}

	POSSIBILITES IN array()
		cols: columns to return
		where: conditionals
		order: order expression
		limit: limit rows returns
		offset: offset in table to start returning rows
*/ 

class getManufact extends dbSelect {
	function getManufact($table, $ar = array()) {
		$this->dbSelect($table, "manufact", $ar);
	}
}

class deleteManufact extends dbDelete {
	function deleteManufact($table, $where = "(true)") {
		$this->dbDelete($table, "manufact", $where);
	}
}

define ("MAX_MESSAGES", 5);

function getStockCostPrice($stock_id, $qty=1)
{

	$sql = "SELECT csprice FROM cubit.stock WHERE stkid='$stock_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$csprice = pg_fetch_result($stock_rslt, 0);

	return $csprice * $qty;

}



function getLabourCost($type, $hours)
{

	$sql = "SELECT (cost_per_hour * '$hours') AS cost WHERE type='$type'";
	$cost_rslt = db_exec($sql) or errDie("Unable to retrieve labour cost.");
	$cost = pg_fetch_result($cost_rslt, 0);
	
	return sprint($cost);

}



function getMachineGroup($machine_id)
{

	$sql = "SELECT group_id FROM manufact.machines WHERE id='$machine_id'";
	$group_rslt = db_exec($sql) or errDie("Unable to retrieve machine group.");
	$group_id = pg_fetch_result($group_rslt, 0);

	return $group_id;

}



function getMachineCost($machine_id, $minutes)
{
	$sql = "SELECT cost_per_minute FROM manufact.machines WHERE id='$machine_id'";
	$machine_rslt = db_exec($sql) or errDie("Unable to retrieve machine cost.");
	$cost_per_minute = pg_fetch_result($machine_rslt, 0);

	return $cost_per_minute * $minutes;

}



function isRecipe($jobcard_id)
{

	// Is this a learning job.
	if (empty($jobcard_id)) {
		return 0;
	}

	$sql = "SELECT recipe FROM manufact.jobcards WHERE id='$jobcard_id'";
	$recipe_rslt = db_exec($sql) or errDie("Unable to check if recipe.");
	$recipe = pg_fetch_result($recipe_rslt, 0);

	if ($recipe == "yes") {
		return 1;
	} else {
		return 0;
	}

}



function isCompleted($jobcard_id)
{

	$sql = "SELECT completion FROM manufact.jobcards WHERE id='$jobcard_id'";
	$completion_rslt = db_exec($sql) or errDie("Unable to check if job completed.");
	$completion = pg_fetch_result($completion_rslt, 0);

	return $completion;

}



function newRecipeId()
{

	$sql = "SELECT max(id) FROM manufact.jobcards WHERE id<100000000000";
	$id_rslt = db_exec($sql) or errDie("Unable to retrieve recipe id.");
	$id = pg_fetch_result($id_rslt, 0);

	if (empty($id)) {
		$id = 1;
	}
	return $id + 1;

}



function newStockBarcode()
{

	// Create a value for the default barcode
	$sql = "SELECT max(bar) FROM cubit.stock
			WHERE bar BETWEEN 700000000000 AND 799999999999";
	$bar_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$barcode = pg_fetch_result($bar_rslt, 0);

	if (empty($barcode)) {
		$barcode = 700000000000;
	}

	$barcode++;

	return $barcode;

}



function getProductionStore()
{

	$sql = "SELECT whid FROM exten.warehouses WHERE whname='Production'";
	$wh_rslt = db_exec($sql) or errDie("Unable to retrieve warehouse.");
	$whid = pg_fetch_result($wh_rslt, 0);

	return $whid;

}



function getStore($stock_id)
{

	$sql = "SELECT whid FROM cubit.stock WHERE stkid='$stock_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$whid = pg_fetch_array($stock_rslt, 0);

	return $whid;

}



function getJobDescription($jobcard_id)
{

	$sql = "SELECT description FROM manufact.jobcards WHERE id='$jobcard_id'";
	$des_rslt = db_exec($sql) or errDie("Unable to retrieve job description.");
	$des = pg_fetch_result($des_rslt, 0);

	return $des;

}



function getUserStore($user_id=USER_ID)
{

	$sql = "SELECT whid FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve user store.");

	if (empty($user_rslt)) {
		$whid = pg_fetch_result($user_rslt, 0);
	} else {
		$sql = "SELECT value FROM manufact.manufact_settings
				WHERE field='default_whid'";
		$wh_rslt = db_exec($sql) or errDie("Unable to retrieve store.");
		$whid = pg_fetch_result($wh_rslt, 0);
	}

	return $whid;

}



function getUserInfo($userid)
{

	db_conn("cubit");
	$sql = "SELECT username, admin FROM users WHERE userid='$userid'";
	$rslt = db_exec($sql) or errDie("Error fetching user information.");

	return pg_fetch_array($rslt);

}



function getMessages($usertype)
{

	$messages_db = new dbSelect("general_messages", "manufact");
	$messages_db->run();

	$messages = array();
	$i = 0;
	while ($messages_data = $messages_db->fetch_array()) {
		$privs = explode (",", $messages_data["privs"]);

		foreach ($privs as $priv) {
			$i++;
			if ($i > MAX_MESSAGES) {
				break 2;
			}
			if ($priv == $usertype || $priv == "all" || empty($priv)) {
				$messages[] = nl2br(base64_decode($messages_data["message"]));
			}
		}
	}

	return $messages;

}



function getStockDetails($jobcard_id)
{

	// Retrieve stock items allocated to this job
	db_conn("manufact");
	$sql = "SELECT DISTINCT stock_id FROM stock_items WHERE job_id='$jobcard_id'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve stock items from Cubit.");

	$items_ar = array();
	while ($item_data = pg_fetch_array($rslt)) {
		// Retrieve stock details
		db_conn("cubit");
		$sql = "SELECT * FROM stock WHERE stkid='$item_data[stock_id]'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock details from Cubit.");
		$stock_data = pg_fetch_array($stock_rslt);

		$items_ar[$item_data["stock_id"]]["barcode"] = $stock_data["bar"];
		$items_ar[$item_data["stock_id"]]["stock_code"] = $stock_data["stkcod"];
		$items_ar[$item_data["stock_id"]]["description"] = $stock_data["stkdes"];

		// Retrieve the quanities
		db_conn("manufact");
		$sql = "SELECT qty FROM stock_items WHERE stock_id='$item_data[stock_id]' AND job_id='$jobcard_id' LIMIT 1";
		$qty_rslt = db_exec($sql) or errDie("Unable to retrieve stock qty from Cubit.");

//		$items_ar[$item_data["stock_id"]]["qty"] = pg_num_rows($qty_rslt);
		$items_ar[$item_data["stock_id"]]["qty"] = pg_fetch_result ($qty_rslt,0,0);

	}

	return $items_ar;

}



function getOperation($job_item_id)
{
	if (!empty($job_item_id)) {
		$sql = "SELECT * FROM manufact.jobcard_items WHERE id='$job_item_id'";
		$ji_rslt = db_exec($sql) or errDie("Unable to retrieve job items.");
		$ji_data = pg_fetch_array($ji_rslt);

		if (pg_num_rows($ji_rslt)) {
			$sql = "SELECT * FROM manufact.jobcard_items WHERE jobid='$ji_data[jobid]'
			ORDER BY id ASC";
			$ji_rslt = db_exec($sql) or errDie("Unable to retrieve job items.");
		}

		$i = 0;
		while ($ji_data = pg_fetch_array($ji_rslt)) {
			$i++;

			if ($ji_data["id"] == $job_item_id) {
				return $i;
			}
		}
	}

	return false;

}



function maxOperations($jobcard_id)
{
	$sql = "SELECT * FROM manufact.jobcard_items WHERE jobid='$jobcard_id'";
	$job_rslt = db_exec($sql) or errDie("Unable to retrieve jobcard items.");
	$op_count = pg_num_rows($job_rslt);

	return $op_count;

}



function expected_date($job_id)
{

	$sql = "SELECT extract('epoch' FROM max(time_end)) FROM manufact.schedule_entries
	WHERE jobcard_id='$job_id'";
	$ed_rslt = db_exec($sql) or errDie("Unable to retrieve schedule entries.");
	$expected_date = pg_fetch_result($ed_rslt, 0);

	if (!empty($expected_date)) {
		$expected_date = date("d-m-Y", $expected_date);
	} else {
		$expected_date = "";
	}

	return $expected_date;

}



class Wip
{
	var $job_id;
	var $job_item_id;
	var $user_id;
	var $wip_id;

	function Wip($job_id, $user_id)
	{
		if ($job_id < 10000000000) {
			return false;
		}
	
		// Has this job already been added
		db_conn("manufact");
		$sql = "SELECT * FROM wip WHERE job_id='$job_id'";
		$wip_rslt = db_exec($sql) or errDie("Unable to retrieve job id's");

		if (!pg_num_rows($wip_rslt)) {
			db_conn("manufact");
			$sql = "
			INSERT INTO wip (job_id, user_id, start_time, end_time)
				VALUES ('$job_id', '$user_id', CURRENT_TIMESTAMP, NULL)";
			db_exec($sql) or errDie("Unable to start work in progress.");

			$this->job_id = $job_id;
			$this->wip_id = pglib_lastid("wip", "id");
		} else {
			$wip_data = pg_fetch_array($wip_rslt);

			$this->job_id = $wip_data["job_id"];
			$this->wip_id = $wip_data["id"];
		}
		$this->user_id = $user_id;
	}

	function endWip()
	{
		db_conn("manufact");
		$sql = "
		UPDATE wip SET end_time=CURRENT_TIMESTAMP
			WHERE id='".$this->wip_id."'";
		db_exec($sql) or errDie("Unable to end work in progress.");

		return true;
	}

	function addEvent($thing_id, $job_item_id, $op_type, $op_event, $time="")
	{
		if (isItemRecipe($job_item_id)) {
			return false;
		}

		// Hack to fix the double setup time problem
		if ($op_type == "machine" && $op_event == "Setup" && empty($job_item_id)) {
			return false;
		}
		
		if (empty($time)) {
			$time = "CURRENT_TIMESTAMP";
		} else {
			$time = "'$time'";
		}

		db_conn("manufact");
		$sql = "
		INSERT INTO wip_events (wip_id, thing_id, job_item_id, op_type,
			op_event, time, user_id)
			VALUES ('".$this->wip_id."', '$thing_id', '$job_item_id',
				'$op_type', '$op_event', $time, '".$this->user_id."')";
		db_exec($sql) or errDie("Unable to add work in progress event.");
		return true;
	}

	function LearnJobEvent($thing_id, $job_item_id, $op_type, $op_event, $time)
	{
		if (isItemRecipe($job_item_id)) {
			return false;
		}

		db_conn("manufact");
	}

}



function isItemRecipe($jobitem_id)
{

	if ($jobitem_id > 100000000000) {	// Learning Job
		return isRecipe($jobitem_id);
	} else {
		$sql = "SELECT jobid FROM manufact.jobcard_items WHERE id='$jobitem_id'";
		$jobid_rslt = db_exec($sql) or errDie("Unable to retrieve item.");
		$jobid = pg_fetch_result($jobid_rslt, 0);

		return isRecipe($jobid);
	}

}



function getPerformed($thing_id, $op_type, $op_event)
{

	switch ($op_type) {
		case "stock":
			db_conn("cubit");
			$sql = "SELECT stkcod FROM stock WHERE stkid='$thing_id'";
			$stk_rslt = db_exec($sql) or errDie("Unable to retrieve stock description.");

			return pg_fetch_result($stk_rslt, 0);
			break;
		case "machine":
			db_conn("manufact");
			$sql = "SELECT machine_name FROM machines WHERE id='$thing_id'";
			$mach_rslt = db_exec($sql) or errDie("Unable to retrieve machine name.");

			return pg_fetch_result($mach_rslt, 0);
			break;
	}

}



function machineMinutes($machine_id, $from_date, $to_date)
{

	$sql = "SELECT *,extract('epoch' FROM time) AS e_time FROM manufact.wip_events
	WHERE thing_id='$machine_id' AND op_type='machine' AND op_event='Setup'
		AND time BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'";
	$start_rslt = db_exec($sql) or errDie("Unable to retrieve machine minutes.");

	$total_minutes = 0;

	while ($start_data = pg_fetch_array($start_rslt)) {
		$sql = "SELECT extract('epoch' FROM time) AS e_time FROM manufact.wip_events
		WHERE thing_id='$machine_id' AND op_type='machine' AND op_event='End'
			AND time BETWEEN '$start_data[time]' AND '$to_date 23:59:59'";
		$end_rslt = db_exec($sql) or errDie("Unable to retrieve machine minutes.");

		$e_time_start = $start_data["e_time"];
		$e_time_end = pg_fetch_result($end_rslt, 0);

		$seconds = $e_time_end - $e_time_start;
		$minutes = $seconds / 60;

		$total_minutes += ceil($minutes);
	}

	return $total_minutes;

}



function calculateJobProgress($job_id)
{

	$sql = "SELECT * FROM manufact.jobcard_items WHERE jobid='$job_id'";
	$ji_rslt = db_exec($sql) or errDie("Unable to retrieve jobcard_items");

	$progress = array(
		"done"=>array(),
		"undone"=>array()
	);
	while ($ji_data = pg_fetch_array($ji_rslt)) {
		if ($ji_data["done"] == "yes") {
			$progress["done"][] = $ji_data["id"];
		} else {
			$progress["undone"][] = $ji_data["id"];
		}
	}

	$c_done = count($progress["done"]);
	$c_undone = count($progress["undone"]);
	$c_total = $c_done + $c_undone;

	if (!$c_done || !$c_total) {
		return sprint(0);
	} else {
		return sprint(($c_done / $c_total) * 100);
	}

}



function atSubcontractor($job_id)
{

	$sql = "SELECT status FROM manufact.jobcards
			WHERE id='$job_id' AND status='subcontractor'";
	$subc_rslt = db_exec($sql) or errDie("Unable to check if job at subcontractor.");

	if (pg_num_rows($subc_rslt)) {
		return 1;
	} else {
		return 0;
	}

}



//-----------------------------------------------------------------------------

function updateProductionCost($jobcard_id, $jobitem_id, $machine_id, $user_id, $minutes, $qty)
{

	$operation = getOperation($jobitem_id);
	$cost = getMachineCost($machine_id, $minutes);

	$suspense_acc = qryAccountsName("Production Suspense");
	$suspense_acc = $suspense_acc["accid"];

	$recovery_acc = qryAccountsName("Production Recovery");
	$recovery_acc = $recovery_acc["accid"];

	$refnum = getRefnum();
	$sysdate = date("Y-m-d");

	$details = "Production cost for job number: $jobcard_id Step: $operation";

	writetrans($suspense_acc, $recovery_acc, $sysdate, $refnum, $cost, $details);

	// Update job costs
	$sql = "
		INSERT INTO manufact.job_costs (
			jobcard_id, jobitem_id, machine_id, user_id, 
			cost, total_time, qty
		) VALUES (
			'$jobcard_id', '$jobitem_id', '$machine_id', '$user_id', 
			'$cost', '$minutes', '$qty'
		)";
	db_exec($sql) or errDie("Unable to update job costs.");

	$sql = "
		SELECT (cost_per_hour / 60) AS cost_per_minute, labour_type
		FROM manufact.jobcard_items
			LEFT JOIN manufact.labour_types ON jobcard_items.labour_type=labour_types.id
		WHERE jobcard_items.id='$jobitem_id'";
	$cost_rslt = db_exec($sql) or errDie("Unable to retrieve labour cost.");
	list($labour_cost, $labour_type) = pg_fetch_array($cost_rslt);
	
	$labour_cost *= $minutes;
	
	$sql = "
	INSERT INTO manufact.labour_cost (jobcard_id, jobitem_id, user_id, cost, total_time, labour_type)
	VALUES ('$jobcard_id', '$jobitem_id', '$user_id', '$labour_cost', '$minutes', '$labour_type')";
	db_exec($sql) or errDie("Unable to update labour cost.");

	return 1;

}



function jobCompleted($jobcard_id)
{

	pglib_transaction("BEGIN");

	$refnum = getRefnum();
	$sysdate = date("Y-m-d");

	$suspense_acc = qryAccountsName("Production Suspense");
	$suspense_acc = $suspense_acc["accid"];

	$production_acc = qryAccountsName("Inventory in Production");
	$production_acc = $production_acc["accid"];

	$inventory_acc = qryAccountsName("Inventory");
	$inventory_acc = $inventory_acc["accid"];

	$details = "Stock manufactured on job $jobcard_id";

	$sql = "SELECT DISTINCT(stock_id) FROM manufact.stock_items WHERE job_id='$jobcard_id'";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$total_cost = 0;
	while ($items_data = pg_fetch_array($items_rslt)) {
		$sql = "SELECT count(stock_id) FROM manufact.stock_items WHERE job_id='$jobcard_id' AND stock_id='$items_data[stock_id]'";
		$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
		$qty = pg_fetch_result($qty_rslt, 0);

		$sql = "SELECT * FROM cubit.stock WHERE stkid='$items_data[stock_id]'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stock_data = pg_fetch_array($stock_rslt);

		// Get production stock
		$sql = "SELECT stkid FROM cubit.stock WHERE stkcod='$stock_data[stkcod]' AND whid='".getProductionStore()."'";
		$production_rslt = db_exec($sql) or errDie("Unable to retrieve production.");
		$items_data["stock_id"] = pg_fetch_result($production_rslt, 0);

		$cost = getStockCostPrice($items_data["stock_id"], $qty);
		$total_cost += $cost;

		$sql = "SELECT stkcod FROM cubit.stock WHERE stkid='$items_data[stock_id]'";
		$stkcod_rslt = db_exec($sql) or errDie("Unable to retrieve stock code.");
		$stkcod = pg_fetch_result($stkcod_rslt, 0);

		$sql = "SELECT stkid FROM cubit.stock WHERE stkcod='$stkcod' AND whid='".getProductionStore()."'";
		$stk_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stock_id = pg_fetch_result($stk_rslt, 0);

		writetrans($suspense_acc, $production_acc, $sysdate, $refnum, $cost, $details);
		reduceStock($stock_id, $qty, $details, 1);
	}

	$sql = "SELECT * FROM manufact.jobcards WHERE id='$jobcard_id'";
	$job_rslt = db_exec($sql) or errDie("Unable to retrieve jobcard.");
	$job_data = pg_fetch_array($job_rslt);

	$sql = "SELECT sum(cost) FROM manufact.job_costs WHERE jobcard_id='$jobcard_id'";
	$costs_rslt = db_exec($sql) or errDie("Unable to retrieve job costs.");
	$total_cost += pg_fetch_result($costs_rslt, 0);

	if ($total_cost != 0 && $job_data["order_qty"] != 0) {
 		$csprice = $total_cost / $job_data["order_qty"];
 	} else {
 		$csprice = 0;
 	}

	// Update cost per unit
	$sql = "UPDATE cubit.stock SET csprice='$csprice' WHERE stkid='$job_data[stock_id]'";
	db_exec($sql) or errDie("Unable to update cost per unit.");

	$sql = "UPDATE manufact.jobcards SET order_qty='0' WHERE id='$job_data[id]'";
	$job_rslt = db_exec($sql) or errDie("Unable to update jobcards.");

	writetrans($inventory_acc, $suspense_acc, $sysdate, $refnum, $total_cost, $details);
	increaseStock($job_data["stock_id"], $job_data["order_qty"], $details, 1);

	$sql = "SELECT csamt, units FROM cubit.stock WHERE stkid='$job_data[stock_id]'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve costs and units.");
	list($csamt, $units) = pg_fetch_array($stock_rslt);

	if ($csamt != 0 && $units != 0) {
		$csprice = ($csamt / $units);
	} else {
		$csprice = 0;
	}

	$sql = "UPDATE cubit.stock SET csprice='$csprice' WHERE stkid='$job_data[stock_id]'";
	db_exec($sql) or errDie("Unable to update cost per unit.");
	
	pglib_transaction("COMMIT");

}



function unallocateStockFromJob($jobcard_id, $stock_id, $units=1)
{

	pglib_transaction("BEGIN");

	for ($i = 0; $i < $units; $i++) {
		$sql = "SELECT max(id) FROM manufact.inventory_allocate WHERE job_id='$jobcard_id' AND stock_id='$stock_id'";
		$alloc_rslt = db_exec($sql) or errDie("Unable to retrieve last allocation.");
		$alloc_id = pg_fetch_result($alloc_rslt, 0);

		$sql = "DELETE FROM manufact.inventory_allocate WHERE id='$alloc_id'";
		db_exec($sql) or errDie("Unable to remove allocated units.");
	}

	pglib_transaction("COMMIT");

	return true;

}



function allocateStockToJob($jobcard_id, $stock_id, $units=1)
{

	$refnum = getRefnum();
	$sysdate = date("Y-m-d");

	$stock_wh = getStore($stock_id);
	$production_wh = getProductionStore();

	$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_data = pg_fetch_array($stock_rslt);

	// Do we have this stock in the production store already
	$sql = "SELECT stkid FROM cubit.stock WHERE stkcod='$stock_data[stkcod]' AND whid='$production_wh'";
	$production_rslt = db_exec($sql)
		or errDie("Unable to retrieve production stock.");
	$production_id = pg_fetch_result($production_rslt, 0);

	if (!pg_num_rows($production_rslt)) {
		$sql = "
			INSERT INTO cubit.stock (
				stkcod, stkdes, prdcls, csamt, units, 
				buom, suom, rate, 
				minlvl, whid, shelf, row, 
				type, catname, classname, 
				com, csprice, serd, 
				lcsprice, vatcode, supplier1, 
				supplier2, supplier3, rfidfreq, 
				rfidrate, size, post_production, 
				treatment, div
			) VALUES (
				'$stock_data[stkcod]', '$stock_data[stkdes]', '$stock_data[prdcls]', '0', '0', 
				'$stock_data[buom]', '$stock_data[suom]', '$stock_data[rate]', 
				'$stock_data[minlvl]', '$production_wh', '$stock_data[shelf]', '$stock_data[row]', 
				'$stock_data[type]', '$stock_data[catname]', '$stock_data[classname]', 
				'$stock_data[com]', '$stock_data[csprice]', '$stock_data[serd]', 
				'$stock_data[lcsprice]', '$stock_data[vatcode]', '$stock_data[supplier1]', 
				'$stock_data[supplier2]', '$stock_data[supplier3]', '$stock_data[rfidfreq]', 
				'$stock_data[rfidrate]', '$stock_data[size]', '$stock_data[post_production]', 
				'$stock_data[treatment]', '".USER_DIV."'
			)";
		db_exec($sql) or errDie("Unable to create in stock store.");
		$production_id = pglib_lastid("cubit.stock", "stkid");

		for ($i = 1; $i <= 12; $i++) {
			$sql = "
				INSERT INTO \"$i\".stkledger (
					stkid, stkcod, stkdes, trantype, 
					edate, qty, csamt, balance, bqty, details, div, yrdb
				) VALUES (
					'$production_id', '$stock_data[stkcod]', '$stock_data[stkdes]', 'bal', 
					'$sysdate', '0', '0', '0', '0','Balance', '".USER_DIV."','".YR_DB."'
				)";
			db_exec($sql) or errDie("Unable to create ledger entries.");
		}
	}

	$details = "Stock allocated to job $jobcard_id";

	reduceStock($stock_id, $units, $details);
	increaseStock($production_id, $units, $details);

	$production_acc = qryAccountsName("Inventory in Production");
	$production_acc = $production_acc["accid"];

	$inventory_acc = qryAccountsName("Inventory");
	$inventory_acc = $inventory_acc["accid"];

	$amount = getStockCostPrice($stock_id) * $units;

	writetrans($production_acc, $inventory_acc, $sysdate, $refnum, $amount, $details);

	return;

}



function reduceStock($stock_id, $units, $reason, $job_end=0)
{

	$sysdate = date("Y-m-d");

	$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_data = pg_fetch_array($stock_rslt);

	$sql = "UPDATE cubit.stock SET units=(units-'$units') WHERE stkid='$stock_id'";
	db_exec($sql) or errDie("Unable to reduce stock units.");

	$price = getStockCostPrice($stock_id, $units, $reason);

	// Update inventory ledger
	if ($job_end) {
		$price = $stock_data["csprice"] * $units;
	}
	stockrec($stock_id, $stock_data["stkcod"], $stock_data["stkdes"], "ct", $sysdate, $units, $price, $reason);

	// Update cost of all units
	$sql = "UPDATE cubit.stock SET csamt=(csamt-'$price') WHERE stkid='$stock_id'";
	db_exec($sql) or errDie("Unable to update stock cost.");

	return;

}



function increaseStock($stock_id, $units, $reason, $job_end=0)
{

	$sysdate = date("Y-m-d");

	$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_data = pg_fetch_array($stock_rslt);

	$sql = "UPDATE cubit.stock SET units=(units+'$units') WHERE stkid='$stock_id'";
	db_exec($sql) or errDie("Unable to reduce stock units.");

	$price = getStockCostPrice($stock_id, $units, $reason);
	// Update inventory ledger
	if ($job_end) {
		$price = $stock_data["csprice"] * $units;
	}
	stockrec($stock_id, $stock_data["stkcod"], $stock_data["stkdes"], "dt",
		$sysdate, $units, $price, $reason);

	// Update cost of all units
	$sql = "UPDATE cubit.stock SET csamt=(csamt+'$price') WHERE stkid='$stock_id'";
	db_exec($sql) or errDie("Unable to update stock cost.");

	return;

}



function maxId($table, $column)
{

	$sql = "SELECT max($column) FROM $table";
	$max_rslt = db_exec($sql) or errDie("Unable to retrieve max.");
	$max = pg_fetch_result($max_rslt, 0);

	return $max;

}



function averageSalesPrice($stkid)
{

	$sql = "SELECT sum(amt) AS total_amt, count(stkid) AS count
			FROM cubit.inv_items WHERE stkid='$stkid'";
	$invi_rslt = db_exec($sql) or errDie("Unable to retrieve average price.");
	$invi_data = pg_fetch_array($invi_rslt);

	$average = $invi_data["total_amt"] - $invi_data["count"];

	return $average;

}



function averageSalesQty($stkid, $from_date, $to_date, $prd)
{

	$DAYS = 60 * 60 * 24;
	$WEEKS = $DAYS * 7;
	$MONTHS = $DAYS * 30;
	
	$times = getDTEpoch("$to_date 23:59:59") - getDTEpoch("$from_date 0:00:00");
	$days = $times / $DAYS;
	
	if ($days < $MONTHS) {
		$MONTHS = $days;
	}
	
	$total_days = 0;

	$sql = "SELECT sum(qty) FROM cubit.inv_items
				LEFT JOIN cubit.invoices
					ON inv_items.invid = invoices.invid
			WHERE odate BETWEEN '$from_date' AND '$to_date' AND stkid='$stkid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	$inv_qty = pg_fetch_result($inv_rslt, 0);

	if (empty($inv_qty)) {
		$inv_qty = 0;
	}

	$from = explode("-", $from_date);
	$to = explode("-", $to_date);

	$from_time = mktime(0, 0, 0, $from[1], $from[2], $from[0]);
	$to_time = mktime(0, 0, 0, $to[1], $to[2], $to[0]);
	switch ($prd) {
		case "daily":
			$total_time = $from_time - $to_time;
			$total_days = (int)($total_time / $DAYS);
			break;
		case "weekly":
			$total_time = $from_time - $to_time;
			$total_days = (int)($total_time / $WEEKS);
			break;
		case "monthly":
			$total_time = $from_time - $to_time;
			$total_days = (int)($total_time / $MONTHS);
			break;
	}
	$total_days = abs($total_days);

	if ($inv_qty && $total_days) {
		$average = ceil($inv_qty / $total_days);
	} else {
		$average = 0;
	}

	return $average;

}



function maxSalesQty($stkid, $prd)
{

	$DAYS = 60 * 60 * 24;
	$WEEKS = $DAYS * 7;
	$MONTHS = $DAYS * 30;

	// Retrieve min and max dates
	$sql = "SELECT max(odate) AS max, min(odate) AS min
			FROM cubit.inv_items
				LEFT JOIN cubit.invoices ON inv_items.invid = invoices.invid
			WHERE stkid='$stkid'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
	$item_data = pg_fetch_array($item_rslt);

	$from_date = $item_data["min"];
	$to_date = $item_data["max"];

	$max = 0;
	if ($from_date && $to_date) {
		$from = explode("-", $from_date);
		$to = explode("-", $to_date);

		$from_time = mktime(0, 0, 0, $from[1], $from[2], $from[0]);
		$to_time = mktime(0, 0, 0, $to[1], $to[2], $to[0]);

		switch ($prd) {
			case "daily":
				for ($i = $from_time; $i <= $to_time; $i += $DAYS) {
					$sql = "SELECT sum(qty) FROM cubit.inv_items
								LEFT JOIN cubit.invoices
									ON inv_items.invid = invoices.invid
							WHERE odate='".date("Y-m-d", $i)."' AND stkid='$stkid'";
					$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
					$qty = pg_fetch_result($qty_rslt, 0);

					if ($qty > $max) $max = $qty;
				}
				break;
			case "weekly":
				for ($i = $from_time; $i <= $to_time; $i += $WEEKS) {
					$from = date("Y-m-d", $i);
					$to = date("Y-m-d", ($i + $WEEKS));

					$sql = "SELECT sum(qty) FROM cubit.inv_items
								LEFT JOIN cubit.invoices
									ON inv_items.invid = invoices.invid
							WHERE odate BETWEEN '$from' AND '$to' AND stkid='$stkid'";
					$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
					$qty = pg_fetch_result($qty_rslt, 0);

					if ($qty > $max) $max = $qty;
				}
				break;
			case "monthly":
				for ($i = $from_time; $i <= $to_time; $i += $MONTHS) {
					$from = date("Y-m-d", $i);
					$to = date("Y-m-d", ($i + $MONTHS));

					$sql = "SELECT sum(qty) FROM cubit.inv_items
								LEFT JOIN cubit.invoices
									ON inv_items.invid = invoices.invid
							WHERE odate BETWEEN '$from' AND '$to' AND stkid='$stkid'";
					$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
					$qty = pg_fetch_result($qty_rslt, 0);

					if ($qty > $max) $max = $qty;
				}
		}
	}

	return $max;

}



function recalculateLeadTimes($supid, $stkid="all")
{

	$DAYS = 60 * 60 * 24;

	if (is_numeric($stkid)) {
		$stkid_sql = " AND stkid='$stkid'";
	} else {
		$stkid_sql = "";
	}

	$unions = array();
	for ($i = 1; $i <= 12; $i++) {
		$unions[] = "
		SELECT id, pur_items.purid, stkid, supid, '$i' AS prd,
			extract('epoch' FROM pdate) AS from_time,
			extract('epoch' FROM pur_items.ddate) AS to_time
			FROM \"$i\".pur_items
				LEFT JOIN \"$i\".purchases ON pur_items.purid = purchases.purid
			WHERE supid='$supid' $stkid_sql";
	}
	$unions[] = "
	SELECT id, pur_items.purid, stkid, supid, '$i' AS prd,
		extract('epoch' FROM pdate) AS from_time,
		extract('epoch' FROM pur_items.ddate) AS to_time
		FROM cubit.pur_items
			LEFT JOIN cubit.purchases ON pur_items.purid = purchases.purid
		WHERE supid='$supid' $stkid_sql";
	$sql = implode(" UNION ", $unions) . " ORDER BY id DESC";
	$pur_rslt = db_exec($sql) or errDie("Unable to retrieve purchases.");

	$count = pg_num_rows($pur_rslt);

	$stkid_count = array();
	$stkid_times = array();
	while ($pur_data = pg_fetch_array($pur_rslt)) {

		$total_time = $pur_data["to_time"] - $pur_data["from_time"];
		$total_days = $total_time / $DAYS;
		if ($total_days < 1) $total_days = 1;

		if (!isset($stkid_count[$pur_data["stkid"]])) {
			$stkid_count[$pur_data["stkid"]] = 0;
			$stkid_times[$pur_data["stkid"]] = 0;

		} elseif ($stkid_count[$pur_data["stkid"]] >= 3) {
			continue;

		} else {
			$stkid_count[$pur_data["stkid"]]++;
		}

		$stkid_times[$pur_data["stkid"]] += $total_days;
	}

	pglib_transaction("BEGIN");
	foreach ($stkid_times as $stkid=>$total_days) {
		$lead_time = ($total_days / $count);
		$lead_time = round($lead_time);

		if ($lead_time <= 0) {
			$lead_time = 30;
		}

		$sql = "SELECT supid, stkid FROM cubit.lead_times
					WHERE supid='$supid' AND stkid='$stkid'";
		$lt_rslt = db_exec($sql) or errDie("Unable to retrieve lead times.");

		if (pg_num_rows($lt_rslt)) {
			$sql = "UPDATE cubit.lead_times SET lead_time='$lead_time'
						WHERE supid='$supid' AND stkid='$stkid'";
		} else {
			$sql = "INSERT INTO cubit.lead_times (supid, stkid, lead_time)
						VALUES ('$supid', '$stkid', '$lead_time')";
		}
		db_exec($sql) or errDie("Unable to add lead time.");
	}
	pglib_transaction("COMMIT");

}



function getLeadTime($supid, $stkid)
{

	recalculateLeadTimes($supid, $stkid);

	$sql = "SELECT lead_time FROM cubit.lead_times
				WHERE supid='$supid' AND stkid='$stkid'";
	$lt_rslt = db_exec($sql) or errDie("Unable to retrieve lead time.");
	$lead_time = pg_fetch_result($lt_rslt, 0);

	return $lead_time;

}



function purExpectedDate($purid, $date_fmt="d-m-Y")
{

	$DAYS = 60 * 60 * 24;

	$sql = "SELECT extract('epoch' FROM pdate) AS e_pdate, lead_time
				FROM cubit.purchases
					LEFT JOIN cubit.lead_times
						ON purchases.purid = lead_times.purid
				 WHERE purchases.purid='$purid'";
	$pur_rslt = db_exec($sql) or errDie("Unable to retrieve lead times.");
	$pur_data = pg_fetch_array($pur_rslt, 0);

	if (empty($pur_data["lead_time"])) {
		$lead_time = 30;
	}

	// Convert the lead time from days to seconds
	$lead_time = $pur_data["lead_time"] * $DAYS;
	$expected_date = $pur_data["e_pdate"] + $lead_time;

	return date($date_fmt, $expected_date);

}



function isLearningJob($jobcard_id)
{

	$sql = "SELECT learning_job FROM manufact.jobcards WHERE id='$jobcard_id'";
	$ljob_rslt = db_exec($sql) or errDie("Unable to check learning job.");
	$ljob = pg_fetch_result($ljob_rslt, 0);
	
	if ($ljob == "yes") {
		return 1;
	} else {
		return 0;
	}

}



function allocMore($job_id, $stk_id, $qty)
{

	for ($i = 0; $i < $qty; $i++) {
		$sql = "SELECT count(id) FROM manufact.inventory_allocate WHERE job_id='$job_id' AND stock_id='$stk_id'";
		$inv_alloc_rslt = db_exec($sql) or errDie("Unable to retrieve alloc count.");
		$inv_alloc = pg_fetch_result($inv_alloc_rslt, 0);
		if (empty($inv_alloc)) $inv_alloc = 0;
		
		$sql = "SELECT allocated FROM manufact.allocated WHERE job_id='$job_id' AND stock_id='$stk_id'";
		$allocated_rslt = db_exec($sql) or errDie("Unable to retrieve allocation.");
		$allocated = pg_fetch_result($allocated_rslt, 0);
		if (empty($allocated)) $allocated = 0;
		
		if ($inv_alloc > $allocated) {
			$sql = "UPDATE cubit.stock SET alloc=(alloc - 1) WHERE stkid='$stk_id' AND whid!='3'";
			db_exec($sql) or errDie("Unable to update stock allocation.");
		}
		
		$sql = "SELECT * FROM manufact.allocated WHERE stock_id='$stk_id' AND job_id='$job_id'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve allocation.");
		
		if (pg_num_rows($rslt)) {
			$sql = "UPDATE manufact.allocated SET allocated=(allocated + 1) WHERE stock_id='$stk_id' AND job_id='$job_id'";
			db_exec($sql) or errDie("Unable to update stock allocation.");
		} else {
			$sql = "INSERT INTO manufact.allocated (stock_id, job_id, allocated) VALUES ('$stk_id', '$job_id', '1')";
			db_exec($sql) or errDie("Unable to update stock allocation.");
		}
	}

}



function allocLess($job_id, $stk_id, $qty)
{

	for ($i = 0; $i < $qty; $i++) {
		$sql = "SELECT count(id) FROM manufact.inventory_allocate WHERE job_id='$job_id' AND stock_id='$stk_id'";
		$inv_alloc_rslt = db_exec($sql) or errDie("Unable to retrieve alloc count.");
		$inv_alloc = pg_fetch_result($inv_alloc_rslt, 0);
		if (empty($inv_alloc)) $inv_alloc = 0;
		
		$sql = "SELECT allocated FROM manufact.allocated WHERE job_id='$job_id' AND stock_id='$stk_id'";
		$allocated_rslt = db_exec($sql) or errDie("Unable to retrieve allocation.");
		$allocated = pg_fetch_result($allocated_rslt, 0);
		if (empty($allocated)) $allocated = 0;
		
		if ($inv_alloc > $allocated) {
			$sql = "UPDATE cubit.stock SET alloc=(alloc + 1) WHERE stkid='$stk_id' AND whid!='3'";
			db_exec($sql) or errDie("Unable to update stock allocation.");
		}
		
		$sql = "SELECT * FROM manufact.allocated WHERE stock_id='$stk_id' AND job_id='$job_id'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve allocation.");
		
		if (pg_num_rows($rslt)) {
			$sql = "UPDATE manufact.allocated SET allocated=(allocated - 1) WHERE stock_id='$stk_id' AND job_id='$job_id'";
			db_exec($sql) or errDie("Unable to update stock allocation.");
		} else {
			$sql = "INSERT INTO manufact.allocated (stock_id, job_id, allocated) VALUES ('$stk_id', '$job_id', '0')";
			db_exec($sql) or errDie("Unable to update stock allocation.");
		}
	}

}



?>

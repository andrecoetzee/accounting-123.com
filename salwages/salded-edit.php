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
require ("../settings.php");
require ("../core-settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirmDeduct ($_POST);
			break;
		case "write":
			if (isset ($_REQUEST["submit_ded"])){
				$OUTPUT = writeDeduct ($_POST);
			}else {
				$OUTPUT = confirmDeduct ($_POST);
			}
			break;
		default:
			$OUTPUT = enterDeduct ($_POST["refno"]);
	}
} else {
	$OUTPUT = enterDeduct ($_GET["refno"]);
}

# display output
require ("../template.php");



# enter new data
function enterDeduct ($refno)
{

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($refno, "string", 1, 20, "Invalid reference number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# get deduction info
	db_connect ();

	$sql = "SELECT * FROM salded WHERE refno='$refno' AND div = '".USER_DIV."'";
	$salRslt = db_exec ($sql) or errDie ("Unable to select salary deduction info from database.");
	if (pg_numrows ($salRslt) < 1) {
		return "Invalid reference number.";
	}
	$mySal = pg_fetch_array ($salRslt);

	core_connect ();

// 	$dedacc = "<select name='accid' style='width: 230'>";
// 	$sql = "SELECT * FROM accounts WHERE (catid='E10' OR catid='I10') AND div = '".USER_DIV."'";
// 	$accRslt = db_exec($sql);
// 	$numrows = pg_numrows($accRslt);
// 	if(empty($numrows)){
// 		$paid = "There are no Balance accounts yet in Cubit.";
// 	}else{
// 		while($acc = pg_fetch_array($accRslt)){
// 			if ( $acc["accid"] == $mySal["accid"] ) {
// 				$sel = "selected";
// 			} else {
// 				$sel = "";
// 			}
// 			$dedacc .= "<option $sel value='$acc[accid]'>$acc[accname]</option>";
// 		}
// 	}
// 	$dedacc .= "</select>";

	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, $mySal["type"]);

	$check1 = "";
	$check2 = "";
	if (isset ($mySal["type"]) AND $mySal["type"] == "Percentage"){
		$check2 = "checked='yes'";
	}else {
		$check1 = "checked='yes'";
	}

	if ($mySal['creditor'] == "In House"){
		$accountsdrop = mkAccSelect("accid", $accid, ACCTYPE_IE);
	}else {
		$accountsdrop = mkAccSelect("accid", $accid, ACCTYPE_B);
	}

	$enterDeduct = "
		<script>
			function inHouse() {
				frm = getObjectById('dedfrm');
				frm.creditor.value='In House';
				frm.details.value='In House';
			}
		</script>
		<h3>Edit salary deduction</h3>
		<table ".TMPL_tblDflts.">
		<form id='dedfrm' action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='refno' value='$refno'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name of deduction</td>
				<td align='center'><input type='text' size='20' name='deduction' value='$mySal[deduction]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Creditor name</td>
				<td align='center'><input type='hidden' name='creditor' value='$mySal[creditor]'>$mySal[creditor]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference no.</td>
				<td align='center'>$mySal[refno]</td>
			</tr>";

	if ( $mySal['creditor'] == "In House" ) {
		$enterDeduct .= "
			<tr class='".bg_class()."'>
				<td>Expense Account</td>
				<td align='center'>".mkAccSelect("expaccid", $expaccid, ACCTYPE_IE)."</td>
			</tr>
			<input type='hidden' name='accid' value='0'>";
	} else {
		$enterDeduct .= "
			<tr class='".bg_class()."'>
				<td>Creditor Account</td>
				<td align='center'>".mkAccSelect("accid", $accid, ACCTYPE_B)."</td>
			</tr>
			<input type='hidden' name='expaccid' value='0'>";
	}

		$enterDeduct .= "
			<tr class='".bg_class()."'>
				<td>Creditor details</td>
				<td align='center'><input type='text' size='20' name='details' value='$mySal[details]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Deduction Type</td>
				<td>
					<input type='radio' name='type' value='Amount' $check1> Amount
					<input type='radio' name='type' value='Percentage' $check2> Percent
				</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $enterDeduct;

}


# confirm new data
function confirmDeduct ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($deduction, "string", 1, 100, "Invalid deduction name.");
	$v->isOk ($creditor, "string", 1, 100, "Invalid creditor name.");
	$v->isOk ($refno, "string", 1, 20, "Invalid reference number.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account Number.");
	$v->isOk ($expaccid, "num", 1, 20, "Invalid Expense Account Number.");
	$v->isOk ($details, "string", 0, 100, "Invalid creditor details.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	core_connect();

	if ( $creditor == "In House" ) {
		$sql = "SELECT accname FROM accounts WHERE accid='$expaccid'";
		$rslt = db_exec($sql) or errDie("Error reading account name for comfirmation.");

		if ( pg_num_rows($rslt) < 1 ) {
			return "<li class='err'>Expense Account selected is invalid.</li>";
		} else {
			$accname = pg_fetch_result($rslt, 0, 0);
		}
	} else {
		$sql = "SELECT accname FROM accounts WHERE accid='$accid'";
		$rslt = db_exec($sql) or errDie("Error reading account name for comfirmation.");

		if ( pg_num_rows($rslt) < 1 ) {
			return "<li class='err'>Account selected is invalid.</li>";
		} else {
			$accname = pg_fetch_result($rslt, 0, 0);
		}
	}



	db_connect ();

	if ($key == "confirm"){

		$scale_from = array ();
		$scale_to = array ();
		$scale_amount = array ();

		$get_scales = "SELECT * FROM salded_scales WHERE saldedid = (SELECT id FROM salded WHERE refno = '$refno' LIMIT 1)";
		$run_scales = db_exec ($get_scales) or errDie ("Unable to get salary deduction information.");
		if (pg_numrows ($run_scales) > 0){
			while ($darr = pg_fetch_array ($run_scales)){
				$scale_from[] = $darr['scale_from'];
				$scale_to[] = $darr['scale_to'];
				$scale_amount[] = $darr['scale_amount'];
			}
		}
	}

	if (isset ($type) AND $type == "Percentage"){

		# get current scales added
		foreach ($scale_from AS $each => $own){

			$own += 0;
			$scale_to[$each] += 0;
			$scale_amount[$each] += 0;

			# check for zero values
			if ($scale_to[$each] == "0" OR $scale_amount[$each] == "0") {
				continue;
			}

			# first value can be zero, but cant then be greater than the to value
			if ($own >= $scale_to[$each]) {
				continue;
			}

			if (isset ($remove_scale) AND is_array ($remove_scale)){
				$rem = array_keys ($remove_scale);
				if ($each == $rem[0]) 
					continue;
			}

			$scales_hidden .= "
				<input type='hidden' name='scale_from[]' value='$own'>
				<input type='hidden' name='scale_to[]' value='$scale_to[$each]'>
				<input type='hidden' name='scale_amount[]' value='$scale_amount[$each]'>";
			$scales_list .= "
				<tr class='".bg_class()."'>
					<td>$own</td>
					<td>$scale_to[$each]</td>
					<td>$scale_amount[$each] <input type='submit' name='remove_scale[$each]' value='Remove'></td>
				</tr>";
		}

		# check for cross linked pairs
		if (
			!isset ($new_scale_from) OR !isset ($new_scale_to) OR !isset ($new_scale_amount) OR 
			empty ($new_scale_from) OR empty ($new_scale_to) OR empty($new_scale_amount) OR 
			search_scale_array ($scale_from, $scale_to, $new_scale_from) OR search_scale_array ($scale_from, $scale_to, $new_scale_to)
		) {
			$scale_error = "<tr><td colspan='3'><li class='err'>Duplicate Or Overlapping Scale Exists</li></td></tr>";
		}else {
			$scale_error = "";
			$scales_hidden .= "
				<input type='hidden' name='scale_from[]' value='$new_scale_from'>
				<input type='hidden' name='scale_to[]' value='$new_scale_to'>
				<input type='hidden' name='scale_amount[]' value='$new_scale_amount'>";
			$scales_list .= "
				<tr class='".bg_class()."'>
					<td>$new_scale_from</td>
					<td>$new_scale_to</td>
					<td>$new_scale_amount <input type='submit' name='remove_scale[".($each+1)."]' value='Remove'></td>
				</tr>";
		}

		$scales_display = "
			$scales_hidden
			<tr class='".bg_class()."'>
				<th colspan='3'>Percentage Deduction Scales</th>
			</tr>
			$scale_error
			<tr>
				<th>From Amount</th>
				<th>To Amount</th>
				<th>Percentage</th>
			</tr>
			$scales_list
			<tr class='".bg_class()."'>
				<td><input type='text' size='5' name='new_scale_from'></td>
				<td><input type='text' size='5' name='new_scale_to'></td>
				<td>
					<input type='text' size='5' name='new_scale_amount'>
					<input type='submit' name='submit_scale' value='Add'>
				</td>
			</tr>";
		
	}else {
		$scales_display = "";
	}

	$confirmDeduct = "
		<h3>Confirm new salary deduction</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='deduction' value='$deduction'>
			<input type='hidden' name='creditor' value='$creditor'>
			<input type='hidden' name='refno' value='$refno'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='expaccid' value='$expaccid'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='type' value='$type'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name of deduction</td>
				<td align='center'>$deduction</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Creditor name</td>
				<td align='center'>$creditor</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reference no.</td>
				<td align='center'>$refno</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Number</td>
				<td align='center'>$accname</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Creditor details</td>
				<td align='center'>$details</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Deduction Type</td>
				<td align='center'>$type</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' name='submit_ded' value='Write &raquo;'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			$scales_display
		</table>
		</form>
		<br>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmDeduct;

}



# write new data
function writeDeduct ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($deduction, "string", 1, 100, "Invalid deduction name.");
	$v->isOk ($creditor, "string", 1, 100, "Invalid creditor name.");
	$v->isOk ($refno, "string", 1, 20, "Invalid reference number.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($expaccid, "num", 1, 20, "Invalid Expense Account number.");
	$v->isOk ($details, "string", 0, 100, "Invalid creditor details.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");
	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_connect ();

	# write to db
	$sql = "
		UPDATE salded 
		SET type='$type', accid='$accid', expaccid='$expaccid', deduction='$deduction', creditor='$creditor', details='$details' 
		WHERE refno='$refno' AND div = '".USER_DIV."'";
	$salRslt = db_exec ($sql) or errDie ("Unable to add salary deduction to database.", SELF);

	# check for scales
	$get_check = "SELECT id FROM salded_scales WHERE saldedid = (SELECT id FROM salded WHERE refno = '$refno' LIMIT 1)";
	$run_check = db_exec ($get_check) or errDie ("Unable to update salary deduction information.");
	if (pg_numrows ($run_check) > 0){

		$del_sql = "DELETE FROM salded_scales WHERE saldedid = (SELECT id FROM salded WHERE refno = '$refno' LIMIT 1)";
		$run_del = db_exec ($del_sql) or errDie ("Unable to get salary deduction information.");

		if (isset ($scale_from) AND is_array ($scale_from)) {
			foreach ($scale_from AS $each => $own){
				$ins_sql = "
					INSERT INTO salded_scales (
						saldedid, scale_from, scale_to, scale_amount
					) VALUES (
						(SELECT id FROM salded WHERE refno = '$refno' LIMIT 1), '$scale_from[$each]', '$scale_to[$each]', '$scale_amount[$each]'
					)";
				$run_ins = db_exec ($ins_sql) or errDie ("Unable to get scales information.");
			}
		}

	}

	$writeDeduct = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Salary deduction edited</th>
			</tr>
			<tr class='datacell'>
				<td>Salary deduction, $deduction, has been successfully edited.</td>
			</tr>
		</table>
		<br>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeDeduct;
}


function search_scale_array ($fromarray=array(), $toarray=array(), $value)
{

	$value += 0;

	foreach ($fromarray AS $each => $own){

		if ($value >= $own) {
			if ($value <= $toarray[$each]) {
				return TRUE;
			}
		}
	}
	return FALSE;

}


?>
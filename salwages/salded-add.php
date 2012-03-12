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
require ("../libs/ext.lib.php");

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
			$OUTPUT = enterDeduct ();
	}
} else {
	$OUTPUT = enterDeduct ();
}

# display output
require ("../template.php");



# enter new data
function enterDeduct ()
{

	# connect to db
	db_connect ();

	# get last inserted id for new ref no
	// a little hack to make stoopid postgres not return a 1 as last id, when there is no last id
	if ( pg_numrows(db_exec("SELECT 1 FROM salded")) < 1 )
		$lastid = 1;
	else
		$lastid = pglib_lastid ("salded", "id") + 1;

        $refno = "saldeduct". sprintf ("%02d",$lastid);

	$Tp = array("No"=>"No","Yes"=>"Yes");
	$taxables = extlib_cpsel("taxable", $Tp,"No");

	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, "Percentage");

	$check1 = "";
	$check2 = "";
	if (isset ($type) AND $type == "Percentage"){
		$check2 = "checked='yes'";
	}else {
		$check1 = "checked='yes'";
	}


        $enterDeduct = "
		<script>
			function inHouse() {
				frm = getObjectById('dedfrm');
				frm.creditor.value='In House';
				frm.details.value='In House';
			}
		</script>
		<h3>New salary deduction</h3>
		<table ".TMPL_tblDflts.">
		<form id='dedfrm' action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='refno' value='$refno'>
			<input type='hidden' name='catid' value='B10'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Name of deduction</td>
				<td align='center'><input type='text' size='20' name='deduction'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Creditor name</td>
				<td align='center'><input type='text' size='20' name='creditor'></td>
				<td><input type='button' value='In House' onClick='inHouse();'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference no.</td>
				<td align='center'>$refno</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Creditor Details/In House</td>
				<td align='center'><input type='text' size='20' name='details'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Before PAYE: Tax Deductable</td>
				<td align='center'>$taxables</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
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

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deduction, "string", 1, 100, "Invalid deduction name.");
	$v->isOk ($creditor, "string", 1, 100, "Invalid creditor name.");
	$v->isOk ($refno, "string", 1, 20, "Invalid reference number.");
	$v->isOk ($catid, "string", 1, 20, "Invalid Category number.");
	$v->isOk ($details, "string", 0, 100, "Invalid creditor details.");
	$v->isOk ($taxable, "string", 1, 3, "Invalid taxablility option.");
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
	core_connect ();

// 	$dedacc= "<select name='accid' style='width: 230'>";
// 		$sql = "SELECT * FROM accounts WHERE catid = '$catid' AND div = '".USER_DIV."'";
// 		$accRslt = db_exec($sql);
// 		$numrows = pg_numrows($accRslt);
// 		if(empty($numrows)){
// 			$paid = "There are no Balance accounts yet in Cubit.";
// 		}else{
// 			$prevtop = "";
// 			while($acc = pg_fetch_array($accRslt)){
// 				if(isb($acc['accid'])) {
// 					continue;
// 				}
// 				if ( $acc["topacc"] == $prevtop && $acc["accnum"] != "000" ) {
// 					$x = "&nbsp;&nbsp;-&nbsp;&nbsp;$acc[topacc]/$acc[accnum]";
// 				} else {
// 					$x = "$acc[topacc]/$acc[accnum]";
// 					$prevtop = $acc["topacc"];
// 				}
// 				if (isset($accid) AND $accid == $acc['accid']){
// 					$dedacc .= "<option value='$acc[accid]' selected>$x $acc[accname]</option>";
// 				}else {
// 					$dedacc .= "<option value='$acc[accid]'>$x $acc[accname]</option>";
// 				}
// 			}
// 		}
// 	$dedacc .= "</select>";

	// Expense account
// 	$expacc = "<select name='expaccid' style='width: 230'>";
// 	$sql = "SELECT * FROM accounts WHERE (catid='E10' OR catid='I10')AND div='".USER_DIV."'";
// 	$expRslt = db_exec($sql);
// 	$prevtop = "";
// 	while($acc = pg_fetch_array($expRslt)) {
// 		if(isb($acc['accid'])) {
// 			continue;
// 		}
// 		if ( $acc["topacc"] == $prevtop && $acc["accnum"] != "000" ) {
// 			$x = "&nbsp;&nbsp;-&nbsp;&nbsp;$acc[topacc]/$acc[accnum]";
// 		} else {
// 			$x = "$acc[topacc]/$acc[accnum]";
// 			$prevtop = $acc["topacc"];
// 		}
// 		if (isset ($expaccid) AND $expaccid == $acc['accid']){
// 			$expacc .= "<option value='$acc[accid]' selected>$x $acc[accname]</option>";
// 		}else {
// 			$expacc .= "<option value='$acc[accid]'>$x $acc[accname]</option>";
// 		}
// 	}

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
				<tr bgcolor='".bgcolorg()."'>
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
				<tr bgcolor='".bgcolorg()."'>
					<td>$new_scale_from</td>
					<td>$new_scale_to</td>
					<td>$new_scale_amount <input type='submit' name='remove_scale[".($each+1)."]' value='Remove'></td>
				</tr>";
		}

		$scales_display = "
			$scales_hidden
			<tr bgcolor='".bgcolorg()."'>
				<th colspan='3'>Percentage Deduction Scales</th>
			</tr>
			$scale_error
			<tr>
				<th>From Amount</th>
				<th>To Amount</th>
				<th>Percentage</th>
			</tr>
			$scales_list
			<tr bgcolor='".bgcolorg()."'>
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
		<h3>Confirm new Salary Deduction</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='deduction' value='$deduction'>
			<input type='hidden' name='creditor' value='$creditor'>
			<input type='hidden' name='refno' value='$refno'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='taxable' value='$taxable'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='catid' value='$catid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Name of deduction</td>
				<td align='center'>$deduction</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Creditor name</td>
				<td align='center'>$creditor</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference no.</td>
				<td align='center'>$refno</td>
			</tr>";

	if ( $creditor == "In House" ) {
		$confirmDeduct .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>Expense Account</td>
				<td align='center'>".mkAccSelect("expaccid", $expaccid, ACCTYPE_IE)."</td>
			</tr>
			<input type='hidden' name='accid' value='0'>";
	} else {
		$confirmDeduct .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>Creditor Account</td>
				<td align='center'>".mkAccSelect("accid", $accid, ACCTYPE_B)."</td>
			</tr>
			<input type='hidden' name='expaccid' value='0'>";
	}

	$confirmDeduct .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>Creditor details</td>
				<td align='center'>$details</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Deduct Before PAYE</td>
				<td align='center'>$taxable</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
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
		</form>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmDeduct;

}



# write new data
function writeDeduct ($_POST)
{

	# get vars
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
	$v->isOk ($taxable, "string", 1, 3, "Invalid taxablility option.");
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

	# check for duplicate
	$sql = "SELECT refno FROM salded WHERE refno='$refno' AND div = '".USER_DIV."'";
	$chkRslt = db_exec ($sql) or errDie ("Unable to check for duplicate entries.");
	if (pg_numrows ($chkRslt) > 0) {
		return "Entry, with reference number '$refno', already exists in database.";
	}

	# write to db
	$sql = "
		INSERT INTO salded (
			refno, deduction, creditor, details, accid, expaccid, add, type, div
		) VALUES (
			'$refno', '$deduction', '$creditor', '$details', '$accid', '$expaccid', '$taxable', '$type', '".USER_DIV."'
		)";
	$salRslt = db_exec ($sql) or errDie ("Unable to add salary deduction to database.", SELF);
	if (pg_cmdtuples ($salRslt) < 1) {
		return "Unable to add salary deduction to database.";
	}

	$get_id = "SELECT id FROM salded ORDER BY id DESC LIMIT 1";
	$run_id = db_exec ($get_id) or errDie ("Unable to get salary deduction information.");
	if (pg_numrows ($run_id) < 1){
		return "Unable to add salary deduction to database.";
	}

	$saldedid = pg_fetch_result ($run_id,0,0);

	if (isset ($scale_from) AND is_array ($scale_from)) {
		foreach ($scale_from AS $each => $own){
			$ins_sql = "
				INSERT INTO salded_scales (
					saldedid, scale_from, scale_to, scale_amount
				) VALUES (
					'$saldedid', '$scale_from[$each]', '$scale_to[$each]', '$scale_amount[$each]'
				)";
			$run_ins = db_exec ($ins_sql) or errDie ("Unable to get scales information.");
		}
	}

	$writeDeduct = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Salary deduction added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New salary deduction, $deduction, has been successfully added to Cubit.</td>
			</tr>
		</table>
		<br>"
	.mkQuickLinks(
		ql("salded-add.php", "Add Salary Deduction"),
		ql("salded-view.php", "View Salary Deductions"),
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
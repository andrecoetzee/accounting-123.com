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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

if (isset($_GET["invid"])) {
	$_POST["invid"] = $_GET["invid"];
}

# decide what to do
if (isset($_GET["invid"]) && isset($_GET["cont"])) {
	$_GET["stkerr"] = '0,0';
	$_GET["done"] = '';
	$_GET["client"] = '';
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
			case "newpos":
				$OUTPUT = newPos();
				break;
			case "details":
				$OUTPUT = details($_POST);
				break;
			case "update":
				$OUTPUT = write($_POST);
				break;
			default:
				$OUTPUT = details($_POST);
		}
	} else {
		$OUTPUT = details($_POST);
	}
}

# get templete
require("../template.php");




# select department
function view()
{

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT deptid,deptname FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			$depts .= "<option value='$dept[deptid]'>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}


	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<tr>
				<th colspan='2'>New Point of Sale Invoice(Cash)</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			".TBL_BR."
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("pos-invoice-list.php", "View Point of Sale Invoices"),
			ql("customers-new.php", "New Customer")
		);
	return $view;

}



# Default view
function view_err($_POST, $err = "")
{

	# get vars
	extract ($_POST);

	# Query server for depts
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li class='err'>There are no Departments found in Cubit.</li>";
	}else{
		$depts = "<select name='deptid'>";
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
		$depts .= "</select>";
	}

	// Layout
	$view = "
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='400'>
			<input type='hidden' name='key' value='details'>
			<tr>
				<th colspan='2'>New Invoice</th>
			</tr>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Department</td>
				<td valign='center'>$depts</td>
			</tr>
			".TBL_BR."
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("pos-invoice-list.php", "View Point of Sale Invoices"),
			ql("customers-new.php", "New Customer")
		);
	return $view;

}




# create a dummy invoice
function create_dummy($deptid)
{

	db_connect();

	# Dummy Vars
	$cusnum = 0;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;
	$vatnum = "";
	$cusacc = "";
	$telno = "";
	$collection = "";
	$custom_txt = "";

	// $invid = divlastid('pinv', USER_DIV);

	// Retrieve default comments.
	$sql = "SELECT value FROM hire.hire_settings WHERE field='comments'";
	$comm_rslt = db_exec($sql) or errDie("Unable to retrieve default comments.");
	$comm = pg_fetch_result($comm_rslt , 0);

	# insert invoice to DB
	$sql = "
		INSERT INTO hire.hire_invoices (
			deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, 
			delchrg, subtot, vat, total, balance, comm, username, printed, done, prd, 
			vatnum, cusacc, telno, div, collection, custom_txt
		) VALUES (
			'$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', '$odate', 
			'$delchrg', '$SUBTOT', '$vat', '$total', '$total', '$comm', '".USER_NAME."', 'n', 'n', '".PRD_DB."', 
			'$vatnum', '$cusacc', '$telno', '".USER_DIV."', '$collection', '$custom_txt'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	db_conn("hire");
	$invid = pglib_lastid("hire_invoices", "invid");
	return $invid;

}



# Details
function details($_POST, $error="")
{

	extract($_REQUEST);

	$fields = array();
	$fields["deptid"] = 2;
	$fields["cusnum"] = 0;
	$fields["telno"] = "";
	$fields["cordno"] = "";
	$fields["des"] = "";
	$fields["pinv_day"] = date("d");
	$fields["pinv_month"] = date("m");
	$fields["pinv_year"] = date("Y");
	$fields["vatinc_yes"] = "checked";
	$fields["vatinc_no"] = "";
	$fields["vat14"] = AT14;
	$fields["vat"] = "0.00";
	$fields["total"] = "0.00";
	$fields["rounding"] = "";
	$fields["nhifrm_year"] = date("Y");
	$fields["nhifrm_month"] = date("m");
	$fields["nhifrm_day"] = date("d");
	$fields["nhito_year"] = date("Y");
	$fields["nhito_month"] = date("m");
	$fields["nhito_day"] = date("d");
	$fields["client_collect"] = "";
	$fields["collect"] = "";
	$fields["deliver"] = "";
	$fields["deposit_amt"] = "0.00";
	$fields["deposit_type"] = "CSH";
	$fields["custom_txt"] = "- A cleaning levy is payable on mixers\n".
		"- It is the customers responsibility to insure equipment.\n".
		"- Customers are responsible for damages\n";
	$fields["monthly"] = false;
	$fields["bk_asset"] = 0; 	// 30 Asset
	$fields["bk_id"] = 0;
	$fields["reprint"] = 0;
	$fields["search"] = "[_BLANK_]";
	$fields["cust_search"] = "[_BLANK_]";

	extract($fields, EXTR_SKIP);

	if (isset($bk_from)) {
		list($nhifrm_year, $nhifrm_month, $nhifrm_day) = explode("-", $bk_from);
	}
	if (isset($bk_to)) {
		list($nhito_year, $nhito_month, $nhito_day) = explode("-", $bk_to);
	}

	$subtot = 0;
	$vattot = 0;

	if (isset($hirenewBtn)) {
		newHire($_POST);
	}

	// Get us an invoice id
	if (!isset($invid)) {
		$invid = create_dummy($deptid);
	} else {
		$sql = "SELECT cusnum FROM hire.hire_invoices WHERE invid='$invid'";
		$cn_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");
		$cusnum = pg_fetch_result($cn_rslt, 0);

		updateTotals($invid);
	}
	if (isset($monthly) && $monthly) {
		$sql = "SELECT hire_invid FROM hire.hire_invoices WHERE invid='$invid'";
		$minv_rslt = db_exec($sql) or errDie("Unable to retrieve hire invoices.");
		$minvid = pg_fetch_result($minv_rslt, 0);
	} else {
		$minvid = $invid;
	}

	$ind_ccol = "";
	$ind_col = "";
	$ind_del = "";

	$collect_ar = array();
	if (!empty($client_collect)) $collect_ar[] = "Client Collect";
	if (!empty($collect)) $collect_ar[] = "Collect";
	if (!empty($deliver)) $collect_ar[] = "Deliver";

	if (empty($client_collect) && empty($collect) && empty($deliver)) {
		$client_collect = "checked";
		$collect_ar[] = "Client Collect";
	}

	$collection = implode(", ", $collect_ar);

	if (empty($monthly)) {
		$sql = "SELECT *, extract('epoch' FROM expected) AS e_exp,
					extract('epoch' FROM to_date) AS e_to
				FROM hire.hire_invitems WHERE invid='$invid'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		while ($item_data = pg_fetch_array($item_rslt)) {
			if (!empty($item_data["expected"])) {
				if ($item_data["e_to"] > time()) {
					$item_data["expected"] = date("Y-m-t", $item_data["e_to"]);
				} else if ($item_data["e_exp"] < time()) {
					$item_data["expected"] = date("Y-m-t");
				}
			
				$sql = "
					UPDATE hire.hire_invitems 
					SET from_date='$item_data[to_date]', to_date='$item_data[expected]', expected=NULL 
					WHERE id='$item_data[id]'";
				db_exec($sql) or errDie("Unable to update invoice.");
			}
		}
	}

	// Retrieve the actual invoice
	$sql = "SELECT * FROM hire.hire_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$inv_rslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	$inv_data = pg_fetch_array($inv_rslt);

	if ($cusnum == 0) {
		$cusnum = $inv_data["cusnum"];
	}

	if (empty($cordno)) {
		$cordno = $inv_data["cordno"];
	}

	$pinv_date = explode("-", $inv_data["odate"]);
	$pinv_year = $pinv_date[0];
	$pinv_month = $pinv_date[1];
	$pinv_day = $pinv_date[2];

	// Create the dropdowns ---------------------------------------------------

	// Retrieve departments
	$sql = "SELECT * FROM exten.departments ORDER BY deptname ASC";
	$dept_rslt = db_exec($sql) or errDie("Unable to retrieve departments.");

	// Create departments dropdown
	$dept_sel = "<select name='deptid' style='width: 100%'>";
	while ($dept_data = pg_fetch_array($dept_rslt)) {
		$dept_sel .= "<option value='$dept_data[deptid]'>
			$dept_data[deptname]
		</option>";
	}
	$dept_sel .= "</select>";

	// Check customer basis
	if ($cusnum > 0) {
		//checkCustBasis($cusnum);
	}

	if (empty($cust_search) && !isset($btnCustSearch)) {
		$cust_search = "[_BLANK_]";
	}

	// Retrieve customers
	$sql = "
		SELECT * FROM cubit.customers
		WHERE surname ILIKE '$cust_search%' AND blocked = 'no' 
		ORDER BY surname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

	if ($cust_search == "[_BLANK_]") {
		$cust_search = "";
	}

	$cust_sel = "";
	if (pg_num_rows($cust_rslt) == 1) {
		$cust_data = pg_fetch_array($cust_rslt);
		
		$cust_sel .= "
		<input type='hidden' name='cusnum' value='$cust_data[cusnum]' />";
		$cusnum = $cust_data["cusnum"];
	}

	// Create customers dropdown
	if (empty($cusnum)) {
		$cust_sel .= "
			<select name='cusnum' style='width: 100%' onchange='javascript:document.form.submit()'>
				<option value='0'>[None]</option>";
		while ($cust_data = pg_fetch_array($cust_rslt)) {
			$sel = fsel(isset($cusnum) && $cusnum == $cust_data["cusnum"]);
			$cust_sel .= "<option value='$cust_data[cusnum]' $sel>$cust_data[surname]</option>";
		}
		$cust_sel .= "</select>";
	} else {
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cusnum'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		$cust_sel = $cust_data["surname"];
	}

	// Retrieve sales people
	$sql = "SELECT * FROM exten.salespeople ORDER BY salesp ASC";
	$salesp_rslt = db_exec($sql) or errDie("Unable to retrieve sales people.");

	// Create sales people dropdown
	$salesp_sel = "<select name='salespid' style='width: 100%'>";
	while ($salesp_data = pg_fetch_array($salesp_rslt)) {
		$salesp_sel.= "<option value='$salesp_data[salespid]'>$salesp_data[salesp]</option>";
	}
	$salesp_sel .= "</select>";

	// Deposit Options
	$deposit_list = array(
		"CSH"=>"Cash",
		"CHQ"=>"Cheque",
		"CRD"=>"Credit Card"
	);

	// Create the deposit dropdown
	$deposit_sel = "<select name='deposit_type'>";
	foreach ($deposit_list as $key=>$value) {
		if ($inv_data["deposit_type"] == $key) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$deposit_sel .= "<option value='$key' $sel>$value</option>";
	}
	$deposit_sel .= "</select>";

	// Items Display -------------------------------------------------------

	$basis_list = array (
		"per_day" => "Per Day",
		"per_hour" => "Per Hour",
		"per_week" => "Per Week",
		"per_month"=> "Per Month"
	);

	// Retrieve items
	$sql = "
	SELECT * FROM hire.hire_invitems WHERE invid='$invid' ORDER BY id ASC";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$items_out = "";
	$temp_assets = array();
	while ($items_data = pg_fetch_array($items_rslt)) {
		$i = $items_data["id"];

		// Create the basis display
		$basis_disp = $basis_list[$items_data["basis"]];

		// Retrieve assets
		$sql = "SELECT * FROM cubit.assets WHERE id='$items_data[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
		$ad = pg_fetch_array($asset_rslt);

		// Retrieve asset group
		$sql = "SELECT * FROM cubit.assetgrp WHERE grpid='$ad[grpid]'";
		$agrp_rslt = db_exec($sql)
			or errDie("Unable to retrieve asset group.");
		$agrp_data = pg_fetch_array($agrp_rslt);

		if ($agrp_data["grpname"] == "Temporary Asset") {
			$temp_assets[] = $agrp_data[$ad["id"]];
		}

// 		if ($ad["serial"] == "CUBIT::-QTY-") {
// 			$asset_disp = "$ad[des]";
// 		} else {
// 			$asset_disp = "$ad[des] ($ad[serial])";
// 		}

		$asset_disp = $ad["des"]." ".getSerial($ad["id"], 1);

		$subtot += $items_data["amt"];//*$items_data["qty"];

		if ($items_data["basis"] == "per_hour") {
			$from_disp = "Hours: $items_data[hours]";
			$to_disp = "";
		} else if ($items_data["basis"] == "per_day") {
				$mfrm_date = $items_data["from_date"];
				$mfrm_date = explode("-", $mfrm_date);

				$mfrm_year[$i] = $mfrm_date[0];
				$mfrm_month[$i] = $mfrm_date[1];
				$mfrm_day[$i] = $mfrm_date[2];

				$mto_date = $items_data["to_date"];
				$mto_date = explode("-", $mto_date);

				$mto_year[$i] = $mto_date[0];
				$mto_month[$i] = $mto_date[1];
				$mto_day[$i] = $mto_date[2];

				$from_disp = mkDateSelectA("mfrm", $i, $mfrm_year[$i], $mfrm_month[$i], $mfrm_day[$i]);
				$to_disp = mkDateSelectA("mto", $i, $mto_year[$i], $mto_month[$i], $mto_day[$i]);
// 				$from_disp = "
// 				<input type='hidden' name='mfrm_year[$i]' value='$mfrm_year[$i]' />
// 				<input type='hidden' name='mfrm_month[$i]' value='$mfrm_month[$i]' />
// 				<input type='hidden' name='mfrm_day[$i]' value='$mfrm_day[$i]' />
// 				$mfrm_day[$i]-$mfrm_month[$i]-$mfrm_year[$i]";
				
// 				$to_disp = "
// 				<input type='hidden' name='mto_year[$i]' value='$mto_year[$i]' />
// 				<input type='hidden' name='mto_month[$i]' value='$mto_month[$i]' />
// 				<input type='hidden' name='mto_day[$i]' value='$mto_day[$i]' />
// 				$mto_day[$i]-$mto_month[$i]-$mto_year[$i]";

				$from_date[$i] = "$mfrm_year[$i]-$mfrm_month[$i]-$mfrm_day[$i]";
				$to_date[$i] = "$mto_year[$i]-$mto_month[$i]-$mto_day[$i]";

				$hidden_date = "
				<input type='hidden' name='from_date[$i]' value='$from_date[$i]' />
				<input type='hidden' name='to_date[$i]' value='$to_date[$i]' />";

// 				$from_disp = "$items_data[from_date]";
// 				$to_disp = "$items_data[to_date]";
		} else if ($items_data["basis"] == "per_week") {
			$from_disp = "Weeks: $items_data[weeks]";
			$to_disp = "";
		} else if ($items_data["basis"] == "per_month") {
			$from_disp = "Months: $items_data[months]";
			$to_disp = "";
		}

		if (!isset($return[$i])) $return[$i] = "";
		if (!isset($hidden_date)) $hidden_date = "";

		$total_days[$i] = $items_data["total_days"];

		if (empty($total_days[$i])) {
			if (empty($epoch_from)) {
				$epoch_from = "0";
			}
			if (empty($epoch_to)) {
				$epoch_to = "0";
			}

			if (!isset($from_date[$i])) {
				$from_date[$i] = date("Y-m-d");
			}
			if (!isset($to_date[$i])) {
				$to_date[$i] = date("Y-m-d");
			}

			$epoch_from = strtotime($from_date[$i]);
			$epoch_to = strtotime($to_date[$i]);
			$total_days[$i] = ($epoch_to - $epoch_from) / (60 * 60 * 24) + 1;
			$total_days[$i] = ceil($total_days[$i]);
		}

		if ($items_data["basis"] == "per_day") {
			$rd_disp = "<input type='text' name='total_days[$i]' value='$total_days[$i]' size='3' style='text align: center' />";
			if ($items_data["half_day"]) {
				$hd_disp = "<input type='checkbox' name='half_day[$i]' value='1' checked /> Half Day</b>";
// 				$hd_disp = "<input type='hidden' name='half_day[$i]' value='1' />";
			} else {
// 				$hd_disp = "<input type='hidden' name='half_day[$i]' value='0' />";
				$hd_disp = "<input type='checkbox' name='half_day[$i]' value='1' /> Half Day";
			}
		} else {
			$hd_disp = "";
			$rd_disp = "<input type='hidden' name='total_days[$i]' value='0' />";
		}

		if ($items_data["weekends"]) {
			$weekends[$i] = "checked";
		} else {
			$weekends[$i] = "";
		}

		// Items should not be removed once processed, use reprint to check
		// if this hire note has already been processed.
		if ((isset($reprint) && $reprint) || !empty($monthly)) {
			$rem_cbox = "";
		} else {
			$rem_cbox = "<td><input type='checkbox' name='remove[$i]'></td>";
		}

		if (isset($monthly) && $monthly) {
			if ($items_data["basis"] == "per_day") {
				$ret_cbox = "<td><input type='checkbox' name='return[$i]' value='checked' $return[$i]></td>";
			} else {
				$ret_cbox = "<td>&nbsp;</td>";
			}
		} else {
			$ret_cbox = "<td><input type='checkbox' name='return[$i]' value='checked' $return[$i]></td>";
		}

		$amt = sprint($items_data["amt"]);

		#any user must be able to change anount to 0 for R0 return
//		if (user_is_admin(USER_ID)) {
			$amount_out = "<input type='text' name='amount[$i]' value='$amt' size='7' />";
//		} else {
//			$amount_out = "
//			<input type='hidden' name='amount[$i]' value='$amt' />$amt";
//		}

		if (!isSerialized($ad["id"])) {
			$qty_out = "<input type='text' name='qty[$i]' value='$items_data[qty]' size='3' style='text-align: center;' />";
		} else {
			$qty_out = "$items_data[qty]";
		}

		$items_out .= "
			<input type='hidden' name='asset_id[$i]' value='$ad[id]' />
			<input type='hidden' name='basis[$i]' value='$items_data[basis]' />
			<input type='hidden' name='aqty[$items_data[asset_id]][]' value='$items_data[qty]' />
			$hidden_date
			<tr bgcolor='".bgcolorg()."'>
				<td>$basis_disp</td>
				<td>$asset_disp</td>
				<td align='center'>$qty_out</td>
				<td align='center'>$from_disp</td>
				<td align='center'>$to_disp</td>
				<td align='center' nowrap>$rd_disp $hd_disp</td>
				<td align='center'>$items_data[collection]</td>
				<td>$amount_out</td>
				$rem_cbox
				$ret_cbox
				<td><input type='checkbox' name='weekends[$i]' value='checked' $weekends[$i]></td>
			</tr>";
	}

	$temp_assets = implode(",", $temp_assets);

	// New Items --------------------------------------------------------------

	// Avoid undefined variable items_out
	if (empty($items_out)) $items_out = "";

	// Buttons
	if (!empty($cusnum)) {
		if ($deposit_type == "CSH" && $deposit_amt != "0.00") {
			$deposit_open = "printer(\"hire/hire-invoice-print.php?key=cash_receipt&invid=$inv_data[invid]\")";
		} else {
			$deposit_open = "";
		}

		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cusnum'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
		$cust_data = pg_fetch_array($cust_rslt);

		$telno = $cust_data["bustel"];
		if (isset($reprint) AND strlen($reprint) > 0 AND $reprint == "1"){
			$return_btn = "<input type='submit' name='upBtn' value='Return' />";
		}else {
			$return_btn = "";
		}

		if (isset($reprint) && $reprint) {
			$new_btn = "<input type='button' value='Reprint' onclick='javascript:printer(\"hire/hire_note_reprint.php?invid=$inv_data[invid]\");$deposit_open'>";
			$purch_btn = "";
			//$purch_btn = "<input type='button' value='Payment' onclick='javascript:popupOpen(\"".SELF."?key=newpos&cusnum=$cusnum\");' />";
		} else {
			if (!$monthly) {
				$new_btn = "<input name='hirenewBtn' type='submit' value='Process' />";
			} else {
				$new_btn = "<input type='submit' name='upBtn' value='Invoice' />";
			}

			$purch_btn = "";
		}

		$hire_buttons = "
			<tr>
				<td>&nbsp;</td>
				<td align='center'>
					<input type='submit' name='upBtn' value='Update'>
					$new_btn
					$return_btn
					$purch_btn
					<!--<input type='button' value='Swap Hire' />-->
				</td>
				<td>&nbsp;</td>
			</tr>";

		$basevis = "visible";
		$credit_limit = CUR.sprint($cust_data["credlimit"] - $cust_data["balance"]);
		$cust_balance = CUR.$cust_data["balance"];
	} else {
		$hire_buttons = "";
		$basevis = "hidden";
		$credit_limit = "";
		$cust_balance = "";
	}

//orig
//	if (empty($search) || !isset($btnAssetSearch)) {
//		$search = "[_BLANK_]";
//	}

	if (!isset($btnAssetSearch)) {
		$search = "[_BLANK_]";
	}
	if (!isset($btnStockSearch)) {
		$stocksearch = "[_BLANK_]";
	}

	// Retrieve assets
	$sql = "
		SELECT *  FROM cubit.assets
		WHERE des ILIKE '$search%' OR serial ILIKE '$search%' AND remaction IS NULL 
		ORDER BY des ASC";
	$nasset_rslt = db_exec($sql) or  errDie("Unable to retrieve asset.");

	if ($search == "[_BLANK_]") $search = "";

	// Assets dropdown
	$nasset_sel = "
		<select name='nasset_id' style='visibility: $basevis; width: 120px' onchange='assetChange(this);'>
			<option value='0'>- SELECT PLANT -</option>";

	$asset_ar = array();
	while ($ad = pg_fetch_array($nasset_rslt)) {
		$sql = "SELECT * FROM hire.hire_invitems
				WHERE asset_id='$ad[id]' AND invid='$invid'";
		$invitem_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

		if (pg_num_rows($invitem_rslt) && isSerialized($ad["id"])) {
			continue;
		}

		if (!empty($ad["remaction"])) {
			continue;
		}

		if (isHired($ad["id"], date("Y-m-d"))) {
			continue;
		}

		if (!isSerialized($ad["id"])) {
			$at = "q";
			//$asset_disp = "$ad[des] ($ad[serial2] available.)";
			
			$minunits = 0;
			if (isset($aqty)) {
				foreach ($aqty as $key=>$lv2) {
					if ($key == $ad["id"]) {
						foreach ($aqty[$key] as $kqty) {
							$minunits += $kqty;
						}
					}
				}
			}
			
			$units_avail = unitsAvailable($ad["id"], date("Y-m-d")) - $minunits;
			$asset_disp = "$ad[des] $units_avail available.";

			if ($ad["serial2"] <= 0) {
				continue;
			}
		} else {
			$at = "s";
			$asset_disp = "$ad[des] ($ad[serial])";
		}

		if ($cust_bk = isBooked($ad["id"], date("Y-m-d"))) {
			$sql = "SELECT surname FROM cubit.customers WHERE cusnum='$cust_bk'";
			$surname_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
			$surname = pg_fetch_result($surname_rslt, 0);

			if (isSerialized($ad["id"])) {
				$asset_disp .= " Booked: $surname";
			} else {
				$units_booked = unitsBooked($ad["id"], date("Y-m-d"));
				$asset_disp .= " $units_booked Units Booked";
			}
		}

		if ($bk_asset == $ad["id"]) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}

		$nasset_sel .= "<option value='$at:$ad[id]' $sel>$asset_disp</option>";

		$asset_ar[] = $ad["id"];
	}

	$nasset_sel .= "</select>";

	$sql = "SELECT id FROM cubit.assets WHERE serial ILIKE '$search%' OR des ILIKE '$search%'";
	$search_rslt = db_exec($sql) or errDie("Unable to search.");

	if (count($asset_ar) == 1 && pg_num_rows($search_rslt)) {
		$asset = $asset_ar[0];

		if (!isSerialized($asset)) {
			$asset_var = "q:$asset";
		} else {
			$asset_var = "s:$asset";
		}

		$hidden = "
			<input type='hidden' name='nbasis' value='0' />
			<input type='hidden' name='nasset_id' value='$asset_var' />
			<input type='hidden' name='subtot' value='0' />
			<input type='hidden' name='custom_txt' value='' />
			<input type='hidden' name='comm' value='' />
			<input type='hidden' name='search' value='' />
			<input type='hidden' name='hours' value='1' />
			<input type='hidden' name='days' value='1' />
			<input type='hidden' name='weeks' value='1' />
			<input type='hidden' name='months' value='1' />
			<input type='hidden' name='nhifrm_year' value='".date("Y")."' />
			<input type='hidden' name='nhifrm_month' value='".date("m")."' />
			<input type='hidden' name='nhifrm_day' value='".date("d")."' />
			<input type='hidden' name='nhito_year' value='".date("Y")."' />
			<input type='hidden' name='nhito_month' value='".date("m")."' />
			<input type='hidden' name='nhito_day' value='".date("d")."' />
			<input type='hidden' name='nweekends' value='checked' />
			<input type='hidden' name='upBtn' value='1' />
			<script>document.form.submit();</script>";
	} else {
		$hidden = "";
	}

	// Create basis dropdown
	$nbasis_sel = "
	$hidden
		<select name='nbasis' style='visibility: $basevis;' onchange='basisChange(this);'>
			<option value='0'>DEFAULT</option>";
	foreach ($basis_list as $key=>$value) {
		$nbasis_sel.= "<option value='$key'>$value</option>";
	}
	$nbasis_sel.= "</select>";

	// Create asset group dropdown
	$sql = "SELECT grpid, grpname FROM cubit.assetgrp ORDER BY grpname ASC";
	$grp_rslt = db_exec($sql) or errDie("Unable to retrieve groups.");

	if ($cusnum) {
		$OTS_OPT = onthespot_encode(
			SELF,
			"cust_selection",
			"deptid=$deptid&cusnum=$cusnum&invid=$invid"
		);

// 		$cust_edit = "
// 			<td nowrap>
// 			<a href='javascript: popupSized(\"../cust-edit.php?cusnum=$cusnum&onthespot=$OTS_OPT\", \"edit_cust\", 700, 630);'>
// 				Edit Customer Details
// 			</a>
// 			</td>";
		$cust_edit = "";
	} else {
		$cust_edit = "";
	}

	// Retrieve service date
	$sql = "SELECT * FROM hire.hire_invitems WHERE invid='$invid'";
	$invi_rslt = db_exec($sql) or errDie("Unable to retrieve item.");

	$sv_warn = "";
	while ($invi_data = pg_fetch_array($invi_rslt)) {
		$sql = "SELECT * FROM cubit.asset_svdates WHERE svdate<=CURRENT_DATE
				AND asset_id='$invi_data[asset_id]'";
		$sv_rslt = db_exec($sql) or errDie("Unable to retrieve service date.");
		$sv_data = pg_fetch_array($sv_rslt);

		// Retrieve asset
		if (pg_num_rows($sv_rslt)) {
			$sql = "SELECT * FROM cubit.assets WHERE id='$sv_data[asset_id]'";
			$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
			$asset_data = pg_fetch_array($asset_rslt);
			$sv_warn .= "<li class='err'><b>SERVICING</b>: ".getSerial($asset_data["id"], 1)."
			$asset_data[des] has a service date on $sv_data[svdate].</li>";
		}

		if ($days = checkServicing($invi_data["asset_id"], 1)) {
			$sv_warn .= "<li class='err'><b>SERVICING</b>: $asset_data[des] needs servicing.</li>";
		}
	}

	// Check if we should use the default comments
	if (empty($inv_data["comm"])) {
		$sql = "SELECT value FROM cubit.settings WHERE constant='HIRE_COMMENTS'";
		$comment_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");
		$inv_data["comm"] = pg_fetch_result($comment_rslt, 0);
	}

	// Site address
	$addr_sel = "";
	if ($cusnum) {
		// Retrieve branch address
		$sql = "SELECT branch_addr FROM hire.hire_invoices WHERE invid='$invid'";
		$addr_rslt = db_exec($sql) or errDie("Unable to retrieve branch address.");
		$branch_addr = pg_fetch_result($addr_rslt, 0);

		$sql = "SELECT id, branch_name FROM cubit.customer_branches WHERE cusnum='$cusnum'";
		$bran_rslt = db_exec($sql) or errDie("Unable to retrieve customer branch.");

		$addr_sel = "<select name='branch_addr' style='width: 100%' onchange='javascript:document.form.submit()'>";
		$addr_sel.= "<option value='0'>Physical Address</option>";
		while ($bran_data = pg_fetch_array($bran_rslt)) {
			if ($branch_addr == $bran_data["id"]) {
				$sel = "selected='selected'";
			} else {
				$sel = "";
			}
			$addr_sel .= "<option value='$bran_data[id]' $sel>$bran_data[branch_name]</option>";
		}
		$addr_sel .= "</select>";

		$addr_sel .= "<br />".branchAddress($branch_addr, $cusnum);
	}

	$booked_items = getBookedItems($cusnum, date("Y-m-d"));
	foreach ($booked_items as $asset_id=>$units_booked) {
		$sql = "SELECT des FROM cubit.assets WHERE id='$asset_id'";
		$bkdes_rslt = db_exec($sql) or errDie("Unable to retrieve bookings.");
		$bkdes = pg_fetch_result($bkdes_rslt, 0);

		$sv_warn .= "<li class='err'><b>BOOKING</b>: {$units_booked}x ".getSerial($asset_id, 1)." $bkdes booked for this customer.</li>";
	}

	if ($monthly) {
		$ret_out = "Invoice";
	} else {
		$ret_out = "Return";
	}

	// Items should not be removed once processed, use reprint to check
	// if this hire note has already been processed or if its monthly.
	if ((isset($reprint) && $reprint) || !empty($monthly)) {
		$rem_th = "";
		$rem_nbsp = "";
	} else {
		$rem_th = "<th>Remove</th>";
		$rem_nbsp = "<td>&nbsp;</td>";
	}

	// Use the customer trad discount on default
	$sql = "SELECT traddisc FROM cubit.customers WHERE cusnum='$cusnum'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve discount.");
	$trade_discount = pg_fetch_result($cust_rslt, 0);

	// Determine if we got any items, if we do, we don't need to go for the
	// default value anymore, because the customer is already selected.
	$sql = "SELECT count(id) FROM hire.hire_invitems WHERE invid='$invid'";
	$count_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
	$count = pg_fetch_result($count_rslt, 0);

	if ($count) {
		$trade_discount = $inv_data["traddisc"];
	}

	if (isset($bk_id) && $bk_id && !isset($bk_done)) {
		$sql = "SELECT serial FROM hire.bookings
					LEFT JOIN cubit.assets ON bookings.asset_id=assets.id
				WHERE bookings.id='$bk_id'";
		$bk_rslt = db_exec($sql) or errDie("Unable to retrieve booking.");
		$serialized = pg_fetch_result($bk_rslt, 0);

		if ($serialized == "Not Serialized") {
			$qty_disabled = "";
		} else {
			$qty_disabled = "disabled='t'";
		}
	} else {
		$qty_disabled = "disabled='t'";
	}

	// New Items
	$new_items_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td>$nbasis_sel</td>
			<td>$nasset_sel</td>
			<td align='center'>
				<input type='text' id='nqty' name='nqty' value='' size='3' class='clear' $qty_disabled style='text-align:center' />
			</td>
			<td align='left' nowrap='t'>
				<div id='d_wks' style='height: 0px; visibility: hidden;'>
					Weeks: <input type='text' name='weeks' size='5' style='text-align: center;' />
				</div>
				<div id='d_hrs' style='height: 0px; visibility: hidden;'>
					Hours: <input type='text' name='hours' size='5' style='text-align: center;' />
				</div>
				<div id='d_mnths' style='height: 0px; visibility: hidden;'>
					Months: <input type='text' name='months' size='5' style='text-align: center;' />
				</div>
				<div id='d_fdate' style='visibility: hidden;'>
					".mkDateSelect("nhifrm", $nhifrm_year, $nhifrm_month, $nhifrm_day)."
				</div>
			</td>
			<td align='left' nowrap='t'>
				<div id='d_tdate' style='visibility: hidden;'>
					".mkDateSelect("nhito", $nhito_year, $nhito_month, $nhito_day)."
					<input type='checkbox' name='nhalf_day' value='checked' />
					Half Day
				</div>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			$rem_nbsp
			<td>&nbsp;</td>
			<td><input type='checkbox' name='nweekends' value='checked' checked /></td>
		</tr>";

	// Retrieve stores
//	$sql = "SELECT whid, whname FROM exten.warehouses ORDER BY whname ASC";
//	$snstore_rslt = db_exec($sql) or errDie("Unable to retrieve store.");
//
//	$snstore_sel = "<select name='snstore'>";
//	while ($snstore_data = pg_fetch_array($snstore_rslt)) {
//		$snstore_sel .= "
//			<option value='$snstore_data[whid]'>
//				$snstore_data[whname]
//			</option>";
//	}
//	$snstore_sel .= "</select>";

	// Retrieve stock
	$sql = "
		SELECT stkid, stkcod, stkdes FROM cubit.stock
		WHERE stkcod ILIKE '$stocksearch%' OR stkdes ILIKE '$stocksearch%'
		ORDER BY stkcod ASC";
	$snstock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	if ($search == "[_BLANK_]") {
		$search = "";
	}

	$snstock_sel = "
		<select name='snstock'>
			<option value='0'>- Select Stock -</option>";
	while ($snstock_data = pg_fetch_array($snstock_rslt)) {
		$snstock_sel .= "<option value='$snstock_data[stkid]'>$snstock_data[stkcod] - $snstock_data[stkdes]</option>";
	}
	$snstock_sel .= "</select>";

	// Retrieve vat codes
	$sql = "SELECT id, code FROM cubit.vatcodes ORDER BY code ASC";
	$snvatcodes_rslt = db_exec($sql) or errDie("Unable to retrieve vatcodes.");

	$snvatcodes_sel = "<select name='snvatcode'>";
	while ($snvatcodes_data = pg_fetch_array($snvatcodes_rslt)) {
		$snvatcodes_sel .= "<option value='$snvatcodes_data[id]'>$snvatcodes_data[code]</option>";
	}
	$snvatcodes_sel .= "</select>";

	//stores are pointless ... 
//			<td>$snstore_sel</td>

	$new_stock_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td>$snstock_sel</td>
			<td align='center'>$snvatcodes_sel</td>
			<td><input type='text' name='snqty' size='3' style='text-align: center' /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>";

	$sql = "
		SELECT hire_stock_items.id, whname, stkcod, stkdes, qty, code, unitcost,
			amount
		FROM hire.hire_stock_items
			LEFT JOIN exten.warehouses ON hire_stock_items.whid=warehouses.whid
			LEFT JOIN cubit.stock ON hire_stock_items.stkid=stock.stkid
			LEFT JOIN cubit.vatcodes ON hire_stock_items.vatcode=vatcodes.id
		WHERE invid='$minvid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");

//stores are pointless
//			<td>$stock_data[whname]</td>
	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$stock_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stock_data[stkcod] - $stock_data[stkdes]</td>
				<td>$stock_data[code]</td>
				<td>$stock_data[qty]</td>
				<td>$stock_data[unitcost]</td>
				<td>$stock_data[amount]</td>
				<td><input type='checkbox' name='snremove[$stock_data[id]]' /></td>
			</tr>";
	}

//pointless stores ...
//								<th>Store</th>

/* -- Final Layout -- */
	$details = "
		<script>
			function basisChange(o) {
				hrs = getObject('d_hrs');
				fd = getObject('d_fdate');
				td = getObject('d_tdate');
				wks = getObject('d_wks');
				mnths = getObject('d_mnths');

				switch (o.value) {
					case 'per_hour':
						hrs.style.visibility = 'visible';
						fd.style.visibility = 'hidden';
						td.style.visibility = 'hidden';
						wks.style.visibility = 'hidden';
						mnths.style.visibility = 'hidden';
						break;
					case 'per_day':
						hrs.style.visibility = 'hidden';
						fd.style.visibility = 'visible';
						td.style.visibility = 'visible';
						wks.style.visibility = 'hidden';
						mnths.style.visibility = 'hidden';
						break;
					case 'per_week':
						hrs.style.visibility = 'hidden';
						fd.style.visibility = 'hidden';
						td.style.visibility = 'hidden';
						wks.style.visibility = 'visible';
						mnths.style.visibiltiy = 'hidden';
						break;
					case 'per_month':
						hrs.style.visibility = 'hidden';
						fd.style.visibility = 'hidden';
						td.style.visibility = 'hidden';
						wks.style.visibility = 'hidden';
						mnths.style.visibility = 'visible';
					default:
						hrs.style.visibility = 'hidden';
						fd.style.visibility = 'hidden';
						td.style.visibility = 'hidden';
						wks.style.visibility = 'hidden';
						mnths.style.visibility = 'hidden';
						break;
				}
			}

			function assetChange(o) {
				qo = getObject('nqty');
				switch(o.value.substr(0, 1)) {
					case 'q':
						qo.value = '';
						qo.disabled = false;
						qo.className = 'std';
						break;
					case 's':
						qo.value = '1';
						qo.disabled = true;
						qo.className = 'clear';
						break;
					default:
						qo.value = '';
						qo.disabled = true;
						qo.className = 'clear'
				}
			}
		</script>

		<style>
			td, input, textarea, select, th { font-size: .7em; }
		</style>
		<center>
		<form method='POST' name='formName'>
			<input type='hidden' name='key' value='update'>
		</form>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='collection' value='$collection' />
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='invid' value='$invid' />
			<input type='hidden' name='temp_assets' value='$temp_assets' />
			<input type='hidden' name='monthly' value='$monthly' />
			<input type='hidden' name='cusnum' value='$cusnum' />
			<input type='hidden' name='chrgvat' value='no' />
			<input type='hidden' name='bk_id' value='$bk_id' />
			<input type='hidden' name='bk_done' value='1' />
			<input type='hidden' name='reprint' value='$reprint' />
			<input type='hidden' name='SCROLL' value='yes' />
		<table ".TMPL_tblDflts." width='100%'>
	 		<tr>
	 			<td valign='top'>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr><th colspan='2'> Customer Details </th></tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td valign='center'>$dept_sel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Filter</td>
							<td align='center'>
								<input type='text' name='cust_search' value='$cust_search' />
								<br />
								<input type='submit' name='btnCustSearch' value='Search' />
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer</td>
							<td valign='center'>$cust_sel</td>
							$cust_edit
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Telephone Number</td>
							<td valign='center'>
								<input type='text' size='20' name='telno' value='$telno'>
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Order number</td>
							<td valign='center'>
								<input type='text' size='10' name='cordno' value='$cordno'>
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Available Credit</td>
							<td>$credit_limit</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Balance</td>
							<td>$cust_balance</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Customer Address</td>
							<td>$addr_sel</td>
						</tr>
					</table>
				<td valign='top' align='center' style='width: 100%;'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td colspan='2' align='center'>
								<h3>Hire Note</h3>
							</td>
						</tr>
						<tr>
							<td colspan='2' align='center'><img src='../compinfo/getimg.php' style='border: 1px solid #000' width='230' height='47' /></td>
						</tr>
						".TBL_BR."
						<tr><th colspan='2'>Point of Hire</th></tr>
						<!--
						<tr bgcolor='".bgcolorg()."'>
							<td>Barcode</td>
							<td>
								<input type='text' size='13' name='bar' value=''>
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td nowrap='t'>Search for description</td>
							<td><input type='text' size='13' name='des' value='$des'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2' align='center'>
								<input type='submit' value='Search'>
							</td>
						</tr>
						-->
						<tr bgcolor='".bgcolorg()."'>
							<td>Collection Method</td>
							<td>
								<input type='checkbox' name='client_collect' value='checked' $client_collect />
								Client Collect
								<br />
								<input type='checkbox' name='deliver' value='checked' $deliver />
								To be Delivered
								<br />
								<input type='checkbox' name='collect' value='checked' $collect />
								To be Collected
							</td>
						</tr>
					</table>
				</td>
			</td>
			<td valign='top' align='right'>
				<table ".TMPL_tblDflts.">
					".TBL_BR."
					<tr>
						<th colspan=2>Hire Details</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Hire No.</td>
						<td valign='center'>H$inv_data[invnum]".rev($inv_data["invid"])."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Sales Order No.</td>
						<td valign='center'>
							<input type='text' size='5' name='ordno' value='$inv_data[ordno]'>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Hire Date</td>
						<td valign='center' nowrap='t'>
							".mkDateSelect("pinv",$pinv_year,$pinv_month,$pinv_day)."
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Sales Person</td>
						<td>$salesp_sel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Trade Discount</td>
						<td valign='center'>
							<input type='text' size='5' name='traddisc'
							value='$trade_discount'>%
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap='t'>Delivery Charge</td>
						<td valign='center'>
							<input type='text' size='7' name='delchrg'
							value='$inv_data[delchrg]'>
						</td>
					</tr>
					<tr>
						<th colspan='2'>Payment Details </th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>User</td>
						<td>
							<input type='hidden' name='user' value='".USER_NAME."'>
							".USER_NAME."
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Deposit Type</td>
						<td>$deposit_sel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Deposit Amount</td>
						<td>
							<input type='text' name='deposit_amt'
							value='".sprint($inv_data["deposit_amt"])."' size='7' />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr><td colspan='3' align='center'><h3>Hire</h3></td></tr>
		<tr>
			<td colspan='3'>
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Basis</th>
						<th>Item</th>
						<th>Qty</th>
						<th>Hire Date</th>
						<th>Expected Return</th>
						<th>Total Days</th>
						<th>Collection</th>
						<th>Amount</th>
						$rem_th
						<th>$ret_out</th>
						<th>Weekends</th>
					</tr>
					$items_out
					$new_items_out
					<tr>
						<th colspan='3'>Filter Assets</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='3' nowrap='t'>
							<input type='text' name='search' value='$search'
							style='width: 78%' />
							<input type='submit' name='btnAssetSearch' value='Search'
							style='width: 20%'/>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		".TBL_BR."
		<tr><td colspan='3' align='center'><h3>Stock Sales</h3></td></tr>
		<tr>
			<td colspan='3' align='center'>
				<table ".TMPL_tblDflts.">
					<tr>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th>Stock Search</th>
								</tr>
								<tr>
									<td nowrap bgcolor='".bgcolorg()."'>
										<input type='text' name='stocksearch' value='' style='width: 78%' />
										<input type='submit' name='btnStockSearch' value='Search' style='width: 20%'/>
									</td>
								</tr>
							</table>
						</td>
						<td width='10'></td>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th>Stock</th>
									<th>VAT Code</th>
									<th>Qty</th>
									<th>Unit Price</th>
									<th>Amount</th>
									<th>Remove</th>
								</tr>
								$stock_out
								$new_stock_out
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td width='70%' valign='top' colspan='2'>
				<table ".TMPL_tblDflts.">
					<tr>
						<td colspan='10'>$sv_warn</td>
					</tr>
					<tr>
						<td rowspan='4' nowrap>"
							.mkQuickLinks(
								ql("javascript:popupOpen(\"../customers-new.php\")", "New Customer"),
								ql("../pos-invoice-new.php", "New POS Invoice"),
								ql("../nons-invoice-new.php", "New Non-Stock Invoice")
							)."
						</td>
						<th>Memo</th>
						<th>Custom Text</th>
						<td rowspan='5' valign='top' width=40%>$error</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td rowspan=4 align=center valign=top>
							<textarea name=comm cols=20 style='height: 100%'>$inv_data[comm]</textarea>
						</td>
						<td rowspan='4' align='center' valign='top'>
							<textarea name='custom_txt' rows='4' cols='60' style='height: 100%'>$custom_txt</textarea>
						</td>
					</tr>
				</table>
			</td>
			<td colspan='2' align='right' valign='top' width='30%'>
				<table ".TMPL_tblDflts.">
					<tr bgcolor='".bgcolorg()."'>
						<td>Delivery Charge</td>
						<td align=right>".CUR." $inv_data[delivery]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Trade Discount</td>
						<td align=right>".CUR." $inv_data[discount]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>SUBTOTAL</td>
						<td align=right>
							".CUR."<input type=hidden name='subtot' value='$inv_data[subtot]'>
							".sprint($inv_data["subtot"])."
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><b>VAT $vat14</b></td>
						<td align=right>".CUR." $inv_data[vat]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<th>GRAND TOTAL</th>
						<td align=right>".CUR." $inv_data[total]</td>
					</tr>
					$rounding
				</table>
			</td>
		</tr>
		$hire_buttons
		</table>
		<a name='bottom'>
		</form></center>";
	
	// Nasty hack fixing the qty of remaining non serialized assets.
	if (isset($_SESSION["ns"]) && $_SESSION["ns"]) {
		$details .= "<script>document.form.submit()</script>";
		$_SESSION["ns"] = 0;
	}
 	return $details;

}




function newHire($_POST)
{

	extract ($_POST);

	// Remove booking if any
	$sql = "DELETE FROM hire.bookings WHERE id='$bk_id'";
	db_exec($sql) or errDie("Unable to remove booking.");

	$sql = "SELECT catname FROM cubit.customers WHERE cusnum='$cusnum'";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
	$category = pg_fetch_result($cat_rslt, 0);

	if ($deposit_type == "CSH" && $deposit_amt != "0.00") {
		$deposit_print = "printer(\"hire/hire-invoice-print.php?invid=$invid&key=cash_receipt\");";
	} else {
		$deposit_print = "";
	}

	$invnum = getHirenum($invid);

	$sql = "UPDATE hire.monthly_invoices SET invnum='$invnum' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to assign hire number to monthly items.");

// 	$sql = "UPDATE hire.monthly_invitems SET invnum='$invnum' WHERE invid='$invid'";
// 	db_exec($sql) or errDie("Unable to assign hire number to monthly items.");

	$sql = "UPDATE hire.hire_invoices SET timestamp=CURRENT_TIMESTAMP,
				invnum='$invnum'
			WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update hire invoices.");

	$OUTPUT = "
		<script>
			move(\"".SELF."\");
			printer(\"hire/hire-invoice-print.php?invid=$invid\");
			popupSized(\"signed_hirenote_save.php?invid=$invid\", \"1\", 500, 300);
			$deposit_print
		</script>";
	require ("template.php");
	exit;

}




function update($_POST)
{

	extract($_POST);

	$collect_ar = array();

	if (!empty($client_collect)) $collect_ar[] = "Client Collect";
	if (!empty($collect)) $collect_ar[] = "Collect";
	if (!empty($deliver)) $collect_ar[] = "Deliver";

	$collection = implode(", ", $collect_ar);

// 	if ((in_array("Collect", $collect_ar) && in_array("Client Collect", $collect_ar))
// 		|| (count($collect_ar) == 3)) {
// 		return "<li class='err'>Invalid collection options selected.</li>";
// 	}

	if (count($collect_ar) > 1 && in_array("Client Collect", $collect_ar)) {
		return "<li class='err'>Invalid collection options selected.</li>";
	}

	$temp_assets = explode(",", $temp_assets);

	pglib_transaction("BEGIN");

	// Stock Sales
	if (isset($snremove) && is_array($snremove)) {
		foreach ($snremove as $id=>$value) {
			$sql = "DELETE FROM hire.hire_stock_items WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove stock item.");
		}
	}

	if (isset($snstock) && $snstock > 0) {
		if (!is_numeric($snqty)) {
			$snqty = 1;
		}

		#get vatperc for this item
		$get_vatp = "SELECT vat_amount FROM vatcodes WHERE id = '$snvatcode' LIMIT 1";
		$run_vatp = db_exec($get_vatp) or errDie ("Unable to get vat code information.");
		if (pg_numrows($run_vatp) < 1){
			$vatperc = 0;
		}else {
			$vatperc = pg_fetch_result ($run_vatp,0,0);
		}

//		$sql = "SELECT selamt FROM cubit.stock WHERE stkid='$snstock'";
//		$price_rslt = db_exec($sql) or errDie("Unable to retrieve price.");
//		$price = pg_fetch_result($price_rslt, 0);
		$price = get_excl_stock($snstock) * $snqty;

		$excl_price = get_excl_stock($snstock) * $snqty;
		$vatamount = sprint (($excl_price / 100) * $vatperc);

		$price += 0;
		$price += sprint (($excl_price / 100) * $vatperc);
		$snamt = ($price * $snqty);

		#hack, because stores functionality isnt working ...
		$get_store = "SELECT whid FROM stock WHERE stkid = '$snstock' LIMIT 1";
		$run_store = db_exec($get_store) or errDie ("Unable to get stock store information.");
		$snstore = pg_fetch_result ($run_store,0,0);

		$sql = "
			INSERT INTO hire.hire_stock_items (
				whid, invid, stkid, qty, vatcode, unitcost, 
				amount, excl_amount, vatamount
			) VALUES (
				'$snstore', '$invid', '$snstock', '$snqty', '$snvatcode', '".sprint ($price/$snqty)."', 
				'".sprint ($snamt/$snqty)."', '$excl_price', '$vatamount'
			)";
		db_exec($sql) or errDie("Unable to add stock.");

		$sql = "UPDATE cubit.stock SET alloc=(alloc+'$snqty') WHERE stkid='$snstock'";
		db_exec($sql) or errDie("Unable to update stock allocation.");
	}

	if (isset($nhalf_day) && $nhalf_day == "checked") {
		$nhalf_day = 1;
	} else {
		$nhalf_day = 0;
	}

	if (isset($nweekends) && $nweekends == "checked") {
		$nweekends = 1;
	} else {
		$nweekends = 0;
	}

	$sql = "UPDATE hire.hire_invoices SET comm='$comm' WHERE invid='$invid'";
	$comm_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");

	foreach ($temp_assets as $key=>$value) {
		$sql = "SELECT * FROM cubit.assets WHERE id='$key'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);
	}

	if (isset($amount)) {
		foreach ($amount as $key => $value) {

// 			if (empty($monthly)) {
// 				$amount[$key] = "";
// 			}
			#redundant code ... breaks new hire if you click update multiple times (sets item amount = 0 after second update)
//			if (!user_is_admin(USER_ID)) {
//				$amount[$key] = "";
//			}

			if (!isset($half_day[$key]) || empty($half_day[$key])) {
				$half_day[$key] = 0;
			}
			if (!isset($weekends[$key]) || empty($weekends[$key])) {
				$weekends[$key] = 0;
			} else {
				$weekends[$key] = 1;
			}
			
			if (!isset($total_days[$key]) || empty($total_days[$key])) {
				$epoch_from = strtotime($from_date[$key]);
				$epoch_to = strtotime($to_date[$key]);
				$total_days[$key] = ($epoch_to - $epoch_from) / (60 * 60 * 24) + 1;
				$total_days[$key] = ceil($total_days[$key]);
			}

			$sql = "SELECT qty, total_days, half_day FROM hire.hire_invitems WHERE id='$key'";
			$old_rslt = db_exec($sql) or errDie("Unable to retrieve old qty.");
			list($oldqty, $olddays, $oldhalf_day) = pg_fetch_array($old_rslt);

			if (!isset($oldqty)) {
				$oldqty = 1;
			}

			if (!isset($qty[$key])) {
				$oldqty = 1;
				$qty[$key] = 1;
			}

			if ($half_day[$key]) {
				$half_day[$key] = 1;
				$recalc = FALSE;
			} else {
				$half_day[$key] = 0;
			}

			if ((empty($amount) && $amount != 0) || $oldqty != $qty[$key] || $olddays != $total_days[$key] || (!isset($amount[$key]) OR empty($amount[$key])) || $half_day == 1) {
				if ($basis[$key] == "per_day") {
					$to_time = strtotime($from_date[$key]) + $total_days[$key] *
						(60 * 60 * 24);

					$hifrm = "$mfrm_year[$key]-$mfrm_month[$key]-$mfrm_day[$key]";
					$hito = date("Y-m-d", $to_time);
					$hours = "0";

					/* calculate amount */
					$ftime = getDTEpoch("$hifrm 0:00:00");
					$ttime = getDTEpoch("$hito 0:00:00");

					$days = 0;
					$weeks = 0;
					$months = 0;
					while ($ftime <= $ttime) {
						if (date("w", $ftime) == 0 && isset($weekends[$key]) &&
							$weekends[$key]) {

							$days += 0.6;
						} else {
							++$days;
						}

						$ftime += 24 * 60 * 60;
					}

					$timeunits = $total_days[$key];
				} else if ($basis[$key] == "per_hour") {
					$hifrm = $hito = mkdate($pinv_year, $pinv_month, $pinv_day);
					$timeunits = $hours;
					$weeks = 0;
					$months = 0;
					if (empty($hours) || !is_numeric($hours)) {
						return "<li class='err'><b>ERROR</b>: Invalid amount of hours.</li>";
					}
				} else if ($nbasis == "per_week") {
					$nhifrm = $nhito = mkdate($pinv_year, $pinv_month, $pinv_day);
					$timeunits = $weeks;

					$hours = 0;
					$months = 0;
					if (empty($weeks) || !is_numeric($weeks)) {
						return "<li class='err'><b>ERROR</b>: Invalid amount of weeks.</li>";
					}
				} else if ($nbasis == "per_month") {
					$nhifrm = $nhito = mkDate($pinv_year, $pinv_month, $pinv_day);
					$timeunits = $months;

					$weeks = 0;
					$hours = 0;

					if (empty($months) || !is_numeric($months)) {
						return "<li class='err'><b>ERROR</b>: Invalid amount of months.</li>";
					}
				}
				if ($half_day[$key]) {
//					$amount[$key] = ($qty[$key] * $timeunits * (basisPrice($cusnum, $asset_id[$key], $basis[$key]) * $qty[$key]) - (basisPrice($cusnum, $asset_id[$key], $basis[$key]) * $qty[$key]) + ((basisPrice($cusnum, $asset_id[$key], $basis[$key]) * $qty[$key])) / halfday_rate());
					$amount[$key] = sprint ($qty[$key] *($timeunits * (basisPrice($cusnum, $asset_id[$key], $basis[$key])) - (basisPrice($cusnum, $asset_id[$key], $basis[$key])) + ((basisPrice($cusnum, $asset_id[$key], $basis[$key])) / halfday_rate())));
				} else {
					$amount [$key] = $qty[$key] * $timeunits * basisPrice($cusnum, $asset_id[$key], $basis[$key]);
				}
			}

			if ($amount[$key] == 0) {
				$amount[$key] = 0;
				$blank_amount = 1;
			} else {
				$blank_amount = 0;
			}

			$sql = "
				UPDATE hire.hire_invitems 
				SET amt='$amount[$key]', half_day='$half_day[$key]', weekends='$weekends[$key]', 
					total_days='$total_days[$key]', qty='$qty[$key]' 
				WHERE id='$key'";
			db_exec($sql) or errDie("Unable to update item amount.");

			$sql = "
				UPDATE hire.reprint_invitems 
				SET amt='$amount[$key]', half_day='$half_day[$key]', weekends='$weekends[$key]' 
				WHERE item_id='$key'";
			db_exec($sql) or errDie("Unable to update return item amount.");

			if ($blank_amount) $amount[$key] = "";
			//$hifrm = "$hifrm_year[$key]-$hifrm_month[$key]-$hifrm_day[$key]";
			//$hito = "$hito_year[$key]-$hito_month[$key]-$hito_day[$key]";

			if (!isset($remove[$key])) {
				$sql = "SELECT basis FROM hire.hire_invitems WHERE id='$key'";
				$item_rslt = db_exec($sql) or errDie("Unable to retrieve basis.");
				$mbasis = pg_fetch_result($item_rslt, 0);

				/* determine time units */
				if ($mbasis == "per_day") {
					$mfrm = mkdate($mfrm_year[$key], $mfrm_month[$key], $mfrm_day[$key]);
					$mto = mkdate($mto_year[$key], $mto_month[$key], $mto_day[$key]);

					/* calculate amount */
					$ftime = mktime(0, 0, 0, $mfrm_month[$key]
					, $mfrm_day[$key], $mfrm_year[$key]);
					$ttime = mktime(0, 0, 0, $mto_month[$key], $mto_day[$key], $mto_year[$key]);

					$days = 0;
					if (empty($weeks)) $weeks = 0;
					if (empty($hours)) $hours = 0;
					if (empty($months)) $months = 0;

					while ($ftime <= $ttime) {
						if (date("w", $ftime) == 0 && isset($weekends[$key]) &&
							$weekends[$key]) {

							$days += 0.6;
						} else {
							++$days;
						}

						$ftime += 24 * 60 * 60;
					}

					$timeunits = $days;

					$sql = "
						UPDATE hire.hire_invitems 
						SET from_date='$mfrm', to_date='$mto' 
						WHERE id='$key'";
					db_exec($sql) or errDie("Unable to update items.");

					$sql = "
						UPDATE hire.reprint_invitems 
						SET from_date='$mfrm', to_date='$mto' 
						WHERE item_id='$key'";
					db_exec($sql) or errDie("Unable to update reprint items.");
				}
			} else {
				// Delete the old items
				$sql = "DELETE FROM hire.hire_invitems WHERE id='$key'";
				db_exec($sql) or errDie("Unable to remove old items.");

				$sql = "DELETE FROM hire.reprint_invitems WHERE item_id='$key'";
				db_exec($sql) or errDie("Unable to remove old reprint items.");

				//.Remove if the item has been hired as well
				$sql = "DELETE FROM hire.assets_hired WHERE item_id='$key'";
				db_exec($sql) or errDie("Unable to remove items from hired log.");
			}
		}
	}

	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$invid'";
	$hi_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$invb = pg_fetch_array($hi_rslt);

	// Default basis
	if (!empty($nasset_id) && $nbasis == "0") {
		list($serialqty, $tasset_id) = explode(":", $nasset_id);
		$nbasis = default_basis($tasset_id);
	}

	// Insert new items
	if ($nasset_id != "0") {
		if ($nasset_id == "0") {
			return "<li class='err'><b>ERROR</b>: No asset selected.</li>";
		}

		/* get asset id */
		list($serialqty, $nasset_id) = explode(":", $nasset_id);

		/* disabled items don't get passed through */
		if ($serialqty == "s" || !isset($nqty)) {
			$nqty = "1";
		} else {
			$sql = "SELECT serial2 FROM cubit.assets WHERE id='$nasset_id'";
			$dqty_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
			$dqty = pg_fetch_result($dqty_rslt, 0);

			if (($dqty - $nqty) < 0) {
				return "<li class='err'><b>ERROR</b>: Invalid quantity. Only &nbsp; <b>$dqty</b> &nbsp; available.</li>";
			}
		}

		if (empty($nqty) || !is_numeric($nqty)) {
			return "<li class='err'><b>ERROR</b>: Invalid quantity</li>";
		}

		// Default basis
		if ($nbasis == "0") {
			$nbasis = default_basis($nasset_id);
		}

		/* determine time units */
		if ($nbasis == "per_day") {
			$nhifrm = mkdate($nhifrm_year, $nhifrm_month, $nhifrm_day);
			$nhito = mkdate($nhito_year, $nhito_month, $nhito_day);
			$hours = "0";

			/* calculate amount */
			$ftime = mktime(0, 0, 0, $nhifrm_month, $nhifrm_day, $nhifrm_year);
			$ttime = mktime(0, 0, 0, $nhito_month, $nhito_day, $nhito_year);

			$days = 0;
			$weeks = 0;
			$months = 0;
			while ($ftime <= $ttime) {
				if (date("w", $ftime) == 0 && isset($nweekends) &&
					$nweekends) {

					$days += 0.6;
				} else {
					++$days;
				}

				$ftime += 24 * 60 * 60;
			}

			$timeunits = $days;
		} else if ($nbasis == "per_hour") {
			$nhifrm = $nhito = mkdate($pinv_year, $pinv_month, $pinv_day);
			$timeunits = $hours;

			$weeks = 0;
			$months = 0;
			if (empty($hours) || !is_numeric($hours)) {
				return "<li class='err'><b>ERROR</b>: Invalid amount of hours.</li>";
			}
		} else if ($nbasis == "per_week") {
			$nhifrm = $nhito = mkdate($pinv_year, $pinv_month, $pinv_day);
			$timeunits = $weeks;

			$hours = 0;
			$months = 0;
			if (empty($weeks) || !is_numeric($weeks)) {
				return "<li class='err'><b>ERROR</b>: Invalid amount of weeks.</li>";
			}
		} else if ($nbasis == "per_month") {
			$nhifrm = $nhito = mkdate($pinv_year, $pinv_month, $pinv_day);
			$timeunits = $months;

			$hours = 0;
			$weeks = 0;
			if (empty($months) || !is_numeric($months)) {
				return "<li class='err'><b>ERROR</b>: Invalid amount of months.</li>";
			}
		} else {
			return "<li class='err'><b>ERROR</b>: No basis selected.</li>";
		}

		/* calculate amount according to hire settings, quantity and time units */
		if ($nhalf_day) {
			$camt = ($nqty * $timeunits * basisPrice($cusnum, $nasset_id, $nbasis)) - basisPrice($cusnum, $nasset_id, $nbasis) + (basisPrice($cusnum, $nasset_id, $nbasis) / halfday_rate());
		} else {
			$camt = $nqty * $timeunits * basisPrice($cusnum, $nasset_id, $nbasis);
		}

		/* insert item */
		$sql = "SELECT asset_id FROM hire.hire_invitems
				WHERE invid='$invid' AND asset_id='$nasset_id'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

		// No duplicate assets
		if (!pg_num_rows($asset_rslt) || !isSerialized($nasset_id)) {
			if (isHired($nasset_id)) {
				return "
				<li class='err'>
					<b>ERROR</b>: Asset has already hired out.
				</li>";
			}

			$sql = "
				INSERT INTO hire.hire_invitems (
					invid, asset_id, qty, amt, from_date, to_date, basis, hours, weeks, 
					months, collection, half_day, weekends
				) VALUES (
					'$invid', '$nasset_id', '$nqty', '$camt', '$nhifrm', '$nhito', '$nbasis', '$hours', '$weeks', 
					'$months', '$collection', '$nhalf_day', '$nweekends'
				)";
			db_exec($sql) or errDie("Unable to create new invoice item.");
			$item_id = pglib_lastid("hire.hire_invitems", "id");

			$sql = "
				INSERT INTO hire.reprint_invitems (
					invid, asset_id, qty, amt, from_date, to_date, basis, hours, weeks, 
					months, collection, half_day, weekends, item_id
				) VALUES (
					'$invid', '$nasset_id', '$nqty', '$camt', '$nhifrm', '$nhito', '$nbasis', '$hours', '$weeks', 
					'$months', '$collection', '$nhalf_day', '$nweekends', '$item_id'
				)";
			db_exec($sql) or errDie("Unable to create reprint invoice item.");
		}
	}

	if ($monthly == "true") {
		$sql = "DELETE FROM hire.monthly_invitems WHERE invid='$invid'";
		db_exec($sql) or errDie("Unable to remove monthly items.");
	} else {
		$sql = "SELECT * FROM hire.hire_invitems WHERE invid='$invid'";
		$mii_rslt = db_exec($sql) or errDie("Unable to retrieve inv items.");

		$sql = "DELETE FROM hire.monthly_invitems WHERE invid='$invid'";
		db_exec($sql) or errDie("Unable to remove monthly items.");

		while ($item = pg_fetch_array($mii_rslt)) {
			$sql = "
				INSERT INTO hire.monthly_invitems (
					invid, asset_id, qty, amt, from_date, to_date, 
					basis, hours, weeks, months, collection, half_day, 
					weekends, item_id, invnum
				) VALUES (
					'$item[invid]', '$item[asset_id]', '$item[qty]', '$item[amt]', '$item[from_date]', '$item[to_date]', 
					'$item[basis]', '$item[hours]', '$item[weeks]', '$item[months]', '$item[collection]', '$item[half_day]',
					'$item[weekends]', '$item[id]', '$invb[invnum]'
				)";
			db_exec($sql) or errDie("Unable to create monthly items.");
		}
	}

	$sql = "SELECT * FROM hire.reprint_invoices WHERE invid='$invid'";
	$ri_rslt = db_exec($sql) or errDie("Unable to retrieve reprints.");

	// Create a new entry, or update
	if (pg_num_rows($ri_rslt)) {
		$sql = "
			UPDATE hire.reprint_invoices 
			SET deptid='$invb[deptid]', cusnum='$invb[cusnum]', deptname='$invb[deptname]', cusacc='$invb[cusacc]',
				cusname='$invb[cusname]', surname='$invb[surname]', cusaddr='$invb[cusaddr]', cusvatno='$invb[cusvatno]', 
				cordno='$invb[cordno]', ordno='$invb[ordno]', chrgvat='$invb[chrgvat]', terms='$invb[terms]', 
				traddisc='$invb[traddisc]', salespn='$invb[salespn]', odate='$invb[odate]', delchrg='$delchrg', 
				subtot='$invb[subtot]', vat='$invb[vat]', total='$invb[total]', balance='$invb[balance]', 
				comm='$invb[comm]', printed='$invb[printed]', done='$invb[done]', div='$invb[div]', 
				username='$invb[username]', rounding='$invb[rounding]', delvat='$invb[delvat]', vatnum='$invb[vatnum]', 
				pcash='$invb[pcash]', pcheque='$invb[pcheque]', pcc='$invb[pcc]', pcredit='$invb[pcredit]' 
			WHERE invid='$invid'";
		db_exec($sql) or errDie("Unable to update reprint.");
	} else {
		$sql = "
			INSERT INTO hire.reprint_invoices (
				invid, invnum, deptid, cusnum, deptname, cusacc, 
				cusname, surname, cusaddr, cusvatno, cordno, ordno, 
				chrgvat, terms, traddisc, salespn, odate, delchrg, 
				subtot, vat, total, balance, comm, printed, done, div, 
				username, rounding, delvat, vatnum, pcash, pcheque, 
				pcc, pcredit
			) VALUES (
				'$invid', '$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', 
				'$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]',
				'$invb[chrgvat]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', 
				'$invb[subtot]', '$invb[vat]' , '$invb[total]', '$invb[balance]', '$invb[comm]', 'y', 'y', '".USER_DIV."', 
				'".USER_NAME."', '$invb[rounding]', '$invb[delvat]', '$invb[vatnum]', '$invb[pcash]', '$invb[pcheque]', 
				'$invb[pcc]', '$invb[pcredit]'
			)";
		db_exec($sql) or errDie("Unable to add reprint.");
	}

	$sql = "SELECT * FROM hire.monthly_invoices WHERE invid='$invid' OR invnum='$invb[invnum]' AND invnum!=0";
	$mi_rslt = db_exec($sql) or errDie("Unable to retrieve monthly.");

	// Should we create a new entry
	if (pg_num_rows($mi_rslt)) {
		$sql = "
			UPDATE hire.monthly_invoices 
			SET deptid='$invb[deptid]', cusnum='$invb[cusnum]', deptname='$invb[deptname]', cusacc='$invb[cusacc]', 
				cusname='$invb[cusname]', surname='$invb[surname]', cusaddr='$invb[cusaddr]', cusvatno='$invb[cusvatno]', 
				cordno='$invb[cordno]', ordno='$invb[ordno]', chrgvat='$invb[chrgvat]', terms='$invb[terms]', 
				traddisc='$invb[traddisc]', salespn='$invb[salespn]', odate='$invb[odate]', delchrg='$invb[delchrg]', 
				subtot='$invb[subtot]', vat='$invb[vat]', total='$invb[total]', balance='$invb[balance]', 
				comm='$invb[comm]', printed='$invb[printed]', done='$invb[done]', div='$invb[div]', 
				username='$invb[username]', rounding='$invb[rounding]', delvat='$invb[delvat]', vatnum='$invb[vatnum]', 
				pcash='$invb[pcash]', pcheque='$invb[pcheque]', pcc='$invb[pcc]', pcredit='$invb[pcredit]', 
				hire_invid='$invid' 
			WHERE invid='$invb[invid]'";
	} elseif (empty($monthly)) {
			$sql = "
				INSERT INTO hire.monthly_invoices (
					invid, invnum, deptid, cusnum, deptname, cusacc, 
					cusname, surname, cusaddr, cusvatno, cordno, 
					ordno, chrgvat, terms, traddisc, salespn, 
					odate, delchrg, subtot, vat, total, balance, 
					comm, printed, done, div, username, rounding, delvat, 
					vatnum, pcash, pcheque, pcc, pcredit, invoiced_month, 
					hire_invid
				) VALUES (
					'$invid', '$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', 
					'$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', 
					'$invb[ordno]', '$invb[chrgvat]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', 
					'$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]', '$invb[total]', '$invb[balance]', 
					'$invb[comm]', 'y', 'y', '".USER_DIV."', '".USER_NAME."', '$invb[rounding]', '$invb[delvat]', 
					'$invb[vatnum]', '$invb[pcash]', '$invb[pcheque]', '$invb[pcc]', '$invb[pcredit]', '".date("m")."', 
					'$invb[invid]' 
				)";
	}
	db_exec($sql) or errDie("Unable to store monthly invoice.");


	pglib_transaction("COMMIT");

	if (!isSerialized($nasset_id) && (!isset($_SESSION["ns"]) || !$_SESSION["ns"])) {
		$_SESSION["ns"] = 1;
	} else {
		$_SESSION["ns"] = 0;
	}

	if (isset($upBtn)) {
		if ($upBtn == "Return") {
			return returnHire();
		} elseif ($upBtn == "Invoice") {
			return invoiceHire();
		}
	}
	return false;

}



# details
function write($_POST)
{

	extract ($_POST);

	$deptid += 0;

	db_conn('cubit');

	if(isset($printsales)) {

		$Sl = "SELECT * FROM settings WHERE constant='PSALES'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		if(pg_num_rows($Ri)<1) {
			$Sl = "INSERT INTO settings (constant,value,div) VALUES ('PSALES','Yes','".USER_DIV."')";
			$Ri = db_exec($Sl);
		} else {
			$Sl = "UPDATE settings SET value='Yes' WHERE constant='PSALES' AND div='".USER_DIV."'";
			$Ri = db_exec($Sl);
		}
	} else {
		$Sl = "UPDATE settings SET value='No' WHERE constant='PSALES' AND div='".USER_DIV."'";
		$Ri = db_exec($Sl);
	}

	//$it+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if(isset($client)) {
		$v->isOk ($client, "string", 0, 20, "Invalid Customer.");
	} else {
		$client = "";
	}
	if(isset($vatnum)) {
		$v->isOk ($vatnum, "string", 0, 30, "Invalid VAT Number.");
	} else {
		$vatnum="";
	}
	if (isset($branch_addr)) {
		$v->isOk ($branch_addr, "num", 1, 20, "Invalid site address.");
	} else {
		$branch_addr = 0;
	}

	$v->isOk ($invid, "num", 1, 20, "Invalid Invoice Number.");
	$v->isOk ($telno, "string", 0, 20, "Invalid Customer Telephone Number.");
	$v->isOk ($cordno, "string", 0, 20, "Invalid Customer Order Number.");
	//$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	$v->isOk ($ordno, "string", 0, 20, "Invalid sales order number.");
// 	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
	$v->isOk ($salespid, "string", 1, 255, "Invalid sales person.");
	$v->isOk ($pinv_day, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($pinv_month, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($pinv_year, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $pinv_year."-".$pinv_month."-".$pinv_day;
	if(!checkdate($pinv_month, $pinv_day, $pinv_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	if($traddisc > 100){
		$v->isOk ($traddisc, "float", 0, 0, "Error : Trade Discount cannot be more than 100 %.");
	}
	$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	$v->isOk ($subtot, "float", 0, 20, "Invalid subtotal.");
	$odate = $pinv_year."-".$pinv_month."-".$pinv_day;
	if(!checkdate($pinv_month, $pinv_day, $pinv_year)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	$v->isOk ($collection, "string", 0, 40, "Invalid collection method.");

	# used to generate errors
	$error = "asa@";

	# check if duplicate serial number selected, remove blanks
	if(isset($sernos)){
		if(!ext_isUnique(ext_remBlnk($sernos))){
			$v->isOk ($error, "num", 0, 0, "Error : Serial Numbers must be unique per line item.");
		}
	}

	# check is serial no was selected
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			# check if serial is selected
			if(ext_isSerial("stock", "stkid", $stkid) && !isset($sernos[$keys])){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}elseif(ext_isSerial("stock", "stkid", $stkid) && !(strlen($sernos[$keys]) > 0)){
				$v->isOk ($error, "num", 0, 0, "Error : Missing serial number for product number : <b>".($keys+1)."</b>");
			}
		}
	}

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$discp[$keys] += 0;
			$disc[$keys] += 0;

			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
		}
	}
	# check whids
	if(isset($whids)){
		foreach($whids as $keys => $whid){
			$v->isOk ($whid, "num", 1, 10, "Invalid Store number, please enter all details.");
		}
	}

	$cusnum += 0;

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}
	# check amt
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}

	if (isset($des)) {
		$des = remval($des);
	}

	if (isset($asset_id) && is_numeric($asset_id)) {
		foreach ($asset_id as $value) {
			$sql = "SELECT id, des FROM cubit.assets WHERE id='$asset_id'";
			$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
			$asset_data = pg_fetch_array($asset_rslt);

			if (isHired($asset_id)) {
				$v->addError(0, "Asset ".getSerial($asset_id)." $asset_data[des] has already been hired out.");
			}
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		return details($_POST, $err);
	}
//
	if(strlen($vatnum) < 1) {$vatnum = "";}
	$_POST['client'] = $client;
	$_POST['vatnum'] = $vatnum;

	$_POST['telno'] = $telno;
	$_POST['cordno'] = $cordno;

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM hire.hire_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
 	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
// 	if (pg_numrows ($invRslt) < 1) {
// 		return "<li>- Invoice Not Found[1]</li>";
// 	}
	$inv = pg_fetch_array($invRslt);

	$inv['traddisc'] = $traddisc;
	$inv['chrgvat'] = 0;

	# check if invoice has been printed
// 	if($inv['printed'] == "y"){
// 		$error = "<li class=err> Error : Invoice number <b>$invid</b> has already been printed.";
// 		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
// 		return $error;
// 	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found[3]</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# fix those nasty zeros
	$traddisc += 0;
	$delchrg += 0;

	$vatamount = 0;
	$showvat = TRUE;

	# insert invoice to DB
	db_connect();

	if (isset($upBtn) || isset($hirenewBtn)) {
		$update_ret = update($_POST);
	} else {
		$update_ret = false;
	}

	# begin updating
	pglib_transaction("BEGIN");

	/* -- Start remove old items -- */
	# get selected stock in this invoice
	$sql = "SELECT * FROM hire.hire_invitems  WHERE invid = '$invid'";
	$stktRslt = db_exec($sql);

	$subtot = 0;
	$vattot = 0;
	while($stkt = pg_fetch_array($stktRslt)){
		# update stock(alloc + qty)
		//$sql = "UPDATE stock SET alloc = (alloc - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
		//$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		//if(strlen($stkt['serno']) > 0)
			//ext_unresvSer($stkt['serno'], $stkt['stkid']);

		$subtot += $stkt["amt"];
		$vattot += $stkt["amt"] / 100 * 14;
	}

	$sql = "SELECT sum(excl_amount) FROM hire.hire_stock_items WHERE invid='$invid'";
	$stksum_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");
	$stksum = pg_fetch_result($stksum_rslt, 0);
	$subtot += $stksum;

	$sql = "SELECT sum(vatamount) FROM hire.hire_stock_items WHERE invid='$invid'";
	$stksum_rslt = db_exec($sql) or errDie("Unable to retrieve stock items.");
	$vatsum = pg_fetch_result($stksum_rslt, 0);
	$vattot += $vatsum;

	# remove old items
		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$VATP = TAX_VAT;

		$subtotal = sprint($subtot + $delchrg);
		$traddiscmt = sprint($subtot/100 * $traddisc);
		$subtotal = sprint($subtotal - $traddiscmt);

		#vat can not be calculated like this if user can change vat setting per item ...
//		$VAT = $subtotal / 100 * 14;
		$VAT = sprint ($vattot/100 * $traddisc);

		$SUBTOT = $subtotal;
		$TOTAL = $subtotal+$VAT;
		$delexvat = sprint($delchrg);

		$Sl = "SELECT * FROM posround";
		$Ri = db_exec($Sl);

		$data = pg_fetch_array($Ri);

		if($data['setting'] == "5cent") {
			if(sprint(floor(sprint($TOTAL/0.05))) != sprint($TOTAL/0.05)) {
				$otot = $TOTAL;
				$nTOTAL = sprint(sprint(floor($TOTAL/0.05))*0.05);
				$rounding = ($otot - $nTOTAL);
			} else {
				$rounding = 0;
			}
		} else {
			$rounding = 0;
		}

		//print sprint(floor($TOTAL/0.05));

		#get accno if invoice is on credit
		if($cusnum != "0"){
			$get_acc = "SELECT * FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
			$run_acc = db_exec($get_acc) or errDie("Unable to get customer information");
			if(pg_numrows($run_acc) < 1){
				$accno = "";
			}else {
				$arr = pg_fetch_array($run_acc);
				$cusacc = $arr['accno'];
				$cusname = "$arr[cusname] $arr[surname]";
			}
		}else {
			$cusacc = "";
			$cusname = "";
		}

		# insert invoice to DB
		$sql = "
			UPDATE hire.hire_invoices 
			SET cusnum='$cusnum', cusname='$cusname', rounding='$rounding', deptid='$deptid', deptname='$dept[deptname]', 
				cordno='$cordno', ordno='$ordno', salespn='$salespid', odate='$odate', traddisc='$traddisc', 
				delchrg='$delchrg', subtot='$SUBTOT', vat='$VAT',balance='$TOTAL', total='$TOTAL', discount='$traddiscmt', 
				delivery='$delexvat', vatnum='$vatnum', cusacc='$cusacc', telno='$telno', deposit_type='$deposit_type', 
				deposit_amt='$deposit_amt', collection='$collection', custom_txt='$custom_txt', branch_addr='$branch_addr' 
			WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
		# remove old data
		$sql = "DELETE FROM pinv_data WHERE invid='$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice data in Cubit.",SELF);

		# put in new data
		$sql = "INSERT INTO pinv_data (invid, dept, customer, div) VALUES ('$invid', '$dept[deptname]', '$client', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice data to Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	return details($_POST, $update_ret);

	if (strlen($bar) > 0) {

		$Sl = "SELECT * FROM possets WHERE div = '".USER_DIV."'";
		$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);

		if (pg_numrows ($Rs) < 1){
			return details($_POST,"Please go set the point of sale settings under the stock settings");
		}
		$Dets = pg_fetch_array($Rs);
		if($Dets['opt'] == "No"){

			switch (substr($bar,(strlen($bar)-1),1)) {
					case "0":
						$tab = "ss0";
						break;
					case "1":
						$tab = "ss1";
						break;
					case "2":
						$tab = "ss2";
						break;
					case "3":
						$tab = "ss3";
						break;
					case "4":
						$tab = "ss4";
						break;
					case "5":
						$tab = "ss5";
						break;
					case "6":
						$tab = "ss6";
						break;
					case "7":
						$tab = "ss7";
						break;
					case "8":
						$tab = "ss8";
						break;
					case "9":
						$tab = "ss9";
						break;
					default:
						return details($_POST,"The code you selected is invalid");

				}
			db_conn('cubit');

			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			$stid = barext_dbget($tab,'code',$bar,'stock');

			if(!($stid>0)){
				return details($_POST,"<li class='err'><b>ERROR</b>: The bar code you selected is not in the system or is not available.</li>");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "
				INSERT INTO hire.hire_invitems (
					invid, whid, stkid, qty, amt, disc, discp, ss, serno, div
				) VALUES (
					'$invid', '$s[whid]', '$stid', '1', '$s[selamt]', '$s[selamt]', '0', '0', '$bar', '$bar', '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			$Sl = "UPDATE ".$tab." SET active = 'no' WHERE code = '$bar' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);

			$stid=ext_dbget('stock','bar',$bar,'stkid');

			if(!($stid>0)){return details($_POST,"<li class='err'><b>ERROR</b>: The bar code you selected is not in the system or is not available.</li>");}

			$Sl = "SELECT * FROM stock WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$Rs = db_exec($Sl);
			$s = pg_fetch_array($Rs);

			# put scanned-in product into invoice db
			$sql = "INSERT INTO hire.hire_invitems(invid, whid, stkid, qty, amt, disc, discp,ss, div) VALUES('$invid', '$s[whid]', '$stid', '1', '$s[selamt]','0','0','$bar', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);

			# update stock(alloc + qty)
			$sql = "UPDATE stock SET alloc = (alloc + '1') WHERE stkid = '$stid' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		}

	}

/* --- Start button Listeners --- */
	if(isset($doneBtn)){
		# check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM hire.hire_invitems WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class=err> Error : Invoice number has no items.";
			return details($_POST, $error);
		}

		$TOTAL = sprint($TOTAL-$rounding);

		if(($pcash + $pcheque + $pcc + $pcredit) < $TOTAL) {

			return details($_POST, "<li class='err'>The total of all the payments is less than the invoice total</li>");

		}

		$change = sprint(sprint($pcash+$pcheque+$pcc+$pcredit)-sprint($TOTAL));

		$pcash = sprint($pcash-$change);

		if($pcash < 0) {
			$pcash = 0;
		}

		if(sprint($pcash + $pcheque + $pcc + $pcredit) != sprint($TOTAL)) {
			return details($_POST, "<li class='err'>The total of all the payments is not equal to the invoice total.<br>
			(You can only overpay with cash)</li>");
		}


		// make plant available
		$sql = "UPDATE hire.hire_invoices SET done = 'y' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice status in Cubit.",SELF);
		# print the invoice
		$OUTPUT = "<script>printer('pos-invoice-print.php?invid=$invid');move('pos-invoice-new.php');</script>";
		require("template.php");


	} elseif(isset($cancel)) {

		// Final Laytout
		$write = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>New Point of Sale Invoice Saved</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Invoice for <b>$client</b> has been saved.</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-new.php'>New Point of Sale Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='pos-invoice-list.php'>View Point of Sale Invoices</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
		return $write;
	}else{
	if(isset($wtd)){$_POST['wtd']=$wtd;}
		return details($_POST);
	}
/* --- End button Listeners --- */

}




function returnHire()
{

	extract ($_REQUEST);

	pglib_transaction("BEGIN");

	if (isset($return)) {
		$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$invid'";
		$hi_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
		$hi_data = pg_fetch_array($hi_rslt);

		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$hi_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		$sql = "SELECT * FROM core.accounts WHERE topacc='1050' AND accnum='000'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
		$acc_data = pg_fetch_array($acc_rslt);

 		// Calculate the totals
 		$item_total = 0;
 		foreach ($return as $item_id=>$value) {
 			$sql = "SELECT * FROM hire.hire_invitems WHERE id='$item_id'";
 			$inv_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
 			$inv_data = pg_fetch_array($inv_rslt);

 			$item_total += $inv_data["amt"];
		}
		$discount = sprint($item_total/100*$hi_data["traddisc"]);

		$subtot = $item_total;
		$subtot += $hi_data["delivery"];
		$subtot -= $discount;
		$vat = $subtot/100*14;
		$total = $subtot+$vat;

		// Retrieve address
		if ($branch_addr > 0) {
			$sql = "
			SELECT branch_descrip FROM cubit.customer_branches
			WHERE id='$branch_addr'";
			$cusaddr_rslt = db_exec($sql) or errDie("Unable to retrieve branch.");
			$cusaddr = pg_fetch_result($cusaddr_rslt, 0);
		} else {
			$cusaddr = $cust_data["paddr1"];
		}

		$hire_invnum = "$hi_data[invnum]".rev($hi_data["invid"]);
		# Insert purchase to DB
		$sql = "
			INSERT INTO cubit.nons_invoices (
				cusname, cusnum, cusaddr, chrgvat, sdate, odate, done, 
				username, prd, invnum, typ, div, accid, discount, 
				delivery, hire_invid, hire_invnum, cordno, subtot, total, vat
			) VALUES (
				'$cust_data[surname]', '$cust_data[cusnum]', '$cusaddr', 'yes', CURRENT_DATE, CURRENT_DATE, 'n', 
				'".USER_NAME."', '".PRD_DB."', 0, 'inv', '".USER_DIV."', '$acc_data[accid]', '$discount', 
				'$hi_data[delivery]', '$invid', '$hire_invnum', '$hi_data[cordno]', '$subtot', '$total', '$vat'
			)";
		$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

		$nons_invid = lastinvid();

		$subtot = 0;

		foreach ($return as $item_id=>$value) {
			$sql = "SELECT * FROM hire.hire_invitems WHERE id='$item_id'";
			$hire_rslt = db_exec($sql) or errDie("Unable to retrieve hire items.");
			$hire_data = pg_fetch_array($hire_rslt);

			$sql = "SELECT * FROM cubit.assets WHERE id='$hire_data[asset_id]'";
			$des_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
			$asset_data = pg_fetch_array($des_rslt);
			$itemdes = getSerial($asset_data["id"], 1) . " $asset_data[des]";

			if ($hire_data["basis"] == "per_hour") {
				$des = "$itemdes hired for $hire_data[hours] hours.";
			} elseif ($hire_data["basis"] == "per_day") {
				$des = "$itemdes hired from $hire_data[from_date] to $hire_data[to_date].";
			} elseif ($hire_data["basis"] == "per_week") {
				$des = "$itemdes hired for $hire_data[weeks] weeks.";
			} else {
				$des = "$itemdes hired for $hire_data[months] months.";
			}

			if ($hire_data["qty"] && $hire_data["amt"]) {
				$unitcost =	$hire_data["amt"] / $hire_data["qty"];
			} else {
				$unitcost = 0;
			}

			#added to try to correct vat/discount not working
//			if ($hi_data["traddisc"] != 0){
//				$unitcost = ($unitcost/100) * $hi_data["traddisc"];
//				$hire_data['amt'] = ($hire_data['amt']/100) * $hi_data["traddisc"];
//			}

			$sql = "
				INSERT INTO hire.hire_nons_inv_items (
					invid, qty, description, div, amt, unitcost, vatex, 
					accid, item_id, asset_id, hired_days
				) VALUES (
					'$nons_invid', '$hire_data[qty]', '$des', ".USER_DIV.", '$hire_data[amt]', '$unitcost',  '2', 
					'$acc_data[accid]', '$item_id', '$hire_data[asset_id]', '$hire_data[total_days]'
				)";
			db_exec($sql) or errDie("Unable to add non stock items.");

			$sql = "
				INSERT INTO hire.hire_return (
					item_id, invid, asset_id
				) VALUES (
					'$item_id', '$nons_invid', '$hire_data[asset_id]'
				)";
			$return_rslt = db_exec($sql) or errDie("Unable to register return.");

			$subtot += $hire_data["amt"];
		}
/*
		$subtot += $hi_data["delivery"];
		$subtot -= $hi_data["discount"];
		$vat = $subtot/100*14;
		$total = $subtot+$vat;

		$sql = "UPDATE cubit.nons_invoices
					SET vat = '$vat', total = '$total', subtot='$subtot',
						balance='$total'
					WHERE invid='$nons_invid'";
		db_exec($sql) or errDie("Unable to update non stock invoice.");
 */
		pglib_transaction("COMMIT") or errDie("Unable to commit transaction.");

// 		header("Location: hire-nons-invoice-print.php?invid=$nons_invid&key=cconfirm&ctyp=s&cusnum=$hi_data[cusnum]&post=true");

		$OUTPUT = "<script>printer(\"hire/hire-nons-invoice-print.php?invid=$nons_invid&key=cconfirm&ctyp=s&cusnum=$hi_data[cusnum]&post=true\");move(\"".SELF."\");</script>";

		return $OUTPUT;
	}

}




function invoiceHire()
{

	extract ($_REQUEST);

	pglib_transaction("BEGIN");

	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$invid'";
	$hi_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$hi_data = pg_fetch_array($hi_rslt);

	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$hi_data[cusnum]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
	$cust_data = pg_fetch_array($cust_rslt);

	$sql = "SELECT * FROM core.accounts WHERE topacc='1050' AND accnum='000'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$acc_data = pg_fetch_array($acc_rslt);

// 	$sql = "
// 	INSERT INTO hire.hire_invoices (deptid, cusnum,
// 		deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno,
// 		ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot,
// 		vat, total, balance, comm, printed, done, div, username, rounding,
// 		delvat, vatnum, pcash, pcheque, pcc, pcredit, invnum)
// 	VALUES('$hi_data[deptid]', '$hi_data[cusnum]',
// 		'$hi_data[deptname]', '$hi_data[cusacc]', '$hi_data[cusname]',
// 		'$hi_data[surname]', '$hi_data[cusaddr]', '$hi_data[cusvatno]',
// 		'$hi_data[cordno]', '$hi_data[ordno]', '$hi_data[chrgvat]',
// 		'$hi_data[terms]', '$hi_data[traddisc]', '$hi_data[salespn]',
// 		'$hi_data[odate]', '$hi_data[delchrg]', '$hi_data[subtot]',
// 		'$hi_data[vat]' , '$hi_data[total]', '$hi_data[balance]',
// 		'$hi_data[comm]', 'y', 'y', '".USER_DIV."', '".USER_NAME."',
// 		'$hi_data[rounding]', '$hi_data[delvat]', '$hi_data[vatnum]',
// 		'$hi_data[pcash]', '$hi_data[pcheque]', '$hi_data[pcc]',
// 		'$hi_data[pcredit]', '$hi_data[invnum]')";
// 	db_exec($sql) or errDie("Unable to create new hire note.");
// 	$in_invid = pglib_lastid("hire.hire_invoices", "invid");
//
// 	$in_invnum = $hi_data["invnum"];
//
// 	$sql = "UPDATE hire.hire_invoices SET invnum='$in_invnum'
// 			WHERE invid='$in_invid'";
// 	db_exec($sql) or errDie("Unable to update hire no.");

	$hire_invnum = "$hi_data[invnum]".rev($hi_data["invid"]);
	# Insert invoice to DB
	$sql = "
		INSERT INTO cubit.nons_invoices (
			cusnum, cusname, cusaddr, chrgvat, sdate, odate, 
			subtot, balance, vat, total, done, username, 
			prd, invnum, typ, div, accid, discount, 
			delivery, hire_invid, hire_invnum, cusvatno, cordno
		) VALUES (
			'$cust_data[cusnum]', '$cust_data[surname]', '$cust_data[paddr1]', 'yes', CURRENT_DATE, CURRENT_DATE, 
			'$hi_data[subtot]', '$hi_data[total]', '$hi_data[vat]', '$hi_data[total]', 'n', '".USER_NAME."', 
			'".PRD_DB."', '$hi_data[invnum]',  'inv', '".USER_DIV."', '$acc_data[accid]', '$hi_data[discount]', 
			'$hi_data[delivery]', '$hi_data[invid]', '$hire_invnum', '$cust_data[vatnum]', '$hi_data[cordno]'
		)";
	$rslt = db_exec($sql) or errDie("Unable to create template Non-Stock Invoice.",SELF);

	db_conn("hire");
	$nons_invid = lastinvid();

	db_conn("cubit");

	$sql = "SELECT * FROM hire.hire_invitems WHERE invid='$invid'";
	$hire_rslt = db_exec($sql) or errDie("Unable to retrieve hire items.");

	while ($hire_data = pg_fetch_array($hire_rslt)) {
		$sql = "SELECT des FROM cubit.assets WHERE id='$hire_data[asset_id]'";
		$des_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$itemdes = pg_fetch_result($des_rslt, 0);

		if ($hire_data["basis"] == "per_hour") {
			$des = getSerial($hire_data["asset_id"], 1).
				   " $itemdes hired for $hire_data[hours] hours.";
		} elseif ($hire_data["basis"] == "per_day") {
			$des = getSerial($hire_data["asset_id"], 1).
				   " $itemdes hired from $hire_data[from_date] to $hire_data[to_date].";
		} elseif ($hire_data["basis"] == "per_week") {
			$des = getSerial($hire_data["asset_id"], 1).
				   " $itemdes hired for $hire_data[weeks] weeks.";
		} else {
			$des = getSerial($hire_data["asset_id"], 1).
				   " $itemdes hired for $hire_data[months] months.";
		}

		$unitcost = $hire_data["amt"] / $hire_data["qty"];

		$sql = "
			INSERT INTO hire.hire_nons_inv_items (
				invid, qty, description, div, amt, unitcost, vatex,
				 accid, item_id, asset_id
			) VALUES (
				'$nons_invid', '$hire_data[qty]', '$des', ".USER_DIV.", '$hire_data[amt]', '$unitcost',  '2', 
				'$acc_data[accid]', '$hire_data[id]', '$hire_data[asset_id]'
			)";
		db_exec($sql) or errDie("Unable to add non stock items.");

/*		$sql = "
		INSERT INTO hire.hire_invitems (invid, qty, ss, div, amt, discp, disc,
			unitcost, noted, serno, vatcode, description, account, from_date,
			to_date, asset_id, basis, hours, stkid, collection, weeks, days)
		VALUES ('$in_invid', '$hire_data[qty]', '$hire_data[ss]',
			'$hire_data[div]', '$hire_data[amt]', '$hire_data[discp]',
			'$hire_data[disc]', '$hire_data[unitcost]', '$hire_data[noted]',
			'$hire_data[serno]', '$hire_data[vatcode]', '$hire_data[description]',
			'$hire_data[account]', '$hire_data[from_date]', '$hire_data[to_date]',
			'$hire_data[asset_id]', '$hire_data[basis]', '$hire_data[hours]',
			'$hire_data[stkid]', '$hire_data[collection]', '$hire_data[weeks]',
			'$hire_data[days]')";
		db_exec($sql) or errDie("Unable to create new hire note items.");*/
	}

	pglib_transaction("COMMIT");

	header("Location: hire-nons-invoice-print.php?invid=$nons_invid&key=cconfirm&ctyp=s&cusnum=$hi_data[cusnum]&post=true&monthly=1");
	return $OUTPUT;

}




function checkCustBasis($cust_id)
{

	$sql = "SELECT * FROM hire.cust_basis WHERE cust_id='$cust_id'";
	$cb_rslt = db_exec($sql) or errDie("Unable to retrieve customer basis.");
	$cb_data = pg_fetch_array($cb_rslt);

	if (!pg_num_rows($cb_rslt)) {
		$sql = "SELECT * FROM cubit.assets";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

		while ($asset_data = pg_fetch_array($asset_rslt)) {
			$sql = "SELECT * FROM hire.basis_prices WHERE assetid='$asset_data[id]'";
			$bp_rslt = db_exec($sql)
				or errDie("Unable to retrieve default basis price.");

			while ($bp_data = pg_fetch_array($bp_rslt)) {
				$sql = "
					INSERT INTO hire.cust_basis (
						cust_id, asset_id, hour, day, week
					) VALUES (
						'$cust_id', '$asset_data[id]', '$bp_data[per_hour]', '$bp_data[per_day]', '$bp_data[per_week]'
					)";
				db_exec($sql) or errDie("Unable to add default basis.");
			}
		}
	}

	return;
}

// function interHireOut()
// {
// 	extract($_REQUEST);
// 	$sql = "SELECT * FROM cubit.assets WHERE id='$asset_id'";
// 	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
// 	$asset_data = pg_fetch_array($asset_rslt);
//
//
//
// function interHireIn()
// {
// 	// Add a temporary asset to cubit
// 	extract($_REQUEST);
// 	$sql = "SELECT * FROM cubit.assets WHERE id='$asset_id'";
// 	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
// 	$asset = pg_fetch_array($asset_rslt);
//
// 	$sql = "INSERT INTO cubit.assets (serial, locat, des, date, bdate, amount,
// 				div, grpid, accdep, dep_perc, dep_month, serial2, team_id,
// 				puramt, conacc, remaction, saledate, saleamt, invid,
// 				autodepr_date, temp_asset)
// 			VALUES ('$asset[serial]', '$asset[locat]', '$asset[des]',
// 				'$asset[date]', '$asset[bdate]', '$asset[amount]',
// 				'$asset[div]', '$asset[grpid]', '$asset[accdep]',
// 				'$asset[dep_perc]', '$asset[dep_month]', '$asset[serial2]',
// 				'$asset[team_id]', '$asset[puramt]', '$asset[conacc]',
// 				'$asset[remaction]', '$asset[saledate]', '$asset[saleamt]',
// 				'$asset[invid]', '$asset[autodepr_date]', 'y')";
// 	db_exec($sql) or errDie("Unable to retrieve asset.");
//
// }




function newPos()
{

	extract ($_REQUEST);

	$deptid = 2;
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;
	$vatnum = "";
	$cusacc = "";
	$telno = "";

	$sql = "
		INSERT INTO cubit.pinvoices (
			deptid, cusnum, cordno, ordno, chrgvat, terms, traddisc, salespn, 
			odate, delchrg, subtot, vat, total, balance, comm, username, 
			printed, done, prd, vatnum, cusacc, telno, div
		) VALUES (
			'$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms', '$traddisc', '$salespn', 
			'$odate', '$delchrg', '$SUBTOT', '$vat' , '$total', '$total', '$comm', '".USER_NAME."', 
			'n', 'n', '".PRD_DB."', '$vatnum', '$cusacc', '$telno', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	$invid = lastinvid();
	header("Location: payment.php?cusnum=$cusnum");

}


?>
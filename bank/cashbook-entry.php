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

require("../settings.php");
require("../core-settings.php");

$OUTPUT = enter($_POST);

require("../template.php");




function enter($_POST)
{

	$shownewaccount = "";

	extract($_POST);

	if(isset($ledger) && $ledger != "sel") {
		$ledgers = $ledger."<input type='hidden' name='ledger' value='$ledger'>";

		if($ledger == "Customer Ledger") {

			db_conn('cubit');

			$Sl = "SELECT cusnum,accno,surname FROM customers WHERE location='loc' ORDER BY surname";
			$Ri = db_exec($Sl) or errDie("Unable to select customer");

			$accounts = "
				<select name='account' onchange='if (this.value==\"multi\") document.form.submit();'>
					<option value='sel'>Select Customer</option>
					<option value='multi'>Multiple Customers/One Receipt</option>";
			while($data = pg_fetch_array($Ri)) {
				if(isset($account) && $account == $data['cusnum']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$accounts .= "<option value='$data[cusnum]' $sel>$data[surname]</option>";
			}
			$accounts .= "</select>";

		} elseif($ledger == "Employee Ledger") {

			db_conn('cubit');

			$Sl = "SELECT empnum,sname,fnames FROM employees ORDER BY sname,fnames";
			$Ri = db_exec($Sl) or errDie("Unable to get employee data.");

			$accounts = "
				<select name='account'>
					<option value='sel'>Select Employee</option>";
			while($data = pg_fetch_array($Ri)) {
				if(isset($account) && $account == $data['empnum']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$accounts .= "<option value='$data[empnum]' $sel>$data[sname], $data[fnames]</option>";
			}
			$accounts .= "</select>";
		} elseif($ledger == "General Ledger") {

			$shownewaccount = "<input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'>";

			db_conn('core');

			$Sl = "SELECT accid,accname,topacc,accnum FROM trial_bal WHERE period='".PRD_DB."' ORDER BY topacc,accnum";
			$Ri = db_exec($Sl) or errDie("Unable to get accounts.");

			$accounts = mkAccSelect ("account",$account);

// 			$accounts = "
// 				<select name='account'>
// 					<option value='sel'>Select Account</option>";
// 			while($data = pg_fetch_array($Ri)) {
// 				if(isb($data['accid'])) {
// 					continue;
// 				}
// 				if(isset($account) && $account == $data['accid']) {
// 					$sel = "selected";
// 				} else {
// 					$sel = "";
// 				}
// 				$accounts .= "<option value='$data[accid]' $sel>$data[topacc]/$data[accnum] $data[accname]</option>";
// 			}
// 			$accounts .= "</select>";

		}  elseif($ledger == "Inventory Ledger") {

			db_conn('cubit');

			$Sl = "SELECT stkid,stkcod FROM stock  ORDER BY stkcod";
			$Ri = db_exec($Sl) or errDie("unable to get stock.");

			$accounts = "
				<select name='account'>
					<option value='sel'>Select Stock</option>";
			while($data = pg_fetch_array($Ri)) {
				if(isset($account) && $account == $data['stkid']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$accounts .= "<option value='$data[stkid]' $sel>$data[stkcod]</option>";
			}
			$accounts .= "</select>";

		} elseif($ledger == "Supplier Ledger") {

			db_conn('cubit');

			$Sl = "SELECT supid,supname FROM suppliers  WHERE location='loc' ORDER BY supname";
			$Ri = db_exec($Sl) or errDie("unable to get suppliers.");

			$accounts = "
				<select name='account'>
					<option value='sel'>Select Supplier</option>";
			while($data = pg_fetch_array($Ri)) {
				if(isset($account) && $account == $data['supid']) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$accounts .= "<option value='$data[supid]' $sel>$data[supname]</option>";
			}
			$accounts .= "</select>";

		}

		if($ledger == "Employee Ledger") {
			$types = "
				<select name='type' onChange='javascript:document.form.submit();'>
					<option value='sel'>Select Type</option>
					<option value='payment'>Payment</option>
				</select>";
		} else {
			$types = "
				<select name='type' onChange='javascript:document.form.submit();'>
					<option value='sel'>Select Type</option>
					<option value='payment'>Payment</option>
					<option value='receipt'>Receipt</option>
				</select>";
		}
	} else {
		$ledgers = "
			<select name='ledger' onChange='javascript:document.form.submit();'>
				<option value='sel'>Select Ledger</option>
				<option value='Customer Ledger'>Customer Ledger</option>
				<option value='Employee Ledger'>Employee Ledger</option>
				<option value='General Ledger'>General Ledger</option>
				<option value='Inventory Ledger'>Inventory Ledger</option>
				<option value='Supplier Ledger'>Supplier Ledger</option>
			</select>";
		$accounts = "";
		$types = "";
	}

	if (isset($ledger) && $ledger == "Customer Ledger" && isset($account) && $account == "multi") {
		header("Location: bank-recpt-multi-debtor.php");
		exit;
	} else if(isset($ledger) && $ledger != "sel" && isset($account) && $account != "sel" && isset($type) && $type != "sel") {
		if($ledger == "Customer Ledger") {
			if($type == "payment") {
				header("Location: bank-pay-cus.php?cusnum=$account");
				exit;
			} elseif($type == "receipt") {
				header("Location: bank-recpt-inv.php?cusnum=$account&e=yes");
				exit;
			}
		}elseif($ledger == "Employee Ledger") {
			if($type == "payment") {
				header("Location: ../salwages/employee-pay.php?id=$account&bankpay=t");
				exit;
			}
		}elseif($ledger == "General Ledger") {
			if($type == "payment") {
				header("Location: bank-pay-add.php?account=$account");
				exit;
			} elseif($type == "receipt") {
				header("Location: bank-recpt-add.php?account=$account");
				exit;
			}
		}elseif($ledger == "Supplier Ledger") {
			if($type == "payment") {
				header("Location: bank-pay-supp.php?supid=$account&e=yes");
				exit;
			} elseif($type == "receipt") {
				header("Location: bank-recpt-supp.php?account=$account&e=yes");
				exit;
			}
		}elseif($ledger == "Inventory Ledger") {
			if($type == "payment") {
				header("Location: stock-tran.php?account=$account&type=payment&e=yes");
				exit;
			} elseif($type == "receipt") {
				header("Location: stock-tran.php?account=$account&type=receipt&e=yes");
				exit;
			}
		}
	}

	$out = "
		<h3>Cashbook Entry</h3>
		<table border='0' cellpadding='1' cellspacing='1'>
		<form action='".SELF."' method='POST' name='form'>
			<tr>
				<th>Select Ledger</th>
				<th>Select Account $shownewaccount</th>
				<th>Type</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$ledgers</td>
				<td>$accounts</td>
				<td>$types</td>
			</tr>
		</form>
		</table>";
	return $out;

}



?>
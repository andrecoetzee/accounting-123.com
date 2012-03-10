<?php

require ("../settings.php");

//error_reporting(E_ALL);

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "hirenotes":
			$OUTPUT = hireNotes();
			break;
		case "invoice":
			$OUTPUT = invoice();
			break;
		case "reset":
			$OUTPUT = resetHire();
			break;
	}
} else {
	$OUTPUT = hireNotes();
}

$OUTPUT .=
	mkQuickLinks(
		ql("hire-invoice-new.php", "New Hire"),
		ql("hire_view.php", "View Hire"),
		ql("hire-invoices-report.php", "Hire Invoices Report")
	);

require ("../template.php");

function hireNotes($errors="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["date_year"] = date("Y");
	$fields["date_month"] = date("m");
	$fields["date_day"] = date("d");
	
	extract ($fields, EXTR_SKIP);

	// Retrieve monthly hire notes for this customer
	$sql = "SELECT * FROM hire.monthly_invoices WHERE invnum>0 AND invoiced='0'
			ORDER BY invnum DESC";
	$mi_rslt = db_exec($sql) or errDie("Unable to retrieve monthly hire notes.");

	$mi_out = "";
	while ($mi_data = pg_fetch_array($mi_rslt)) {
		$sql = "SELECT *,
					(SELECT serial FROM cubit.assets WHERE id=asset_id) AS serial,
					(SELECT des FROM cubit.assets WHERE id=asset_id) as des
				FROM hire.monthly_invitems
				WHERE invid='$mi_data[hire_invid]'";
		$mii_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

		if (!pg_num_rows($mii_rslt)) {
			$sql = "DELETE FROM hire.monthly_invoices WHERE invid='$mi_data[invid]'";
			db_exec($sql) or errDie("Unable to remove monthly invoice.");

			continue;
		}

		$mi_out .= "<tr>
			<th colspan='5'>Monthly Hire No: $mi_data[invnum]</th>
			<th align='right'>
				<input type='submit' name='invoice[$mi_data[invid]]'
				value='Invoice' />
			</th>
		</tr>";

		$sql = "SELECT surname, cusname FROM cubit.customers
					WHERE cusnum='$mi_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
		$cust_data = pg_fetch_array($cust_rslt);

		while ($mii_data = pg_fetch_array($mii_rslt)) {
			if (!isset($mii_data["invoiced_month"])) $mii_data["invoiced_month"] = 0;
			if ($mii_data["invoiced_month"] == date("m")) {
				continue;
			}

			if (empty($mii_data["e_invoiced"])) {
				$invoiced = "Never";
			} else {
				$invoiced = date("d-m-Y", $mii_data["e_invoiced"]);
			}

			$basis = ucwords(implode(" ", explode("_", $mii_data["basis"])));

			$mi_out .= "<tr bgcolor='".bgcolorg()."'>
				<td>$cust_data[surname] $cust_data[cusname]</td>
				<td>$basis</td>
				<td>".getSerial($mii_data["asset_id"], true)." $mii_data[des]</td>
				<td>$mii_data[qty]</td>
				<td>".sprint($mii_data["amt"])."</td>
				<td>&nbsp;</td>
			</tr>";
		}
	}

	if (empty($mi_out)) {
		$mi_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='6'>No invoices found.</td>
		</tr>";
	}

	if (!isset($cust_id)) $cust_id = 0;

	$OUTPUT = "<center>
	<h3>Monthly Processing</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><td colspan='2'>$errors</td></tr>
		<tr><th colspan='2'>Processing Date</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
		</tr>
	</table>
	<input type='hidden' name='cust_id' value='$cust_id' />
	<input type='hidden' name='key' value='invoice' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Customer</th>
			<th>Basis</th>
			<th>Item</th>
			<th>Qty</th>
			<th>Amount</th>
			<th>&nbsp;</th>
		</tr>
		$mi_out
		</form>
		<form method='post' action='".SELF."'>
		<input type='hidden' name='cust_id' value='$cust_id' />
		<input type='hidden' name='key' value='reset' />
		<tr>
			<td colspan='6' align='center'>
				<input type='submit' value='Reset' />
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

function invoice()
{
	extract ($_REQUEST);

	define("TOMMOROW", time() + (60 * 60 * 24));
	$date = "$date_year-$date_month-$date_day";

	pglib_transaction("BEGIN");
	
	$invid = 0;
	if (isset($invoice)) {
		foreach ($invoice as $id=>$value) {
			$sql = "SELECT * FROM hire.monthly_invoices WHERE invid='$id'";
			$mi_rslt = db_exec($sql) or errDie("Unable to retrieve monthly invoice.");
			$mi = pg_fetch_array($mi_rslt);

			if ($mi["invoiced"] == "1") {
				return hireNotes("
				<li class='err'>
					Another user already invoiced this monthly hire
				</li>");
			}

			$sql = "UPDATE hire.monthly_invoices SET invoiced='1' WHERE invid='$id'";
			db_exec($sql) or errDie("Unable to update monthly invoices.");

			$sql = "INSERT INTO hire.hire_invoices(invnum, deptid, cusnum,
						deptname, cusacc, cusname, surname, cusaddr, cusvatno,
						cordno, ordno, chrgvat, terms, traddisc, salespn, odate,
						subtot, vat, total, balance, comm, printed, done,
						div, username, rounding, delvat, vatnum, pcash, pcheque,
						pcc, pcredit, hire_invid)
					VALUES('$mi[invnum]', '$mi[deptid]', '$mi[cusnum]', 
						'$mi[deptname]', '$mi[cusacc]', '$mi[cusname]', 
						'$mi[surname]', '$mi[cusaddr]', '$mi[cusvatno]', 
						'$mi[cordno]', '$mi[ordno]', '$mi[chrgvat]', '$mi[terms]', 
						'$mi[traddisc]', '$mi[salespn]', '$mi[odate]',
						'$mi[subtot]', '$mi[vat]' , '$mi[total]',
						'$mi[balance]', '$mi[comm]', 'y', 'y', '".USER_DIV."', 
						'".USER_NAME."', '$mi[rounding]', '$mi[delvat]', 
						'$mi[vatnum]', '$mi[pcash]', '$mi[pcheque]', '$mi[pcc]', 
						'$mi[pcredit]', '$mi[hire_invid]')";
			db_exec($sql) or errDie("Unable to add hire note.");
			$invid = pglib_lastid("hire.hire_invoices", "invid");
			
			$sql = "INSERT INTO hire.reprint_invoices(invnum, deptid, cusnum,
						deptname, cusacc, cusname, surname, cusaddr, cusvatno,
						cordno, ordno, chrgvat, terms, traddisc, salespn, odate,
						subtot, vat, total, balance, comm, printed, done,
						div, username, rounding, delvat, vatnum, pcash, pcheque,
						pcc, pcredit, hire_invid, invid)
					VALUES('$mi[invnum]', '$mi[deptid]', '$mi[cusnum]', 
						'$mi[deptname]', '$mi[cusacc]', '$mi[cusname]', 
						'$mi[surname]', '$mi[cusaddr]', '$mi[cusvatno]', 
						'$mi[cordno]', '$mi[ordno]', '$mi[chrgvat]', '$mi[terms]', 
						'$mi[traddisc]', '$mi[salespn]', '$mi[odate]',
						'$mi[subtot]', '$mi[vat]' , '$mi[total]',
						'$mi[balance]', '$mi[comm]', 'y', 'y', '".USER_DIV."', 
						'".USER_NAME."', '$mi[rounding]', '$mi[delvat]', 
						'$mi[vatnum]', '$mi[pcash]', '$mi[pcheque]', '$mi[pcc]', 
						'$mi[pcredit]', '$mi[hire_invid]', '$invid')";
			db_exec($sql) or errDie("Unable to add hire note.");
			
			$sql = "UPDATE hire.hire_invoices SET odate='$date'
					WHERE invid='$invid'";
			db_exec($sql) or errDie("Unable to update hire note.");
			
			$sql = "UPDATE hire.reprint_invoices SET odate='$date'
					WHERE invid='$invid'";
			db_exec($sql) or errDie("Unable to update reprint info.");
			
			$sql = "SELECT hire_invid FROM hire.hire_invoices
					WHERE hire_invid='$mi[hire_invid]'";
			$hinvid_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
			$hinvid = pg_fetch_result($hinvid_rslt, 0);

			$sql = "SELECT max(revision) FROM hire.hire_invoices
					WHERE hire_invid='$mi[hire_invid]' AND hire_invid!='0'";
			$rev_rslt = db_exec($sql) or errDie("Unable to retrieve revision.");
			$rev = pg_fetch_result($rev_rslt, 0);
			if (empty($rev)) $rev = 0;
			$rev += 1;
			
			$sql = "UPDATE hire.hire_invoices SET revision='$rev'
					WHERE invid='$invid'";
			db_exec($sql) or errDie("Unable to add revision.");
			
			$sql = "UPDATE hire.reprint_invoices SET revision='$rev'
					WHERE invid='$invid'";
			db_exec($sql) or errDie("Unable to add revision to reprint.");

			$sql = "UPDATE hire.monthly_invoices SET invoiced='1',
						last_expected='".date("Y-m-d")."'
					WHERE invid='$id'";
			db_exec($sql) or errDie("Unable to update invoice status.");
		}

		$sql = "SELECT *, extract('epoch' FROM last_invoiced) AS e_invoiced
				FROM hire.monthly_invitems WHERE invid='$mi[hire_invid]'";
		$invi_rslt = db_exec($sql) or errDie("Unable to retrieve hire items.");
		
		while ($invi = pg_fetch_array($invi_rslt)) {
			$sql = "INSERT INTO hire.hire_invitems (invid, asset_id, qty, amt,
						from_date, to_date, basis, hours, weeks, collection,
						half_day, weekends, expected)
					VALUES ('$invid', '$invi[asset_id]', '$invi[qty]',
						'$invi[amt]', '$invi[from_date]', '$invi[to_date]',
						'$invi[basis]', '$invi[hours]', '$invi[weeks]',
						'$invi[collection]', '$invi[half_day]', '$invi[weekends]',
						'$invi[to_date]')";
			db_exec($sql) or errDie("Unable to create monthly items.");
			$item_id = pglib_lastid("hire.hire_invitems", "id");
			
			$sql = "INSERT INTO hire.reprint_invitems (invid, asset_id, qty, amt,
						from_date, to_date, basis, hours, weeks, collection,
						half_day, weekends, expected, item_id)
					VALUES ('$invid', '$invi[asset_id]', '$invi[qty]',
						'$invi[amt]', '$invi[from_date]', '$invi[to_date]',
						'$invi[basis]', '$invi[hours]', '$invi[weeks]',
						'$invi[collection]', '$invi[half_day]', '$invi[weekends]',
						'$invi[to_date]', '$item_id')";
			db_exec($sql) or errDie("Unable to create reprint items.");
			
			if (empty($invi["last_invoiced"])) {
				$sql = "UPDATE hire.hire_invitems
						SET to_date='$date',
							last_invoiced='$date'
						WHERE id='$item_id'";
				db_exec($sql) or errDie("Unable to update monthly.");

				$sql = "UPDATE hire.monthly_invitems SET to_date='$date',
							last_invoiced='$date'
						WHERE id='$invi[id]'";
				db_exec($sql) or errDie("Unable to update monthly.");
					
				$sql = "UPDATE hire.reprint_invitems
						SET to_date='$date',
							last_invoiced='$date'
						WHERE id='$item_id'";
				db_exec($sql) or errDie("Unable to update reprint items.");
			} else {
				$invoice_from = date("Y-m-d", ($invi["e_invoiced"] + (60 * 60 * 24)));
				$sql = "UPDATE hire.hire_invitems
						SET from_date='$invoice_from', to_date='$date',
							last_invoiced='$date'
						WHERE id='$item_id'";
				db_exec($sql) or errDie("Unable to update monthly");

				$sql = "UPDATE hire.monthly_invitems
							SET from_date='$invoice_from', to_date='$date',
								last_invoiced='$date'
						WHERE id='$invi[id]'";
				db_exec($sql) or errDie("Unable to update monthly.");
							
				
				$sql = "UPDATE hire.reprint_invitems
						SET from_date='$invoice_from', to_date='$date',
							last_invoiced='$date'
						WHERE id='$item_id'";
				db_exec($sql) or errDie("Unable to update monthly");
			}
			updateAmounts($item_id);
			
			if ($hinvid) {
				$sql = "DELETE FROM hire.hire_invoices WHERE invid='$hinvid'";
				db_exec($sql) or errDie("Unable to close hire note.");
				
				$sql = "DELETE FROM hire.hire_invitems WHERE invid='$hinvid'";
				db_exec($sql) or errDie("Unable to close hie note.");
			}
		}
		//header("Location:hire-invoice-new.php?invid=$invid");
		$OUTPUT = "<script>
			printer(\"hire/hire-invoice-new.php?invid=$invid&monthly=true\");
			move(\"".SELF."?key=hirenotes&cust_id=$cust_id\");
		</script>";
	}
	pglib_transaction("COMMIT");

	return $OUTPUT;
}

function resetHire()
{
	extract ($_REQUEST);

	$sql = "UPDATE hire.monthly_invoices SET invoiced='0'";
	$mi_rslt = db_exec($sql) or errDie("Unable to reset invoices.");

	return hireNotes();
}

function updateAmounts($item_id)
{
	pglib_transaction("BEGIN");
	
	$sql = "SELECT *, cusnum
			FROM hire.hire_invitems
				LEFT JOIN hire.hire_invoices
					ON hire_invitems.invid=hire_invoices.invid
			WHERE id='$item_id'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve hire items.");
	$item = pg_fetch_array($item_rslt);
	
	if ($item["basis"] == "per_day") {
		$from_time = getDTEpoch("$item[from_date] 0:00:00");
		$to_time = getDTEpoch("$item[to_date] 23:59:59");
		
		$days = 0;
		$weeks = 0;
		while ($from_time <= $to_time) {
			if (date("w", $from_time) == 0 && $item["weekends"]) {
				$days += 0.6;
			} else {
				++$days;
			}
			$from_time += 60 * 60 * 24;
		}
		$timeunits = ceil($days);
	} elseif ($item["basis"] == "per_week") {
		$timeunits = $item["weeks"];
	} elseif ($item["basis"] == "per_hour") {
		$timeunits = $item["hours"];
	}
	$amount = $item["qty"] * $timeunits * basisPrice($item["cusnum"],
		$item["asset_id"], $item["basis"]);
	if ($item["basis"] = "per_day") {
		$total_days = ",total_days='$timeunits'";
	} else {
		$total_days = "0";
	}

	$sql = "UPDATE hire.hire_invitems SET amt='$amount' $total_days WHERE id='$item_id'";
	db_exec($sql) or errDie("Unable to update hire items.");

	// Update totals
	$sql = "
	SELECT sum(amt) FROM hire.hire_invitems
	WHERE invid='$item[invid]'";
	$subtot_rslt = db_exec($sql) or errDie("Unable to retrieve subtotal.");
	$subtot = pg_fetch_result($subtot_rslt, 0);

	// Trade discount
	$sql = "SELECT traddisc FROM hire.hire_invoices WHERE invid='$item[invid]'";
	$disc_rslt = db_exec($sql) or errDie("Unable to retrieve discount.");
	$disc = pg_fetch_result($disc_rslt, 0);

	$traddiscamt = ($subtot / 100) * $disc;
	$subtot = $subtot - $traddiscamt;
	$vat = ($subtot / 100) * 14;
	$total = $subtot + $vat;

	$sql = "
	UPDATE hire.hire_invoices SET total='$total', vat='$vat',
		discount='$traddiscamt' 
	WHERE invid='$item[invid]'";
	db_exec($sql) or errDie("Unable to update amounts.");
	pglib_transaction("COMMIT");
	return $amount;
}

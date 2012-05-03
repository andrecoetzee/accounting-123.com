<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "select":
			$OUTPUT = select();
			break;
		case "balance":
			$OUTPUT = balance();
			break;
		case "balance_update":
			$OUTPUT = balance_update();
			break;
		case "reason":
			$OUTPUT = reason();
			break;
		case "reason_update":
			$OUTPUT = reason_update();
			break;
		case "comments":
			$OUTPUT = comments();
			break;
		case "comments_update":
			$OUTPUT = comments_update();
			break;
	}
} else {
	$OUTPUT = select();
}

require ("template.php");

function select($message="")
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["search"] = "";
	
	extract ($fields, EXTR_SKIP);

	if (empty($search)) $search = "[(EMPTY SEARCH FIELD)]";

	$sql = "SELECT supid, supno, supname FROM cubit.suppliers
			WHERE supno ILIKE '$search%' OR supname ILIKE '$search%'
			ORDER BY supno ASC";
	$suppliers_rslt = db_exec($sql) or errDie("Unable to retrieve suppliers.");
	
	if ($search == "[(EMPTY SEARCH FIELD)]") $search = "";
	
	$suppliers_out = "";
	while (list($supid, $supno, $supname) = pg_fetch_array($suppliers_rslt)) {
		$suppliers_out .= "
		<tr class='".bg_class()."'>
			<td>$supno</td>
			<td>$supname</td>
			<td><a href='".SELF."?key=balance&supid=$supid'>Select</a></td>
		</tr>";
	}
	
	if (empty($suppliers_out) && empty($search)) {
		$suppliers_out = "
		<tr class='".bg_class()."'>
			<td colspan='3'>
				<li>
					Please enter the first few letters of the creditors name or
					supplier no.
				</li>
			</td>
		</tr>";
	} elseif (empty($suppliers_out)) {
		$suppliers_out = "
		<tr class='".bg_class()."'>
			<td colspan='3'>
				<li>No results found.</li>
			</td>
		</tr>";
	}
		
	
	$OUTPUT = "
	<center>
	<h3>Add balance according to creditor</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='select' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$message</td>
		</tr>
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Supplier No.</th>
			<th>Name</th>
			<th>Select</th>
		</tr>
		$suppliers_out
	</table>
	</center>";
	 
	return $OUTPUT;
}

function balance()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["balance"] = "0.00";
	
	extract ($fields, EXTR_SKIP);
	
	$OUTPUT = "
	<center>
	<h3>Balance According to Creditor</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='balance_update' />
	<input type='hidden' name='supid' value='$supid' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Balance</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				".CUR."<input type='text' name='balance' style='text-align: right'
						value='$balance' />
			</td>
		</tr>
		<tr>
			<td align='center'><input type='submit' value='Write &raquo' /></td>
		</tr>
	</table>
	</form>
	</center>";
	
	return $OUTPUT;
}

function balance_update()
{
	extract ($_REQUEST);
	
	$sql = "SELECT supid FROM cubit.recon_creditor_balances WHERE supid='$supid'";
	$rcb_rslt = db_exec($sql) or errDie("Unable to retrieve creditor balance.");
	
	if (pg_num_rows($rcb_rslt)) {
		$sql = "UPDATE cubit.recon_creditor_balances SET balance='$balance'
				WHERE supid='$supid'";
	} else {
		$sql = "INSERT INTO cubit.recon_creditor_balances (supid, balance)
				VALUES ('$supid', '$balance')";
	}
	db_exec($sql) or errDie("Unable to update creditor balance.");
	
	return select("<li class='err'>Balance updated</li>");
}	

function reason()
{
	extract ($_REQUEST);
	
	if (!isset($supid) || !is_numeric($supid)) {
		return select();
	}
	
	$sql = "SELECT id, date, reason_id, amount FROM cubit.recon_balance_ct
			WHERE supid='$supid'
			ORDER BY id DESC";
	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");
	
	$sql = "SELECT id, reason FROM cubit.recon_reasons ORDER BY reason ASC";
	$reason_rslt = db_exec($sql) or errDie("Unable to retrieve reasons.");
	$balance_out = "";
	while (list($bal_id, $date, $reason_id, $amount) = pg_fetch_array($balance_rslt)) {
		$reasons_sel = "
		<select name='oreason_id'>
			<option value='0'>[None]</option>";
		pg_result_seek($reason_rslt, 0);
		while (list($id, $reason) = pg_fetch_array($reason_rslt)) {
			if ($reason_id == $id) {
				$sel = "selected='selected'";
			} else {
				$sel = "";
			}
			
			$reasons_sel .= "<option value='$id' $sel>$reason</option>";
		}
		$reasons_sel .= "</select>";
		
		$balance_out .= "
		<tr class='".bg_class()."'>
			<td>$date</td>
			<td>$reasons_sel</td>
			<td>
				<input type='text' name='amount[$bal_id]' value='$amount' size='8' />
			</td>
			<td><input type='checkbox' name='remove[$bal_id]' value='$bal_id' /></td>
		</tr>";
	}
	
	pg_result_seek($reason_rslt, 0);
	
	$reason_sel = "
	<select name='nreason_id'>
		<option value='0'>[None]</option>";
	while (list($id, $reason) = pg_fetch_array($reason_rslt)) {
		$reason_sel .= "<option value='$id'>$reason</option>";
	}
	$reason_sel .= "</select>";
	
	$OUTPUT = "
	<center>
	<h3>Add Balance According to Creditor</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='reason_update' />
	<input type='hidden' name='supid' value='$supid' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Reason</th>
			<th>Amount</th>
			<th>Remove</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".date("Y-m-d")."</td>
			<td>$reason_sel</td>
			<td><input type='text' name='namount' size='8' /></td>
			<td>&nbsp;</td>
		</tr>
		$balance_out
	</table>
	<input type='submit' value='Update' />
	</form>
	<table ".TMPL_tblDflts.">
		<tr class='".bg_class()."'>
			<td>
				<a href='recon_statement_ct.php?key=display&supid=$supid'
				style='font-size: 1.3em'>
					Return to Statement
				</a>
			</td>
		</tr>
	</center>";
	
	return $OUTPUT;
}

function reason_update()
{
	extract ($_REQUEST);
	
	if (is_numeric($namount)) {
		$sql = "INSERT INTO cubit.recon_balance_ct (supid, reason_id, amount)
				VALUES ('$supid', '$nreason_id', '$namount')";
		db_exec($sql) or errDie("Unable to add entry.");
	}
	
	if (isset($amount)) {
		foreach ($amount as $bal_id=>$value) {
			if (is_numeric($value)) {
				$sql = "UPDATE cubit.recon_balance_ct SET reason_id='$oreason_id', 
							amount='$value' WHERE id='$bal_id'";
				db_exec($sql) or errDie("Unable to add entry.");
			}
		}
	}
	
	if (isset($remove)) {
		foreach ($remove as $bal_id=>$value) {
			$sql = "DELETE FROM cubit.recon_balance_ct WHERE id='$bal_id'";
			db_exec($sql) or errDie("Unable to remove reason.");
		}
	}
	
	return reason();
}

function comments()
{
	extract ($_REQUEST);

	$sql = "
	SELECT id, date, comment FROM cubit.recon_comments_ct
	WHERE supid='$supid' ORDER BY id DESC";
	$comments_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");
	
	$comments_out = "";
	while ($comments_data = pg_fetch_array($comments_rslt)) {
		$comments_out .= "
		<tr class='".bg_class()."'>
			<td>$comments_data[date]</td>
			<td>".nl2br(base64_decode($comments_data["comment"]))."</td>
			<td><input type='checkbox' name='remove[$comments_data[id]]' value='1' /></td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Creditor Recon Statement Comments</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='comments_update' />
	<input type='hidden' name='supid' value='$supid' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Comment</th>
			<th>Remove</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".date("Y-m-d")."</td>
			<td><textarea name='n_comment'></textarea></td>
			<td>&nbsp;</td>
		</tr>
		$comments_out
		<tr>
			<td colspan='3' align='center'>
				<input type='submit' value='Update' />
			</td>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='3' align='center'>
				<a href='recon_statement_ct.php?key=display&supid=$supid'>
					Return to Statement
				</a>
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function comments_update()
{
	extract ($_REQUEST);
	
	if (!empty($n_comment)) {
		$sql = "
		INSERT INTO cubit.recon_comments_ct (comment, supid)
		VALUES ('".base64_encode($n_comment)."', '$supid')";
		db_exec($sql) or errDie("Unable to update comments.");
	}
	
	if (isset($remove)) {
		foreach ($remove as $id=>$value) {
			$sql = "DELETE FROM cubit.recon_comments_ct WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove comments.");
		}
	}
	
	return comments();
}
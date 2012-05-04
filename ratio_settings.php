<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "update":
			$OUTPUT = update();
			break;
	}
} else {
	$OUTPUT = display();
}

$OUTPUT .= mkQuickLinks(
	ql("health_report.php", "Business Health Report")
);

require ("template.php");

function display()
{
	extract($_REQUEST);

	// Retrieve account types
	$sql = "SELECT id, rname FROM cubit.ratio_account_types ORDER BY rname ASC";
	$type_rslt = db_exec($sql) or errDie("Unable to retrieve ratios.");

	$ratios_out = "";

	while ($type_data = pg_fetch_array($type_rslt)) {
		// Retrieve accounts
		$sql = "SELECT accounts.accid, topacc, accnum, accname
				FROM cubit.ratio_account_owners
					LEFT JOIN core.accounts
						ON ratio_account_owners.accid=accounts.accid
				WHERE type_id='$type_data[id]'
				ORDER BY id ASC";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts");

		$rows = pg_num_rows($acc_rslt) + 1;
		$bgcolor = bgcolorg();
		$results = pg_num_rows($acc_rslt);

		$ratios_out .= "
		<tr class='".bg_class()."'>
			<td rowspan='$rows'><b>$type_data[rname]</b></td>";

		$i = 0;
		$tr = "";
		while ($acc_data = pg_fetch_array($acc_rslt)) {
			// Should a new row be created
			if ($i) {
				$tr = "<tr class='".bg_class()."'>";
			} else {
				$tr = "";
			}

			$ratios_out .= "
			$tr
				<td>($acc_data[topacc]/$acc_data[accnum]) $acc_data[accname]</td>
				<td align='center'>
					<input type='checkbox' name='rem[$type_data[id]]'
					value='$acc_data[accid]'
					onchange='javascript:document.form.submit()' />
				</td>
			</tr>";

			$i++;
		}

		// Retrieve accounts
		$sql = "SELECT accid, topacc, accnum, accname FROM core.accounts
				ORDER BY topacc, accnum ASC";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

		$acc_sel = "<select name='account[$type_data[id]]'
					onchange='javascript:document.form.submit()'>";
		$acc_sel.= "<option value='0'>[None]</option>";
		while ($acc_data = pg_fetch_array($acc_rslt)) {
			$acc_sel .= "
			<option value='$acc_data[accid]'>
				($acc_data[topacc]/$acc_data[accnum]) $acc_data[accname]
			</option>";
		}
		$acc_sel .= "</select>";

		// New row
		if ($results) {
			$tr = "<tr class='".bg_class()."'>";
		} else {
			$tr = "";
		}

		$ratios_out .= "
			$tr
			<td>$acc_sel</td>
			<td>&nbsp;</td>
		</tr>";
	}

	if (empty($ratios_out)) {
		$ratios_out = "<tr class='".bg_class()."'>
			<td colspan='3'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>Link Accounts to Ratios</h3>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='update' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Ratio</th>
			<th>Accounts</th>
			<th>Remove</th>
		</tr>
		$ratios_out
	</table>
	</form>";

	return $OUTPUT;
}

function update()
{
	extract($_REQUEST);

	pglib_transaction("BEGIN");

	if (isset($rem)) {
		foreach ($rem as $type_id=>$accid) {
			$sql = "DELETE FROM cubit.ratio_account_owners
					WHERE type_id='$type_id' AND accid='$accid'";
			db_exec($sql) or errDie("Unable to remove entries.");
		}

	}

	if (isset($account)) {
		foreach ($account as $type_id=>$accid) {
			if ($accid) {
				$sql = "SELECT id FROM cubit.ratio_account_owners
						WHERE type_id='$type_id' AND accid='$accid'";
				$own_rslt = db_exec($sql) or errDie("Unable to retrieve owners.");

				if (!pg_num_rows($own_rslt)) {
					$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
							VALUES ('$type_id', '$accid')";
					db_exec($sql) or errDie("Unable to add new entries.");
				}
			}
		}
	}

	pglib_transaction("COMMIT");

	return display();

}
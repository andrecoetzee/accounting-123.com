<?php

require ("../settings.php");

$OUTPUT = export();

function export()
{
	$sql = "
	SELECT ttype, debitacc, creditacc, tdate, refno, amount, vat, details, iid
	FROM exten.tranreplay";
	$replay_rslt = db_exec($sql) or errDie("Unable to retrieve transactions.");

	$replay_out = "";
	while ($replay_data = pg_fetch_array($replay_rslt)) {
		$replay_out .= "$replay_data[ttype], $replay_data[debitacc], "
			."$replay_data[creditacc], $replay_data[tdate], "
			."$replay_data[refno], $replay_data[amount], $replay_data[vat],"
			."$replay_data[details], $replay_data[iid]\n";
	}

	header ("Content-Type: application/octet-stream");
	header ("Content-Transfer-Encoding: binary");
	header ("Content-Disposition: attachment; filename=\"replay_trans.csv\"");
	print $replay_out;
}
?>


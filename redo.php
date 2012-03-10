<?

ini_set("max_execution_time", 0);

require("settings.php");

//$accwh = "WHERE accid='5'";
$accwh = "";

pglib_transaction("BEGIN");
ob_start();

process();
reorder();

ob_end_flush();
pglib_transaction("COMMIT");

mdbg(false);

function process() {
	global $PRDMON, $accwh;
	
	$sql = "SELECT * FROM core.accounts $accwh";
	mdbg($sql);
	$rslt = db_exec($sql);
	
	print "<xmp>";	
	print "accounts to process: ".(pg_num_rows($rslt))."\n";
	
	$accnum = 0;
	while ($acc = pg_fetch_assoc($rslt)) {
		print "processing account number: ".(++$accnum)."\n";
		flush();
		ob_flush();
		
		$month = $PRDMON[1];
		$sql = "SELECT * FROM \"$month\".ledger WHERE acc='$acc[accid]' LIMIT 1";
		mdbg($sql);
		$ybrslt = db_exec($sql);
			
		if (pg_num_rows($ybrslt) > 0) {
			$yb = pg_fetch_assoc($ybrslt);
			if ($yb["debit"] == $yb["dbalance"] && $yb["credit"] == $yb["cbalance"]) {
				$p_dbal = 0;
				$p_cbal = 0;
			} else {
				$p_dbal = $yb["debit"];
				$p_cbal = $yb["credit"];
			}
		} else {
			$p_cbal = 0;
			$p_dbal = 0;
		}
		
		/* first move the transactions */
		for ($i = 1; $i <= 12; ++$i) {
			print ".";
			flush();
			ob_flush();
		
			$month = $PRDMON[$i];
			
			$sql = "SELECT id,date_part('month', edate) AS dp 
					FROM \"$month\".ledger 
					WHERE acc='$acc[accid]' AND date_part('month', edate)!='$month'";
			mdbg($sql);
			$wmrslt = db_exec($sql);
			
			if (pg_num_rows($wmrslt) >= 0) {
				while ($moverow = pg_fetch_assoc($wmrslt)) {
					$sql = "INSERT INTO \"$moverow[dp]\".ledger (acc, contra, edate, eref, 
								descript, credit, debit, div, caccname, ctopacc, caccnum, 
								cbalance, dbalance, refnum, sdate)
							SELECT acc, contra, edate, eref, descript, credit, debit, div, 
								caccname, ctopacc, caccnum, cbalance, dbalance, refnum, sdate 
							FROM \"$month\".ledger WHERE id='$moverow[id]'";
					mdbg($sql);
					db_exec($sql);
					
					$sql = "DELETE FROM \"$month\".ledger WHERE id='$moverow[id]'";
					mdbg($sql);
					db_exec($sql);
				}
			}
		}
		
		print "\n";
		flush();
		ob_flush();
		
		/* then create the balance transactions for every month start,
			if there are transaction but no balance transaction
			BALANCE defined as 0 debit and credit transaction
				with the smallest id of the bunch and date YEAR-MONTH-01 */
		for ($i = 1; $i <= 12; ++$i) {
			print ".";
			flush();
			ob_flush();
			
			$month = $PRDMON[$i];
			
			// remove the previous balance entry
			$sql = "DELETE FROM \"$month\".ledger 
					WHERE acc=contra AND descript='Balance' 
						AND debit='0' AND credit='0'
						AND acc='$acc[accid]'";
			mdbg($sql);
			db_exec($sql);
			
			// no create the new balance entry
			$sql = "SELECT id, dbalance, cbalance 
					FROM \"$month\".ledger
					WHERE acc='$acc[accid]'
					ORDER BY id ASC
					LIMIT 1";
			mdbg($sql);
			$balrslt = db_exec($sql);
			
			if (pg_num_rows($balrslt) > 0) {
				$ai = pg_fetch_array($balrslt);
				
				// get smallest date's year
				$sql = "SELECT date_part('year', edate) AS syear
						FROM \"$month\".ledger
						WHERE acc='$acc[accid]'
						ORDER BY edate ASC
						LIMIT 1";
				mdbg($sql);
				$smrslt = db_exec($sql);
				$syear = pg_fetch_result($smrslt,  0, 0);
				
				moveids($month, $ai["id"]);
				
				$sql = "SELECT nextval('$month.ledger_id_seq')";
				mdbg($sql);
				db_exec($sql);
				
				// create the balance entry
				$sql = "INSERT INTO \"$month\".ledger (id, acc, contra, edate, eref, descript,
							credit, debit, div, caccname, ctopacc, caccnum, cbalance, dbalance)
						VALUES('$ai[id]', '$acc[accid]', '$acc[accid]', '$syear-$month-01', '0', 
							'Balance', '0', '0', '2', '$acc[accname]', '$acc[topacc]', 
							'$acc[accnum]', '$ai[cbalance]', '$ai[dbalance]')";
				mdbg($sql);
				db_exec($sql);
				
				// update ordering of the other transactions
				//$sql = "SELECT id FROM \"$month\".ledger WHERE id!='$ai[id]'";
				//mdbg($sql);
				//$ordrslt = db_exec($slq);
				
				//while ($ordrow = pg_fetch_assoc($ordrslt)) {
					//$sql = "UPDATE \"$month\".ledger 
				//}
			}
		}
		
		print "\n";
		flush();
		ob_flush();
		
		/* then update the running balances */
		for ($i = 1; $i <= 12; ++$i) {
			print ".";
			flush();
			ob_flush();
			
			$month = $PRDMON[$i];
			
			$sql = "SELECT id,debit,credit,dbalance,cbalance
				FROM \"$month\".ledger 
				WHERE acc='$acc[accid]'
				ORDER BY edate ASC,id ASC";
			mdbg($sql);
			$wmrslt = db_exec($sql);
			
			if (pg_num_rows($wmrslt) > 0) {
				while ($rc = pg_fetch_assoc($wmrslt)) {
					$p_cbal += $rc["credit"];
					$p_dbal += $rc["debit"];
					
					$sql = "UPDATE \"$month\".ledger SET cbalance='$p_cbal', dbalance='$p_dbal'
						WHERE id='$rc[id]'";
					mdbg($sql);
					db_exec($sql);
				}
			} else {
				$sql = "INSERT INTO \"$month\".ledger (acc, contra, edate, eref, descript,
						credit, debit, div, caccname, ctopacc, caccnum, cbalance, dbalance)
					VALUES('$acc[accid]', '$acc[accid]', '2006-$month-01', '0', 'Balance',
						'0', '0', '2', '$acc[accname]', '$acc[topacc]', '$acc[accnum]', 
						'$p_cbal', '$p_dbal')";
				mdbg($sql);
				db_exec($sql);
			}
			
			/* update the id of the last transaction */
			/*
			$sql = "SELECT id
					FROM \"$month\".ledger 
					WHERE acc='$acc[accid]'
					ORDER BY edate DESC
					LIMIT 1";
			mdbg($sql);
			$wmrslt = db_exec($sql);
			
			if (pg_num_rows($wmrslt) > 0) {
				$updid = pg_fetch_result($wmrslt, 0, 0);
				$sql = "UPDATE \"$month\".ledger SET id=(
							SELECT nextval('\"$month\".ledger_id_seq'::regclass)
						) WHERE id='$updid'";
				db_exec($sql);
			}*/

			/* update the trial balance */
			$sql = "UPDATE core.trial_bal SET debit='$p_dbal', credit='$p_cbal'
				WHERE period='$i' AND accid='$acc[accid]'";
			mdbg($sql);
			db_exec($sql);
		}
		
		print "\n";
		flush();
		ob_flush();
	}
	
	print "\nDone.";
	print "</xmp>";
}

function moveids($month, $minid) {
	//$sql = "UPDATE \"$month\".ledger SET id=id WHERE id<'$minid'";
	//db_exec($sql);
	
	$sql = "UPDATE \"$month\".ledger SET id=id+1 WHERE id>='$minid'";
	db_exec($sql);
}

function reorder() {
	global $PRDMON, $accwh;
	
	print "<xmp>";
	print "Re-Ordering accounts.\n";
		/* reorder by date,id */
		for ($i = 1; $i <= 12; ++$i) {
			print "Period: $i\n";
			flush();
			ob_flush();
			
			//print ".";
			flush();
			ob_flush();
			
			$month = $PRDMON[$i];
			
			$sql = "SELECT id
					FROM \"$month\".ledger 
					ORDER BY edate ASC,id ASC";
			mdbg($sql);
			$wmrslt = db_exec($sql);
			
			while ($rc = pg_fetch_assoc($wmrslt)) {					
				print ".";
				flush();
				ob_flush();
			
				//print "ordering: $month-$rc[id]\n";
				$sql = "UPDATE \"$month\".ledger SET id=id 
						WHERE id='$rc[id]'";
				mdbg($sql);
				db_exec($sql);
			}
			
			print "\n";
			flush();
			ob_flush();
		}
		
	print "Done.";
	print "</xmp>";
}

function reorder2() {
	global $PRDMON, $accwh;
	
//	$accwh = "WHERE accid='5'";
	
	$sql = "SELECT * FROM core.accounts $accwh";
	mdbg($sql);
	$rslt = db_exec($sql);
	
	print "<xmp>";
	
	print "accounts to reorder: ".(pg_num_rows($rslt))."\n";
	
	$touched5 = false;
	
	$accnum = 0;
	while ($acc = pg_fetch_assoc($rslt)) {
		print "reordering account number: ".(++$accnum)." ($acc[accid])\n";
		flush();
		ob_flush();
		
		/* reorder by date,id */
		for ($i = 1; $i <= 12; ++$i) {
			//print ".";
			flush();
			ob_flush();
			
			$month = $PRDMON[$i];
			
			$sql = "SELECT id
					FROM \"$month\".ledger 
					WHERE acc='$acc[accid]'
					ORDER BY edate ASC,id ASC";
			mdbg($sql);
			$wmrslt = db_exec($sql);
			
			while ($rc = pg_fetch_assoc($wmrslt)) {					
				print "ordering: $acc[accid]-$month-$rc[id]\n";
				$sql = "UPDATE \"$month\".ledger SET id=id 
						WHERE id='$rc[id]' AND acc='$acc[accid]'";
				mdbg($sql);
				db_exec($sql);
			}
			
			/* check sales account period 9 ordering */
			if ($touched5) {
				$sql = "SELECT descript FROM \"9\".ledger WHERE acc='5' LIMIT 1";
				$aaa = pg_fetch_result(db_exec($sql), 0, 0);
				
				if ($aaa != "Balance") {
					print "</xmp>";
					errDie("Fucked at $acc[accid] period ");
				}
			}
		}
		
		if ($acc["accid"] == "5") {
			$touched5 = true;
		}
		
		print "\n";
	}
	
	print "Done.";
	print "</xmp>";
}

function mdbg($sql) {
	static $sql_counter = 0;
	
	if ($sql === false) {
		print "Queries executed: $sql_counter<br />";
	} else {
		++$sql_counter;
		
		//$sql = preg_replace("/[\n\t ]+/", " ", $sql);
		//print "$sql\n";
	}
}

?>

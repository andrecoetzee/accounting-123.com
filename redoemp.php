<?


ini_set("max_execution_time", 0);

require("settings.php");
require("salwages/emp-functions.php");

if (!isset($_POST["key"])) {
	$_POST["key"] = "select";
}

switch ($_POST["key"]) {
	case "select":
		$OUTPUT = select();
		break;
	case "write":
		$OUTPUT = write();
		break;
}

function select() {
	global $ePRDMON;
	
	pglib_transaction("BEGIN");
	
	$sql = "SELECT * FROM cubit.employees";
	mdbg($sql);
	$rslt = db_exec($sql);
	
	print "<xmp>";
	
	print "employees to process: ".(pg_num_rows($rslt))."\n";
	
	$empnum = 0;
	while ($emp = pg_fetch_assoc($rslt)) {
		print "processing employee number: ".(++$empnum)."\n";
		flush();
		ob_flush();
		
		$sql = "SELECT * FROM \"3\".empledger WHERE empid='$emp[empnum]'";
		mdbg($sql);
		$ybrslt = db_exec($sql);
			
		if (pg_num_rows($ybrslt) > 0) {
			$yb = pg_fetch_assoc($ybrslt);
			$p_dbal = $yb["dbalance"];
			$p_cbal = $yb["cbalance"];
		} else {
			$p_dbal = 0;
			$p_cbal = 0;
		}			
		
		/* create the balance transactions */
		for ($i = 1; $i <= 12; ++$i) {
			$month = $ePRDMON[$i];
			
			$sql = "SELECT * FROM \"$month\".empledger 
					WHERE empid='$emp[empnum]'
					ORDER BY edate,id";
			$elrslt = db_exec($sql);
			
			if (pg_num_rows($elrslt) <= 0) {
				$sql = "INSERT INTO \"$month\".empledger (empid, contra, edate, ref, des,
							debit, credit, dbalance, cbalance, sdate, div)
						VALUES('$emp[empnum]', '0', CURRENT_DATE, 0, 'Balance', '0', '0', 
							'$p_dbal', '$p_cbal', CURRENT_DATE, 2)";
				db_exec($sql);
			} else {
				while ($row = pg_fetch_assoc($elrslt)) {
					$p_dbal += $row["debit"];
					$p_cbal += $row["credit"];
					
					if ($p_dbal >= $p_cbal) {
						$p_dbal = $p_dbal - $p_cbal;
						$p_cbal = 0;
					} else {
						$p_cbal = $p_cbal - $p_dbal;
						$p_dbal = 0;
					}
				}
			}
		}
	}
	
	print "\nDone.";
	print "</xmp>";
	
	mdbg(false);
	
	pglib_transaction("COMMIT");
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
<?

require("../settings.php");

dotaxed_sal271("salpaid");
dotaxed_sal271("salr");

function dotaxed_sal271($tbl) {
	$sql = "SELECT sp.*,e.payprd,e.idnum, e.sname,e.fnames
		FROM cubit.$tbl sp LEFT JOIN cubit.employees e
			ON (sp.empnum=e.empnum)";
	$rslt = pge($sql);
	
	while ($row = pg_fetch_array($rslt)) {
		/* determine age */
		if (!empty($row["idnum"])) {
			$bd_year = 1900 + substr($row["idnum"], 0, 2);
			$bd_month = substr($row["idnum"], 2, 2);
			$bd_day = substr($row["idnum"], 4, 2);

			if (!checkdate($bd_month, $bd_day, $bd_year)) {
				$age = 1;
			} else {		
				$sql = "SELECT EXTRACT('year' FROM AGE('2007-02-28', '$bd_year-$bd_month-$bd_day'))";
				$agerslt = pge($sql);
				$age = pg_fetch_result($agerslt, 0, 0);
			}
		} else {
			$age = 1;
		}
		
		/* salary periods */
		switch($row["payprd"]){
			case 'w':
				$tyear = 52;
				break;
			case 'f':
				$tyear = 26;
				break;
			case 'd':
				$tyear = 260;
				break;
			case 'm':
			default:
				$tyear = 12;
				break;
		}
		
		/* determine possible gross salary */
		// first try it with this amount
		$initgross = $tsal = $row['salary'] - $row['totallow'] - $row['comm'] + $row['totded']
					+ $row['uif'] + $row['paye'] + $row['loanins'];
		$calc_paye = calculate_paye_271($tsal, $tyear, $age);
		
		// if paye greater than actual, try small start gross 
		if ($calc_paye > $row["paye"]) {
			$tsal = $row['salary'] - $row['totallow'] - $row['comm'] + $row['paye'];
			$calc_paye = calculate_paye_271($tsal, $tyear, $age);
			
			// if still over, start from 0
			if ($calc_paye > $row["paye"]) {
				$tsal = 0;
				$calc_paye = calculate_paye_271($tsal, $tyear, $age);
			}
		}
		
		// store the start of gross
		$gross = $tsal;
		
		// now loop until we find the paye salary
		$fail = false;
		while (sprint($calc_paye) < sprint($row["paye"])) {
			$tsal = sprint($tsal + 0.01);
			$calc_paye = calculate_paye_271($tsal, $tyear, $age);
			
			// print at every 10 rand
			if (round($tsal) == $tsal && $tsal % 10 == 0) {
				//print "--> trying $tsal with $calc_paye aiming at $row[paye]<br />";
			}
			
			if ($tsal > $initgross * 10) {
				$fail = true;
				break;
			}
		}

		if ($fail === true) {
			$tsal = $initgross;
		}
		
		$sql = "UPDATE cubit.$tbl SET taxed_sal='$tsal' WHERE id='$row[id]'";
		pge($sql);
	}
}

function tabiast_271($sal) {
	$ptables = array(
		/* percentage, extra, min, max */
		array(18, 0, 0, 100000.99),
		array(25, 18000, 100001, 160000.99),
		array(30, 33000, 160001, 220000.99),
		array(35, 51000, 220001, 300000.99),
		array(38, 79000, 300001, 400000.99),
		array(40, 117000, 400001, 999999999)
	);

	foreach ($ptables as $t) {
		if ($sal >= $t[2] && $sal <= $t[3]) {
			return $t;
		}
	}

	return false;
}

function calculate_paye_271($paye_salary, $tyear, $age) {
	// get PAYE bracket percantages
	if(($tables = tabiast_271($paye_salary * $tyear)) === false){
		fatal("The PAYE bracket for R $paye_salary does not exist.");
	} else {
		list($payeperc, $payex, $min, $max) = $tables;
	}

	// Get paye rebate
	$rebate = 7740;
	if ( $age >= 65 ) {
		$rebate += 4680;
	}

	if ( $min > 0 ) --$min;

	$paye = ($paye_salary * $tyear - $min) * $payeperc / 100;
	$paye = $paye + $payex - $rebate;

	if ( $paye < 0 ) {
		$paye = 0;
	}

	return sprint($paye/$tyear);
}

?>

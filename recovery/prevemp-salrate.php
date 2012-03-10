<?

require("../settings.php");
require("../salwages/emp-functions.php");

cFramework::run("select_emp");
cFramework::parse();

function select_emp(&$frm) {
	/* @var $frm cForm */
	$frm->setkey("entersal");
	$frm->settitle("Previous Employee Salary Information");
	
	$emps = qryEmployee();
	$emplist = new dbList($emps);
	$emplist->setFmt("#empnum", "#sname, #fnames");
	
	$frm->add_heading("Employee Details");
	$frm->add_select("Employee", "empnum", false, $emplist, "num", "1:10");
	return $frm->getfrm_input();
}

function entersal(&$frm) {
	/* @var $frm cForm */
	if ($frm->validate("entersal")) {
		return select_emp($frm);
	}
	
	$frm->setkey("writesal");
	
	extract($_POST);
	
	$empi = qryEmployee($empnum);
	
	$qry = new dbSelect("salpaid", "cubit", grp(
		m("cols", "month, week"),
		m("where", "empnum='$empnum' AND cyear='".EMP_YEAR."'"),
		m("order", "month, week"),
		m("group", "month, week")
	));
	//print $qry->sql;
	$qry->run();
	
	/* in case we did a correction, we run clean_fields to remove the 
		previous employee's fields */
	$frm->clean_fields("headers");
	$frm->clean_fields("hrs", true);
	$frm->clean_fields("sal", true);
	
	$frm->setcell(1, 2);
	$frm->add_layout("
		<tr>
			<th>Month</th>
			".($empi["payprd"] == "m" ? "" : "<th>Week/Day</th>")."
			<th>Basic Salary Rate<br />for Month</th>
			".($empi["saltyp"] != "h" ? "" : "<th>Hours Worked</th>")."
		</tr>", false, "headers"
	);
	
	while ($row = $qry->fetch_array()) {
		$sqry = new dbSelect("salpaid", "cubit", grp(
			m("where", "empnum='$empnum' AND cyear='".EMP_YEAR."'
						AND month='$row[month]' AND week='$row[week]'"),
			m("order", "true_ids DESC"),
			m("limit", "1")
		));
		$sqry->run();
		
		if ($sqry->num_rows() > 0) {
			$si = $sqry->fetch_array();
			
			/* hours field option */
			if ($empi["saltyp"] == "h") {
				$hrsopt = "<td>%fldonly</td>";
			} else {
				$hrsopt = "";
			}
			
			/* show week number */
			if ($empi["payprd"] == "m") {
				$weekdisp = "";
			} else {
				$weekdisp = "<td>$row[week]</td>";
			}
			
			$lay = "
			<tr %bg>
				<td>$row[month]</td>
				$weekdisp
				<td>%fldonly</td>
				$hrsopt
			</tr>";
			$frm->add_layout($lay);
			$frm->add_text("", "sal[$si[id]]", $si["salrate"], "float", "1:40", array(
				"size" => "7"
			));
			
			if ($empi["saltyp"] == "h") {
				$frm->add_text("", "hrs[$si[id]]", $si["hours"], "float", "1:40", array(
					"size" => "5"
				));
			}
		}
	}
	
	return $frm->getfrm_input();
}

function writesal($frm) {
	if ($frm->validate("writesal")) {
		return entersal($frm);
	}
	
	extract($_POST);
	
	$upd = new dbUpdate("salpaid", "cubit", false);
	
	foreach ($sal as $payid => $salrate) {
		$cols = grp(
			m("salrate", $salrate),
			isset($hrs[$payid]) ? m("hours", $hrs[$payid]) : false
		);
		
		$upd->setOpt($cols, "id='$payid'");
		$upd->run(DB_UPDATE);
	}
	
	$OUT = "
	<h3>Previous Employee Salary Information</h3>
	Successfully updated employee payslip information.";
	
	return $OUT;
}

?>
<?

require("../settings.php");

invalid_use("Script Disabled.");
exit;

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "select";
}

$frm = & new cForm();

switch ($_REQUEST["key"]) {
	case "write":
		$OUTPUT = write($frm);
		break;
	case "confirm":
		$OUTPUT = confirm($frm);
		break;
	case "select":
	default:
		$OUTPUT = select($frm);
		break;
}

$OUTPUT .= "<br /><br />".mkQuickLinks(
	ql("salaries-staff.php", "Process Employee Salary"),
	ql("settings-acc-edit.php", "General Settings"),
	ql("../admin-employee-add.php", "Add New Employee"),
	ql("../admin-employee-view.php", "View Employees")
);

parse();

function select($frm)
{

	extract($_REQUEST);

	if (!isset($emp_year)) {
		$emp_year = getCSetting("EMP_TAXYEAR");
	}

	/* @var $frm cForm */
	$frm->setkey("confirm");
	$frm->settitle("Select Active Employee Tax Year");
	$frm->setmsg("The employee's tax year will end on 28 February of the year you select below.<br />
		<li class='err'>Also note that, no matter which year is chosen below, the 2006/2007 PAYE
			tax tables will be used until current tax legislation is changed..</li>");

	$yrs = array();
	for ($i = 1990; $i < 2028; ++$i) {
		$yrs[$i] = $i;
	}

	$frm->add_heading("Select");
	$frm->add_select("Tax Year", "emp_year", $emp_year, $yrs, "num", "4:4");

	return $frm->getfrm_input();

}




function confirm($frm)
{

	/* @var $frm cForm */
	if ($frm->validate("confirm")) {
		return select($frm);
	}

	$frm->setkey("write");

	return $frm->getfrm_input();

}




function write($frm)
{

	if (isset($_REQUEST["btn_back"])) {
		return select($frm);
	}

	/* @var $frm cForm */
	if ($frm->validate("confirm")) {
		return confirm($frm);
	}

	$cols = grp(
		m("value", $_REQUEST["emp_year"])
	);

	$upd = new dbUpdate("settings", "cubit", $cols, "constant='EMP_TAXYEAR'");
	$upd->run(DB_UPDATE);

	$OUT = "
	<h3>Active Tax Year</h3>
	Successfully updated active Tax Year to $_REQUEST[emp_year]";

	return $OUT;

}



?>
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

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "recover":
			$OUTPUT = recover();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "rem":
			$OUTPUT = rem();
			break;
		default:
			$OUTPUT = slctcomp();
	}
} else {
	$OUTPUT = slctcomp();
}

$OUTPUT .= mkQuickLinks(
	ql("company-new.php", "Add New Company"),
	ql("company-view.php", "View Companies")
);

require("template.php");



function slctcomp ($err = "")
{

	db_con("cubit");

	# Get Companies
	$sql = "SELECT * FROM companies ORDER BY name ASC";
	$compRslt = db_exec ($sql) or die ("Unable to get companies from database.");
	if (pg_numrows ($compRslt) < 1) {
		header("Location: company-new.php");
	}
	
	$comps = "<select size='1' name='code'>";
	while ($comp = pg_fetch_array ($compRslt)) {
		$comps .= "<option value='$comp[code]'>$comp[name]</option>";
	}
	$comps .= "</select>";

	$slct = "
				<h3>Remove Company</h3>
				<form action=".SELF." method='POST'>
					<input type='hidden' name='key' value='confirm'>
				<table cellpadding='1' cellspacing='1'>
					<tr>
						<td colspan='2'>$err</td>
					</tr>
					<tr>
						<th colspan='2'>Select Company</th>
					</tr>
					<tr bgcolor='#77AAEE'>
						<td>Company Name</td>
						<td align='center'>$comps</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Remove &raquo;'></td>
					</tr>
				</form>
				</table>";
	return $slct;

}



function confirm()
{

	extract($_REQUEST);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($code,"string", 1, 5, "Invalid company code.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctcomp($confirm);
	}



	if (strtolower(COMP_DB) == strtolower($code)) {
		return "<li class='err'>You cannot delete the active company</li>";
	}

	# Change code to lowercase
	$code = strtolower($code);

	# Get Company Name
	db_con("cubit");
	$sql = "SELECT name,code FROM companies WHERE code = '$code'";
	$compRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	if(pg_numrows($compRslt) < 1){
		return "<li class=err> Invalid Company Code";
	}else{
		$comp = pg_fetch_array($compRslt);
	}

	$confirm = "
					<h3>Remove Company</h3>
					<h4>Are you sure you want to remove this company?</h4>
					<form action=".SELF." method='POST'>
						<input type='hidden' name='key' value='rem' />
						<input type='hidden' name='code' value='$code' />
						".(isset($perm) ? "<input type='hidden' name='perm' value='t' />" : "")."
					<table cellpadding='1' cellspacing='1'>
						<tr>
							<th colspan='2'>Details</th>
						</tr>
						<tr bgcolor='#77AAEE'>
							<td>Company Name</td>
							<td>$comp[name]</td>
						</tr>
						<tr bgcolor='#77AAEE'>
							<td>Company Code</td>
							<td>$comp[code]</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Remove &raquo;'></td>
						</tr>
					</form>
					</table>";
	return $confirm;

}



function rem()
{

	extract($_REQUEST);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($code,"string", 1, 5, "Invalid company code.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctcomp($confirm);
	}



	if (strtolower(COMP_DB) == strtolower($code)) {
		return "<li class='err'>You cannot delete the active company</li>";
	}

	# Change code to lowercase
	$code = strtolower($code);

	# Get Company Name
	db_con("cubit");
	$sql = "SELECT * FROM companies WHERE code = '$code'";
	$compRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	if(pg_numrows($compRslt) < 1){
		return "<li class='err'> Invalid Company Code.</li>";
	}else{
		$comp = pg_fetch_array($compRslt);
	}

	if ($comp["status"] == "removed" && isset($perm)) {

		$sql = "DROP DATABASE cubit_$code";
		$sql2 = db_exec($sql) or errDie("Error permanently deleting company.");

		$sql = "DELETE FROM companies WHERE code = '$code'";
		$delRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$date=date("Y-m-d");
		$Sl="INSERT INTO ch(comp,code,des,f,t,date) VALUES ('$comp[name]','$comp[code]','Company permanently deleted by ".USER_NAME."','$comp[ver]','$comp[ver]','$date')";
		$Ri=db_exec($Sl);
	} else {
		$sql = "UPDATE companies SET status='removed' WHERE code = '$code'";
		$delRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

		$date=date("Y-m-d");
		$Sl="INSERT INTO ch(comp,code,des,f,t,date) VALUES ('$comp[name]','$comp[code]','Company removed by ".USER_NAME."','$comp[ver]','$comp[ver]','$date')";
		$Ri=db_exec($Sl);
	}

	$rem = "
				<h3>Remove Company</h3>
				<h4><li> Company : $comp[name] has been successfully removed.</h4>";
	return $rem;

}



function recover()
{

	extract($_REQUEST);

	require_lib("validate");
	$v = new validate ();
	$v->isOk ($code,"string", 1, 5, "Invalid company code.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctcomp($confirm);
	}



	# Change code to lowercase
	$code = strtolower($code);

	# Get Company Name
	db_con("cubit");
	$sql = "SELECT * FROM companies WHERE code = '$code'";
	$compRslt = db_exec($sql) or errDie("Unable to access database.", SELF);
	if(pg_numrows($compRslt) < 1){
		return "<li class='err'> Invalid Company Code</li>";
	}else{
		$comp = pg_fetch_array($compRslt);
	}

	$sql = "UPDATE companies SET status='active' WHERE code = '$code'";
	$delRslt = db_exec($sql) or errDie("Unable to access database.", SELF);

	$date=date("Y-m-d");
	$Sl="INSERT INTO ch(comp,code,des,f,t,date) VALUES ('$comp[name]','$comp[code]','Recover by ".USER_NAME."','$comp[ver]','$comp[ver]','$date')";
	$Ri=db_exec($Sl);

	$rem = "
			<h3>Recover Company</h3>
			<h4><li> Company : $comp[name] has been successfully been recovered and marked as active.</h4>";
	return $rem;

}



function drop($code)
{
	$sql = "DROP DATABASE \"cubit_$code\";";
	$sql2 = db_exec($sql) or errDie("Error permanently deleting company.");
}


?>
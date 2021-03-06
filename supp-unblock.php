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

# Get settings
require ("settings.php");

# Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_GET['supid'])){
				$OUTPUT = ublock ($_GET['supid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET['supid'])){
		$OUTPUT = ublock ($_GET['supid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("template.php");




function ublock($supid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND ddiv = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li> Invalid supplier ID.</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
		# get vars
		foreach ($supp as $key => $value) {
			$$key = $value;
		}
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$deptname = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	$enter = "
		<h3>Unblock Supplier</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='supid' value='$supid'>
		<table cellpadding='0' cellspacing='0'>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Supplier Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Department</td>
							<td>$deptname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier No</td>
							<td>$supno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Name </td>
							<td>$supname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Number</td>
							<td>$vatnum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Address</td>
							<td>$supaddr</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Contact Name</td>
							<td>$contname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Tel No.</td>
							<td>$tel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Fax No.</td>
							<td>$fax</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E-mail</td>
							<td>$email</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Web Address</td>
							<td>http://$url</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						<tr class='".bg_class()."'>
							<th colspan='2'> Bank Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank </td>
							<td>$bankname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch</td>
							<td>$branname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td>$brancode</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Number</td>
							<td>$bankaccno</td>
						</tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Unblock &raquo;'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2' align='right'>
								<table border='0' cellpadding='2' cellspacing='1'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='supp-view.php'>View Suppliers</a></td>
									</tr>
									<tr class='".bg_class()."'>
										<td><a href='main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>";
	return $enter;

}




# Write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# connect to db
	db_connect();

	# Select
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND ddiv = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li> Invalid supplier ID.";
	}else{
		$supp = pg_fetch_array($suppRslt);
		# get vars
		foreach ($supp as $key => $value) {
			$$key = $value;
		}
	}

	# write to db
	$sql = "UPDATE suppliers SET blocked = 'no', div = ddiv, ddiv = 0 WHERE supid  = '$supid'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to Unblock supplier on the system.", SELF);
	if (pg_cmdtuples ($suppRslt) < 1) {
		return "<li class='err'>Unable to Unblock supplier in database.</li>";
	}

	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Supplier unblocked</th>
			</tr>
			<tr class='datacell'>
				<td>Supplier <b>$supname</b>, has been unblocked.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='supp-view.php'>View Suppliers</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}



?>
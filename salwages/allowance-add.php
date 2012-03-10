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

# get settings
require ("../settings.php");
require ("../core-settings.php");
require ("../libs/ext.lib.php");


# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmAllow ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeAllow ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterAllow ();
	}
} else {
	$OUTPUT = enterAllow ();
}

# display output
require ("../template.php");



# enter new data
function enterAllow ()
{

	# connect to db
	core_connect ();
	$allcat= "<select name='catid'>";
		$sql = "SELECT * FROM expenditure WHERE div = '".USER_DIV."'";
		$catRslt = db_exec($sql);
		if(pg_numrows($catRslt) < 1){
				return "<li> There are no Expenditure Accounts categories yet in Cubit.</li>";
		}else{
				while($cat = pg_fetch_array($catRslt)){
					if ( (! isset($expval)) || $cat["catname"] == "Expenditure" ) {
						$expval = $cat["catid"];
					}
					$allcat .= "<option value='$cat[catid]'>$cat[catname]</option>";
				}
		}
	$allcat .="</select>";

	$Tp = array("No"=>"No","Yes"=>"Yes");
	$taxables = extlib_cpsel("taxable", $Tp,"Yes");

	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, "Percentage");

	$enterAllow = "
					<h3>Add allowance to system</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm' />
						<input type='hidden' name='catid' value='$expval' />
						<tr>
							<th colspan='2'>Allowance Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Name of allowance</td>
							<td align='center'><input type='text' size='20' name='allowance'></td>
						</tr>
						<!--
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Category</td>
							<td align='center'>$allcat</td>
						</tr>
						//-->
						<tr bgcolor='".bgcolorg()."'>
							<td>Add Before PAYE</td>
							<td align='center'>$taxables</td>
						</tr>
						<input type='hidden' name='type' value='Amount'>
						<!--
						<tr bgcolor='".bgcolorg()."'>
							<td>Allowance Type</td>
							<td>$seltype</td>
						</tr>
						//-->
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
						</tr>
					</form>
					</table>
					<br>"
	.mkQuickLinks(
		ql("allowance-add.php","Add Allowance"),
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $enterAllow;

}



# confirm new data
function confirmAllow ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($allowance, "string", 1, 100, "Invalid allowance name.");
	$v->isOk ($catid, "string", 1, 20, "Invalid Category number.");
	$v->isOk ($taxable, "string", 1, 3, "Invalid taxablility option.");

	$v->isOk ($type, "string", 1, 15, "Invalid type.");

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
	core_connect ();
	$allacc= "<select name='accid'>";
		$sql = "SELECT * FROM accounts WHERE catid = '$catid' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$numrows = pg_numrows($accRslt);
		if(empty($numrows)){
				return "<li> There are no accounts under selected category.</li>
				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		}else{
				$prevtop = "";
				while($acc = pg_fetch_array($accRslt)){
				if(isb($acc['accid'])) {
					continue;
				}
						if ( $acc["topacc"] == $prevtop && $acc["accnum"] != "000" ) {
							$x = "&nbsp;&nbsp;-&nbsp;&nbsp;$acc[topacc]/$acc[accnum]";
						} else {
							$x = "$acc[topacc]/$acc[accnum]";
							$prevtop = $acc["topacc"];
						}

						$allacc .= "<option value='$acc[accid]'>$x $acc[accname]</option>";
				}
		}
	$allacc .="</select>";


	$confirmAllow = "
						<h3>Confirm new allowance</h3>
						<table ".TMPL_tblDflts.">
						<form action='".SELF."' method='POST'>
							<input type='hidden' name='key' value='write'>
							<input type='hidden' name='allowance' value='$allowance'>
							<input type='hidden' name='taxable' value='$taxable'>
							<input type='hidden' name='type' value='$type'>
							<tr>
								<th colspan='2'>Allowance Details</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Name of allowance</td>
								<td align='center'>$allowance</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Allowance Account</td>
								<td align='center'>$allacc</td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td>Add Before PAYE</td>
								<td align='center'>$taxable</td>
							</tr>
							<!--
							<tr bgcolor='".bgcolorg()."'>
								<td>Allowance Type</td>
								<td align='center'>$type</td>
							</tr>
							//-->
							<tr>
								<td colspan='2' align='right'><input type=submit value='Write &raquo;'></td>
							</tr>
						</form>
						</table>
						<br>"
	.mkQuickLinks(
		ql("allowance-add.php","Add Allowance"),
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmAllow;

}



# write new data
function writeAllow ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($allowance, "string", 1, 100, "Invalid allowance name.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($taxable, "string", 1, 3, "Invalid taxablility option.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");

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
	db_connect ();

	# write to db
	$sql = "INSERT INTO allowances (allowance, add, accid, type, div) VALUES ('$allowance', '$taxable', '$accid', '$type', '".USER_DIV."')";
	$allowRslt = db_exec ($sql) or errDie ("Unable to add allowance to database.", SELF);
	if (pg_cmdtuples ($allowRslt) < 1) {
		return "Unable to add allowance to database.";
	}

	$writeAllow = "
					<table ".TMPL_tblDflts." width='50%'>
						<tr>
							<th>Allowance added to system</th>
						</tr>
						<tr class='datacell'>
							<td>New allowance, $allowance, has been successfully added to Cubit.</td>
						</tr>
					</table>
					<br>"
	.mkQuickLinks(
		ql("allowance-add.php","Add Allowance"),
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeAllow;

}


?>
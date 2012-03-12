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

##
# admin-paye-add.php :: New PAYE bracket
##

# get settings
require ("settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("template.php");



# enter new paye bracket details
function enter ()
{
	$enter = "
				<h3>New Interest bracket</h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Minimum</td>
						<td align='center'>
							<table>
								<tr>
									<td>".CUR."</td>
									<td><input type='text' size='10' name='min' class='right'></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Maximum</td>
						<td align='center'>
							<table>
								<tr>
									<td>".CUR."</td>
									<td><input type='text' size='10' name='max' class='right'></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Percentage</td>
						<td align='center'>
							<table>
								<tr>
									<td><input type='text' size='10' name='percentage' class='right'></td>
									<td>%</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
					</tr>
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='#88BBFF'>
						<td><a href='intbrac-view.php'>View Interest Brackets</a></td>
					</tr>
					<tr bgcolor='#88BBFF'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $enter;

}



# Confirm new paye bracket details
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid interest percentage.");

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



	$confirm = "
					<h3>Confirm new Interest bracket</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='min' value='$min'>
						<input type='hidden' name='max' value='$max'>
						<input type='hidden' name='percentage' value='$percentage'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Minimum</td>
							<td align='right'>".CUR." $min</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Maximum</td>
							<td align='right'>".CUR." $max</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Percentage</td>
							<td align='right'>$percentage %</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td valign='left'><input type='submit' value='Write &raquo;'></td>
						</tr>
					</form>
					</table>
					<p>
					<table border=0 cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='#88BBFF'>
							<td><a href='intbrac-view.php'>View Interest Brackets</a></td>
						</tr>
						<tr bgcolor='#88BBFF'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $confirm;

}



# write new paye bracket
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($min, "num", 1, 9, "Invalid minimum amount.");
	$v->isOk ($max, "num", 1, 9, "Invalid maximum amount.");
	$v->isOk ($percentage, "float", 1, 6, "Invalid interest percentage.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"];
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_connect ();

	# add PAYE to db
	$sql = "INSERT INTO intbracs (min, max, percentage) VALUES ('$min', '$max', '$percentage')";
	$pRslt = db_exec ($sql) or errDie ("Unable to add Interest bracket to database.", SELF);

	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Interest bracket added to database</th>
					</tr>
					<tr class='datacell'>
						<td>New Interest bracket has been successfully added to Cubit.</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='#88BBFF'>
						<td><a href='intbrac-view.php'>View Interest Brackets</a></td>
					</tr>
					<tr bgcolor='#88BBFF'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</tr>";
	return $write;

}


?>
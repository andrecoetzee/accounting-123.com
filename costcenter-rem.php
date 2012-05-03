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
require("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			if (isset($_GET["ccid"])){
				$OUTPUT = confirm($_GET);
			} else {
				# Display default output
				$OUTPUT = "<li class=err>Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET["ccid"])){
		$OUTPUT = confirm($_GET);
	} else {
		# Display default output
		$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	}
}

# Get template
require("template.php");

# confirm
function confirm($_POST)
{

	# get vars
	extract ($_POST);


	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ccid, "num", 1, 20, "Invalid Cost center code.");
//	$v->isOk ($centercode, "string", 0, 50, "Invalid Cost center code.");
//	$v->isOk ($centername, "string", 1, 255, "Invalid Cost center name.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm."
					<p>
					<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
					<P>
					<table ".TMPL_tblDflts." width='100'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='costcenter-view.php'>View Cost Centers</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</form>
					</table>";
	}

	$flag = TRUE;
	#check if cost center has any trans
	for ($x=1;$x<=14;$x++){
		db_conn($x);
		$get_check = "SELECT * FROM cctran WHERE ccid = '$ccid' LIMIT 1";
		$run_check = db_exec($get_check) or errDie("Unable to get cost center information.");
		if(pg_numrows($run_check) > 0){
			$flag = FALSE;
		}
	}

	if($flag == FALSE)
		return "<li class='err'>Unable to remove cost center. Transactions Exist</li>";

	# Query server
	db_connect();

	$sql = "SELECT * FROM costcenters WHERE ccid = '$ccid'";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
	if (pg_numrows ($ccRslt) < 1) {
		return "<li class='err'>Invalid Cost Center.</li>";
	}
	$cc = pg_fetch_array ($ccRslt);
	extract ($cc);

	// Layout
	$confirm = "
			<h3>Edit Cost Center</h3>
			<h4>Confirm entry</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='ccid' value='$ccid'>
				<input type='hidden' name='centercode' value='$centercode'>
				<input type='hidden' name='centername' value='$centername'>
				<tr>
					<th width='40%'>Field</th>
					<th width='60%'>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Code</td>
					<td>$centercode</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Name</td>
					<td>$centername</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
					<td align='left'><input type='submit' value='Confirm &raquo'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts." width='100'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-view.php'>View Cost Centers</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

	return $confirm;
}

# write
function write($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ccid, "num", 1, 20, "Invalid Cost center code.");
	$v->isOk ($centercode, "string", 0, 50, "Invalid Cost center code.");
	$v->isOk ($centername, "string", 1, 255, "Invalid Cost center name.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]<li>";
		}
		$confirm .= "
				<p>
				<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
				<P>
				<table ".TMPL_tblDflts." width='100'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='costcenter-view.php'>View Cost Centers</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</form>
				</table>";

		return $confirm;
	}

	// Insert cost centers
	db_connect();
	$sql = "DELETE FROM costcenters WHERE ccid = '$ccid'";
	$rslt = db_exec($sql) or errDie("Unable to remove stock cost center.",SELF);

	$write = "
			<table ".TMPL_tblDflts." width='50%'>
				<tr>
					<th>Cost Center removed</th>
				</tr>
				<tr class='datacell'>
					<td>Cost Center, $centername ($centercode) has been successfully removed.</td>
				</tr>
			</table>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-view.php'>View Cost Centers</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-add.php'>Add Cost Center</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

	return $write;
}
?>

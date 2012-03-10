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


if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctDep ();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slctDep();
}

require ("template.php");




# Default view
function slctDep()
{

	# Check if account creation axists
	$sql = "SELECT label,value FROM set WHERE label = 'ACCNEW_LNK'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$set = pg_fetch_array($Rslt);
		if($set['value'] == 'acc-new.php'){
			$sets = "
			<center>
			<table ".TMPL_tblDflts.">
				<tr>
					<td><li class='err'>Default Accounts cannot be created</td>
				</tr>
				".TBL_BR."
				<tr>
					<th>Note : </th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Please select \"User selected account numbers\" on admin settings for default accounts to be created.<b></td>
				</tr>
				".TBL_BR."
			</table>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
			return $sets;

		}
	}else{
		$sets = "
		<center>
		<table ".TMPL_tblDflts.">
			<tr>
				<td><li class='err'>Account Creation not set</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Note : </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Please Set account  creation to \"User selected account numbers\" on admin settings.<b></td>
			</tr>
			".TBL_BR."
		</table>
    	<p>
		<table ".TMPL_tblDflts." width='15%'>
        	".TBL_BR."
        	<tr>
        		<th>Quick Links</th>
        	</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $sets;

	}

	# Check if setting exists
	$sql = "SELECT label,value FROM set WHERE label = 'DEF_ACC' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$set = pg_fetch_array($Rslt);
		$sets = "
		<center>
		<table ".TMPL_tblDflts.">
			<tr><td><li class='err'> Default Account can only be set once</td></tr>
			".TBL_BR."
			<tr>
				<th>Note : </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Default Accounts Have already been set to: <b>$set[value]<b></td>
			</tr>
			".TBL_BR."
		</table>
    	<p>
		<table ".TMPL_tblDflts." width='15%'>
        	".TBL_BR."
        	<tr>
        		<th>Quick Links</th>
        	</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $sets;

	}

	# Check if any accounts exists
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accnum != '999' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing accounts.");
	if (pg_numrows ($Rslt) > 0) {
		$acc = "
		<center>
		<table ".TMPL_tblDflts.">
			<tr>
				<td><li class='err'>ERROR : There are already accounts in Cubit</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Note : </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Default Accounts can only be set on a new cubit installation or a new branch.</td>
			</tr>
			".TBL_BR."
		</table>
    	<p>
		<table ".TMPL_tblDflts." width='15%'>
        	".TBL_BR."
        	<tr>
        		<th>Quick Links</th>
        	</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $acc;

	}


	# Set up table to display in
	$printDep = "
					<h3>Select Company Type For Default Accounts</h3>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='confirm'>
					<table ".TMPL_tblDflts."'>
						<tr>
							<th>Company Types</th>
						</tr>";

	# connect to database
	core_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM defdep ORDER BY depname ASC";
    $depRslt = db_exec ($sql) or errDie ("Unable to retrieve default company types from database.");
	if (pg_numrows ($depRslt) < 1) {
		return "<li>There are default company types in Cubit.</li>";
	}
	$printDep .= "<tr bgcolor='".bgcolorg()."'><td><select name='depid' size='5'>";

	while ($dep = pg_fetch_array ($depRslt)) {
		# get number of accounts
		$sql = "SELECT count(accname) FROM defacc WHERE depid = '$dep[depid]'";
		$cRslt = db_exec($sql);
		$count = pg_fetch_array($cRslt);

		# view in a select mode
		$printDep .= "<option value='$dep[depid]'>$dep[depname] ($count[count])</option>";
		$i++;
	}

	$printDep .= "
								</select>
							</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'><input type='submit' value='Continue &raquo'></td>
						</tr>
					</table>
					</form>
				    <p>
					<table ".TMPL_tblDflts." width='15%'>
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $printDep;

}



# show stock
function confirm ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($depid, "num", 1, 50, "Invalid Department ID.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}


	# Set up table to display in
	$confirm = "
				<center>
				<h3>Company Type Default Accounts</h3>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='depid' value='$depid'>
				<table ".TMPL_tblDflts.">
					<tr>
						<td align='center'><h4>Income Accounts</h4></td>
						<td align='center'><h4>Expenditure Accounts</h4></td>
						<td align='center'><h4>Balance Sheet Accounts</h4></td>
					</tr>
					<tr>
						<td valign='top'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th>Account Number</th>
									<th>Account Name</th>
								</tr>";

		# connect to database
		core_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_INC." AND topacc <= ".MAX_INC." ORDER BY topacc, accnum ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no stock items in the selected category.</li>";
		}
		while ($acc = pg_fetch_array ($accRslt)) {

			$confirm .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$acc[topacc]/$acc[accnum]</td>
								<td>$acc[accname]</td>
							</tr>";
			$i++;
		}

		$confirm .= "
							</table>
						</td>
						<td valign='top'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th>Account Number</th>
									<th>Account Name</th>
								</tr>";

		# connect to database
		core_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_EXP." AND topacc <= ".MAX_EXP." ORDER BY topacc, accnum ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no stock items in the selected category.";
		}
		while ($acc = pg_fetch_array ($accRslt)) {

			$confirm .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$acc[topacc]/$acc[accnum]</td>
								<td>$acc[accname]</td>
							</tr>";
			$i++;
		}

		$confirm .= "
							</table>
						</td>
						<td valign=top>
							<table '".TMPL_tblDflts."'>
								<tr>
									<th>Account Number</th>
									<th>Account Name</th>
								</tr>";

		# connect to database
		core_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_BAL." AND topacc <= ".MAX_BAL." ORDER BY topacc, accnum ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no stock items in the selected category.</li>";
		}
		while ($acc = pg_fetch_array ($accRslt)) {

			$confirm .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$acc[topacc]/$acc[accnum]</td>
								<td>$acc[accname]</td>
							</tr>";
			$i++;
		}

		$confirm .= "
									</table>
								</td>
							</tr>
							<tr>
								<td></td>
								<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
								<td><input type='submit' value='Continue &raquo'></td>
							</tr>
						</table>
						</form>
						<p>
						<table ".TMPL_tblDflts." width='15%'>
							<tr><td><br></td></tr>
							<tr>
								<th>Quick Links</th>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>";
	return $confirm;

}



function write ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($depid, "num", 1, 50, "Invalid Department ID.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}




	# begin sql transaction
	core_connect();
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Create income accounts
		# Create a Default Category
		$seq = "Income_seq";
        # Write to db
        $sql = "INSERT INTO income (catid, catname, div) VALUES ('I' || nextval('$seq'), 'INCOME', '".USER_DIV."')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Category to Database.");

		# get last inserted id for new cat
		$inccatid = pglib_getlastid ("income_seq");
		$inccatid = "I".$inccatid;

		# Query server for income account
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_INC." AND topacc <= ".MAX_INC." ORDER BY topacc, accnum ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no income accounts.</li>";
		}

		while ($acc = pg_fetch_array ($accRslt)) {
			if(create($acc['topacc'], $acc['accnum'], $acc['accname'], $inccatid, "I", "f") > 0){
				pglib_transaction ("ROLLBACK");
				return "<li> Failed To return accounts ($acc[topacc], $acc[accnum], $acc[accname]).";
			}
			$i++;
		}

		// Create Expenditure accounts
		# Create a Default Category
		$seq = "expenditure_seq";
        # Write to db
        $sql = "INSERT INTO expenditure (catid, catname, div) VALUES ('E' || nextval('$seq'), 'EXPENDITURE', '".USER_DIV."')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Category to Database.");

		# get last inserted id for new cat
		$expcatid = pglib_getlastid ("expenditure_seq");
		$expcatid = "E".$expcatid;

		# Query server for income account
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_EXP." AND topacc <= ".MAX_EXP." ORDER BY topacc, accnum ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no income accounts.</li>";
		}

		while ($acc = pg_fetch_array ($accRslt)) {
			if(create($acc['topacc'], $acc['accnum'], $acc['accname'], $expcatid, "E", "f") > 0){
				pglib_transaction ("ROLLBACK");
				return "<li> Failed To return accounts ($acc[topacc], $acc[accnum], $acc[accname]).";
			}
			$i++;
		}

		// Create Balance accounts
		# Create a Default Category
		$seq = "balance_seq";
        # Write to db
        $sql = "INSERT INTO balance (catid, catname, div) VALUES ('B' || nextval('$seq'), 'BALANCE', '".USER_DIV."')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Category to Database.");

		# get last inserted id for new cat
		$balcatid = pglib_getlastid ("balance_seq");
		$balcatid = "B".$balcatid;

		# Query server for income account
		$i = 0;
		$sql = "SELECT * FROM defacc WHERE depid = '$depid' AND topacc >= ".MIN_BAL." AND topacc <= ".MAX_BAL." ORDER BY topacc, accnum ASC";
		$accRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($accRslt) < 1) {
			return "<li>There are no income accounts.";
		}

		while ($acc = pg_fetch_array ($accRslt)) {
			if(create($acc['topacc'], $acc['accnum'], $acc['accname'], $balcatid, "B", "f") > 0){
				pglib_transaction ("ROLLBACK");
				return "<li> Failed To return accounts ($acc[topacc], $acc[accnum], $acc[accname]).";
			}
			$i++;
		}
	# commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to start a database transaction.",SELF);

	# Query server
	core_connect();
	$sql = "SELECT * FROM defdep WHERE depid = '$depid'";
	$depRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($depRslt) < 1) {
		return "<li> Invalid Company Type ID.</li>";
	}
	$dep = pg_fetch_array($depRslt);

	# Block setting
	db_connect();
	$sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Default accounts', 'DEF_ACC', '$dep[depname]', 'Default accounts', '".USER_DIV."')";
	$setRslt = db_exec ($sql) or errDie ("Unable to set settings in Cubit.");

	return "
	<center>
	<h3>Company Type Default Accounts</h3>
	<table ".TMPL_tblDflts.">
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><li>All accounts have been created</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts." width='15%'>
        <tr><td><br></td></tr>
        <tr>
        	<th>Quick Links</th>
        </tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

}

// Creating an account
function create($topacc, $accnum, $accname, $catid, $acctype, $vat)
{

	# Check Account name on selected type and category
	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND div = '".USER_DIV."'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 1;
	}

	$sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '".USER_DIV."'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 2;
	}

	# Write to DB
	$Sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('$topacc', '$accnum', '$accname', '$acctype', '$catid', '$vat', '".USER_DIV."')";
	$accRslt = db_exec ($Sql) or errDie ("Unable to add Account to Database.", SELF);

	# Get last inserted id for new acc
	$accid = pglib_lastid ("accounts", "accid");

	# Insert account into trial Balance
	$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '$topacc', '$accnum', '$accname', '$vat', '".USER_DIV."')";
	$trialRslt = db_exec($query);

	# return Zero on success
	return 0;

}


?>
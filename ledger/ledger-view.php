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
## get settings

require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "viewLedg":
			$OUTPUT = viewLedg($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = view();
	}
} else {
	# Display default output
	$OUTPUT = view();
}

# Get templete
require("../template.php");



# Default view
function view()
{
	//layout
	$view = "
		<h3>View High Speed Input Ledgers</h3>
		<table cellpadding='5'>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='viewLedg'>
						<input type='hidden' name='search' value='lname'>
						<tr>
							<th colspan='2'>Search By Name</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><input type='text' size='20' name='lname'></td>
							<td rowspan='2' valign='bottom'><input type='submit' value='Search'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='viewLedg'>
						<input type='hidden' name='search' value='all'>
				  		<tr>
				  			<th>View All</th>
				  		</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td align='center'><input type='submit' value='View All'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-new.php'>New High Speed Input Ledger</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $view;

}



# View Categories
function viewLedg($HTTP_POST_VARS)
{

    # get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	# Search by refnum
	if($search == "lname"){
		$v->isOk ($lname, "string", 1, 255, "Invalid Search String.");
		$lname = strtolower($lname);

		# Create the Search SQL
		$search = "SELECT * FROM in_ledgers WHERE lower(lname) LIKE '%$lname%' AND div = '".USER_DIV."' ORDER BY lname ASC";
	}

	# View all
	if($search == "all"){
		# create the Search SQL
		$search = "SELECT * FROM in_ledgers WHERE div = '".USER_DIV."' ORDER BY lname ASC";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	core_connect();
	// Layout
	$viewLedg = "
		<center>
		<h3>View High Speed Input Ledgers</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Ledger Name</th>
				<th>Debit</th>
				<th>Credit</th>
				<th colspan='10'>Options</th>
			</tr>";

	$ledRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve High Speed Input ledgers from database.", SELF);
	if (pg_numrows ($ledRslt) < 1) {
	return "
		<li> No High Speed input ledgers found.<br><br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-new.php'>New High Speed Input Ledger</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	}

	# display all transaction
	$i = 0;
	while ($led = pg_fetch_array ($ledRslt)){
		#get vars from tran as the are in db
		foreach ($led as $key => $value) {
			$$key = $value;
		}

		# get account names
		$deb = get("core","accname, topacc, accnum","accounts","accid",$dtaccid);
		$debacc = pg_fetch_array($deb);
		$ct = get("core","accname, topacc,accnum","accounts","accid",$ctaccid);
		$ctacc = pg_fetch_array($ct);

		$viewLedg .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='ledger-run.php?ledgid=$ledgid'>$led[lname]</a></td>
				<td>$debacc[topacc]/$debacc[accnum] - $debacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
				<td><a href='ledger-det.php?ledgid=$ledgid'>View Details</a></td>
				<td>&nbsp;&nbsp;<a href='ledger-run.php?ledgid=$ledgid'>Run</a>&nbsp;&nbsp;</td>
				<td><a href='ledger-edit.php?ledgid=$ledgid'>Edit</a></td>
				<td><a href='ledger-rem.php?ledgid=$ledgid'>Delete</a></td>
			</tr>";
		$i++;
	}

	$viewLedg .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-new.php'>New High Speed Input Ledger</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='ledger-view.php'>View High Speed Input Ledger</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $viewLedg;

}


?>
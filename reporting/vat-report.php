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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			if(isset($_POST["amt"])){
				$OUTPUT = viewRepAmt($_POST);
				break;
			}elseif(isset($_POST["inp"])){
				$OUTPUT = viewRepIn($_POST);
				break;
			}elseif(isset($_POST["out"])){
				$OUTPUT = viewRepOut($_POST);
				break;
			}elseif(isset($_POST["srchout"])){
				$OUTPUT = viewOut($_POST);
				break;
			}elseif(isset($_POST["srchin"])){
				$OUTPUT = viewIn($_POST);
				break;
			}else{
				$OUTPUT = viewRep($_POST);
				break;
			}
			break;
		default:
			$OUTPUT = view();
		}
} else {
	$OUTPUT = view();
}

# get templete
require("../template.php");



# Default view
function view()
{

	// Layout
	$view = "
				<h3>Vat Report<h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='view'>
					<tr>
						<th colspan='2'>Transactions By Date Range</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center' colspan='2'>
							".mkDateSelect("from",date("Y"),date("m"),"01")."
							&nbsp;TO&nbsp;
							".mkDateSelect("to")."
						</td>
					</tr>
					".TBL_BR."
					<tr>
						<th colspan='2'>Vat Balance</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><input type='submit' name='amt' value='View Amount'></td>
						<td align='center'><input type='submit' name='srch' value='Search by Date'></td>
					</tr>
					".TBL_BR."
					<tr>
						<th colspan='2'>VAT Input</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><input type='submit' name='inp' value='View Amount'></td>
						<td align='center'><input type='submit' name='srchin' value='Search by Date'></td>
					</tr>
					".TBL_BR."
					<tr>
						<th colspan='2'>VAT Output</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><input type='submit' name='out' value='View Amount'></td>
						<td align='center'><input type='submit' name='srchout' value='Search by Date'></td>
					</tr>
					".TBL_BR."
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr><th>Quick Links</th></tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
	return $view;

}



# Default view
function viewRep($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;
		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

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
	$printRep = "
					<h3>Vat Report: $fromdate TO  $todate</h3>
					<table ".TMPL_tblDflts." width='70%'>
						<tr>
							<th>Date</th>
							<th>Reference</th>
							<th>Amount</th>
							<th>Description</th>
						</tr>
				";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND div = '".USER_DIV."' ORDER BY edate DESC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	if (pg_numrows ($vatRslt) < 1) {
		$vattot = 0;
		$printRep .= "
						<tr class='".bg_class()."'>
							<td colspan='10'><li>No previous vat Transactions.</li></td>
						</tr>
					";
	}else{
		# connect to database
		db_connect ();
		$vatout = 0;
		$vatin = 0;
		$vattot = 0;
		while($vat = pg_fetch_array($vatRslt)){

			# format date
			$vat['edate'] = explode("-", $vat['edate']);
			$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

			$vattot += $vat['amount'];

			$vat['amount'] = sprint($vat['amount']);
			$printRep .= "
							<tr class='".bg_class()."'>
								<td>$vat[edate]</td>
								<td>$vat[ref]</td>
								<td>".CUR." $vat[amount]</td>
								<td>$vat[descript]</td>
							</tr>";
			$i++;
		}
	}

	$vattot = sprint($vattot);

	// Layout
	$printRep .= "
					<tr class='".bg_class()."'>
						<td colspan='2'><b>Total vat balance</b></td>
						<td colspan='2' align='right'><b>".CUR." $vattot</b></td>
					</tr>
					".TBL_BR."
					<tr>
						<td align='center' colspan='10'>
							<form action='../xls/vat-report-xls.php' method='POST' name='form'>
								<input type='hidden' name='key' value='view'>
								<input type='hidden' name='from_day' value='$from_day'>
								<input type='hidden' name='from_month' value='$from_month'>
								<input type='hidden' name='from_year' value='$from_year'>
								<input type='hidden' name='to_day' value='$to_day'>
								<input type='hidden' name='to_month' value='$to_month'>
								<input type='hidden' name='to_year' value='$to_year'>
								<input type='submit' name='xls' value='Export to spreadsheet'>
							</form>
						</td>
					</tr>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $printRep;

}



# Default view
function viewRepAmt($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;
		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	# connect to database
	db_connect ();

	# Get negetive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE chrgvat='VATIN' AND div = '".USER_DIV."' AND edate >= '$fromdate' AND edate <= '$todate'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$pvat = pg_fetch_array($vatRslt);

	# Get positive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE chrgvat='VATOUT' AND div = '".USER_DIV."' AND edate >= '$fromdate' AND edate <= '$todate'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$rvat = pg_fetch_array($vatRslt);

	$rvat['sum'] = sprint($rvat['sum']);
	$pvat['sum'] = sprint($pvat['sum'] * (-1));

	$totbal = sprint($rvat['sum'] - $pvat['sum']);

	# Set up table to display in
	$printRep = "
					<h3>Vat Report: $fromdate TO  $todate</h3>
					<table ".TMPL_tblDflts." width='300'>
						<tr>
							<th colspan='2'>Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Total Input Vat</td>
							<td>".CUR." $pvat[sum]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Total Output Vat</td>
							<td>".CUR." $rvat[sum]</td>
						</tr>
						".TBL_BR."
						<tr class='".bg_class()."'>
							<td><b>Total Vat Balance</b></td>
							<td><b>".CUR." $totbal</b></td>
						</tr>
						".TBL_BR."
						<tr>
							<td align='center' colspan='10'>
								<form action='../xls/vat-report-xls.php' method='POST' name='form'>
									<input type='hidden' name='key' value='view'>
									<input type='hidden' name='amt' value=' '>
									<input type='hidden' name='from_day' value='$from_day'>
									<input type='hidden' name='from_month' value='$from_month'>
									<input type='hidden' name='from_year' value='$from_year'>
									<input type='hidden' name='to_day' value='$to_day'>
									<input type='hidden' name='to_month' value='$to_month'>
									<input type='hidden' name='to_year' value='$to_year'>
									<input type='submit' name='xls' value='Export to spreadsheet'>
								</form>
							</td>
						</tr>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $printRep;

}



# Vat input transactions
function viewIn($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;
		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

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
	$printRep = "
					<h3>Vat Report - Input: $fromdate TO $todate</h3>
					<table ".TMPL_tblDflts." width='70%'>
						<tr>
							<th>Date</th>
							<th>Reference</th>
							<th>Amount</th>
							<th>Description</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND chrgvat='VATIN' AND div = '".USER_DIV."' ORDER BY edate DESC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	if (pg_numrows ($vatRslt) < 1) {
		$vattot = 0;
		$printRep .= "
						<tr class='".bg_class()."'>
							<td colspan='10'><li>No previous vat Transactions.</li></td>
						</tr>
					";
	}else{
		# connect to database
		db_connect ();
		$vatout = 0;
		$vatin = 0;
		$vattot = 0;
		while($vat = pg_fetch_array($vatRslt)){
			$vat['amount']=$vat['amount']*-1;

			# format date
			$vat['edate'] = explode("-", $vat['edate']);
			$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

			$vattot += $vat['amount'];

			$vat['amount'] = sprint($vat['amount']);
			$printRep .= "
							<tr class='".bg_class()."'>
								<td>$vat[edate]</td>
								<td>$vat[ref]</td>
								<td>".CUR." $vat[amount]</td>
								<td>$vat[descript]</td>
							</tr>
						";
			$i++;
		}
	}

	$vattot = sprint($vattot);

	// Layout
	$printRep .= "
						<tr class='".bg_class()."'>
							<td colspan='2'><b>Total vat Input</b></td>
							<td colspan='2' align='right'><b>".CUR." $vattot</b></td>
						</tr>
						".TBL_BR."
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $printRep;

}



# Default view
function viewRepIn($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;
		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	# connect to database
	db_connect ();

	# Get negetive vat amounts
	$sql = "SELECT sum(amount*-1) FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND chrgvat='VATIN' AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$pvat = pg_fetch_array($vatRslt);

	$pvat['sum'] = sprint($pvat['sum']);

	$totbal = sprint($pvat['sum']);

	# Set up table to display in
	$printRep = "
					<h3>Vat Report: $fromdate TO $todate</h3>
					<table ".TMPL_tblDflts." width='300'>
						<tr>
							<th colspan='2'>Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Total VAT Input</td>
							<td>".CUR." $pvat[sum]</td>
						</tr>
						".TBL_BR."
					</table>
					<p>
					<table ".TMPL_tblDflts."'>
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				";
	return $printRep;

}



# Vat output transactions
function viewOut($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;
		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

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
	$printRep = "
					<h3>Vat Report - Output: $fromdate TO $todate</h3>
					<table ".TMPL_tblDflts." width='70%'>
						<tr>
							<th>Date</th>
							<th>Reference</th>
							<th>Amount</th>
							<th>Description</th>
						</tr>
				";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND chrgvat='VATOUT' AND div = '".USER_DIV."' ORDER BY edate DESC";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	if (pg_numrows ($vatRslt) < 1) {
		$vattot = 0;
		$printRep .= "
						<tr class='".bg_class()."'>
							<td colspan='10'><li>No previous vat Transactions.</li></td>
						</tr>
					";
	}else{
		# connect to database
		db_connect ();
		$vatout = 0;
		$vatin = 0;
		$vattot = 0;
		while($vat = pg_fetch_array($vatRslt)){

			# format date
			$vat['edate'] = explode("-", $vat['edate']);
			$vat['edate'] = $vat['edate'][2]."-".$vat['edate'][1]."-".$vat['edate'][0];

			$vattot += $vat['amount'];

			$vat['amount'] = sprint($vat['amount']);
			$printRep .= "
							<tr class='".bg_class()."'>
								<td>$vat[edate]</td>
								<td>$vat[ref]</td>
								<td>".CUR." $vat[amount]</td>
								<td>$vat[descript]</td>
							</tr>
						";
			$i++;
		}
	}

	$vattot = sprint($vattot);

	// Layout
	$printRep .= "
						<tr class='".bg_class()."'>
							<td colspan='2'><b>Total vat Output</b></td>
							<td colspan='2' align='right'><b>".CUR." $vattot</b></td>
						</tr>
						".TBL_BR."
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				";
	return $printRep;

}

# Default view
function viewRepOut($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;
		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	# connect to database
	db_connect ();

	# Get positive vat amounts
	$sql = "SELECT sum(amount) FROM vatrec WHERE edate >= '$fromdate' AND edate <= '$todate' AND chrgvat='VATOUT' AND div = '".USER_DIV."'";
	$vatRslt = db_exec ($sql) or errDie ("Unable to retrieve vat records from database.");
	$rvat = pg_fetch_array($vatRslt);

	$rvat['sum'] = sprint($rvat['sum']);

	$totbal = sprint($rvat['sum']);

	# Set up table to display in
	$printRep = "
					<h3>Vat Report: $fromdate TO $todate</h3>
					<table ".TMPL_tblDflts." width='300'>
						<tr>
							<th colspan='2'>Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Total VAT Output</td>
							<td>".CUR." $rvat[sum]</td>
						</tr>
						".TBL_BR."
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				";
	return $printRep;

}


?>
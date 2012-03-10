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

require ("settings.php");          // Get global variables & functions

// show current stock
printyr();

/*
 * Functions
 *
 */

// Prints a form to enter new stock details into

function printyr()
{
	// Set up table to display in
	// Connect to database
	core_Connect ();
	$sql = "SELECT * FROM year ORDER BY yrname";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to get Financial year details from database.", SELF);
	$numrows = pg_numrows ($Rslt);
	if ($numrows < 1) {
		$OUTPUT = "There are no Financial years defined in Cubit.";
		require ("template.php");
	}

	# display all Accounts
	$yrs = "";
	for ($i=0; $i < $numrows; $i++) {
		$yr = pg_fetch_array ($Rslt, $i);

		if (!isset($thisyear) && $yr["closed"] == "n") {
			$thisyear = $yr["yrname"];
		}

		# alternate bgcolor
		$bgColor = bgcolorc($i);

		if (PRD_STATE != "py" && $yr["closed"] == "y") {
			$status = "Closed";
		} else if (PRD_STATE == "py" && $yr["yrname"] == YR_NAME) {
			$status = "Closed (In Use)";
		} else if ((PRD_STATE != "py" && $yr["yrname"] == YR_NAME)
				|| (PRD_STATE == "py" && $yr["yrname"] == PYR_NAME)) {
			$status = "Current";
		} else {
			$status = "";
		}

		$yrs .= "
		<tr bgcolor='$bgColor'>
			<td>$yr[yrname]</td>
			<td align='right'>$yr[yrdb]</td>
			<td align='center'>$status</td>
		</tr>\n";
	}

	if (!isset($thisyear)) {
		$thisyear = "All financial years have been closed.";
	} else {
		$thisyear = substr($thisyear, 1);
	}

	global $PRDMON, $MONPRD;
	$pmon = 0;
	$fyear = getFinYear() - (int)($PRDMON[1] > 1);

	$prddesc = array();
	for ($i = 1; $i <= 12; $i++) {
		$mon = $PRDMON[$i];

		if ($mon < $pmon) {
			++$fyear;
		}
		$pmon = $mon;

		if ($i == 1 || $i == 12) {
			$prddesc[] = getMonthName($mon)." $fyear";
		}
	}

	$prddesc = implode(" to ", $prddesc);

	$OUTPUT = "
	<h3>Financial Years</h3>
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan='3'>Current Financial Year: $thisyear</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<th colspan='3'>Period Range: $prddesc</td>
	</tr>
	<tr>
		<th>Year Name</th>
		<th>Year Database</th>
		<th>Status</th>
	</tr>
	$yrs
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a href='finyearnames-new.php'>Set Financial Year</td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	// all template to display the info and die
	require ("template.php");
}

?>

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
		case "viewinv":
		$OUTPUT = printInv($HTTP_POST_VARS);
		break;
	case "viewtemp":
		$OUTPUT = printtemp($HTTP_POST_VARS);
		break;
	default:
		$OUTPUT = slct();
	}
} else {
        # Display default output
        $OUTPUT = slct();
}

require ("template.php");



# Default view
function slct()
{

	//layout
	$slct = "
				<h3>View Temp/Invoice number<h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='viewinv'>
					<tr>
						<th colspan='2'>Find Invoice Number(input temp num)</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='text' size='8' name='temp' value=''></td>
						<td><input type='submit' value='Find'></td>
					</tr>
				</form>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='viewtemp'>
					<tr>
						<th colspan='2'>Find Temp Number(input inv num)</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='text' size='8' name='invnum' value=''></td>
						<td><input type='submit' value='Find'></td>
					</tr>
				</form>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>
			";
	return $slct;

}



# show invoices
function printInv ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($temp, "num", 1,10, "Invalid temp invoice num.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
	    return $confirm."</li>".slct();
	}



		# Set up table to display in
		$printInv = "
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Temp Num</th>
								<th>Invoice No.</th>
							</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM ncsrec WHERE oldnum = '$temp' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
		if (pg_numrows ($invRslt) < 1) {
			$printInv = "<li> No Outstanding Invoices found.";
		}else{
			while ($inv = pg_fetch_array ($invRslt)) {
				# alternate bgcolor
				$printInv .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$inv[oldnum]</td>
									<td>$inv[newnum]</td>
								</tr>";
				$i++;
			}
		}

	$printInv .= "
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td><a href='main.php'>Main Menu</td>
						</tr>
					</table>";
	return $printInv;

}



# show invoices
function printtemp ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($invnum, "num", 1,10, "Invalid invoice num.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
	    return $confirm.slct();
	}



	# Set up table to display in
	$printInv = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Temp Num</th>
							<th>Invoice No.</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$sql = "SELECT * FROM ncsrec WHERE newnum = '$invnum' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($invRslt) < 1) {
		$printInv = "<li> No Outstanding Invoices found.";
	}else{
		while ($inv = pg_fetch_array ($invRslt)) {
			$printInv .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$inv[oldnum]</td>
								<td>$inv[newnum]</td>
							</tr>
						";
			$i++;
		}
	}

	$printInv .= "
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td><a href='main.php'>Main Menu</td>
						</tr>
					</table>
				";
	return $printInv;

}


?>
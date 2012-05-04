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

require ("../settings.php");
require_lib("ext");
require_lib("validate");

$OUTPUT = export();

require ("../template.php");




function printCust ()
{

	global $_SESSION;
	extract($_REQUEST);

	if ( ! isset($action) ) $action = "listcust";


	$sqlfilter = "";
	$printCust_begin = "<h2>View Customers</h2>";
	$ajaxCust = "";


	$ajaxCust .= "
	<form action='statements-email.php' method='get'>
	<input type='hidden' name='key' value='confirm' />";

	if (!isset($offset) && isset($_SESSION["offset"])) {
		$offset = $_SESSION["offset"];
	} else if (!isset($offset)) {
		$offset = 0;
	}

	$_SESSION["offset"] = $offset;

	# connect to database
	db_connect();

	# counting the number of possible entries
	$sql = "SELECT * FROM customers
    		WHERE (div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') $sqlfilter
    		ORDER BY surname ASC";
	$rslt = db_exec($sql) or errDie("Error counting matching customers.");
	$custcount = pg_num_rows($rslt);

	# Query server
	$tot = 0;
	$totoverd = 0;
	$i = 0;

	
	$ajaxCust .= "
	<table ".TMPL_tblDflts.">

	<tr>
		<th>Acc no.</th>
		<th>Company/Name</th>
		<th>Tel</th>
		<th>Category</th>
		<th>Class</th>
		<th colspan='2'>Balance</th>
		<th>Overdue</th>
	</tr>";

	/* query object for cashbook */
	$cashbook = new dbSelect("cashbook", "cubit");

	$custRslt = new dbSelect("customers", "cubit", grp(
		m("where", "(div ='".USER_DIV."' or ddiv='".USER_DIV."') $sqlfilter"),
		m("order", "surname ASC"),
		m("offset", $offset),
		m("limit", 100)
	));
	$custRslt->run();

	if ($custRslt->num_rows() < 1) {
		$ajaxCust .= "
		<tr class='".bg_class()."'>
			<td colspan='20'><li>There are no Customers matching the criteria entered.</li></td>
		</tr>";
	}else{
		while ($cust = $custRslt->fetch_array()) {

			if (!user_in_team($cust["team_id"], USER_ID)) {
				continue;
			}

			# Check type of age analisys
			if(div_isset("DEBT_AGE", "mon")){
				$overd = ageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
			}else{
				$overd = age($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
			}

			if ($overd < 0) {
				$overd = 0;
			}

			if ($overd > $cust['balance']) {
				$overd = $cust['balance'];
			}

			if ($cust["location"] == "int") {
				$cur = qryCurrency($cust["fcid"], "rate");
				$rate = $cur["rate"];

				if ($rate != 0) {
					$totoverd += $overd * $rate;
				} else {
					$totoverd += $overd;
				}
			} else {
				$totoverd += $overd;
			}


			/* check if customer may be removed */
			$cashbook->setOpt(grp(
				m("where", "cusnum='$cust[cusnum]' AND banked='no' AND div='".USER_DIV."'")
			));
			$cashbook->run();

			if(strlen(trim($cust['bustel']))<1) {
				$cust['bustel']=$cust['tel'];
			}

			$cust['balance'] = sprint($cust['balance']);

			if ($cust["location"] == "int") {
				if ($rate != 0.00) {
					$tot = $tot + ($cust['fbalance'] * $rate);
				} else {
					$tot = $tot + ($cust['balance']);
				}
			} else {
				$tot = $tot + $cust['balance'];
			}



			# Locations drop down
			$locs = array("loc"=>"Local", "int"=>"International", "" => "");
			$loc = $locs[$cust['location']];

			$fbal = "--";
			$ocurr = CUR;


			# alternate bgcolor
			$bgColor = bgcolor($i);
			$ajaxCust .= "<tr class='".bg_class()."'>";

			if ($action == "contact_acc") {
				$updatelink = "javascript: updateAccountInfo(\"$cust[cusnum]\", \"$cust[accno]\");";
				$ajaxCust .= "
					<td><a href='$updatelink'>$cust[accno]</a></td>
					<td><a href='$updatelink'>$cust[surname]</a></td>";
			} else if ($action == "select") {
				$ajaxCust .= "
					<td><a href='".SELF."?key=select&cusnum=$cust[cusnum]&".frmupdate_passon(true)."'>$cust[accno]</a></td>
					<td><a href='".SELF."?key=select&cusnum=$cust[cusnum]&".frmupdate_passon(true)."'>$cust[surname]</a></td>";
			} else {
				$ajaxCust .= "
					<td>$cust[accno]</td>
					<td>$cust[surname]</td>";
			}

			$ajaxCust .= "
					<td>$cust[bustel]</td>
					<td>$cust[catname]</td>
					<td>$cust[classname]</td>
					<td align='right' nowrap>$ocurr $cust[balance]</td>
					<td align='center' nowrap>$fbal</td>
					<td align='right' nowrap>$ocurr $overd</td>";



			$ajaxCust .= "</tr>";
		}

		$bgColor = bgcolor($i);
		$tot = sprint($tot);
		$totoverd = sprint($totoverd);

		$i--;

		$ajaxCust .= "
		<tr class='".bg_class()."'>
			<td colspan='5'>Total Amount Outstanding, from $i ".($i > 1 ? "clients" : "client")."</td>
			<td align='right' nowrap>".CUR." $tot</td>
			<td></td>
			<td align='right' nowrap>".CUR." $totoverd</td>
		</tr>";


	}


	$ajaxCust .= "
		".TBL_BR."
		</table>
		</form>";

	$printCust_end = "
	</div>";



	if (AJAX) {
		return $ajaxCust;
	} else {
		return "$printCust_begin$ajaxCust$printCust_end";
	}
}



function export() {
	$OUT = clean_html(printCust());

	require_lib("xls");
	StreamXLS("CustomerList", $OUT);
}



function age($cusnum, $days, $loc)
{

	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate < '".extlib_ago($days)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND odate < '".extlib_ago($days)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum'] );

}



function ageage($cusnum, $age, $loc)
{

	$bal = "balance";
	if($loc == 'int')
		$bal = "fbalance";

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age > '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND age > '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']);

}


?>
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

require("../settings.php");

if(isset($HTTP_POST_VARS["sum"])){
	$OUTPUT = sum($HTTP_POST_VARS);
} elseif(isset($HTTP_POST_VARS["all"])) {
	$OUTPUT = all($HTTP_POST_VARS);
} else {
	$OUTPUT = sel();
}

	$OUTPUT .= "
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";

require("../template.php");




function sel()
{

	$out = "
				<h3>Sales Report (POS Invoices Only)</h3>
				<table ".TMPL_tblDflts.">
				<form action='".SELF."' method='POST'>
					<tr>
						<th colspan='2'>Date Range</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td align='center' colspan='2'>
							<input type=text size=2 name=fday maxlength=2 value='1'>-
							<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-
							<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
							&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
							<input type='text' size='2' name='today' maxlength='2' value='".date("d")."'>-
							<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-
							<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='submit' name='sum' value='Summary'></td>
						<td align='right'><input type='submit' name='all' value='All Pos Sales'></td>
					</tr>
				</form>
				</table>";
	return $out;

}



function sum($HTTP_POST_VARS) {

	extract($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
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



	db_conn('cubit');

	$Sl="SELECT distinct(cust) FROM pr WHERE pdate>='$fromdate' AND pdate<='$todate' ORDER BY cust";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	$out = "
				<h3>Pos Sales Report</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Customer</th>
						<th>Amount</th>
					</tr>";

	$i=0;

	$tot=0;

	while($pd=pg_fetch_array($Ri)) {

		$Sl="SELECT sum(amount) AS amount FROM pr WHERE pdate>='$fromdate' AND pdate<='$todate' AND cust='$pd[cust]'";
		$Rd=db_exec($Sl) or errDie("Unable to get data.");

		$sd=pg_fetch_array($Rd);


		$out .= "
					<tr>
						<td>$pd[cust]</td>
						<td align='right'>".CUR." $sd[amount]</td>
					</tr>";

		$i++;

		$tot+=$sd['amount'];

	}

	$tot=sprint($tot);


	$out .= "
					<tr>
						<td>Total</td>
						<td align='right'>".CUR." $tot</td>
					</tr>
				</table>";

	include("temp.xls.php");

	Stream("Report", $out);

}



function all($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# Mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
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



	db_conn('cubit');

	$Sl="SELECT * FROM pr WHERE pdate>='$fromdate' AND pdate<='$todate' ORDER BY cust";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	$out = "
				<h3>Pos Sales Report</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Customer</th>
						<th>Date</th>
						<th>Inv</th>
						<th>Amount</th>
					</tr>";

	$i=0;

	$tot=0;

	while($pd=pg_fetch_array($Ri)) {

		$out .= "
					<tr>
						<td>$pd[cust]</td>
						<td>$pd[pdate]</td>
						<td>$pd[inv]</td>
						<td align='right'>".CUR." $pd[amount]</td>
					</tr>";

		$i++;

		$tot+=$pd['amount'];

	}

	$tot=sprint($tot);

	$out .= "
					<tr>
						<td>Total</td>
						<td></td>
						<td></td>
						<td align='right'>".CUR." $tot</td>
					</tr>
				</table>";

	include("temp.xls.php");
	Stream("Report", $out);
	return $out;

}


?>
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
require ("libs/ext.lib.php");
require_lib("validate");

if ( isset($HTTP_GET_VARS['addcontact']) ) {
	$OUTPUT = AddContact();
	$OUTPUT .= printCust();
} else {
	# show current stock
	$OUTPUT = printCust();
}

require ("template.php");



# show stock
function printCust ()
{

	# Set up table to display in
	$printCust = "
					<center>
					<h3>Current Customers</h3>
					<table border='1' cellpadding='3' cellspacing='0'>
						<tr>
							<th>Department</th>
							<th>Acc no.</th>
							<th>Surname/Company</th>
							<th>Business Tel</th>
							<th>Home Tel</th>
							<th>Category</th>
							<th>Classification</th>
							<th>Balance</th>
						</tr>
				";


	# Query server
	$tot = 0;
	$i = 0;

	# connect to database
	db_connect();

    $sql = "SELECT cusnum,deptname,accno,surname,bustel,tel,catname,classname FROM customers WHERE div = '".USER_DIV."' OR  ddiv = '".USER_DIV."' ORDER BY accno ASC";
    $custRslt = db_exec ($sql) or errDie ("Unable to retrieve Customers from database.");
	if (pg_numrows ($custRslt) < 1) {
		return "<li>There are no Customers in Cubit.</li>";
	}

	#get all stmnt amounts for array
	$get_stmnt = "SELECT cusnum,amount FROM stmnt";
	$run_stmnt = db_exec($get_stmnt) or errDie ("Unable to get customer statement information.");
	if (pg_numrows($run_stmnt) < 1){
		$cust_stmnt = array ();
	}else {
		$cust_stmnt = array ();
		while ($sarr = pg_fetch_array ($run_stmnt)){
			if (!isset($cust_stmnt[$sarr['cusnum']]))
				$cust_stmnt[$sarr['cusnum']] = 0;
			$cust_stmnt[$sarr['cusnum']] += $sarr['amount'];
		}
	}

//print "<pre>";
//var_dump ($cust_stmnt);
//print "</pre>";
//die ;

	while ($cust = pg_fetch_array ($custRslt)) {

//		#get customer balance from stmnt
//		$get_bal = "SELECT sum(amount) from stmnt WHERE cusnum = '$cust[cusnum]'";
//		$run_bal = db_exec($get_bal) or errDie ("Unable to get customer statement information.");
//		if (pg_numrows($run_bal) < 1){
//			$cust['balance'] = sprint ($cust['balance']);
//		}else {
//			$cust['balance'] = sprint (pg_fetch_result($run_bal,0,0));
//		}

		if (key_exists($cust['cusnum'],$cust_stmnt)){
			$cust['balance'] = sprint ($cust_stmnt[$cust['cusnum']]);
		}else {
			$cust['balance'] = sprint (0);
		}

		$tot = $tot + $cust['balance'];

		# alternate bgcolor
		$printCust .= "
						<tr>
							<td>$cust[deptname]</td>
							<td>$cust[accno]</td>
							<td>$cust[surname]</td>
							<td>$cust[bustel]</td>
							<td>$cust[tel]&nbsp;</td>
							<td>$cust[catname]</td>
							<td>$cust[classname]</td>
							<td align='right' nowrap>".CUR." $cust[balance]</td>
						</tr>
					";
		$i++;
	}

	if ($i > 1){
		$s = "s";
	}else {
		$s = "";
	}

	$tot = sprint($tot);

	$printCust .= "
						<tr><td colspan='8'><br></td></tr>
						<tr>
							<td colspan='7'>Total Amount Outstanding, from $i client$s </td>
							<td align='right' nowrap>".CUR." $tot</td>
						</tr>
					</table>
				";
	$OUTPUT = $printCust;
	require("tmpl-print.php");

}



// adds the customer to the contact list
function AddContact() {

	global $HTTP_GET_VARS;

	$v = & new Validate();
	if ( ! $v->isOk($HTTP_GET_VARS["addcontact"], "num", 1, 9, "") )
		return "Invalid Customer Number";

	// check if supplier can be added to contact list
	$rslt = db_exec("SELECT * FROM cons WHERE cust_id='$HTTP_GET_VARS[addcontact]'");
	if ( pg_numrows($rslt) >= 1 ) {
		return "Customer Already Added as a Contact<br>";
	}

	// get it from the db
	$sql = "SELECT * FROM customers WHERE cusnum='$HTTP_GET_VARS[addcontact]'";
	$rslt = db_exec($sql) or errDie("Unable to add customer to contact list. (RD)", SELF);
	if ( pg_numrows($rslt) < 1 )
		return "Unable to add customer to contact list. (RD2)";

	$data = pg_fetch_array($rslt);

	extract($data);

	if ( isset($HTTP_GET_VARS["addcontact_as"]) && $HTTP_GET_VARS["addcontact_as"] == "Company" ) {
		$company = "$surname";
		$surname = "";
	} else {
		$company = "";
	}

	// put it in the db
	$sql = "
		INSERT INTO cons (
			name, surname, comp, ref, tell, 
			cell, fax, email, hadd, padd, 
			date, cust_id, con, by, div
		) VALUES (
			'$cusname', '$surname', '$company', 'Customer', '$bustel',
			'$cellno', '$fax', '$email', '$addr1', '$paddr1',
			CURRENT_DATE, '$cusnum', 'No', '".USER_NAME."', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to add customer to contact list", SELF);

	if (pg_cmdtuples($rslt) < 1) {
		return "<li class='err'>Unable to add customer to contact list.</li>";
	}

}



?>
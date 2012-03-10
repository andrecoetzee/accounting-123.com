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
require ("../core-settings.php");
require ("../libs/ext.lib.php");

// show current stock
printaccnt();



function printaccnt()
{
	// Set up table to display in
	$OUTPUT = "
		<center>
		<h3>View Bank Accounts</h3></td>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Account Type</th>
				<th>Bank Name</th>
				<th>Type</th>
				<th>Currency</th>
				<th>Branch Name</th>
				<th>Branch Code</th>
				<th>Account Name</th>
				<th>Account Number</th>
				<th>Foreign Balance</th>
				<th>Local Currency</th>
				<th>Details</th>
				<th colspan='2'>Options</th>
			</tr>";

	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' ORDER BY bankname,branchname";
	$bankRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank account details from database.", SELF);
	$numrows = pg_numrows ($bankRslt);
	if ($numrows < 1) {
		$OUTPUT = "No Bank Accounts.";
		require ("../template.php");
	}

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");

	# display all orders
	for ($i = 0; $i < $numrows; $i++) {
		$bankacc = pg_fetch_array($bankRslt, $i);

		if($bankacc['fcid']!=0) {
			$curr = getSymbol($bankacc['fcid']);
		} else {
			$curr = 0;
			$locs[$bankacc['btype']] = "Local";
		}
		$type = $locs[$bankacc['btype']];

		# Get hook account number
		core_connect();

		$sql = "SELECT * FROM bankacc WHERE accid = '$bankacc[bankid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
		# Check if link exists
		if(pg_numrows($rslt) <1){
			return "<li class='err'>ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
		}
		$banklnk = pg_fetch_array($rslt);

		# Get bank balance
		$sql = "SELECT (debit - credit) as bal FROM core.trial_bal
				WHERE accid = '$banklnk[accnum]' AND period='12' AND div = '".USER_DIV."'";
		$brslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
		$bal = pg_fetch_array($brslt);

		$fbal = ($bankacc['btype'] == 'int')? "$curr[symbol] $bankacc[fbalance]" : "<center> - </center>";
		$lbal = ($bankacc['btype'] == 'int')? CUR." $bankacc[balance]" : CUR." ".sprint($bal['bal']);

		# alternate bgcolor
		$bgColor = bgcolorc($i);
		$OUTPUT .= "
			<tr bgcolor='$bgColor'>
				<td>$bankacc[acctype]</td>
				<td>$bankacc[bankname]</td>
				<td>$type</td>
				<td>$curr[symbol] - $curr[name]</td>
				<td>$bankacc[branchname]</td>
				<td>$bankacc[branchcode]</td>
				<td>$bankacc[accname]</td>
				<td align='right'>$bankacc[accnum]</td>
				<td align='right'>$fbal</td>
				<td align='right'>$lbal</td>
				<td>$bankacc[details]</td>";

		if($bankacc['type'] == 'cr'){
			$OUTPUT .= "<td><a href='creditcard-edit.php?bankid=$bankacc[bankid]'>Edit</a></td>";
		}elseif($bankacc['type'] == 'ptrl'){
			$OUTPUT .= "<td><a href='petrolcard-edit.php?bankid=$bankacc[bankid]'>Edit</a></td>";
		}else{
			$OUTPUT .= "<td><a href='bankacct-edit.php?bankid=$bankacc[bankid]'>Edit</a></td>";
		}

		db_connect();
		# Check if record can be removed
		$sql = "SELECT * FROM cashbook WHERE banked = 'no' AND bankid='$bankacc[bankid]' AND div = '".USER_DIV."'";
		$rs = db_exec($sql) or errDie("Unable to get cashbook entries.",SELF);
		if(pg_numrows($rs) > 0){
			$OUTPUT .= "<td><br></td></tr>";
		}else{
			$OUTPUT .= "<td><a href='bankacct-rem.php?bankid=$bankacc[bankid]'>Delete</a></td></tr>";
		}
	}

	$OUTPUT .= "
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td><a href='bank-pay-add.php'>Add Bank Payment</a></td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td><a href='cashbook-view.php'>View Cash Book</a></td>
	        </tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	require ("../template.php");

}



?>
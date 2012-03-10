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
require ("core-settings.php");
require ("libs/ext.lib.php");
require_lib("docman");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "view":
			$OUTPUT = printSerial($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
        $OUTPUT = slct();
}

require ("template.php");



# Default view
function slct($serno = "", $err = "")
{

	$slct = "
		<h3>Find Serial No.<h3>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<td>$err</td>
			</tr>
			<tr>
				<th>Serial Number</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='20' name='serno' value=$serno></td>
			</tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='submit' value='Continue'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slct;

}


# show invoices
function printSerial($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new validate ();
	$v->isOk ($serno, "string", 1, 10, "Invalid Serial number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return slct($serno, $confirm);
	}

	$serlist = "";
	$details = "no details";

	db_connect ();

	$sql = "SELECT * FROM serialrec WHERE serno LIKE '%$serno%' AND div = '".USER_DIV."' ORDER BY recid ASC";
	$serRslt = db_exec ($sql) or errDie ("Unable to retrieve purchases from database.");

	if(pg_numrows ($serRslt) > 0) {
		while($ser = pg_fetch_array($serRslt)){
			$stk = qryStock($ser["stkid"]);

			switch($ser['typ']){
				case "inv":
					$details = "Invoiced to $ser[cusname] Invoice No. $ser[invnum]";
					break;
				case "note":
					$details = "Received from $ser[cusname] Credit Note No. $ser[invnum]";
					break;
				case "pur":
					$details = "Purchased from Supplier $ser[cusname] Purchase No. $ser[invnum]";
					break;
				case "ret":
					$details = "Returned to Supplier $ser[cusname] Purchase No. $ser[invnum]";
					break;
				case "tran":
					$details = "Stock Decrease Transaction : $ser[cusname]";
					break;
			}

			$ser['edate'] = ext_rdate($ser['edate']);
			$serlist .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$ser[serno]</td>
					<td>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td>$details</td>
					<td align='center'>&nbsp;&nbsp;&nbsp;$ser[tdate]&nbsp;&nbsp;&nbsp;</td>
					<td>$stk[warranty]</td>
				</tr>";
		}
	}elseif(ext_findSer($serno)){
		$sers = ext_findSer($serno);
		foreach($sers as $key => $ser){
			# Get selected stock
			db_connect();
			$sql = "SELECT stkdes,stkcod FROM stock WHERE stkid = '$ser[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			$serlist .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$ser[serno]</td>
					<td>$stk[stkcod]</td>
					<td>$stk[stkdes]</td>
					<td align='center'>Available</td>
					<td align='center'>---</td>
					<td>&nbsp;</td>
				</tr>";
		}
	}else{
		$err = "<li class='err'>Serial Number <b>$serno</b> not found.</li>";
		return slct($serno, $err);
	}

	$serials = "
		<center>
		<h3>Stock Serial Numbers</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Serial No.</th>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Details</th>
				<th>Date</th>
				<th>Warranty</th>
			</tr>
			$serlist
			<tr><td><br></td></tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='".SELF."'>Find Another</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $serials;

}


?>
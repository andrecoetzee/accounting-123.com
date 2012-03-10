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

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "view";
}

switch ($_REQUEST["key"]) {
	case "view":
	default:
		$OUTPUT = printSord ();
		break;
	case "invoice":
	case "pos_invoice":
		$OUTPUT = invoice();
		break;
}

$OUTPUT .= mkQuickLinks(
	ql("sorder-new.php", "New Sales Order"),
	ql("customers-new.php", "New Customer")
);

require ("../template.php");

# show Sales Orders
function printSord () {

	extract($_REQUEST);

	if (isset($key)) {
		$key = strtolower($key);
		switch ($key) {
			case "export to spreadsheet":
			case "print":
			case "save":
				$pure = true;
				break;
			case "view":
			default:
				$pure = false;
		}
	} else {
		$pure = false;
	}

	if (!isset($from_year)) {
		explodeDate(false, $from_year, $from_month, $from_day);
		explodeDate(false, $to_year, $to_month, $to_day);
	}

	$printSord = "";

	if (!$pure) {
		$printSord .= "
		<form method='post' action='".SELF."'>
	    <table ".TMPL_tblDflts.">
	    <tr>
	    	<th colspan='2'>View Options</th>
	    </tr>
	    <tr bgcolor='".bgcolorg()."'>
	    	<td>Begin Date:</td>
	    	<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
	    	<td>End Date:</td>
	    	<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
	    </tr>
	    <tr>
	    	<td colspan='2' align='right'><input type='submit' value='Filter' /></td>
	    </tr>
	    </table>";
	}

	if (!isset($key)) {
		$printSord .= "</form>";
		return $printSord;
	}

	$printSord .= "
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Department</th>
		<th>Sales Person</th>
		<th>Sales Order No.</th>
		<th>Sales Order Date</th>
		<th>Customer Name</th>
		<th>Order No</th>
		".($pure?"":"<th colspan=6>Options</th>")."
	</tr>";

	$i = 0;
    $sql = "SELECT * FROM cubit.sorders
    		WHERE accepted = 'n' AND done = 'y' AND div = '".USER_DIV."' AND
    			odate BETWEEN '$from_year-$from_month-$from_day' AND 
    				'$to_year-$to_month-$to_day' AND slip_done='n'
    		ORDER BY sordid DESC";
    $sordRslt = db_exec ($sql) or errDie ("Unable to retrieve Sales Orders.");
	if (pg_numrows ($sordRslt) < 1) {
		$printSord .= "
		<tr bgcolor='".bgcolorc(0)."'>
			<td colspan='13'>No Sales Orders matching criteria.</td>
		</tr>";
	} else {
		while ($sord = pg_fetch_array ($sordRslt)) {
			# alternate bgcolor
			$bgColor = bgcolor($i);;

			# format date
			$sord['odate'] = explode("-", $sord['odate']);
			$sord['odate'] = $sord['odate'][2]."-".$sord['odate'][1]."-".$sord['odate'][0];
			$det = "sorder-details.php";
			$cancel = "sorder-cancel.php";
			$accept = "sorder-accept.php";
			$print = "sorder-print.php";
			$edit = "sorder-new.php";

			if($sord['location'] == 'int'){
				$det = "intsorder-details.php";
				$cancel = "intsorder-cancel.php";
				$accept = "intsorder-accept.php";
				$print = "intsorder-print.php";
				$edit = "intsorder-new.php";
			}

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
			$bcurr = CUR;
			if($sord['location'] == 'int'){
				$bcurr = $sord['currency'];
			}

			if ($sord["username"] == USER_NAME || user_is_admin(USER_NAME)) {
				$done = "
				<a href='picking_slip_done.php?sordid=$sord[sordid]'>
					Cancel
				</a>";
			} else {
				$done = "";
			}


			$printSord .= "<tr bgcolor='$bgColor'>
				<td>$sord[deptname]</td>
				<td>$sord[salespn]</td>
				<td>$sord[sordid]</td>
				<td align=center>$sord[odate]</td>
				<td>$sord[cusname] $sord[surname]</td>
				<td align=right>$sord[ordno]</td>
				<td>
					<a href='javascript:printer(\"picking_slips/picking_slip_print.php?sordid=$sord[sordid]\")'>
						Print Picking Slip
					</a>
				</td>
				<td>
					<a href='".SELF."?key=invoice&sordid=$sord[sordid]'>
						Invoice
					</a>
				</td>
				<td>
					<a href='".SELF."?key=pos_invoice&sordid=$sord[sordid]'>
						POS Invoice
					</a>
				</td>
				<td>$done</td>";
		}
	}

	if (!$pure) {
		$printSord .= "
		</table>
		</form>";
	} else {
		$printSord .= "
		</table>";

		$OUTPUT = clean_html($printSord);

		switch ($key) {
			case "export to spreadsheet":
				require_lib("xls");
				StreamXLS("sorders", $OUTPUT);
				break;

			case "print":
				$OUTPUT = "<h3>Sales Orders</h3>$OUTPUT";
				require("tmpl-print.php");
				break;

			case "save":
				$pure = true;
				break;
		}
	}

	return $printSord;
}

function invoice()
{
	extract ($_REQUEST);

	$pos = ($key == "pos_invoice") ? "pos=1" : "";

	$sql = "SELECT cusnum FROM cubit.sorders WHERE sordid='$sordid'";
	$sorders_rslt = db_exec($sql) or errDie("Unable to retrieve sales order.");
	$cusnum = pg_fetch_result($sorders_rslt, 0);

	$sql = "SELECT stkid, qty FROM cubit.sorders_items WHERE sordid='$sordid'";
	$sorder_rslt = db_exec($sql) or errDie("Unable to retrieve order.");

	while (list($stkid, $qty) = pg_fetch_array($sorder_rslt)) {
		$sql = "UPDATE cubit.stock SET alloc=(alloc-'$qty') WHERE stkid='$stkid'";
//		db_exec($sql) or errDie("Unable to update stock allocation.");
	}

	$OUTPUT = "
	<script>
		popupOpen(\"picking_slip_invoice.php?sordid=$sordid&cusnum=$cusnum&$pos\");
		move(\"".SELF."\");
	</script>";

	$sql = "DELETE FROM cubit.sorders WHERE sordid='$sordid'";
	db_exec($sql) or errDie("Unable to remove sales order.");


	return $OUTPUT;
}
?>

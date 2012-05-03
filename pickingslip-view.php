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

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "view";
}

switch ($_REQUEST["key"]) {
	case "view":
	default:
		$OUTPUT = printSord ();
		break;
}

$OUTPUT .= mkQuickLinks(
	ql("sorder-new.php", "New Sales Order"),
	ql("customers-new.php", "New Customer")
);

require ("template.php");

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

	if (!isset($type)) {
		$type = "all";
	}

	$sel_accepted = ($type == "accepted") ? "checked='t'" : "";
	$sel_notaccepted = ($type == "notaccepted") ? "checked='t'" : "";
	$sel_all = ($type != "accepted" && $type != "notaccepted") ? "checked='t'" : "";

	$printSord = "<h3>View Picking Slips</h3>";

	if (!$pure) {
		$printSord .= "
		<form method='post' action='".SELF."'>
	    <table ".TMPL_tblDflts.">
	    <tr>
	    	<th colspan='2'>View Options</th>
	    </tr>
	    <tr class='".bg_class()."'>
	    	<td>Begin Date:</td>
	    	<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
		</tr>
		<tr class='".bg_class()."'>
	    	<td>End Date:</td>
	    	<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
	    </tr>
	    <tr class='".bg_class()."'>
	    	<td>Type:</td>
	    	<td>
	    		<input type='radio' name='type' $sel_accepted value='accepted' /> Accepted/Invoiced
	    		<input type='radio' name='type' $sel_notaccepted value='notaccepted' /> Not Yet Accepted
	    		<input type='radio' name='type' $sel_all value='all' /> All
	    	</td>
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

	/* build filter */
	$filt = "odate>='$from_year-$from_month-$from_day' AND odate<='$to_year-$to_month-$to_day'";

	switch ($type) {
		case "accepted":
			$filt .= " AND accepted='y'";
			break;
		case "notaccepted":
			$filt .= " AND accepted='n'";
			break;
		case "all":
		default:
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
		<th>Grand Total</th>
		".($pure?"":"<th colspan=6>Options</th>")."
	</tr>";

	$i = 0;
    $sql = "SELECT * FROM cubit.sorders
    		WHERE accepted != 'c' AND done = 'y' AND div = '".USER_DIV."' AND ($filt)
    		ORDER BY sordid DESC";
    $sordRslt = db_exec ($sql) or errDie ("Unable to retrieve Sales Orders from database.");
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

			$printSord .= "<tr bgcolor='$bgColor'>
				<td>$sord[deptname]</td>
				<td>$sord[salespn]</td>
				<td>$sord[sordid]</td>
				<td align=center>$sord[odate]</td>
				<td>$sord[cusname] $sord[surname]</td>
				<td align=right>$sord[ordno]</td>
				<td align=right>$bcurr $sord[total]</td>";

			if (!$pure) {
				$printSord .= "
					<td><a href='$det?sordid=$sord[sordid]'>Details</a></td>
					<td><a href='javascript: printer(\"$print?invid=$sord[sordid]\");'>Print</a></td>";

				if ($sord['accepted'] == 'n') {
					$printSord .= "

						<td><a href='$edit?sordid=$sord[sordid]&cont=1'>Edit</a></td>
						<td><a href='$cancel?sordid=$sord[sordid]'>Cancel</a></td>
						<td><a href='$accept?sordid=$sord[sordid]'>Invoice</a></td>
					</tr>";
				} else {
					$printSord .="
						<td colspan='3' align='center'>Accepted</td>
					</tr>";
				}
			}
		}
	}

	if (!$pure) {
		$printSord .= "
		<tr>
			<td colspan='13'>
				<input type='submit' name='key' value='Print'>
				| <input type='submit' name='key' value='Export to Spreadsheet'>
			</td>
		</tr>
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
?>

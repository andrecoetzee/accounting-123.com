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

if (isset($_POST["email"])) {
	# show current stock
	$OUTPUT = email_custs($_POST);
}else {
	# show current stock
	$OUTPUT = printCust();
}

require ("template.php");

# show stock
function printCust () {
	global $_SESSION, $_GET;
	# get vars
	extract($_GET);

	if ( ! isset($action) ) $action = "listcust";

	/* session var prefix */
	$SPRE = "custview_";
	/* max number of customers in list */
	if (isset($viewall_cust)) {
		define("ACT_SHOW_LIMIT", 2147483647);
		$offset = 0;
	} else {
		define("ACT_SHOW_LIMIT", SHOW_LIMIT);
	}

	if (!isset($fval) && isset($_SESSION["${SPRE}fval"])) {
		$fval = $_SESSION["${SPRE}fval"];
	}

	if (!isset($filter) && isset($_SESSION["${SPRE}filter"])) {
		$filter = $_SESSION["${SPRE}filter"];
	}

	if (!isset($all) && isset($_SESSION["${SPRE}all"])) {
		$all = $_SESSION["${SPRE}all"];
	}

	if(isset($filter) && !isset($all)){
		if($filter == "all")
			$filter = "surname";
		$sqlfilter = " AND lower($filter) LIKE lower('%$fval%')";
		if (isset($_SESSION["${SPRE}all"])) unset($_SESSION["${SPRE}all"]);
		$_SESSION["${SPRE}fval"] = $fval;
		$_SESSION["${SPRE}filter"] = $filter;
	} else {
		if (isset($_SESSION["${SPRE}fval"])) unset($_SESSION["fval"]);
		if (isset($_SESSION["${SPRE}filter"])) unset($_SESSION["filter"]);
		$filter = "";
		$fval = "";
		$_SESSION["${SPRE}all"] = "true";
		$sqlfilter = "";
	}

	$filterarr = array("surname" => "Company/Name", "init" => "Initials", "accno" => "Account Number", "deptname" => "Department", "category"=>"Category", "class"=>"Classification");
	$filtersel = extlib_cpsel("filter", $filterarr, $filter);

	# Set up table to display in
	$printCust_begin = "
    <h3>Current Customers</h3>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type='hidden' name='action' value='$action'>
	<tr><th>.: Filter :.</th><th>.: Value :.</th></tr>
	<tr class='bg-odd'>
		<td>$filtersel</td>
		<td><input type='text' size='20' id='fval' value='$fval' onKeyUp='applyFilter();'></td>
	</tr>
	<tr class='bg-even'>
		<td align=center><input type='button' name='all' value='View All' onClick='viewAll();'></td>
		<td align=center><input type='button' value='Apply Filter' onClick='applyFilter();'></td>
	</tr>
	</table>
	<script>
		/* CRM CODE */
		function updateAccountInfo(id, name) {
			window.opener.document.frm_con.accountname.value=name;
			window.opener.document.frm_con.account_id.value=id;
			window.opener.document.frm_con.account_type.value='Customer';
			window.close();
		}

		/* AJAX filter code */
		function viewAll() {
			ajaxRequest('".SELF."', 'cust_list', AJAX_SET);
		}

		function applyFilter() {
			filter = getObject('filter').value;
			fval = getObject('fval').value;

			ajaxRequest('".SELF."', 'cust_list', AJAX_SET, 'filter=' + filter + '&fval=' + fval);
		}

		function updateOffset(noffset, viewall) {
			if (viewall && !noffset) {
				ajaxRequest('".SELF."', 'cust_list', AJAX_SET, 'viewall_cust=t');
			} else {
				ajaxRequest('".SELF."', 'cust_list', AJAX_SET, 'offset=' + noffset);
			}
		}
	</script>
	<p>
	<div id='cust_list'>";

	if (!isset($err)) {
		$err = "";
	} else {
		$err = "<li class='err'>$err</li>";
	}

	$ajaxCust = "
	<form action='customers-email-msg.php' method='post'>
	$err";

	if (!isset($offset) && isset($_SESSION["${SPRE}offset"])) {
		$offset = $_SESSION["${SPRE}offset"];
	} else if (!isset($offset)) {
		$offset = 0;
	}

	$_SESSION["${SPRE}offset"] = $offset;

	# connect to database
	db_connect();

	# counting the number of possible entries
	$sql = "SELECT * FROM customers
    		WHERE (div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') AND length(email) > 5 $sqlfilter
    		ORDER BY surname ASC";
	$rslt = db_exec($sql) or errDie("Error counting matching customers.");
	$custcount = pg_num_rows($rslt);
	
	/* view offsets */
	if ($offset > 0) {
		$poffset = ($offset >= ACT_SHOW_LIMIT) ? $offset - ACT_SHOW_LIMIT : 0;
		$os_prev = "<a class='nav' href='javascript: updateOffset(\"$poffset\");'>Previous</a>";
	} else {
		$os_prev = "&nbsp;";
	}

	if (($offset + ACT_SHOW_LIMIT) > $custcount) {
		$os_next = "&nbsp;";
	} else {
		$noffset = $offset + ACT_SHOW_LIMIT;
		$os_next = "<a class='nav' href='javascript: updateOffset(\"$noffset\");'>Next</a>";
	}
	
	if ($os_next != "&nbsp;" || $os_prev != "&nbsp;") {
		$os_viewall = "| <a class='nav' href='javascript: updateOffset(false, true);'>View All</a>";
	} else {
		$os_viewall = "";
	}
	
	$ajaxCust .= "
	<table ".TMPL_tblDflts.">
	<tr>
		<td colspan='20'>
		<table width='100%' border='0'>
		<tr>
			<td align='right' width='50%'>$os_prev</td>
			<td align='left' width='50%'>$os_next $os_viewall</td>
		</tr>
		</table>
		</td>
	</tr>
	<tr>
		<th>Acc no.</th>
		<th>Company/Name</th>
		<th>Tel</th>
		<th>Category</th>
		<th>Class</th>
		<th colspan='2'>Balance</th>
		<th>Overdue</th>
		".($pure?"":"<th colspan='11'>Options</th>")."
	</tr>";

	# Query server
	$tot = 0;
	$totoverd = 0;
	$i = 0;
    $sql = "SELECT * FROM customers
    		WHERE (div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') AND length(email) > 5 $sqlfilter
    		ORDER BY surname ASC
    		OFFSET $offset LIMIT ".ACT_SHOW_LIMIT;
    $custRslt = db_exec ($sql) or errDie("unable to get customer list.");
	if (pg_numrows ($custRslt) < 1) {
		$ajaxCust .= "
		<tr class='bg-odd'>
			<td colspan=20><li>There are no Customers matching the criteria entered.</li></td>
		</tr>";
	}else{
		while ($cust = pg_fetch_array ($custRslt)) {

			# Check type of age analisys
			if(div_isset("DEBT_AGE", "mon")){
				$overd = ageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
			}else{
				$overd = age($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
			}

			if($overd<0) {
				$overd=0;
			}

			if($overd>$cust['balance']) {
				$overd=$cust['balance'];
			}

			$totoverd += $overd;

			if(strlen(trim($cust['bustel']))<1) {
				$cust['bustel']=$cust['tel'];
			}

			$cust['balance'] = sprint($cust['balance']);
			$tot=$tot+$cust['balance'];

			$inv = "";

			# Locations drop down
			$locs = array("loc"=>"Local", "int"=>"International", "" => "");
			$loc = $locs[$cust['location']];

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

			$fbal = "$sp4--$sp4";
			$ocurr = CUR;
			$trans = "";

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$ajaxCust .= "<tr bgcolor='$bgColor'>";

			if ( $action == "contact_acc" ) {
				$updatelink = "javascript: updateAccountInfo(\"$cust[cusnum]\", \"$cust[accno]\");";
				$ajaxCust .= "
					<td><a href='$updatelink'>$cust[accno]</a></td>
					<td><a href='$updatelink'>$cust[surname]</a></td>";
			} else {
				$ajaxCust .= "
					<td>$cust[accno]</td>
					<td>$cust[surname]</td>";
			}

			$ajaxCust .= "
					<td>$cust[bustel]</td>
					<td>$cust[catname]</td>
					<td>$cust[classname]</td>
					<td align='right'>".CUR." $cust[balance]</td>
					<td align='right'>$fbal</td>
					<td align='right'>$ocurr $overd</td>";

			if ( $action == "listcust" ) {
				$ajaxCust .= "
					$trans $inv";

				$ajaxCust .= "
				<td><input type='checkbox' name='emails[]' value='$cust[email]' checked='yes'/></td>
				</tr>";
			}

			$i++;
		}
		if ($i > 1) {
			$s = "s";
		} else {
			$s = "";
		}
		
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$tot = sprint($tot);
		$totoverd = sprint($totoverd);
		$ajaxCust .= "
		<tr bgcolor='$bgColor'>
			<td colspan='5'>Total Amount Outstanding, from $i client$s </td>
			<td align='right'>".CUR." $tot</td>
			<td></td>
			<td align='right'>".CUR." $totoverd</td>
			<td colspan='11' align='right'><input type='submit' name='email' value='Email Customers' /></td>
		</tr>";

		$ajaxCust .= "
		<tr>
			<td colspan='20'>
			<table width='100%' border='0'>
			<tr>
				<td align='right' width='50%'>$os_prev</td>
				<td align='left' width='50%'>$os_next $os_viewall</td>
			</tr>
			</table>
			</td>
		</tr>";
	}

	$ajaxCust .= "
	<tr><td><br /></td></tr>
	</table>
	</form>";

	$printCust_end = "
	</div>"
	.mkQuickLinks(
		ql("customers-new.php", "Add New Customer")
	);

	if (AJAX) {
		return $ajaxCust;
	} else {
		return "$printCust_begin$ajaxCust$printCust_end";
	}
}

function age($cusnum, $days, $loc){
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

function ageage($cusnum, $age, $loc){
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

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

# get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($HTTP_GET_VARS["calloutid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
			case "update":
				$OUTPUT = write($HTTP_POST_VARS);
				break;

			default:
				$OUTPUT = details($HTTP_POST_VARS);
				break;
		}
	} else {
		$OUTPUT = details($HTTP_POST_VARS);
	}
}

# get templete
require("template.php");

# details
function details($HTTP_POST_VARS, $error="") {
	extract($HTTP_POST_VARS);

	# validate input
	include("libs/validate.lib.php");
	$v = new validate ();
	if (isset($calloutid)) {
		$v->isOk($calloutid, "num", 1, 20, "Invalid callout document number.");
	}

	if (isset($deptid)) {
		$v->isOk($deptid, "num", 1, 20, "Invalid department number.");
	}

	if (isset($letters)) {
		$v->isOk($letters, "string", 0, 5, "Invalid First 3 Letters.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>$e[msg]</li>";
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	if (!isset($deptid)) {
		$deptid = 0;
	} else if (isset($calloutid)) {
		db_conn("cubit");
		$sql = "UPDATE callout_docs SET deptid='$deptid' WHERE calloutid='$calloutid' AND deptid<>'$deptid'";
		db_exec($sql) or errDie("Error updating invoice department.");
	}

	if (!isset($stkerr)) {
		$stkerr = "0,0";
	}

	if(!isset($done)){
		$done = "";
	}

	# Get callout document info
	db_connect();
	$sql = "SELECT * FROM callout_docs WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$docRslt = db_exec ($sql) or errDie ("Unable to get callout document information");
	if (pg_numrows ($docRslt) < 1) {
		return "<li class=err>Callout Document Not Found</li>";
	}
	$doc = pg_fetch_array($docRslt);

	$cusnum = $doc['cusnum'];

	# check if callout document has been printed
	if($doc['accepted'] == "y"){
		$error = "<li class=err> Error : Callout Document number <b>$calloutid</b> has already been printed.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$doc[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected customer info
		db_connect();
		$sql = "SELECT * FROM customers WHERE cusnum = '$doc[cusnum]' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		if (pg_numrows ($custRslt) < 1) {
			db_connect();
			# Query server for customer info
			$sql = "SELECT cusnum,cusname,surname FROM customers WHERE deptid = '$doc[deptid]' AND location != 'int' AND lower(surname) LIKE lower('$letters%') AND div = '".USER_DIV."' ORDER BY surname";
			$custRslt = db_exec ($sql) or errDie ("Unable to view customers");
			if (pg_numrows ($custRslt) < 1) {
				$ajax_err = "<li class=err>No customer names starting with <b>$letters</b> in database.</li>";
				//return view_err($HTTP_POST_VARS, $err);
			}else{
				$customers = "<select name='cusnum' onChange='javascript:document.form.submit();'>";
				$customers .= "<option value='-S' selected>Select Customer</option>";
				while($cust = pg_fetch_array($custRslt)){
					$customers .= "<option value='$cust[cusnum]'>$cust[cusname] $cust[surname]</option>";
				}
				$customers .= "</select>";
			}
			# take care of the unset vars
			$cust['addr1'] = "";
			$cust['cusnum'] = "";
			$cust['accno'] = "";
		}else{
			$cust = pg_fetch_array($custRslt);
			# moarn if customer account has been blocked
			if($cust['blocked'] == 'yes'){
				return "<li class=err>Error : Selected customer account has been blocked.</li>";
			}
			$customers = "<input type=hidden name=cusnum value='$cust[cusnum]'>$cust[cusname]  $cust[surname]";
			$cusnum = $cust['cusnum'];
		}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class=err> There are no Stores found in Cubit.</li>";
	}else{
			$whs .= "<option value='-S' disabled selected>Select Store</option>";
			while($wh = pg_fetch_array($whRslt)){
					$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
	}
	$whs .="</select>";

	# days drop downs
	$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");


	# format date
	list($oyear, $omon, $oday) = explode("-", $doc['odate']);

/* --- End Drop Downs --- */
	// get the ID of the first warehouse
	db_conn("exten");
	$sql = "SELECT whid FROM warehouses ORDER BY whid ASC LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error reading warehouses (FWH).");

	if ( pg_num_rows($rslt) > 0 ) {
		$FIRST_WH = pg_fetch_result($rslt, 0, 0);
	} else {
		$FIRST_WH = "-S";
	}
/* --- Start Products Display --- */

	# select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>STORE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>ITEM PRICE</th><tr>";

	# get selected stock in this callout document
	db_connect();
	$sql = "SELECT * FROM callout_docs_items  WHERE calloutid = '$calloutid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$stkd['account']+=0;

		$stkd['unitcost'] = sprint ($stkd['unitcost']);

			# keep track of selected stock amounts
			$amts[$i] = $stkd['amt'];
			$i++;

			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# put in product
			$products .="<input type=hidden name=accounts[] value=0>
			<input type=hidden name=descriptions[] value=''>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=hidden name=whids[] value='$stkd[whid]'>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>".extlib_rstr($stk['stkdes'], 30)."</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td></tr>";
			$key++;
	}

	# Look above(remprod keys)
	$keyy = $key;

	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}

	# check if stock warehouse was selected

	/* -- start Listeners -- */


	/* -- End Listeners -- */

	$products .= "</table>";

/* --- End Products Display --- */


/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = sprint($doc['subtot']);

	# Calculate subtotal
	$SUBTOT = sprint($doc['subtot']);

/* --- End Some calculations --- */


/*--- Start checks --- */


	$details_begin = "
	<center><h3>Confirm Call Out Document Has Been Invoiced</h3>
	<form action='".SELF."' method='post' name='form'>
	<input type='hidden' name='key' value='update'>
	<input type='hidden' name='calloutid' value='$calloutid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border='0' width=95%>
 	<tr><td valign=top>
 	<div id='cust_selection'>";

		$ajaxOut = "
		<input type=hidden name=stkerr value='$stkerr'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr>
				<th colspan=2> Customer Details </th>
			</tr>
			<tr bgcolor='".TMPL_tblDataColor1."'>
				<td>Department</td>
				<td valign=center>$dept[deptname]</td>
			</tr>
			<tr bgcolor='".TMPL_tblDataColor2."'>
				<td>Customer</td>
				<td valign=center>$customers</td>
			</tr>
			<tr bgcolor='".TMPL_tblDataColor1."'>
				<td valign=top>Customer Address</td>
				<td valign=center>".nl2br($cust['addr1'])."</td>
			</tr>
			<tr bgcolor='".TMPL_tblDataColor2."'>
				<td>Customer VAT Number</td>
				<td>11</td>
			</tr>
		</table>";

	$details_end = "
	</div>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Callout Document Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Job Number</td><td valign=center>$doc[calloutid]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Call Out Person</td><td valign=center>$doc[calloutp]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date Service Required</td><td valign=center>$oday-$omon-$oyear</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Default Travel or Call Out</td><td valign=center>$doc[def_travel]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Default Labour</td><td valign=center>$doc[def_labour]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr>
		<td colspan='4'>".nl2br($doc['sign'])."</td>
	</tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Description Of Callout</th><th width=25%>Comments</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='callout-new.php'>New Callout Document</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top>".nl2br($doc['calloutdescrip'])."</td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top>".nl2br($doc['comm'])."</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='callout-view.php'>View Callout Documents</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
	</td></tr>
	<tr><td align='right'><input type='submit' value='Next'></td></tr>
	</table>
	</form>
	</center>";

		return "$details_begin$ajaxOut$details_end";
}


function write ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($calloutid))
		return "Invalid use of module";

	db_connect ();

	$update_sql = "UPDATE callout_docs SET invoiced = 'yes' WHERE calloutid = '$calloutid'";
	$run_update = db_exec($update_sql) or errDie("Unable to update call out document information");

	return "
				<h2>Document Stored As Invoice.</h2>
        <p>
        <table ".TMPL_tblDflts.">
            <tr><th width='50'>Quick Links</th></tr>
            <tr bgcolor='".bgcolorg()."'><td><a href='callout-new.php'>New Callout Document</a></td></tr>
            <tr bgcolor='".bgcolorg()."'><td><a href='callout-view.php'>View Callout Documents</a></td></tr>
            <script>document.write(getQuicklinkSpecial());</script>
        </table>";

}


?>

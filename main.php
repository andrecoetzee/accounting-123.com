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
require_lib("pic");

if (isset($_GET["popup"])) {
	setNavLinkTarget($_GET["popup"]);
}

$OUTPUT = main();

require ("template.php");

function main() {
	$STYLES = "
	<style type='text/css'>
	.ctable {
		padding: ".TMPL_tblCellPadding."px;
		border-spacing: ".TMPL_tblCellSpacing."px;
	}

	.v_show {
		visibility: visible;
	}

	.v_hide {
		visibility: collapse;
	}

	#navigator {
		position: absolute;
		left: 0px;
		top: 87px;

		width: 100%;
		height: 80%;
	}

	#navigator td {
		text-align: center;
		vertical-align: middle;
	}

	#categories {
		position: relative;
		left: 0px;
		top: 0px;

		width: 756px;
		height: 100px;
	}

	#categories td {
		text-align: center;
		vertical-align: top;
		width: 108px;
		font-weight: bold;
		cursor: pointer;
	}

	#actions {
		position: relative;
		left: 0px;
		top: 0px;

		width: 756px;
		height: 50px;
	}

	#actions td {
		text-align: center;
		vertical-align: middle;
		width: 189px;
	}

	.actiontd1_off {
		background: url(images/new2/regular_processing.gif);
		background-repeat: no-repeat;
		background-position: center;
	}

	.actiontd1_on {
		background: url(images/new2/regular_processing_sh.gif);
		background-repeat: no-repeat;
		background-position: center;
		width: 1000px;
	}

	.actiontd2_off {
		background: url(images/new2/processing.gif);
		background-repeat: no-repeat;
		background-position: center;
	}

	.actiontd2_on {
		background: url(images/new2/processing_sh.gif);
		background-repeat: no-repeat;
		background-position: center;
		width: 1000px;
	}

	.actiontd3_off {
		background: url(images/new2/reports.gif);
		background-repeat: no-repeat;
		background-position: center;
	}

	.actiontd3_on {
		background: url(images/new2/reports_sh.gif);
		background-repeat: no-repeat;
		background-position: center;
		width: 1000px;
	}

	.actiontd4_off {
		background: url(images/new2/maintenance.gif);
		background-repeat: no-repeat;
		background-position: center;
	}

	.actiontd4_on {
		background: url(images/new2/maintenance_sh.gif);
		background-repeat: no-repeat;
		background-position: center;
		width: 1000px;
	}

	.actiontd5_off {
		background: url(images/new2/settings.gif);
		background-repeat: no-repeat;
		background-position: center;
	}

	.actiontd5_on {
		background: url(images/new2/settings_sh.gif);
		background-repeat: no-repeat;
		background-position: center;
		width: 1000px;
	}






	#actions div {
		width: 135px;
		height: 36px;
		display: table-cell;
		vertical-align: middle;
		font-weight: bold;
		cursor: pointer;
	}

	#links_container {
		display: table-cell;
		width: 690px;
		height: 270px;
	}

	#links_container_data {
		vertical-align: top;
	}

	#links_container_data td {
		width: 115px;
		height: 35px;
		border: 2px solid #222;
		background: #fff;
		cursor: pointer;
		font-size: 9pt;
		-moz-border-radius: 8px;
	}
	</style>";

	$navlink_target = getNavLinkTarget();

	$SCRIPTS = "
	<script>
	/* function: handles the links */
	function link(url) {
		if ($navlink_target == 0) {
			document.location.href = url;
		} else {
			popupSized(url, 'popup' + url, screen.availWidth, screen.availHeight,'');
		}
	}

	/* function: links to self with supplied get vars */
	function selflink(getvars) {
		document.location.href = '".SELF."?' + getvars;
	}

	/* function: links to top frame */
	function parentlink(url) {
		top.location.href = url;
	}

	/* function: sets category */
	function set_category(el, img, catid) {
		// reset all of them
		unset_cat_bg('customers', 	'images/new2/customers.gif');
		unset_cat_bg('suppliers', 	'images/new2/suppliers.gif');
		unset_cat_bg('inventory', 	'images/new2/stock.gif');
		unset_cat_bg('ledger', 		'images/new2/general_ledger.gif');
		unset_cat_bg('payroll', 	'images/new2/salaries.gif');
		unset_cat_bg('business', 	'images/new2/business.gif');

		// get the elements
		ce = document.getElementById('catc_' + el);
		ie = document.getElementById('cat_' + el);

		// alter them
		ce.style.border = '1px solid #ddd';
		ie.src = img;

		sel_category = catid;
		UpdateContainer();
	}

	/* function: sets the specified category cell's background pic and mode */
	function unset_cat_bg(el, img) {
		ce = document.getElementById('catc_' + el);
		ie = document.getElementById('cat_' + el);

		ce.style.border = 'none';
		ie.src = img;
	}

	/* function: sets action */
	function set_action(cell, actid, button_action_command) {
		unset_action_bg('a_regproc','actiontd1_off');
		unset_action_bg('a_proc','actiontd2_off');
		unset_action_bg('a_reports','actiontd3_off');
		unset_action_bg('a_maint','actiontd4_off');
		unset_action_bg('a_admin','actiontd5_off');

		cell.className = button_action_command;

		sel_action = actid;
		UpdateContainer();
	}

	/* function: sets the specified action cell's background pic and mode */
	function unset_action_bg(cell,button_action_command2) {
		ce = document.getElementById(cell);
		ce.className = button_action_command2;
	}

	/* function: sets sub action */
	function set_subaction(cell, actid) {
		// get the clicked cell's id, because we are going to update the whole
		// links container, and that means even the cell we clicked on
		// this way we can just get the cell with the same id in the new data
		// and set's it's style to give a 'clicked' effect
		cid = cell.id;

		// set the subaction id to update the links in the container
		sel_subaction = actid;
		UpdateSubContainer();

		// now use the saved id to set the sister link's background color
		document.getElementById(cid).style.background = '#f00';
	}

	/* function: unsets the sub action button backgrounds */
	function unset_subaction_bg(cell) {
		c = document.getElementById(cell);

		if (c) {
			c.style.background = '#000';
		}
	}

	/* updates the container data */
	function UpdateContainer() {
		document.getElementById('links_container').innerHTML = lc_data[sel_category][sel_action];
	}

	/* updates the container data with subaction array */
	function UpdateSubContainer() {
		document.getElementById('links_container').innerHTML = lc_subdata[sel_subaction];
	}

	/* makes the email popup */
	function linkEmail() {
		emailwin = window.open('groupware/index.php','email_window', 'scrollbars=no, width=750, height=550');
		emailwin.focus();
	}

	/* link container array */
	lc_data = new Array();
	lc_subdata = new Array();";

	/* take the lc data to java script */

	// has set the default selected category and action
	$setdefault = false;

	// it unnecessary to set the action constants with every category
	$setactions = false;

	// category + action counters
	$cat_num = 0;
	$act_num = 0;
	$sub_num = 0;

	// get lc data
	$lc = getLC();

	// go!
	foreach ($lc as $cat => $actions) {
		// constant to reference categories
		$SCRIPTS .= "$cat = $cat_num;\n";

		// reset the category to array
		$SCRIPTS .= "lc_data[$cat] = new Array()\n";

		foreach ($actions as $act => $lcdata) {
			if (is_array($lcdata)) continue;

			if (!$setactions) {
				// constant to reference actions
				$SCRIPTS .= "$act = $act_num;\n";
				++$act_num;
			}

			// set default selections
			if (!$setdefault) {
				if (isset($_GET["selected"]) && strtoupper($_GET["selected"]) == strtoupper($cat)) {
					$SCRIPTS .= "sel_category = $cat;\n";
					$SCRIPTS .= "sel_action = $act;\n";
					$setdefault = true;
				} else if (!isset($_GET["selected"])) {
					$SCRIPTS .= "sel_category = $cat;\n";
					$SCRIPTS .= "sel_action = $act;\n";
					$setdefault = true;
				}
			}

			// dump the data
			$itemdata = preg_replace("/[\r\n]/", "", $lcdata);
			$itemdata = preg_replace("/[\"]/", "\\\"", $itemdata);

			$SCRIPTS .= "lc_data[$cat][$act] = \"$itemdata\";\n";

			if (isset($actions["${act}S"]) && is_array($actions["${act}S"])) {
				foreach ($actions["${act}S"] as $subid => $subdata) {
					// set the constant
					$SCRIPTS .= "${cat}_${act}_${subid} = $sub_num;\n";

					// dump the sub data
					$subitemdata = preg_replace("/[\r\n]/", "", $subdata);
					$subitemdata = preg_replace("/[\"]/", "\\\"", $subitemdata);

					$SCRIPTS .= "lc_subdata[${cat}_${act}_${subid}] = \"$itemdata$subitemdata\";\n";

					++$sub_num;
				}
			}
		}

		++$cat_num;
		$setactions = true;
	}

	$SCRIPTS .= "
	</script>";

	addonload("UpdateContainer();");

	#get this company's logo
	db_conn ("cubit");

	$get_logo = "SELECT imgtype FROM compinfo WHERE div = '".USER_DIV."' AND length(tel) > 0 LIMIT 1";
	$run_logo = db_exec($get_logo);

	if(pg_numrows($run_logo) < 1){
//		$showlogo = "<a href='compinfo-view.php'><img src='images/blanklogo.png' border='0' /></a>";
		$showlogo = "<a href=\"javascript:popupSized('compinfo-view.php', 'Company Information', screen.width, screen.height)\"><img src='images/blanklogo.png' border='0' /></a>";
	}else {
		$arr = pg_fetch_array($run_logo);
		if(strlen($arr['imgtype']) < 1){
			$showlogo = "<img src='images/bluelogo.png' border='0' />";
		}else {
			$showlogo = "<img src='images/showlogo.php' height='80' />";
		}
	}

	/* create the selections */
	$sel_customers = "";
	$sel_suppliers = "";
	$sel_inventory = "";
	$sel_payroll = "";
	$sel_ledger = "";
	$sel_business = "";

	if (isset($_GET["selected"])) {
		${"sel_".strtolower($_GET["selected"])} = "style='border: 1px solid #ddd'";
	} else {
		$sel_customers = "style='border: 1px solid #ddd'";
	}

	// Should we display the heart?
	$sql = "SELECT value FROM cubit.settings WHERE constant='HEART'";
	$heart_rslt = db_exec($sql) or errDie("Unable to check if heart should be displayed");
	$heart = pg_fetch_result($heart_rslt, 0);

	if ($heart) {
		$showheart = "<a href=\"javascript:popupSized('health_report.php', 'Health Report', screen.width, screen.height)\"><img src='images/cubithealth3.jpg' border='0' height='70' /></a>";
	} else {
		$showheart = "";
	}

/*
	<table ".TMPL_tblDflts." style='width: 100%;'>
		<tr>
			<td align='center'>$showlogo</td>
		</tr>
	</table>
*/

//			Customers
//			Suppliers
//			Stock
//			Salaries
//			General Ledger
//			Business


	$OUTPUT = "
	<div style='position: absolute; top: 0px; left: 0px; width: 100%; display: table-cell; text-align: right'>
		<a href='logout.php'><img src='images/logout.gif' border='0' /></a><br />
		$showheart
	</div>
	<center>
	<div style='position: absolute; top: 0px; display: table-cell;'>
		$showlogo
	</div>
	</center>
	<table id='navigator' class='ctable'><tr><td>

	<center>
	<!-- Categories -->
	<table id='categories' class='ctable'>
	<tr>
		<td id='catc_customers' onclick='set_category(\"customers\", \"images/new2/customers_sh.gif\", CUSTOMERS);' $sel_customers>
			<img id='cat_customers' src='images/new2/customers.gif' /><br />

		</td>
		<td id='catc_suppliers' onclick='set_category(\"suppliers\", \"images/new2/suppliers_sh.gif\", SUPPLIERS);' $sel_suppliers>
			<img id='cat_suppliers' src='images/new2/suppliers.gif' /><br />

		</td>
		<td id='catc_inventory' onclick='set_category(\"inventory\", \"images/new2/stock_sh.gif\", STOCK);' $sel_inventory>
			<img id='cat_inventory' src='images/new2/stock.gif' /><br />

		</td>
		<td id='catc_payroll' onclick='set_category(\"payroll\", \"images/new2/salaries_sh.gif\", SALARIES);' $sel_payroll>
			<img id='cat_payroll' src='images/new2/salaries.gif' /><br />

		</td>
		<td id='catc_ledger' onclick='set_category(\"ledger\", \"images/new2/general_ledger_sh.gif\", LEDGER);' $sel_ledger>
			<img id='cat_ledger' src='images/new2/general_ledger.gif' /><br />

		</td>
		<td id='catc_business' onclick='set_category(\"business\", \"images/new2/business_sh.gif\", BUSINESS);' $sel_business>
			<img id='cat_business' src='images/new2/business.gif' /><br />

		</td>
	</tr>
	</table>
	<br />
	<!-- Actions -->
	<table id='actions' class='ctable'>
	<tr>
		<td onclick=\"set_action(this, REGPROC, 'actiontd1_on');\" id='a_regproc' class='actiontd1_on'><center><div></div></center></td>
		<td onclick=\"set_action(this, PROC, 'actiontd2_on');\" id='a_proc' class='actiontd2_off'><center><div></div></center></td>
		<td onclick=\"set_action(this, REPORTS, 'actiontd3_on');\" id='a_reports' class='actiontd3_off'><center><div></div></center></td>
		<td onclick=\"set_action(this, MAINT, 'actiontd4_on');\" id='a_maint' class='actiontd4_off'><center><div></div></center></td>
		<td onclick=\"set_action(this, ADMIN, 'actiontd5_on');\" id='a_admin' class='actiontd5_off'><center><div></div></center></td>
	</tr>
	</table>

	<!-- Links Container -->
	<div id='links_container'>
	</div>

	<script>
	var arrNavClear = new Array();
	var iNavClear = 0;
	function clearNavBtn(btn) {
		arrNavClear[iNavClear] = btn;
		setTimeout('clearNavBtnAct(' + iNavClear + ')', 200);

		//clearNavBtnAct(iNavClear);

		if (++iNavClear == 65535) {
			iNavClear = 0;
		}
	}

	function clearNavBtnAct(arrid) {
		arrNavClear[arrid].style.background=\"#fff\";
		arrNavClear[arrid] = null;
	}
	</script>

	</center>

	</td></tr>
       <tr>
                <td valign='center'>
                        <div>Powered By <br /><a style='text-decoration: none' href='http://www.cubit.co.za'><img border='0' src='images/newcubitlogo.jpg' /></a>
                        <p>This software is used in accordance with the <a target='_blank' href='license.html'>Cubit Licence</a></p></div>
                </td>
        </tr>
	</table>";

	return $STYLES.$SCRIPTS.$OUTPUT;
}

function getLC() {
	$FX = array();
	$FX[] = "onmouseover='this.style.background=\"#f00\"'";
	$FX[] = "onmouseout='clearNavBtn(this)'";
	$FX[] = "onmousedown='this.style.background=\"#ed6f1b\"'";
	$FX[] = "onmouseup='this.style.background=\"#f00\"'";

	$FX = implode(" ", $FX);

	//$FX = "onmouseover='this.style.border=\"2px solid #f00\"' onmouseout='this.style.border=\"2px solid #000\"'";

	$lc = array();

	$lc["CUSTOMERS"] = array();

	$lc["CUSTOMERS"]["REGPROC"] = "
	<br /><br />
	<table id='links_container_data' class='ctable'>
	<tr>
		<td onclick='set_subaction(this, CUSTOMERS_REGPROC_SALES);' id='cust_rp_sales'><center><div>Sales</div></center></td>
		<td onclick='set_subaction(this, CUSTOMERS_REGPROC_PAYMT);' id='cust_rp_paymt'><center><div>Payments &amp; Receipts</div></center></td>
		<td onclick='set_subaction(this, CUSTOMERS_REGPROC_QUOTE);' id='cust_rp_quote'><center><div>Quotes</div></center></td>
		<td onclick='set_subaction(this, CUSTOMERS_REGPROC_STOCK);' id='cust_rp_stock'><center><div>Stock</div></center></td>
		<td onclick='set_subaction(this, CUSTOMERS_REGPROC_JOURN);' id='cust_rp_journ'><center><div>Journal</div></center></td>
	</tr>
	</table>
	<br /><br />";

	$lc["CUSTOMERS"]["REGPROCS"]["SALES"] = "
	<table id='links_container_data' class='ctable'>
	<tr>
		<td $FX onclick='link(\"cust-credit-stockinv.php\");'>Invoice: New</td>
		<td $FX onclick='link(\"nons-invoice-new.php\");'>Invoice: New Non Stock</td>
		<td $FX onclick='link(\"nons-invoice-view.php\");'>Invoice: Non Stock View Details/Edit/Remove/Process</td>
		<td $FX onclick='link(\"nons-intinvoice-new.php\");'>Invoice: New International Non Stock</td>
		<td $FX onclick='link(\"rec-nons-invoice-new.php\");'>Invoice: New Recurring Non Stock</td>
		<td $FX onclick='link(\"rec-invoice-new.php\");'>Invoice: New Recurring</td>
	</tr>
	<tr>
		<td $FX onclick='link(\"invoice-view.php?mode=creditnote\");'>Issue Credit Note</td>
		<td $FX onclick='link(\"nons-invoice-view.php?mode=creditnote\");'>Issue Non Stock Credit Note</td>
		<td $FX onclick='link(\"invoice-view.php\");'>Invoices: View</td>
		<td $FX onclick='link(\"invoice-search.php\");'>Invoice: Search</td>
		<td $FX onclick='link(\"invoice-unf-view.php\");'>Invoices: Incomplete Continue/Cancel</td>
	</tr>
	<tr>
		<td $FX onclick='link(\"pos-invoice-new.php\");'>Invoice: New POS</td>
		<td $FX onclick='link(\"pos-invoice-view-prd.php\");'>Invoices: POS Processed Credit Note/Details</td>
		<td $FX onclick='link(\"pos-invoice-view-prd.php\");'>Invoices: POS Processed Reprint/Slip</td>
		<td $FX onclick='link(\"pos-invoice-list.php\");'>Invoices: POS Unprocessed Details/Edit</td>
		<td $FX onclick='link(\"pos-invoice-list.php\");'>Invoices: POS Unprocessed Remove/Process</td>
	</tr>
	<tr>
		<td $FX onclick='link(\"sorder-new.php\");'>Sales Order: New</td>
		<td $FX onclick='link(\"nons-sorder-new.php\");'>Sales Order: New Non-Stock</td>
		<td style='background: none;'></td>
		<td $FX onclick='link(\"groupware/dashboard.php\")'>Sales Person Today Display</td>
	</tr>
	</table>";


	$lc["CUSTOMERS"]["REGPROCS"]["PAYMT"] = "
	<table id='links_container_data' class='ctable'>
	<tr>
		<td $FX onclick='link(\"bank/bank-recpt-inv.php\");'>Bank: Receipt From Customer</td>
		<td $FX onclick='link(\"bank/bank-recpt-multi-debtor.php\");'>Bank: One Receipt From Multiple Customer</td>
		<td $FX onclick='link(\"bank/petty-recpt-cust.php\");'>Petty Cash: Receipt From Customer</td>
		<td $FX onclick='link(\"bank/bank-pay-cus.php\");'>Bank: Pay Customer</td>
		<td $FX onclick='link(\"bank/petty-pay-cust.php\");'>Petty Cash: Pay Customer</td>
	</tr>
	<tr>
		<td $FX onclick='link(\"invoice-note-view-prd.php\");'>Credit Notes: View/Details</td>
		<td $FX onclick='link(\"nons-invoice-note-view.php\");'>Credit Notes: Non Stock Details/Reprint</td>
	</tr>
	</table>";

	$lc["CUSTOMERS"]["REGPROCS"]["QUOTE"] = "
	<table id='links_container_data' class='ctable'>
	<tr>
		<td $FX onclick='link(\"quote-new.php\");'>Quote: New</td>
		<td $FX onclick='link(\"quote-view.php\");'>Quotes: Details/Edit</td>
		<td $FX onclick='link(\"quote-view.php\");'>Quotes: Cancel/Accept</td>
		<td $FX onclick='link(\"quote-view.php\");'>Quotes: Print/Print PDF</td>
	</tr>
	<tr>
		<td $FX onclick='link(\"pos-quote-new.php\");'>Quote: POS New</td>
		<td $FX onclick='link(\"pos-quote-view.php\");'>Quote: POS Details/Edit</td>
		<td $FX onclick='link(\"pos-quote-view.php\");'>Quote: POS Cancel/Accept</td>
		<td $FX onclick='link(\"pos-quote-view.php\");'>Quote: POS Print/Print PDF</td>
	</tr>
	<tr>
		<td $FX onclick='link(\"nons-quote-new.php\");'>Quote: New Non Stock</td>
		<td $FX onclick='link(\"nons-quote-view.php\");'>Quotes: Non Stock Details/Edit/Print</td>
		<td $FX onclick='link(\"nons-quote-view.php\");'>Quotes: Non Stock Print PDF/Accept</td>
	</tr>
	</table>";

	$lc["CUSTOMERS"]["REGPROCS"]["STOCK"] = "
	<table id='links_container_data' class='ctable'>
	<tr>
		<td $FX onclick='link(\"corder-new.php\");'>Consignment Order: New</td>
		<td $FX onclick='link(\"corder-view.php\");'>Consignment Orders: Details/Edit/Cancel</td>
		<td $FX onclick='link(\"corder-view.php\");'>Consignment Orders: Print/Invoice</td>
	</tr>
	</table>";

	$lc["CUSTOMERS"]["REGPROCS"]["JOURN"] = "
	<table id='links_container_data' class='ctable'>
	<tr>
		<td $FX onclick='link(\"customers-view.php\");'>Customer Journal</td>
	</tr>
	</table>";

	$lc["CUSTOMERS"]["PROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"customers-view.php\");'>View/Find Customer</td>
			<td $FX onclick='link(\"find-num.php\");'>View Temp/Invoice Number</td>
			<td $FX onclick='link(\"quote-unf-view.php\");'>Quotes: Incomplete Continue/Cancel</td>
			<td $FX onclick='link(\"quote-canc-view.php\");'>Quotes: Canceled View</td>
			<td $FX onclick='link(\"pos-quote-unf-view.php\");'>Quotes: POS Incomplete Continue/Cancel</td>
			<td $FX onclick='link(\"pos-quote-canc-view.php\");'>Quotes: POS Canceled View</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"invoice-view-prd.php\");'>Invoices: Paid Details/Credit Note</td>
			<td $FX onclick='link(\"invoice-view-prd.php\");'>Invoices: Paid Reprint/Delivery Note</td>
			<td $FX onclick='link(\"invoice-canc-view.php\");'>Invoices: Canceled View</td>
			<td $FX onclick='link(\"rec-invoice-view.php\");'>Invoices: Recurring Details/Edit</td>
			<td $FX onclick='link(\"rec-invoice-view.php\");'>Invoices: Recurring Invoice/Remove</td>
			<td $FX onclick='link(\"rec-nons-invoice-view.php\");'>Invoice: Recurring Non Stock Details/Edit/Delete</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"nons-invoice-unf-view.php\");'>Invoices: View Incomplete Non Stock Invoices</td>
			<td style='background: none;'></td>
			<td $FX onclick='link(\"corder-unf-view.php\");'>Consignment Orders: Incomplete Continue/Cancel</td
			<td $FX onclick='link(\"corder-canc-view.php\");'>Consigment Orders: Canceled View</td>
			<td style='background: none;'></td>
			<td $FX onclick='link(\"calc-int.php\");'>Calculate Interest (Monthly Task)</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"sorder-view.php\");'>Sales Orders: Details/Edit</td>
			<td $FX onclick='link(\"sorder-view.php\");'>Sales Orders: Cancel/Print/Invoice</td>
			<td $FX onclick='link(\"sorder-unf-view.php\");'>Sales Orders: Incomplete Continue/Cancel</td>
			<td $FX onclick='link(\"nons-sorder-view.php\");'>Sales Orders: Non-Stock Details/Edit/Print</td>
			<td $FX onclick='link(\"nons-sorder-view.php\");'>Sales Orders: Non-Stock Print PDF/Accept</td>
			<td $FX onclick='link(\"sorder-canc-view.php\");'>Sales Order: Canceled View</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"customers-new.php\");'>Customer: Add</td>
			<td $FX onclick='link(\"customers-view.php\");'>Customers: Details/Add Receipt/Outstanding Stock</t
			<td $FX onclick='link(\"customers-view.php\");'>Customers: Edit/Statement</td>
			<td $FX onclick='link(\"customers-view.php\");'>Customers: Transaction/Print Invoices</td>
			<td $FX onclick='link(\"customers-view.php\");'>Customers: Block/Add Contact</td>
			<td $FX onclick='link(\"customers-email.php\");'>Customers: Email</td>
		</tr>
		</table>";
//<td $FX onclick='link(\"save-age.php\");'>Save Age Analysis</td>
	$lc["CUSTOMERS"]["REPORTS"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"cust-list.php\");'>Print Customer List</td>
			<td $FX onclick='link(\"pdf/pdf-statement.php?key=cusdetailsall\");'>Customers Statements (PDF)</td>
			<td $FX onclick='link(\"invoice-disc-rep.php\");'>Invoice Discounts Report</td>
			<td $FX onclick='link(\"stock-sales-rep-stk.php\");'>Stock Sales Report</td>
			<td $FX onclick='link(\"nons-sales-rep.php\");'>Non-Stock Sales Report</td>
			<td $FX onclick='link(\"sn-sales-rep.php\");'>Total Sales Report</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"coms-report.php\");'>Sales Rep Commission Report</td>
			<td $FX onclick='link(\"reporting/debt-age-analysis.php\");'>Debtors Age Analysis</td>
			<td $FX onclick='link(\"month-end.php\");'>Record month end for age analysis</td>
			<td $FX onclick='link(\"reporting/cust-ledger.php\");'>Debtors Ledger</td>
			<td $FX onclick='link(\"audit/cust-ledger-audit.php\");'>View Previous Year Debtors Ledger</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"label-cust-print.php\");'>Labels: Print</td>
			<td $FX onclick='link(\"label-cust-save.php\");'>Labels: Save</td>
		</tr>
		</table>";

	$lc["CUSTOMERS"]["MAINT"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"pos-user-add.php\");'>Add POS user</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"toms/dept-add.php\");'>Department: Add</td>
			<td $FX onclick='link(\"toms/dept-view.php\");'>Departments: Edit/Remove</td>
			<td $FX onclick='link(\"toms/salesp-add.php\");'>Sales Person: Add</td>
			<td $FX onclick='link(\"toms/salesp-view.php\");'>Sales People: Edit/Remove</td>
			<td $FX onclick='link(\"toms/cat-add.php\");'>Category: Add</td>
			<td $FX onclick='link(\"toms/cat-view.php\");'>Categories: Edit/Remove</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"toms/class-add.php\");'>Classification: Add</td>
			<td $FX onclick='link(\"toms/class-view.php\");'>Classifications: Edit/Remove</td>
			<td $FX onclick='link(\"intbrac-add.php\");'>Interest Bracket: Add</td>
			<td $FX onclick='link(\"intbrac-view.php\");'>Interest Bracket: Edit/Remove</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"email-groups.php\");'>Email User Groups</td>
			<td $FX onclick='link(\"cust-payment-allocate.php\");'>Allocate Customer Receipts</td>
		</tr>
		</table>";

	$lc["CUSTOMERS"]["ADMIN"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"set-state.php\");'>Set Debtors Statement Type</td>
			<td $FX onclick='link(\"set-int-type.php\");'>Set Interest Calculation Method</td>
			<td $FX onclick='link(\"templates/template-settings.php\");'>Invoice / Statement Template Settings</td>
			<td $FX onclick='link(\"core/sales-link.php?type=E&amp;payname=rounding\");'>Set rounding account</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=cc\");'>Set credit card control</td>
			<td $FX onclick='link(\"toms/invid-set.php\");'>Set Invoice Number</td>
			<td $FX onclick='link(\"coms-edit.php\");'>Set Sales Rep Commission</td>
			<td $FX onclick='link(\"pos-setting.php\");'>Point of Sale Rounding</td>
			<td $FX onclick='link(\"default-comments.php\");'>Invoice Default Comments</td>
			<td $FX onclick='link(\"default-pos-comments.php\");'>POS Invoice Default Comments</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"default-stmnt-comments.php\");'>Statement Default Comments</td>
			<td $FX onclick='link(\"dealers/import-customers.php\");'>Import Customers</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"] = array();

	$lc["SUPPLIERS"]["REGPROC"] = "
		<table id='links_container_data' class='ctable'>
		<br /><br />
		<tr>
			<td onclick='set_subaction(this, SUPPLIERS_REGPROC_PURCH);' id='supp_rp_purch'><center><div>Purchases</div></center></td>
			<td onclick='set_subaction(this, SUPPLIERS_REGPROC_ORDER);' id='supp_rp_order'><center><div>Orders To Suppliers</div></center></td>
			<td onclick='set_subaction(this, SUPPLIERS_REGPROC_GOODS);' id='supp_rp_goods'><center><div>Goods Received</div></center></td>
			<td onclick='set_subaction(this, SUPPLIERS_REGPROC_BANK);' id='supp_rp_bank'><center><div>Bank</div></center></td>
			<td onclick='set_subaction(this, SUPPLIERS_REGPROC_JOURN);' id='supp_rp_journ'><center><div>Journal</div></center></td>
		</tr>
		<br /><br />
		</table>";

	$lc["SUPPLIERS"]["REGPROCS"]["PURCH"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"purchase-new.php\");'>Order: New</td>
			<td $FX onclick='link(\"purchase-view.php\");'>Orders: Details/Delete</td>
			<td $FX onclick='link(\"purchase-view.php\");'>Orders: Print/Edit</td>
			<td $FX onclick='link(\"purchase-view.php\");'>Orders: Receive/Record Invoice</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"nons-purchase-new.php\");'>Order: New Non Stock</td>
			<td $FX onclick='link(\"nons-purchase-view.php\");'>Orders: Non Stock Received/Cancel</td>
			<td $FX onclick='link(\"nons-purchase-view.php\");'>Orders: Non Stock Details/Print/Edit</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"purch-int-new.php\");'>Order: New International</td>
			<td $FX onclick='link(\"purch-int-view.php\");'>Orders: International Print/Edit/Cancel</td>
			<td $FX onclick='link(\"purch-int-view.php\");'>Orders: International Receive/Record Invoice</td>
			<td $FX onclick='link(\"purch-int-view-prd.php\");'>Purchases: International Received Details</td>
			<td $FX onclick='link(\"purch-int-view-prd.php\");'>Purchases: International Return/Credit Note</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"purchase-view-prd.php\");'>Purchases: Received Return/Credit Note/Details</td>
			<td $FX onclick='link(\"purchase-view-ret.php\");'>Purchases: Returned Details</td>
			<td style='background: none;'> </td>
			<td $FX onclick='link(\"nons-purch-int-new.php\");'>Order: New International Non Stock</td>
			<td $FX onclick='link(\"nons-purch-int-view.php\");'>Orders: International Non Stock Details/Edit</td>
			<td $FX onclick='link(\"nons-purch-int-view.php\");'>Orders: International Non Stock Received/Cancel</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"]["REGPROCS"]["ORDER"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"supp-view.php\");'>Suppliers: Details/Statement</td>
			<td $FX onclick='link(\"supp-view.php\");'>Suppliers: Payment/Transaction</td>
			<td $FX onclick='link(\"supp-view.php\");'>Suppliers: Edit/Block/Add Contact</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"]["REGPROCS"]["GOODS"] = "
		<table id='links_container_data' class='ctable'>
			<td $FX onclick='link(\"purch-recv-purnum.php\");'>Purchase: Receive</td>
		</table>";

	$lc["SUPPLIERS"]["REGPROCS"]["BANK"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"bank/bank-pay-supp.php\");'>Bank: New Payment To Supplier</td>
			<td $FX onclick='link(\"bank/bank-recpt-supp.php\");'>Bank: Receipt From Supplier</td>
			<td $FX onclick='link(\"bank/petty-recpt-supp.php\");'>Petty Cash: Receive from Supplier</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"]["REGPROCS"]["JOURN"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"supp-view.php\");'>Supplier Journal</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"]["PROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"supp-new.php\");'>Supplier: Add</td>
			<td $FX onclick='link(\"supp-find.php\");'>Supplier: Find</td>
			<td $FX onclick='link(\"bank/petty-pay-supp.php\");'>Petty Cash: Pay Supplier</td>
			<td $FX onclick='link(\"purch-canc-view.php\");'>Orders: Canceled View</td>
			<td $FX onclick='link(\"purch-int-view-ret.php\");'>Purchases: International Returned Details</td>
			<td $FX onclick='link(\"nons-purchase-view-prd.php\");'>Purchases: Non Stock Received Return/Details</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"nons-note-view.php\");'>Purchases: Non Stock Returned Details</td>
			<td $FX onclick='link(\"nons-purch-int-view-prd.php\");'>Purchases: International Non Stock Received</td>
			<td $FX onclick='link(\"lnons-purch-new.php\");'>Order: New Linked Non Stock</td>
			<td $FX onclick='link(\"supp-group-add.php\");'>Supplier Group: Add</td>
			<td $FX onclick='link(\"supp-group-view.php\");'>Supplier Groups: View/Add supplier</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"toms/pricelist-add.php\");'>Pricelist: Add</td>
			<td $FX onclick='link(\"toms/pricelist-view.php\");'>Pricelists: Details/Print/Edit</td>
			<td $FX onclick='link(\"toms/pricelist-view.php\");'>Pricelists: Copy/Remove</td>
			<td $FX onclick='link(\"toms/sup-pricelist-add.php\");'>Supplier Pricelist: Add</td>
			<td $FX onclick='link(\"toms/sup-pricelist-view.php\");'>Supplier Pricelists: Details/Copy</td>
			<td $FX onclick='link(\"toms/sup-pricelist-view.php\");'>Supplier Pricelists: Edit/Remove</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"nons-purch-batch-add.php\");'>Batch Creditors Non-Stock Invoices Add</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"]["REPORTS"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"supp-tran-rep.php\");'>Suppliers Transaction Report</td>
			<td $FX onclick='link(\"reporting/cred-age-analysis.php\");'>Creditors Age Analysis</td>
			<td $FX onclick='link(\"reporting/supp-ledger.php\");'>Creditors Ledger</td>
			<td $FX onclick='link(\"supp-list.php\");'>Print Supplier List</td>
			<td $FX onclick='link(\"audit/supp-ledger-audit.php\");'>View Previous Year Creditors Ledger</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"label-supp-print.php\");'>Labels: Print</td>
			<td $FX onclick='link(\"label-supp-save.php\");'>Labels: Save</td>
		</tr>
		</table>";

	$lc["SUPPLIERS"]["MAINT"] = "
		<table id='links_container_data' class='ctable'>
		<tr>

		</tr>
		<tr>

		</tr>
		</table>";

	$lc["SUPPLIERS"]["ADMIN"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"dealers/import-suppliers.php\");'>Import Suppliers</td>
		</tr>
		</table>";

	$lc["STOCK"] = array();
	$lc["STOCK"]["REGPROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"stock-view.php\");'>Stock Journal</td>
			<td $FX onclick='link(\"stock-view.php\");'>Stock</td>
			<td $FX onclick='link(\"purchase-view.php\");'>Receive Stock</td>
			<td $FX onclick='link(\"purchase-view-prd.php\");'>Return Stock</td>
			<td $FX onclick='link(\"stock-search.php\");'>Stock: Search</td>
			<td $FX onclick='link(\"stock-taking.php\");'>Stock Taking</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"stock-transfer.php\");'>Stock Transfer (store)</td>
			<td $FX onclick='link(\"stock-transit-view.php\");'>View Stock in Transit</td>
		</tr>
		</table>";

	$lc["STOCK"]["PROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"stock-serno-find.php\");'>Find Serial No.</td>
			<td $FX onclick='link(\"pos-pricelist-edit.php\");'>POS Pricelist Edit</td>
		</tr>
		</table>";

	$lc["STOCK"]["REPORTS"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"stock-avail.php\");'>Available Stock</td>
			<td $FX onclick='link(\"stock-lvl-rep.php\");'>Stock Levels</td>
			<td $FX onclick='link(\"stock-sales-rep-stk.php\");'>Stock Sales Report</td>
			<td $FX onclick='link(\"stock-sales-rep.php\");'>Stock Sales Report (By Invoices)</td>
			<td $FX onclick='link(\"stock-move-rep.php\");'>Stock Movement Report</td>
			<td $FX onclick='link(\"reporting/stock-ledger.php\");'>Stock Ledger</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"audit/stock-ledger-audit.php\");'>View Previous Year Stock Ledger</td>
			<td $FX onclick='link(\"label-stock-print.php\");'>Labels: Print</td>
			<td $FX onclick='link(\"label-stock-save.php\");'>Labels: Save</td>
		</tr>
		</table>";

	$lc["STOCK"]["MAINT"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"stock-add.php\");'>Stock: Add</td>
			<td $FX onclick='link(\"stockcat-add.php\");'>Stock Category: Add</td>
			<td $FX onclick='link(\"stockcat-view.php\");'>Stock Categories: Details/Edit</td>
			<td $FX onclick='link(\"stockclass-add.php\");'>Stock Classification: Add</td>
			<td $FX onclick='link(\"stockclass-view.php\");'>Stock Classification: Edit/Remove</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"toms/whouse-add.php\");'>Store: Add</td>
			<td $FX onclick='link(\"toms/whouse-view.php\");'>Stores: Edit/Remove</td>
			<td $FX onclick='link(\"stock-price-inc.php\");'>Increase Selling Price</td>
			<td $FX onclick='link(\"stock-price-dec.php\");'>Decrease Selling Price</td>
		</tr>
		</table>";

	$lc["STOCK"]["ADMIN"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"toms/defwh-set.php\");'>Set Default Store</td>
			<td $FX onclick='link(\"set-vat-type.php\");'>Set Stock Selling price VAT Type</td>
			<td $FX onclick='link(\"set-purch-apprv.php\");'>Set Stock Purchases Approval</td>
			<td $FX onclick='link(\"pos-set.php\");'>Stock Point of Sale Setting</td>
			<td $FX onclick='link(\"core/pchs-link.php?acctype=E&amp;payname=Cost Variance\");'>Set Cost Variance Account</td>
			<td $FX onclick='link(\"dealers/import-stock.php\");'>Import Stock</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"currency-add.php\");'>Add Currency</td>
			<td $FX onclick='link(\"currency-view.php\");'>View Currencies</td>
			<td $FX onclick='link(\"set.php\");'>Set Account Creation</td>
			<td $FX onclick='link(\"vatcodes-add.php\");'>Add VAT Code</td>
			<td $FX onclick='link(\"vatcodes-view.php\");'>View VAT Codes</td>
		</tr>
		</table>";

	$lc["SALARIES"] = array();


	$lc["SALARIES"]["REGPROC"] = "
	<br /><br />
	<table id='links_container_data' class='ctable'>
	<tr>
		<td $FX onclick='link(\"admin-employee-view.php\");'>Pay Salaries (By Batch)</td>
		<td $FX onclick='link(\"salwages/salaries-staff.php\");'>Salaries: Process per Individual</td
		<td onclick='set_subaction(this, SALARIES_REGPROC_SALES);' id='sal_rp_sales'><center><div>HR</div></center></td>
	</tr>
	</table>
	<br /><br />";

	$lc["SALARIES"]["REGPROCS"]["SALES"] = "
	<table id='links_container_data' class='ctable'>
	<tr>
			<td $FX onclick='link(\"salwages/loan_apply.php\");'>Loan: Apply</td>
			<td $FX onclick='link(\"salwages/loan_apply_view.php\");'>Loan: View/Approve</td>
			<td $FX onclick='link(\"salwages/fringeben-add.php\");'>Fringe Benefit: Add</td>
			<td $FX onclick='link(\"salwages/fringebens-view.php\");'>Fringe Benefits: View/Edit</td>
			<td $FX onclick='link(\"grievances-add.php\");'>Add Grievances</td>
			<td $FX onclick='link(\"employee-training-add.php\");'>Staff Training</td>
	</tr>
	<tr>
			<td $FX onclick='link(\"training-provider-add.php\");'>Add Staff Training Provider</td>
			<td $FX onclick='link(\"salwages/employee-leave-apply.php\");'>Leave: Apply</td>
			<td $FX onclick='link(\"salwages/employee-leave-view.php\");'>Leave: Approve/Cancel</td>
			<td $FX onclick='link(\"grievances-view.php?mode=open\");'>View Open Grievances</td>
			<td $FX onclick='link(\"salwages/loans-archive.php\");'>View Archived Loans</td>
			<td $FX onclick='link(\"salwages/employee-onleave.php\");'>View Employees on Leave</td>
	</tr>
	<tr>
			<td $FX onclick='link(\"grievances-view.php?mode=closed\");'>View Closed Grievances</td>
			<td $FX onclick='link(\"training-view.php\");'>View Staff Training</td>
			<td $FX onclick='link(\"training-search.php\");'>Search Staff Training</td>
			<td $FX onclick='link(\"reporting/employee-training-rep.php\");'>Staff Training Report</td>
			<td $FX onclick='link(\"bursary_type_add.php\");'>Add Bursary</td>
			<td $FX onclick='link(\"bursary_type_view.php\");'>View Bursaries</td>
	</tr>
	<tr>
			<td $FX onclick='link(\"bursary_give.php\");'>Give Bursary</td>
			<td $FX onclick='link(\"bursary_give_view.php\");'>View Given Bursaries</td>
			<td style='background: none;'></td>
	</tr>
	<tr>
			<td $FX onclick='link(\"salwages/employee-reports-add.php\");'>HR: Add Employee Report</td>
			<td $FX onclick='link(\"salwages/employee-reports-view.php\");'>HR: View Employee Report</td>
			<td $FX onclick='link(\"salwages/report-type-add.php\");'>HR: New Report Type</td>

	</tr>
	</table>";

	$lc["SALARIES"]["PROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"admin-employee-view.php\");'>View Employees</td>
			<td $FX onclick='link(\"admin-employee-add.php\");'>Add New Employee</td>
			<td $FX onclick='link(\"salwages/salaries-staffr.php\");'>Reverse Salaries</td>
			<td $FX onclick='link(\"recovery/prevemp-salrate.php\");'>Previous Employee Salary Payments</td>
			<td $FX onclick='link(\"admin-employee-view.php\");'>Employee Leaving Company</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"salwages/salded-add.php\");'>Salary Deduction: Add</td>
			<td $FX onclick='link(\"salwages/salded-view.php\");'>Salary Deduction: View/Edit</td>
			<td $FX onclick='link(\"admin-employee-view.php\");'>Employee Journal</td>
			<td $FX onclick='link(\"salwages/rbs-add.php\");'>Reimbursement: Add</td>
			<td $FX onclick='link(\"salwages/rbs-view.php\");'>Reimbursement: View/Edit</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"salwages/subsistence-edit.php\");'>Add Subsistence Allowance</td>
			<td $FX onclick='link(\"salwages/subsistence-view.php\");'>View/Edit Subsistence Allowances</td>
            <td $FX onclick='link(\"salwages/allowance-add.php\");'>Allowance: Add</td>
            <td $FX onclick='link(\"salwages/allowances-view.php\");'>Allowance: View/Edit</td>
		</tr>
		</table>";

	$lc["SALARIES"]["REPORTS"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"salwages/payslips.php\");'>Salaries: View Paid</td>
			<td $FX onclick='link(\"salwages/payslipsr.php\");'>Salaries: View Reversed</td>
			<td $FX onclick='link(\"salwages/payslips.php?key=emp\");'>View Employee Salary</td>
			<td $FX onclick='link(\"admin-lemployee-view.php\");'>View Past Employees</td>
			<td $FX onclick='link(\"salwages/employee-ledger.php\");'>View employee ledger accounts</td>
			<td $FX onclick='link(\"audit/emp-ledger-audit.php\");'>View Previous Year Employee Ledger</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"reporting/leave_report.php\");'>Leave Report</td>
			<td $FX onclick='link(\"salwages/irp5-data.php\");'>All Employee Payslips/Year to Date</td>
			<td $FX onclick='link(\"pdf/irp5-pdf.php\");'>IRP5</td>
		</tr>
		</table>";

	$lc["SALARIES"]["MAINT"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
		</tr>
		</table>";

	$lc["SALARIES"]["ADMIN"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"core/sal-link.php?type=E&amp;payname=salaries\");'>Add Salary Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=I&amp;payname=interestreceived\");'>Add Interest Received Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=salaries%20control\");'>Add Salary Control Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=E&amp;payname=Commision\");'>Add Commission Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=PAYE\");'>Add PAYE Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=UIF\");'>Add UIF Account Link</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/sal-link.php?type=E&amp;payname=pensionexpense\");'>Add Pension Expense Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=E&amp;payname=retireexpense\");'>Add Retirement Annuity Fund Expense Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=E&amp;payname=medicalexpense\");'>Add Medical Aid Expense Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=pension\");'>Add Pension Control Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=retire\");'>Add Retirement Annuity Fund Control Account Link</td>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=medical\");'>Add Medical Aid Control Account Link</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/sal-link.php?type=B&amp;payname=cash\");'>Add Cash Account Link</td>
			<td $FX onclick='link(\"accounttype-add.php\");'>Bank Account Types</td>
			<td $FX onclick='link(\"salwages/settings-acc-edit.php\");'>General Settings</td>
		</tr>
		</tr>
		</table>";

	$lc["LEDGER"] = array();

	$lc["LEDGER"]["REGPROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"core/trans-new.php\");'>Add Journal Transaction</td>
			<td $FX onclick='link(\"core/trans-new-sep.php\");'>Add Journal Transaction (one DR/CR, multiple CR/DR)</td>
			<td $FX onclick='link(\"bank/cashbook-entry.php\");'>Cash Book Entry</td>
			<td $FX onclick='link(\"bank/bank-pay-add.php\");'>Add Bank Payment (non Suppliers)</td>
			<td $FX onclick='link(\"bank/bank-recpt-add.php\");'>Add Bank Receipt (non Customers)</td>
			<td $FX onclick='link(\"bank/multi-bank-pay-add.php\");'>Add Multiple Bank Payment (Split)</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"bank/bank-stmnt.php\");'>Add Multiple Cash Book Entries</td>
			<td $FX onclick='link(\"bank/multi-bank-recpt-add.php\");'>Add Multiple Bank Receipts (Split)</td>
			<td $FX onclick='link(\"core/acc-new-dec.php\");'>Account: Add New</td>
			<td $FX onclick='link(\"core/rectrans-new.php\");'>Add Recurring Journal Transaction</td>
			<td $FX onclick='link(\"core/rectrans-view.php\");'>View Recurring Journal Transactions</td>
			<td $FX onclick='link(\"core/multi-trans.php\");'>Add Multiple Journal Transactions</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/trans-batch-new.php\");'>Add Journal Transaction to Batch</td>
			<td $FX onclick='link(\"core/trans-batch.php\");'>Add Multiple Journal Transactions to Batch</td>
			<td $FX onclick='link(\"bank/petty-req-add.php\");'>Add Petty Cash Requisition</td>
			<td $FX onclick='link(\"bank/petty-req-multi-add.php\");'>Add Multiple Petty Cash Requisitions</td>
			<td $FX onclick='link(\"budget/cfe-add.php\");'>Add Cash Flow Budget Entry</td>
		</tr>
		</table>";

	$lc["LEDGER"]["PROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"reporting/index-reports-banking.php\");'>Bank Reconciliation</td>
			<td $FX onclick='link(\"bank/bank-trans.php\");'>Bank Transfer</td>
			<td $FX onclick='link(\"bank/bank-trans-int.php\");'>Transfer Funds to/from foreign accounts</td>
			<td $FX onclick='link(\"bank/petty-trans.php\");'>Transfer Funds to Petty Cash Account</td>
			<td $FX onclick='link(\"ledger/ledger-new.php\");'>Add High Speed Ledger</td>
			<td $FX onclick='link(\"ledger/ledger-view.php\");'>View High Speed Ledgers</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"bank/pettycashbook-view.php\");'>View Petty Cash Requisistions</td>
			<td $FX onclick='link(\"bank/petty-bank.php\");'>Bank Petty Cash</td>
			<td $FX onclick='link(\"import/import-statement.php\");'>Import bank statement</td>
			<td $FX onclick='link(\"asset-new.php\");'>Asset: Add</td>
			<td $FX onclick='link(\"asset-view.php\");'>Assets: Edit/Depreciation</td>
			<td $FX onclick='link(\"asset-view.php\");'>Assets: Appreciation/Report/Remove</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/acc-view.php\");'>Accounts: Edit/View Transaction</td>
			<td $FX onclick='link(\"core/acc-view.php\");'>Accounts: Delete/Change Category</td>
			<td style='background: none;'></td>
			<td style='background: none;'></td>
			<td $FX onclick='link(\"set-period-use.php\");'>Enter Previous Year/Current Year Transactions</td>
			<td $FX onclick='link(\"core/finyearnames-view.php\");'>Show Current Financial Year</td>
		</tr>
		</table>";

	$lc["LEDGER"]["REPORTS"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"reporting/index-reports.php\");'>View Journal Entries/All Report Options</td>
			<td $FX onclick='link(\"reporting/allcat.php\");'>Show All Accounts/Categories</td>
			<td $FX onclick='link(\"reporting/allcat.php?popup=t\");'>Show All Accounts/Categories (New Window)</td>
			<td $FX onclick='link(\"reporting/index-reports-stmnt.php\");'>Current Year Financial Statements</td>
			<td $FX onclick='link(\"reporting/index-reports-journal.php\");'>Current Year Detailed General Ledger</td>
			<td $FX onclick='link(\"reporting/index-reports-other.php\");'>Other Reports</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"reporting/index-multi-reports.php\");'>Head Office Reports</td>
			<td $FX onclick='link(\"core/batch-view.php\");'>View Batch (Journal)</td>
			<td $FX onclick='link(\"bank/batch-cashbook-view.php\");'>View Batch (Cash Book)</td>
			<td $FX onclick='link(\"bank/cashbook-view.php\");'>View Cash Book</td>
			<td $FX onclick='link(\"bank/pettycash-rep.php\");'>Petty Cash Book Report</td>
			<td $FX onclick='link(\"audit/trial-bal.php\");'>View Year Close Reports (Trial Balance)</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"audit/yr-income-stmnt.php\");'>View Year Close Reports (Income Statement)</td>
			<td $FX onclick='link(\"audit/balance-sheet.php\");'>View Year Close Reports (Balance Sheet)</td>
			<td $FX onclick='link(\"audit/ledger-audit.php\");'>View Previous Year General Ledger</td>
			<td $FX onclick='link(\"audit/ledger-audit-prd.php\");'>View Previous Year General Ledger by Period Range</td>
			<td $FX onclick='link(\"budget/budget-view.php\");'>View Budgets</td>
			<td $FX onclick='link(\"budget/cfe-view.php\");'>View Cash Flow Budget Entries</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"budget/cashflow-report.php\");'>Cash Flow Budget Report</td>
			<td $FX onclick='link(\"costcenter-rep.php\");'>Cost Center Report</td>
			<td $FX onclick='link(\"reporting/auditor_record.php\");'>Auditor Report</td>
			<td $FX onclick='link(\"reporting/report_asa401.php\");'>ASA 401 assistance reports (System CIS Risk Management)</td>
			<td $FX onclick='link(\"core/finyearnames-view.php\");'>View Financial Year Names</td>
			<td $FX onclick='link(\"core/finyearnames-view.php\");'>Show Current Financial Year</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/acc-view.php\");'>Accounts: Edit/View Transaction</td>
			<td $FX onclick='link(\"core/acc-view.php\");'>Accounts: Delete/Change Category</td>
		</tr>
		</table>";

	$lc["LEDGER"]["MAINT"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"bank/creditcard-new.php\");'>Credit Card: Add</td>
			<td $FX onclick='link(\"bank/petrolcard-new.php\");'>Petrol Card: Add</td>
			<td $FX onclick='link(\"bank/bankacct-new.php\");'>Bank Account: Add</td>
			<td $FX onclick='link(\"bank/bankacct-view.php\");'>Bank Account: View/Edit</td>
			<td $FX onclick='link(\"budget/budget-new.php\");'>Monthly Budget: New</td>
			<td $FX onclick='link(\"budget/budget-yr-new.php\");'>Yearly Budget: New</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"assetgrp-new.php\");'>Asset Group: Add</td>
			<td $FX onclick='link(\"assetgrp-view.php\");'>Asset Groups: Edit/Remove</td>
			<td $FX onclick='link(\"core/accat-new.php\");'>Account Category: Add</td>
			<td $FX onclick='link(\"costcenter-add.php\");'>Cost Center: Add</td>
			<td $FX onclick='link(\"costcenter-view.php\");'>Cost Centers: Edit</td>
			<td $FX onclick='link(\"costcenter-tran.php\");'>Add Cost Center Entry</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"costcenter-unallocated.php\");'>Allocate Outstanding Cost Center Data</td>
			<td $FX onclick='link(\"projects-edit.php\");'>Edit Projects And Cost Center Allocation</td>
		</tr>
		</table>";

	$lc["LEDGER"]["ADMIN"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"set-costcenter-use.php\");'>Set Cost Center Usage</td>
			<td $FX onclick='link(\"core/finyearnames-new.php\");'>Set/Show Financial Year Names</td>
			<td $FX onclick='link(\"core/finyearnames-view.php\");'>View Financial Year Names</td>
			<td $FX onclick='link(\"core/finyear-range.php\");'>View Period Range</td>
			<td $FX onclick='link(\"core/finyear-range.php\");'>Set Period Range</td>
			<td $FX onclick='link(\"core/yr-close.php\");'>Close Year</td>
			<td $FX onclick='link(\"set-period-use.php\");'>Enter Previous Year/Current Year Transactions</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"core/cash-link.php\");'>Set Petty Cash Account</td>
			<td $FX onclick='link(\"dealers/import-tb.php\");'>Import Trial Balance</td>
		</tr>
		</table>";



	$lc["BUSINESS"] = array();

	$lc["BUSINESS"]["REGPROC"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"groupware/index.php?script=todo_sub_save.php\");'>Todo</td>
			<td $FX onclick='link(\"req_gen.php\");'>Instant Message: New</td>
			<td $FX onclick='link(\"view_req.php\");'>Instant Messages: View</td>
			<td $FX onclick='link(\"groupware/index.php?script=diary-index.php\");'>Diary (Day View)</td>
			<td $FX onclick='link(\"groupware/index.php?script=diary-index.php?key=month\");'>Diary (Monthly View)</td>
			<td $FX onclick='linkEmail();'>Email</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"groupware/index.php?script=../new_con.php\");'>Contact: Add New</td>
			<td $FX onclick='link(\"list_cons.php\");'>Contacts: View</td>
			<td $FX onclick='link(\"list_cons.php?ref=customers\");'>Contacts: (Customers) View</td>
			<td $FX onclick='link(\"list_cons.php?ref=suppliers\");'>Contacts: (Suppliers) View</td>
			<td $FX onclick='link(\"groupware/index.php?script=doc-add.php\");'>Document: Add New</td>
			<td $FX onclick='link(\"groupware/index.php?script=doc-view.php\");'>Documents: View</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"crmsystem/leads_new.php\");'>Lead: New</td>
			<td $FX onclick='link(\"crmsystem/leads_list.php\");'>Leads: View</td>
			<td $FX onclick='link(\"crm/tokens-list-unall.php\");'>List Unallocated Queries</td>
			<td $FX onclick='link(\"crm/tokens-new.php\");'>Query: New</td>
			<td $FX onclick='link(\"crm/tokens-manage.php\");'>Queries: Manage</td>
			<td $FX onclick='link(\"groupware/index.php?script=today.php\");'>Today</td>
		</tr>
		</table>";

	$lc["BUSINESS"]["PROC"] = "";

	$lc["BUSINESS"]["REPORTS"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"crm/reports-tokens-stats.php\");'>Outstanding Query Statistics</td>
			<td $FX onclick='link(\"crm/reports-tokens-closed.php\");'>Search Closed Queries</td>
			<td $FX onclick='link(\"crm/reports-tokens-closed2.php\");'>List Closed Queries</td>
			<td $FX onclick='link(\"workshop-view.php?key=workshop_report&report=age\");'>Workshop Age Report</td>
			<td $FX onclick='link(\"workshop-view.php?key=workshop_report&report=date\");'>Workshop Date Report</td>
		</tr>
		</table>";

	$lc["BUSINESS"]["MAINT"] = "
		<table id='links_container_data' class='ctable'>
		<tr>
			<td $FX onclick='link(\"docman/doctype-add.php\");'>Add Document type</td>
			<td $FX onclick='link(\"docman/doctype-view.php\");'>View Document types</td>
			<td $FX onclick='link(\"crm/team-add.php\");'>Team: Add</td>
			<td $FX onclick='link(\"crm/team-list.php\");'>View Teams</td>
			<td $FX onclick='link(\"company-export.php\");'>Make Backup</td>
			<td $FX onclick='link(\"company-import.php\");'>Restore Backup</td>
		</tr>
		<tr>
			<td $FX onclick='link(\"crm/tcat-add.php\");'>Query Category: Add</td>
			<td $FX onclick='link(\"crm/tcat-list.php\");'>Query Categories: View</td>
			<td $FX onclick='link(\"crm/action-add.php\");'>Action: Add</td>
			<td $FX onclick='link(\"crm/action-list.php\");'>Actions: View</td>
		</tr>
		</table>";

	$lc["BUSINESS"]["ADMIN"] = "
		<br /><br />
		<table id='links_container_data' class='ctable'>
		<tr>
			<td onclick='set_subaction(this, BUSINESS_ADMIN_CONTENT);' id='buss_rp_cont'><center><div>Content Settings</div></center></td>
			<td onclick='set_subaction(this, BUSINESS_ADMIN_COMPANY);' id='buss_rp_comp'><center><div>Company Settings</div></center></td>
			<td onclick='set_subaction(this, BUSINESS_ADMIN_CUBIT);' id='buss_rp_sett'><center><div>Cubit Settings</div></center></td>
			<td $FX onclick='link(\"cubit_docs.php\");'>Cubit Documents</td>
		</tr>
		</table>
		<br /><br />";

	$lc["BUSINESS"]["ADMINS"]["CONTENT"] = "
		<table id='links_container_data' class='ctable'>
			<tr>
				<td $FX onclick='link(\"crm/crms-allocate.php\");'>Set default user teams</td>
				<td $FX onclick='link(\"crm/crms-list.php\");'>Select multiple teams for a user</td>
				<td $FX onclick='link(\"workshop-settings.php\");'>Workshop Conditions</td>
				<td $FX onclick='link(\"workshop-view.php\");'>Workshop: View</td>
				<td $FX onclick='link(\"workshop-add.php\");'>Workshop: Add to</td>
			</tr>
			<tr>
				<td $FX onclick='link(\"setup.php\");'>Quick Setup</td>
				<td $FX onclick='link(\"admin-usradd.php\");'>Add User</td>
				<td $FX onclick='link(\"admin-usrview.php\");'>View User</td>
				<td $FX onclick='link(\"admin-deptadd.php\");'>Add User Department</td>
				<td $FX onclick='link(\"admin-deptview.php\");'>View User Department</td>
			</tr>
		</table>";

	$lc["BUSINESS"]["ADMINS"]["COMPANY"] = "
		<table id='links_container_data' class='ctable'>
			<tr>
				<td $FX onclick='link(\"company-new.php\");'>Add New Company</td>
				<td $FX onclick='link(\"company-view.php\");'>View Companies</td>
				<td $FX onclick='link(\"compinfo-view.php\");'>Company Details</td>
				<td $FX onclick='link(\"core/finyearnames-new.php\");'>Set/Show Financial Year Names</td>
			</tr>
			<tr>
				<td $FX onclick='link(\"core/yr-close.php\");'>Close Year</td>
				<td $FX onclick='link(\"company-import.php\");'>Import Company</td>
				<td $FX onclick='link(\"company-export.php\");'>Export Company</td>
				<td $FX onclick='link(\"set-inv-bankdetails.php\");'>Set Company Banking Details</td>

			</tr>
			<tr>
				<td $FX onclick='link(\"import/import-settings.php\");'>Statement Import Settings</td>
			</tr>
		</table>";

	$lc["BUSINESS"]["ADMINS"]["CUBIT"] = "
		<table id='links_container_data' class='ctable'>
			<tr>
				<td $FX onclick='selflink(\"popup=true\");'>Navigation Opens in New Window</td>
				<td $FX onclick='selflink(\"popup=false\");'>Navigation Opens in Same Window</td>
				<td $FX onclick='link(\"printing.php\");'>Printing Options</td>
				<td $FX onclick='link(\"admin-usrpasswd.php\");'>Password</td>

				<td $FX onclick='link(\"register.php\");'>Register Cubit</td>
				<td $FX onclick='parentlink(\"logout.php\");'>Logout</td>
			</tr>
			<tr>
				<td $FX onclick='link(\"email-settings.php\");'>Email Settings</td>
				<td $FX onclick='link(\"set-view.php\");'>View Settings</td>
				<td $FX onclick='link(\"splash.php\");'>Change Splash Screen</td>
				<td $FX onclick='link(\"set-login-retries.php\");'>Set Login Retries</td>
				<td $FX onclick='link(\"set-cust-inv-warn.php\");'>Set customer credit limit response</td>
			</tr>
			<tr>
				<td $FX onclick='link(\"toms/xrate-change.php\");'>Update Exchange Rate</td>
				<td $FX onclick='link(\"core/finyearnames-view.php\");'>View Year Names</td>
				<td $FX onclick='link(\"core/finyear-range.php\");'>View Period Range</td>
				<td $FX onclick='link(\"core/finyear-range.php\");'>Set Period Range</td>
				<td $FX onclick='link(\"set-currency-symbol.php\");'>Set Currency Symbol</td>
				<td $FX onclick='link(\"ch.php\");'>Cubit Version Information</td>
			</tr>
		</table>";

	return $lc;
}

/* returns true if navigation opens in a new window */
function getNavLinkTarget() {
	db_conn("cubit");
	$sql = "SELECT LOWER(SUBSTR(value, 1, 1)) FROM settings WHERE constant='NAVLINK_TARGET'";
	$rslt = db_exec($sql) or errDie("Error reading navigation link target.");

	if (pg_num_rows($rslt) <= 0) {
		$sql = "INSERT INTO settings (constant, label, value, type, datatype, minlen, maxlen, div, readonly)
				VALUES('NAVLINK_TARGET', 'Home Navigation Opens in New Window', 'Yes', 'layout', 'string', '2', '3',
						0, 't')";
		$rslt = db_exec($sql) or errDie("Error updating navigation link target (INS).");

		$nlt = "y";
	} else {
		$nlt = pg_fetch_result($rslt, 0, 0);
	}

	return (($nlt == "y") ? "1" : "0");
}

/* sets whether the navigation links open in new window */
function setNavLinkTarget($setting) {
	$setting = ($setting == "true" ? "Yes" : "No");

	db_conn("cubit");
	$sql = "UPDATE settings SET value='$setting' WHERE constant='NAVLINK_TARGET'";
	$rslt = db_exec($sql) or errDie("Error updating navigation link target (UPD).");
}

function show_logo() {
	$sqlpic = "SELECT img,imgtype FROM cubit.compinfo WHERE div='".USER_DIV."' LIMIT 1";
	$imgRsltpic = db_exec ($sqlpic) or errDie ("Unable to retrieve image from database");
	$imgBin = pg_fetch_array ($imgRsltpic);

	$img = base64_decode($imgBin["img"]);
	$mime = $imgBin["imgtype"];

	header("Content-Type: ". $mime ."\n");
	header("Content-Transfer-Encoding: binary\n");
	header("Content-length: " . strlen ($img) . "\n");

	print $img;
}

?>

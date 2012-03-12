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
require_lib("ext");
require_lib("validate");

if (isset($_GET['addcontact'])) {
	$OUTPUT = AddContact($_GET);
	$OUTPUT .= printCust();
} else if (isset($_REQUEST["key"]) && $_REQUEST["key"] == "select") {
	$OUTPUT = select();
} else if (isset($_POST["export"])) {
	$OUTPUT = export($_POST);
} else {
	$OUTPUT = printCust();
}

require ("template.php");



/* does the update for external form fields depending on the selection from viewcust */
function select()
{

	$cusnum = $_REQUEST["cusnum"];
	$OUT = frmupdate_exec(array($cusnum));
	return $OUT;

}



function printCust ()
{

	global $_SESSION;
	extract($_REQUEST);

	$fields = array();
	$fields["action"] = "listcust";
	$fields["fval"] = "[_BLANK_]";
	$fields["filter"] = "surname";
	$fields["offset"] = 0;
	
	extract ($fields, EXTR_SKIP);

	if (isset($cids) && isset($button["email"])) {
		$get = "";
		foreach ($cids as $key=>$value) {
			$get .= "&cids[$key]=$value";
		}

		header("Location: statements-email.php?a=q$get");
	}



	/* session var prefix */
	$SPRE = "custview_";

	/* max number of customers in list */
	if (isset($viewall_cust)) {
		$offset = 0;
		define("ACT_SHOW_LIMIT", 2147483647);
	} else {
		define("ACT_SHOW_LIMIT", SHOW_LIMIT);
	}

	if($filter == "all"){
		$sqlfilter = " AND 
			(lower(accno) LIKE lower('%$fval%') OR lower(surname) LIKE lower('%$fval%') OR 
			lower(paddr1) LIKE lower('%$fval%') OR lower(addr1) LIKE lower('%$fval%') OR 
			lower(del_addr1) LIKE lower('%$fval%') OR lower(bustel) LIKE lower('%$fval%') OR 
			lower(email) LIKE lower('%$fval%') OR lower(vatnum) LIKE lower('%$fval%') OR 
			lower(contname) LIKE lower('%$fval%') OR lower(tel) LIKE lower('%$fval%') OR 
			lower(cellno) LIKE lower('%$fval%') OR lower(fax) LIKE lower('%$fval%') OR 
			lower(url) LIKE lower('%$fval%') OR lower(comments) LIKE lower('%$fval%') OR 
			lower(bankname) LIKE lower('%$fval%') OR lower(branname) LIKE lower('%$fval%') OR 
			lower(brancode) LIKE lower('%$fval%') OR lower(bankaccno) LIKE lower('%$fval%') OR 
			lower(bankaccname) LIKE lower('%$fval%') OR lower(bankacctype) LIKE lower('%$fval%'))";
	}else {
		if ($filter == "class"){
			#get classids that match
			db_conn ('exten');
			$get_ids = "SELECT clasid FROM class WHERE classname ILIKE '%$fval%'";
			$run_ids = db_exec($get_ids) or errDie ("Unable to get class information.");
			if (pg_numrows($run_ids) > 0){
				$cids = array ();
				while ($carr = pg_fetch_array ($run_ids)){
					$cids[] = "class = '$carr[clasid]'";
				}
				$cids_string = implode (" OR ",$cids);
				$sqlfilter = " AND ($cids_string)";
			}
		}elseif ($filter == "category"){
			#get catids that match
			db_conn ('exten');
			$get_ids = "SELECT catid FROM categories WHERE category ILIKE '%$fval%'";
			$run_ids = db_exec($get_ids) or errDie ("Unable to get class information.");
			if (pg_numrows($run_ids) > 0){
				$cids = array ();
				while ($carr = pg_fetch_array ($run_ids)){
					$cids[] = "category = '$carr[catid]'";
				}
				$cids_string = implode (" OR ",$cids);
				$sqlfilter = " AND ($cids_string)";
			}
		}else {
			$sqlfilter = " AND lower($filter) LIKE lower('%$fval%')";
		}
	}

	if ($fval == "[_BLANK_]") $fval = "";

	$filterarr = array(
		"all" => "Detailed", 
		"surname" => "Company/Name", 
		"init" => "Initials", 
		"registration" => "ID/Registration", 
		"accno" => "Account Number", 
		"deptname" => "Department", 
		"category" => "Category", 
		"class" => "Classification"
	);
	$filtersel = extlib_cpsel("filter", $filterarr, $filter, "onChange='applyFilter();'");

	if (isset($export)) {
		$pure = true;
	} else {
		$pure = false;
	}

	if(isset($nozerobal) AND $nozerobal == "yes"){
		$showzerobal = "AND balance > 0";
		$zerosel = "checked='yes'";
	}else {
		$showzerobal = "";
		$zerosel = "";
		$nozerobal = "";
	}

	if (!$pure) {
		# Set up table to display in
		$printCust_begin = "
		    <h3>".(isset($findcust)?"Find":"Current")." Customers</h3>
			<form method='POST' action='".SELF."' name='form1'>
			<table ".TMPL_tblDflts.">
				<input type='hidden' name='action' value='$action' />
				<tr>
					<th>.: Filter :.</th>
					<th colspan='2'>.: Search :.</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$filtersel</td>
					<td><input type='text' size='20' name='fval' value='$fval'></td>
					<td align='center'><input type='submit' value='Search' /></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'><input type='submit' name='button[all]' value='View All' /></td>
					<td><input type='checkbox' name='nozerobal' value='yes' $zerosel> No Zero Balances</td>
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
					ajaxRequest('".SELF."', 'cust_list', AJAX_SET, 'all=t');
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
	} else {
		$printCust_begin = "";
	}

	/* FIND CUSTOMER START */
	if (!isset($findcust)) {
		$ajaxCust = "";

		if (!$pure) {
			$ajaxCust .= "
				<form action='statements-email.php' method='GET'>
				<input type='hidden' name='key' value='confirm' />";
		}

		# connect to database
		db_connect();

		# counting the number of possible entries
		$sql = "SELECT * FROM customers WHERE (div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') $sqlfilter ORDER BY surname ASC";
		$rslt = db_exec($sql) or errDie("Error counting matching customers.");
		$custcount = pg_num_rows($rslt);

		# Query server
		$tot = 0;
		$totoverd = 0;
		$i = 0;

		if(!isset($ajaxCust)) {
			$ajaxCust = "";
		}

		/* view offsets */
		if ($offset > 0) {
			$poffset = ($offset >= ACT_SHOW_LIMIT) ? $offset - ACT_SHOW_LIMIT : 0;
			$os_prev = "<a class='nav' href='".SELF."?offset=$poffset&fval=$fval&filter=$filter&nozerobal=$nozerobal'>Previous</a>";
		} else {
			$os_prev = "&nbsp;";
		}

		if (($offset + ACT_SHOW_LIMIT) > $custcount) {
			$os_next = "&nbsp;";
		} else {
			$noffset = $offset + ACT_SHOW_LIMIT;
			$os_next = "<a class='nav' href='".SELF."?offset=$noffset&fval=$fval&filter=$filter&nozerobal=$nozerobal'>Next</a>";
		}

		if ($os_next != "&nbsp;" || $os_prev != "&nbsp;") {
			$os_viewall = "";//| <a class='nav' href='javascript: updateOffset(false, true);'>View All</a>";
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
					".($pure?"":"<th colspan='12'>Options</th>")."
				</tr>";

	//		<th>Overdue</th>
		/* query object for cashbook */
		$cashbook = new dbSelect("cashbook", "cubit");
		$custRslt = new dbSelect("customers", "cubit", grp(
			m("where", "(div ='".USER_DIV."' or ddiv='".USER_DIV."') $sqlfilter $showzerobal"),
			m("order", "surname ASC"),
			m("offset", $offset),
			m("limit", ACT_SHOW_LIMIT)
		));
		$custRslt->run();

		if ($custRslt->num_rows() < 1) {
			$ajaxCust .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='20'>
						<li>There are no Customers matching the criteria entered.</li>
						<li>Please redefine your search.</li>
					</td>
				</tr>";
		}else{
			while ($cust = $custRslt->fetch_array()) {

				if (!user_in_team($cust["team_id"], USER_ID)) {
					continue;
				}

				# Check type of age analisys
	//			if(div_isset("DEBT_AGE", "mon")){
	//				$overd = ageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
	//			}else{
	//				$overd = age($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
	//			}

	//			if ($overd < 0) {
	//				$overd = 0;
	//			}

	//			if ($overd > $cust['balance']) {
	//				$overd = $cust['balance'];
	//			}

				if ($cust["location"] == "int") {
					$cur = qryCurrency($cust["fcid"], "rate");
					$rate = $cur["rate"];

	//				if ($rate != 0) {
	//					$totoverd += $overd * $rate;
	//				} else {
	//					$totoverd += $overd;
	//				}
				} else {
	//				$totoverd += $overd;
				}

				if (!$pure) {
					/* check if customer may be removed */
					$cashbook->setOpt(grp(
						m("where", "cusnum='$cust[cusnum]' AND banked='no' AND div='".USER_DIV."'")
					));
					$cashbook->run();

					if ($cashbook->num_rows() <= 0 && $cust['balance'] == 0){
						$rm = "<td><a href='cust-rem.php?cusnum=$cust[cusnum]'>Remove</a></td>";
					} else {
						$rm = "<td></td>";
					}
				}

				if(strlen(trim($cust['bustel']))<1) {
					$cust['bustel'] = $cust['tel'];
				}

	//			$cust['balance'] = sprint($cust['balance']);
				#for whatever reason, this balance is not reliable ... rather get amount from stmnt
				$get_bal = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cust[cusnum]'";
				$run_bal = db_exec($get_bal) or errDie ("Unable to get customer balance.");
				if (pg_numrows($run_bal) < 1){
					$cust['balance'] = sprint (0);
				}else {
					$cust['balance'] = sprint(pg_fetch_result ($run_bal,0,0));
				}

				if ($cust["location"] == "int") {
					if ($rate != 0.00) {
						$tot = $tot + ($cust['fbalance'] * $rate);
					} else {
						$tot = $tot + ($cust['balance']);
					}
				} else {
					$tot = $tot + $cust['balance'];
				}

				/* determine which template to use when printing customer invoices */
				if (templateScript("invoices") != "pdf/cust-pdf-print-invoices.php") {
					$template = "pdf/pdf-tax-invoice.php?type=cusprintinvoices";
				} else {
					$template = "pdf/pdf-tax-invoice.php?type=cusprintinvoices";
				}

				$inv = "<td><a href='$template&cusnum=$cust[cusnum]' target='_blank'>Print Invoices</a></td>";

				# Locations drop down
				$locs = array("loc" => "Local", "int" => "International", "" => "");
				$loc = $locs[$cust['location']];

				$fbal = "--";
				$ocurr = CUR;
				$trans = "<td><a href='core/cust-trans.php?cusnum=$cust[cusnum]'>Transaction</a></td>";

				if($cust['location'] == 'int'){
					$cust['balance'] = sprint ($rate*$cust['balance']);
					$fbal = "$cust[currency] $cust[fbalance]";
					$ocurr = CUR;
					$trans = "<td><a href='core/intcust-trans.php?cusnum=$cust[cusnum]'>Transaction</a></td>";
					$receipt="<a href='bank/bank-recpt-inv-int.php?cusid=$cust[cusnum]&amp;cash=yes'>Add Receipt</a>";
				} else {
					$receipt="<a href='bank/bank-recpt-inv.php?cusnum=$cust[cusnum]&amp;cash=yes'>Add Receipt</a>";
				}

				# alternate bgcolor
				$bgColor = bgcolor($i);
				$ajaxCust .= "<tr bgcolor='$bgColor'>";

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
					<td align='center' nowrap>$fbal</td>";

	//<td align='right' nowrap>$ocurr $overd</td>

				if (!$pure) {
					if ($action == "listcust") {
						$ajaxCust .= "
							<td>$receipt</td>
							<td><a href='delnote-report.php?cusnum=$cust[cusnum]'>Outstanding Stock</a></td>
							<td><a href='cust-det.php?cusnum=$cust[cusnum]'>Details</a></td>
							<td><a href='customers-new.php?cusnum=$cust[cusnum]'>Edit</a></td>
							<td><a href='#' onClick='openPrintWin(\"cust-stmnt.php?cusnum=$cust[cusnum]\");'>Statement</a></td>
							$trans $inv";

						if($cust['blocked'] == 'yes'){
							$ajaxCust .= "<td><a href='cust-unblock.php?cusnum=$cust[cusnum]'>Unblock</a></td>";
						}else{
							$ajaxCust .= "<td><a href='cust-block.php?cusnum=$cust[cusnum]'>Block</a></td>";
						}

						$ajaxCust .= "<td><a href='transheks/pricelist_send.php?cusnum=$cust[cusnum]'>Send Pricelist</a></td>";

						$ajaxCust .= "
							$rm
							<td><a href='conper-add.php?type=cust&amp;id=$cust[cusnum]'>Add Contact</a></td>
							<td><a href='#' onClick='popupSized(\"customers-note.php?cusnum=$cust[cusnum]\",\"cusnote$cust[cusnum]\",470,340)'>Notes</a></td>
							<td><input type='checkbox' name='cids[]' value='$cust[cusnum]' /></td>";
					} else {
						$ajaxCust .= "
							<td align='center'>
								<a href='javascript:popupSized(\"cust-det.php?cusnum=$cust[cusnum]\", \"custdetails\", 550, 400, \"\");'>Details</a>
							</td>";
					}
				}

				$ajaxCust .= "</tr>";
			}

			$bgColor = bgcolor($i);
			$tot = sprint($tot);
			$totoverd = sprint($totoverd);

			$i--;
	//			<td align='right' nowrap>".CUR." $totoverd</td>
			$ajaxCust .= "
				<tr bgcolor='$bgColor'>
					<td colspan='5'>Total Amount Outstanding, from $i ".($i > 1 ? "clients" : "client")."</td>
					<td align='right' nowrap>".CUR." $tot</td>
					<td></td>
					".($pure?"":"<td colspan='11' align='right'><input type='submit' name='button[email]' value='Email Statements' /></td>")."
				</tr>";

			if (!$pure) {
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
		}

		if ($pure) {
			$ajaxCust .= "</table>";
		} else {
			$ajaxCust .= "
				".TBL_BR."
			</table>
			</form>
			<form action='".SELF."' method='POST'>
			<table>
				<input type='hidden' name='export' value='yes' />
				<input type='hidden' name='filter' value='$filter' />
				<input type='hidden' name='fval' value='$fval' />
				<tr>
					<td colspan='3'><input type='submit' value='Export to Spreadsheet' /></td>
				</tr>
			</table>
			</form>
			<script>
				document.form1.fval.focus();
			</script>";
		}
	/* FIND CUSTOMER END */
	} else {
		$ajaxCust = "
			<script>
				document.form1.fval.focus();
			</script>";
	}

	$printCust_end = "</div>";

	if (!$pure) {
		$printCust_end .=
			mkQuickLinks(
				ql("customers-new.php", "Add New Customer")
			);
	}

	if (AJAX) {
		return $ajaxCust;
	} else {
		return "$printCust_begin$ajaxCust$printCust_end";
	}

}



function export()
{

	$OUT = clean_html(printCust());
	require_lib("xls");
	StreamXLS("CustomerList", $OUT);

}



// adds the customer to the contact list
function AddContact()
{

	global $_GET;

	$v = & new Validate();
	if ( ! $v->isOk($_GET["addcontact"], "num", 1, 9, "") ) 
		return "Invalid Customer Number";

	// check if supplier can be added to contact list
	$rslt = db_exec("SELECT * FROM cons WHERE cust_id='$_GET[addcontact]'");
	if ( pg_numrows($rslt) >= 1 ) {
		return "Customer Already Added as a Contact<br>";
	}

	// get it from the db
	$sql = "SELECT * FROM customers WHERE cusnum='$_GET[addcontact]'";
	$rslt = db_exec($sql) or errDie("Unable to add customer to contact list. (RD)", SELF);
	if ( pg_numrows($rslt) < 1 )
		return "Unable to add customer to contact list. (RD2)";

	$data = pg_fetch_array($rslt);

	extract($data);

	if ( isset($_GET["addcontact_as"]) && $_GET["addcontact_as"] == "Company" ) {
		$company = "$surname";
		$surname = "";
	} else {
		$company = "";
	}

	// put it in the db
	$sql = "
		INSERT INTO cons (
			name, surname, comp, ref, tell, cell, fax, email, hadd, padd, 
			date, cust_id, con, by, div
		) VALUES (
			'$cusname', '$surname', '$company', 'Customer', '$bustel', '$cellno', '$fax', '$email', '$addr1', '$paddr1', 
			CURRENT_DATE, '$cusnum', 'No', '".USER_NAME."', '".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie("Unable to add customer to contact list", SELF);

	if (pg_cmdtuples($rslt) < 1) {
		return "<li class='err'>Unable to add customer to contact list.</li>";
	}

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
<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

require("settings.php");

if (isset($_POST["emailsavepage_action"])) {
	if ($_POST["emailsavepage_action"] == "save") {
		$fname = preg_replace("/.php\$/", "", $_POST["emailsavepage_name"]);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=$fname.html");
		print base64_decode($_POST["emailsavepage_content"]);
		exit;
	} else {
		switch ($_POST["emailsavepage_key"]) {
			case "content_supplied":
				$OUTPUT = get_recip();
				break;
			case "gather_emails":
				$OUTPUT = gather_emails();
				break;
			case "sendmails":
				$OUTPUT = send_mails();
				break;
			default:
				invalid_use();
		}
	}
} else if (AJAX) {
	$OUTPUT = get_recip();
} else {
	invalid_use();
}

require("template.php");



function get_recip()
{

	global $_SESSION;
	extract($_REQUEST);

	if (!AJAX) {
		$content = $_POST["emailsavepage_content"];
	}

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

	if (!isset($all) && isset($_SESSION["${SPRE}all"]) && !isset($filter) && !isset($fval)) {
		$all = $_SESSION["${SPRE}all"];
	}

	if(isset($filter) && isset($fval) && !isset($all)){
		if($filter == "all")
			$filter = "surname";
		if(AJAX)
			$sqlfilter = " AND lower($filter) LIKE lower('%$fval%')";
		else 
			$sqlfilter = " AND FALSE";
		if (isset($_SESSION["${SPRE}all"])) unset($_SESSION["${SPRE}all"]);
		$_SESSION["${SPRE}fval"] = $fval;
		$_SESSION["${SPRE}filter"] = $filter;
	} else {
		if (isset($_SESSION["${SPRE}fval"])) {unset($_SESSION["${SPRE}fval"]);}
		if (isset($_SESSION["${SPRE}filter"])) unset($_SESSION["${SPRE}filter"]);
		$filter = "";
		$fval = "";
		$_SESSION["${SPRE}all"] = "true";
		$sqlfilter = "";
	}

	$filterarr = array("surname" => "Company/Name", "init" => "Initials", "accno" => "Account Number", "deptname" => "Department", "category"=>"Category", "class"=>"Classification");
	$filtersel = extlib_cpsel("filter", $filterarr, $filter, "onChange='applyFilter();'");

	if(!isset($custom_address))
		$custom_address = "";

	# Set up table to display in
	if (!AJAX) {
		$printCust_begin = "
		<h3>Please select who you wish to send this page to:</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='emailsavepage_key' value='gather_emails'>
			<input type='hidden' name='emailsavepage_action' value=''>
			<input type='hidden' name='emailsavepage_name' value='$emailsavepage_name' />
			<input type='hidden' name='emailsavepage_content' value='$content'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='10'>Customers</th>
			</tr>
			<tr>
				<th>Customer Name</th>
				<th>Email Address</th>
				<th>Select</th>
			</tr>
			<input type='hidden' name='action' value='$action'>
			<tr>
				<th>.: Filter :.</th>
				<th>.: Value :.</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$filtersel</td>
				<td><input type='text' size='20' id='fval' value='$fval' onKeyUp='applyFilter();'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='button' name='all' value='View All' onClick='viewAll();'></td>
				<td align='center'><input type='button' value='Apply Filter' onClick='applyFilter();'></td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Send To This Address</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' size='35' name='custom_address' value='$custom_address'></td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Send Email Member Of This Group</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='show' onClick='applyFilter();'> Send To Customer</td>
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
				ajaxRequest('".SELF."', 'cust_list', AJAX_SET, 'emailsavepage_key=content_supplied&all=t');
			}

			function applyFilter() {
				filter = getObject('filter').value;
				fval = getObject('fval').value;

				ajaxRequest('".SELF."', 'cust_list', AJAX_SET, 'emailsavepage_key=content_supplied&filter=' + filter + '&fval=' + fval);
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
	}

	if (!isset($offset) && isset($_SESSION["${SPRE}offset"])) {
		$offset = $_SESSION["${SPRE}offset"];
	} else if (!isset($offset)) {
		$offset = 0;
	}

	$_SESSION["${SPRE}offset"] = $offset;

	# connect to database
	db_connect();

	# counting the number of possible entries
	$sel = grp(
    	m("where", "(div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') $sqlfilter")
    );
	$customers = new dbSelect("customers", "cubit", $sel);
	$customers->run();
	$custcount = $customers->num_rows();

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

	if(!isset($ajaxCust))
		$ajaxCust = "";

	if(!isset($pure))
		$pure = "";

	$ajaxCust .= "
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='9' align='right'><input type='submit' value='Send Emails'></td>
			</tr>
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

    $sel = grp(
    	m("order", "surname ASC"),
    	m("offset", $offset),
    	m("limit", ACT_SHOW_LIMIT),
    	m("where", "(div = '".USER_DIV."' OR  ddiv = '".USER_DIV."') $sqlfilter")
    );
	$customers = new dbSelect("customers", "cubit", $sel);
	$customers->run();

	if ($customers->num_rows() < 1) {
		$ajaxCust .= "
		<tr class='".bg_class()."'>
			<td colspan='20'><li>There are no Customers matching the criteria entered.</li></td>
		</tr>";
	}else{
		while ($cust = $customers->fetch_array()) {
			# Check type of age analisys
			if(div_isset("DEBT_AGE", "mon")){
				$overd = ageage($cust['cusnum'], ($cust['overdue']/30) - 1, $cust['location']);
			}else{
				$overd = age($cust['cusnum'], ($cust['overdue'])- 1, $cust['location']);
			}

			if($overd < 0) {
				$overd = 0;
			}

			if($overd > $cust['balance']) {
				$overd = $cust['balance'];
			}

			if ($cust["location"] == "int") {
				db_conn("cubit");
				$sql = "SELECT rate FROM currency WHERE fcid='$cust[fcid]'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve currency rate from Cubit.");
				$rate = pg_fetch_result($rslt, 0);

				if ($rate != 0) {
					$totoverd += $overd * $rate;
				} else {
					$totoverd += $overd;
				}
			} else {
				$totoverd += $overd;
			}

			if(strlen(trim($cust['bustel'])) < 1) {
				$cust['bustel'] = $cust['tel'];
			}

			$cust['balance'] = sprint($cust['balance']);

			if ($cust["location"] == "int") {
				db_conn("cubit");
				$sql = "SELECT rate FROM currency WHERE fcid='$cust[fcid]'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve currency rate from Cubit.");
				$rate = pg_fetch_result($rslt, 0);

				if ($rate != 0.00) {
					$tot = $tot + $cust['balance'] * $rate;
				} else {
					$tot = $tot + $cust['balance'];
				}
			} else {
				$tot = $tot + $cust['balance'];
			}

			$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
			$ts_rslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
			$template = pg_fetch_result($ts_rslt, 0);

			if ($template != "pdf/pdf-tax-invoice.php") {
				$template = "pdf/invoice-pdf-cust.php";
			}

			$inv = "";
			$inv = "
			<td>
				<a href='$template?cusnum=$cust[cusnum]&type=cusprintinvoices' target='_blank'>Print Invoices</a>
			</td>";

			# Locations drop down
			$locs = array("loc"=>"Local", "int"=>"International", "" => "");
			$loc = $locs[$cust['location']];

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

			$fbal = "$sp4--$sp4";
			$ocurr = CUR;


			$ajaxCust .= "<tr class='".bg_class()."'>";

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
					<td align='right'>$ocurr $cust[balance]</td>
					<td align='right'>$fbal</td>
					<td align='right'>$ocurr $overd</td>";

			if ( $action == "listcust" ) {
				$ajaxCust .= "
					<input type='hidden' name='surnames[$cust[cusnum]]' value='$cust[surname]'>
					<td><input type='checkbox' name='emailcust[$cust[cusnum]]' value='$cust[email]'></td>
					<td><a href='delnote-report.php?cusnum=$cust[cusnum]' target='_blank'>Outstanding Stock</a></td>
					<td><a href='cust-det.php?cusnum=$cust[cusnum]' target='_blank'>Details</a></td>
					<td><a href='cust-edit.php?cusnum=$cust[cusnum]' target='_blank'>Edit</a></td>
					<td><a href='#' onClick='openPrintWin(\"cust-stmnt.php?cusnum=$cust[cusnum]\");'>Statement</a></td>
					$inv";

				if($cust['blocked'] == 'yes'){
					$ajaxCust .= "<td><a href='cust-unblock.php?cusnum=$cust[cusnum]' target='_blank'>Unblock</a></td>";
				}else{
					$ajaxCust .= "<td><a href='cust-block.php?cusnum=$cust[cusnum]' target='_blank'>Block</a></td>";
				}

				$ajaxCust .= "
				</tr>";
			} else {
				$ajaxCust .= "
					<td align='center'>
						<a href='javascript: popupSized(\"cust-det.php?cusnum=$cust[cusnum]\", \"custdetails\", 550, 400, \"\");'>Details</a>
					</td>";
			}

			$i++;
		}
		if ($i > 1){$s = "s";} else {$s = "";}

		$tot = sprint($tot);
		$totoverd = sprint($totoverd);
		$ajaxCust .= "
			<tr class='".bg_class()."'>
				<td colspan='5'>Total Amount Outstanding, from $i client$s </td>
				<td align='right' nowrap>".CUR." $tot</td>
				<td></td>
				<td align='right' nowrap>".CUR." $totoverd</td>
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
			".TBL_BR."
			<tr>
				<td colspan='9' align='right'><input type='submit' value='Send Emails'></td>
			</tr>
		</table>
		</form>";

	if (!AJAX) {
		$printCust_end = "
		</div>
	    <p>
		<table ".TMPL_tblDflts." width='15%'>
	       ".TBL_BR."
	        <tr>
	        	<th>Quick Links</th>
	        </tr>
			<tr class='".bg_class()."'>
				<td><a href='customers-new.php'>Add Customer</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}

	if (AJAX) {
		return $ajaxCust;
	} else {
		return "$printCust_begin$ajaxCust$printCust_end";
	}
	return $OUT;

}



function gather_emails()
{

	extract($_POST);

	if (!isset($emailsavepage_subject)) {
		$emailsavepage_subject = "";
	}

	if (!isset($emailcust) AND (!isset($custom_address) OR (strlen($custom_address) < 1))) {
		return get_recip();
	}

	$OUT = "
	<h3>Send Page</h3>
	<form method='POST' action='".SELF."'>
		<input type='hidden' name='emailsavepage_key' value='sendmails'>
		<input type='hidden' name='emailsavepage_action' value=''>
		<input type='hidden' name='emailsavepage_name' value='$emailsavepage_name' />
		<input type='hidden' name='emailsavepage_content' value='$emailsavepage_content'>
	<table ".TMPL_tblDflts.">
		<tr
			<th colspan='2'>Subject: <input type='text' size='40' name='emailsavepage_subject' value='$emailsavepage_subject'></th>
		</tr>
		".TBL_BR."
		<tr>
			<th>Customer Name</th>
			<th>Email</td>
		</tr>";

	if(isset($custom_address) AND (strlen($custom_address) > 0)){
		$OUT .= "
					<tr class='".bg_class()."'>
						<td><input type='text' size='30' name='surnames[custom_address]' value=''></td>
						<td><input type='text' size='30' name='emailcust[custom_address]' value='$custom_address'></td>
					</tr>
				";
	}

	if(isset($emailcust))
		foreach ($emailcust as $cusnum => $email) {
			$surname = $surnames[$cusnum];
			$OUT .= "
			<input type='hidden' name='surnames[$cusnum]' value='$surname'>
			<tr class='".bg_class()."'>
				<td>$surname</td>
				<td><input type='text' name='emailcust[$cusnum]' value='$email'></td>
			</tr>";
		}

	$OUT .= "
		<tr>
			<td colspan='2' align='right'><input type='submit' value='Send &raquo;'></td>
		</tr>
	</table>
	</form>";
	return $OUT;

}



function send_mails()
{

	/* check for valid settings */
	$settings = new dbSelect("esettings", "cubit");
	$settings->run();

	if ($settings->num_rows() <= 0) {
		r2sListSet("emailsettings");
		header("Location: email-settings.php");
		exit;
	}

	/* send them */
	extract($_POST);

	require_lib("mail.smtp");

	$send = new clsSMTPMail();

	$settings->fetch_array();
	$server = $settings->d["smtp_host"];
	$from = $settings->d["fromname"];
	$reply = $settings->d["reply"];

	$content = chunk_split($emailsavepage_content);
	$boundary = md5($content) . "=:" . strlen($content);

	$headers = array();
	$headers[] = "From: $from";
	$headers[] = "Reply-To: $reply";
	$headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
	$headers[] = "MIME-Version: 1.0";

	if (!isset($emailsavepage_mime)) {
		$attachmime = "text/html";
		$ext = ".html";
	} else {
		$attachmime = $emailsavepage_mime;

		if ($attachmime == "text/plain") {
			$ext = ".txt";
		} else {
			$ext = "";
		}
	}

	if ($emailsavepage_name == "") {
		$filename = "attachment$ext";
	} else {
		$filename = preg_replace("/.php\$/", "", $emailsavepage_name).$ext;
	}

	// company image
	$get_img = "SELECT img, imgtype FROM compinfo LIMIT 1";
	$run_img = db_exec ($get_img) or errDie ("Unable to get company image information.");
	if (pg_numrows ($run_img) > 0){
		$carr = pg_fetch_array ($run_img);

		// hack to limit a header line to 64 chars
		$temp = $carr['img'];
		$carr['img'] = "";
		$cnt = 0;
		for ($x=0;$x<=strlen($temp);$x++){
			$cnt++;
			$carr['img'] .= substr($temp,$x,1);
			if ($cnt==64){
				$carr['img'] .= "\n";
				$cnt = 0;
			}
		}

		if (strlen ($carr['img']) > 10){

			if ($carr['imgtype'] == "image/jpeg") 
				$imgfilename = "logo.jpg";
			elseif ($carr['imgtype'] == "image/png")
				$imgfilename = "logo.png";
			elseif ($carr['imgtype'] == "image/gif")
				$imgfilename = "logo.gif";

			$imagemsg = "Content-Type: $carr[imgtype]; charset=UTF-8\r\n"
				."Content-Transfer-Encoding: base64\r\n"
				."Content-Disposition: attachment; filename=\"$imgfilename\""
				."\r\n\r\n"
				."$carr[img]\n";

			$content = base64_encode(str_replace ("compinfo/getimg.php", "$imgfilename", base64_decode ($content)));

		}
	}

	// hack to limit a header line to 64 chars
	$temp = $content;
	$content = "";
	$cnt = 0;
	for ($x=0;$x<=strlen($temp);$x++){
		$cnt++;
		$content .= substr($temp,$x,1);
		if ($cnt==64){
			$content .= "\n";
			$cnt = 0;
		}
	}

	// the actual page
	$pagecontent = "Content-Type: $attachmime; charset=UTF-8\r\n"
		."Content-Transfer-Encoding: base64\r\n"
		."Content-Disposition: attachment; filename=\"$filename\""
		."\r\n\r\n"
		."$content";

	$msg = "--$boundary\n"
		."Content-Type: text/plain; charset=UTF-8\r\n\nDocument Attached\n\n"
		."--$boundary\n"
		."$pagecontent\n\n"
		."--$boundary\n";

	if (isset ($imagemsg) AND strlen ($imagemsg) > 0){
		$msg .= "$imagemsg\n"
		."--$boundary--\n";
	}

	$OUT = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Customer</th>
			<th>Email Status</th>
		</tr>";

	foreach ($emailcust as $cusnum => $email) {
		$custheaders = implode("\r\n", $headers);
		$custheaders .= "\r\nTo: \"$surnames[$cusnum]\" <$email>";

		$ret = $send->sendMessages($server, 25, "", "", "", $email, $from,
			$emailsavepage_subject, $msg, $custheaders);

		$redir = "";
		if($cusnum == "custom_address")
			$redir = "
				<td valign='center'>
					<form action='customers-new.php' method='POST'>
						<input type='hidden' name='surname' value='$surnames[$cusnum]'>
						<input type='hidden' name='email' value='$email'>
						<input type='submit' value='Add As Customer'>
					</form>
				</td>";

		$OUT .= "
			<tr class='".bg_class()."'>
				<td>$surnames[$cusnum]</td>
				<td>$ret</td>
				$redir
			</tr>";
	}
	$OUT .= "</table><br>".mkQuickLinks();
	return $OUT;

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
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

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");
require_lib("mail.smtp");

# Decide what to do
if (isset($_GET["invid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# Get templete
require("template.php");

# Details
function details($_GET)
{

	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Check if invoice has been printed
	if($inv['printed'] != "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has not been printed yet.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- Start some checks --- */

	# Check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has no items.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	/* --- End some checks --- */

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	$taxex = 0;

	# Get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){

		# Get warehouse name
		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# Get selected stock in this warehouse
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";
		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$taxex += ($stkd['amt']);
			$ex = "#";
		}else{
			$ex = "&nbsp;&nbsp;";
		}

		# Keep track of discounts
		$disc += $stkd['disc'];

		# Put in product
		$products .="<tr valign=top>
			<td>$stk[stkcod]</td>
			<td>$ex $sp $stk[stkdes]</td>
			<td>$stkd[qty]</td>
			<td>$stkd[unitcost]</td>
			<td>$stkd[disc]</td>
			<td>".CUR." $stkd[amt]</td>
		</tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate tradediscm
	if(strlen($inv['traddisc']) > 0){
		$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
	}else{
		$traddiscm = "0.00";
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	/* --- End Some calculations --- */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	# Avoid little box
	if(strlen($inv['comm']) > 0){
		$inv['comm'] = "<table border=1 cellspacing='0' bordercolor='#000000'>
			<tr><td>".nl2br($inv['comm'])."</td></tr>
		</table>";
	}

	# Update number of prints
	$inv['prints']++;
	db_connect();
	$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	//$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	/* -- Final Layout -- */
	$details = "<html><center><h2>Tax Invoice</h2>
	<table ".TMPL_tblDflts." width=770>
	<tr><td valign=top width=30%>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$inv[surname]</td>
			</tr>
			<tr>
				<td>".nl2br($inv['cusaddr'])."</td>
			</tr>
			<tr>
				<td>(Vat No. $inv[cusvatno])</td>
			</tr>
		</table>
	</td><td valign=top width=30%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_PADDR."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
		Reg No. ".COMP_REGNO."<br>
		VAT No. ".COMP_VATNO."<br>
	</td><td align=left width=20%>
		<img src='compinfo/getimg.php' width=230 height=47>
	</td><td valign=bottom align=right width=20%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr>
				<td><b>Invoice No.</b></td>
				<td valign=center>$inv[invnum]</td>
			</tr>
			<tr>
				<td><b>Proforma Inv No.</b></td>
				<td>$inv[docref]</td>
			</tr>
			<tr>
				<td><b>Order No.</b></td>
				<td valign=center>$inv[ordno]</td>
			</tr>
			<tr>
				<td><b>Terms</b></td>
				<td valign=center>$inv[terms] Days</td>
			</tr>
			<tr>
				<td><b>Invoice Date</b></td>
				<td valign=center>$inv[odate]</td>
			</tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr>
			<th>ITEM NUMBER</th>
			<th width=45%>DESCRIPTION</th>
			<th>QTY</th>
			<th>UNIT PRICE</th>
			<th>UNIT DISCOUNT</th>
			<th>AMOUNT</th>
		<tr>
		$products
	</table>
	</td></tr>
	<tr><td>
	$inv[comm]
	</td><td>
		".BNK_BANKDET."
	</td><td align=right colspan=2>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr>
				<td><b>SUBTOTAL</b></td>
				<td align=right>".CUR." $SUBTOT</td>
			</tr>
			<tr>
				<td><b>Trade Discount</b></td>
				<td align=right>".CUR." $inv[discount]</td>
			</tr>
			<tr>
				<td><b>Delivery Charge</b></td>
				<td align=right>".CUR." $inv[delivery]</td>
			</tr>
			<tr>
				<td><b>VAT @ $VATP%</b></td>
				<td align=right>".CUR." $VAT</td>
			</tr>
			<tr>
				<th><b>GRAND TOTAL<b></th>
				<td align=right>".CUR." $TOTAL</td>
				</tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='2' cellspacing='0' border=1>
			<tr><td colspan=2>VAT Exempt indicator = #</td></tr>
		</table>
	</td><td><br></td></tr>
	</table></center></html>";

	$body=$details;

	$es = qryEmailSettings();

	db_conn("cubit");
	$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
	$Ri=db_exec($Sl);

	$cd=pg_fetch_array($Ri);

	if(strlen($cd['email']) <1) {
		return "This customer does not have an email address";
	}


	//$send_cc="mg@mailbox.co.za";
	//$send_bcc="mg@mailbox.co.za";
	$send_cc="";
	$send_bcc="";

	$smtp_data['signature']=$es['sig'];
	$smtp_data['smtp_from']=$es['fromname'];
	$smtp_data['smtp_reply']=$es['reply'];
	$smtp_data['smtp_host']=$es['smtp_host'];
	$smtp_data['smtp_auth']=$es['smtp_auth'];
	$smtp_data['smtp_user']=$es['smtp_user'];
	$smtp_data['smtp_pass']=$es['smtp_pass'];


	//db_conn('cubit');

// 	$rslt = db_exec("SELECT smtp_from, smtp_reply, signature, smtp_host, smtp_auth, smtp_user, smtp_pass
// 		FROM mail_accounts");
// 	$smtp_data = pg_fetch_array($rslt);


	// build msg body
	$body = "$body\n\n$smtp_data[signature]";

	// determine whether or not here is an attachment
	//$has_attachment = is_uploaded_file($attachment["tmp_name"]);
	$has_attachment =FALSE;
	// modify message and create content_type header depending on whether or not an attachment was posted
	if ( $has_attachment == FALSE ) {
		$content_type = "text/html;charset=US-ASCII";
		$transfer_encoding = "8bit";
	} else { // has attachment
		$content_type = "multipart/mixed";

		// create the main body
		$body_text = "Content-Type: text/plain; charset=US-ASCII\n";
		$body_text .= "Content-Transfer-Encoding: base64\n";
		$body_text .= "\n" . chunk_split(base64_encode($body));

		// get the attachment data
		if ( ($fd = fopen($attachment["tmp_name"], "r")) == TRUE ) {
			$attachment_data = "";
			while ( ! feof($fd) ) {
				$attachment_data .= fgets( $fd, 4096 );
			}
			fclose($fd);

			// delete the temporary file
			unlink($attachment["tmp_name"]);

			$attachment_data =  chunk_split(base64_encode($attachment_data));

			$attachment_headers = "Content-Type: $attachment[type]; name=\"$attachment[name]\"\n";
			$attachment_headers .= "Content-Transfer-Encoding: base64\n";
			$attachment_headers .= "Content-Disposition: attachment; filename=\"$attachment[name]\"\n";

			$attachment_data = "$attachment_headers\n$attachment_data";
		} else { // error opening the attachment file
			$attachment_data = "";
		}

		// generate a unique boundary ( md5 of filename + ":=" + filesize )
		$boundary = md5($attachment["name"]) . "=:" . $attachment["size"];
		$content_type .= "; boundary=\"$boundary\"";

		// put together the body
		$body = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment_data\n\n--$boundary--\n
		";
	}

	print $body;

	// build headers
	$headers[] = "From: $smtp_data[smtp_from]";
	$headers[] = "To: $cd[email]";
	$headers[] = "Reply-To: $smtp_data[smtp_reply]";
	$headers[] = "X-Mailer: Cubit Mail";
	$headers[] = "Return-Path: $smtp_data[smtp_reply]";
	$headers[] = "Content-Type: $content_type";
	$headers[] = "cc: $send_cc";
	$headers[] = "bcc: $send_bcc";

	// create the mime header if should
	if ( $has_attachment == TRUE ) {
		$headers[] = "MIME-Version: 1.0";
	}

	// create the header variable (it is done this way, to make management of headers easier, since there
	// may be no tabs and unnecesary whitespace in mail headers)
	//$headers[] = "\n"; // add another new line to finish the headers
	$headers = implode("\n", $headers);

        // send the message
	$sendmail = & new clsSMTPMail;
	$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"],
		$smtp_data["smtp_pass"],$cd['email'], $smtp_data["smtp_from"], "Invoice: $inv[invnum]", $body, $headers);

	/*if ( mail($send_to, $subject, $body, $headers) == TRUE )
		$OUTPUT = "Successfully sent mail to $send_to.<br>";
	else
		$OUTPUT = "Error sending mail.<br>";*/

	return $OUTPUT;


}
?>

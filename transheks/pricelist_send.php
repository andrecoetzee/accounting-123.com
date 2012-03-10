<?

require("../settings.php");

cFramework::run("buildlist");
cFramework::parse();

function buildlist(&$frm) {
	if (!isset($_GET["cusnum"])) {
		invalid_use();
	}
	
	$cust = qryCustomer($_GET["cusnum"]);
	
	$qry = new dbSelect("plist_prices", "exten", grp(
		m("where", "listid='$cust[pricelist]' AND div='".USER_DIV."'")
	));
	$qry->run();
	
	$pli = array();
	$pli_noshow = array();
	while ($row = $qry->fetch_array()) {
		if ($row["show"] == "Yes") {
			$pli[$row["stkid"]] = $row["price"];
		} else {
			$pli_noshow[$row["stkid"]] = $row["price"];
		}
	}
	
	$qry = new dbSelect("stock", "cubit", grp(
		m("where", "div='".USER_DIV."'")
	));
	$qry->run();
	
	$plist = "";
	while ($row = $qry->fetch_array()) {
		if (isset($pli_noshow[$row["stkid"]])) {
			continue;
		}
		
		if (isset($pli[$row["stkid"]])) {
			$price = $pli[$row["stkid"]];
		} else {
			$price = $row["selamt"];
		}
		
		$desc = preg_replace("/,/", "", $row["stkdes"]);
		
		$plist .= "$row[stkcod],$price,$desc\n";
	}
	
	/* @var $frm cForm */
	$frm->setFormParm("post", "../emailsave_page.php");
	$frm->setkey("sendmails");
	$frm->add_hidden("emailsavepage_key", "sendmails", "string");
	$frm->add_hidden("emailsavepage_action", "email", "string");
	$frm->add_hidden("emailsavepage_content", base64_encode($plist), "string");
	$frm->add_hidden("emailsavepage_mime", "text/plain", "string");
	$frm->add_hidden("emailsavepage_subject", "Pricelist from ".COMP_NAME, "string");
	$frm->add_hidden("emailsavepage_name", "pricelist.csv", "string");
	$frm->add_heading("Sending Pricelist to Customer");
	$frm->add_layout("
		<tr %bgc>
			<td>Customer:</td>
			<td>$cust[surname]</td>
		</tr>
		<tr %bgc>
			<td>%disp[0]</td>
			<td>%fld[0]</td>
		</tr>");
	$frm->add_hidden("surnames[$cust[cusnum]]", $cust["surname"], "string");
	$frm->add_text("E-mail", "emailcust[$cust[cusnum]]", $cust["email"], "string", "1:255");
	
	return $frm->getfrm_input();
}

?>
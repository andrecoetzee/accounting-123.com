<?

require("../settings.php");

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "list";
}

switch ($_REQUEST["key"]) {
	case "approve":
		$OUTPUT = approve();
		break;
	case "deny":
		$OUTPUT = deny();
		break;
	case "list":
	default:
		$OUTPUT = listorders();
}

$OUTPUT .= "<br />".mkQuickLinks(
	ql("../purchase-new.php", "New Order"),
	ql("../sorder-view.php", "View Customer Sales Orders"),
	ql("configuration.php", "Transheks Configuration")
);

parse();

function listorders($err = "") {
	/* filters */
	$filter = wgrp(
		m("approved", "n")
	);
	$order = "pdate";

	/* output */
	$OUT = "
	<h3>Approve Customer Orders</h3>
	$err
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Customer</th>
		<th>Received</th>
		<th>Total</th>
		<th colspan='3'>Options</th>
	</tr>";

	$qry = new dbSelect("recvpurch", "trh", grp(
		//m("cols", "*, (trhkey.*)"),
		m("where", wgrp($filter)),
		m("order", $order)
	));
	$qry->run();

	while ($row = $qry->fetch_array()) {
		$ci = qryCustomer($row["custid"]);

		if (!empty($ci["cusname"])) {
			$exdisp = ", $ci[cusname]";
		} else {
			$exdisp = "";
		}

		$OUT .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$ci[surname]$exdisp</td>
			<td>$row[pdate]</td>
			<td>$row[balance]</td>
			<td><a href='".SELF."?key=approve&approve=a&id=$row[id]'>Approve Sales Order</a></td>
			<td><a href='".SELF."?key=approve&approve=i&id=$row[id]'>Approve and Invoice Sales Order</a></td>
			<td><a href='".SELF."?key=deny&id=$row[id]'>Deny Sales Order</a></td>
		</tr>";
	}

	$OUT .= "
	</table>";

	return $OUT;
}

function approve() {
	extract($_REQUEST);

	if (!isset($approve) || ($approve != "i" && $approve != "a")) {
		invalid_use("<li class='err'>Invalid action.<li>");
	}

	/* order info */
	$qry = new dbSelect("recvpurch", "trh", grp(
		m("where", wgrp(
			m("id", $id)
		))
	));
	$qry->run();

	if ($qry->num_rows() <= 0) {
		invalid_use("<li class='err'>Invalid Sales Order Id (TRHAPP).</li>");
	}

	$soi = $qry->fetch_array();

	/* customer info */
	$ci = qryCustomer($soi["custid"]);

	/* sales person name */
	if (empty($ci["sales_rep"])) {
		$speoples = qrySalesPerson();
		if ($speoples->num_rows() <= 0) {
			$salespn = "General";
		} else {
			$speoples->fetch_array();
			$salespn = $speoples->d["salesp"];
			$speoples->free();
		}
	} else {
		$m = qrySalesPerson($ci["sales_rep"]);
		$salespn = $m["salesp"];
	}

	/* currency info */
	$curinfo = qryCurrency($ci["fcid"]);

	$cols = grp(
		m("deptid", "$ci[deptid]"),
		m("cusnum", "$ci[cusnum]"),
		m("cordno", ""),
		m("ordno", ""),
		m("chrgvat", $soi["vatinc"] == "yes" ? "inc" : "no"),
		m("terms", "$ci[credterm]"),
		m("salespn", $salespn),
		m("odate", "$soi[pdate]"),
		m("accepted", "n"),
		m("comm", ""),
		m("done", "y"),
		m("username", USER_NAME),
		m("deptname", "$ci[deptname]"),
		m("cusacc", "$ci[accno]"),
		m("cusname", "$ci[cusname]"),
		m("surname", "$ci[surname]"),
		m("cusaddr", "$ci[addr1]"),
		m("cusordno", "$soi[purnum]"),
		m("cusvatno", "$ci[vatnum]"),
		m("prd", "0"),
		m("div", USER_DIV),
		m("disc", "0.00"),
		m("discp", "0.00"),
		m("delchrg", "$soi[shipchrg]"),
		m("subtot", "$soi[subtot]"),
		m("traddisc", "0.00"),
		m("balance", "$soi[balance]"),
		m("vat", "$soi[vat]"),
		m("total", "$soi[total]"),
		m("jobid", "0"),
		m("jobnum", "0"),
		m("dir", ""),
		m("location", ""),
		m("fcid", "$ci[fcid]"),
		m("currency", "$curinfo[symbol]"),
		m("xrate", "$curinfo[rate]"),
		m("fbalance", "0.00"),
		m("fsubtot", "0.00"),
		m("discount", "0.00"),
		m("delivery", "$soi[shipchrg]"),
		m("delvat", "$soi[delvat]"),
		m("display_costs", "yes"),
		m("proforma", "no"),
		m("pinvnum", "0")
	);

	$upd = new dbUpdate("sorders", "cubit", $cols);
	$upd->run(DB_INSERT);

	$sordid = $upd->lastvalue("sordid");

	/* items */
	$qry->reset();
	$qry->setTable("recvpurch_items");
	$qry->setOpt(grp(
		m("where", wgrp(
			m("recvpurch_id", $id)
		))
	));
	$qry->run();

	$upd->setTable("sorders_items");

	while ($row = $qry->fetch_array()) {
		if (empty($row["sup_stkcod"])) {
			invalid_use("Supplier stock codes not setup for customer. Order ignored.");
		}
		$stkinfo = qryStockC($row["sup_stkcod"]);

		$cols = grp(
			m("sordid", "$sordid"),
			m("whid", "$stkinfo[whid]"),
			m("stkid", "$stkinfo[stkid]"),
			m("qty", "$row[qty]"),
			m("div", USER_DIV),
			m("amt", "$row[amt]"),
			m("discp", "0.00"),
			m("disc", "0.00"),
			m("unitcost", "$row[unitcost]"),
			m("hidden", ""),
			m("funitcost", "0.00"),
			m("famt", "0.00"),
			m("pinv", "0.00"),
			m("vatcode", "$stkinfo[vatcode]"),
			m("description", "$stkinfo[stkdes]"),
			m("account", "0")
		);

		$upd->setOpt($cols);
		$upd->run(DB_INSERT);
	}

	/* set approve status */
	$upd->reset();
	$upd->setTable("recvpurch", "trh");
	$upd->setOpt(grp(
		m("approved", "$approve")
	), "id='$id'");
	$upd->run(DB_UPDATE);

	/* get customer trh config */
	$keyinfo = trhKeyCust($soi["custid"]);
	$email = $keyinfo["email"];

	/* send trh response message */
	$purinfo = array(
		"purid" => $soi["purid"],
		"status" => $approve
	);

	$ret = send_trhmsg("cust", $soi["custid"], $email, "rsppur", $purinfo);

	if ($approve == "i") {
		header("Location: ../sorder-accept.php?sordid=$sordid");
		exit;
	} else {
		$OUT = listorders("<li class='err'>Successfully approved sales order.</li>");
	}

	return $OUT;
}

function deny() {
	extract($_REQUEST);

	/* order info */
	$qry = new dbSelect("recvpurch", "trh", grp(
		m("where", wgrp(
			m("id", $id)
		))
	));
	$qry->run();

	if ($qry->num_rows() <= 0) {
		invalid_use("<li class='err'>Invalid Sales Order Id (TRHAPP).</li>");
	}

	$soi = $qry->fetch_array();

	/* set approve status */
	$cols = grp(
		m("approved", "d")
	);
	$upd = new dbUpdate("recvpurch", "trh", $cols, "id='$id'");
	$upd->run(DB_UPDATE);

	/* get customer trh config */
	$keyinfo = trhKeyCust($soi["custid"]);
	$email = $keyinfo["email"];

	/* send trh response message */
	$purinfo = array(
		"purid" => $soi["purid"],
		"status" => "d"
	);

	$ret = send_trhmsg("cust", $soi["custid"], $email, "rsppur", $purinfo);

	$OUT = listorders("<li class='err'>Successfully denied sales order.</li>");
	return $OUT;
}

?>
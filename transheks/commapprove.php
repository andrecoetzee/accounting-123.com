<?

require("../settings.php");

cFramework::run("view");
cFramework::quickLinks(
	ql("order_approve.php", "Approve Customer Sales Orders"),
	ql("../customers-view.php", "View Customers"),
	ql("../supp-view.php", "View Suppliers"),
	ql("configuration.php", "Transheks Configuration")
);
cFramework::parse();

function view(&$frm, $err = "") {
	$OUT = "
	<h3>Unknown Transactioning Requests</h3>
	$err
	<table ".TMPL_tblDflts.">
	<tr>
		<th colspan='5'>Customers</th>
	</tr>
	<tr>
		<th>Name</th>
		<th>Telephone</th>
		<th colspan='2'>Options</th>
	</tr>";
	
	$qry = new dbSelect("keys", "trh", grp(
		m("where", "custid='-1'")
	));
	$qry->run();
	
	if ($qry->num_rows() <= 0) {
		$OUT .= "
		<tr class='".bg_class()."'>
			<td colspan='4'>No unknown requests.</td>
		</tr>";
	}
	
	while ($row = $qry->fetch_array()) {
		$OUT .= "
		<tr class='".bg_class()."'>
			<td>$row[compname]</td>
			<td>$row[bustel]</td>
			<td><a href='".SELF."?id=$row[id]&key=approve'>Approve</a></td>
			<td><a href='".SELF."?id=$row[id]&key=deny'>Deny</a></td>
		</tr>";	
	}
	
	$OUT .= 
	TBL_BR."
	<tr>
		<th colspan='5'>Suppliers</th>
	</tr>
	<tr>
		<th>Name</th>
		<th>Telephone</th>
		<th colspan='2'>Options</th>
	</tr>";
	
	$qry = new dbSelect("keys", "trh", grp(
		m("where", "suppid='-1'")
	));
	$qry->run();
	
	if ($qry->num_rows() <= 0) {
		$OUT .= "
		<tr class='".bg_class()."'>
			<td colspan='4'>No unknown requests.</td>
		</tr>";
	}
	
	while ($row = $qry->fetch_array()) {
		$OUT .= "
		<tr class='".bg_class()."'>
			<td>$row[compname]</td>
			<td>$row[bustel]</td>
			<td><a href='".SELF."?id=$row[id]&key=approve'>Approve</a></td>
			<td><a href='".SELF."?id=$row[id]&key=deny'>Deny</a></td>
		</tr>";	
	}
	
	$OUT .= "
	</table>";
	
	return $OUT;
}

function approve(&$frm) {
	/* @var $frm cForm */
	if (($e = $frm->validateValue($_GET["id"], "num", 1, 10)) !== false) {
		return view($frm, "<li class='err'>Error reading key: $e.</li>");
	}

	/* create the form */
	$frm->setkey("approve_send");
	$frm->settitle("Approve Request");
	$frm->add_hidden("id", $_GET["id"], "num");

	$qry = new dbSelect("keys", "trh", grp(
		m("where", "id='$_GET[id]'")
	));
	$qry->run();
	
	if ($qry->num_rows() <= 0) {
		return view($frm, "<li class='err'>Invalid key selected.</li>");
	}
	
	$ki = $qry->fetch_array();
	
	if ($ki["custid"] == "-1") {
		$frm->add_heading("Select Customer");
		
		$sopt = grp(
			m("cols", "cusnum, surname, bustel")
		);
		$cs = new dbList("customers", "cubit", $sopt, "#cusnum", "#surname (#bustel)");
		$frm->add_select("Customer", "cusnum", 0, $cs, "num", "1:10");
	} else if ($ki["suppid"] == "-1") {
		$frm->add_heading("Select Supplier");
		
		$sopt = grp(
			m("cols", "supid, supname")
		);
		$cs = new dbList("suppliers", "cubit", $sopt, "#supid", "#supname");
		$frm->add_select("Supplier", "supid", 0, $cs, "num", "1:10");
	} else {
		return view($frm, "<li class='err'>Key already approved.</li>");
	}
	
	return $frm->getfrm_input();
}

function approve_send(&$frm) {
	/* @var $frm cForm */
	if ($frm->validate("approve_send")) {
		return approve($frm);
	}
	
	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "*, (key).*"),
		m("where", "id='$_POST[id]'")
	));
	$qry->run();
	
	if ($qry->num_rows() <= 0) {
		return view($frm, "<li class='err'>Invalid key selected.</li>");
	}
	
	$ki = $qry->fetch_array();
	
	if ($ki["custid"] == "-1") {
		$fromwho = "cust";
		$fromwhoid = $_POST["cusnum"];
	} else if ($ki["suppid"] == "-1") {
		$fromwho = "supp";
		$fromwhoid = $_POST["supid"];
	} else {
		return view($frm, "<li class='err'>Key already approved.</li>");
	}
	
	$cols = grp(
		m("${fromwho}id", $fromwhoid)
	);
	$upd = new dbUpdate("keys", "trh", $cols, "id='$_POST[id]'");
	$upd->run(DB_UPDATE);
	
	if (send_trhmsg($fromwho, $fromwhoid, $ki["email"], "rspkey", $ki["recv_key"])) {
		return view($frm, "<li class='err'>Successfully approved request.</li>");
	} else {
		/* set the id back to -1, because there was an error */
		$cols = grp(
			m("${fromwho}id", "-1")
		);
		$upd = new dbUpdate("keys", "trh", $cols, "id='$_POST[id]'");
		$upd->run(DB_UPDATE);
		
		return view($frm, "<li class='err'>Error approving request.</li>");
	}
}

function deny(&$frm) {
	/* @var $frm cForm */
	if (($e = $frm->validateValue($_GET["id"], "num", 1, 10)) !== false) {
		return view($frm, "<li class='err'>Error reading key: $e.</li>");
	}
	
	$qry = new dbSelect("keys", "trh", grp(
		m("cols", "*, (key).*"),
		m("where", "id='$_GET[id]'")
	));
	$qry->run();
	
	if ($qry->num_rows() <= 0) {
		return view($frm, "<li class='err'>Invalid key selected.</li>");
	}
	
	$ki = $qry->fetch_array();
	
	if ($ki["custid"] == "-1") {
		$fromwho = "cust";
	} else if ($ki["suppid"] == "-1") {
		$fromwho = "supp";
	} else {
		return view($frm, "<li class='err'>Key already approved.</li>");
	}
	
	if (send_trhmsg($fromwho, "-1", $ki["email"], "rspkey", str_pad("denied", 32, 'A', STR_PAD_RIGHT))) {
		$upd = new dbDelete("keys", "trh", "id='$_GET[id]'");
		$upd->run();
	
		return view($frm, "<li class='err'>Successfully denied request.</li>");
	} else {
		/* set the id back to -1, because there was an error */
		$cols = grp(
			m("${fromwho}id", "-1")
		);
		$upd = new dbUpdate("keys", "trh", $cols, "id='$_POST[id]'");
		$upd->run(DB_UPDATE);
		
		return view($frm, "<li class='err'>Error denying request.</li>");
	}
}

?>
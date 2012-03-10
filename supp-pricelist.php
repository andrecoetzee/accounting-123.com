<?

require ("settings.php");
require_lib("validate");

$frm = new cForm();

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "view";
}

switch ($_REQUEST["key"]) {
	case "import":
		$OUTPUT = import($frm);
		break;
	case "view":
	default:
		$OUTPUT = view($frm);
		break;
}

parse();



function view($frm) {
	extract($_REQUEST);

	if ($msg = cForm::validateValue($supid, "num", 1, 10)) {
		return "<li class='err'>The supplier ID is invalid. $msg</li>
			<input type='button' onclick='window.history.back();' value='&laquo; Correction' />";
	}

	/* @var frm cForm */
	$frm->settitle("Supplier Pricelist");
	$frm->setkey("import");
	$frm->add_heading("Import New Pricelist");
	$frm->add_message("
			<li class='err'>Pricelists has to be in CSV format and to import them
				you have to specify the order and format of the fields<br />
				by selecting what each of them are in the same order as they appear
				in the file. Only the stock code<br />
				and the price is needed, so for the other fields you simply
				select the '-' options.
			</li>", "inst");
	$frm->add_message("
			<li class='err'>Note that the stock codes of the supplier should be added
				by editing the stock item and selecting the<br />
				'Add/Edit/Remove' button below the stock code input field.
			</li>", "suppstkcod");
	$frm->add_hidden("supid", $supid, "num");
	$frm->add_file("Pricelist", "supplist");
	$frm->add_checkbox("VAT Inclusive", "vatinc", true, true);
	$frm->add_heading("Comma Seperated Volume (CSV) Fields");
	$frm->add_layout("
	<tr %bgc>
		<td colspan='2' nowrap='t'>
			<strong>
				%fldonly ,
				%fldonly ,
				%fldonly ,
				%fldonly ,
				%fldonly ,
				%fldonly
			</strong>
		</td>
	</tr>");

	// field types
	$ft = array(
		"ignore" => "-",
		"stkcod" => "Stock Code",
		"price" => "Price"
	);
	$frm->add_select("", "fld[0]", "stkcod", $ft, "string", "5:6");
	$frm->add_select("", "fld[1]", "price", $ft, "string", "5:6");
	$frm->add_select("", "fld[2]", "ignore", $ft, "string", "5:6");
	$frm->add_select("", "fld[3]", "ignore", $ft, "string", "5:6");
	$frm->add_select("", "fld[4]", "ignore", $ft, "string", "5:6");
	$frm->add_select("", "fld[5]", "ignore", $ft, "string", "5:6");

	$frm->add_ctrlbtn("Import", "submit", "btn_import");

	$OUT = $frm->getfrm_input();

	/* supplier info */
	$suppinfo = qrySupplier($supid);
	$supcur = qryCurrency($suppinfo["fcid"]);
	$supcur = $supcur["symbol"];

	/* list current pricelist */
	$OUT .= "
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Stock Code</td>
		<th>Supplier Stock Code</th>
		<th>Current Supplier Price</th>
		<th>Current Selling Price</th>
	</tr>";

	$sql = "SELECT pli.*
			FROM exten.spricelist pl INNER JOIN exten.splist_prices pli
				ON pl.listid=pli.listid
			WHERE pl.suppid='$supid'";
	$qry = new dbSql($sql);
	$qry->run();

	if ($qry->num_rows() <= 0) {
		$OUT .= "
		<tr bgcolor='".bgcolorc(0)."'>
			<td colspan='4'>No pricelist.</td>
		</tr>";
	}

	$i = 0;
	while ($row = $qry->fetch_array()) {
		if (empty($row["supstkcod"])) {
			$our_stkcod = "No supplier stock code";
			$our_price = "";
		} else if (($stkid = suppStkid($supid, $row["supstkcod"])) === false) {
			$our_stkcod = "<li class='err'>No such stock item.
				<a href='stock-add.php?stkcod=$row[supstkcod]&supid=$supid&supstkcod=$row[supstkcod]'>Add Stock</a> /
				<a href='stock-view.php'>Edit Stock</li>";
			$our_price = "";
		} else {
			$stkrow = qryStock($stkid, "stkcod, selamt");

			$our_stkcod = "<strong>$stkrow[stkcod]</strong>";
			$our_price = CUR." ".sprint($stkrow["selamt"]);
		}

		$OUT .= "
		<tr bgcolor='".bgcolor($i)."'>
			<td>$our_stkcod</td>
			<td>$row[supstkcod]</td>
			<td align='right'>$supcur ".sprint($row["price"])."</td>
			<td align='right'>$our_price</td>
		</tr>";
	}

	$OUT .= "
	</table>";

	return $OUT;
}

function import($frm) {
	/* @var $frm cForm */
	if ($frm->validate("import")) {
		return view($frm);
	}

	/* get field indexes */
	$stkcod = false;
	$price = false;
	foreach ($_REQUEST["fld"] as $fi => $ft) {
		if ($ft != "ignore") {
			${$ft} = $fi;
		}
	}

	/* import file if all field types specified */
	if ($stkcod === false || $price === false) {
		$frm->setmsg("<li class='err'>Not all field types satisfied</li>");
	} else {
		$qry = new dbSelect("spricelist", "exten", grp(
			m("cols", "listid"),
			m("where", "suppid='$_REQUEST[supid]'")
		));
		$qry->run();

		if ($qry->num_rows() <= 0) {
			$suppinfo = qrySupplier($_REQUEST["supid"]);

			$cols = grp(
				m("suppid", $_REQUEST["supid"]),
				m("listname", $suppinfo["supname"]),
				m("div", USER_DIV)
			);
			$upd = new dbUpdate("spricelist", "exten", $cols);
			$upd->run(DB_INSERT);

			$listid = $upd->lastid("listid");
		} else {
			$listid = $qry->fetch_result();
		}

		$upd = new dbDelete("splist_prices", "exten", "listid='$listid'");
		$upd->run();

		$upd = new dbUpdate("splist_prices", "exten");

		$invalid_fields = array();
		$nosuch_fields = array();
		$file = ucfs::file("supplist");
		foreach ($file as $rd) {
			$ri = explode(",", $rd);

			$ri[$stkcod] = trim($ri[$stkcod]);
			$ri[$price] = trim($ri[$price]);

			if (cForm::validateValue($ri[$stkcod], "string", 1, 250)
				|| cForm::validateValue($ri[$price], "float", 1, 40)) {
				$invalid_fields[] = $ri[$stkcod];
				continue;
			}

			$stkid = suppStkid($_REQUEST["supid"], $ri[$stkcod]);
			if ($stkid === false) {
				$stkinfo = array(
					"stkid" => "0",
					"catid" => "0",
					"prdcls" => "0"
				);
			} else {
				$stkinfo = qryStock($stkid, "stkid, catid, prdcls");
			}

			if (!isset($_REQUEST["vatinc"])) {
				$ri[$price] += $ri[$price] * TAX_VAT / 100;
			}

			$cols = grp(
				m("listid", $listid),
				m("stkid", $stkinfo["stkid"]),
				m("catid", $stkinfo["catid"]),
				m("clasid", $stkinfo["prdcls"]),
				m("price", $ri[$price]),
				m("div", USER_DIV),
				m("supstkcod", $ri[$stkcod])
			);

			$upd->setCols($cols);
			$upd->run();
		}

		if (count($invalid_fields) > 0) {
			$msg = "<br />The following items weren't imported because they contain
				invalid values for either the stock code or the price:<br />";
			foreach ($invalid_fields as $v) {
				$msg .= "&nbsp;&nbsp;&nbsp;&nbsp;- $v<br />";
			}
		} else {
			$msg = "";
		}

		$frm->setmsg("<li class='err'>Successfully imported new pricelist.$msg</li>");
	}
	return view($frm);

}



?>
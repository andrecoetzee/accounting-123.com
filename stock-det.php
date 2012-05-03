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


if (isset($_POST['key'])) {
	switch ($_POST["key"]) {
		case "rem":
			$OUTPUT = rem ($_POST);
			break;
		default:
			if (isset($_GET['stkid'])){
				$OUTPUT = confirm ($_GET['stkid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
    if (isset($_GET['stkid'])){
        $OUTPUT = confirm ($_GET['stkid']);
    } else {
        $OUTPUT = "<li> - Invalid use of module.</li>";
    }
}

# Get template
require("template.php");




# Confirm
function confirm($stkid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
		return "<li> Invalid Stock ID.</li>";
	}else{
		$stk = pg_fetch_array($stkRslt);
	}

	# get stock vars
	foreach ($stk as $key => $value) {
		$$key = $value;
	}

	db_conn("exten");

	# get warehouse
	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	$vat = (getSetting("SELAMT_VAT") == 'inc') ? "Including VAT" : "Excluding VAT";

	db_conn("cubit");
	$supplier1 += 0;
	$supplier2 += 0;
	$supplier3 += 0;

	if($supplier1 != 0) {
		$Sl = "SELECT supname FROM suppliers WHERE supid='$supplier1'";
		$Ri = db_exec($Sl);
		$sd = pg_fetch_array($Ri);
		$supname1 = $sd['supname'];
	} else {
		$supname1 = "";
	}

	if($supplier2 != 0) {
		$Sl = "SELECT supname FROM suppliers WHERE supid='$supplier2'";
		$Ri = db_exec($Sl);
		$sd = pg_fetch_array($Ri);
		$supname2 = $sd['supname'];
	} else {
		$supname2 = "";
	}

	if($supplier3 != 0) {
		$Sl = "SELECT supname FROM suppliers WHERE supid='$supplier3'";
		$Ri = db_exec($Sl);
		$sd = pg_fetch_array($Ri);
		$supname3 = $sd['supname'];
	} else {
		$supname3 = "";
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM stkimgs WHERE stkid='$stkid'";
	$Ry = db_exec($Sl) or errDie("Unable to get stock image.");

	if(pg_numrows($Ry) > 0) {
		$img = "<img src='stock-view-image.php?id=$stkid' width='150' height='200'>";
	} else {
		$img = "To add an image for this stock item, use '<a href='stock-edit.php?stkid=$stkid'>Edit Stock</a>'";
	}

	// Layout
	$confirm = "
		<h3>Stock Details</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='40%'>Field</th>
							<th width='60%'>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Store</td>
							<td>$wh[whname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Stock code</td>
							<td>$stkcod</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Stock description</td>
							<td>".nl2br($stkdes)."</pre></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Category</td>
							<td>$catname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Classification</td>
							<td>$classname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Warranty</td>
							<td>$warranty</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bought Unit of measure</td>
							<td>$buom</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling Unit of measure</td>
							<td>$suom</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling Units per Bought unit</td>
							<td>$rate</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Location</td>
							<td>Shelf : $shelf - Row : $row</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Minimum level</td>
							<td>$minlvl</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Maximum level</td>
							<td>$maxlvl</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cost price per selling unit</td>
							<td>".CUR." $csprice</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling price per selling unit</td>
							<td>".CUR." ".sprint($selamt)." $vat</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier1</td>
							<td>$supname1</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier2</td>
							<td>$supname2</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier3</td>
							<td>$supname3</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Image</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>$img</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";

	$confirm .= mkQuickLinks(
		ql("stock-edit.php?stkid=$stk[stkid]", "Edit This Stock Item"),
		ql("stock-add.php", "Add New Stock Item"),
		ql("stock-view.php", "View Stock")
//		ql(r2sListLink("stock_view", "stock-view.php"), "View Stock")
	);
	return $confirm;

}



?>
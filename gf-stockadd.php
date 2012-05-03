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

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = view();
	}
} else {
	# Display default output
	$OUTPUT = view();
}

# Get template
require("template.php");



# Default view
function view()
{

	extract($_REQUEST);

	$fields = array(
		"stkcod" => ""
	);

	extract($fields, EXTR_SKIP);

	# Select the stock category
	db_connect();

	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.</li>";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
		}
	}
	$cats .= "</select>";

	$vat = (getSetting("SELAMT_VAT") == 'inc') ? "Including VAT" : "Excluding VAT";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .= "</select>";

	db_connect ();

	$get_defwh = "SELECT * FROM set WHERE label = 'DEF_WH' LIMIT 1";
	$run_defwh = db_exec($get_defwh) or errDie("Unable to get default store information");
	if(pg_numrows($run_defwh) < 1){
		$defwhid = "";
	}else {
		$darr = pg_fetch_array($run_defwh);
		$defwhid = $darr['value'];
	}

	# Select the stock warehouse
	db_conn("exten");

	$whs= "<select name='whid'>";
	$sql = "SELECT whid,whname,whno FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li>There are no stock stores in Cubit.</li>";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			if($wh['whid'] == $defwhid){
				$whs .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
			}else {
				$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
		}
	}
	$whs .= "</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$vatcodes = "<select name='vatcode'>";

	while($vd = pg_fetch_array($Ri)) {
		if($vd['del'] == "Yes") {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$vatcodes .= "<option value='$vd[id]' $sel>$vd[code] $vd[description]</option>";
	}

	$vatcodes .= "</select>";

	$Sl = "SELECT supid,supname FROM suppliers ORDER BY supname";
	$Ri = db_exec($Sl);

	$supplier1 = 0;
	$supplier2 = 0;
	$supplier3 = 0;

	$suppliers1 = "
		<select name='supplier1'>
			<option value='0'>Select Supplier 1</option>";

	$suppliers2 = "
		<select name='supplier2'>
			<option value='0'>Select Supplier 2</option>";

	$suppliers3 = "
		<select name='supplier3'>
			<option value='0'>Select Supplier 3</option>";

	while($sd = pg_fetch_array($Ri)) {
		if($sd['supid'] == $supplier1) {
			$sel1 = "selected";
		} else {
			$sel1 = "";
		}
		$suppliers1 .= "<option value='$sd[supid]' $sel1>$sd[supname]</option>";

		if($sd['supid'] == $supplier2) {
			$sel2 = "selected";
		} else {
			$sel2 = "";
		}
		$suppliers2 .= "<option value='$sd[supid]' $sel2>$sd[supname]</option>";

		if($sd['supid'] == $supplier3) {
			$sel3 = "selected";
		} else {
			$sel3 = "";
		}
		$suppliers3 .= "<option value='$sd[supid]' $sel3>$sd[supname]</option>";
	}

	$suppliers1 .= "</select>";
	$suppliers2 .= "</select>";
	$suppliers3 .= "</select>";

	$warranty_ar = array (
		"year" => "Year/s",
		"month" => "Month/s",
		"day" => "Day/s"
	);

	$warranty_sel = "<select name='warranty_range'>";
	foreach ($warranty_ar as $key=>$title) {
		$warranty_sel .= "<option value='$key'>$title</option>";
	}
	$warranty_sel .= "</select>";

	/* adding from supplier stock */
	if (isset($supid) && isset($supstkcod)) {
		$supadd = "
			<input type='hidden' name='supid' value='$supid' />
			<input type='hidden' name='supstkcod' value='$supstkcod' />";
	} else {
		$supadd = "";
	}

	// Layout
	$view = "
		<h3>Add Stock</h3>
		<form action='".SELF."' method=post name='form'>
		<input type='hidden' name='key' value='confirm'>
		$supadd
		<table ".TMPL_tblDflts.">
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Select Store</td>
							<td>$whs</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Stock code</td>
							<td><input type='text' size='20' name='stkcod' value='$stkcod'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Stock description</td>
							<td><textarea cols='18' rows='5' name='stkdes'></textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Type</td>
							<td valign='center'>
								<input type='radio' name='stktp' value='stk' checked=yes>Stock<b> | </b>
								<input type='radio' name='stktp' value='lab'> Services/Labour
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Serialized</td>
							<td valign='center'>
								<input type='radio' name='serd' value='yes' >Yes<b> | </b>
								<input type='radio' name='serd' value='no' checked='yes'> No</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID type of tag</td>
							<td valign='center'><input type='text' name='rfidtype' value='Gen2'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID (869.4 to 928 Mhz UHF)</td>
							<td valign='center'><input type='text' name='rfidfreq' value='869.4'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID Tag read rate</td>
							<td valign='center'><input type='text' name='rfidrate' value='2'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Select Category [<a href='javascript: popupSized(\"stockcat-add.php?".frmupdate_make("list", "form", "catid")."\", \"stock\", 380, 400);'>Add New</a>]</td>
							<td>$cats</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Classification [<a href='javascript: popupSized(\"stockclass-add.php?".frmupdate_make("list", "form", "clasid")."\", \"stock\", 380, 400);'>Add New</a>]</td>
							<td>$class</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Warranty</td>
							<td><input type='text' name='warranty' size=2>$warranty_sel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Upload Stock Image</td>
							<td>
								<input type='radio' name='change_image' value='yes'>Yes<b> | </b>
								<input type='radio' name='change_image' value='no' checked>No
							</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Buying Unit of measure</td>
							<td><input type='text' size='7' name='buom'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling Unit of measure</td>
							<td><input type='text' size='7' name='suom'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling Units per Buying unit</td>
							<td><input type='text' size='5' name='rate'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Location</td>
							<td>Shelf <input type='text' size='5' name='shelf'> Row <input type='text' size='5' name='row'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Level</td>
							<td>Minimum <input type='text' size='5' name='minlvl'> Maximum <input type='text' size='5' name='maxlvl'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling price per unit</td>
							<td>".CUR." <input type='text' size='7' name='selamt'> $vat</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bar Code</td>
							<td><input type='text' size='20' name='bar'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Code</td>
							<td>$vatcodes</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Markup Percentage</td>
							<td><input type='text' size='10' name='markup'> %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier1</td>
							<td>$suppliers1</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier2</td>
							<td>$suppliers2</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier3</td>
							<td>$suppliers3</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td></td>
				<td valign='center'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}




# View Error
function view_err($_POST, $err = "")
{
	# Get vars
	extract ($_POST);

	# Select the stock warehouse
	db_conn("exten");

	$whs= "<select name='whid'>";
	$sql = "SELECT whid,whname,whno FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
		return "<li>There are no stock warehouses in Cubit.</li>";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			if($wh['whid'] == $whid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$whs .= "<option value='$wh[whid]' $sel>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .= "</select>";

	# Select the stock category
	db_connect();

	$cats= "<select name='catid' style='width: 167'>";
	$sql = "SELECT * FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no categories in Cubit.</li>";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			if($cat['catid'] == $catid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$cats .= "<option value='$cat[catid]' $sel>$cat[cat]</option>";
		}
	}
	$cats .= "</select>";

	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			if($clas['clasid'] == $clasid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$class .= "<option value='$clas[clasid]' $sel>$clas[classname]</option>";
		}
	}
	$class .="</select>";

	if($stktp == 'stk'){
		$tstk = "checked=yes";
		$tlab = "";
	}else{
		$tstk = "";
		$tlab = "checked=yes";
	}

	// 		if($exvat == 'yes'){
	// 			$vaty = "checked=yes";
	// 			$vatn = "";
	// 		}else{
	// 			$vaty = "";
	// 			$vatn = "checked=yes";
	// 		}

	$vaty = "checked=yes";
	$vatn = "";

	$vat = (getSetting("SELAMT_VAT") == 'inc') ? "Including VAT" : "Excluding VAT";

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$vatcodes = "<select name='vatcode'>";
	while($vd = pg_fetch_array($Ri)) {
		if($vatcode == $vd['id']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$vatcodes .= "<option value='$vd[id]' $sel>$vd[code] $vd[description]</option>";
	}

	$vatcodes .= "</select>";

	$Sl = "SELECT supid,supname FROM suppliers ORDER BY supname";
	$Ri = db_exec($Sl);

	$suppliers1 = "
		<select name='supplier1'>
			<option value='0'>Select Supplier 1</option>";

	$suppliers2 = "
		<select name='supplier2'>
			<option value='0'>Select Supplier 2</option>";

	$suppliers3 = "
		<select name='supplier3'>
			<option value='0'>Select Supplier 3</option>";

	while($sd = pg_fetch_array($Ri)) {
		if($sd['supid'] == $supplier1) {
			$sel1 = "selected";
		} else {
			$sel1 = "";
		}
		$suppliers1 .= "<option value='$sd[supid]' $sel1>$sd[supname]</option>";

		if($sd['supid'] == $supplier2) {
			$sel2 = "selected";
		} else {
			$sel2 = "";
		}
		$suppliers2 .= "<option value='$sd[supid]' $sel2>$sd[supname]</option>";

		if($sd['supid'] == $supplier3) {
			$sel3 = "selected";
		} else {
			$sel3 = "";
		}
		$suppliers3 .= "<option value='$sd[supid]' $sel3>$sd[supname]</option>";
	}

	$suppliers1 .= "</select>";
	$suppliers2 .= "</select>";
	$suppliers3 .= "</select>";

	if($serd == 'yes'){
		$serdy = "checked='yes'";
		$serdn = "";
	}else{
		$serdy = "";
		$serdn = "checked='yes'";
	}

	// Was the a change to the image specified?
	if ($change_image == "yes") {
		$img_yes = "checked";
		$img_no = "";
	} else {
		$img_yes = "";
		$img_no = "checked";
	}

	$warranty_ar = array (
		"year" => "Year/s",
		"month" => "Month/s",
		"day" => "Day/s"
	);

	$warranty_sel = "<select name='warranty_range'>";
	foreach ($warranty_ar as $key=>$title) {
		$warranty_sel .= "<option value='$key'>$title</option>";
	}
	$warranty_sel .= "</select>";

	/* adding from supplier stock */
	if (isset($supid) && isset($supstkcod)) {
		$supadd = "
			<input type='hidden' name='supid' value='$supid' />
			<input type='hidden' name='supstkcod' value='$supstkcod' />";
	} else {
		$supadd = "";
	}

	// Layout
	$view = "
		<h3>Add Stock</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='markup'>
			$supadd
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan=2>$err</td>
			</tr>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Select Store</td>
							<td>$whs</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Stock code</td>
							<td><input type='text' size='20' name='stkcod' value='$stkcod'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Stock description</td>
							<td><textarea cols='18' rows='5' name='stkdes'>$stkdes</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Type</td>
							<td valign='center'>
								<input type='radio' name='stktp' value='stk' $tstk>Stock<b> | </b>
								<input type='radio' name='stktp' value='lab' $tlab> Services/Labour
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Serialized</td>
							<td valign='center'>
								<input type='radio' name='serd' value='yes' $serdy>Yes<b> | </b>
								<input type='radio' name='serd' value='no' $serdn> No
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID type of tag</td>
							<td valign='center'><input type='text' name='rfidtype' value='$rfidtype'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID (869.4 to 928 Mhz UHF)</td>
							<td valign='center'><input type='text' name='rfidfreq' value='$rfidfreq'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID Tag read rate</td>
							<td valign='center'><input type='text' name='rfidrate' value='$rfidrate'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Select Category</td>
							<td>$cats</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Classification</td>
							<td>$class</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Warranty</td>
							<td><input type='text' name='warranty' size=2>$warranty_sel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Upload Stock Image</td>
							<td><input type='radio' name='change_image' value='yes' $img_yes>Yes <b>|</b> <input type='radio' name='change_image' value='no' $img_no>No</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Buying Unit of measure</td>
							<td><input type='text' size='7' name='buom' value='$buom'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling Unit of measure</td>
							<td><input type='text' size='7' name='suom' value='$suom'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selling Units per Buying unit</td>
							<td><input type='text' size='5' name='rate' value='$rate'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Location</td>
							<td>Shelf <input type='text' size='5' name='shelf' value='$shelf'> Row <input type='text' size='5' name='row' value='$row'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Level</td>
							<td>Minimum <input type='text' size='5' name='minlvl' value='$minlvl'> Maximum <input type='text' size='5' name='maxlvl' value='$maxlvl'></td>
						</tr>
						<!--<tr class='".bg_class()."'>
							<td>Exempt from VAT</td>
							<td valign='center'><input type='radio' name='exvat' value='yes' $vaty>Yes | <input type='radio' name='exvat' value='no' $vatn> No</td>
						</tr>-->
						<tr class='".bg_class()."'>
							<td>Selling price per unit</td>
							<td>".CUR." <input type='text' size='7' name='selamt' value='$selamt'> $vat</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bar Code</td>
							<td><input type='text' size='20' name='bar' value='$bar'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Code</td>
							<td>$vatcodes</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Markup Percentage</td>
							<td><input type='text' name='markup' size='10' value='$markup'> %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier1</td>
							<td>$suppliers1</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier2</td>
							<td>$suppliers2</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier3</td>
							<td>$suppliers3</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>
		</form>";
	return $view;

}



# Confirm
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	$supplier1 += 0;
	$supplier2 += 0;
	$supplier3 += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Store.");
	$v->isOk ($stkcod, "string", 1, 50, "Invalid stock code.");
	$v->isOk ($bar, "string", 0, 20, "Invalid bar code.");
	$v->isOk ($catid, "num", 1, 50, "Invalid Stock Category.");
	$v->isOk ($stkdes, "string", 0, 255, "Invalid stock description.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
	$v->isOk ($buom, "string", 0, 10, "Invalid bought unit of measure.");
	$v->isOk ($suom, "string", 0, 10, "Invalid selling unit of measure.");
	$v->isOk ($rate, "num", 0, 10, "Invalid selling units per bought unit.");
	$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
	$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
	$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
	$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.");
	$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
	$v->isOk ($change_image, "string", 0, 3, "Invalid stock image selection.");
	$v->isOk ($markup, "float", 0, 10, "Invalid Markup value.");
	$v->isOk ($warranty, "num", 0, 9, "Invalid warranty.");
	$v->isOk ($warranty_range, "string", 0, 80, "Invalid warranty range.");
        $v->isOk ($rfidtype, "string", 0, 80, "Invalid RFID type of tag.");
	$v->isOk ($rfidfreq, "string", 0, 80, "Invalid RFID Frequency.");
	$v->isOk ($rfidrate, "string", 0, 80, "Invalid RFID Tag read rate.");

	$minlvl += 0;
	$maxlvl += 0;
	$selamt += 0;
	$markup += 0;

	if($rate < 1){
		$rate = 1;
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return view_err($_POST, $confirm);
	}


	# Get category account name
	db_connect();

	$sql = "SELECT cat FROM stockcat WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql);
	$cat = pg_fetch_array($catRslt);

	# Get Classification
	$sql = "SELECT * FROM stockclass WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		$class = "<li class='err'>Class not Found.</li>";
	}else{
		$clas = pg_fetch_array($clasRslt);
		$class = $clas['classname'];
	}

	# Get warehouse name
	db_conn("exten");

	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	if($stktp == 'stk'){
		$type = "Stock";
	}else{
		$type = "Labour";
	}

	# check stock code
	db_connect();

	$sql = "SELECT stkcod FROM stock WHERE lower(stkcod) = lower('$stkcod') AND whid = '$whid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> An item with stock code : <b>$stkcod</b> already exists in the selected store.</li>";
		return view_err($_POST, $error);
	}

	if(strlen($bar) > 0) {
		$sql = "SELECT bar FROM stock WHERE lower(bar) = lower('$bar') AND div = '".USER_DIV."'";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class='err'> An item with Bar Code : <b>$bar</b> already exists.</li>";
			return $error;
		}
	}

	$vat = (getSetting("SELAMT_VAT") == 'inc') ? "Including VAT" : "Excluding VAT";

	db_conn('cubit');

	$vatcode += 0;

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri = db_exec($Sl);

	$vd = pg_fetch_array($Ri);

	$vat = $vd['code'] . " $vd[description]";

	// Do we want the user to upload an image
	if ( $change_image == "yes" ) {
		$img = "
			<tr class='".bg_class()."'>
				<td>Image</td>
				<td><input type='file' size='20' name='image'> width = 230 height=47</td>
			</tr>";
	} else {
		$img = "";
	}

	if (!empty($warranty)) {
		$warranty_out = $warranty ." ". ucfirst($warranty_range);

		if ($warranty > 1) {
			$warranty_out .= "s";
		}

		$warranty = "$warranty $warranty_range";
	} else {
		$warranty = "";
		$warranty_range = "";
		$warranty_out = "";
	}

	$selamt = sprint ($selamt);

	/* adding from supplier stock */
	if (isset($supid) && isset($supstkcod)) {
		$supadd = "
			<input type='hidden' name='supid' value='$supid' />
			<input type='hidden' name='supstkcod' value='$supstkcod' />";
	} else {
		$supadd = "";
	}

	// Layout
	$confirm = "
		<h3>Add Stock</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form enctype='multipart/form-data' action='".SELF."' method='POST'>
			$supadd
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='stkcod' value='$stkcod'>
			<input type='hidden' name='stkdes' value='$stkdes'>
			<input type='hidden' name='stktp' value='$stktp'>
			<input type='hidden' name='serd' value='$serd'>
			<input type='hidden' name='rfidtype' value='$rfidtype'>
			<input type='hidden' name='rfidfreq' value='$rfidfreq'>
			<input type='hidden' name='rfidrate' value='$rfidrate'>
			<input type='hidden' name='clasid' value='$clasid'>
			<input type='hidden' name='buom' value='$buom'>
			<input type='hidden' name='suom' value='$suom'>
			<input type='hidden' name='rate' value='$rate'>
			<input type='hidden' name='shelf' value='$shelf'>
			<input type='hidden' name='row' value='$row'>
			<input type='hidden' name='minlvl' value='$minlvl'>
			<input type='hidden' name='maxlvl' value='$maxlvl'>
			<input type='hidden' name='selamt' value='$selamt'>
			<input type='hidden' name='bar' value='$bar'>
			<input type='hidden' name='vatcode' value='$vatcode'>
			<input type='hidden' name='supplier1' value='$supplier1'>
			<input type='hidden' name='supplier2' value='$supplier2'>
			<input type='hidden' name='supplier3' value='$supplier3'>
			<input type='hidden' name='change_image' value='$change_image'>
			<input type='hidden' name='markup' value='$markup'>
			<input type='hidden' name='warranty' value='$warranty'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
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
							<td><pre>$stkdes</pre></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Type</td>
							<td>$type</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Serialized</td>
							<td>$serd</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID type of tag</td>
							<td>$rfidtype</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID (869.4 to 928 Mhz UHF)</td>
							<td>$rfidfreq</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>RFID Tag read rate</td>
							<td>$rfidrate</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Category</td>
							<td>$cat[cat]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Product class</td>
							<td>$class</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Warranty</td>
							<td>$warranty_out</td>
						</tr>
						$img
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Field</th>
							<th>Value</th>
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
								<td>Selling price per unit</td>
								<td>".CUR." $selamt</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Bar Code</td>
								<td>$bar</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>VAT Code</td>
								<td>$vat</td>
							</tr>
							<tr class='".bg_class()."'>
								<td>Markup Percentage</td>
								<td>$markup %</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo; Correction'></td>
					<td align='right'><input type='submit' value='Write &raquo'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts." width='100'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-view.php'>View Stock</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $confirm;

}




# Write
function write($_POST)
{
	# Get vars
	extract ($_POST);

	$supplier1 += 0;
	$supplier2 += 0;
	$supplier3 += 0;


	if(isset($back)) {
		return view_err($_POST);
	}

	$vatcode += 0;

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Store.");
	$v->isOk ($catid, "num", 1, 50, "Invalid Stock Category.");
	$v->isOk ($stkcod, "string", 1, 50, "Invalid stock code.");
	$v->isOk ($stkdes, "string", 0, 255, "Invalid stock description.");
	$v->isOk ($bar, "string", 0, 20, "Invalid bar code.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
	$v->isOk ($buom, "string", 0, 10, "Invalid bought unit of measure.");
	$v->isOk ($suom, "string", 0, 10, "Invalid selling unit of measure.");
	$v->isOk ($rate, "num", 1, 10, "Invalid selling units per bought unit.");
	$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
	$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
	$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
	$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.");
	$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
	$v->isOk ($markup, "float", 0, 10, "Invalid markup percentage.");
	$v->isOk ($warranty, "string", 0, 80, "Invalid warranty.");
	$v->isOk ($rfidtype, "string", 0, 80, "Invalid RFID type of tag.");
	$v->isOk ($rfidfreq, "string", 0, 80, "Invalid RFID Frequency.");
	$v->isOk ($rfidrate, "string", 0, 80, "Invalid RFID Tag read rate.");


	$minlvl += 0;
	$maxlvl += 0;
	$selamt += 0;

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "
			<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>
			<P>
			<table ".TMPL_tblDflts." width='100'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-view.php'>View Stock</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</form>
			</table>";
		return $confirm;
	}

	# Get category name
	db_connect();
	$sql = "SELECT cat FROM stockcat WHERE catid = '$catid'";
	$catRslt = db_exec($sql);
	$cat = pg_fetch_array($catRslt);

	# Get class
	$sql = "SELECT classname FROM stockclass WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec($sql);
	$clas = pg_fetch_array($clasRslt);

	# Check stock code
	db_connect();
	$sql = "SELECT stkcod FROM stock WHERE lower(stkcod) = lower('$stkcod') AND whid = '$whid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> An item with stock code : <b>$stkcod</b> already exists in the selected store.</li>";
		return view_err($_POST, $error);
	}

	if(strlen($bar) > 0)
	{
		$sql = "SELECT bar FROM stock WHERE lower(bar) = lower('$bar') AND div = '".USER_DIV."'";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class='err'> An item with Bar Code : <b>$bar</b> already exists.</li>";
			return $error;
		}
	}

	# Insert into stock
	db_connect();

	$sql = "
		INSERT INTO stock (
			supplier1, supplier2, supplier3, stkcod, stkdes, prdcls, 
			classname, csamt, units, buom, suom, rate, 
			shelf, row, minlvl, maxlvl, csprice, selamt, 
			exvat, catid, catname, whid, blocked, type, 
			serd, alloc, com, bar, div, vatcode, 
			markup, rfidtype, rfidfreq, rfidrate, warranty
		) VALUES (
			'$supplier1', '$supplier2', '$supplier3', '$stkcod', '$stkdes', '$clasid', 
			'$clas[classname]', '0', '0', '$buom', '$suom', '$rate', 
			'$shelf', '$row', '$minlvl', '$maxlvl', '0', '$selamt', 
			'n', '$catid', '$cat[cat]', '$whid', 'n', '$stktp', 
			'$serd', '0', '0', '$bar', '".USER_DIV."', '$vatcode', 
			'$markup', '$rfidtype', '$rfidfreq', '$rfidrate', '$warranty'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);


	# Get last stock ID
	$stkid = pglib_lastid ("stock", "stkid");

	# Add this product to all pricelists
	db_conn("exten");

	$sql = "SELECT * FROM pricelist WHERE div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) > 0){
		while($list = pg_fetch_array($listRslt)){
			db_conn ("exten");
			$sql = "INSERT INTO plist_prices (listid, stkid, catid, clasid, price, div,show) VALUES ('$list[listid]', '$stkid', '$catid', '$clasid', '$selamt', '".USER_DIV."','Yes')";
			$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);
		}
	}

	$sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."'";
	$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($listRslt) > 0){
		while($list = pg_fetch_array($listRslt)){
			db_conn ("exten");
			$sql = "INSERT INTO splist_prices (listid, stkid, catid, clasid, price, div) VALUES ('$list[listid]', '$stkid', '$catid', '$clasid', '0', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);
		}
	}

	/* adding from supplier stock */
	if (isset($supid) && isset($supstkcod)) {
		$cols = grp(
			m("suppid", $supid),
			m("stkid", $stkid),
			m("stkcod", $supstkcod)
		);

		$upd = new dbUpdate("suppstock", "cubit", $cols);
		$upd->run(DB_INSERT);
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM stock WHERE stkid='$stkid'";
	$Ri = db_exec($Sl) or errDie("Unable to get stock.");

	$data = pg_fetch_array($Ri);

	$date = date("Y-m-d");

	db_conn('audit');

	$Sl = "SELECT * FROM closedprd ORDER BY id";
	$Ri = db_exec($Sl);

	while($pd = pg_fetch_array($Ri)) {

		db_conn($pd['prdnum']);

		$Sl = "
			INSERT INTO stkledger (
				stkid, stkcod, stkdes, trantype, edate, qty, 
				csamt, balance, bqty, details, div, yrdb
			) VALUES (
				'$data[stkid]', '$data[stkcod]', '$data[stkdes]', 'bal', '$date', '$data[units]', 
				'$data[csamt]', '$data[csamt]', '$data[units]', 'Balance', '".USER_DIV."', '".YR_DB."'
			)";
		$Ro = db_exec($Sl);
	}

	# deal with logo image
	global $_FILES;
	if ($change_image == "yes") {
		if (empty ($_FILES["image"])) {
			return "<li class='err'>Please select an image to upload from your hard drive.</li>";
		}
		if (is_uploaded_file ($_FILES["image"]["tmp_name"])) {
			# Check file ext
			if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $_FILES["image"]["type"], $extension)) {
				$type = $_FILES["image"]["type"];

				// open file in "read, binary" mode
				$img = "";
				$file = fopen ($_FILES['image']['tmp_name'], "rb");
				while (!feof ($file)) {
					// fread is binary safe
					$img .= fread ($file, 1024);
				}
				fclose ($file);
				# base 64 encoding
				$img = base64_encode($img);

				db_connect();
				$Sl = "INSERT INTO stkimgs (stkid, image, imagetype) VALUES ('$data[stkid]','$img','$type')";
				$Ry = db_exec($Sl) or errDie("Unable to upload company logo Image to DB.",SELF);

				# to show IMG
				//$logoimg = "<br><img src='compinfo/getimg.php' width=230 height=47><br><br>";
				//$logo = "compinfo/getimg.php";
			}else {
				return "<li class='err'>Please note that we only accept images of the types PNG,GIF and JPEG.</li>";
			}
		} else {
			return "Unable to upload file, Please check file permissions.";
		}
	}

	// Layout
	$write ="
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New Stock added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New Stock, $stkdes ($stkcod) has been successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}



?>
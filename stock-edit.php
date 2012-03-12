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
			if (isset($_GET['stkid'])){
				$OUTPUT = edit ($_GET['stkid']);
			} else {
				$OUTPUT = "<li>Invalid use of module</li>";
			}
	}
} else {
	if (isset($_GET['stkid'])){
		$OUTPUT = edit ($_GET['stkid']);
	} else {
		$OUTPUT = "<li>Invalid use of module</li>";
	}
}

# get template
require("template.php");




 # confirm
function edit($stkid,$err = "")
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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}



	global $_POST;

	extract($_POST);

	# Select Stock
	db_connect();

	$sql = "SELECT * FROM stock WHERE stkid = '$stkid' AND div = '".USER_DIV."'";
	$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($stkRslt) < 1){
		return "<li>Invalid Stock ID.</li>";
    }else{
        $stk = pg_fetch_array($stkRslt);
    }

	if(!isset($catid)) {
		# Get stock vars
		extract ($stk);
	} else {
		$prdcls = $clasid;
		$csamt = $stk['csamt'];
		$type = $stk['type'];
		$accid = $stk['accid'];
		$units = $stk['units'];
		$ordered = $stk['ordered'];
	}

	# Select the stock category
	db_connect();

	$cats = "<select name='catid' style='width: 167'>";
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

	$classes = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			if($clas['clasid'] == $prdcls){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$classes .= "<option value='$clas[clasid]' $sel>$clas[classname]</option>";
		}
	}
	$classes .= "</select>";

	if($csamt == 0 && $units == 0 && $ordered == 0){
		if($type == 'stk'){
			$tstk = "checked=yes";
			$tlab = "";
		}else{
			$tstk = "";
			$tlab = "checked=yes";
		}
		$ttype = "<input type='radio' name='stktp' value='stk' $tstk>Stock | <input type='radio' name='stktp' value='lab' $tlab> Services/Labour";
	}else{
		if($type == 'stk'){
			$typen = "Stock";
		}else{
			$typen = "Labour";
		}
		$ttype = "<input type='hidden' name='stktp' value='$type'> $typen";
	}

// 	if($exvat == 'yes'){
// 		$vaty = "checked=yes";
// 		$vatn = "";
// 	}else{
// 		$vaty = "";
// 		$vatn = "checked=yes";
// 	}

	if($serd == 'yes'){
		$serdy = "checked=yes";
		$serdn = "";
	}else{
		$serdy = "";
		$serdn = "checked=yes";
	}

	# Get warehouse name
	db_conn("exten");

	$sql = "SELECT whname FROM warehouses WHERE whid = '$whid' AND div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	$vat = (getSetting("SELAMT_VAT") == 'inc') ? "Including VAT" : "Excluding VAT";

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$vatcodes = "<select name='vatcode'>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $vatcode) {
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

	$warranty_ar = array (
		"year" => "Year/s",
		"month" => "Month/s",
		"day" => "Day/s"
	);

	if(strlen($warranty) > 0){
		$warr = explode (" ",$warranty);
		$warranty = $warr[0];
		$warranty_period = $warr[1];
	}else {
		$warranty = "";
		$warranty_period = "";
	}

	$warranty_sel = "<select name='warranty_range'>";
	foreach ($warranty_ar as $key=>$title) {
		if($warranty_period == $key){
			$warranty_sel .= "<option value='$key' selected>$title</option>";
		}else {
			$warranty_sel .= "<option value='$key'>$title</option>";
		}
	}
	$warranty_sel .= "</select>";


	if ($stkid != 0){

		db_connect ();

		#get 1 lower cusnum
		$get_prev = "SELECT stkid FROM stock WHERE stkid < '$stkid' ORDER BY stkid DESC LIMIT 1";
		$run_prev = db_exec($get_prev) or errDie ("Unable to get previous stock information.");
		if (pg_numrows($run_prev) > 0){
			$back_stkid = pg_fetch_result ($run_prev,0,0);
			$show_back_button = "<input type='button' onClick=\"document.location='stock-edit.php?stkid=$back_stkid';\" value='View Previous Item'>";
		}

		$get_next = "SELECT stkid FROM stock WHERE stkid > '$stkid' ORDER BY stkid ASC LIMIT 1";
		$run_next = db_exec($get_next) or errDie ("Unable to get next stock information.");
		if (pg_numrows($run_next) > 0){
			$next_stkid = pg_fetch_result ($run_next,0,0);
			$show_next_button = "<input type='button' onClick=\"document.location='stock-edit.php?stkid=$next_stkid';\" value='View Next Item'>";
		}

		$showbuttons = "$show_back_button $show_next_button <br><br>";

	}


	// Layout
	$edit = "
		$showbuttons
		$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='stkid' value='$stkid'>
			<input type='hidden' name='origid' value='$stkid'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='rfidtype' value='$rfidtype'>
			<input type='hidden' name='rfidfreq' value='$rfidfreq'>
			<input type='hidden' name='rfidrate' value='$rfidrate'>
			<input type='hidden' name='oldprice' value='$selamt'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Edit Stock</th>
						</tr>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Store</td>
							<td>$wh[whname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Stock code</td>
							<td><input type='text' size='20' name='stkcod' value='$stkcod'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>
								Supplier Stock Codes<br />
								<font style='font-size: 12px;'>(necessary for Transactioning<br />
									and Importing Supplier Pricelists)
								</font>
							</td>
							<td><input type='button' value='Assign/Edit/Remove'
									onClick='popupSized(\"supp_stkcod.php?id=$stkid\", \"suppstkcod$stkid\", 400, 300);' /></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Stock description</td>
							<td><textarea cols='18' rows='5' name='stkdes'>$stkdes</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Type</td>
							<td valign='center'>$ttype</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Serialized</td>
							<td valign='center'>
								<input type='radio' name='serd' value='yes' $serdy>Yes<b> | </b>
								<input type='radio' name='serd' value='no' $serdn> No
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>RFID type of tag</td>
							<td valign='center'><input type='text' name='rfidtype' value='$rfidtype'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>RFID (869.4 to 928 Mhz UHF)</td>
							<td valign='center'><input type='text' name='rfidfreq' value='$rfidfreq'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>RFID Tag read rate</td>
							<td valign='center'><input type='text' name='rfidrate' value='$rfidrate'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Select Category</td>
							<td>$cats</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Classification</td>
							<td>$classes</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Warranty</td>
							<td><input type='text' name='warranty' size='2' value='$warranty'> $warranty_sel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Upload Image</td>
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
							<th colspan='2'>Edit Stock</th>
						</tr>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Buying Unit of measure</td>
							<td><input type='text' size='10' name='buom' value='$buom'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Selling Unit of measure</td>
							<td><input type='text' size='10' name='suom' value='$suom'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Selling Units per Buying unit</td>
							<td><input type='text' size='10' name='rate' value='$rate'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Location</td>
							<td>Shelf <input type='text' size='5' name='shelf' value='$shelf'> Row <input type='text' size='5' name='row' value='$row'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Level</td>
							<td>Minimum <input type='text' size='5' name='minlvl' value='$minlvl'> Maximum <input type='text' size='5' name='maxlvl' value='$maxlvl'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Selling price per selling unit</td>
							<td>".CUR." <input type='text' size='14' name='selamt' value='".sprint($selamt)."'> $vat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Bar Code</td>
							<td><input type='text' size='20' name='bar' value='$bar'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Code</td>
							<td>$vatcodes</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Markup Value</td>
							<td><input type='text' size='10' name='markup' value='$markup'> %</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'><td>Supplier1</td>
							<td>$suppliers1</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier2</td>
							<td>$suppliers2</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
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
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $edit;

}




# confirm
function confirm($_POST)
{

	# Get vars
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
	$v->isOk ($catid, "num", 1, 50, "Invalid Stock Category.");
	$v->isOk ($stkcod, "string", 1, 50, "Invalid stock code.");
	$v->isOk ($stkdes, "string", 0, 255, "Invalid stock description.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
	$v->isOk ($buom, "string", 0, 10, "Invalid bought unit of measure.");
	$v->isOk ($suom, "string", 0, 10, "Invalid selling unit of measure.");
	$v->isOk ($rate, "num", 0, 10, "Invalid selling units per bought unit.");
	$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
	$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
	$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
	$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.<PRE>$maxlvl");
	$v->isOk ($markup, "float",0,10, "Invalid Markup Percentage.");
	$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
	$v->isOk ($bar, "string", 0, 20, "Invalid bar code.");
	$v->isOk ($change_image, "string", 0, 3, "Invalid image selection.");
	$v->isOk ($warranty, "num", 0, 9, "Invalid warranty.");
	$v->isOk ($warranty_range, "string", 1, 9, "Invalid warranty range.");
	$v->isOk ($rfidtype, "string", 0, 80, "Invalid RFID type of tag.");
	$v->isOk ($rfidfreq, "string", 0, 80, "Invalid RFID Frequency.");
	$v->isOk ($rfidrate, "string", 0, 80, "Invalid RFID Tag read rate.");

	$minlvl += 0;
	$maxlvl += 0;
	$selamt += 0;
	$markup += 0;

	$oldprice += 0;
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
		return $confirm.edit($stkid);
	}



	# Get category account name
	db_connect();

	$sql = "SELECT cat FROM stockcat WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		$cat['cat'] = "<li class='err'>Category not Found.</li>";
	}else{
		$cat = pg_fetch_array($catRslt);
	}

	# get Classification
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

	# check stock code
	db_connect();

	$sql = "SELECT stkcod FROM stock WHERE lower(stkcod) = lower('$stkcod') AND whid = '$whid' AND stkid != '$stkid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> An item with stock code : <b>$stkcod</b> already exists in the selected store.</li>";
		return edit($origid,$error);
	}

	
	#manufacturing ... extra check ... double stock = barcode check logic conflic
	if(strlen($bar) > 0) {
		$sql = "SELECT bar FROM stock WHERE lower(bar) = lower('$bar') AND stkid != '$stkid' AND div = '".USER_DIV."'";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class='err'> An item with Bar Code : <b>$bar</b> already exists.";
			return $error;
		}
	}

	if($stktp == 'stk'){
		$type = "Stock";
	}else{
		$type = "Labour";
	}

	db_conn('cubit');

	$vatcode += 0;

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
	$Ri = db_exec($Sl);

	$vd = pg_fetch_array($Ri);

	$vat = $vd['code'];

	$note = "";
	if($selamt != $oldprice) {
		$note = "<li class='err'> You have changed the selling price. <br>Please note this is only the default/cash price, to change customer prices do so at settings, stock(price lists).</li>";
	}

	$vat = (getSetting("SELAMT_VAT") == 'inc') ? "Including VAT" : "Excluding VAT";

	$supplier1 += 0;
	$supplier2 += 0;
	$supplier3 += 0;

	// Do we want the user to upload an image
	if ( $change_image == "yes" ) {
		$img = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Image</td>
				<td><input type='file' size='20' name='image'></td>
			</tr>";
	} else {
		$img = "";
	}

	$warranty_out = $warranty ." ". ucfirst($warranty_range);

	if ($warranty > 1) {
		$warranty_out .= "s";
	}

	$warranty = "$warranty $warranty_range";

	$selamt = sprint ($selamt);

	// Layout
	$confirm = "
		<h3>Edit Stock</h3>
		<h4>Confirm entry</h4>
		$note
		<table ".TMPL_tblDflts.">
		<form enctype='multipart/form-data' action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='whid' value='$whid'>
			<input type='hidden' name='stkid' value='$stkid'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='stkcod' value='$stkcod'>
			<input type='hidden' name='serd' value='$serd'>
			<input type='hidden' name='rfidtype' value='$rfidtype'>
			<input type='hidden' name='rfidfreq' value='$rfidfreq'>
			<input type='hidden' name='rfidrate' value='$rfidrate'>
			<input type='hidden' name='stkdes' value='$stkdes'>
			<input type='hidden' name='stktp' value='$stktp'>
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
			<input type='hidden' name='markup' value='$markup'>
			<input type='hidden' name='change_image' value='$change_image'>
			<input type='hidden' name='warranty' value='$warranty'>
			<tr>
				<th width='40%'>Field</th>
				<th width='60%'>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Store</td>
				<td>$wh[whname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock code</td>
				<td>$stkcod</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock description</td>
				<td><pre>$stkdes</pre></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type</td>
				<td>$type</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Serialized</td>
				<td>$serd</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>RFID type of tag</td>
				<td>$rfidtype</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>RFID (869.4 to 928 Mhz UHF)</td>
				<td>$rfidfreq</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>RFID Tag read rate</td>
				<td>$rfidrate</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category</td>
				<td>$cat[cat]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Classification</td>
				<td>$class</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Warranty</td>
				<td>$warranty_out</td>
			</tr>
			$img
			<tr bgcolor='".bgcolorg()."'>
				<td>Bought Unit of measure</td>
				<td>$buom</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Selling Unit of measure</td>
				<td>$suom</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Selling Units per Bought unit</td>
				<td>$rate</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Location</td>
				<td>Shelf : $shelf - Row : $row</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Minimum level</td>
				<td>$minlvl</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Maximum level</td>
				<td>$maxlvl</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Selling price per selling unit</td>
				<td>".CUR." $selamt $vat</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bar Code</td>
				<td>$bar</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$vat</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Markup Percentage</td>
				<td>$markup %</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}



# write
function write($_POST)
{

	# Get vars
	extract ($_POST);

	$supplier1 += 0;
	$supplier2 += 0;
	$supplier3 += 0;

	if(isset($back)) {
		return edit($stkid);
	}

	$vatcode += 0;


	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid stock id.");
	$v->isOk ($catid, "num", 1, 50, "Invalid Stock Category.");
	$v->isOk ($stkcod, "string", 1, 50, "Invalid stock code.");
	$v->isOk ($stkdes, "string", 0, 255, "Invalid stock description.");
	$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
	$v->isOk ($bar, "string", 0, 20, "Invalid bar code.");
	$v->isOk ($buom, "string", 0, 10, "Invalid bought unit of measure.");
	$v->isOk ($suom, "string", 0, 10, "Invalid selling unit of measure.");
	$v->isOk ($rate, "num", 1, 10, "Invalid selling units per bought unit.");
	$v->isOk ($shelf, "string", 0, 10, "Invalid Shelf number.");
	$v->isOk ($row, "string", 0, 10, "Invalid Row number.");
	$v->isOk ($minlvl, "num", 0, 10, "Invalid minimum stock level.");
	$v->isOk ($maxlvl, "num", 0, 10, "Invalid maximum stock level.");
	$v->isOk ($selamt, "float", 0, 10, "Invalid selling amount.");
	$v->isOk ($change_image, "string", 0, 3, "Invalid image selection.");
	$v->isOk ($markup, "float", 0, 10, "Invalid Markup Percentage.");
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Get category name
	db_connect();

	$sql = "SELECT cat FROM stockcat WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql);
	$cat = pg_fetch_array($catRslt);

	# Get class
	$sql = "SELECT classname FROM stockclass WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec($sql);
	$clas = pg_fetch_array($clasRslt);

	# check stock code
	db_connect();

	$sql = "SELECT stkcod FROM stock WHERE lower(stkcod) = lower('$stkcod') AND whid = '$whid' AND stkid != '$stkid' AND div = '".USER_DIV."'";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'> An item with stock code : <b>$stkcod</b> already exists in the selected store : </li>";
		return $error;
	}

	if(strlen($bar) > 0) {
		$sql = "SELECT bar FROM stock WHERE lower(bar) = lower('$bar') AND stkid != '$stkid' AND div = '".USER_DIV."'";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class='err'> An item with Bar Code : <b>$bar</b> already exists.</li>";
			return $error;
		}
	}


	# Insert the customer
	db_connect();

	$sql = "UPDATE stock SET supplier1='$supplier1',supplier2='$supplier2',supplier3='$supplier3',vatcode='$vatcode',bar='$bar', serd = '$serd', catid = '$catid', catname = '$cat[cat]', stkcod = '$stkcod', stkdes = '$stkdes', prdcls = '$clasid', classname = '$clas[classname]', buom = '$buom', suom = '$suom', rate = '$rate', shelf = '$shelf', row = '$row', minlvl = '$minlvl', type = '$stktp', maxlvl = '$maxlvl', selamt = '$selamt', markup = '$markup', warranty='$warranty', rfidtype='$rfidtype', rfidfreq='$rfidfreq', rfidrate='$rfidrate' WHERE stkid = '$stkid'";
	$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

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

				$Sl = "INSERT INTO stkimgs (stkid, image, imagetype) VALUES ('$stkid','$img','$type')";
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
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Stock edited</th>
			</tr>
			<tr class='datacell'>
				<td>Stock, $stkdes ($stkcod) has been successfully edited.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-add.php'>Add Stock</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>

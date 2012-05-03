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
require ("core-settings.php");

if (isset($_POST["update"])){
	$OUTPUT = save_entries ($_POST);
}elseif(isset($_POST["process"])){
	if(isset($_POST["supinv"]) AND strlen($_POST["supinv"]) > 0){
		$OUTPUT = save_entries ($_POST);
	}else {
		$OUTPUT = process_entries ($_POST);
	}
}else {
	if(isset($_POST["rem_selected"])){
		$OUTPUT = remove_entries ($_POST);
	}elseif(isset($_POST["remcost"])){
		$OUTPUT = remove_cost_entries ($_POST);
	}else {
		$OUTPUT = get_items ($_POST);
	}
}

require ("template.php");




function get_items ($_POST,$err="")
{

	extract ($_POST);

	$old_entries = "";
	$new_entry = "";
	$i = 0;
	$total = 0;
	$counter = 0;

	db_connect ();

	#get all entries currently in db
	$get_entries = "SELECT * FROM purch_batch_entries ORDER BY id DESC";
	$run_entries = db_exec($get_entries) or errDie("Unable to get batch entries.");
	if(pg_numrows($run_entries) > 0){
		$old_entries = "
			<tr>
				<td colspan='9' align='right'><input type='submit' name='remall' value='Select All'></td>
			</tr>
			<tr>
				<th>Supplier</th>
				<th>Item Account</th>
				<th>Date</th>
				<th>VAT Code</th>
				<th>Supp. Inv. No.</th>
				<th>Description</th>
				<th>Qty</th>
				<th>Unit Price (Incl. VAT)</th>
				<th>Remove</th>
			</tr>";

		while ($earr = pg_fetch_array($run_entries)){


			#supplier drop
			db_connect ();

			$get_supp = "SELECT * FROM suppliers WHERE supid = '$earr[supplier]' LIMIT 1";
			$run_supp = db_exec($get_supp) or errDie("Unable to get supplier information.");
			if(pg_numrows($run_supp) < 1){
				unset ($_POST["new"]);
				$showsupplier = "Supplier Not Found.";
			}else {
				$sarr = pg_fetch_array($run_supp);
				$showsupplier = "$sarr[supname]";
			}



			#get account drop
			core_connect();

			$sql = "SELECT * FROM accounts WHERE accid = '$earr[account]' LIMIT 1";
			$accRslt = db_exec($sql);
			if(pg_numrows($accRslt) < 1){
				unset ($_POST["new"]);
				$showaccount = "Account Not Found.";
			}else {
				$aarr = pg_fetch_array($accRslt);
				$showaccount = "$aarr[topacc]/$aarr[accnum] - $aarr[accname]";
			}


			#get vatcode information
			db_connect ();

			$Sl = "SELECT * FROM vatcodes WHERE id = '$earr[vatcode]' LIMIT 1";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");
			if(pg_numrows($Ri) < 1){
				$showvatcode = "Vatcode Not Found.";
			}else {
				$varr = pg_fetch_array($Ri);
				$showvatcode = $varr['code'];
			}


			$parr = explode("-",$earr['pdate']);
			$dates_year = $parr[0];
			$dates_month = $parr[1];
			$dates_day = $parr[2];


			if(isset($remall)){
				$removetick = "checked=yes";
			}else {
				$removetick = "";
			}

//								<input type='hidden' name='suppliers[$i]' value='$earr[supplier]'>
//								<input type='hidden' name='accounts[$i]' value='$earr[account]'>
//								<input type='hidden' name='dates_year[$i]' value='$dates_year'>
//								<input type='hidden' name='dates_month[$i]' value='$dates_month'>
//								<input type='hidden' name='dates_day[$i]' value='$dates_day'>
//								<input type='hidden' name='vatcodes[$i]' value='$earr[vatcode]'>
//								<input type='hidden' name='supinvs[$i]' value='$earr[supinv]'>
//								<input type='hidden' name='descriptions[$i]' value='$earr[description]'>
//								<input type='hidden' name='qtys[$i]' value='$earr[qty]'>
//								<input type='hidden' name='prices[$i]' value='$earr[price]'>

			$old_entries .= "
				<input type='hidden' name='ids[$i]' value='$earr[id]'>
				<tr class='".bg_class()."'>
					<td>$showsupplier</td>
					<td>$showaccount</td>
					<td nowrap>$dates_year-$dates_month-$dates_day</td>
					<td>$showvatcode</td>
					<td>$earr[supinv]</td>
					<td>$earr[description]</td>
					<td>$earr[qty]</td>
					<td>".CUR." $earr[price]</td>
					<td><input type='checkbox' name='remids[$i]' value='$earr[id]' $removetick></td>
				</tr>";
			$i++;
			$counter++;
			$total = $total + ($earr['qty'] * $earr['price']);
			if($counter == 20){
				$old_entries .= "
					<tr>
						<td colspan='9' align='right'><input type='submit' name='process' value='Process'></td>
					</tr>";
				$counter = 0;
			}
		}
		$total = sprint ($total);
		$old_entries .= "
			<tr class='".bg_class()."'>
				<td colspan='5'></td>
				<th colspan='2'>Total</th>
				<th align='left'>".CUR." $total</th>
				<td></td>
			</tr>
			<tr>
				<td colspan='9' align='right'><input type='submit' name='rem_selected' value='Remove Selected'></td>
			</tr>";
	}


	$new = "";
	#generate new field
	if (isset($new)){
		
//		if(!isset($))
//			$ = "";
			
					
//			$supplier
//			$account
// 		if(!isset($date_year) OR (strlen($date_year) < 4))
// 			$date_year = date("Y");
// 		if(!isset($date_month) OR (strlen($date_month) < 2))
// 			$date_month = date("m");
// 		if(!isset($date_day) OR (strlen($date_day) < 2))
// 			$date_day = date("d");

		if (!isset ($date_day)){
			$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
			if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
				$trans_date_value = getCSetting ("TRANSACTION_DATE");
				$date_arr = explode ("-", $trans_date_value);
				$date_year = $date_arr[0];
				$date_month = $date_arr[1];
				$date_day = $date_arr[2];
			}else {
				$date_year = date("Y");
				$date_month = date("m");
				$date_day = date("d");
			}
		}

		if(!isset($vatcode))
			$vatcode = "";
		if(!isset($supinv))
			$supinv = "";
		if(!isset($description))
			$description = "";
		if(!isset($qty))
			$qty = "";
		if(!isset($price))
			$price = "";
							
				

		#get supplier drop
		db_connect ();

		$get_supp = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' AND ((length(blocked) < 3) OR (blocked IS NULL)) ";
		$run_supp = db_exec($get_supp) or errDie("Unable to get supplier information.");
		if(pg_numrows($run_supp) < 1){
			unset ($_POST["new"]);
			return "<li class='err'>No Suppliers Found.</li><br>".
				mkQuickLinks(
					ql("supp-new.php","Add Supplier"),
					ql("supp-view.php","View Suppliers"),
					ql("purchase-new.php","Add Purchase"),
					ql("purchase-view.php","View Purchases"),
					ql("nons-purchase-new.php","Add Non-Stock Purchase"),
					ql("nons-purchase-view.php","View Non-Stock Purchases")
				);
//			return get_items ($_POST,"<li class='err'>No Suppliers Found.</li>");
		}else {
			$supplier_drop = "<select name='supplier'>";
			while ($sarr = pg_fetch_array($run_supp)){
				if(isset($supplier) AND ($supplier == $sarr['supid'])){
					$supplier_drop .= "<option value='$sarr[supid]' selected>$sarr[supname]</option>";
				}else {
					$supplier_drop .= "<option value='$sarr[supid]'>$sarr[supname]</option>";
				}
			}
			$supplier_drop .= "</select>";
		}



		#get account drop
		core_connect();

		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			unset ($_POST["new"]);
			return get_items ($_POST,"<li class='err'>There are No accounts in Cubit.</li>");
		}

		$accounts_drop = mkAccSelect ("account", $account);

// 		$accounts_drop = "<select name='account'>";
// 		while($acc = pg_fetch_array($accRslt)){
// 			# Check Disable
// 			if(isDisabled($acc['accid']))
// 				continue;
// 			if(isset($account) AND ($account == $acc['accid'])){
// 				$accounts_drop .= "<option value='$acc[accid]' selected>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 			}else {
// 				$accounts_drop .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 			}
// 		}
// 		$accounts_drop .= "</select>";



		#get vatcode information
		db_connect ();

		$Sl="SELECT * FROM vatcodes ORDER BY code";
		$Ri=db_exec($Sl) or errDie("Unable to get vat codes");
		$vatcodes_drop = "
			<select name='vatcode'>
				<option value='0'>Select</option>";
		while($vd = pg_fetch_array($Ri)) {
			if($vatcode == $vd['id']){
				$vatcodes_drop .= "<option value='$vd[id]' selected>$vd[code]</option>";
			}else {
				$vatcodes_drop .= "<option value='$vd[id]'>$vd[code]</option>";
			}
		}
		$vatcodes_drop .= "</select>";

		#get the projects
		$get_pros = "SELECT * FROM projects WHERE project_name != 'No Project' ORDER BY project_name";
		$run_pros = db_exec($get_pros) or errDie("Unable to get project information.");
		if(pg_numrows($run_pros) < 1){
			$project_drop = "<li class='err'>No Projects Found.</li>";
		}else {
			$parr = pg_fetch_array ($run_pros);
			$project_drop = "<select name='project'>";
		}

		$costs = "";
		$get_newpros = "SELECT * FROM purch_batch_entries_newcostcenters";
		$run_newpros = db_exec($get_newpros) or errDie("Unable to get cost center listing");
		if(pg_numrows($run_newpros) < 1){
			$projects = array ();
		}else {
			while ($parr = pg_fetch_array ($run_newpros)){
				$costs .= "
					<tr class='".bg_class()."'>
						<td colspan='2'>
							<input type='text' size='15' name='old_project[$parr[id]]' value='$parr[project]'>
							<input type='text' size='15' name='old_costcenter[$parr[id]]' value='$parr[costcenter]'>
							<input type='text' size='15' name='old_costperc[$parr[id]]' value='$parr[costperc]'>
							<input type='checkbox' name='remcost' value='$parr[id]' onChange='document.form.submit();'> (Remove)
						</td>
					</tr>";
			}
		}



	if(!isset($newproject) OR (strlen($newproject) < 1)){
		$showproject = "value='Project ' onFocus=\"value=''\"";
	}else {
		$showproject = "value='$newproject'";
	}

	if(!isset($newcostcenter) OR (strlen($newcostcenter) < 1)){
		$showcostcenter = "value='Cost Center' onFocus=\"value=''\"";
	}else {
		$showcostcenter = "value='$newcostcenter'";
	}

	if(!isset($newcostperc) OR (strlen($newcostperc) < 1)){
		$showcostperc = "value='100'";
	}else {
		$showcostperc = "value='$newcostperc'";
	}

	$csetting = getsetting("CC_USE");

	if($csetting == "use"){
		$showcostcenters = "
			<tr>
				<th colspan='2'>Cost Center</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2' nowrap>
					<input type='text' size='15' name='newproject' $showproject>
					<input type='text' size='15' name='newcostcenter' $showcostcenter>
					<input type='text' size='4' name='newcostperc' $showcostperc> Percent
				</td>
			</tr>
			$costs";
	}else {
		$showcostcenters = "";
	}

		$new_entry .= "
			<tr>
				<th>Supplier</th>
				<th colspan='3'>Item Account <input align='right' type='button' onClick=\"window.open('core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Date</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$supplier_drop</td>
				<td colspan='3'>$accounts_drop</td>
				<td nowrap>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
			</tr>
			<tr>
				<th>Supp. Inv. No.</th>
				<th>Description</th>
				<th>Qty</th>
				<th>Unit Price (Incl. VAT)</th>
				<th>VAT Code</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' size='5' name='supinv' value='$supinv'></td>
				<td><input type='text' size='25' name='description' value='$description'></td>
				<td><input type='text' size='5' name='qty' value='$qty'></td>
				<td><input type='text' size='15' name='price' value='$price'></td>
				<td>$vatcodes_drop</td>
			</tr>
			$showcostcenters";
	}

	$showsetting = getsetting("CC_USE");


	$display = "
		<h2>Batch Creditor Non-Stock Invoices</h2>
		$err
		<br>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='save'>
		<table ".TMPL_tblDflts." width='95%'>
			$new_entry
			<td><input type='submit' name='update' value='Add Item'></td>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts." width='95%'>
			$old_entries
			".TBL_BR."
			<tr>
				<td colspan='9' align='right'><input type='submit' name='process' value='Process'></td>
			</tr>
		</table>
		</form>
		<p>".
	mkQuickLinks(
		ql("purchase-new.php","Add Purchase"),
		ql("purchase-view.php","View Purchases"),
		ql("nons-purchase-new.php","Add Non-Stock Purchase"),
		ql("nons-purchase-view.php","View Non-Stock Purchases")
	);
	return $display;

}























function process_entries ($_POST)
{

	extract ($_POST);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	db_connect ();


pglib_transaction("BEGIN") or errDie("Unable to start transaction.");

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");
	$vatinc = "yes";


	#remove any selected entries
	foreach ($ids as $keys => $value){
		if(isset($remids[$keys]) AND ($remids[$keys] == $value)){
			db_connect ();
			#now remove this entry
			$rem_sql = "DELETE FROM purch_batch_entries WHERE id = '$ids[$keys]'";
			$run_rem = db_exec($rem_sql) or errDie("Unable to remove processed batch entry.");
			continue;
		}
	}

		db_connect();

		#first we get the suppliers involved
		$get_sup = "SELECT distinct(supplier) FROM purch_batch_entries";
		$run_sup = db_exec($get_sup) or errDie("Unable to get supplier information.");
		if(pg_numrows($run_sup) < 1){
			return get_items ($_POST,"<li class='err'>Please Add At Least One Item.</li>");
		}else {
			while ($sarr0 = pg_fetch_array($run_sup)){

				#get this supplier's name
				$get_supp = "SELECT supname,supaddr FROM suppliers WHERE supid = '$sarr0[supplier]' LIMIT 1";
				$run_supp = db_exec($get_supp) or errDie("Unable to get supplier information.");
				if(pg_numrows($run_supp) < 1){
					$supname = "";
					$supaddr = "";
				}else {
					$sarr = pg_fetch_array($run_supp);
					$supname = $sarr['supname'];
					$supaddr = $sarr['supaddr'];
				}

				#get distinct invs
				$get_inv = "SELECT distinct (supinv) FROM purch_batch_entries WHERE supplier = '$sarr0[supplier]'";
				$run_inv = db_exec($get_inv) or errDie("Unable to get batch entries.");
				if(pg_numrows($run_inv) < 1){
					return get_items ($_POST,"<li class='err'>Please Add At Least One Item.</li>");
				}else {
					while ($earr = pg_fetch_array($run_inv)){

						#get the info + each entry = new line item
						$get_items = "SELECT * FROM purch_batch_entries WHERE supplier = '$sarr0[supplier]' AND supinv = '$earr[supinv]'";
						$run_items = db_exec($get_items) or errDie("Unable to get purchase information.");
						if(pg_numrows($run_items) < 1){
							return get_items($_POST,"<li class='err'>Please Add At Least One Item.</li>");
						}else {
							$total = 0;
							while ($arr1 = pg_fetch_array($run_items)){

								if (strtotime($arr1['pdate']) >= strtotime($blocked_date_from) AND strtotime($arr1['pdate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
									return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
								}

								#calculate the total
								$total = $total + ($arr1['qty'] * $arr1['price']);
								$pdate = $arr1['pdate'];
								$ddate = $arr1['pdate'];
							}
						}

						#get the info + each entry = new line item
						$get_items = "SELECT * FROM purch_batch_entries WHERE supplier = '$sarr0[supplier]' AND supinv = '$earr[supinv]'";
						$run_items = db_exec($get_items) or errDie("Unable to get purchase information.");
						if(pg_numrows($run_items) < 1){
							return get_items($_POST,"<li class='err'>Please Add At Least One Item.</li>");
						}else {
							#################[ write the non stock purchase ]################
							$remarks = "";
							$supaddr = "";
							$terms = "0";
							$total = 0;
							$subtot = 0;
					//		$pdate = "$dates_year[$keys]-$dates_month[$keys]-$dates_day[$keys]";
					//		$ddate = $arr['sdate'];
							$shipchrg = "0.00";
							$purnum = divlastid("pur", USER_DIV);
							$typeid = 0;

							if(!isset($ctyp))
								$ctyp = "s";
//old ...
//'$sarr0[supplier]','2','$supname','$supaddr','$terms',
							# Insert Order to DB
							$sql = "
								INSERT INTO nons_purchases (
									supid, deptid, supplier, supaddr, terms, pdate, ddate, 
									shipchrg, subtot, total, balance, vatinc, vat, remarks, received, done, 
									prd, div, purnum, ctyp, typeid
								) VALUES (
									'$sarr0[supplier]', '2', '$supname', '$supaddr', '$terms', '$pdate', '$ddate', 
									'$shipchrg', '$subtot', '$total', '$total', 'yes', '0', '$remarks', 'n', 'n', 
									'".PRD_DB."', '".USER_DIV."', '$purnum', '$ctyp', '$typeid'
								)";

							$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);
							#################################################################

							$refnum = getrefnum();
							$resub = 0;
							$totstkamt = array();
							$revat = 0;

							#######[ NOW WRITE THE ITEMS ]########
							while ($arr1 = pg_fetch_array($run_items)){

								if(!in_array("$arr1[id]",$ids));

								$darr = explode ("-",$arr1['pdate']);
								$suppliers[$keys] = $arr1['supplier'];
								$accounts[$keys] = $arr1['account'];
								$dates_year[$keys] = $darr[0];
								$dates_month[$keys] = $darr[1];
								$dates_day[$keys] = $darr[2];
								$vatcodes[$keys] = $arr1['vatcode'];
								$supinvs[$keys] = $arr1['supinv'];
								$descriptions[$keys] = $arr1['description'];
								$qtys[$keys] = $arr1['qty'];
								$prices[$keys] = $arr1['price'];

								# Get next ordnum
								$purid = lastpurid();

								$novat[$keys] = "1";

								# Calculate amount
								$amt[$keys] = ($qtys[$keys] * $prices[$keys]);

								$tv = $vatinc;
								db_conn('cubit');
								$Sl = "SELECT * FROM vatcodes WHERE id='$vatcodes[$keys]'";
								$Ri = db_exec($Sl);
								if(pg_num_rows($Ri) < 1) {
									return get_items($_POST, "<li class='err'>Please select the vatcode for all your items.</li>");
								}
								$vd = pg_fetch_array($Ri);
								$VATP = $vd['vat_amount'];
								if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
									$showvat = FALSE;
								}
								# Check Tax Excempt
								if($vd['zero'] == "Yes"){
									$vat[$keys] = 0;
									$vatinc = "novat";
								}

								if(isset($novat[$keys]) || strlen($vat[$keys]) < 1){
									# If vat is not included
									if($vatinc == "no"){
										$vat[$keys] = sprint(($VATP/100) * $amt[$keys]);
									}elseif($vatinc == "yes"){
										$vat[$keys] = sprint(($amt[$keys]/(100 + $VATP)) * $VATP);
									}else{
										$vat[$keys] = 0;
									}
								}

								if($vatinc == "novat"){
									$vat[$keys] = 0;
								}

								if($vatinc != "novat"){
									# If vat is not included
									if($vatinc == "no"){
										$vat[$keys] = sprintf("%01.2f", (($VATP/100) * $amt[$keys]));
									}else{
										$vat[$keys] = sprintf("%01.2f", (($amt[$keys]/($VATP + 100)) * $VATP));
									}
								}

								$vatinc=$tv;

								# insert Order items
								$sql = "
									INSERT INTO nons_pur_items (
										purid, cod, des, 
										qty, unitcost, amt, 
										svat, ddate, div, vatcode
									) VALUES (
										'$purid', '$supinvs[$keys]', '$descriptions[$keys]', 
										'$qtys[$keys]', '$prices[$keys]', '$amt[$keys]', 
										'$vat[$keys]', '$ddate', '".USER_DIV."','$vatcodes[$keys]'
									)";
								$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
//FP
								$itemid = pglib_lastid ("nons_pur_items","id");
								
								$SUBTOT = "";
								$TOTAL = "";

								# calculate subtot
								if(isset($amt[$keys])){
									$SUBTOT = $amt[$keys];
								}else{
									$SUBTOT = 0.00;
								}

								# If there vatable items
								if(isset($vat[$keys])){
									$VAT = $vat[$keys];
								}else{
									$VAT = 0;
								}

								# Total
								$TOTAL = $SUBTOT;

								# If vat is not included
								if($vatinc == "no"){
									$TOTAL = ($TOTAL + $VAT);
								}else{
									$TOTAL = $TOTAL;
									$SUBTOT -= ($VAT);
								}

								# insert Order to DB
								$sql = "
									UPDATE nons_purchases 
									SET terms = '$terms', 
										subtot = subtot + '$SUBTOT', total = total + '$TOTAL',
										balance = balance + '$TOTAL', vatinc = 'yes', vat = vat + '$VAT',
										supinv='$supinvs[$keys]'
									WHERE purid = '$purid' AND div = '".USER_DIV."'";
								$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
								

								
#####################[ BEGIN PROCESSING ]#######################

								db_connect();
					
								$td = $pdate;
					
								# amount of stock in
								$amt[$keys] = ($qtys[$keys] * $prices[$keys]);
					
								$SUBTOTAL = $amt[$keys];


	
								# Get selected stock line
								$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND id = '$itemid' AND div = '".USER_DIV."'";
								$stkdRslt = db_exec($sql);
								$stkd = pg_fetch_array($stkdRslt);
					
								# Calculate cost amount bought
								$amt[$keys] = ($qtys[$keys] * $prices[$keys]);
					
								/* delivery charge */
					
								# Calculate percentage from subtotal
								$perc[$keys] = (($amt[$keys]/$SUBTOTAL) * 100);
					
								/* end delivery charge */
					
								# the subtotal + delivery charges
								$resub += $amt[$keys];
					
								# calculate vat
								$svat[$keys] = svat($amt[$keys], $stkd['amt'], $stkd['svat']);

								db_conn('cubit');
						
								$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
								$Ri = db_exec($Sl) or errDie("Unable to get data.");

								$vd = pg_fetch_array($Ri);
					
								vatr($vd['id'],$pdate,"INPUT",$vd['code'],$refnum,"VAT for Non-Stock Purchase No. $purnum",-$amt[$keys],-$svat[$keys]);
					
								# received vat
								$revat += $svat[$keys];
					
								# make amount vat free
								$amt[$keys] = ($amt[$keys] - $svat[$keys]);
					


								# keep records for transactions
								if(isset($totstkamt[$accounts[$keys]])){
									$totstkamt[$accounts[$keys]] += $amt[$keys];
								}else{
									$totstkamt[$accounts[$keys]] = $amt[$keys];
								}

								# check if there are any outstanding items
								$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";// the old method check for this .. we receive everything NOW so we dont need this AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
								$stkdRslt = db_exec($sql);
								# if none the set to received
								if(pg_numrows($stkdRslt) < 1){
									//NO items were found ... problem somewhere
						
								}else {
									# update surch_int(received = 'y')
									$sql = "UPDATE nons_purchases SET received = 'y' WHERE purid = '$purid' AND div = '".USER_DIV."'";
									$rslt = db_exec($sql) or errDie("Unable to update international Orders in Cubit.",SELF);

								//	while ($uniarr1 = pg_fetch_array($stkdRslt)){
										# Update Order items
										$sql = "UPDATE nons_pur_items SET rqty = (rqty + '$qtys[$keys]'), accid = '$accounts[$keys]' WHERE purid='$purid' AND div = '".USER_DIV."' AND id = '$itemid'";
										$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
								//	}
								}
								# Update Order on the DB
								$sql = "UPDATE nons_purchases SET  typeid = '2', refno = '', remarks = '$remarks' WHERE purid = '$purid' AND div = '".USER_DIV."'";
								$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);
					
					
								/* - Start Hooks - */
					
								$vatacc = gethook("accnum", "salesacc", "name", "VAT");
					
								if(isset($suppliers[$keys])){
									$typeid = $suppliers[$keys];
									db_connect ();
									$sql = "SELECT * FROM suppliers WHERE supid = '$suppliers[$keys]' AND div = '".USER_DIV."'";
									$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
									if (pg_numrows ($supRslt) < 1) {
										$error = "<li class='err'> Supplier not Found.</li>";
										$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
										return $confirm;
									}else{
										$sup = pg_fetch_array($supRslt);
										$pur['supplier'] = $sup['supname'];
										$pur['supaddr'] = $sup['supaddr'];
							
										# Get department info
										db_conn("exten");
										$sql = "SELECT * FROM departments WHERE deptid = '$sup[deptid]' AND div = '".USER_DIV."'";
										$deptRslt = db_exec($sql);
										if(pg_numrows($deptRslt) < 1){
											return "<i class='err'>Department Not Found</i>";
										}else{
											$dept = pg_fetch_array($deptRslt);
										}
										$supacc = $dept['credacc'];
									}
								}


								/* - End Hooks - */
	### DATA SET 2 WAS HERE

#####################################################################################

//pglib_transaction("COMMIT") or errDie("Unable to complete transaction.");


	#received so now move

//pglib_transaction("BEGIN") or errDie("Unable to start transaction.");


## move stuff went here



#####################################################################################

			db_connect ();

//			$sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' LIMIT 1";
//			$banks = db_exec($sql);
//			if(pg_numrows($banks) < 1){
//				return "<li class='err'> There are no accounts held at the selected Bank.
//				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
//			}
//			$barr = pg_fetch_array($banks);
//			$bankid = $barr['bankid'];

//			core_connect();
//			$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
//			$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
//			# Check if link exists
//			if(pg_numrows($rslt) <1){
//				return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
//			}

//			$banklnk = pg_fetch_array($rslt);

//			$cc_trantype = cc_TranTypeAcc($arr1['account'], $banklnk['accnum']);
//
//			if(($arr1['project_id'] == 0) OR (strlen($arr1['project_code']) < 1) OR (strlen($arr1['center_code']) < 1) OR (strlen($arr1['center_perc']) < 1)){
//				$cc = "
//						<script>
//							CostCenter('$cc_trantype', 'Non Stock Purchase', '$arr1[pdate]', '$arr1[description]', '$arr1[price]', '');
//						</script>
//						";
//			}else {
//				$cc = "";
//			}

//								$get_data = "SELECT * FROM purch_batch_entries WHERE id = '$ids[$keys]' LIMIT 1";
								$get_data = "SELECT * FROM purch_batch_entries WHERE id = '$arr1[id]' LIMIT 1";
								$run_data = db_exec($get_data) or errDie("Unable to get cost ledger information.");
								if(pg_numrows($run_data) < 1){ 
									return "
										<table ".TMPL_tblDflts.">
											<tr>
												<td><li class='err'>Unable to get batch entry information.</li></td>
											</tr>
										</table>
										<br>".
										mkQuickLinks(
											ql("nons-purch-batch-add.php","Batch Creditors Non-Stock Invoices Add"),
											ql("purchase-new.php","Add Purchase"),
											ql("purchase-view.php","View Purchases"),
											ql("nons-purchase-new.php","Add Non-Stock Purchase"),
											ql("nons-purchase-view.php","View Non-Stock Purchases")
										);
								}

								$data_arr = pg_fetch_array ($run_data);

								$csetting = getsetting("CC_USE");

								if($csetting == "use"){

									# we want to store all the relevant information in a neat and orderly manner ...
									$get_info = "SELECT * FROM purch_batch_entries_costcenters WHERE batch_entry = '$arr1[id]'";//$ids[$keys]'";
									$run_info = db_exec ($get_info) or errDie ("Unable to get batch entry cost ledger information.");
									if(pg_numrows($run_info) < 1){
										#no cost entries ????
									}else {
										$ccenters = "";
					
										while ($arr = pg_fetch_array($run_info)){
											$amt_vat = (($arr1['price']/(100+$vd['vat_amount']))*$vd['vat_amount']);
											$amount = $arr1['qty'] * ($arr1['price'] - $amt_vat);
					
											db_connect ();
					
											#get project id
											$get_pro = "SELECT * FROM projects WHERE code = '$arr[project]' LIMIT 1";
											$run_pro = db_exec($get_pro) or errDie("Unable to get project information.");
											if(pg_numrows($run_pro) < 1){
												$project = 0;
											}else {
												$pro_arr = pg_fetch_array ($run_pro);
												$project = $pro_arr['id'];
											}
					
											#get costcenter id
											$get_cst = "SELECT * FROM costcenters WHERE centercode = '$arr[costcenter]' LIMIT 1";
											$run_cst = db_exec($get_cst) or errDie ("Unable to get cost center information.");
											if(pg_numrows($run_cst) < 1){
												$costcenterlink = 0;
											}else {
												$cst_arr = pg_fetch_array($run_cst);
												$costcenterlink = $cst_arr['ccid'];
											}

											#get costcenter/project link id
											$get_link = "SELECT * FROM costcenters_links WHERE ccid = '$costcenterlink' AND project1 = '$project' LIMIT 1";
											$run_link = db_exec($get_link) or errDie ("Unable to get cost center link information.");
											if(pg_numrows($run_link) < 1){
												return "<li class='err'>Unable to get cost ledger link information.</li>";
											}
					
											$cc = pg_fetch_array ($run_link);
					
											$edate = ext_rdate($data_arr['pdate']);
											$edarr = explode ("-",$edate);
											$prd = $edarr[1];



											$ccamts = sprint($amount * ($arr['costperc']/100));
					
											#we need to connect to the actual period db
											db_conn($prd);
											$sql = "
												INSERT INTO cctran (
													ccid, trantype, typename, edate, description, 
													amount, username, div, project
												) VALUES (
													'$cc[ccid]', 'ct', 'Non Stock Purchase', '$data_arr[pdate]', '$data_arr[description]', 
													'$ccamts', '".USER_NAME."', '".USER_DIV."', '$project'
												)";
											$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
					
										}
									}
								}

								db_connect ();
								#now remove this entry
								$rem_sql = "DELETE FROM purch_batch_entries WHERE id = '$arr1[id]'";
								$run_rem = db_exec($rem_sql) or errDie("Unable to remove processed batch entry.");

								#remove cost center
								$rem_sql2 = "DELETE FROM purch_batch_entries_costcenters WHERE batch_entry = '$arr1[id]'";
								$run_rem2 = db_exec($rem_sql2) or errDie("Unable to remove cost center information.");

							}

							# Get Order info
							db_connect();
							$sql = "SELECT * FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
							$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
							if (pg_numrows ($purRslt) < 1) {
								return "<li>- Order Not Found</li>";
							}
							$pur = pg_fetch_array($purRslt);
							$sid = $pur['supid'];
							$purnum = $pur['purnum'];

							if($pur['received'] == "y"){
					
								if(isset($bankid)) {
									$sid = $bankid;
									$sid += 0;
								}
	### DATA SET 2 ...
	
								$detadd = "";
								if(isset($sid)){
									$detadd = " from Supplier $pur[supplier]";
								}
					
								$sdate = $pdate;
								$tpp = 0;
								$ccamt = 0;
								# record transaction  from data
					
								if(isset($BA)) {
									$supacc = $BA;
								}

								foreach($totstkamt as $stkacc => $wamt){
									writetrans($stkacc, $supacc, $td, $refnum, $wamt, "Non-Stock Purchase No. $purnum Received $detadd.");
									pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $purnum Received $detadd.", $wamt, "Cash Order");
								}

								# vat
								$vatamt = $revat;
					
								# Add vat if not included
								$retot = ($resub);

					
								if(isset($sid)){
									db_connect();
									# update the supplier (make balance more)
									$sql = "UPDATE suppliers SET balance = (balance + '$retot') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
									$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
								}
					
								if(isset($sid)){
									# Ledger Records
									$DAte = $pur['pdate'];
									suppledger($sup['supid'], $stkacc, $DAte, $purid, "Non-Stock Purchase No. $purnum received.", $retot, 'c');
								}

								if($vatamt <> 0){
									# Debit bank and credit the account involved
									writetrans($vatacc, $supacc, $td, $refnum, $vatamt, "Non-Stock Purchase VAT paid on Non-Stock Order No. $purnum $detadd.");
									pettyrec($supacc, $sdate, "ct", "Non-Stock Purchase No. $purnum Received $detadd.", $vatamt, "Cash Order VAT");
					
									# Record the payment on the statement
									db_connect();
									$sdate = $pdate;
								}

//								if(isset($bankid)) {
//									db_connect();
//									$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div) VALUES ('$bankid', 'withdrawal', '$sdate', '$pur[supplier]', 'Non-Stock Purchase No. $pur[purnum] received', '0', '$retot', '$vatamt', '$pur[vatinc]', 'no', '$stkacc', '".USER_DIV."')";
//									$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
//								}

								if(isset($sid)){
									db_connect();
									$DAte = $pdate;
									$sql = "
										INSERT INTO sup_stmnt (
											supid, edate, cacc, amount, 
											descript, ref, ex, div
										) VALUES (
											'$pur[supid]', '$pur[pdate]', '$stkacc', '$retot', 
											'Non Stock Purchase No. $purnum Received', '$refnum', '$purnum', '".USER_DIV."'
										)";
									$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);
									db_connect();
									# update the supplier age analysis (make balance less)
									/* Make transaction record for age analysis */
									$sql = "
										INSERT INTO suppurch (
											supid, purid, pdate, balance, div
										) VALUES (
											'$pur[supid]', '$purnum', '$pur[pdate]', '$retot', '".USER_DIV."'
										)";
									$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
								}
	
	###
								# copy Order
								db_conn(PRD_DB);
								$sql = "
									INSERT INTO nons_purchases (
										purid, deptid, supplier, supaddr, terms, 
										pdate, ddate, shipchrg, shipping, subtot, 
										total, balance, vatinc, vat, remarks, 
										refno, received, done, ctyp, typeid, 
										div, purnum, supid, mpurid, is_asset, supinv
									) VALUES (
										'$purid', '$pur[deptid]', '$pur[supplier]',  '$pur[supaddr]', '$pur[terms]', 
										'$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[shipping]', '$pur[subtot]', 
										'$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', '$pur[remarks]', 
										'$pur[refno]', 'y', 'y', '$pur[ctyp]', '$pur[typeid]', 
										'".USER_DIV."', '$pur[purnum]','$sid', '$supacc', '$pur[is_asset]', '$pur[supinv]'
									)";
								$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);
					
								db_connect();
								# Get selected stock
								$sql = "SELECT * FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
								$stktcRslt = db_exec($sql);

								while($stktc = pg_fetch_array($stktcRslt)){
									# Insert Order items
									db_conn($pur['prd']);
									$sql = "
										INSERT INTO nons_pur_items (
											purid, cod, des, qty, unitcost, 
											amt, svat, ddate, accid, 
											div, vatcode
										) VALUES (
											'$purid', '$stktc[cod]', '$stktc[des]', '$stktc[qty]', '$stktc[unitcost]', 
											'$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '$stktc[accid]', 
											'".USER_DIV."', '$stktc[vatcode]'
										)";
									$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
								}
					
								db_connect();
								# Remove the Order from running DB
								$sql = "DELETE FROM nons_purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
								$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
					
								# Remove those Order items from running DB
								$sql = "DELETE FROM nons_pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
								$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
					
							}
						}
					}
				}
			}
		}
pglib_transaction("COMMIT") or errDie("Unable to complete transaction.");


	return "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Processing Completed</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>All Selected Transactions Completed</td>
			</tr>
		</table>
		<br>
		".
		mkQuickLinks(
			ql("nons-purch-batch-add.php","Batch Creditors Non-Stock Invoices Add"),
			ql("purchase-new.php","Add Purchase"),
			ql("purchase-view.php","View Purchases"),
			ql("nons-purchase-new.php","Add Non-Stock Purchase"),
			ql("nons-purchase-view.php","View Non-Stock Purchases")
		);

}























function save_entries ($_POST)
{

	extract ($_POST);

	#do validation here
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supplier, "num", 1, 20, "Invalid Supplier.");
	$v->isOk ($account, "num", 1, 255, "Invalid Item Account.");
	$v->isOk ($date_year, "num", 4, 4, "Invalid Date (Year).");
	$v->isOk ($date_month, "num", 1, 2, "Invalid Date (Month).");
	$v->isOk ($date_day, "num", 1, 2, "Invalid Date (Day).");
	$v->isOk ($vatcode, "num", 1, 10, "Invalid Vatcode.");
	$v->isOk ($supinv, "string", 1, 20, "Invalid Supplier Invoice Number.");

	$v->isOk ($qty, "float", 1, 20, "Invalid Quantity.");
	$v->isOk ($price, "float", 1, 25, "Invalid Price.");
	if (isset($vatcode))
		if ($vatcode == "0")
			$v->addError($vatcode,"Please Select a Valid VAT Code.");

	db_connect ();

	$csetting = getsetting ("CC_USE");
	if($csetting == "use"){

		$v->isOk ($description, "string", 0, 100, "Invalid Description.");

		if(!isset($old_project))
			$old_project = array ();
		if(!isset($old_costcenter))
			$old_costcenter = array ();
		if(!isset($old_costperc))
			$old_costperc = array();

		foreach ($old_project AS $each => $own){
			$v->isOk ($own, "string", 1, 20, "Invalid Project.");
			$v->isOk ($old_costcenter[$each], "string", 1, 20, "Invalid Cost Ledger.");
			$v->isOk ($old_costperc[$each], "string", 1, 20, "Invalid Cost Ledger Percentage.");
		}

		if(isset($newproject)){
			if(($newproject == "Project") OR ($newcostcenter == "Cost Center")){
				unset ($newproject);
				unset ($newcostcenter);
				unset ($newcostperc);
			}
		}

		if(isset($newproject) AND (strlen($newproject) > 0)){
			$v->isOk ($newproject, "string", 1, 20, "Invalid New Project. Entry");
			$v->isOk ($newcostcenter, "string", 1, 20, "Invalid New Cost Ledger Entry.");
			$v->isOk ($newcostperc, "num", 1, 20, "Invalid New Cost Ledger Percentage Entry.");

			#check if the entries are valid
			$get_pro_check = "SELECT * FROM projects WHERE code = '$newproject'";
			$run_pro_check = db_exec($get_pro_check) or errDie("Unable to get project information.");
			if(pg_numrows($run_pro_check) < 1){
				$v->addError($newproject,"Selected Project Not Found.");
			}
	
			$get_cst_check = "SELECT * FROM costcenters WHERE centercode = '$newcostcenter' LIMIT 1";
			$run_cst_check = db_exec($get_cst_check) or errDie("Unable to get costcenter information.");
			if(pg_numrows($run_cst_check) < 1){
				$v->addError($newcostcenter,"Selected Cost Center Not Found.");
			}
		}
	}else {
		$v->isOk ($description, "string", 1, 100, "Invalid Description.");
	}

	$pdate = "$date_year-$date_month-$date_day";
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($pdate, "num", 1, 1, "Invalid Date.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$_POST["new"] = "yes";
		return get_items ($_POST, $err);
	}




	#verify the percentages matches
//	if(sum($costperc) != 100){
//		return get_items ($_POST,"li class='err'>Cost Ledger Total Percentage Must Be 100%</li>");
//	}


	db_connect ();

	if(isset($remids) AND is_array($remids)){
		foreach ($remids AS $remid){
			$rem_sql = "DELETE FROM purch_batch_entries WHERE id = '$remid'";
			$run_rem = db_exec($rem_sql) or errDie("Unable to remove batch entry.");
		}
	}

	if($csetting == "use"){
		#add the cost entry if added
		if (isset($newproject) AND (strlen($newproject) > 0)){
			#add the entry
			$ins_sql = "
				INSERT INTO purch_batch_entries_newcostcenters (
					project, costcenter, costperc
				) VALUES (
					'$newproject', '$newcostcenter', '$newcostperc'
				)";
			$run_ins = db_exec($ins_sql) or errDie("Unable to save cost ledger information.");

			#check if we have a description
			if(!isset($description) OR (strlen($description) < 1)){
				#no description ? use the cost center name !!
				$get_cname = "SELECT * FROM costcenters WHERE centercode = '$newcostcenter' LIMIT 1";
				$run_cname = db_exec($get_cname) or errDie("Unable to get cost center description information.");
				if(pg_numrows($run_cname) < 1){
					return get_items ($_POST,"<li class='err'>Invalid Cost Center Selected</li>");
				}else {
					$csarr = pg_fetch_array($run_cname);
					$description = $csarr['centername'];
				}
			}
		}

		#only add if the cost total == 100
		$get_sum = "SELECT sum(costperc) FROM purch_batch_entries_newcostcenters";
		$run_sum = db_exec($get_sum) or errDie("Unable to get cost ledger information.");
		if(pg_numrows($run_sum) > 0){
			$arr = pg_fetch_array($run_sum);
			$total = $arr['sum'];
		}else {
			$total = 0;
		}
	}else {
		$total = 100;
	}

	if($total == 100){
		#add new entry
		$ins_sql = "
			INSERT INTO purch_batch_entries (
				supplier, account, pdate, sdate, vat, 
				vatcode, supinv, description, qty, price
			) VALUES (
				'$supplier', '$account', '$date_year-$date_month-$date_day', 'now', 'inc', 
				'$vatcode', '$supinv', '$description', '$qty', '$price'
			)";
		$run_ins = db_exec($ins_sql) or errDie("Unable to save batch entry");

		if($csetting == "use"){
			$get_id = pglib_lastid("purch_batch_entries","id");

			$get_csts = "SELECT * FROM purch_batch_entries_newcostcenters";
			$run_csts = db_exec($get_csts) or errDie("Unable to get cost ledger information.");
			if(pg_numrows($run_csts) < 1){
				return get_items ($_POST);
			}else {
				while ($csarr = pg_fetch_array($run_csts)){
					$ins_sql = "
						INSERT INTO purch_batch_entries_costcenters (
							project, costcenter, costperc, batch_entry
						) VALUES (
							'$csarr[project]', '$csarr[costcenter]', '$csarr[costperc]', '$get_id'
						)";
					$run_ins = db_exec($ins_sql) or errDie("Unable to store cost ledger information.");
				}
			}

			#now clear table for next entry
			$rem_sql = "DELETE FROM purch_batch_entries_newcostcenters";
			$run_rem = db_exec($rem_sql) or errDie("Unable to clearn batch cost center information.");

		}

		$_POST['supinv'] = "";
		$_POST['description'] = "";
		$_POST['qty'] = "";
		$_POST['price'] = "";
		$_POST['newproject'] = "";
		$_POST['newcostcenter'] = "";
		$_POST['newcostperc'] = "";

		#redirect
		return get_items ($_POST);

	}else {


	#remove any selected entries

//	$_POST['supplier'] = "";
//	$_POST['account'] = "";
//	$_POST['date_year'] = "";
//	$_POST['date_month'] = "";
//	$_POST['date_day'] = "";
//	$_POST['vatcode'] = "";

//	$_POST['supinv'] = "";
//	$_POST['description'] = "";
//	$_POST['qty'] = "";
//	$_POST['price'] = "";
	$_POST['newproject'] = "";
	$_POST['newcostcenter'] = "";
	$_POST['newcostperc'] = "";

	#redirect
	return get_items ($_POST,"<li class='err'>Please Ensure Cost Center Total Is 100%</li>");

	}
}




















function remove_entries ($_POST)
{

	extract ($_POST);

	db_connect ();

	if(isset($remids) AND is_array($remids)){
		foreach ($remids AS $remid){
			$rem_sql = "DELETE FROM purch_batch_entries WHERE id = '$remid'";
			$run_rem = db_exec($rem_sql) or errDie("Unable to remove batch entry.");
		}
	}

	return get_items ($_POST);

}








function remove_cost_entries ($_POST)
{

	extract ($_POST);

	db_connect ();

	if(isset($remcost)){
		$rem_sql = "DELETE FROM purch_batch_entries_newcostcenters WHERE id = '$remcost'";
		$run_rem = db_exec($rem_sql) or errDie("Unable to remove batch entry.");
	}

	return get_items ($_POST);

}









function svat($amt, $samt, $svat){
	$perc = ($amt/$samt);
	$rvat = sprint($perc * $svat);
	return $rvat;
}


?>
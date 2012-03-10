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

# decide what to do
if (isset($HTTP_GET_VARS["purid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}else{
	if (isset($HTTP_POST_VARS["key"])) {
		switch ($HTTP_POST_VARS["key"]) {
            case "update":
				$OUTPUT = write($HTTP_POST_VARS);
				break;
			case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;
			case "":
				$OUTPUT = details($HTTP_POST_VARS);
				break;
            default:
				$OUTPUT = "<li class='err'> Invalid use of module.</li>";
			}
	} else {
		$OUTPUT = "<li class='err'> Invalid use of module.</li>";
	}
}

# get templete
require("template.php");




# Details
function details($HTTP_POST_VARS, $error="")
{

	# get vars
	extract ($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}




	# get Order info
	db_connect();
	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>Purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$pur[purnum]</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# currency
	$currs = getSymbol($pur['fcid']);
	$curr = $currs['symbol'];
	$currsel = "$currs[symbol] - $currs[descrip]";

	/* --- Start Drop Downs --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($del_year, $del_month, $del_day) = explode("-", $pur['ddate']);

	core_connect();

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.</li>";
	}

	$stkacc = "<select name='stkacc[]'>";
	while($acc = pg_fetch_array($accRslt)){
		# Check Disable
		if(isDisabled($acc['accid']))
			continue;
		$stkacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$stkacc .= "</select>";

	# get selected supplier info
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
	if (pg_numrows ($supRslt) < 1) {
		$error = "<li class='err'> Supplier not Found.</li>";
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	$sup = pg_fetch_array($supRslt);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th colspan='2'>UNIT PRICE</th>
				<th colspan='2'>DUTY</th>
				<th>LINE TOTAL</th>
				<th>ITEM ACCOUNT <input align='right' type='button' onClick=\"window.open('core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
			</tr>";

	# get selected stock in this Order
	db_connect();

	$sql = "SELECT *, (qty - rqty) as qty FROM nons_purint_items  WHERE purid = '$purid' AND (qty - rqty) > 0 AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){
		# put in product
		$products .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='ids[]' value='$stkd[id]'>$stkd[cod]</td>
				<td>$stkd[des]</td>
				<td><input type='hidden' name='qts[]' value='$stkd[qty]'><input type='text' size='5' name='qtys[]' value='$stkd[qty]'></td>
				<td nowrap='t'>$pur[curr] $stkd[cunitcost] or </td>
				<td nowrap='t'>".CUR." $stkd[unitcost]</td>
				<td nowrap='t'>$pur[curr] $stkd[duty] or </td>
				<td>$stkd[dutyp]%</td>
				<td nowrap='t'>$pur[curr] $stkd[amt]</td>
				<td>$stkacc</td>
			</tr>";
	}

	$products .= "</table>";

/* --- End Products Display --- */

/* -- Final Layout -- */
	$details = "
		<center>
		<h3>International Non-Stock Order received</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account</td>
							<td>$sup[supno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$pur[supplier]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Address</td>
							<td valign='center'><pre>$pur[supaddr]</pre></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Order No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='text' name='refno' size='10' value='$pur[refno]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$pday-$pmon-$pyear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Foreign Currency</td>
							<td valign='center'>$currsel &nbsp;&nbsp;Exchange rate $pur[curr] $pur[xrate]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tax</td>
							<td valign='center'>$pur[curr] $pur[tax]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Shipping Charges</td>
							<td valign='center'>$pur[curr] $pur[shipchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Date</td>
							<td valign='center' nowrap='t'>".mkDateSelect("del",$del_year,$del_month,$del_day)."</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'>$products</td>
			</tr>
			<tr>
				<td>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'>$error</td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-purch-int-new.php'>New International Non-Stock Order</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align=right>
					<table ".TMPL_tblDflts." width='80%'>
						<tr bgcolor='".bgcolorg()."'>
							<td>SUBTOTAL</td>
							<td align='right'>$pur[curr] $pur[subtot]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Charges</td>
							<td align='right'>$pur[curr] $pur[shipping]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tax </td>
							<td align='right'>$pur[curr] $pur[tax]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<th>GRAND TOTAL</th>
							<td align='right'>$pur[curr] $pur[total]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='upBtn' value='Confirm'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $details;

}



# Details
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Non-Stock Order number.");
	$ddate = $del_year."-".$del_month."-".$del_day;
	if(!checkdate($del_month, $del_day, $del_year)){
    	$v->isOk ($ddate, "num", 1, 1, "Invalid Date.");
    }
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			if($qty > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "Error : Quantity for product number : <b>".($keys+1)."</b> is more that Qty Orderd");
			}
			if($qty < 1){
				$v->isOk ($qty, "num", 0, 0, "Error : Item Quantity must be at least one. Product number : <b>".($keys+1)."</b>");
			}
			$v->isOk ($stkacc[$keys], "num", 1, 10, "Invalid Item Account number : <b>".($keys+1)."</b>");
		}
	}else{
		$v->isOk ("#", "num", 0, 0, "Error : no products selected.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		$error = "";
		foreach ($errors as $e) {
			$error .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm = "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return details($HTTP_POST_VARS, $error);
		return $confirm;
	}



	# get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class='err'>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$pur[purnum]</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# currency
	$currs = getSymbol($pur['fcid']);
	$curr = $currs['symbol'];
	$currsel = "$currs[symbol] - $currs[descrip]";

	# get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get supplier");
	if (pg_numrows ($supRslt) < 1) {
		$error = "<li class='err'> Supplier not Found.</li>";
		$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	$sup = pg_fetch_array($supRslt);

	/* --- Start Drop Downs --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($del_year, $del_month, $del_day) = explode("-", $pur['ddate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>ITEM NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QTY</th>
				<th colspan='2'>UNIT PRICE</th>
				<th colspan='2'>DUTY</th>
				<th>LINE TOTAL</th>
				<th>COST PER UNIT</th>
				<th>ITEM ACCOUNT</th>
			<tr>";

		$amt = 0;
		foreach($qtys as $keys => $value){
			if($qtys[$keys] < 1)
				continue;
			db_connect();
			# Get selected stock line
			$sql = "SELECT * FROM nons_purint_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$stkd = pg_fetch_array($stkdRslt);

			core_connect();

			# Get selected stock line
			$sql = "SELECT accname,accid,topacc,accnum FROM accounts WHERE accid = '$stkacc[$keys]' AND div = '".USER_DIV."'";
			$accRslt = db_exec($sql);
			$acc = pg_fetch_array($accRslt);

			/* -- Calculations -- */
			# Calculate cost amount bought
			$totamt = ($stkd['qty'] * $stkd['cunitcost']);

			# Calculate percentage from subtotal
			if($pur['subtot'] <> 0){
				$perc = ((($totamt+$stkd['duty'])/$pur['subtot']) * 100);
			}else{
				$perc = 0;
			}

			# Get percentage from shipping charges
			$shipchrg = sprint(($perc / 100) * $pur['shipchrg']);


			# Add shipping charges to amt
			$totamt = sprint($totamt + $shipchrg+$stkd['duty']);

			$unitamt = sprint($totamt / $stkd['qty']);

			$amt = sprint($amt + $unitamt);

			/* -- End Calculations --*/

			# put in product
			$products .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='hidden' name='ids[]' value='$stkd[id]'>$stkd[cod]</td>
					<td>$stkd[des]</td>
					<td><input type='hidden' size='5' name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td>
					<td nowrap>$pur[curr] $stkd[cunitcost] or </td>
					<td nowrap>".CUR." $stkd[unitcost]</td>
					<td nowrap>$pur[curr] <input type='hidden' size='7' name='duty[]' value='$stkd[duty]'>$stkd[duty] or </td>
					<td>$stkd[dutyp]%</td>
					<td nowrap>$pur[curr] $stkd[amt]</td>
					<td nowrap><input type='hidden' name='unitamts[]' value='$unitamt'>$pur[curr] $unitamt</td>
					<td><input type='hidden' name='stkacc[]' value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
				</tr>";
		}

	$products .= "</table>";

/* --- End Products Display --- */

/* -- Final Layout -- */
	$confirm = "
		<center>
		<h3>International Non-Stock Order received</h3>
		<h2>Confirm Entry</h2>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='purid' value='$purid'>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Supplier Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account</td>
							<td>$sup[supno]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier</td>
							<td valign='center'>$pur[supplier]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Address</td>
							<td valign='center'><pre>$pur[supaddr]</pre></td>
						</tr>
					</table>
				</td>
				<td valign='top' align='right'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'> Non-Stock Order Details </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Non-Stock Order No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Ref No.</td>
							<td valign='center'><input type='text' name='refno' size='10' value='$pur[refno]'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center' nowrap='t'>$pday-$pmon-$pyear</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Foreign Currency</td>
							<td valign='center'>$currsel &nbsp;&nbsp;Exchange rate $pur[curr] $pur[xrate]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tax</td>
							<td valign='center'>$pur[curr] $pur[tax]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Shipping Charges</td>
							<td valign='center'>$pur[curr] $pur[shipchrg]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Delivery Date</td>
							<td valign='center'>".mkDateSelect("del",$_POST['del_year'],$_POST['del_month'],$_POST['del_day'])."</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'>$products</td>
			</tr>
			<tr>
				<td>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th width='25%'>Quick Links</th>
							<th width='25%'>Remarks</th>
							<td rowspan='5' valign='top' width='50%'></td>
						</tr>
						<tr>
							<td bgcolor='".bgcolorg()."'><a href='nons-purch-int-new.php'>New International Non-Stock Order</a></td>
							<td bgcolor='".bgcolorg()."' rowspan='4' align='center' valign='top'>".nl2br($pur['remarks'])."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='nons-purch-int-view.php'>View International Non-Stock Orders</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
				<td align='right'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr bgcolor='".bgcolorg()."'>
							<th>TOTAL COST RECEIVED</th>
							<td align='right' nowrap>$pur[curr] $amt</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='upBtn' value='Write'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $confirm;

}



# details
function write($HTTP_POST_VARS)
{

	#get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$ddate = $del_year."-".$del_month."-".$del_day;
	if(!checkdate($del_month, $del_day, $del_year)){
    	$v->isOk ($ddate, "num", 1, 1, "Invalid Date.");
    }

	# used to generate errors
	$error = "asa@";

	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qtys[$keys], "num", 1, 10, "Invalid Quantity for product number : <b>".($keys+1)."</b>");
			$v->isOk ($unitamts[$keys], "float", 1, 20, "Invalid Unit Price for product number : <b>".($keys+1)."</b>.");
			$v->isOk ($stkacc[$keys], "num", 1, 10, "Invalid Item Account number : <b>".($keys+1)."</b>");
		}
	}else{
		$v->isOk ("#", "num", 0, 0, "Error : no products selected.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return details($HTTP_POST_VARS, $err);
	}



	# Get Order info
	db_connect();

	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($pur['pdate']) >= strtotime($blocked_date_from) AND strtotime($pur['pdate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
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

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class='err'> Error : Order number <b>$purid</b> has already been received.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Insert Order to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Update all supplies xchange rate first
		xrate_update($pur['fcid'], $pur['xrate'], "suppurch", "id");
		sup_xrate_update($pur['fcid'], $pur['xrate']);


		db_connect();

		$retax = 0;
		if(isset($qtys)){
			foreach($qtys as $keys => $value){
				# Get selected stock line

				$sql = "SELECT * FROM nons_purint_items WHERE id = '$ids[$keys]' AND purid = '$purid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				# the unitcost + delivery charges * qty
				$famt[$keys] = sprint($unitamts[$keys] * $qtys[$keys]);
				# calculate tax
				$ftaxes[$keys] = svat($famt[$keys], $pur['subtot'], $pur['tax']);

				$amt[$keys] = sprint(($unitamts[$keys] * $pur['xrate']) * $qtys[$keys]);
				# calculate tax
				$retax += sprint($ftaxes[$keys] * $pur['xrate']);

				# Update Order items
				$sql = "UPDATE nons_purint_items SET rqty = (rqty + '$qtys[$keys]'), accid = '$stkacc[$keys]' WHERE id = '$ids[$keys]' AND purid='$purid' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);

				# keep records for transactions
				if(isset($totstkamt[$stkacc[$keys]])){
					$totstkamt[$stkacc[$keys]] += $amt[$keys];
				}else{
					$totstkamt[$stkacc[$keys]] = $amt[$keys];
				}
			}
		}
	/* Transactions */

	/* - Start Hooks - */
		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$refnum = getrefnum();
		$sdate = $pur["pdate"]; //$ddate;
	/* - End Hooks - */

		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			# Debit Stock and Credit Suppliers control
			writetrans($stkacc, $supacc, $sdate, $refnum, $wamt, "Non-Stock Purchase No. $pur[purnum] Received from Supplier $sup[supname].");
		}

		db_connect();

		$Sl = "SELECT * FROM vatcodes WHERE id='$pur[cusid]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd = pg_fetch_array($Ri);



		if($retax > 0){
			writetrans($vatacc, $supacc, $sdate, $refnum, $retax, "Non-Stock Purchase Vat paid on Non-Stock Order No. $pur[purnum].");
		}

		$retot = sprint(array_sum($amt) + $retax);

		vatr($vd['id'],$pur['pdate'],"INPUT",$vd['code'],$refnum,"Non-Stock Purchase Vat paid on Non-Stock Order No. $pur[purnum].",-($retot),-$retax);
		$fretot = sprint(array_sum($famt) + array_sum($ftaxes));

		suppledger($sup['supid'], $stkacc, $sdate, $pur['purid'], "Non-Stock Purchase No. $pur[purnum] received.", $retot, 'c');

		db_connect();

		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$retot'), fbalance = (fbalance + '$fretot') WHERE supid = '$sup[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$sql = "
			INSERT INTO sup_stmnt (
				supid, edate, cacc, amount, descript, 
				ref, ex, div
			) VALUES (
				'$sup[supid]', '$sdate', '$dept[credacc]', '$fretot', 'Non Stock Purchase No. $pur[purnum] Received', 
				'$refnum', '$pur[purnum]', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		db_connect();
		# make transaction record for age analysis
		$sql = "
			INSERT INTO suppurch (
				supid, purid, pdate, fcid, balance, fbalance, div
			) VALUES (
				'$sup[supid]', '$pur[purnum]', '$sdate', '$pur[fcid]', '$retot', '$fretot', '".USER_DIV."'
			)";
		$purcRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

# Commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	db_connect();

	# check if there are any outstanding items
	$sql = "SELECT * FROM nons_purint_items WHERE purid = '$purid' AND (qty - rqty) > '0' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	# if none the set to received
	if(pg_numrows($stkdRslt) < 1){
		# update surch_int(received = 'y')
		$sql = "UPDATE nons_purch_int SET received = 'y', supplier = '$sup[supname]', supaddr = '$sup[supaddr]' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update international Orders in Cubit.",SELF);
	}
	# Update Order on the DB
	$sql = "UPDATE nons_purch_int SET refno = '$refno' WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update Order in Cubit.",SELF);

/* End Transactions */

/* Start moving if Order received */


	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	$sql = "SELECT * FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	if($pur['received'] == "y"){
		# copy Order
		db_conn($pur['prd']);
		$sql = "
			INSERT INTO nons_purch_int (
				purid, deptid, supid, supplier, supaddr, 
				terms, pdate, ddate, shipchrg, xrate, 
				fcid, curr, currency, shipping, subtot, 
				total, balance, tax, remarks, refno, 
				received, done, div, purnum,cusid
			) VALUES (
				'$purid', '$pur[deptid]', '$pur[supid]', '$pur[supplier]',  '$pur[supaddr]', 
				'$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[xrate]', 
				'$pur[fcid]', '$pur[curr]', '$pur[currency]', '$pur[shipping]', '$pur[subtot]', 
				'$pur[total]', '0', '$pur[tax]', '$pur[remarks]', '$pur[refno]', 
				'y', 'y', '".USER_DIV."', '$pur[purnum]', '$pur[cusid]'
			)";
		$rslt = db_exec($sql) or errDie("Unable to insert Non-Stock Order to Cubit.",SELF);

		db_connect();
		# get selected stock
		$sql = "SELECT * FROM nons_purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktcRslt = db_exec($sql);

		while($stktc = pg_fetch_array($stktcRslt)){
			# Insert Order items
			db_conn($pur['prd']);
			$sql = "
				INSERT INTO nons_purint_items (
					purid, cod, des, qty, unitcost, 
					cunitcost, duty, dutyp, amt, accid, div
				) VALUES (
					'$purid', '$stktc[cod]', '$stktc[des]', '$stktc[qty]', '$stktc[unitcost]', 
					'$stktc[cunitcost]', '$stktc[duty]', '$stktc[dutyp]', '$stktc[amt]', '$stktc[accid]', '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
		}

		db_connect();
		# Remove the Order from running DB
		$sql = "DELETE FROM nons_purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);

		# Remove those Order items from running DB
		$sql = "DELETE FROM nons_purint_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update int Orders information in Cubit.",SELF);
	}

# Commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
/* End moving Order received */

	$cc = "<script> CostCenter('ct', 'International Non-Stock Purchase', '$pur[pdate]', 'Non Stock Purchase No.$pur[purnum]', '".($retot-$retax)."', ''); </script>";

	// Final Layout
	$write = "
		$cc
		<table ".TMPL_tblDflts.">
			<tr>
				<th>International Non-Stock Order received</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>International Non-Stock Order has been received.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-purch-int-view.php'>View International Orders</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}




function svat($amt, $samt, $svat)
{

	$perc = ($amt/$samt);
	$rvat = sprint($perc * $svat);
	return $rvat;

}



?>

<?

require ("settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = confirm_remove ($_POST);
			break;
		case "remove":
			$OUTPUT = remove ($_POST);
			break;
		default:
			$OUTPUT = show_centers ($_POST);
	}
}else {
	$OUTPUT = show_centers ($_POST);
}

$OUTPUT .= 
			mkQuickLinks(
				ql("costcenter-unallocated.php","View Unallocated Cost Center Data")
			);
require ("template.php");



function show_centers ($_POST,$err="")
{

	extract ($_POST);
	
	db_connect ();
	
	$get_scpops = "SELECT * FROM sc_popup_data ORDER BY edate";
	$run_scpops = db_exec($get_scpops) or errDie("Unable to get sc popups.");
	if(pg_numrows($run_scpops) < 1){
		$sclisting = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='6'>No Outstanding Transactions</td>
						</tr>
					";
	}else {
		$sclisting = "";
		while ($scarr = pg_fetch_array($run_scpops)){
			$sclisting .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$scarr[descrip]</td>
									<td>$scarr[amount]</td>
									<td>$scarr[edate]</td>
									<td>$scarr[sdate]</td>
									<td align='center'><input type='button' onClick=\"sCostCenter('$scarr[type]&writeid=$scarr[id]', '$scarr[typename]', '$scarr[edate]', '$scarr[descrip]', '$scarr[amount]', '$scarr[cdescrip]', '$scarr[cosamt]', '');\" value='Continue'></td>
									<td><input type='checkbox' name='scremids[]' value='$scarr[id]'></td>
								</tr>
							";
		}
	}

	$get_ccpops = "SELECT * FROM cc_popup_data ORDER BY edate";
	$run_ccpops = db_exec($get_ccpops) or errDie("Unable to get cc popups.");
	if(pg_numrows($run_ccpops) < 1){
		$cclisting = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='6'>No Outstanding Transactions</td>
						</tr>
					";
	}else {
		$cclisting = "";
		while ($ccarr = pg_fetch_array($run_ccpops)){
			$cclisting .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$ccarr[descrip]</td>
									<td>$ccarr[amount]</td>
									<td>$ccarr[edate]</td>
									<td>$ccarr[sdate]</td>
									<td align='center'><input type='button' onClick=\"CostCenter('$ccarr[type]&writeid=$ccarr[id]', '$ccarr[typename]', '$ccarr[edate]', '$ccarr[descrip]', '$ccarr[amount]', '$ccarr[cdescrip]', '', '');\" value='Continue'></td>
									<td><input type='checkbox' name='ccremids[]' value='$ccarr[id]'></td>
								</tr>
							";
		}
	}

	$get_ncpops = "SELECT * FROM nc_popup_data ORDER BY edate";
	$run_ncpops = db_exec($get_ncpops) or errDie("Unable to get sc popups.");
	if(pg_numrows($run_ncpops) < 1){
		$nclisting = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='6'>No Outstanding Transactions</td>
						</tr>
					";
	}else {
		$nclisting = "";
		while ($ncarr = pg_fetch_array($run_ncpops)){
			$nclisting .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$ncarr[descrip]</td>
									<td>$ncarr[amount]</td>
									<td>$ncarr[edate]</td>
									<td>$ncarr[sdate]</td>
									<td align='center'><input type='button' onClick=\"nCostCenter('$ncarr[type]&writeid=$ncarr[id]', '$ncarr[typename]', '$ncarr[edate]', '$ncarr[descrip]', '$ncarr[amount]', '$ncarr[cdescrip]', '$ncarr[cosamt]', '');\" value='Continue'></td>
									<td><input type='checkbox' name='ncremids[]' value='$ncarr[id]'></td>
								</tr>
							";
		}
	}



	$display = "
					<h2>Unallocated Cost Center Data</h2>
					$err
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Transaction Date</th>
							<th>System Date</th>
							<th>Continue Transaction</th>
							<th>Remove</th>
						</tr>
						$sclisting
						".TBL_BR."
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Transaction Date</th>
							<th>System Date</th>
							<th>Continue Transaction</th>
							<th>Remove</th>
						</tr>
						$cclisting
						".TBL_BR."
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Transaction Date</th>
							<th>System Date</th>
							<th>Continue Transaction</th>
							<th>Remove</th>
						</tr>
						$nclisting
						<tr>
							<td colspan='6' align='right'><input type='submit' value='Remove Selected'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function confirm_remove ($_POST)
{

	extract ($_POST);


	if((!isset($scremids) OR !is_array($scremids)) AND (!isset($ccremids) OR !is_array($ccremids)) AND (!isset($ncremids) OR !is_array($ncremids))){
		return show_centers ($_POST,"<li class='err'>Please Select Entries To Remove</li>");
	}

	$sclisting = "";
	$cclisting = "";
	$nclisting = "";
	$scpasson = "";
	$ccpasson = "";
	$ncpasson = "";

	db_connect ();

	if (isset($scremids) AND is_array($scremids)){
		foreach ($scremids as $each){
			$get_info = "SELECT * FROM sc_popup_data WHERE id = '$each' LIMIT 1";
			$run_info = db_exec($get_info) or errDie("Unable to get popup information.");
			if(pg_numrows($run_info) > 0){
				$arr = pg_fetch_array($run_info);
				$sclisting .= "
									<tr bgcolor='".bgcolorg()."'>
										<td>$arr[descrip]</td>
										<td>$arr[amount]</td>
										<td>$arr[edate]</td>
										<td>$arr[sdate]</td>
									</tr>
								";
				$scpasson .= "<input type='hidden' name='scremids[]' value='$arr[id]'>";
			}
		}
	}else {
		$sclisting .= "<tr bgcolor='".bgcolorg()."'><td colspan='4'>No Entries Selected For Removal.</td></tr>";
	}

	if (isset($ccremids) AND is_array($ccremids)){
		foreach ($ccremids as $each){
			$get_info = "SELECT * FROM cc_popup_data WHERE id = '$each' LIMIT 1";
			$run_info = db_exec($get_info) or errDie("Unable to get popup information.");
			if(pg_numrows($run_info) > 0){
				$arr = pg_fetch_array($run_info);
				$cclisting .= "
									<tr bgcolor='".bgcolorg()."'>
										<td>$arr[descrip]</td>
										<td>$arr[amount]</td>
										<td>$arr[edate]</td>
										<td>$arr[sdate]</td>
									</tr>
								";
				$ccpasson .= "<input type='hidden' name='ccremids[]' value='$arr[id]'>";
			}
		}
	}else {
		$cclisting .= "<tr bgcolor='".bgcolorg()."'><td colspan='4'>No Entries Selected For Removal.</td></tr>";
	}

	if (isset($ncremids) AND is_array($ncremids)){
		foreach ($ncremids as $each){
			$get_info = "SELECT * FROM nc_popup_data WHERE id = '$each' LIMIT 1";
			$run_info = db_exec($get_info) or errDie("Unable to get popup information.");
			if(pg_numrows($run_info) > 0){
				$arr = pg_fetch_array($run_info);
				$nclisting .= "
									<tr bgcolor='".bgcolorg()."'>
										<td>$arr[descrip]</td>
										<td>$arr[amount]</td>
										<td>$arr[edate]</td>
										<td>$arr[sdate]</td>
									</tr>
								";
				$ncpasson .= "<input type='hidden' name='ncremids[]' value='$arr[id]'>";
			}
		}
	}else {
		$nclisting .= "<tr bgcolor='".bgcolorg()."'><td colspan='4'>No Entries Selected For Removal.</td></tr>";
	}

	$display = "
					<h2>Confirm Entries To Be Removed</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='remove'>
						$scpasson
						$ccpasson
						$ncpasson
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Transaction Date</th>
							<th>System Date</th>
						</tr>
						$sclisting
						".TBL_BR."
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Transaction Date</th>
							<th>System Date</th>
						</tr>
						$cclisting
						".TBL_BR."
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Transaction Date</th>
							<th>System Date</th>
						</tr>
						$nclisting
						".TBL_BR."
						<tr>
							<td colspan='6' align='right'><input type='submit' value='Remove'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function remove ($_POST)
{

	extract ($_POST);

	if((!isset($scremids) OR !is_array($scremids)) AND (!isset($ccremids) OR !is_array($ccremids)) AND (!isset($ncremids) OR !is_array($ncremids))){
		return show_centers ($_POST,"<li class='err'>Please Select Entries To Remove</li>");
	}

	if (isset($scremids) AND is_array($scremids)){
		foreach ($scremids as $each){
			$get_info = "DELETE FROM sc_popup_data WHERE id = '$each'";
			$run_info = db_exec($get_info) or errDie("Unable to remove popup information.");
		}
	}

	if (isset($ccremids) AND is_array($ccremids)){
		foreach ($ccremids as $each){
			$get_info = "DELETE FROM cc_popup_data WHERE id = '$each'";
			$run_info = db_exec($get_info) or errDie("Unable to remove popup information.");
		}
	}

	if (isset($ncremids) AND is_array($ncremids)){
		foreach ($ncremids as $each){
			$get_info = "DELETE FROM nc_popup_data WHERE id = '$each'";
			$run_info = db_exec($get_info) or errDie("Unable to remove popup information.");
		}
	}

	$display = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Entries Removed.</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Selected Entries Have Been Removed.</td>
						</tr>
						".TBL_BR."
					</table>
				";
	return $display;

}


?>
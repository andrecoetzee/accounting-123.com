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
require ("settings.php");
require ("core-settings.php");
require ("libs/ext.lib.php");

# decide what to do
if(isset($_GET["type"]) && isset($_GET["amount"])){
	$OUTPUT = enter($_GET);
}elseif (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			if(!isset($_POST["done"])){
				$OUTPUT = enter ($_POST);
			}else {
				$OUTPUT = confirm ($_POST);
			}
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = enterdet ();
	}
} else {
	$OUTPUT = enterdet ();
}

# display output
require ("template.php");



# enter new data
function enter ($_GET,$err="")
{

	# get vars
	extract ($_GET);

	$amount = sprint($amount);
	$cosamt = sprint($cosamt);
	//$edate = ext_dateEntry("e");
	
	if (!isset($e_year)) {
		explodeDate($_GET["edate"], $e_year, $e_month, $e_day);
	}
	
	$edate = mkDateSelect("e", $e_year, $e_month, $e_day);

	db_connect();

	$cc_list = "";
	if(!isset($remids))
		$remids = array();

	$search_flag = FALSE;
	$search_val = "";


	if(isset($search) AND strlen($search) > 0){
		unset ($changeproject);
		$get_ccid = "SELECT ccid FROM costcenters WHERE centercode = '$search' LIMIT 1";
		$run_ccid = db_exec($get_ccid) or errDie("Unable to get cost center information.");
		if(pg_numrows($run_ccid) > 0){
			if(!isset($project1) OR ($project1 == "") OR ($project1 == "0")){
				unset ($_GET["search"]);
				return enter ($_GET,"<li class='err'>Please Select A Project First</li>");
			}
			$temparr = pg_fetch_array($run_ccid);
			$get_link = "SELECT id FROM costcenters_links WHERE ccid = '$temparr[ccid]' AND project1 = '$project1' LIMIT 1";
			$run_link = db_exec($get_link) or errDie("Unable to get cost center information.");
			if(pg_numrows($run_link) > 0){
				$search_flag = TRUE;
				$val = pg_fetch_array($run_link);
				$search_val = $val['id'];
			}else {
				$search_flag = FALSE;
			}
		}
	}

	#compile list of selected centers
	if(isset($new_cc) AND ($new_cc != "0")){
		$ccids[] = $new_cc;
	}

	foreach($remids as $each => $own){
		if($own == "yes")
			unset ($ccids[$each]);
	}

	if(!isset($writeid))
		$writeid = "";

	if(!isset($ccids))
		$ccids = "";

	if(!isset($project1))
		$project1 = "";

	$showedate = "$e_year-$e_month-$e_day";
	if((!isset($writeid) OR (strlen($writeid) < 1)) AND ($writeid != "0")){
		#write all this information to safe location for future retrieval
		$ins_sql = "
						INSERT INTO sc_popup_data 
							(type,typename,edate,descrip,amount,cdescrip,cosamt,sdate) 
						VALUES 
							('$type','$typename','$showedate','$descrip','$amount','$cdescrip','$cosamt','now')
					";
		$run_ins = db_exec($ins_sql) or errDie("Unable to save cost center information.");
		$writeid = pglib_lastid("sc_popup_data","id");
	}


	$showproject = "<tr>";
	if(!isset($project1) OR (strlen($project1) < 1) OR (isset($changeproject))){
		$showproject .= "<td><br></td></tr><tr><th colspan='3'>Select Project</th></tr><tr class='".bg_class()."'><td colspan='3'>";
		$showproject .= "<select name='project1' onChange='javascript:document.form1.submit();'>";
		$showproject .= "<option value='' disabled selected>Select Project</option>";
		$get_pros = "SELECT * FROM projects WHERE id != '1'";
		$run_pros = db_exec($get_pros) or errDie("Unable to get project information");
		if(pg_numrows($run_pros) > 0){
			while ($parr = pg_fetch_array($run_pros)){
				$showproject .= "<option value='$parr[id]'>$parr[project_name]</option>";
			}
		}
		$showproject .= "</select>";
		$showproject ."</td>";
		$prosearch = "0";
	}else {
		#show current + offer to change
		$get_pro = "SELECT * FROM projects WHERE id = '$project1' LIMIT 1";
		$run_pro = db_exec($get_pro) or errDie("Unable to get project information.");
		if(pg_numrows($run_pro) > 0){
			$parr = pg_fetch_array($run_pro);
			$showproject .= "
								".TBL_BR."
								<tr>
									<th colspan='3'>Cost Centers For Project : $parr[project_name]</th>
								</tr>
								<tr class='".bg_class()."'>
									<input type='hidden' name='project1' value='$project1'>
									<td colspan='2'>$parr[project_name]</td>
									<td><input type='submit' name='changeproject' value='Change'></td>
								</tr>";
		}
		$prosearch = "$project1";
	}
	$showproject .= "</tr>";

	#get ccids of all cost centers in this 'project'
	$get_ccids = "SELECT id,ccid FROM costcenters_links WHERE project1 = '$prosearch'";
	$run_ccids = db_exec($get_ccids) or errDie("Unable to get cost center information.");
	if(pg_numrows($run_ccids) > 0){
		$pccids = array ();
		while ($ccarr = pg_fetch_array($run_ccids)){
			$pccids[] = $ccarr['id'];
		}
	}else {
		$pccids[] = "";
	}

	#make the new dropdown
	$get_ccs = "SELECT * FROM costcenters_links";
	$run_ccs = db_exec($get_ccs) or errDie("Unable to get cost center information.");

	$cc_drop = "<input type='text' size='5' name='search'><select name='new_cc' onChange='javascript:document.form1.submit();'>";
	$cc_drop .= "<option value='0'>Select A Cost Center</option>";
	while ($cc = pg_fetch_array($run_ccs)){
		$get_cname = "SELECT centername FROM costcenters WHERE ccid = '$cc[ccid]' LIMIT 1";
		$run_cname = db_exec($get_cname) or errDie("Unable to get cost center information.");
		if(pg_numrows($run_cname) == 1){
			$varr = pg_fetch_array ($run_cname);
			$cname = $varr['centername'];
		}else {
			$cname = "";
		}

		#first check if this cost center is in the 'selected' project
		if(in_array($cc['id'],$pccids)){
			if(!is_array($ccids) OR !in_array($cc['id'],$ccids)){
				if($search_flag){
					$ccids[] = $search_val;
					$search_flag = FALSE;
				}else {
					$cc_drop .= "<option value='$cc[id]'>$cname</option>";
				}
			}
		}
	}
	$cc_drop .= "</select>";

	if(is_array($ccids))
		$ccids = array_unique($ccids);

	if(is_array($ccids))
		foreach ($ccids as $each => $own){
			$get_cc = "SELECT ccid FROM costcenters_links WHERE id = '$own' LIMIT 1";
			$run_cc = db_exec($get_cc) or errDie("Unable to get cost center information.");
			if (pg_numrows($run_cc) < 1){
				#problem
			}
			$arr = pg_fetch_array($run_cc);

			if(!isset($ccperc[$each]))
				$ccperc[$each] = "";

			if(!isset($ccidpro[$each]))
				$ccidpro[$each] = $project1;

			$get_cname = "SELECT centercode,centername FROM costcenters WHERE ccid = '$arr[ccid]' LIMIT 1";
			$run_cname = db_exec($get_cname) or errDie("Unable to get cost center information.");
			if(pg_numrows($run_cname) == 1){
				$varr = pg_fetch_array ($run_cname);
				$cname = $varr['centername'];
				$ccode = $varr['centercode'];
			}else {
				$cname = "";
				$ccode = "";
			}

			$cc_list .= "
							<input type='hidden' name='ccids[$each]' value='$own'>
							<input type='hidden' name='ccidpro[$each]' value='$ccidpro[$each]'>
							<tr class='".bg_class()."'>
								<td>($ccode) $cname</td>
								<td><input type='text' name='ccperc[$each]' size='8' value='$ccperc[$each]'></td>
								<td><input type='checkbox' name='remids[$each]' value='yes' onClick='javascript:document.form1.submit();'></td>
							</tr>
						";
		}

	$enter = "
			<center>
			<h3>Allocate amount to Cost Centers</h3>
			<center>$err</center>
			<br>
			<form action='".SELF."' method='POST' name='form1'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='type' value='$type'>
				<input type='hidden' name='typename' value='$typename'>
				<input type='hidden' name='amount' value='$amount'>
				<input type='hidden' name='cosamt' value='$cosamt'>
				<input type='hidden' name='descrip' value='$descrip'>
				<input type='hidden' name='cdescrip' value='$cdescrip'>
				<input type='hidden' name='e_year' value='$e_year'>
				<input type='hidden' name='e_month' value='$e_month'>
				<input type='hidden' name='e_day' value='$e_day'>
				<input type='hidden' name='writeid' value='$writeid'>
				<input type='hidden' name='project1' value='$project1'>
			<table ".TMPL_tblDflts." width='400'>
				<tr>
					<th>Field</th>
					<th colspan='2'>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Type</td>
					<td colspan='2'>$typename</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td colspan='2' nowrap>$edate</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total Amount</td>
					<td colspan='2'>$amount</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total Cost Amount</td>
					<td colspan='2'>$cosamt</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Description</td>
					<td colspan='2'>$descrip</td>
				</tr>
				$showproject
				<tr><td><br></td></tr>
				<tr>
					<th>Cost Center</th>
					<th>% of Total Amount</th>
					<th>Remove</th>
				</tr>
				$cc_list
				<tr class='".bg_class()."'>
					<td colspan='3'>$cc_drop</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan='2' align='center'><input type='submit' name='done' value='Confirm &raquo;'></td>
				</tr>
			</table>
			</form>";
	return $enter;

}




# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 255, "Invalid Transaction type switch.");
	$v->isOk ($typename, "string", 1, 255, "Invalid Transaction type.");
	$edate = $v->chkDate($e_day, $e_month, $e_year, "Invalid date.");
	$v->isOk ($amount, "float", 1, 13, "Invalid Amount.");
	$v->isOk ($cosamt, "float", 1, 13, "Invalid Cost Amount.");
	$v->isOk ($descrip, "string", 0, 255, "Invalid description.");
	$v->isOk ($cdescrip, "string", 0, 255, "Invalid description.");

	if(isset($ccids)){
		foreach($ccids as $key => $value){
			$v->isOk ($ccperc[$key], "float", 1, 20, "Invalid Cost center percentage.");
		}
		if(array_sum($ccperc) <> 100)
			return enter($_POST, "<li class='err'> The total percentage must be exaclly 100%, check percentages.</li>");
	}else{
		return enter($_POST, "<li class='err'> There are no Cost centers found.</li>");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return enter($_POST, $confirm);
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	db_connect();

	# Query server
	$ccenters = "";
	foreach($ccids as $key => $value){
		if($ccperc[$key] < 1)
			continue;

		$sql = "SELECT * FROM costcenters_links WHERE id = '$ccids[$key]'";
		$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
		$cc = pg_fetch_array ($ccRslt);

		$ccamts[$key] = sprint($amount * ($ccperc[$key]/100));

		$get_cname = "SELECT centercode,centername FROM costcenters WHERE ccid = '$cc[ccid]' LIMIT 1";
		$run_cname = db_exec($get_cname) or errDie("Unable to get cost center information.");
		if(pg_numrows($run_cname) == 1){
			$varr = pg_fetch_array ($run_cname);
			$ccode = $varr['centercode'];
			$cname = $varr['centername'];
		}else {
			$ccode = "";
			$cname = "";
		}

		$ccenters .= "
				<input type='hidden' name='ccidpro[]' value='$ccidpro[$key]'>
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='ccids[]' value='$cc[id]'>$cname ($ccode) </td>
					<td align='right'><input type='hidden' name='ccperc[]' size='8' value='$ccperc[$key]'>$ccperc[$key] %</td>
					<td align='right'>".CUR." $ccamts[$key]</td>
				</tr>";
	}

	$confirm = "
			<center>
			<h3>Allocate amount to Cost Centers</h3>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='type' value='$type'>
				<input type='hidden' name='typename' value='$typename'>
				<input type='hidden' name='edate' value='$edate'>
				<input type='hidden' name='amount' value='$amount'>
				<input type='hidden' name='cosamt' value='$cosamt'>
				<input type='hidden' name='descrip' value='$descrip'>
				<input type='hidden' name='cdescrip' value='$cdescrip'>
				<input type='hidden' name='e_year' value='$e_year'>
				<input type='hidden' name='e_month' value='$e_month'>
				<input type='hidden' name='e_day' value='$e_day'>
				<input type='hidden' name='writeid' value='$writeid'>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Type</td>
					<td>$typename</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td>$edate</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total Amount</td>
					<td>$amount</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total Cost Amount</td>
					<td>$cosamt</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Description</td>
					<td>$descrip</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan=2>
						<table ".TMPL_tblDflts." width='100%'>
							<tr>
								<th>Cost Center</th>
								<th>%</th>
								<th>Amount</th>
							</tr>
							$ccenters
						</table>
					</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' name='back' value='&laquo Correction'></td>
					<td colspan='2' align='center'><input type='submit' value='Confirm &raquo;'></td>
				</tr>
			</table>
			</form>";
	return $confirm;

}



# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);
	
	if(isset($back))
		return enter ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 255, "Invalid Transaction type switch.");
	$v->isOk ($typename, "string", 1, 255, "Invalid Transaction type.");
	$edate = $v->chkrDate($edate, "Invalid date.");
	$v->isOk ($amount, "float", 1, 13, "Invalid Amount.");
	$v->isOk ($cosamt, "float", 1, 13, "Invalid Cost Amount.");
	$v->isOk ($descrip, "string", 0, 255, "Invalid description.");
	$v->isOk ($cdescrip, "string", 0, 255, "Invalid description.");

	if(isset($ccids)){
		foreach($ccids as $key => $value){
			$v->isOk ($ccperc[$key], "float", 1, 20, "Invalid Cost center percentage.");
		}
	}else{
		return "<li class='err'> There are no Cost centers found.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return enter($_POST, $confirm);
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	$type = strtolower($type);
	$edate = ext_rdate($edate);

	$edarr = explode ("-",$edate);
	$prd = $edarr[1];

	## start transaction
	pglib_transaction("BEGIN") or errDie("Unable to start transaction.");

	$ccenters = "";
	foreach($ccids as $key => $value){
		db_connect();
		$sql = "SELECT * FROM costcenters_links WHERE id = '$ccids[$key]'";
		$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
		$cc = pg_fetch_array ($ccRslt);

		$ccamts[$key] = sprint($amount * ($ccperc[$key]/100));
		$cccost[$key] = sprint($cosamt * ($ccperc[$key]/100));

		#we need to connect to the actual period db
		db_conn($prd);
		$sql = "
					INSERT INTO cctran 
						(ccid, trantype, typename, edate, description, amount, username, div, project) 
					VALUES 
						('$cc[ccid]', '$type', '$typename', '$edate', '$descrip', '$ccamts[$key]', '".USER_NAME."', '".USER_DIV."', '$ccidpro[$key]')";
		$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");

		$otype = "ct";
		if(strtolower($type) != 'dt'){
			if(strtolower($type) != 'ct' OR ($typename == "Credit Note")){
				$otype = "dt";
			}
		}

		$sql = "
				INSERT INTO cctran 
					(ccid, trantype, typename, edate, description, amount, username, div, project) 
				VALUES 
					('$cc[ccid]', '$otype', 'Cost Of $typename', '$edate', '$cdescrip', '$cccost[$key]', '".USER_NAME."', '".USER_DIV."', '$ccidpro[$key]')";
		$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
	}

	db_connect();
	#now remove the temp entry
	$rem_sql = "DELETE FROM sc_popup_data WHERE id = '$writeid'";
	$run_rem = db_exec($rem_sql) or errDie("Unable to remove temporary cost center information.");

	pglib_transaction("COMMIT") or errDie("Unable to complete transaction.");

	// Layout
	$write = "
			<center>
			<table ".TMPL_tblDflts." width='300'>
				<tr class='".bg_class()."'>
					<td align='center'><b>( i )</b> Amount has been allocated to Cost Centers. <b>( i )</b></td>
				</tr>
			</table>
			<p>
			<input type='button' value=' [X] Close ' onClick='javascript:window.close();'>
			</center>";
	return $write;

}


?>
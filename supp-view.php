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
require_lib("validate");

if ( isset($_GET['addcontact']) ) {
	$OUTPUT = AddContact();
	$OUTPUT .= printSupp ($_GET);
} else {
	# show current stock
	if(isset($_POST["export"])) {
		$OUTPUT = export ($_POST);
	} else {
		$OUTPUT = printSupp ();
	}
}

require ("template.php");





function printSupp ()
{

	extract ($_REQUEST);
	
	define ("LIMIT", 100);
	
	$fields = array();
	$fields["action"] = "listsupp";
	$fields["filter"] = "supname";
	$fields["search"] = "[_BLANK_]";
	$fields["offset"] = 0;

	extract ($fields, EXTR_SKIP);

	if(!isset ($supp_grp))
		$supp_grp = "";

	// Should results be displayed on first load results are only
	// displayed if results are less than the limit defined
	if ($search == "[_BLANK_]") {
		$sql = "SELECT count(supid) FROM cubit.suppliers";
		$count_rslt = db_exec($sql)
			or errDie("Unable to retrieve supplier count.");
		$count = pg_fetch_result($count_rslt, 0);
		
		if ($count < LIMIT) {
			$search = "";
		}
	}

	if(isset($filter) && !isset($all)){
		$sqlfilter = " AND $filter ILIKE '%$search%'";
	}else{
		$filter = "";
		$search = "";
		$sqlfilter = "";
	}
	
	if ($search == "[_BLANK_]") $search = "";

	$filterarr = array(
		"supname" => "Supplier Name",
		"supno" => "Account Number",
		"groupname"=>"Supplier Groups"
	);
	$filtersel = extlib_cpsel("filter", $filterarr, $filter);

	$supp_grps = "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='2'>
							<select name='supp_grp'>
								<option value='all'>View All</option>
				";
	$get_grps = "SELECT * FROM supp_groups WHERE id != '0' ORDER BY groupname";
	$run_grps = db_exec($get_grps) or errDie ("Unable to get supplier group information.");
	if (pg_numrows($run_grps) > 0){
		while ($grp_arr = pg_fetch_array ($run_grps)){
			if($grp_arr['id'] == $supp_grp){
				$supp_grps .= "<option value='$grp_arr[id]' selected>$grp_arr[groupname]</option>";
			}else {
				$supp_grps .= "<option value='$grp_arr[id]'>$grp_arr[groupname]</option>";
			}
		}
	}
	$supp_grps .= "
							</select> 
							<input type='submit' value='View'>
						</td>
					</tr>
					";

	if(!isset($sortfilter))
		$sortfilter = "supname";

	$sel1 = "";
	$sel2 = "";
	if ($sortfilter == "supname ASC") 
		$sel1 = "selected";
	else 
		$sel2 = "selected";

	$sort_drop = "
					<select name='sortfilter'>
						<option value='supname ASC' $sel1>Alphabetically</option>
						<option value='balance DESC' $sel2>Balance</option>
					</select>
				";
	

	# Set up table to display in
	$printSupp = "
	<h3>Current Suppliers</h3>
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='GET'>
	<input type='hidden' name='action' value='$action'>
	<tr>
		<th>.: Filter :.</th>
		<th>.: Value :.</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>$filtersel</td>
		<td><input type='text' size='20' name='search' value='$search'></td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td align='center'><input type='submit' name='all' value='View All'></td>
		<td align='center'><input type='submit' value='Apply Filter'></td>
	</tr>
	<tr><td><br></td></tr>
	<tr>
		<th colspan='2'>Select View Type</th>
	</tr>
	$supp_grps
	".TBL_BR."
	<tr>
		<th colspan='2'>Sort By</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td colspan='2'>$sort_drop <input type='submit' value='Sort'></td>
	</tr>
	</form>
	</table>
	<script>
		/* CRM CODE */
		function updateAccountInfo(id, name) {
			window.opener.document.frm_con.accountname.value=name;
			window.opener.document.frm_con.account_id.value=id;
			window.opener.document.frm_con.account_type.value='Supplier';
			window.close();
		}
	</script>
	<p></p>
	<table ".TMPL_tblDflts.">
	   <tr>
	    	<th>Department</th>
	    	<th>Supp No.</th>
	    	<th>Supplier Name</th>
	    	<th>Branch</th>
	    	<th>Contact Name</th>
	    	<th>Tel No.</th>
	    	<th>Fax No.</th>
	    	<th colspan='2'>Balance</th>
	    	<th colspan='10'>Options</th>
	</tr>";

	
	$i = 0;
	$tot=0;
	$sql = "
	SELECT deptid, balance, supid, location, supno, branch, contname, tel,
		fax, blocked, supname, groupname 
	FROM cubit.suppliers
		LEFT JOIN cubit.supp_groups
			ON suppliers.groupid=supp_groups.id
	WHERE (div = '".USER_DIV."' OR ddiv = '".USER_DIV."') $sqlfilter
	ORDER BY $sortfilter
	OFFSET $offset LIMIT ".LIMIT;
	$suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	
	$sql = "
	SELECT count(supid) FROM cubit.suppliers
	WHERE (div = '".USER_DIV."' OR ddiv = '".USER_DIV."') $sqlfilter";
	$count_rslt = db_exec($sql) or errDie("Unable to retrieve count.");
	$count = pg_fetch_result($count_rslt, 0);

	$grps_arr = array ();

	$get_grps_owners = "SELECT * FROM supp_grpowners";
	$run_grps_owners = db_exec($get_grps_owners) or errDie ("Unable to get group information.");
	if(pg_numrows($run_grps_owners) > 0){
		while ($garr = pg_fetch_array($run_grps_owners)){
			$grps_arr[$garr['supid']] = $garr['grpid'];
		}
	}

	if (pg_numrows ($suppRslt) < 1) {
		$printSupp .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='20'>
				<li>Please enter the first few characters of
				the supplier's name in the search box, to
				view the suppliers.</li>
			</td>
		</tr>";
	}else{
		while ($supp = pg_fetch_array ($suppRslt)) {

			#check if this supplier is in the selected group
			if(isset($supp_grp) AND strlen($supp_grp) > 0 AND ($supp_grp != 'all')){
				if(!isset ($grps_arr[$supp['supid']]))
					$grps_arr[$supp['supid']] = 0;
				if($grps_arr[$supp['supid']] != $supp_grp){
					continue;
				}
			}

			# get department
			$sql = "
			SELECT * FROM exten.departments
			WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);

			if(pg_numrows($deptRslt) < 1){
				$deptname = "<li class='err'>Department not Found.</li>";
			}else{
				$dept = pg_fetch_array($deptRslt);
				$deptname = $dept['deptname'];
			}
			$supp['balance']=sprint($supp['balance']);

			# Check if record can be removed
			$sql = "
			SELECT * FROM cubit.cashbook
			WHERE banked = 'no' AND supid = '$supp[supid]' AND
				div = '".USER_DIV."'";
			$rs = db_exec($sql) or errDie("Unable to get cashbook entries.",SELF);
			if(pg_numrows($rs) < 1 && $supp['balance'] == 0){
				$rm="<a href='supp-rem.php?supid=$supp[supid]'>Remove</a>";
			}else{
				$rm = "";
			}
			#if($supp['balance']==0) {$rm="<a href='supp-rem.php?supid=$supp[supid]'>Remove</a>";} else {$rm="";}

			// check if supplier can be added to contact list
			$addcontact = "<td><a href='conper-add.php?type=supp&id=$supp[supid]'>Add Contact</a></td>";

			$tot = $tot + $supp['balance'];

			# Locations drop down
			$locs = array("loc"=>"Local", "int"=>"International", "" => "");
			$loc = $locs[$supp['location']];

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

			$fbal = "$sp4--$sp4";
			$trans = "<a href='core/supp-trans.php?supid=$supp[supid]'>Transaction</a>";
			if($supp['location'] == 'int'){
				$fbal = "$sp4 $supp[currency] $supp[fbalance]";
				$trans = "<a href='core/intsupp-trans.php?supid=$supp[supid]'>Transaction</a>";
				$pay="<td><a href='bank/bank-pay-supp-int.php?supid=$supp[supid]&cash=yes'>Add Payment</a></td>";
			} else {
				$pay="<td><a href='bank/bank-pay-supp.php?supid=$supp[supid]&cash=yes'>Add Payment</a></td>";
			}

			# Alternate bgcolor
			$bgColor = bgcolor($i);
			$printSupp .= "<tr bgcolor='$bgColor'><td>$deptname</td>";

			if ( $action == "contact_acc" ) {
				$updatelink = "javascript: updateAccountInfo(\"$supp[supid]\", \"$supp[supno]\");";

				$printSupp .= "	<td><a href='$updatelink'>$supp[supno]</a></td>
						<td align=center><a href='$updatelink'>$supp[supname]</a></td>";
			} else {
				$printSupp .= "<td>$supp[supno]</td><td align=center>$supp[supname]</td>";
				$printSupp .= "<td align=center>$supp[branch]</td>";
			}

			$printSupp .= "
			<td>$supp[contname]</td>
			<td>$supp[tel]</td>
			<td>$supp[fax]</td>
			<td align='right' nowrap>$sp4 ".CUR." $supp[balance]</td>
			<td align='right' nowrap>$fbal</td>$pay";

			if ( $action == "listsupp" ) {
				// Retrieve the template settings
				db_conn("cubit");
				$sql = "SELECT filename FROM template_settings WHERE div='".USER_DIV."' AND template='statements'";
				$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
				$template = pg_fetch_result($tsRslt, 0);

				$printSupp .= "
				<td><a href='supp-det.php?supid=$supp[supid]'>Details</a></td>
				<td><a href='#' onclick='openPrintWin(\"supp-stmnt.php?supid=$supp[supid]\")'>Statement</a></td>
				<td>$trans</td>
				<td><a href='supp-edit.php?supid=$supp[supid]'>Edit</a></td>
				<td><a href='supp-pricelist.php?supid=$supp[supid]'>Pricelist</a></td>";

				if($supp['blocked'] == 'yes'){
					$printSupp .= "<td><a href='supp-unblock.php?supid=$supp[supid]'>Unblock</a></td>";
				}else{
					$printSupp .= "<td><a href='supp-block.php?supid=$supp[supid]'>Block</a></td>";
				}

				/* MODULE BEG: trh */
                $trhqry = new dbSelect("keys", "trh", grp(
			m("cols", "email"),
			m("where", "suppid='$supp[supid]'")
                ));
                $trhqry->run();

                if ($trhqry->num_rows() == 0) {
                    $printSupp .= "<td><a href='transheks/comm_init.php?suppid=$supp[supid]'>Configure for Transheks</a></td>";
                } else {
                	//$trh_email = $trhqry->fetch_result();
                	//$printSupp .= "<td><a href='transheks/comm_init.php?suppid=$supp[supid]&email=$trh_email'>Reconfigure for Transheks</a></td>";
                }
                /* MODULE END: trh */

			$printSupp .= "<td>$rm</td>$addcontact</tr>";
			} else {
				$printSupp .= "<td><a href='javascript: popupSized(\"supp-det.php?supid=$supp[supid]\", \"suppdetails\", 500, 300, \"\");'>Details</a></td>";
			}
		}

		$tot=sprint($tot);
		$printSupp .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan=7>Total Amount Owed, to $i ".($i > 1 ? "suppliers" : "supplier")." </td>
			<td align=right>".CUR." $tot</td>
			<td colspan='12'>&nbsp;</td>
		</tr>";
	}
	
	$next_offset = $offset + LIMIT;
	$prev_offset = $offset - LIMIT;
	
	$get_vars = "filter=$filter&search=$search";
		
	$prev_ancor = ($prev_offset >= 0) ? "<a href='".SELF."?offset=$prev_offset&$get_vars'>&laquo; Previous</a>" : "";
	$next_ancor = ($next_offset < $count) ? "<a href='".SELF."?offset=$next_offset&$get_vars'>Next &raquo;</a>" : "";
		
	$printSupp .= "
	<tr bgcolor='".bgcolorg()."'>
	<td colspan='20' align='center'>
			$prev_ancor
			$next_ancor
		</td>
	</tr>";

	$printSupp .= "
		</form>
		<form action='".SELF."' method='POST'>
		<input type='hidden' name='export' value='yes'>
		<input type='hidden' name='filter' value='$filter'>
		<input type='hidden' name='search' value='$search'>
		<tr><td><br></td></tr>
		<tr>
			<td colspan='3'><input type='submit' value='Export to Spreadsheet'></td>
		</tr>
		</form>
	</table>";

	if ( $action == "listsupp" ) {
		$printSupp .= "
		<p></p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
				<tr bgcolor='".bgcolorg()."'>
				<td><a href='supp-new.php'>Add Supplier</a></td>
			</tr>
				<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	}
	return $printSupp;

}



# show stock
function export ($_GET)
{

	# get vars
	extract ($_GET);

	if ( ! isset($action) ) $action = "listsupp";

	if($filter=="") {
		unset($filter);
	}

	if(isset($filter) && !isset($all)){
		$sqlfilter = " AND lower($filter) LIKE lower('%$search%')";
	}else{
		$filter = "";
		$search = "";
		$sqlfilter = "";
	}

	$filterarr = array("supname" => "Supplier Name", "supno" => "Account Number");
	$filtersel = extlib_cpsel("filter", $filterarr, $filter);

	# Set up table to display in
	$printSupp = "
	<h3>Current Suppliers</h3>
	<p>
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Department</th>
		<th>Supp No.</th>
		<th>Supplier Name</th>
		<th>Branch</th>
		<th>Contact Name</th>
		<th>Tel No.</th>
		<th>Fax No.</th>
		<th colspan='2'>Balance</th>
	</tr>";

	# connect to database
	db_connect();

	# Query server
	$i = 0;
	$tot=0;
    $sql = "SELECT * FROM suppliers WHERE (div = '".USER_DIV."' OR ddiv = '".USER_DIV."') $sqlfilter ORDER BY supname ASC";
    $suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	if (pg_numrows ($suppRslt) < 1) {
		$printSupp .= "
						<tr>
							<td colspan='20'><li>There are no Suppliers in Cubit.</td>
						</tr>";
	}else{
		while ($supp = pg_fetch_array ($suppRslt)) {
			# get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				$deptname = "<li class=err>Department not Found.";
			}else{
				$dept = pg_fetch_array($deptRslt);
				$deptname = $dept['deptname'];
			}
			$supp['balance']=sprint($supp['balance']);

			# Check if record can be removed
			db_connect();
			$sql = "SELECT * FROM cashbook WHERE banked = 'no' AND supid = '$supp[supid]' AND div = '".USER_DIV."'";
			$rs = db_exec($sql) or errDie("Unable to get cashbook entries.",SELF);
			if(pg_numrows($rs) < 1 && $supp['balance'] == 0){
				$rm="<a href='supp-rem.php?supid=$supp[supid]'>Remove</a>";
			}else{
				$rm = "";
			}
			#if($supp['balance']==0) {$rm="<a href='supp-rem.php?supid=$supp[supid]'>Remove</a>";} else {$rm="";}

			// check if supplier can be added to contact list
			$addcontact = "<td><a href='conper-add.php?type=supp&id=$supp[supid]'>Add Contact</a></td>";

			$tot = $tot + $supp['balance'];

			# Locations drop down
			$locs = array("loc"=>"Local", "int"=>"International", "" => "");
			$loc = $locs[$supp['location']];

			$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

			$fbal = "$sp4--$sp4";
			$trans = "<a href='core/supp-trans.php?supid=$supp[supid]'>Transaction</a>";
			if($supp['location'] == 'int'){
				$fbal = "$sp4 $supp[currency] $supp[fbalance]";
				$trans = "<a href='core/intsupp-trans.php?supid=$supp[supid]'>Transaction</a>";
			}

			# Alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$printSupp .= "<tr><td>$deptname</td>";

			if ( $action == "contact_acc" ) {
				$updatelink = "javascript: updateAccountInfo(\"$supp[supid]\", \"$supp[supno]\");";

				$printSupp .= "	<td><a href='$updatelink'>$supp[supno]</a></td>
						<td align=center><a href='$updatelink'>$supp[supname]</a></td>";
			} else {
				$printSupp .= "<td>$supp[supno]</td><td align=center>$supp[supname]</td>";
				$printSupp .= "<td align=center>$supp[branch]</td>";
			}

			$printSupp .= "
			<td>$supp[contname]</td><td>$supp[tel]</td>
			<td>$supp[fax]</td><td align=right>$sp4 ".CUR." $supp[balance]</td><td align=right>$fbal</td>";

			if ( $action == "listsupp" ) {
				// Retrieve the template settings
				db_conn("cubit");
				$sql = "SELECT filename FROM template_settings WHERE div='".USER_DIV."' AND template='statements'";
				$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
				$template = pg_fetch_result($tsRslt, 0);
			} else {
				$printSupp .= "	<td><a href='javascript: popupSized(\"supp-det.php?supid=$supp[supid]\", \"suppdetails\", 500, 300, \"\");'>Details</a></td>";
			}
			$i++;
		}
		if ($i>1){$s="s";} else {$s="";}
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$tot=sprint($tot);
			$printSupp .= "<tr><td colspan=7>Total Amount Owed, to $i supplier$s </td><td align=right>".CUR." $tot</td></tr>";
	}

	$printSupp .= "</form></table>";

	if ( $action == "listsupp" ) {
		$printSupp .= "
		";
	}

	$OUTPUT=$printSupp;

	include("xls/temp.xls.php");
	Stream("Suppliers", $OUTPUT);

	return $printSupp;
}



// add's the supplier to the contact list
function AddContact()
{

	global $_GET;

	$v = & new Validate();
	if ( ! $v->isOk($_GET["addcontact"], "num", 1, 9, "") )
		return "Invalid Supplier Number";

	// check if supplier can be added to contact list
	$rslt = db_exec("SELECT * FROM cons WHERE supp_id='$_GET[addcontact]'");
	if ( pg_numrows($rslt) >= 1 ) {
		return "Supplier Already Added as a Contact<br>";
	}

	// get it from the db
	$sql = "SELECT * FROM suppliers WHERE supid='$_GET[addcontact]'";
	$rslt = db_exec($sql) or errDie("Unable to add supplier to contact list. (RD)", SELF);
	if ( pg_numrows($rslt) < 1 )
		return "Unable to add supplier to contact list. (RD2)";

	$data = pg_fetch_array($rslt);

	extract($data);

	// put it in the db
	db_connect();
	$sql = "INSERT INTO cons (name,surname,comp,ref,tell,cell,fax,email,hadd,padd,date,supp_id,con,by,div)
		VALUES ('$contname','$supname','','Supplier','$tel','','$fax','$email','$supaddr','',CURRENT_DATE,
			'$supid', 'No', '".USER_NAME."','".USER_DIV."')";
	$rslt = db_exec($sql) or errDie ("Unable to add supplier to contact list.", SELF);

	if ( pg_cmdtuples($rslt) < 1 ) {
		return "<li class=err>Unable to add supplier to contact list.</li>";
	}

}


?>
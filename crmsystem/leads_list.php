<?
# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please lead us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

require ("../settings.php");

// remove all '
if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_POST_VARS[$key] = str_replace("'", "", $value);
	}
}
if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

// store the post vars in get vars, so that both vars can be accessed at once
// it is done this was around, so post vars get's higher priority and overwrites duplicated in get vars
if ( isset($HTTP_POST_VARS) ) {
	foreach( $HTTP_POST_VARS as $arr => $arrval ) {
		$HTTP_GET_VARS[$arr] = str_replace("'","",$arrval);
	}
}

// see what to do
define("CONTACT_DISPLAY_AMOUNT", 25);
if (isset ($HTTP_GET_VARS["key"])) {
	switch ($HTTP_GET_VARS["key"]) {
		default:
			$OUTPUT = listLeads();
	}
} else {
	$OUTPUT = listLeads();
}

require ("../template.php");



// function creates the listing of the leads (listing all if no alphabet letter was specified)
function listLeads()
{

	global $HTTP_GET_VARS, $HTTP_SESSION_VARS;
	global $mail_sender;
	extract ( $HTTP_GET_VARS );

	$OUTPUT = "";

	// store unset variables so different commands remember previous values
	// this way u can search, and go through leads, suppliers and customers with the same search
	$fields = array();

	$fields["key"] = "";
	$fields["fields"] = "";
	$fields["filter"] = "";
	$fields["offset"] = 0;
	$fields["action"] = "viewcon";
	$fields["frm_day"] = "";
	$fields["frm_month"] = "";
	$fields["frm_year"] = "";
	$fields["to_day"] = "";
	$fields["to_month"] = "";
	$fields["to_year"] = "";
	$fields["salespn"] = "";

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	$offset += 0;
	$pass_filter = $filter; // stores the filter so it can be passed on cleanly

	// format the filter
	if ( ! isset($filter) || $filter == 'all' ) {
		$filter = "()";
	} else if ( isset($key) && $key == "search" ) {
		$filter = "(" . str_replace(" ","|",$filter) . ")";
	} else {
		$filter = "^($filter)";
	}
	// create the fields array
	if ( ! isset($field) || ! isset($key) || $key != "search" ) {
		$fields_look[]="surname";
	} else {
		$fields_look = explode(",", $field);
	}

	// set the $ref var
	//if ( ! isset($ref) )
	$ref = "leads";

	// select the type of lead to view
	if ( $ref == "suppliers" ) {
		$pgref = "ref='Supplier'";
	} elseif ($ref=="customers") {
		$pgref = "ref='Customer'";
	} else {
		$pgref="ref <> 'Supplier' AND ref <> 'Customer'";
	}

	// create the actual conditions
	$sql_filters = Array();
	foreach ( $fields_look as $arr => $arrval ) {
		$sql_filters[] = "$arrval ~* '$filter'";
	}
	$sql_filters = "(" . implode(" OR ", $sql_filters) . ")";

	// count the results first
	$sql = "SELECT COUNT(id) FROM leads
		WHERE $pgref
			AND $sql_filters
			AND ( (
				assigned_to = '$HTTP_SESSION_VARS[USER_NAME]'
				AND con = 'Yes'
			) OR (
				con = 'No'
			) )";

	db_conn("crm");
	$rslt = db_exec($sql);

	$result_count = pg_fetch_result($rslt, 0, 0);

	// execute the query
	$sql = "SELECT id, name, surname, title, accountname, tell, team_id, email FROM leads
		WHERE $pgref
			AND $sql_filters
			AND ( (
				assigned_to = '$HTTP_SESSION_VARS[USER_NAME]'
				AND con = 'Yes'
			) OR (
				con = 'No'
			) ) ORDER BY surname LIMIT ".CONTACT_DISPLAY_AMOUNT." OFFSET $offset";
	db_conn("crm");
	$rslt = db_exec($sql);

	// temp vars
	$cellcolor[0] = TMPL_tblDataColor1;
	$cellcolor[1] = TMPL_tblDataColor2;
	$cellcolor[2] = TMPL_tblDataColorOver;

	// generate the leads list from Cubit results
	$lead_data = "";
	$i=0;
	while ( $row = pg_fetch_array($rslt) ) {
		if (!user_in_team($row["team_id"], USER_ID)) {
			continue;
		}
		// create the event data for the row
		$rowname = "conrow_$row[id]";
		$mmove_events = "
			onMouseOver = 'javascript: changeLeadRowColor(\"$rowname\",\"$cellcolor[2]\");'
			onMouseOut = 'javascript: changeLeadRowColor(\"$rowname\",\"$cellcolor[$i]\");'";

		// create the row with it's information
		$fullname = "";
		if ( ! empty($row["name"]) ) $fullname .= "$row[name] ";
		$fullname .= "$row[surname]";

		if ( $action == "viewcon" ) {
			$href_action = "javascript: viewLead(\"$row[id]\")";
		} else if ( $action == "reportsto" ) {
			$href_action = "javascript: updateReportsTo(\"$row[id]\", \"$fullname\")";
		}

		$lead_data .= "
			<tr id='$rowname' $mmove_events bgcolor='$cellcolor[$i]'>
				<td><a href='$href_action'>$fullname</a></td>";

		$lead_data .="
				<td>$row[title]</td>
				<td>$row[accountname]</td>
				<td>$row[tell]</td>
				<td align=center><a href='$mail_sender$row[email]' target=rightframe>$row[email]</a></td>
				<td>
					<a href='../groupware/today.php?key=future'>
						Date/s to be contacted
					</a>
				</td>
			</tr>";

		$i = ++$i % 2; // select the color of the next row (this way is neat little formula i just thought up)
	}

	// if no data has been found, make a default cell telling this
	if ( $lead_data == "" ) {
		$lead_data = "<tr bgcolor='$cellcolor[0]'><td colspan=6>No leads found</td></tr>";
	}

	// the select filter list
	$flist = "<font size=2><b>";

	$flist .= "<a class=nav href='leads_list.php?ref=$ref&action=$action&filter=0";
	for ( $i = ord('1') ; $i <= ord('9') ; $i++ ) {
		$flist .= "|" . chr($i);
	}
	$flist .= "'>#</a> ";

	for ( $i = ord('A') ; $i <= ord('Z') ; $i++ ) {
		$flist .= "<a class=nav href='leads_list.php?ref=$ref&action=$action&filter=" . chr( $i ) . "|" . chr( $i+32 ) ."'>"
		. chr($i) ."</a> ";
	}
	$flist .= "<a class=nav href='leads_list.php?ref=$ref&action=$action'>All</a></b></font>";

	// set which is selected under the lead type selection box
	$refselected_leads = "";
	$refselected_suppliers = "";
	$refselected_customers = "";

	if ( $ref == "suppliers" )
	$refselected_suppliers = "selected";
	else if ( $ref == "customers" )
	$refselected_customers = "selected";
	else
	$refselected_leads = "selected";

	// Sales person
	db_conn("exten");
	$sql = "SELECT * FROM salespeople WHERE div='".USER_DIV."' ORDER BY salesp ASC";
	$rslt = db_exec($sql) or errDie("Unable to retrieve sales people from Cubit.");

	$salespn_out = "<select name='salespn' style='width: 100%'>";
	while ($salespn_data = pg_fetch_array($rslt)) {
		if ($salespn == $salespn_data["salespid"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$salespn_out .= "<option value='$salespn_data[salespid]'>$salespn_data[salesp]</option>";
	}
	$salespn_out .= "</select>";

	// create the output
	$OUTPUT = "
	<script>
		// leads scripts
		function changeLeadRowColor(obj, tocolor) {
			getObjectById(obj).style.background=tocolor;
		}

		function viewLead(id) {
			popupOpen('leads_view.php?id=' + id,'lead_popup','scrollbars=yes,width=720,height=600');
		}

		function updateReportsTo(id, name) {
			window.opener.document.frm_con.reports_to.value=name;
			window.opener.document.frm_con.reports_to_id.value=id;
			window.close();
		}
	</script>
	<center>
	<table ".TMPL_tblDflts.">
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='search'>
					<input type='hidden' name='field' value='name,surname,comp,email,padd,hadd'>
					<input type='hidden' name='ref' value='$ref'>
					<input type='hidden' name='action' value='$action'>
					<input type='text' name='filter' value='' style='width: 100%'>
			</td>
			<td>
					<input type='submit' value='search' style='width: 100%'>
				</form>
			</td>
		</tr>
	</table>

	<table width='100%' ".TMPL_tblDflts.">
	<tr>
		<td align='left' nowrap><font size='2'><b>";

	if ( $action == "viewcon" ) {
		$OUTPUT .= "<a class='nav' href=\"leads_new.php\">New Lead</a></b></font>";
	}

	$OUTPUT .= "
		</td>
	</tr>
	<tr>
		<td width='80%' align='center' nowrap>
			$flist
		</td>
		<td width='20%' align='right' nowrap>
			<form method='POST' action='".SELF."'>
				<input type='hidden' name='filter' value='$pass_filter'>
				<input type='hidden' name='key' value='$key'>
				<input type='hidden' name='fields' value='$fields'>
				<input type='hidden' name='action' value='$action'>
			</form>
		</td>
	</tr>
	<tr>
		<td width='100%' colspan='2'>
		<table width='100%' cellpadding='1' cellspacing='0'>";

	// previous images
	if ( $offset < CONTACT_DISPLAY_AMOUNT ) {
		$go_previous = "
			<font color='#9C999C'>
			<img src='go_start_off.gif'> Start
			<img src='go_previous_off.gif'> Previous
			</font>";
	} else {
		$go_previous = "
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=0'
					id=leads_nextprevious>
				<img border=0 src='go_start.gif'> Start
			</a>
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=".($offset - CONTACT_DISPLAY_AMOUNT)."'
					id=leads_nextprevious>
				<img border=0 src='go_previous.gif'> Previous
			</a>";
	}

	if ( $offset + CONTACT_DISPLAY_AMOUNT >= $result_count ) {
		$go_next = "
			<font color='#9C999C'>
			Next <img src='go_next_off.gif'>
			End <img src='go_end_off.gif'>
			</font>";
	} else {
		$go_next = "
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=".($offset + CONTACT_DISPLAY_AMOUNT)."'
					id=leads_nextprevious>
				Next <img border=0 src='go_next.gif'>
			</a>
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=".($result_count - CONTACT_DISPLAY_AMOUNT)."'
					id=leads_nextprevious>
				End <img border=0 src='go_end.gif'>
			</a>";
	}

	$OUTPUT .= "<tr>
			<td colspan=6 bgcolor='#eeeeee' height='15' align=right>
				$go_previous&nbsp;&nbsp;
				(".($offset+1)." - ".($offset+CONTACT_DISPLAY_AMOUNT<=$result_count?$offset+CONTACT_DISPLAY_AMOUNT:$result_count)." of $result_count)
				&nbsp;&nbsp;$go_next
			</td>
		</tr>
		<tr>";

	// table heading style
	$head_s = "background='header_bg2.gif' height=20 align=left";

	$OUTPUT .= "	<td $head_s><b>Company/Name</b></td>
			<td $head_s><b>Title</b></td>
			<td $head_s><b>Account</b></td>
			<td $head_s><b>Tel</b></td>
			<td $head_s><b>Email</b></td>
			<td $head_s><b>Options</b></td>";


	// finish the output
	$OUTPUT .= "		</tr>
				$lead_data
			</table>
		</td>
	</tr>
	</table>";
	return $OUTPUT;

}


?>
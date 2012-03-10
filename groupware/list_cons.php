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
# file please contact us at +27834433455 or via email
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
require ("gw-common.php");

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
			$OUTPUT = listContacts();
	}
} else {
	$OUTPUT = listContacts();
}

require ("gw-tmpl.php");

// function creates the listing of the contacts (listing all if no alphabet letter was specified)
function listContacts() {
	global $HTTP_GET_VARS, $HTTP_SESSION_VARS;
	global $mail_sender;
	extract ( $HTTP_GET_VARS );

	$OUTPUT = "";

	// store unset variables so different commands remember previous values
	// this way u can search, and go through contacts, suppliers and customers with the same search
	if ( ! isset($key) ) $key = "";
	if ( ! isset($fields) ) $fields = "";
	if ( ! isset($filter) ) $filter = "";
	if ( ! isset($offset) ) $offset = 0;
	if ( ! isset($action) ) $action = "viewcon";
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
	if ( ! isset($fields) || ! isset($key) || $key != "search" ) {
		$fields_look[]="surname";
	} else {
		$fields_look = explode(",", $fields);
	}

	// set the $ref var
	if ( ! isset($ref) ) $ref = "contacts";

	// select the type of contact to view
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
	$sql = "SELECT COUNT(id) FROM cons
		WHERE $pgref
			AND $sql_filters
			AND ( (
				assigned_to = '$HTTP_SESSION_VARS[USER_NAME]'
				AND con = 'Yes'
			) OR (
				con = 'No'
			) )";

	db_conn("cubit");
	$rslt = db_exec($sql);

	$result_count = pg_fetch_result($rslt, 0, 0);

	// execute the query
	$sql = "SELECT * FROM cons
		WHERE $pgref
			AND $sql_filters
			AND ( (
				assigned_to = '$HTTP_SESSION_VARS[USER_NAME]'
				AND con = 'Yes'
			) OR (
				con = 'No'
			) ) ORDER BY surname LIMIT ".CONTACT_DISPLAY_AMOUNT." OFFSET $offset";
	db_conn("cubit");
	$rslt = db_exec($sql);

	// temp vars
	$cellcolor[0] = TMPL_tblDataColor1;
	$cellcolor[1] = TMPL_tblDataColor2;
	$cellcolor[2] = TMPL_tblDataColorOver;

	// generate the contacts list from Cubit results
	$contact_data = "";
	$i=0;
	while ( $row = pg_fetch_array($rslt) ) {
		if (!in_team($row["team_id"], USER_ID)) {
			continue;
		}

		// create the event data for the row
		$rowname = "conrow_$row[id]";
		$mmove_events = "
			onMouseOver = 'javascript: changeContactRowColor(\"$rowname\",\"$cellcolor[2]\");'
			onMouseOut = 'javascript: changeContactRowColor(\"$rowname\",\"$cellcolor[$i]\");'";

		// create the row with it's information
		$fullname = "";
		if ( ! empty($row["name"]) ) $fullname .= "$row[name] ";
		$fullname .= "$row[surname]";

		if ( $action == "viewcon" ) {
			$href_action = "javascript: viewContact(\"$row[id]\")";
		} else if ( $action == "reportsto" ) {
			$href_action = "javascript: updateReportsTo(\"$row[id]\", \"$fullname\")";
		}

		$contact_data .= "
			<tr id='$rowname' $mmove_events class='even'>
				<td><a href='$href_action'>$fullname</a></td>";

		$contact_data .="
				<td>$row[title]</td>
				<td>$row[accountname]</td>
				<td>$row[tell]</td>
				<td align=center>
					<a href='$mail_sender$row[email]' target=rightframe>
						$row[email]
					</a>
				</td>
			</tr>";

		$i = ++$i % 2; // select the color of the next row (this way is neat little formula i just thought up)
	}

	// if no data has been found, make a default cell telling this
	if ( $contact_data == "" ) {
		$contact_data = "<tr class='odd'><td colspan=6>No contacts found</td></tr>";
	}

	// the select filter list
	$flist = "<font size=2><b>";

	$flist .= "<a class=nav href='list_cons.php?ref=$ref&action=$action&filter=0";
	for ( $i = ord('1') ; $i <= ord('9') ; $i++ ) {
		$flist .= "|" . chr($i);
	}
	$flist .= "'>#</a> ";

	for ( $i = ord('A') ; $i <= ord('Z') ; $i++ ) {
		$flist .= "<a class=nav href='list_cons.php?ref=$ref&action=$action&filter=" . chr( $i ) . "|" . chr( $i+32 ) ."'>"
			. chr($i) ."</a> ";
	}
	$flist .= "<a class=nav href='list_cons.php?ref=$ref&action=$action'>All</a></b></font>";

	// set which is selected under the contact type selection box
        $refselected_contacts = "";
	$refselected_suppliers = "";
	$refselected_customers = "";

	if ( $ref == "suppliers" )
		$refselected_suppliers = "selected";
	else if ( $ref == "customers" )
		$refselected_customers = "selected";
	else
		$refselected_contacts = "selected";

	// create the output
	$OUTPUT = "
	<script>
		// contacts scripts
		function changeContactRowColor(obj, tocolor) {
			getObjectById(obj).style.background=tocolor;
		}

		function viewContact(id) {
			popupOpen('view_con.php?id=' + id,'contact_popup','scrollbars=yes,width=660,height=450');
		}

		function updateReportsTo(id, name) {
			window.opener.document.frm_con.reports_to.value=name;
			window.opener.document.frm_con.reports_to_id.value=id;
			window.close();
		}
	</script>
	<table width='95%' border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<td align=left nowrap><font size=2><b>";

	if ( $action == "viewcon" ) {
		$OUTPUT .= "<a class=nav href=\"new_con.php\">New Main Contact</a></b></font>";
	}

	$OUTPUT .= "
		</td>
		<td align=right nowrap>
			<form method=post action='".SELF."'>
				<input type=hidden name=key value=search>
				<input type=hidden name=fields value='name,surname,comp,email,padd,hadd'>
				<input type=hidden name=ref value='$ref'>
				<input type=hidden name=action value='$action'>
				<input type=text name=filter value=''>
				<input type=submit value=search>
			</form>
		</td>
	</tr>
	<tr>
		<td width='80%' align=center nowrap>
			$flist
		</td>
		<td width='20%' align=right nowrap>
			<form method=post action='".SELF."'>
			<input type=hidden name=filter value='$pass_filter'>
			<input type=hidden name=key value='$key'>
			<input type=hidden name=fields value='$fields'>
			<input type=hidden name=action value='$action'>
			<select name=ref onChange='form.submit()'>
				<option value='contacts' $refselected_contacts>Contacts</option>
				<option value='customers' $refselected_customers>Customers</option>
				<option value='suppliers' $refselected_suppliers>Suppliers</option>
			</select>
			</form>
		</td>
	</tr>
	<tr>
		<td width='100%' colspan=2>
		<table cellpadding='2' cellspacing='0' class='shtable' width='100%'>";

	// previous images
	if ( $offset < CONTACT_DISPLAY_AMOUNT ) {
		$go_previous = "
			<font color='#9C999C'>
			<img src='../crmsystem/go_start_off.gif'> Start
			<img src='../crmsystem/go_previous_off.gif'> Previous
			</font>";
	} else {
		$go_previous = "
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=0'
					id=contacts_nextprevious>
				<img border=0 src='crmsystem/go_start.gif'> Start
			</a>
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=".($offset - CONTACT_DISPLAY_AMOUNT)."'
					id=contacts_nextprevious>
				<img border=0 src='crmsystem/go_previous.gif'> Previous
			</a>";
	}

	if ( $offset + CONTACT_DISPLAY_AMOUNT >= $result_count ) {
		$go_next = "
			<font color='#9C999C'>
			Next <img src='../crmsystem/go_next_off.gif'>
			End <img src='../crmsystem/go_end_off.gif'>
			</font>";
	} else {
		$go_next = "
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=".($offset + CONTACT_DISPLAY_AMOUNT)."'
					id=contacts_nextprevious>
				Next <img border=0 src='crmsystem/go_next.gif'>
			</a>
			<a href='".SELF."?ref=$ref&action=$action&key=$key&fields=$fields&filter=$pass_filter&offset=".($result_count - CONTACT_DISPLAY_AMOUNT)."'
					id=contacts_nextprevious>
				End <img border=0 src='crmsystem/go_end.gif'>
			</a>";
	}

	$OUTPUT .= "<tr>
			<td colspan=5 bgcolor='#eeeeee' height=15 align=right>
				$go_previous&nbsp;&nbsp;
				(".($offset+1)." - ".($offset+CONTACT_DISPLAY_AMOUNT<=$result_count?$offset+CONTACT_DISPLAY_AMOUNT:$result_count)." of $result_count)
				&nbsp;&nbsp;$go_next
			</td>
		</tr>
		<tr>";

	// table heading style
	$head_s = "background='crmsystem/header_bg2.gif' height=20 align=left";

	$OUTPUT .= "	<th>Company/Name</th>
		<th>Title</th>
		<th>Account</th>
		<th>Tel</th>
		<th>Email</th>";


// finish the output
	$OUTPUT .= "		</tr>
				$contact_data
			</table>
		</td>
	</tr>
	</table>";

	return $OUTPUT;
}


?>

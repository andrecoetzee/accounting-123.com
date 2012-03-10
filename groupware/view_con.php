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

// store the post vars in get vars, so that both vars can be accessed at once
// it is done this was around, so post vars get's higher priority and overwrites duplicated in get vars
if ( isset($HTTP_POST_VARS) ) {
	foreach( $HTTP_POST_VARS as $arr => $arrval ) {
		$HTTP_GET_VARS[$arr] = $arrval;
	}
}

// see what to do
if (isset ($HTTP_GET_VARS["key"])) {
	switch ($HTTP_GET_VARS["key"]) {
		case "delete":
		case "confirm_delete":
			$OUTPUT = deleteContact();
			break;
		default:
			$OUTPUT = viewContact ();
	}
} else {
	$OUTPUT = viewContact ();
}

# display output
require ("gw-tmpl.php");




# enter new data
function viewContact ()
{

	global $HTTP_GET_VARS;
	global $user_admin;

	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id,"num", 1,100, "Invalid num.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return $confirmCust;
	}



	$sql = "SELECT * FROM cubit.cons WHERE id='$id'";
	$con_rslt = db_exec($sql) or errDie("Unable to retrieve main contact.");
	$con_data = pg_fetch_array($con_rslt);

	// Check to see if we've actually got access to view this contact
	$sql = "SELECT admin FROM cubit.users WHERE userid='".USER_ID."'";
	$admin_rslt = db_exec($sql) or errDie("Unable to check for admin.");
	$admin = pg_fetch_result($admin_rslt, 0);

	if ($con_data["team_id"] && !$admin) {
		$sql = "SELECT * FROM crm.team_owners
		WHERE user_id='".USER_ID."' AND team_id='$con_data[team_id]'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");

		// ok, no access... next contact...
		if (!pg_num_rows($team_rslt)) {
			return "<li class='err'>You don't have sufficient permission to view this contact</li>";
		}
	}

	db_conn('cubit');

	$user = USER_ID;

	# write to db
	$Sql = "SELECT * FROM cons WHERE ((id='$id')and ((con='Yes' and assigned_to_id='$user') or(con='No')))";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if (pg_numrows($Rslt) < 1) {return "Contact not found";}
	$Data = pg_fetch_array($Rslt);

	$date = $Data['date'];

	$mon = substr($date,5,2);

	if ($mon == 1){$td = 31; $M = 'January';}
	if ($mon == 2){$td = 28; $M = 'February';}
	if ($mon == 3){$td = 31; $M = 'March';}
	if ($mon == 4){$td = 30; $M = 'April';}
	if ($mon == 5){$td = 31; $M = 'May';}
	if ($mon == 6){$td = 30; $M = 'June';}
	if ($mon == 7){$td = 31; $M = 'July';}
	if ($mon == 8){$td = 31; $M = 'August';}
	if ($mon == 9){$td = 30; $M = 'September';}
	if ($mon == 10){$td = 31; $M = 'October';}
	if ($mon == 11){$td = 30; $M = 'November';}                             //        and substr(date,7,4)='$year'
	if ($mon == 12){$td = 31; $M = 'December';}

	$Day = substr($date,8,2);
	$Day = $Day + 0;
	$Year = substr($date,0,4);

    $Date = $Day." ".$M." "." ".$Year;

    $hadd = $Data['hadd'];
    $padd = $Data['padd'];

$busy_deleting = isset($HTTP_GET_VARS["key"]) && $HTTP_GET_VARS["key"] == "confirm_delete";

// only show this when not deleting
$viewContact = "";
if ( ! ($busy_deleting) )
	$viewContact .= "<center><h3>Main Contact details</h3></center>";

	db_conn('cubit');


	$i = 0;
	$conpers = "";


	$Sl = "SELECT * FROM conpers WHERE con='$Data[id]' ORDER BY name";
	$Ry = db_exec($Sl) or errDie("Unable to get contacts from db.");

	if(pg_num_rows($Ry) > 0) {

		$conpers = "
			<h3>Sub Contacts</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Name</th>
					<th>Position</th>
					<th>Tel</th>
					<th>Cell</th>
					<th>Fax</th>
					<th>Email</th>
					<th>Notes</th>
					<th colspan='2'>Options</th>
				</tr>";

		while($cp = pg_fetch_array($Ry)) {
			$i++;
			$bgcolor = ($i%2) ? "class='odd'" : "class='even'";

			$conpers .= "
				<tr $bgcolor>
					<td>$cp[name]</td>
					<td>$cp[pos]</td>
					<td>$cp[tell]</td>
					<td>$cp[cell]</td>
					<td>$cp[fax]</td>
					<td>$cp[email]</td>
					<td>$cp[notes]</td>
					<td><a href='../conper-edit.php?id=$cp[id]&type=edit'>Edit</a></td>
					<td><a href='../conper-rem.php?id=$cp[id]'>Delete</a></td>
				</tr>";
		}

		$conpers .= "</table>";
	}

	extract($Data);

	if (isset($birthdate)) {
		list($bf_year, $bf_month, $bf_day) = explode("-", $birthdate);
		$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_month, $bf_day, $bf_year));
	} else {
		$birthdate_description = "";
	}

	$sql = "SELECT * FROM cons_img WHERE con_id=$id";
	$ci_rslt = db_exec($sql) or errDie("Unable to retrieve contact.");
	$ci_data = pg_fetch_array($ci_rslt);

	if (pg_num_rows($ci_rslt)) {
//		$img = "<img src='cons_image_view.php?id=$ci_data[id]' style='border: 1px solid #000; background: #fff' />";
		$img = "<img src='cons_image_view.php?id=$ci_data[con_id]' style='border: 1px solid #000; background: #fff' />";
	} else {
		$img = "To add an image for this contact use ' <a href='mod_con.php?id=$id'>Edit Contact</a>'";
	}

	$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");

	if (pg_num_rows($team_rslt)) {
		$team_name = pg_fetch_result($team_rslt, 0);
	} else {
		$team_name = "[None]";
	}

	$viewContact .= "
		<br>
		<center>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='4'>Contact Information</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4' align='center'>$img</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td width='120'>First Name</td>
				<td width='210'>$name</td>
				<td width='120'>Office Phone</td>
				<td width='210'>$tell_office</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company/Last Name</td>
				<td>$surname</td>
				<td>Mobile</td>
				<td>$cell</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reports To</td>
				<td>$reports_to</td>
				<td>Home Phone</td>
				<td>$tell</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Lead Source</td>
				<td>$lead_source</td>
				<td>Other Phone</td>
				<td>$tell_other</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Title</td>
				<td>$title</td>
				<td>Fax</td>
				<td>$fax</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Department</td>
				<td>$department</td>
				<td>E-mail</td>
				<td>$email</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Birthdate</td>
				<td>$birthdate_description</td>
				<td>Other E-mail</td>
				<td>$email_other</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td>$accountname ($account_type)</td>
				<td>Assistant</td>
				<td>$assistant</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>Assistant Phone</td>
				<td>$assistant_phone</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>&nbsp;</td>
				<td>Team Permissions</td>
				<td>$team_name</td>
			</tr>
			<tr>
				<th colspan='2'>Physical Address</th>
				<th colspan='2'>Postal Address</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='left' valign='top'><xmp>$hadd</xmp></td>
				<td colspan='2' align='left'><xmp>$padd</xmp></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>City</td>
				<td>$padd_city</td>
				<td>City</td>
				<td>$hadd_city</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>State/Province</td>
				<td>$padd_state</td>
				<td>State/Province</td>
				<td>$hadd_state</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td>$padd_code</td>
				<td>Postal Code</td>
				<td>$hadd_code</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Country</td>
				<td>$padd_country</td>
				<td>Country</td>
				<td>$hadd_country</td>
			</tr>
			<tr>
				<th colspan='4'>Description</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4' align='left'><xmp>$description</xmp></td>
			</tr>
			<tr>
				<th colspan='4'>Permissions</th>
			</tr>
			$user_perms
			$team_perms";

// check if own entry own entry, and if it is, create the delete field, so the delete field doesn't display
// when it is not your contact
if ( $Data["by"] == USER_NAME || $user_admin) {
	$DeleteField = "<a class=nav href=\"view_con.php?key=confirm_delete&id=$Data[id]\">
				Delete Contact</a>";
} else {
	$DeleteField = "";
}

// only add the following when not deleting
if ( ! ($busy_deleting) ) {
	$viewContact .= "
	</table>
	<font size='2'><b><a class='nav' href=\"mod_con.php?id=$Data[id]\">Edit Contact</a> &nbsp; &nbsp;</b></font>
	<font size='2'><b><a class='nav' href=\"conper-add.php?type=conn&id=$Data[id]\">Add Sub Contact</a> &nbsp; &nbsp;$DeleteField</b></font>";
}

	$viewContact .= "
		$conpers
		<p></center>";
	return $viewContact;

}


// function that deletes a contact
function deleteContact()
{

	global $HTTP_GET_VARS, $HTTP_SESSION_VARS;
	global $user_admin;

	$OUTPUT = "";

	if ( isset($HTTP_GET_VARS["key"]) && isset($HTTP_GET_VARS["id"]) ) {
		$id = $HTTP_GET_VARS["id"];
		$key = $HTTP_GET_VARS["key"];

		// first make sure it is this person's contact, or that the user is root
		if ( ! $user_admin ) {
			$rslt = db_exec("SELECT * FROM cons WHERE id='$id' AND
				( by='$HTTP_SESSION_VARS[USER_NAME]' )");
			if ( pg_num_rows($rslt) <= 0 ) {
				return "You are not allowed to delete this entry!";
			}
		}

		// check if a confirmation or deletion should occur (confirm_delete let's the cofirmation display)
		if ( $key == "confirm_delete" ) {
			$Sl = "SELECT * FROM cons WHERE id='$id'";
			$Rl = db_exec($Sl) or errDie("Unable to get contact details.");
			$cdata = pg_fetch_array($Rl);

			$Sl = "SELECT * FROM customers WHERE cusnum='$cdata[cust_id]'";
			$Ry = db_exec($Sl) or errDie("Unable to get customer from system.");

			if(pg_num_rows($Ry) > 0) {
				return "The contact you are trying to delete still has a customer connected to it.\nRemove the customer first.";
			}


			$Sl = "SELECT * FROM suppliers WHERE supid='$cdata[supp_id]'";
			$Ry = db_exec($Sl) or errDie("Unable to get supplier from system.");

			if(pg_num_rows($Ry) > 0) {
				return "The contact you are trying to delete still has a supplier connected to it.\nRemove the supplier first.";
			}
			$OUTPUT .= "<font size='2'>Are you sure you want to delete this entry:</font><br>";
			$OUTPUT .= viewContact();
			$OUTPUT .= "
				<table><tr><td align='center'>
					<form method='POST' action='".SELF."'>
						<input type='hidden' name='key' value='delete'>
						<input type='hidden' name='id' value='$id'>
						<input type='submit' value='yes'>
						<input type='button' value='no' onClick='window.close();'>
					</form>
				</td></tr></table>";
		} else if ( $key == "delete" ) {
			// delete it !!!!!!!
			$rslt = db_exec("DELETE FROM cons WHERE id='$id' ");
			if ( pg_cmdtuples($rslt) <= 0 ) {
				$OUTPUT .= "Error Deleting Entry<br> Please check that it exists, else contact Cubit<br>";
			} else {
				$OUTPUT .= "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
			}
		}
	} else {
			$OUTPUT .= "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	}
	return $OUTPUT;

}



?>
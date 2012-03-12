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

// store the post vars in get vars, so that both vars can be accessed at once
// it is done this was around, so post vars get's higher priority and overwrites duplicated in get vars
if ( isset($_POST) ) {
	foreach( $_POST as $arr => $arrval ) {
		$_GET[$arr] = $arrval;
	}
}

// see what to do
if (isset ($_GET["key"])) {
	switch ($_GET["key"]) {
		case "delete":
		case "confirm_delete":
			$OUTPUT = deleteLead();
			break;
		default:
			$OUTPUT = viewLead ();
	}
} else {
	$OUTPUT = viewLead ();
}

# display output
require ("../template.php");



# enter new data
function viewLead ()
{

	global $_GET;
	global $user_admin;

	extract ($_GET);

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

	db_conn('crm');

	$user = USER_ID;

	# write to db
	$Sql = "SELECT * FROM leads WHERE ((id='$id')and ((con='Yes' and assigned_to_id='$user') or(con='No')))";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if (pg_numrows($Rslt) < 1) {
		return "Lead not found";
	}
	$Data = pg_fetch_array($Rslt);

	$date = $Data['date'];

	$mon = substr($date,5,2);

	if ($mon == 1){$td = 31;$M='January';}
	if ($mon == 2){$td = 28;$M='February';}
	if ($mon == 3){$td = 31;$M='March';}
	if ($mon == 4){$td = 30;$M='April';}
	if ($mon == 5){$td = 31;$M='May';}
	if ($mon == 6){$td = 30;$M='June';}
	if ($mon == 7){$td = 31;$M='July';}
	if ($mon == 8){$td = 31;$M='August';}
	if ($mon == 9){$td = 30;$M='September';}
	if ($mon == 10){$td = 31;$M='October';}
	if ($mon == 11){$td = 30;$M='November';}                             //        and substr(date,7,4)='$year'
	if ($mon == 12){$td = 31;$M='December';}

	$Day = substr($date,8,2);
	$Day = $Day + 0;
	$Year = substr($date,0,4);

	$Date = $Day." ".$M." "." ".$Year;

	$hadd = $Data['hadd'];
	$padd = $Data['padd'];

	$busy_deleting = isset($_GET["key"]) && $_GET["key"] == "confirm_delete";

	// only show this when not deleting
	$viewLead = "";
	if ( ! ($busy_deleting) )
	$viewLead .= "<center><h3>Lead details</h3></center>";

	db_conn('crm');


	$i = 0;
	$conpers = "";

	/* DEACTIVED
	$Sl="SELECT * FROM conpers WHERE con='$Data[id]' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get leads from db.");

	if(pg_num_rows($Ry)>0) {

	$conpers="<h3>Lead Persons</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Name</th><th>Position</th><th>Tel</th><th>Cell</th><th>Fax</th><th>Email</th><th>Notes</th><th colspan=2>Options</th></tr>";

	while($cp=pg_fetch_array($Ry)) {
	$i++;
	$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

	$conpers.="<tr bgcolor='$bgcolor'><td>$cp[name]</td><td>$cp[pos]</td><td>$cp[tell]</td><td>$cp[cell]</td><td>$cp[fax]</td><td>$cp[email]</td>
	<td>$cp[notes]</td><td><a href='conper-edit.php?id=$cp[id]&type=edit'>Edit</a></td><td><a href='conper-rem.php?id=$cp[id]'>Delete</a></td></tr>";
	}

	$conpers.="</table>";
	}
	*/

	extract($Data);
	list($bf_year, $bf_month, $bf_day) = explode("-", $birthdate);
	$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_month, $bf_day, $bf_year));

	if (!empty($ncdate)) {
		$ncdate = explode("-", $ncdate);
		$ncdate_out = "$ncdate[2]-$ncdate[1]-$ncdate[0]";
	} else {
		$ncdate_out = "";
	}

	db_conn("exten");

	$sql = "SELECT salesp FROM salespeople WHERE salespid='$salespid'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve sales person from Cubit.");
	$salespn_out = pg_fetch_result($rslt, 0);

	$viewLead .= "
		<br>
		<center>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Lead Information</th>
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
				<td>Team Permissions</td>
				<td>$team_name</td>
				<td>Home Phone</td>
				<td>$tell</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Lead Source</td>
				<td>".crm_get_leadsrc($lead_source)."</td>
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
				<td>Religion</td>
				<td>$religion</td>
				<td>Website</td>
				<td>$website</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Race</td>
				<td>$race</td>
				<td>Next Contact Date</td>
				<td>$ncdate_out</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Gender</td>
				<td>$gender</td>
				<td>Sales Person</td>
				<td>$salespn_out</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
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
			<tr><td>&nbsp;</td></tr>
			<tr>
				<th colspan='2'>Description</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='left'><xmp>$description</xmp></td>
			</tr>
			<tr><td>&nbsp;</td></tr>";

	// check if own entry own entry, and if it is, create the delete field, so the delete field doesn't display
	// when it is not your lead
	if ( $Data["by"] == USER_NAME || $user_admin) {
		$DeleteField = "<a class=nav href=\"leads_view.php?key=confirm_delete&id=$Data[id]\">
				Delete Lead</a>";
	} else {
		$DeleteField = "";
	}

	// only add the following when not deleting
	if ( ! ($busy_deleting) ) {
// target='mainframe' onClick='setTimeout(window.close,50);'
		$viewLead .= "
			<tr>
				<td align='center' colspan='4'>
					<font size=2><b><a class='nav' href=\"leads_edit.php?id=$Data[id]\">Edit Lead</a> &nbsp;$DeleteField</b></font>
				</td>
			</tr>";

		/* DEACTIVED
		$viewLead .= "
		<tr>
		<td align=center colspan=4><font size=2><b>
		<a class=nav target=mainframe href=\"conper-add.php?type=conn&id=$Data[id]\" onClick='setTimeout(window.close,50);' >Add Lead Person</a> &nbsp;
		</b></font></td>
		</tr>";
		*/
	}

	$viewLead .= "
		</table>
		$conpers
		<p></center>";
	return $viewLead;

}



// function that deletes a lead
function deleteLead()
{

	global $_GET, $_SESSION;
	global $user_admin;

	$OUTPUT = "";

	if ( isset($_GET["key"]) && isset($_GET["id"]) ) {
		$id = $_GET["id"];
		$key = $_GET["key"];

		// first make sure it is this person's lead, or that the user is root
		if ( ! $user_admin ) {
			db_conn("crm");
			$rslt = db_exec("SELECT * FROM leads WHERE id='$id' AND
				( by='$_SESSION[USER_NAME]' )");
			if ( pg_num_rows($rslt) <= 0 ) {
				return "You are not allowed to delete this entry!";
			}
		}

		// check if a confirmation or deletion should occur (confirm_delete let's the cofirmation display)
		if ( $key == "confirm_delete" ) {
			db_conn("crm");
			$Sl = "SELECT * FROM leads WHERE id='$id'";
			$Rl = db_exec($Sl) or errDie("Unable to get lead details.");
			$cdata = pg_fetch_array($Rl);

			db_conn("cubit");
			$Sl = "SELECT * FROM customers WHERE cusnum='$cdata[cust_id]'";
			$Ry = db_exec($Sl) or errDie("Unable to get customer from system.");

			if(pg_num_rows($Ry) > 0) {
				return "The lead you are trying to delete still has a customer connected to it.\nRemove the customer first.";
			}

			db_conn("cubit");

			$Sl = "SELECT * FROM suppliers WHERE supid='$cdata[supp_id]'";
			$Ry = db_exec($Sl) or errDie("Unable to get supplier from system.");

			if(pg_num_rows($Ry) > 0) {
				return "The lead you are trying to delete still has a supplier connected to it.\nRemove the supplier first.";
			}
			$OUTPUT .= "<font size='2'>Are you sure you want to delete this entry:</font><br>";
			$OUTPUT .= viewLead();
			$OUTPUT .= "
				<table>
					<tr>
						<td align='center'>
							<form method='post' action='".SELF."'>
								<input type='hidden' name='key' value='delete'>
								<input type='hidden' name='id' value='$id'>
								<input type='submit' value='yes'>
								<input type='button' value='no' onClick='window.close();'>
							</form>
						</td>
					</tr>
				</table>";
		} else if ( $key == "delete" ) {
			// delete it !!!!!!!
			db_conn("crm");
			$rslt = db_exec("DELETE FROM leads WHERE id='$id' ");
			if ( pg_cmdtuples($rslt) <= 0 ) {
				$OUTPUT .= "Error Deleting Entry<br> Please check that it exists, else lead Cubit<br>";
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
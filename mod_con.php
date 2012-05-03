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
require ("settings.php");
require ("libs/ext.lib.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = con_data ($_POST);
			break;
		case "write":
			$OUTPUT = write_data ($_POST);
			break;
		default:
			$OUTPUT = get_data ($_GET);
	}
} else {
	$OUTPUT = get_data ($_GET);
}

# display output
require ("template.php");
# enter new data
function get_data ($_GET)
{

foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num", 1,100, "Invalid num.");

        # display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>$e[msg]</li>";
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

  db_conn('cubit');
  $user =USER_NAME;
  # write to db
  $Sql = "SELECT * FROM cons WHERE ((id='$id')and ((con='Yes' and by='$user' AND div = '".USER_DIV."') or(con='No' AND div = '".USER_DIV."')))";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");
  if(pg_numrows($Rslt)<1){return "Contact not Found";}
  $Data = pg_fetch_array($Rslt);


 $date= $Data['date'];

  $mon=substr($date,5,2);

  if ($mon==1){$td=31;$M='January';}
  if ($mon==2){$td=28;$M='February';}
  if ($mon==3){$td=31;$M='March';}
  if ($mon==4){$td=30;$M='April';}
  if ($mon==5){$td=31;$M='May';}
  if ($mon==6){$td=30;$M='June';}
  if ($mon==7){$td=31;$M='July';}
  if ($mon==8){$td=31;$M='August';}
  if ($mon==9){$td=30;$M='September';}
  if ($mon==10){$td=31;$M='October';}
  if ($mon==11){$td=30;$M='November';}                             //        and substr(date,7,4)='$year'
  if ($mon==12){$td=31;$M='December';}


   $Day=substr($date,8,2);
     $Day=$Day+0;
    $Year=substr($date,0,4);

    $Date=$Day." ".$M." "." ".$Year;



     $hadd=$Data['hadd'];
    $padd=$Data['padd'];

	if ( $Data["con"] == "No" ) {
		$Cons ="<select size=1 name=Con>
			<option value='No' selected>No</option>
			<option value='Yes'>Yes</option>
			</select>";
	} else {
		$Cons ="<select size=1 name=Con>
			<option value='No'>No</option>
			<option value='Yes' selected>Yes</option>
			</select>";
	}

	extract($Data);

	$select_source = extlib_cpsel("lead_source", crm_get_leadsrc(-1), $lead_source);

	if (!empty($birthdate)) {
		$date = explode("-", $birthdate);
		$bf_year = $date[0];
		$bf_month = $date[1];
		$bf_day = $date[2];
	} else {
		$bf_year = 0;
		$bf_month = 0;
		$bf_day = 0;
	}
	
	if ( $bf_year >= 1971) {
		$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));
	} else {
		$birthdate_description = "";
	}
	
	$select_bfday = "<select name=bf_day>";
	for ( $i = 1; $i <= 31; $i++ ) {
		if ( $bf_day == $i )
			$sel = "selected";
		else
			$sel = "";
	
		$select_bfday .= "<option $sel value='$i'>$i</option>";
	}
	$select_bfday .= "</select>";
	
	$select_bfmonth = "<select name=bf_month>";
	for ( $i = 1; $i <= 12; $i++ ) {
		if ( $bf_month == $i )
			$sel = "selected";
		else
			$sel = "";
	
		$select_bfmonth .= "<option $sel value='$i'>".date("F", mktime(0, 0, 0, $i, 1, 2000))."</option>";
	}
	$select_bfmonth .= "</select>";
	
	$select_bfyear = "<select name=bf_year>";
	for ( $i = 1971; $i <= 2027; $i++ ) {
		if ( $bf_year == $i )
			$sel = "selected";
		else
			$sel = "";
	
		$select_bfyear .= "<option $sel value='$i'>$i</option>";
	}
	$select_bfyear .= "</select>";
	$get_data = "
	<h3>Modify Contact</h3>
	<br>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' name=frm_con method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=4>Contact Information</th></tr>
	<tr class='bg-even'>
		<td width=120>First Name</td>
		<td width=210><input type=text size=27 name=name value='$name'></td>

		<td width=120>Office Phone</td>
		<td width=210><input type=text size=27 name=tell_office value='$tell_office'></td>
	</tr>
	<tr class='bg-odd'>
		<td>".REQ."Company/Last Name</td>
		<td><input type=text size=27 name=surname value='$surname'></td>

		<td>Mobile</td>
		<td><input type=text size=27 name=cell value='$cell'></td>
	</tr>
	<tr class='bg-even'>
		<td>Reports To</td>
		<td>
			<input type=text readonly=yes size=27 name='reports_to' value='$reports_to'>
			<input type=hidden name='reports_to_id' value='$reports_to_id'>
			<input type=button value='Select' onClick='popupSized(\"list_cons.php?action=reportsto\", \"reportsto\", 700, 300, \"\");'>
		</td>

		<td>Home Phone</td>
		<td><input type=text size=27 name=tell value='$tell'></td>
	</tr>
	<tr class='bg-odd'>
		<td>Lead Source</td>
		<td>$select_source</td>

		<td>Other Phone</td>
		<td><input type=text size=27 name=tell_other value='$tell_other'></td>
	</tr>
	<tr class='bg-even'>
		<td>Title</td>
		<td><input type=text size=27 name=title value='$title'></td>

		<td>Fax</td>
		<td><input type=text size=27 name=fax value='$fax'></td>
	</tr>
	<tr class='bg-odd'>
		<td>Department</td>
		<td><input type=text size=27 name=department value='$department'></td>

		<td>E-mail</td>
		<td><input type=text size=27 name=email value='$email'></td>
	</tr>
	<tr class='bg-even'>
		<td>Birthdate</td>
		<td>$select_bfday $select_bfmonth $select_bfyear</td>

		<td>Other E-mail</td>
		<td><input type=text size=27 name=email_other value='$email_other'></td>
	</tr>
	<tr class='bg-odd'>
		<td rowspan=2>Account Name</td>
		<td rowspan=2>
			<table><tr>
			<td>
				<input type=text readonly=yes size=27 name=accountname value='$accountname'>
				<input type=hidden name=account_id value='$account_id'>
				<input type=hidden name=account_type value='$account_type'>
			</td>
			<td align=center>
				<input type=button value='Customer' onClick='popupSized(\"customers-view.php?action=contact_acc\", \"contactacc\", 700, 450, \"\");'><br>
				<input type=button value='Supplier' onClick='popupSized(\"supp-view.php?action=contact_acc\", \"contactacc\", 700, 300, \"\");'>
			</td>
			</tr></table>
		</td>

		<td>Assistant</td>
		<td><input type=text size=27 name=assistant value='$assistant'></td>
	</tr>
	<tr class='bg-even'>

		<td>Assistant Phone</td>
		<td><input type=text size=27 name=assistant_phone value='$assistant_phone'></td>
	</tr>

	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<th colspan=2>Physical Address</th>
		<th colspan=2>Postal Address</th>
	</tr>
	<tr class='bg-even'>
		<td colspan=2 align=center><textarea name=hadd rows=4 cols=35>$hadd</textarea></td>
		
		<td colspan=2 align=center><textarea name=padd rows=4 cols=35>$padd</textarea></td>
	</tr>
	<tr class='bg-odd'>
		<td>City</td>
		<td><input type=text size=27 name=padd_city value='$padd_city'></td>
		<td>City</td>
		<td><input type=text size=27 name=hadd_city value='$hadd_city'></td>
	</tr>
	<tr class='bg-even'>
		<td>State/Province</td>
		<td><input type=text size=27 name=padd_state value='$padd_state'></td>
		<td>State/Province</td>
		<td><input type=text size=27 name=hadd_state value='$hadd_state'></td>
	</tr>
	<tr class='bg-odd'>
		<td>Postal Code</td>
		<td><input type=text size=27 name=padd_code value='$padd_code'></td>
		<td>Postal Code</td>
		<td><input type=text size=27 name=hadd_code value='$hadd_code'></td>
	</tr>
	<tr class='bg-even'>
		<td>Country</td>
		<td><input type=text size=27 name=padd_country value='$padd_country'></td>
		<td>Country</td>
		<td><input type=text size=27 name=hadd_country value='$hadd_country'></td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>

	<tr>
		<th colspan=2>Notes</th>
	</tr>
	<tr class='bg-odd'>
		<td colspan=2 align=center><textarea name=description rows=4 cols=35>$description</textarea></td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-even'>
		<td>Private</td>
		<td align=center>$Cons</td>
	</tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='list_cons.php'>List contacts</a></td></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $get_data;
}

# confirm new data
function con_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk($id, "num", 1, 9, "ID Field (hidden)");
	$v->isOk($surname, "string", 1, 100, "Last name");
	$v->isOk($name, "string", 0, 100, "First name");
	$v->isOk($accountname, "string", 0, 100, "Account");
	$v->isOk($account_id, "num", 0, 9, "Account ID (hidden)");
	$v->isOk($account_type, "string", 0, 100, "Account type (hidden)");
	$v->isOk($reports_to, "string", 0, 100, "Reports to");
	$v->isOk($reports_to_id, "num",0, 9, "Reports to ID (hidden)");
	$v->isOk($lead_source, "string", 0, 100, "Lead Source");
	$v->isOk($title, "string", 0, 100, "Title");
	$v->isOk($department, "string", 0, 100, "Department");
	$v->isOk($tell, "string", 0, 100, "Home Phone");
	$v->isOk($cell, "string", 0, 100, "Mobile Phone");
	$v->isOk($fax, "string", 0, 100, "Fax");
	$v->isOk($tell_office, "string", 0, 100, "Office Phone");
	$v->isOk($tell_other, "string", 0, 100, "Other Phone");
	$v->isOk($email, "string", 0, 100, "Email");
	$v->isOk($email_other, "string", 0, 100, "Other Email");
	$v->isOk($assistant, "string", 0, 100, "Assistant");
	$v->isOk($assistant_phone, "string", 0, 100, "Assistant Phone");
	$v->isOk($padd, "string", 0, 100, "Physical Address");
	$v->isOk($padd_city, "string", 0, 100, "Physical Address: City");
	$v->isOk($padd_state, "string", 0, 100, "Physical Address: State/Province");
	$v->isOk($padd_code, "string", 0, 100, "Physical Address: Postal Code");
	$v->isOk($padd_country, "string", 0, 100, "Physical Address: Country");
	$v->isOk($hadd, "string", 0, 100, "Postal Address");
	$v->isOk($hadd_city, "string", 0, 100, "Postal Address: City");
	$v->isOk($hadd_state, "string", 0, 100, "Postal Address: State/Province");
	$v->isOk($hadd_code, "string", 0, 100, "Postal Address: Postal Code");
	$v->isOk($hadd_country, "string", 0, 100, "Postal Address: Country");
	$v->isOk($description, "string", 0, 100, "Notes");
        $v->isOk($Con,"string",2 ,3, "Invalid private.");

        $birthdate = "$bf_year-$bf_month-$bf_day";
	if ( $v->isOk($birthdate, "string", 1, 100, "Birthdate") ) {
		if ( ! checkdate($bf_month, $bf_day, $bf_year) ) {
			$v->addError("_OTHER", "Invalid birthdate. No such date exists.");
		}
	}

	if ( $bf_year >= 1971) {
		$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));
	} else {
		$birthdate_description = "";
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "The following field value errors occured:<br>";

		$errors = $v->getErrors();

		foreach ($errors as $e) {
			if ( $e["value"] == "_OTHER" )
				$err .= "<li class=err>$e[msg]</li>";
			else
				$err .= "<li class=err>Invalid characters: $e[msg]</li>";
		}
		return get_data($err);
	}

	$con_data = "<h3>Confirm contact details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key      value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name='surname' value='$surname'>
	<input type=hidden name='name' value='$name'>
	<input type=hidden name='account_id' value='$account_id'>
	<input type=hidden name='account_type' value='$account_type'>
	<input type=hidden name='lead_source' value='$lead_source'>
	<input type=hidden name='title' value='$title'>
	<input type=hidden name='department' value='$department'>
	<input type=hidden name='bf_day' value='$bf_day'>
	<input type=hidden name='bf_month' value='$bf_month'>
	<input type=hidden name='bf_year' value='$bf_year'>
	<input type=hidden name='reports_to_id' value='$reports_to_id'>
	<input type=hidden name='tell' value='$tell'>
	<input type=hidden name='cell' value='$cell'>
	<input type=hidden name='fax' value='$fax'>
	<input type=hidden name='tell_office' value='$tell_office'>
	<input type=hidden name='tell_other' value='$tell_other'>
	<input type=hidden name='email' value='$email'>
	<input type=hidden name='email_other' value='$email_other'>
	<input type=hidden name='assistant' value='$assistant'>
	<input type=hidden name='assistant_phone' value='$assistant_phone'>
	<input type=hidden name='padd' value='$padd'>
	<input type=hidden name='padd_city' value='$padd_city'>
	<input type=hidden name='padd_state' value='$padd_state'>
	<input type=hidden name='padd_code' value='$padd_code'>
	<input type=hidden name='padd_country' value='$padd_country'>
	<input type=hidden name='hadd' value='$hadd'>
	<input type=hidden name='hadd_city' value='$hadd_city'>
	<input type=hidden name='hadd_state' value='$hadd_state'>
	<input type=hidden name='hadd_code' value='$hadd_code'>
	<input type=hidden name='hadd_country' value='$hadd_country'>
	<input type=hidden name='description' value='$description'>
	<input type=hidden name='Con' value='$Con'>
	<tr><th colspan=4>Contact Information</th></tr>
	<tr class='bg-even'>
		<td width=120>First Name</td>
		<td width=210>$name</td>

		<td width=120>Office Phone</td>
		<td width=210>$tell_office</td>
	</tr>
	<tr class='bg-odd'>
		<td>Company/Last Name</td>
		<td>$surname</td>

		<td>Mobile</td>
		<td>$cell</td>
	</tr>
	<tr class='bg-even'>
		<td>Account Name</td>
		<td>$accountname</td>

		<td>Home Phone</td>
		<td>$tell</td>
	</tr>
	<tr class='bg-odd'>
		<td>Lead Source</td>
		<td>".crm_get_leadsrc($lead_source)."</td>

		<td>Other Phone</td>
		<td>$tell_other</td>
	</tr>
	<tr class='bg-even'>
		<td>Title</td>
		<td>$title</td>

		<td>Fax</td>
		<td>$fax</td>
	</tr>
	<tr class='bg-odd'>
		<td>Department</td>
		<td>$department</td>

		<td>E-mail</td>
		<td>$email</td>
	</tr>
	<tr class='bg-even'>
		<td>Birthdate</td>
		<td>$birthdate_description</td>

		<td>Other E-mail</td>
		<td>$email_other</td>
	</tr>
	<tr class='bg-odd'>
		<td>Reports To</td>
		<td>$reports_to</td>

		<td>Assistant</td>
		<td>$assistant</td>
	</tr>
	<tr class='bg-even'>
		<td>&nbsp;</td>
		<td>&nbsp;</td>

		<td>Assistant Phone</td>
		<td>$assistant_phone</td>
	</tr>

	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<th colspan=2>Physical Address</th>
		<th colspan=2>Postal Address</th>
	</tr>
	<tr class='bg-even'>
		<td colspan=2 align=left valign=top><xmp>$hadd</xmp></td>
		
		<td colspan=2 align=left><xmp>$padd</xmp></td>
	</tr>
	<tr class='bg-odd'>
		<td>City</td>
		<td>$padd_city</td>
		<td>City</td>
		<td>$hadd_city</td>
	</tr>
	<tr class='bg-even'>
		<td>State/Province</td>
		<td>$padd_state</td>
		<td>State/Province</td>
		<td>$hadd_state</td>
	</tr>
	<tr class='bg-odd'>
		<td>Postal Code</td>
		<td>$padd_code</td>
		<td>Postal Code</td>
		<td>$hadd_code</td>
	</tr>
	<tr class='bg-even'>
		<td>Country</td>
		<td>$padd_country</td>
		<td>Country</td>
		<td>$hadd_country</td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<th colspan=2>Notes</th>
	</tr>
	<tr class='bg-odd'>
		<td colspan=2 align=left><xmp>$description</xmp></td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>

	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-even'>
		<td>Private</td>
		<td align=center>$Con</td>
	</tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='list_cons.php'>List contacts</a></td></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";
        return $con_data;
}
# write new data
function write_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk($id, "num", 1, 9, "ID Field (hidden)");
	$v->isOk($surname, "string", 1, 100, "Last name");
	$v->isOk($name, "string", 0, 100, "First name");
	$v->isOk($account_id, "num", 0, 9, "Account ID (hidden)");
	$v->isOk($account_type, "string", 0, 100, "Account type (hidden)");
	$v->isOk($reports_to_id, "num",0, 9, "Reports to ID (hidden)");
	$v->isOk($lead_source, "string", 0, 100, "Lead Source");
	$v->isOk($title, "string", 0, 100, "Title");
	$v->isOk($department, "string", 0, 100, "Department");
	$v->isOk($tell, "string", 0, 100, "Home Phone");
	$v->isOk($cell, "string", 0, 100, "Mobile Phone");
	$v->isOk($fax, "string", 0, 100, "Fax");
	$v->isOk($tell_office, "string", 0, 100, "Office Phone");
	$v->isOk($tell_other, "string", 0, 100, "Other Phone");
	$v->isOk($email, "string", 0, 100, "Email");
	$v->isOk($email_other, "string", 0, 100, "Other Email");
	$v->isOk($assistant, "string", 0, 100, "Assistant");
	$v->isOk($assistant_phone, "string", 0, 100, "Assistant Phone");
	$v->isOk($padd, "string", 0, 100, "Physical Address");
	$v->isOk($padd_city, "string", 0, 100, "Physical Address: City");
	$v->isOk($padd_state, "string", 0, 100, "Physical Address: State/Province");
	$v->isOk($padd_code, "string", 0, 100, "Physical Address: Postal Code");
	$v->isOk($padd_country, "string", 0, 100, "Physical Address: Country");
	$v->isOk($hadd, "string", 0, 100, "Postal Address");
	$v->isOk($hadd_city, "string", 0, 100, "Postal Address: City");
	$v->isOk($hadd_state, "string", 0, 100, "Postal Address: State/Province");
	$v->isOk($hadd_code, "string", 0, 100, "Postal Address: Postal Code");
	$v->isOk($hadd_country, "string", 0, 100, "Postal Address: Country");
	$v->isOk($description, "string", 0, 100, "Notes");
        $v->isOk($Con,"string",2 ,3, "Invalid private.");

        $birthdate = "$bf_year-$bf_month-$bf_day";
	if ( $v->isOk($birthdate, "string", 1, 100, "Birthdate") ) {
		if ( ! checkdate($bf_month, $bf_day, $bf_year) ) {
			$v->addError("_OTHER", "Invalid birthdate. No such date exists.");
		}
	}

	if ( $bf_year >= 1971) {
		$birthdate_description = date("d F Y", mktime(0, 0, 0, $bf_day, $bf_month, $bf_year));
	} else {
		$birthdate_description = "";
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "The following field value errors occured:<br>";

		$errors = $v->getErrors();

		foreach ($errors as $e) {
			if ( $e["value"] == "_OTHER" )
				$err .= "<li class=err>$e[msg]</li>";
			else
				$err .= "<li class=err>Invalid characters: $e[msg]</li>";
		}
		return get_data($err);
	}

	db_conn('cubit');

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class=err>Unable to edit contact(TB)</li>";
	}

	$Sl="SELECT * FROM cons WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get contact details.");

	if(pg_num_rows($Ry)<1) {
		return "Invalid contact.";
	}

	$cdata=pg_fetch_array($Ry);

	// reports to name
	$reports_to = "";
	if ( ! empty($reports_to_id) ) {
		$reports_to_id += 0;
		
		db_conn("cubit");
		$sql = "SELECT * FROM cons WHERE id='$reports_to_id' LIMIT 1";
		$rslt = db_exec($sql) or errDie("Error retrieving 'Reports to' value.");

		$dat = pg_fetch_array($rslt);
		
		if ( ! empty($dat["name"]) ) {
			$reports_to .= "$dat[name] ";
		}

		$reports_to .= "$dat[surname]";
	}

	if ( $account_type == "Customer" ) {
		db_conn("cubit");
		$sql = "SELECT surname FROM customers WHERE cusnum='$account_id'";
		$rslt = db_exec($sql) or errDie("Error reading account name (customers)");

		if ( pg_num_rows($rslt) > 0 ) {
			$accountname = pg_fetch_result($rslt, 0, 0);
		} else {
			$account_id = 0;
			$accountname = "";
			$account_type = "";
		}
	} else if ( $account_type == "Supplier" ) {
		db_conn("cubit");
		$sql = "SELECT supname FROM suppliers WHERE supid='$account_id'";
		$rslt = db_exec($sql) or errDie("Error reading account name (suppliers)");

		if ( pg_num_rows($rslt) > 0 ) {
			$accountname = pg_fetch_result($rslt, 0, 0);
		} else {
			$account_id = 0;
			$accountname = "";
			$account_type = "";
		}
	} else {
		$accountname = "";
	}
	
	# write to db
	$Sql = "UPDATE cons SET surname='$surname', name='$name', accountname='$accountname',
			account_id='$account_id', account_type='$account_type',
			lead_source='$lead_source', title='$title', department='$department',
			birthdate='$birthdate', reports_to='$reports_to',
			reports_to_id='$reports_to_id', tell='$tell', cell='$cell', fax='$fax',
			tell_office='$tell_office', tell_other='$tell_other', email='$email',
			email_other='$email_other', assistant='$assistant',
			assistant_phone='$assistant_phone', padd='$padd', padd_city='$padd_city',
			padd_state='$padd_state', padd_code='$padd_code',
			padd_country='$padd_country', hadd='$hadd', hadd_city='$hadd_city',
			hadd_state='$hadd_state', hadd_code='$hadd_code',
			hadd_country='$hadd_country', description='$description', con='$Con'
		WHERE id='$id'";

	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Rslt);

	if($cdata['supp_id']!=0) {
		$Sl="UPDATE suppliers SET supname='$surname',tel='$tell',fax='$fax',email='$email',supaddr='$padd \n $hadd' WHERE supid='$cdata[supp_id]'";
		$Ry=db_exec($Sl) or errDie("Unable to update supplier.");
	}

	if($cdata['cust_id']!=0) {
		$Sl="UPDATE customers SET surname='$surname',tel='$tell',fax='$fax',email='$email',paddr1='$padd',addr1='$hadd' WHERE cusnum='$cdata[cust_id]'";
		$Ry=db_exec($Sl) or errDie("Unable to update customers.");
	}

	if (!pglib_transaction("COMMIT")) {
		return "<li class=err>Unable to edit contact. (TC)</li>";
	}

	$write_data =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Contact modified</th></tr>
	<tr class=datacell><td>$surname has been modified.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='list_cons.php'>List contacts</a></td></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";
	return $write_data;
}
?>

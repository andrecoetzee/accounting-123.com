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

// Decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "display":
			$OUTPUT = display();
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = display();
}

// Append quick links to each page
$OUTPUT .= "
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				<tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='supp-group-add.php'>Add Supplier Groups</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='supp-new.php'>Add Supplier<a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

require ("template.php");




function display($error="")
{

	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);
	
	// Get the group names
	db_conn("cubit");
	$sql = "SELECT * FROM supp_groups WHERE id!='0' ORDER BY groupname ASC";
	$grpRslt = db_exec($sql) or errDie("Unable to retrieve supplier groups from Cubit.");

	// See if we actually got any groups, and decide if we should display the remove button
	if (pg_num_rows($grpRslt) == 0) {
		$blocks = "<li class='err'>No supplier groups found! First add supplier groups to use this feature.</li>";
		$remove_btn = "";
	} else {
		$blocks = "";
		$remove_btn = "<input type='submit' name='remove' value='Remove Selected &raquo'>";
	}
	while ($grpData = pg_fetch_array($grpRslt)) {
		// Retrieve list of suppliers for the dropdown
		db_conn("cubit");
		$sql = "SELECT * FROM suppliers WHERE div='".USER_DIV."' ORDER BY supname ASC";
		$suppdnRslt = db_exec($sql) or errDie("Unable to retrieve list of suppliers from Cubit.");

		// Create the dropdown
		$suppdn = "<th nowrap><select name=suppid[$grpData[id]] style='width: 180px'>";
		while ($suppdnData = pg_fetch_array($suppdnRslt)) {
			db_conn("cubit");
			$sql = "SELECT * FROM supp_grpowners WHERE supid='$suppdnData[supid]' and grpid='$grpData[id]'";
			$rslt = db_exec($sql) or errDie("Unable to check if user is already in group");

			if (pg_num_rows($rslt) == 0) {
				$suppdn .= "<option value='$suppdnData[supid]'>$suppdnData[supname]</option>";
			}
		}
		$suppdn .= "</select><input type='submit' name='add[$grpData[id]]' value='Add &raquo'></th>";

		// See if suppdn is blank
		if (preg_match('/<select[^>]*>[\s]*<\/select>/', $suppdn)) {
			$suppdn = "";
		}

		// See if the group has been checked
		$checked = "";
		if (isset($remgrp)) {
			foreach ($remgrp as $key => $val) {
				if ($grpData["id"] == $key) {
					$checked = "checked";
				}
			}
		}
		
		// Group tables
		$blocks .= "<table ".TMPL_tblDflts." style='width: 400px'>
		  <tr>
		    <th style='width:20px' align=left><input type=checkbox name=remgrp[$grpData[id]] $checked></th>
		    <th style='width:200px' align=left>$grpData[groupname]</th>
		    $suppdn
		  </tr>";

		// Get the list of group owners
		db_conn("cubit");
		$sql = "SELECT * FROM supp_grpowners WHERE grpid='$grpData[id]'";
		$grpownRslt = db_exec($sql) or errDie("Unable to retrieve group owners from Cubit.");

		// Check if we got any suppliers in the current group
		if (pg_num_rows($grpownRslt) == 0) {
			$blocks .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='3'>There are no suppliers in this group.</th>
						</tr>";
		}

		$i = 0;
		while ($grpownData = pg_fetch_array($grpownRslt)) {
			// Get the name of the supplier
			$sql = "SELECT supname FROM suppliers WHERE supid='$grpownData[supid]'";
			$suppRslt = db_exec($sql) or errDie("Unable to get supplier name from Cubit.");
			$supname = pg_fetch_result($suppRslt, 0);

			// Draw the cells for the current group's table
			$i++;

			// See if we already got something checked
			$checked = "";
			if (isset($remid)) {
				foreach ($remid as $key=>$val) {
					if ("$grpownData[supid]:$grpownData[grpid]" == $key) {
						$checked = "checked";
					}
				}
			}
				
			$blocks .= "
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='checkbox' name='remid[$grpownData[supid]:$grpownData[grpid]]' $checked></td>
								<td colspan='2'>$supname</td>
							</tr>";
		}
		$blocks .= "</table>
		  <p>";
	}

	// Main layout
	$OUTPUT = "
				<h3>View Supplier Groups</h3>
				$error
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='confirm'>
					$blocks
					$remove_btn
				</form>";
	return $OUTPUT;

}



function confirm($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);
	
	// Validate
	require_lib("validate");
	$v = new validate;
	if (isset($suppid)) {
		foreach ($suppid as $key => $val) {
			$v->isOk($val, "num", 0, 9, "Invalid supplier id");
		}
	}
	if (isset($remgrp)) {
		foreach ($remgrp as $key => $val) {
			$v->isOk($val, "string", 0, 2, "Invalid group to be removed");
		}
	}
	if (isset($remid)) {
		foreach ($remid as $key=> $val) {
			$v->isOk($val, "string", 0, 2, "Invalid supplier to be removed");
		}
	}
	// See if we actually got anything before we proceed
	if (!isset($suppid) && !isset($remgrp) && !isset($remid)) {
		$v->addError(0, "Nothing selected to be removed");
	}
	// Display errors if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return display($confirm);
	}

	// Add suppliers to group
	if (isset($add)) {
		foreach ($add as $key => $val) {
			// See if the supplier is already in this group
			db_conn("cubit");
			$sql = "SELECT * FROM supp_grpowners WHERE grpid='$key' AND supid='$suppid[$key]'";
			$checkRslt = db_exec($sql) or errDie("Unable to validate if user is in the group.");

			if (pg_num_rows($checkRslt) == 0) {
				db_conn("cubit");
				$sql = "INSERT INTO supp_grpowners (grpid, supid) VALUES ('$key', '$suppid[$key]')";
				$addRslt = db_exec($sql) or errDie("Unable to add supplier into group");
				return display();
			} else {
				return display("<li class='err'>Supplier is already in that group.</li>");
			}
		}
	}
	// Remove suppliers from group
	if (isset($remove)) {
		$supps = $groups = "";
		$bgcolor = $i = 0;

		// Remove this table if empty
		$empty = true;
		if (isset($remid)) {
			foreach ($remid as $key => $val) {
				$keys = explode(":",$key);
				if (!isset($remgrp[$keys[1]])) {
					$empty = false;
				}
			}
		}
		// List individual suppliers to be removed
		if (isset($remid) && $empty == false) {
			$supps = "
			<h3>Suppliers to be removed from groups</h3>
			<table ".TMPL_tblDflts." width='400'>
	    	  <tr>
	        	<th>Suppliers</th>
	        	<th>Remove from group</th>
	      	</tr>";

			foreach ($remid as $key => $val) {
				$keys = explode(":",$key);
			
				// Get the group id
				$sql = "SELECT grpid FROM supp_grpowners WHERE supid='$keys[0]'";
				$grpidRslt = db_exec($sql) or errDie("Unable to retrieve group id from Cubit.");
				$grpid = pg_fetch_result($grpidRslt, 0);

				// Get the supplier name
				$sql = "SELECT supname FROM suppliers WHERE supid='$keys[0]'";
				$suppnameRslt = db_exec($sql) or errDie("Unable to retrieve supplier name from Cubit.");
				$supname = pg_fetch_result($suppnameRslt, 0);

				// Get the group name
				$sql = "SELECT groupname FROM supp_groups WHERE id='$keys[1]'";
				$groupnameRslt = db_exec($sql) or errDie("Unable to retrieve group name from Cubit.");
				$groupname = pg_fetch_result($groupnameRslt, 0);

				// If the entire group is to be removed, don't show the
				// suppliers that may have been selected in THAT group.
				if (!isset($remgrp[$keys[1]])) {
					$supps .= "
								<tr bgcolor='".bgcolorg()."'>
									<td align='center'>$supname</td>
									<td align='center'>$groupname</td>
								</tr>";
				}
			}
		}
		// List entire groups to be removed
		if (isset($remgrp)) {
			$groups = "
						<h3>Entire Groups to be removed</h3>
						<table ".TMPL_tblDflts." width='400'>
							<tr>
								<th>Group</th>
							</tr>";
	    
			foreach ($remgrp as $key => $val) {
				$sql = "SELECT groupname FROM supp_groups WHERE id='$key'";
				$grpRslt = db_exec($sql) or errDie("Unable to retrieve groups from Cubit.");
				$groupname = pg_fetch_result($grpRslt, 0);

				$groups .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$groupname</td>
							</tr>";
			}
		}
		
	}
	$supps .= "</table>";
	$groups .= "</table>";

	$hidden = "";
	// Create hidden fields
	if (isset($remid)) {
		foreach ($remid as $key=>$val) {
			$hidden .= "<input type=hidden name=remid[$key] value='$val'>";
		}
	}
	if (isset($remgrp)) {
		foreach ($remgrp as $key=>$val) {
			$hidden .= "<input type=hidden name=remgrp[$key] value='$val'>";
		}
	}
	if (isset($suppid)) {
		foreach ($suppid as $key=>$val) {
			$hidden .= "<input type=hidden name=suppid[$key] value='$val'>";
		}
	}
	
	$OUTPUT = "<form method=post action='".SELF."'>
	<input type=hidden name=key value='write'>
	$hidden
	$supps
	<p>
	$groups
	<p>
	</table>
	<input type=submit name=key value='&laquo Correction'>
	<input type=submit value='Remove &raquo'>
	</form>";

	return $OUTPUT;
}

function write($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);
	
	// Validate
	require_lib("validate");
	$v = new validate;
	if (isset($suppid)) {
		foreach ($suppid as $key => $val) {
			$v->isOk($val, "num", 0, 9, "Invalid supplier id");
		}
	}
	if (isset($remgrp)) {
		foreach ($remgrp as $key => $val) {
			$v->isOk($val, "string", 0, 2, "Invalid group to be removed");
		}
	}
	if (isset($remid)) {
		foreach ($remid as $key=> $val) {
			$v->isOk($val, "string", 0, 2, "Invalid supplier to be removed");
		}
	}
	// Display errors if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return display($confirm);
	}

	$OUTPUT = "<form method=post action='".SELF."'>";
	if (isset($remid)) {
		foreach ($remid as $key=>$val) {
			$keys = explode(":",$key);
			if (!isset($remgrp[$keys[1]])) {
				$sql = "DELETE FROM supp_grpowners WHERE supid='$keys[0]' AND grpid='$keys[1]'";
				$rslt = db_exec($sql) or errDie("Unable to remove suppliers from groups");
			}
		}
		$OUTPUT .= "<li>Suppliers has been successfully removed from groups.</li>";
	}
	if (isset($remgrp)) {
		foreach ($remgrp as $key=>$val) {
			$sql = "DELETE FROM supp_groups WHERE id='$key'";
			$rslt = db_exec($sql) or errDie("Unable to remove group from Cubit.");

			$sql = "DELETE FROM supp_grpowners WHERE grpid='$key'";
			$rslt = db_exec($sql) or errDie("Unable to clear group ownerships from Cubit.");
		}
		$OUTPUT .= "<li>Supplier groups have been successfully removed from Cubit.</li>";
	}
	
	$OUTPUT .= "<p>	<input type=submit name=key value='&laquo View Supplier Groups'>
	</form>";
	
	return $OUTPUT;
}
?>
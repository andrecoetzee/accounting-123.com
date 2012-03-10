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
require("../settings.php");
//require("core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($HTTP_GET_VARS["cat"]) && isset($HTTP_GET_VARS["action"])) {
	switch ($HTTP_GET_VARS["action"]){
		case "add":
			$OUTPUT = add_item ($HTTP_GET_VARS);
			break;
		case "view":
			$OUTPUT = view_item ($HTTP_GET_VARS);
			break;
		default:
			$OUTPUT = "Invalid use of module";
	}
}elseif(isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]) {
		case "add":
			$OUTPUT = write_add_item ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid option selected.";
	}
}else {
	$OUTPUT = cat_list ();
}

# get templete
require("../template.php");



# Default view
function cat_list ()
{

	$display = "
			<h2>Auditor recording section</h2>
			<table ".TMPL_tblDflts." width='400'>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Complex/unusual transactions</li></td>
					<td><a href='auditor_record.php?cat=1&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=1&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Significant estimates</li></td>
					<td><a href='auditor_record.php?cat=2&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=2&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Period-end adjustments</li></td>
					<td><a href='auditor_record.php?cat=3&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=3&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Past misstatements</li></td>
					<td><a href='auditor_record.php?cat=4&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=4&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Unreconciled differences</li></td>
					<td><a href='auditor_record.php?cat=5&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=5&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Information or Adjustments provided late in audit</li></td>
					<td><a href='auditor_record.php?cat=6&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=6&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Items not in line with expectations</li></td>
					<td><a href='auditor_record.php?cat=7&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=7&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Unneccessary complexity</li></td>
					<td><a href='auditor_record.php?cat=8&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=8&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Management overrides</li></td>
					<td><a href='auditor_record.php?cat=9&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=9&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Revenue recognition</li></td>
					<td><a href='auditor_record.php?cat=10&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=10&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Conflicting or missing evidence</li></td>
					<td><a href='auditor_record.php?cat=11&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=11&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Unsupported transactions</li></td>
					<td><a href='auditor_record.php?cat=12&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=12&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Frequent changes in accounting estimates</li></td>
					<td><a href='auditor_record.php?cat=13&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=13&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Strange accounting policies</li></td>
					<td><a href='auditor_record.php?cat=14&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=14&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Identified fraud opportunities</li></td>
					<td><a href='auditor_record.php?cat=15&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=15&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Non reconciling suspense accounts</li></td>
					<td><a href='auditor_record.php?cat=16&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=16&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Reconciliation variations (Bank, Debtor, Creditor)</li></td>
					<td><a href='auditor_record.php?cat=17&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=17&action=view'>View</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><li>Incomplete or late transactions</li></td>
					<td><a href='auditor_record.php?cat=18&action=add'>Add</a></td>
					<td><a href='auditor_record.php?cat=18&action=view'>View</a></td>
				</tr>
			</table>
			<br>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr><th>Quick Links</th></tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>
		";
	return $display;

}


function add_item ()
{

	global $HTTP_GET_VARS;
	extract ($HTTP_GET_VARS);

	if(!isset($action))
		return "Invalid action";

	if(!isset($cat))
		return "Invalid entry";

	$display = "
			<h2>Add report</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='400'>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='add'>
				<input type='hidden' name='cat' value='$cat'>
				<tr>
					<td colspan='2'><textarea name='detail' cols='60' rows='12'></textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='button' onClick='javascript:history.back();' value='<< Correction'></td>
					<td align='right'><input type='submit' value='Add'></td>
				</tr>
			</form>
			</table>
		";
	return $display;

}


function write_add_item ()
{

	global $HTTP_POST_VARS;
	extract ($HTTP_POST_VARS);

	global $HTTP_SESSION_VARS;

	db_connect ();

	$write_sql = "INSERT INTO auditor_report (cat,detail,date_added,user_added)
					VALUES ('$cat','$detail','now','$HTTP_SESSION_VARS[USER_NAME]')";
	$run_write = db_exec($write_sql) or errDie("Unable to add report detail");

	return cat_list();

}


function view_item ()
{

	global $HTTP_GET_VARS;
	extract ($HTTP_GET_VARS);

	if(!isset($action))
		return "Invalid action";

	if(!isset($cat))
		return "Invalid entry";

	#get all entries for this category

	db_connect ();

	$get_info = "SELECT * FROM auditor_report WHERE cat = '$cat' ORDER BY date_added";
	$run_info = db_exec($get_info) or errDie("Unable to get auditor report detail");
	if(pg_numrows($run_info) < 1){
		$listing = "
						<tr>
							<td><li class='err'>No recorded entries found.</li></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='button' onClick='javascript:history.back();' value='Back'></td>
						</tr>";
	}else {
		$listing = "";
		$i = 0;
		while ($arr = pg_fetch_array($run_info)){

			$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>".nl2br($arr['detail'])."</td>
					</tr>
				";

			$i++;
		}
		$listing .= "
				<tr><td><br></td></tr>
				<tr><td><input type='button' onClick=\"javascript:document.location.href='auditor_record.php'\"' value='<< Correction'></td></tr>
			";
	}

	$display = "
			<h2>Auditor Report</h2>
			<table ".TMPL_tblDflts." width='400'>
				$listing
			</table>
		";
	return $display;


}


?>
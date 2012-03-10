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
# decide what to do

if (isset($HTTP_GET_VARS['supid'])){
	$OUTPUT = view ($HTTP_GET_VARS['supid']);
} else {
	$OUTPUT = "<li> - Invalid use of module.</li>";
}

# display output
require ("template.php");




function view($supid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid Supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li> Invalid supplier ID.</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
		# get vars
		foreach ($supp as $key => $value) {
			$$key = $value;
		}
	}

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$deptname = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	db_conn("cubit");
	$sql = "SELECT * FROM supp_groups WHERE id='$groupid'";
	$grpRslt = db_exec($sql);
	$group = pg_fetch_array($grpRslt);

	db_conn('cubit');

	$Sl = "SELECT id FROM cons WHERE supp_id='$supid'";
	$Ry = db_exec($Sl) or errDie("Unable to get contact from db.");

	$i = 0;
	$conpers = "";

	if(pg_num_rows($Ry) > 0) {

		$cdata = pg_fetch_array($Ry);

		$Sl = "SELECT * FROM conpers WHERE con='$cdata[id]' ORDER BY name";
		$Ry = db_exec($Sl) or errDie("Unable to get contacts from db.");

		if(pg_num_rows($Ry) > 0) {

			$conpers = "
				<h3>Contact Persons</h3>
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
				$bgcolor = ($i%2) ? bgcolorg() : bgcolorg();

				$conpers .= "
					<tr bgcolor='$bgcolor'>
						<td>$cp[name]</td>
						<td>$cp[pos]</td>
						<td>$cp[tell]</td>
						<td>$cp[cell]</td>
						<td>$cp[fax]</td>
						<td>$cp[email]</td>
						<td>$cp[notes]</td>
						<td><a href='conper-edit.php?id=$cp[id]&type=edit'>Edit</a></td>
						<td><a href='conper-rem.php?id=$cp[id]'>Delete</a></td>
					</tr>";
			}

			$conpers .= "</table>";
 
		}

	}

	// Retrieve team name
	$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
	$team_name = pg_fetch_result($team_rslt, 0);

	# Layout
	$confirm = "
		<h3>Supplier Details</h3>
		<table cellpadding=0 cellspacing=0>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Supplier Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td>$deptname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier No</td>
							<td>$supno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Name</td>
							<td>$supname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Group</td>
							<td>$group[groupname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td>$branch</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Number</td>
							<td>$vatnum</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Address</td>
							<td><pre>$supaddr</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Postal Address</td>
							<td><pre>$suppostaddr</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Contact Name</td>
							<td>$contname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tel No.</td>
							<td>$tel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Fax No.</td>
							<td>$fax</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cell No.</td>
							<td>$cell</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>E-mail</td>
							<td>$email</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Web Address</td>
							<td>http://$url</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Status BEE</td>
							<td>$bee_status</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Team Permissions</td>
							<td>$team_name</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Comments</td>
							<td>$comments</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						<tr bgcolor='".bgcolorg()."'>
							<th colspan='2'> Bank Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Bank </td>
							<td>$bankname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td>$branname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch Code</td>
							<td>$brancode</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Number</td>
							<td>$bankaccno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference Number</td>
							<td>$reference</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Settlement Discount %</td>
							<td>$setdisc %</td>
						</tr>
						<tr><td><br></td></tr>
						<tr><td>";
		// Documents
//		$sdoc_db = new dbSelect("supplier_docs", "crm", m("where", "supid='$supid'"));
//		$sdoc_db->run();

		$docs_out = "";
//		while ($sdoc_data = $sdoc_db->fetch_array()) {

		db_conn ('crm');

		$get_docs = "SELECT * FROM supplier_docs WHERE supid = '$supid'";
		$run_docs = db_exec($get_docs) or errDie ("Unable to get supplier document information.");
		while ($sdoc_data = pg_fetch_array ($run_docs)){

			if (strlen($sdoc_data['filename']) > 0){
				$showdoc = "$sdoc_data[filename]";
			}elseif (strlen($sdoc_data['real_filename']) > 0){
				$showdoc = "$sdoc_data[real_filename]";
			}else {
				$showdoc = "File".$sdoc_data["id"];
			}

			$docs_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='supp_doc_get.php?id=$sdoc_data[id]'>$showdoc</a></td>
					<td>".getFileSize($sdoc_data["size"])."</td>
				</tr>";
		}

		$confirm .= "
				</tr>
			</td>
			<tr>
				<td colspan='2'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Documents</th>
						</tr>
						<tr>
							<th>Filename</th>
							<th>Size</th>
						</tr>
						$docs_out
					</table>
				</td>
			</tr>";

		$confirm .="
				</table>
					<tr>
						<td colspan='2' align='right'>
							<table border=0 cellpadding='2' cellspacing='1'>
								<tr>
									<th>Quick Links</th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='supp-view.php'>View Suppliers</a></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='main.php'>Main Menu</a></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				</form>
			</td>
		</tr>
	</table>
	$conpers";
	return $confirm;

}



?>

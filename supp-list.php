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
	$OUTPUT .= printSupp();
} else {
	# show current stock
	$OUTPUT = printSupp ();
}

$OUTPUT .= "<br>".
			mkQuickLinks(
				ql("supp-new.php","Add Supplier")
			);

require ("template.php");



# show stock
function printSupp ()
{
	# Set up table to display in
	$printSupp = "
					<center>
					<h3>Current Suppliers</h3>
					<table border='1' cellpadding='3' cellspacing='0'>
						<tr>
							<th>Department</th>
							<th>Supplier no.</th>
							<th>Supplier Name</th>
							<th>Contact Name</th>
							<th>Tel No.</th>
							<th>Fax No.</th>
							<th>Balance</th>
						</tr>";

	# connect to database
	db_connect();

	# Query server
	$i = 0;
	$tot=0;
    $sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' OR ddiv = '".USER_DIV."' ORDER BY supid ASC";
    $suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class='err'>There are no Suppliers in Cubit.</li>";
	}

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

		$tot = $tot + $supp['balance'];

		$printSupp .= "
						<tr>
							<td>$deptname</td>
							<td>$supp[supno]</td>
							<td align='center'>$supp[supname]</td>
							<td>$supp[contname]&nbsp;</td>
							<td>$supp[tel]&nbsp;</td>
							<td>$supp[fax]&nbsp;</td>
							<td align='right'>".CUR." $supp[balance]</td>
						</tr>";

		$i++;
	}

	 if ($i>1){$s="s";} else {$s="";}
        $tot=sprint($tot);
        $printSupp .= "
        				<tr>
        					<td colspan='8'><br></td>
        				</tr>
						<tr>
							<td colspan='6'>Total Amount Owed, to $i supplier$s </td>
							<td align='right'>".CUR." $tot</td>
						</tr>";

	$printSupp .= "</table>";

	$OUTPUT = $printSupp;
	require("tmpl-print.php");
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
		return "<li class='err'>Unable to add supplier to contact list.</li>";
	}

}


?>
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

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = con_data ($_POST);
			break;
		default:
			$OUTPUT = view_data ($_GET);
	}
} else {
	$OUTPUT = view_data ($_GET);
}

# Display output
require ("template.php");

# enter new data
function view_data ($_GET)
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
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');
	$user =USER_NAME;
	$Sql = "SELECT * FROM assets WHERE (id = '$id' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){return "Asset not Found";}
	$set = pg_fetch_array($Rslt);

	# Get group
	db_connect();
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$set[grpid]' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql);
	$grp = pg_fetch_array($grpRslt);

	# Get Transactions
	db_connect();
	$sql = "SELECT * FROM assetledger WHERE assetid = '$id'";
	$tranRslt = db_exec($sql);
	$trans = "";
	while($tran = pg_fetch_array($tranRslt)){

		# Format date
		$tran['date'] = explode("-", $tran['date']);
		$tran['date'] = $tran['date'][2]."-".$tran['date'][1]."-".$tran['date'][0];
		$tran['depamt'] = sprint($tran['depamt']);
		$tran['netval'] = sprint($tran['netval']);

		$trans .= "
					<tr class='".bg_class()."'>
						<td>$tran[date]</td>
						<td align='right'>".CUR." $tran[depamt]</td>
						<td align='right'>".CUR." $tran[netval]</td>
					</tr>";
	}

	$view_data = "
				<h3>Depreciation Report</h3>
				<table ".TMPL_tblDflts.">
					<tr valign='top'>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'>Asset Details</th>
								</tr>
								<tr class='".bg_class()."'>
									<td>Group</td>
									<td>$grp[grpname]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Description</td>
									<td>$set[des]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Date Bought</td>
									<td>$set[bdate]</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Cost Amount</td>
									<td>$set[amount]</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<br>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Date</th>
						<th>Accum Depreciation</th>
						<th>Net Value</th>
					</tr>
					$trans
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='asset-view.php'>View Assets</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";

	return $view_data;
}

# confirm new data
function con_data ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($id,"num",0 ,100, "Invalid number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');
	$Sql = "DELETE FROM assets WHERE id='$id' AND div = '".USER_DIV."'";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");

	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Asset Removed</th>
					</tr>
					<tr class='datacell'>
						<td>Asset has been deleted from the system.</td>
					</tr>
				</table>
				<p>
				<table border='0' cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='asset-new.php'>New Asset</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='asset-view.php'>View Assets</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
	return $write;

}


?>
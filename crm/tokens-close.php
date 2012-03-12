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
require("settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "delete":
			$OUTPUT = remove($_POST);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = confirm($_GET);
}

$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tokens-new.php'>Add Query</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tokens-manage.php'>Manage Queries</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function confirm($_GET) {

	extract($_GET);
	$id+=0;
	
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}
	
	$data=pg_fetch_array($Ry);

	$out="<h3>Close query: $id</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='delete'>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Confirm query to close</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>No</td><td>$id</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>$data[csct] name</td><td>$data[name]</td></tr>
	<tr><th colspan=2>Query Notes</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><pre>$data[notes]</pre></td></tr>
	<tr><td colspan=2><input type=submit value='Remove &raquo;'></td></tr>
	</form>
	</table>";
	
	return $out;

}

function remove ($_POST) {
	extract($_POST);
	
	$id+=0;
	
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}
	
	$data=pg_fetch_array($Ry);
	
	$Sl="SELECT * FROM token_actions WHERE token='$id'";
	$Rs=db_exec($Sl) or errDie("Unable to get data from system.");

	$Sl="SELECT * FROM archived_actions WHERE token='$id'";
	$Ri=db_exec($Sl) or errDie("Unable to get data from system.");
	
	$Sl="INSERT INTO closedtokens (tid,userid,username,teamid,cat,catid,openby,opendate,lastdate,csct,csc,name,accnum,
	con,tel,cell,fax,email,address,sub,notes,closedate,closeby,closebyid)
	VALUES ('$id','$data[userid]','$data[username]','$data[teamid]','$data[cat]','$data[catid]','$data[openby]',
	'$data[opendate]','$data[lastdate]','$data[csct]','$data[csc]','$data[name]','$data[accnum]','$data[con]',
	'$data[tel]','$data[cell]','$data[fax]','$data[email]','$data[address]','$data[sub]','$data[notes]',
	'".date("Y-m-d")."','".USER_NAME."','".USER_ID."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert query record.");

	while($adata=pg_fetch_array($Rs)) {
		$Sl="INSERT INTO closed_token_actions (token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','$adata[action]','$adata[donedate]','$adata[donetime]','$adata[doneby]','$adata[donebyid]')";
		$Ry=db_exec($Sl) or errDie("Unable to insert token action.");
	}

	while($adata=pg_fetch_array($Ri)) {
		$Sl="INSERT INTO closed_token_actions (token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','$adata[action]','$adata[donedate]','$adata[donetime]','$adata[doneby]','$adata[donebyid]')";
		$Ry=db_exec($Sl) or errDie("Unable to insert token action(archived).");
	}

	$Sl="DELETE FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to remove query.");

	$Sl="DELETE FROM token_actions WHERE token='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to remove query actions.");

	$Sl="DELETE FROM archived_actions WHERE token='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to remove archived query actions.");

	header("Location: tokens-manage.php");
	exit;
}


?>

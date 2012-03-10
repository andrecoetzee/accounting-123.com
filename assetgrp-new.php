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
require ("core-settings.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter ($HTTP_POST_VARS);
	}
} else {
	$OUTPUT = enter ($HTTP_POST_VARS);
}

# display output
require ("template.php");

# enter new data
function enter ($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	if(!isset($group)) {
		$group="";
		$costacc=0;
		$accdacc=0;
		$depacc=0;
	}

	# connect to db
	core_connect ();
	$Dcostacc= "<select name='costacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Balance accounts yet in Cubit.</li>";
		}else{
			while($acc = pg_fetch_array($accRslt)){
				if(isb($acc['accid'])) {
					continue;
				}
				if($costacc==$acc['accid']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$Dcostacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
			}
		}
	$Dcostacc .="</select>";

	$Daccdacc= "<select name='accdacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
				return "<li class=error>There are no Balance accounts yet in Cubit.</li>";
		}else{
				while($acc = pg_fetch_array($accRslt)){
					if(isb($acc['accid'])) {
						continue;
					}
					if($acc['accid']==$accdacc) {
						$sel="selected";
					} else {
						$sel="";
					}
					$Daccdacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
				}
		}
	$Daccdacc .="</select>";

	$Ddepacc = "<select name='depacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype = 'E' AND div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li class=error>There are no Expenditure accounts yet in Cubit.</li>";
		}else{
			while($acc = pg_fetch_array($accRslt)){
				if(isb($acc['accid'])) {
					continue;
				}
				if($acc['accid']==$depacc) {
					$sel="selected";
				} elseif($depacc==0 && $acc['accname']=="Depreciation") {
					$sel="selected";
				}else {
					$sel="";
				}
				$Ddepacc .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
			}
		}
	$Ddepacc .="</select>";

	$enter =
	"<h3>Add Asset Group</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>".REQ."Asset Group</td><td><input type=text size=20 name=group value='$group'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>".REQ."Cost Account</td><td>$Dcostacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>".REQ."Accumulated Depreciation Account</td><td>$Daccdacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>".REQ."Depreciation Account</td><td>$Ddepacc</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='assetgrp-view.php'>View Asset Groups</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($group, "string", 1, 255, "Invalid Asset Group name.");
	$v->isOk ($costacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($accdacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($depacc, "num", 1, 20, "Invalid Account number.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return $confirm."</li>".enter($HTTP_POST_VARS)."</li>";
	}

	# Get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$costacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acdacct = pg_fetch_array($accRslt);

	# Get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$accdacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acdacc = pg_fetch_array($accRslt);

	# Get ledger account name
	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$depacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$accdep = pg_fetch_array($accRslt);

	$confirm =
	"<h3>Confirm Asset Group</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=group value='$group'>
	<input type=hidden name=costacc value='$costacc'>
	<input type=hidden name=accdacc value='$accdacc'>
	<input type=hidden name=depacc value='$depacc'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Asset Group</td><td>$group</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Cost Account</td><td>$acdacct[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Accumulated Depreciation Account</td><td>$acdacc[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Depreciation Account</td><td>$accdep[accname]</td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='assetgrp-view.php'>View Asset Group</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		return enter($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($group, "string", 1, 255, "Invalid Asset Group name.");
	$v->isOk ($costacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($accdacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($depacc, "num", 1, 20, "Invalid Account number.");

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

	# connect to db
	db_connect ();

	# write to db
	$sql = "INSERT INTO assetgrp(grpname, costacc, accdacc, depacc, div) VALUES ('$group', '$costacc', '$accdacc', '$depacc', '".USER_DIV."')";
	$inRslt = db_exec ($sql) or errDie ("Unable to add Asset Group to system.", SELF);

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Asset Group added to system</th></tr>
	<tr class=datacell><td>New Asset Group <b>$group</b>, has been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='assetgrp-view.php'>View Asset Groups</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>

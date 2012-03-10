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
require("../core-settings.php");
require("../libs/ext.lib.php");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "update":
			$OUTPUT = update($HTTP_POST_VARS);
			break;

		default:
			$OUTPUT = edit();
	}
} else {
	$OUTPUT = edit();
}

# get templete
require("../template.php");

# details
function edit($HTTP_POST_VARS = array(), $error="")
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

/* --------------------------- bal ------------------------------*/
	$products = "";

	$selected = array();
	core_connect();
	$sql = "SELECT * FROM trialgrps ";
	$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
	while($grp = pg_fetch_array($grpRslt)){
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=grpnames[] value='$grp[grpname]'></td><td><input name=addacc_$grp[gkey] type=submit value='Add Accounts'></td><td><input type=checkbox name=delgrps[] value='$grp[gkey]'></td><tr>";

		$sql = "SELECT * FROM trialgrpaccids WHERE gkey = '$grp[gkey]'";
		$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");

		while($gacc = pg_fetch_array($gaccRslt)){
			$selected[] = $gacc['accid'];
			$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
			$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
			$acc = pg_fetch_array($accRslt);

			$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td><input type=hidden name=grpaccids[$grp[gkey]][] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><input type=checkbox name=delaccids[$grp[gkey]][] value='$acc[accid]'></td></tr>";
		}
		$btn = "addacc_$grp[gkey]";
		if(isset($$btn)){
			$accs = "<select name=grpaccids[$grp[gkey]][] multiple size=20>";
				$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
				$accRslt = db_exec($sql);
				if(pg_numrows($accRslt) < 1){
					return "<li>There are No accounts in Cubit.";
				}
				while($acc = pg_fetch_array($accRslt)){
					if(in_array($acc['accid'], $selected)) continue;
					$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
				}
			$accs .= "</select>";

			$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td align=center>$accs</td><td><br></td></tr>";
		}
	}
	if(isset($grpadd)){
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=grpnames[] value=''></td><td><br></td><td><br></td><tr>";
	}

	$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=3><br></td></tr>
	<tr><th></th><th>Accounts</th><th></th></tr>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."'";
	$accRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
	while($acc = pg_fetch_array($accRslt)){
		if(in_array($acc['accid'], $selected)) continue;
		$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><br></td></tr>";
	}

/* -- Final Layout -- */
	$details = "<center><h3>Trial Balance Settings</h3>
	<form action='".SELF."' method=post name=form1>
	<input type=hidden name=key value=update>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><td align=center colspan=3><input name=grpadd type=submit value='Add Group'> | <input name=updateBtn type=submit value='Update'> | <input name=doneBtn type=submit value='Done'></td></tr>
	<tr><td colspan=4><br></td></tr>
	<tr><th>Groups</th><th>Accounts</th><th>Delete</th></tr>
	$products
	<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=3><br></td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center colspan=3><input name=grpadd type=submit value='Add Group'> | <input name=updateBtn type=submit value='Update'> | <input name=doneBtn type=submit value='Done'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table></center>";

	return $details;
}

# details
function update($HTTP_POST_VARS)
{

	#get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($grpnames)){
		foreach($grpnames as $gkey => $grpname){
			$v->isOk ($grpname, "string", 1, 255, "Invalid Group Name.");

			if(isset($grpaccids[$gkey])){
				foreach($grpaccids[$gkey] as $akey => $accid){
					$v->isOk ($accid, "num", 1, 20, "Invalid Group Account.");
				}
			}
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return edit($HTTP_POST_VARS, $err);
	}

	core_connect();
	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
		$sql = "DELETE FROM trialgrps";
		$rslt = db_exec($sql) or errDie("Unable to update groups in Cubit.",SELF);

		$sql = "DELETE FROM trialgrpaccids";
		$rslt = db_exec($sql) or errDie("Unable to update groups in Cubit.",SELF);

		/* bal */
		if(isset($grpnames)){
			foreach($grpnames as $gkey => $grpname){
				if(isset($delgrps) && in_array($gkey, $delgrps))continue;
				$sql = "INSERT INTO trialgrps(gkey, grpname) VALUES('$gkey', '$grpname')";
				$rslt = db_exec($sql) or errDie("Unable to insert groups in Cubit.",SELF);

				if(isset($grpaccids[$gkey])){
					foreach($grpaccids[$gkey] as $akey => $accid){
						if(isset($delaccids[$gkey]) && in_array($accid, $delaccids[$gkey])){
							continue;
						}
						$sql = "INSERT INTO trialgrpaccids(gkey, accid) VALUES('$gkey', '$accid')";
						$rslt = db_exec($sql) or errDie("Unable to insert group accounts in Cubit.",SELF);
					}
				}
			}
		}

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if( !isset($doneBtn) ){
		return edit($HTTP_POST_VARS);
	} else {
		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Trial Balance has been set</th></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $write;
	}
}
?>

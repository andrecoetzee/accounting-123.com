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

/* --------------------------- OE ------------------------------*/
	$products = "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4><h4>Equity & Liabilities</h4></td></tr>";

	# Get settings
	db_conn("core");
	$sql = "SELECT * FROM balsubs WHERE typ = 'oe' ORDER BY skey ASC";
	$subRslt = db_exec ($sql) or errDie ("Unable to get sub-headings information.");

	while ($sub = pg_fetch_array($subRslt)) {
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=oe_subnames[] value='$sub[subname]'></td><td><input name=oe_addgrp_$sub[skey] type=submit value='Add Group'></td><td><br></td><td><input type=checkbox name=oe_delsubs[] value='$sub[skey]'></td><tr>";

		$sql = "SELECT * FROM balgrps WHERE skey = '$sub[skey]' AND typ = 'oe'";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td> <br> </td>
			<td><input type=text size=30 name=oe_grpnames[$sub[skey]][] value='$grp[grpname]'></td><td><input name=oe_addacc_$sub[skey]_$grp[gkey] type=submit value='Add Accounts'></td><td><input type=checkbox name=oe_delgrps[$sub[skey]][] value='$grp[gkey]'></td><tr>";

			$sql = "SELECT * FROM balgrpaccids WHERE skey = '$sub[skey]' AND gkey = '$grp[gkey]' AND typ = 'oe'";
			$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");

			while($gacc = pg_fetch_array($gaccRslt)){
				$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$acc = pg_fetch_array($accRslt);

				$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td> <br> </td><td> <br> </td>
				<td><input type=hidden name=oe_grpaccids[$sub[skey]][$grp[gkey]][] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><input type=checkbox name=oe_delaccids[$sub[skey]][$grp[gkey]][] value='$acc[accid]'></td></tr>";
			}
			$btn = "oe_addacc_$sub[skey]_$grp[gkey]";
			if(isset($$btn)){
				$accs = "<select name=oe_grpaccids[$sub[skey]][$grp[gkey]][] multiple size=20>";
					$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."' ORDER BY accname ASC";
					$accRslt = db_exec($sql);
					if(pg_numrows($accRslt) < 1){
						return "<li>There are No accounts in Cubit.";
					}
					while($acc = pg_fetch_array($accRslt)){
						$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
					}
				$accs .= "</select>";

				$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td> <br> </td><td> <br> </td>
				<td align=center>$accs</td><td><br></td></tr>";
			}
		}
		$btn = "oe_addgrp_$sub[skey]";
		if(isset($$btn)){
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td> <br> </td>
			<td><input type=text size=30 name=oe_grpnames[$sub[skey]][] value=''></td><td><br></td><td><br></td><tr>";
		}
		$products .="</tr>";
	}
	if(isset($oe_addsub)){
		 $products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=oe_subnames[] value=''></td><td><br></td><td><br></td><td><br></td><tr>";
	}

	/* --------------------------- Asserts ------------------------------*/
	$products .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4><h4>Assets</h4></td></tr>";

	# Get settings
	db_conn("core");
	$sql = "SELECT * FROM balsubs WHERE typ = 'as' ORDER BY skey ASC";
	$subRslt = db_exec ($sql) or errDie ("Unable to get sub-headings information.");

	while ($sub = pg_fetch_array($subRslt)) {
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=as_subnames[] value='$sub[subname]'></td><td><input name=as_addgrp_$sub[skey] type=submit value='Add Group'></td><td><br></td><td><input type=checkbox name=as_delsubs[] value='$sub[skey]'></td><tr>";

		$sql = "SELECT * FROM balgrps WHERE skey = '$sub[skey]' AND typ = 'as'";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td> <br> </td>
			<td><input type=text size=30 name=as_grpnames[$sub[skey]][] value='$grp[grpname]'></td><td><input name=as_addacc_$sub[skey]_$grp[gkey] type=submit value='Add Accounts'></td><td><input type=checkbox name=as_delgrps[$sub[skey]][] value='$grp[gkey]'></td><tr>";

			$sql = "SELECT * FROM balgrpaccids WHERE skey = '$sub[skey]' AND gkey = '$grp[gkey]' AND typ = 'as'";
			$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");

			while($gacc = pg_fetch_array($gaccRslt)){
				$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$acc = pg_fetch_array($accRslt);

				$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td> <br> </td><td> <br> </td>
				<td><input type=hidden name=as_grpaccids[$sub[skey]][$grp[gkey]][] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><input type=checkbox name=as_delaccids[$sub[skey]][$grp[gkey]][] value='$acc[accid]'></td></tr>";
			}
			$btn = "as_addacc_$sub[skey]_$grp[gkey]";
			if(isset($$btn)){
				$accs = "<select name=as_grpaccids[$sub[skey]][$grp[gkey]][] multiple size=20>";
					$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."' ORDER BY accname ASC";
					$accRslt = db_exec($sql);
					if(pg_numrows($accRslt) < 1){
						return "<li>There are No accounts in Cubit.";
					}
					while($acc = pg_fetch_array($accRslt)){
						$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
					}
				$accs .= "</select>";

				$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td> <br> </td><td> <br> </td>
				<td align=center>$accs</td><td><br></td></tr>";
			}
		}
		$btn = "as_addgrp_$sub[skey]";
		if(isset($$btn)){
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td> <br> </td>
			<td><input type=text size=30 name=as_grpnames[$sub[skey]][] value=''></td><td><br></td><td><br></td><tr>";
		}
		$products .="</tr>";
	}
	if(isset($as_addsub)){
		 $products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text size=30 name=as_subnames[] value=''></td><td><br></td><td><br></td><td><br></td><tr>";
	}

/* -- Final Layout -- */
	$details = "<center><h3>Balance sheet Settings</h3>
	<form action='".SELF."' method=post name=form1>
	<input type=hidden name=key value=update>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><td align=center colspan=3><input name=oe_addsub type=submit value='Add Equity & Liabilities Sub'> | <input name=as_addsub type=submit value='Add Assets Sub'> | <input name=updateBtn type=submit value='Update'> | <input name=doneBtn type=submit value='Done'></td></tr>
	<tr><td colspan=4><br></td></tr>
	<tr><th width=20%>Sub Heading</th><th width=20%>Group</th><th>Accounts</th><th>Delete</th></tr>
	$products
	<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=4><br></td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center colspan=3><input name=oe_addsub type=submit value='Add Equity & Liabilities Sub'> | <input name=as_addsub type=submit value='Add Assets Sub'> | <input name=updateBtn type=submit value='Update'> | <input name=doneBtn type=submit value='Done'></td></tr>
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
	if(isset($oe_subnames)){
		foreach($oe_subnames as $skey => $oe_subname){
			$v->isOk ($oe_subname, "string", 1, 255, "Invalid Sub Name.");

			if(isset($oe_grpnames[$skey])){
				foreach($oe_grpnames[$skey] as $gkey => $oe_grpname){
					$v->isOk ($oe_grpname, "string", 1, 255, "Invalid Group Name.");

					if(isset($oe_grpaccids[$skey][$gkey])){
						foreach($oe_grpaccids[$skey][$gkey] as $akey => $oe_accid){
							$v->isOk ($oe_accid, "num", 1, 20, "Invalid Group Account.");
						}
					}
				}
			}
		}
	}
	if(isset($as_subnames)){
		foreach($as_subnames as $skey => $as_subname){
			$v->isOk ($as_subname, "string", 1, 255, "Invalid Sub Name.");

			if(isset($as_grpnames[$skey])){
				foreach($as_grpnames[$skey] as $gkey => $as_grpname){
					$v->isOk ($as_grpname, "string", 1, 255, "Invalid Group Name.");

					if(isset($as_grpaccids[$skey][$gkey])){
						foreach($as_grpaccids[$skey][$gkey] as $akey => $as_accid){
							$v->isOk ($as_accid, "num", 1, 20, "Invalid Group Account.");
						}
					}
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
		$sql = "DELETE FROM balsubs";
		$rslt = db_exec($sql) or errDie("Unable to update sub-headings in Cubit.",SELF);

		$sql = "DELETE FROM balgrps";
		$rslt = db_exec($sql) or errDie("Unable to update groups in Cubit.",SELF);

		$sql = "DELETE FROM balgrpaccids";
		$rslt = db_exec($sql) or errDie("Unable to update groups in Cubit.",SELF);

		/* OE */
		if(isset($oe_subnames)){
			foreach($oe_subnames as $skey => $subname){
				if(isset($oe_delsubs) && in_array($skey, $oe_delsubs))continue;
				$sql = "INSERT INTO balsubs(typ, skey, subname) VALUES('oe', '$skey', '$subname')";
				$rslt = db_exec($sql) or errDie("Unable to insert sub-headings in Cubit.",SELF);

				if(isset($oe_grpnames[$skey])){
					foreach($oe_grpnames[$skey] as $gkey => $grpname){
						if(isset($oe_delgrps[$skey]) && in_array($gkey, $oe_delgrps[$skey]))continue;
						$sql = "INSERT INTO balgrps(typ, skey, gkey, grpname) VALUES('oe', '$skey', '$gkey', '$grpname')";
						$rslt = db_exec($sql) or errDie("Unable to insert groups in Cubit.",SELF);

						if(isset($oe_grpaccids[$skey][$gkey])){
							$oe_remed = array();
							foreach($oe_grpaccids[$skey][$gkey] as $akey => $accid){
								if(isset($oe_delaccids[$skey][$gkey]) && in_array($accid, $oe_delaccids[$skey][$gkey]) && !in_array($accid, $oe_remed)){
									$oe_remed[] = $accid;
									continue;
								}
								$sql = "INSERT INTO balgrpaccids(typ, skey, gkey, accid) VALUES('oe', '$skey', '$gkey', '$accid')";
								$rslt = db_exec($sql) or errDie("Unable to insert group accounts in Cubit.",SELF);
							}
						}
					}
				}
			}
		}
		/* Asserts */
		if(isset($as_subnames)){
			foreach($as_subnames as $skey => $subname){
				if(isset($as_delsubs) && in_array($skey, $as_delsubs))continue;
				$sql = "INSERT INTO balsubs(typ, skey, subname) VALUES('as', '$skey', '$subname')";
				$rslt = db_exec($sql) or errDie("Unable to insert sub-headings in Cubit.",SELF);

				if(isset($as_grpnames[$skey])){
					foreach($as_grpnames[$skey] as $gkey => $grpname){
						if(isset($as_delgrps[$skey]) && in_array($gkey, $as_delgrps[$skey]))continue;
						$sql = "INSERT INTO balgrps(typ, skey, gkey, grpname) VALUES('as', '$skey', '$gkey', '$grpname')";
						$rslt = db_exec($sql) or errDie("Unable to insert groups in Cubit.",SELF);

						if(isset($as_grpaccids[$skey][$gkey])){
							$as_remed = array();
							foreach($as_grpaccids[$skey][$gkey] as $akey => $accid){
								if(isset($as_delaccids[$skey][$gkey]) && in_array($accid, $as_delaccids[$skey][$gkey]) && !in_array($accid, $as_remed)){
									$as_remed[] = $accid;
									continue;
								}
								$sql = "INSERT INTO balgrpaccids(typ, skey, gkey, accid) VALUES('as', '$skey', '$gkey', '$accid')";
								$rslt = db_exec($sql) or errDie("Unable to insert group accounts in Cubit.",SELF);
							}
						}
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
			<tr><th>Balance sheet has been set</th></tr>
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

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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "update":
			$OUTPUT = update($_POST);
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
function edit($_POST = array(), $error="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

/* --------------------------- Income ------------------------------*/
	$products = "<tr class='bg-odd'><td colspan=4><h3>Income</h3></td></tr>";

	# Get settings
	db_conn("core");
	$sql = "SELECT * FROM stmntgrps WHERE typ = 'inc'";
	$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
	while($grp = pg_fetch_array($grpRslt)){
		$products .="<tr class='bg-odd'>
		<td><input type=text size=30 name=inc_grpnames[] value='$grp[grpname]'></td><td><input name=inc_addacc_$grp[gkey] type=submit value='Add Accounts'></td><td><input type=checkbox name=inc_delgrps[] value='$grp[gkey]'></td><tr>";

		$sql = "SELECT * FROM stmntgrpaccids WHERE gkey = '$grp[gkey]' AND typ = 'inc'";
		$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");

		while($gacc = pg_fetch_array($gaccRslt)){
			$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
			$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
			$acc = pg_fetch_array($accRslt);

			$products .="<tr class='bg-even'><td> <br> </td>
			<td><input type=hidden name=inc_grpaccids[$grp[gkey]][] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><input type=checkbox name=inc_delaccids[$grp[gkey]][] value='$acc[accid]'></td></tr>";
		}
		$btn = "inc_addacc_$grp[gkey]";
		if(isset($$btn)){
			$accs = "<select name=inc_grpaccids[$grp[gkey]][] multiple size=20>";
				$sql = "SELECT * FROM accounts WHERE acctype = 'I' AND div = '".USER_DIV."' ORDER BY accname ASC";
				$accRslt = db_exec($sql);
				if(pg_numrows($accRslt) < 1){
					return "<li>There are No accounts in Cubit.";
				}
				while($acc = pg_fetch_array($accRslt)){
					$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
				}
			$accs .= "</select>";

			$products .="<tr class='bg-even'><td> <br> </td>
			<td align=center>$accs</td><td><br></td></tr>";
		}
	}
	$btn = "inc_addgrp";
	if(isset($$btn)){
		$products .="<tr class='bg-odd'>
		<td><input type=text size=30 name=inc_grpnames[] value=''></td><td><br></td><td><br></td><tr>";
	}
	$products .="</tr>";

	/* --------------------------- COS ------------------------------*/
	$products .= "<tr class='bg-odd'><td colspan=4><h3>Cost Of Sales</h3></td></tr>";

	# Get settings
	db_conn("core");
	$sql = "SELECT * FROM stmntgrps WHERE typ = 'cos'";
	$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
	while($grp = pg_fetch_array($grpRslt)){
		$products .="<tr class='bg-odd'>
		<td><input type=text size=30 name=cos_grpnames[] value='$grp[grpname]'></td><td><input name=cos_addacc_$grp[gkey] type=submit value='Add Accounts'></td><td><input type=checkbox name=cos_delgrps[] value='$grp[gkey]'></td><tr>";

		$sql = "SELECT * FROM stmntgrpaccids WHERE gkey = '$grp[gkey]' AND typ = 'cos'";
		$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");

		while($gacc = pg_fetch_array($gaccRslt)){
			$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
			$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
			$acc = pg_fetch_array($accRslt);

			$products .="<tr class='bg-even'><td> <br> </td>
			<td><input type=hidden name=cos_grpaccids[$grp[gkey]][] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><input type=checkbox name=cos_delaccids[$grp[gkey]][] value='$acc[accid]'></td></tr>";
		}
		$btn = "cos_addacc_$grp[gkey]";
		if(isset($$btn)){
			$accs = "<select name=cos_grpaccids[$grp[gkey]][] multiple size=20>";
				$sql = "SELECT * FROM accounts WHERE acctype = 'E' AND div = '".USER_DIV."' ORDER BY accname ASC";
				$accRslt = db_exec($sql);
				if(pg_numrows($accRslt) < 1){
					return "<li>There are No accounts in Cubit.";
				}
				while($acc = pg_fetch_array($accRslt)){
					$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
				}
			$accs .= "</select>";

			$products .="<tr class='bg-even'><td> <br> </td>
			<td align=center>$accs</td><td><br></td></tr>";
		}
	}
	$btn = "cos_addgrp";
	if(isset($$btn)){
		$products .="<tr class='bg-odd'>
		<td><input type=text size=30 name=cos_grpnames[] value=''></td><td><br></td><td><br></td><tr>";
	}
	$products .="</tr>";

	/* --------------------------- exp ------------------------------*/
	$products .= "<tr class='bg-odd'><td colspan=4><h3>Expenses</h3></td></tr>";

	# Get settings
	db_conn("core");
	$sql = "SELECT * FROM stmntgrps WHERE typ = 'exp'";
	$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
	while($grp = pg_fetch_array($grpRslt)){
		$products .="<tr class='bg-odd'>
		<td><input type=text size=30 name=exp_grpnames[] value='$grp[grpname]'></td><td><input name=exp_addacc_$grp[gkey] type=submit value='Add Accounts'></td><td><input type=checkbox name=exp_delgrps[] value='$grp[gkey]'></td><tr>";

		$sql = "SELECT * FROM stmntgrpaccids WHERE gkey = '$grp[gkey]' AND typ = 'exp'";
		$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");

		while($gacc = pg_fetch_array($gaccRslt)){
			$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
			$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
			$acc = pg_fetch_array($accRslt);

			$products .="<tr class='bg-even'><td> <br> </td>
			<td><input type=hidden name=exp_grpaccids[$grp[gkey]][] value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td><input type=checkbox name=exp_delaccids[$grp[gkey]][] value='$acc[accid]'></td></tr>";
		}
		$btn = "exp_addacc_$grp[gkey]";
		if(isset($$btn)){
			$accs = "<select name=exp_grpaccids[$grp[gkey]][] multiple size=20>";
				$sql = "SELECT * FROM accounts WHERE acctype = 'E' AND div = '".USER_DIV."' ORDER BY accname ASC";
				$accRslt = db_exec($sql);
				if(pg_numrows($accRslt) < 1){
					return "<li>There are No accounts in Cubit.";
				}
				while($acc = pg_fetch_array($accRslt)){
					$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
				}
			$accs .= "</select>";

			$products .="<tr class='bg-even'><td> <br> </td>
			<td align=center>$accs</td><td><br></td></tr>";
		}
	}
	$btn = "exp_addgrp";
	if(isset($$btn)){
		$products .="<tr class='bg-odd'>
		<td><input type=text size=30 name=exp_grpnames[] value=''></td><td><br></td><td><br></td><tr>";
	}
	$products .="</tr>";

/* -- Final Layout -- */
	$details = "<center><h3>Income Statement Settings</h3>
	<form action='".SELF."' method=post name=form1>
	<input type=hidden name=key value=update>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><td align=center colspan=3><input name=inc_addgrp type=submit value='Add Income Group'> | <input name=cos_addgrp type=submit value='Add COS Group'> | <input name=exp_addgrp type=submit value='Add Expediture Group'> | <input name=updateBtn type=submit value='Update'> | <input name=doneBtn type=submit value='Done'></td></tr>
	<tr><td colspan=4><br></td></tr>
	<tr><th width=20%>Group</th><th>Accounts</th><th>Delete</th></tr>
	$products
	<tr class='bg-odd'><td colspan=4><br></td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center colspan=3><input name=inc_addgrp type=submit value='Add Income Group'> | <input name=cos_addgrp type=submit value='Add COS Group'> | <input name=exp_addgrp type=submit value='Add Expediture Group'> | <input name=updateBtn type=submit value='Update'> | <input name=doneBtn type=submit value='Done'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>
	</center>";

	return $details;
}

# details
function update($_POST)
{

	#get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($inc_grpnames)){
		foreach($inc_grpnames as $gkey => $inc_grpname){
			$v->isOk ($inc_grpname, "string", 1, 255, "Invalid Group Name.");

			if(isset($inc_grpaccids[$gkey])){
				foreach($inc_grpaccids[$gkey] as $akey => $inc_accid){
					$v->isOk ($inc_accid, "num", 1, 20, "Invalid Group Account.");
				}
			}
		}
	}
	if(isset($cos_grpnames)){
		foreach($cos_grpnames as $gkey => $cos_grpname){
			$v->isOk ($cos_grpname, "string", 1, 255, "Invalid Group Name.");

			if(isset($cos_grpaccids[$gkey])){
				foreach($cos_grpaccids[$gkey] as $akey => $cos_accid){
					$v->isOk ($cos_accid, "num", 1, 20, "Invalid Group Account.");
				}
			}
		}
	}
	if(isset($exp_grpnames)){
		foreach($exp_grpnames as $gkey => $exp_grpname){
			$v->isOk ($exp_grpname, "string", 1, 255, "Invalid Group Name.");

			if(isset($exp_grpaccids[$gkey])){
				foreach($exp_grpaccids[$gkey] as $akey => $exp_accid){
					$v->isOk ($exp_accid, "num", 1, 20, "Invalid Group Account.");
				}
			}
		}
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li cleass=err>".$e["msg"];
		}
		return edit($_POST, $err);
	}

	core_connect();
	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		/* -- Start remove old items -- */
		$sql = "DELETE FROM stmntgrps";
		$rslt = db_exec($sql) or errDie("Unable to update groups in Cubit.",SELF);

		$sql = "DELETE FROM stmntgrpaccids";
		$rslt = db_exec($sql) or errDie("Unable to update groups in Cubit.",SELF);

		/* inc */
		if(isset($inc_grpnames)){
			foreach($inc_grpnames as $gkey => $grpname){
				if(isset($inc_delgrps) && in_array($gkey, $inc_delgrps))continue;
				$sql = "INSERT INTO stmntgrps(typ, gkey, grpname) VALUES('inc', '$gkey', '$grpname')";
				$rslt = db_exec($sql) or errDie("Unable to insert groups on Cubit.",SELF);

				if(isset($inc_grpaccids[$gkey])){
					$inc_remed = array();
					foreach($inc_grpaccids[$gkey] as $akey => $accid){
						if(isset($inc_delaccids[$gkey]) && in_array($accid, $inc_delaccids[$gkey]) && !in_array($accid, $inc_remed)){
							$inc_remed[] = $accid;
							continue;
						}
						$sql = "INSERT INTO stmntgrpaccids(typ, gkey, accid) VALUES('inc', '$gkey', '$accid')";
						$rslt = db_exec($sql) or errDie("Unable to insert group accounts in Cubit.",SELF);
					}
				}
			}
		}
		/* cos */
		if(isset($cos_grpnames)){
			foreach($cos_grpnames as $gkey => $grpname){
				if(isset($cos_delgrps) && in_array($gkey, $cos_delgrps))continue;
				$sql = "INSERT INTO stmntgrps(typ, gkey, grpname) VALUES('cos', '$gkey', '$grpname')";
				$rslt = db_exec($sql) or errDie("Unable to insert groups in Cubit.",SELF);

				if(isset($cos_grpaccids[$gkey])){
					$cos_remed = array();
					foreach($cos_grpaccids[$gkey] as $akey => $accid){
						if(isset($cos_delaccids[$gkey]) && in_array($accid, $cos_delaccids[$gkey]) && !in_array($accid, $cos_remed)){
							$cos_remed[] = $accid;
							continue;
						}
						$sql = "INSERT INTO stmntgrpaccids(typ, gkey, accid) VALUES('cos', '$gkey', '$accid')";
						$rslt = db_exec($sql) or errDie("Unable to insert group accounts in Cubit.",SELF);
					}
				}
			}
		}
		/* exp */
		if(isset($exp_grpnames)){
			foreach($exp_grpnames as $gkey => $grpname){
				if(isset($exp_delgrps) && in_array($gkey, $exp_delgrps))continue;
				$sql = "INSERT INTO stmntgrps(typ, gkey, grpname) VALUES('exp', '$gkey', '$grpname')";
				$rslt = db_exec($sql) or errDie("Unable to insert groups in Cubit.",SELF);

				if(isset($exp_grpaccids[$gkey])){
					$exp_remed = array();
					foreach($exp_grpaccids[$gkey] as $akey => $accid){
						if(isset($exp_delaccids[$gkey]) && in_array($accid, $exp_delaccids[$gkey]) && !in_array($accid, $exp_remed)){
							$exp_remed[] = $accid;
							continue;
						}
						$sql = "INSERT INTO stmntgrpaccids(typ, gkey, accid) VALUES('exp', '$gkey', '$accid')";
						$rslt = db_exec($sql) or errDie("Unable to insert group accounts in Cubit.",SELF);
					}
				}
			}
		}

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if( !isset($doneBtn) ){
		return edit($_POST);
	} else {
		// Final Laytout
		$write = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Income Statement has been set</th></tr>
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

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
		case "seltoken":
			$OUTPUT = seltoken();
			break;
		case "updatecsc":
			$OUTPUT = updatecsc($_POST);
			break;
		case "find":
			$OUTPUT = find($_POST);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = manage($_GET);
} else {
	$OUTPUT = manage($_POST);
}

require("template.php");

function seltoken() {

	global $_POST;
	extract($_POST);

	if(!isset($name)) {
		$name="";
	}
	if(!isset($subject)) {
		$subject="";
	}
	if(!isset($notes)) {
		$notes="";
	}

	$name=remval($name);
	$subject=remval($subject);
	$notes=remval($notes);
	
	$whe="";
	$csc=0;

	if(!isset($team)) {
		$team=0;
		$user=0;
		$cat=0;
		$csc=0;
	} else {
		$team+=0;
		$user+=0;
		$cat+=0;
		$csc+=0;
	}
	
        if($team==0) {
		db_conn('crm');
		$Sl="SELECT teamid FROM crms WHERE userid='".USER_ID."'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$crmdata=pg_fetch_array($Ri);

		$team=$crmdata['teamid'];
	}
	if($team!=0) {
		$whe.=" AND teamid='$team' ";
	}
	if($user!=0) {
		$whe.=" AND userid='$user' ";
	}
	if($cat!=0) {
		$whe.=" AND catid='$cat' ";
	}
	if($csc!=0) {
		if($csc==1) {
			$whe.=" AND csct='Contact' ";
		} elseif($csc==2) {
			$whe.=" AND csct='Customer' ";
		} elseif($csc==3) {
			$whe.=" AND csct='Supplier' ";
		}
	}

	if(strlen($name)>0) {
		$whe.=" AND lower(name) LIKE lower('%$name%') ";
	}

	if(strlen($subject)>0) {
		$whe.=" AND lower(sub) LIKE lower('%$subject%') ";
	}

	if(strlen($notes)>0) {
		$whe.=" AND lower(notes) LIKE lower('%$notes%') ";
	}
	
	$date=date("Y-m-d");

	db_conn('crm');
	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry=db_exec($Sl) or errDie("Unable to get info from db.");

	if(pg_numrows($Ry)<1) {
		return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}
	
	$crmdata=pg_fetch_array($Ry);

	if($crmdata['teamid']==0)  {
                return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}

	$Sl="SELECT * FROM teams WHERE id='$team'";
	$Ry=db_exec($Sl) or errDie("Unable to get team data.");

	$teamdata=pg_fetch_array($Ry);
	
	$username=USER_NAME;
	$disdate=date("d-m-Y, l, G:i");

	$i=0;

	$Sl="SELECT * FROM teamlinks WHERE team='$team' ORDER BY num";
	$Ry=db_exec($Sl) or errDie("Unable to get teamlinks from system.");

	if(pg_numrows($Ry)<1) {
		$teamlinks="<tr><td>There are no links for this team. Select links under settings, view teams.</td></tr><tr><th>Quick Links</th></tr>
                <tr class='bg-odd'><td align=center><a href='team-links.php?id=$crmdata[teamid]'>Select Team Links</a></td></tr>";
	} else {
		$teamlinks="";
		while($linkdata=pg_fetch_array($Ry)) {
			$i++;

			$teamlinks.="<tr class='".bg_class()."'><td><a target=_blank href='$linkdata[script]'>$linkdata[name]</a></td></tr>";
		}
	}
	
	$i=0;
	$tokens="";
	
	$Sl="SELECT id,sub,name FROM tokens WHERE userid='".USER_ID."' $whe ORDER BY id";
        $Ry=db_exec($Sl) or errDie("Unable to get queries from db.");

	while($tokendata=pg_fetch_array($Ry)) {
		$i++;

		$tokens.="<tr class='".bg_class()."'><td>$tokendata[id]</td><td>$tokendata[name] - $tokendata[sub]</td><td><a href='tokens-manage.php?id=$tokendata[id]'>Open</a></td></tr>";
	}
	
	$Sl="SELECT id,name,username,sub,lastdate,opendate FROM tokens WHERE nextdate<='$date' $whe ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_numrows($Ry)>0) {

		$i=0;

		$out="<h3>All Outstanding Queries</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>No.</th><th>Subject</th><th>User</th><th>Date Opened</th><th>Last Date</th>
		<th>Options</th></tr>";

		while($data=pg_fetch_array($Ry)) {
			$i++;

			$out.="<tr class='".bg_class()."'><td>$data[id]</td><td>$data[name] - $data[sub]</td><td>$data[username]</td>
			<td>$data[opendate]</td><td>$data[lastdate]</td>
			<td><a href='tokens-manage.php?id=$data[id]'>Open</a></td></tr>";

		}

		$out.="</table>";
		
	} else {
		$out="No Outstanding queries";
	}
	
	$Sl="SELECT id,name,username,sub,lastdate,nextdate,opendate FROM tokens WHERE nextdate>'$date' $whe ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_numrows($Ry)>0) {

		$i=0;

		$future="<h3>All Forwarded Queries</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>No.</th><th>Subject</th><th>User</th><th>Date Opened</th><th>Last Date</th><th>Next Date</th>
		<th>Options</th></tr>";

		while($data=pg_fetch_array($Ry)) {
			$i++;

			$future.="<tr class='".bg_class()."'><td>$data[id]</td><td>$data[name] - $data[sub]</td><td>$data[username]</td>
			<td>$data[opendate]</td><td>$data[lastdate]</td><td>$data[nextdate]</td>
			<td><a href='tokens-manage.php?id=$data[id]'>Open</a></td></tr>";

		}

		$future.="</table>";

	} else {
		$future="No forwarded queries.";
	}


        $cteams=explode("|",$crmdata['teams']);
	$Sl="SELECT id,name FROM teams ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get teams from system.");

	$teams="<select name=team onChange='javascript:document.form.submit();'>";
	//$teams.="<option value='0'>All</option>";

        while($tdata=pg_fetch_array($Ry)) {
                if($team==0) {
			if($tdata['id']==$crmdata['teamid']) {
				$sel="selected";
			} else {
				$sel="";
			}
		} else {
			if($team==$tdata['id']) {
				$sel="selected";
			} else {
				$sel="";
			}
		}
		if(in_array($tdata['id'],$cteams)) {
			$teams.="<option value='$tdata[id]' $sel>$tdata[name]</option>";
		}
	}
	
	$teams.="</select>";
	
	$Sl="SELECT userid,name,teamid FROM crms WHERE div='".USER_DIV."'";
	$Ry=db_exec($Sl) or errDie("Unable to get users from db.");

	$users="<select name=user onChange='javascript:document.form.submit();'>";
	$users.="<option value='0'>All</option>";
	
	while($udata=pg_fetch_array($Ry)) {
		if($user==$udata['userid']) {
			$sel="selected";
		} else {
			$sel="";
		}
		if(in_array($udata['teamid'],$cteams)) {
			$users.="<option value='$udata[userid]' $sel>$udata[name]</option>";
		}
	}
	
	$users.="</select>";
	
	$Sl="SELECT * FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get categories from system.");

	$cats="<select name=cat onChange='javascript:document.form.submit();'>";
	$cats.="<option value='0'>All</option>";

	while($cdata=pg_fetch_array($Ry)) {
		if($cat==$cdata['id']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$cats.="<option value='$cdata[id]' $sel>$cdata[name]</option>";
	}

	$cats.="</select>";
	
        $csc=0;
	if($csc==0) {
		$op0="selected";
		$op1="";
		$op2="";
		$op3="";
	} elseif($csc==1) {
		$op0="";
		$op1="selected";
		$op2="";
		$op3="";
	} elseif($csc==2) {
		$op0="";
		$op1="";
		$op2="selected";
		$op3="";
	} elseif($csc==3) {
		$op0="";
		$op1="";
		$op2="";
		$op3="selected";
	}

	$cscs="<select name=csc onChange='javascript:document.form.submit();'>
	<option value='0' $op0>All</option>
	<option value='1' $op1>Contacts</option>
	<option value='2' $op2>Customers</option>
	<option value='3' $op3>Suppliers</option>
	</select>";


	$out="<h3>Select a Query</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=750>
	<tr>
		<td colspan=3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr>
			<td>
			<form action='tokens-new.php'>
				<input type=submit value='New Query &raquo;'>
			</form>
			</td>
			<td align=center><h3>Team: $teamdata[name] | User: $username | Date: $disdate</h3></td>
		</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td colspan=2>
		<table border=0 cellpadding=2 cellspacing=1 width='100%'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value='seltoken'>
		<tr><th colspan=2>Query Criteria</th></tr>
		<tr class='bg-odd'><td>Team</td><td>$teams</td></tr>
		<tr class='bg-even'><td>Users</td><td>$users</td></tr>
		<tr class='bg-odd'><td>Categories</td><td>$cats</td></tr>
		<tr class='bg-even'><td>Enquery By(name)</td><td><input type=text size=20 name=name value='$name'></td></tr>
		<tr class='bg-odd'><td>Subject</td><td><input type=text size=20 name=subject value='$subject'></td></tr>
		<tr class='bg-even'><td>Notes</td><td><input type=text size=20 name=notes value='$notes'></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Search &raquo;'></td></tr>
		</form>
		</table>
		</td>
		<td align=center valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>$teamdata[name] Quick Links</th></tr>
		$teamlinks
		</table>
		</td>
	</tr>
	<tr>
	<td><br></td>
	</tr>
	<tr>
		<td width='22%'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value='find'>
		<tr><th colspan=2>Search</th></tr>
		<tr class='bg-odd'><td colspan=2><li><a href='tokens-list-open.php'>List All Open Queries</a></li></td></tr>
		<tr class='bg-even'><td>Input No</td><td><input name=id type=text size=7></td></tr>
		<tr><td colspan=2 align=right><input type=submit name='search' value='Search &raquo;'></td></tr>
		</form>
		</table>
		</td>
		<td valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=3>My Open Queries</th></tr>
		$tokens
		</table>
		</td>
	</tr>
	</table>
	$out
	<p>
	$future
	<p>
	<p>
                <table border=0 cellpadding='2' cellspacing='1'>
                <tr><th>Quick Links</th></tr>
                <tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
                <tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
                </table>";

	return $out;
}

function find($_POST) {
	extract($_POST);
	
	$id+=0;

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query from system.");
	
	if(pg_numrows($Ry)<1) {
		return "The query number you typed in was not found.(maybe it was closed).".seltoken();
	}

	$data=pg_fetch_array($Ry);

        $Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

        $cdata=pg_fetch_array($Ri);

	$teams=explode("|",$cdata['teams']);

	if(!(in_array($data['teamid'],$teams))) {
                return "The query number you typed in does not belong to your team.".seltoken();
	}
	
	return manage($_POST);
}

function manage($_POST,$Notes="") {

	extract($_POST);
	if(!(isset($id))) {
		return seltoken();
	}

	db_conn('crm');
	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry=db_exec($Sl) or errDie("Unable to get info from db.");

	if(pg_numrows($Ry)<1) {
		return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}

	$crmdata=pg_fetch_array($Ry);

	if($crmdata['teamid']==0)  {
                return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}

	$cteams=explode("|",$crmdata['teams']);

	$crmdata['listcat']+=0;

	$Sl="SELECT * FROM teams WHERE id='$crmdata[teamid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get team data.");

	$teamdata=pg_fetch_array($Ry);

	$username=USER_NAME;
	$date=date("Y-m-d");
	$disdate=date("d-m-Y, l, G:i");

        $exwhe="";

        if($crmdata['listcat']>0) {
		$exwhe.="AND catid='$crmdata[listcat]' ";
	}

	$Sl="SELECT id,sub,name,teamid FROM tokens WHERE userid='".USER_ID."' AND nextdate<='$date'  $exwhe ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get queries from db.");

	$i=0;
	$tokens="<tr><th colspan=3>My Outstanding Queries</th></tr>";

	while($tokendata=pg_fetch_array($Ry)) {
                if(in_array($tokendata['teamid'],$cteams)) {
			$i++;

			$tokens.="<tr class='".bg_class()."'><td>$tokendata[id]</td><td>$tokendata[name] - ".substr($tokendata['sub'],0,14)."</td><td><a href='tokens-manage.php?id=$tokendata[id]'>Open</a></td></tr>";

		}
	}

	$tokens.="<tr class='".bg_class()."'><td colspan=3>Outstanding Queries: $i</td></tr>";
	$tokens.="<tr><td><br></td></tr>";
	
	if($i==0) {
		$tokens="";
	}

	$Sl="SELECT id,sub,nextdate,name,teamid FROM tokens WHERE userid='".USER_ID."' AND nextdate>'$date'  $exwhe ORDER BY nextdate";
	$Ry=db_exec($Sl) or errDie("Unable to get queries from db.");

	$i=0;
	$future_tokens="<tr><th colspan=4>My Forwarded Queries</th></tr>";
	$future_tokens.="<tr><th>No.</th><th>Subject</th><th>Date</th><th>Options</th></tr>";

	while($tokendata=pg_fetch_array($Ry)) {
		if(in_array($tokendata['teamid'],$cteams)) {
			$i++;

			$future_tokens.="<tr class='".bg_class()."'><td>$tokendata[id]</td><td>$tokendata[name] - ".substr($tokendata['sub'],0,10)."</td><td>$tokendata[nextdate]</td><td><a href='tokens-manage.php?id=$tokendata[id]'>Open</a></td></tr>";
		}
	}

	$future_tokens.="<tr class='".bg_class()."'><td colspan=4>Forwarded Queries: $i</td></tr>";

	if($i==0) {
		$future_tokens="";
	}

	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data.");

	if(pg_numrows($Ry)<1) {
		return "Invalid.";
	}

	$tokendata=pg_fetch_array($Ry);

	if(!(in_array($tokendata['teamid'],$cteams))) {
                return "The query number you typed in does not belong to your team.".seltoken();
	}

	db_conn('cubit');
	$conpos="";

	if($tokendata['csct']=="Customer") {
		$Sl="SELECT accno,balance FROM customers WHERE cusnum='$tokendata[csc]'";
		$Ry=db_exec($Sl) or errDie("Unable to get customer details.");

		if(pg_numrows($Ry)<1) {
			$balance="<li class=err>Invalid Customer</li>";
			$accnum="";
			$ex1="";
			$ex2="";
		} else {
			$cusdata=pg_fetch_array($Ry);
			$balance=$cusdata['balance'];
			$accnum=$cusdata['accno'];
			$ex1="<tr><td colspan=2 align=center><input type=button value='View Customer Details' onclick='openwindow(\"../cust-det.php?cusnum=$tokendata[csc]\")'></td></tr>";
			$ex2="<tr><td colspan=2 align=center><input type=button value='Print Customer Statement' onclick='openwindow(\"../cust-stmnt.php?cusnum=$tokendata[csc]\")'></td></tr>";

		}
	} elseif ($tokendata['csct']=="Supplier") {
		$Sl="SELECT supno,balance FROM suppliers WHERE supid='$tokendata[csc]'";
		$Ry=db_exec($Sl) or errDie("Unable to get customer details.");

		if(pg_numrows($Ry)<1) {
			$balance="<li class=err>Invalid Customer</li>";
			$accnum="";
			$ex1="";
			$ex2="";
		} else {
			$supdata=pg_fetch_array($Ry);
			$balance=$supdata['balance'];
			$accnum=$supdata['supno'];
			$ex1="<tr><td colspan=2 align=center><input type=button value='View Supplier Details' onclick='openwindow(\"../supp-det.php?supid=$tokendata[csc]\")'></td></tr>";
			$ex2="<tr><td colspan=2 align=center><input type=button value='Print Supplier Statement' onclick='openwindow(\"../supp-stmnt.php?supid=$tokendata[csc]\")'></td></tr>";
		}
	} elseif ($tokendata['csct']=="Contact") {
		$Sl="SELECT * FROM cons WHERE id='$tokendata[csc]'";
		$Rt=db_exec($Sl) or errDie("Unable to get data from db.");

		$condata=pg_fetch_array($Rt);

		$balance="0.00";
		$accnum="";
		$ex1="<tr><td colspan=2 align=center><input type=button value='View Contact Details' onclick='openwindow(\"../view_con.php?id=$tokendata[csc]\")'></td></tr>";
		$ex2="";

		$Sl="SELECT accno,balance FROM customers WHERE cusnum='$condata[cust_id]'";
		$Ry=db_exec($Sl) or errDie("Unable to get customer details.");

		if(pg_numrows($Ry)>0) {
			$cusdata=pg_fetch_array($Ry);
			$balance.="<br>Customer balance: ".CUR." $cusdata[balance]";
			$accnum.=$cusdata['accno'];
			$ex1.="<tr><td colspan=2 align=center><input type=button value='View Customer Details' onclick='openwindow(\"../cust-det.php?cusnum=$condata[cust_id]\")'></td></tr>";
			$ex2.="<tr><td colspan=2 align=center><input type=button value='Print Customer Statement' onclick='openwindow(\"../cust-stmnt.php?cusnum=$condata[cust_id]\")'></td></tr>";
		}

                $Sl="SELECT supno,balance FROM suppliers WHERE supid='$condata[supp_id]'";
		$Ry=db_exec($Sl) or errDie("Unable to get customer details.");

		if(pg_numrows($Ry)>0) {
			$supdata=pg_fetch_array($Ry);
			$balance.="<br>Supplier balance: ".CUR." $supdata[balance]";
			$accnum.="/".$supdata['supno'];
			$ex1.="<tr><td colspan=2 align=center><input type=button value='View Supplier Details' onclick='openwindow(\"../supp-det.php?supid=$condata[supp_id]\")'></td></tr>";
			$ex2.="<tr><td colspan=2 align=center><input type=button value='Print Supplier Statement' onclick='openwindow(\"../supp-stmnt.php?supid=$condata[supp_id]\")'></td></tr>";
		}
		if(strlen($tokendata['conpos'])>0) {
			$conpos="<tr class='bg-odd'><td>Position</td><td>$tokendata[conpos]</td></tr>";
		}


	} else {
		return "Invalid.";
	}


	$i=0;

	db_conn('crm');

	$Sl="SELECT * FROM teamlinks WHERE team='$tokendata[teamid]' ORDER BY num";
	$Ry=db_exec($Sl) or errDie("Unable to get teamlinks from system.");

	if(pg_numrows($Ry)<1) {
		$teamlinks="<tr><td>There are no links for this team. Select links under settings, view teams.</td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='team-links.php?id=$tokendata[teamid]'>Select Team Links</a></td></tr>";
	} else {
		$teamlinks="";
		while($linkdata=pg_fetch_array($Ry)) {
			$i++;

			$teamlinks.="<tr class='".bg_class()."'><td align=center><a target=_blank href='$linkdata[script]'>$linkdata[name]</a></td></tr>";
		}
	}
	
	$i=0;
	$pactions="";
	$Sl="SELECT donedate,donetime,action,doneby FROM token_actions WHERE token='$id' ORDER BY id DESC";
	$Ry=db_exec($Sl) or errDie("Unable to get query actions from system.");

	while($pdata=pg_fetch_array($Ry)) {
		$i++;

		$pactions.="<tr class='".bg_class()."'><td>$pdata[donedate], ".substr($pdata['donetime'],0,5)."</td><td>$pdata[action]</td><td>$pdata[doneby]</td></tr>";

	}
	
	$Sl="SELECT name FROM teams WHERE id='$crmdata[teamid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get team.");

	$teamdata=pg_fetch_array($Ry);

	$Sl="SELECT name FROM teams WHERE id='$tokendata[teamid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get team.");

	$tteamdata=pg_fetch_array($Ry);

	if($tokendata['nextdate']>$date) {
		$nextdate="<tr class='bg-even'><td>Next Date</td><td>$tokendata[nextdate]</td></tr>";
	} else {
		$nextdate="";
	}

	$Sl="SELECT action FROM actions ORDER BY action";
	$Ri=db_exec($Sl) or errDie("Unable to get actions.");

	$actions="<select name=action>
	<option value='0'>Input Action</option>";

        while($ad=pg_fetch_array($Ri)) {
		$actions.="<option value='$ad[action]'>$ad[action]</option>";
	}

	$actions.="</select>";

	$Sl="SELECT * FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get categories from system.");

	$cats="<select name=cat>";

	while($cdata=pg_fetch_array($Ry)) {
		if($tokendata['catid']==$cdata['id']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$cats.="<option value='$cdata[id]' $sel>$cdata[name]</option>";
	}

	$cats.="</select>";

	$Sl="SELECT * FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get categories from system.");

	$listcats="<select name=listcat onChange='javascript:document.form.submit();'>";
	$listcats.="<option value='0'>All</option>";

	while($cdata=pg_fetch_array($Ry)) {
		if($crmdata['listcat']==$cdata['id']) {
			$sel="selected";
		} else {
			$sel="";
		}
		$listcats.="<option value='$cdata[id]' $sel>$cdata[name]</option>";
	}

	$listcats.="</select>";

	$Sl="SELECT id,name FROM teams WHERE div='".USER_DIV."' ORDER BY name";
	$Ri=db_exec($Sl) or errDie("Unable to go on holiday.");

	$qteams="<select name=qteam>";

	while($qdata=pg_fetch_array($Ri)) {
                if(in_array($qdata['id'],$cteams)) {
			if($qdata['id']==$tokendata['teamid']) {
				$sel="selected";
			} else {
				$sel="";
			}

			$qteams.="<option value='$qdata[id]' $sel>$qdata[name]</option>";
		}
	}

	$qteams.="</select>";

	// Still to do
	//<tr class='bg-even'><td>Send Fax</td></tr>
        //Works but replaced
	//<tr class='bg-even'><td><input type=button value='Record other action taken &raquo;' onclick='openwindow(\"tokens-action-other.php?id=$id\")'></td></tr>
	$out="$Notes
	<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=750>
	<tr>
		<td colspan=4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr>
			<td>
			<form action='tokens-new.php'>
				<input type=submit value='New Query &raquo;'>
			</form>
			</td>
			<td align=center><h3>Team: $tteamdata[name] | User: $username | Date: $disdate | TOKEN: $id</h3></td>
		</tr>
		</table>
		</td>
	</tr>
	<tr>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value='updatecsc'>
		<input type=hidden name=id value='$id'>
		<td width='22%' valign=top align=center>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Search Queries</th></tr>
		<tr class='bg-odd'><td colspan=2><li><a href='tokens-list-open.php'>List All Open Queries</a></li></td></tr>
		<tr class='bg-even'><td colspan=2><li><a href='tokens-manage.php'>Advanced Search</a></li></td></tr>
		<tr class='bg-odd'><td>Input No</td><td><input name=find type=text size=7></td></tr>
		<tr><td align=right colspan=2><input type=submit name='search' value='Search &raquo;'></td></tr>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='80%'>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-even'><td align='center'><a href='team-links.php?id=$crmdata[teamid]'>Team Links</a></td></tr>
		<tr class='bg-odd'><td align=center><a href='tokens-manage.php'>Manage Queries</a></td></tr>
		<tr class='bg-even'><td align=center><a href='index.php'>My Business</a></td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</a></td></tr>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='95%'>
		<tr><td><br></td></tr>
			<tr>
			<td><input type=button value='Todo List' onclick='openwindow(\"../todo.php\")'></td>
			<td><input type=button value='View Diary' onclick='openwindowbg(\"../diary/diary-index.php\")'></td>
			</tr>
		</table>
		</td>
		<td valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr class='bg-odd'><th>Query Team</th><td>$qteams</td></tr>
		<tr class='bg-even'><th>Query Category</th><td>$cats</td></tr>
		<tr class='bg-odd'><th>SUBJECT/SUMMARY:</th><td><input size=30 type=text name=sub value='$tokendata[sub]'></td></tr>
		<tr><th colspan=2>Query Notes</th></tr>
		<tr class='bg-even'><td colspan=2><textarea cols=50 rows=3 name=notes>$tokendata[notes]</textarea></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Update Query Information &raquo;'></td></tr>
		</table>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Select/Input other action</th></tr>
		<tr class='bg-odd'><td>$actions</td><td><input type=text size=20 name=oaction></td></tr>
		</table>
		</td>
		<td align=center valign=top colspan=2>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='90%'>
		<tr><th>$tteamdata[name] Quick Links</th></tr>
		$teamlinks
		
		</table>
		</td>
	</tr>
	<tr>
		<td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><th>Query Actions</th></tr>
		<tr class='bg-odd'><td><input type=button value='Send Message to User &raquo;' onclick='openwindow(\"message-send.php?id=$id\")'></td></tr>
		<tr class='bg-even'><td><input type=button value='Send E-Mail &raquo;' onclick='openwindow(\"email-send.php?id=$id\")'></td></tr>
		<tr class='bg-odd'><td><input type=button value='Send SMS &raquo;' onclick='openwindow(\"https_face.php?target=sms-send.php?id=$id\")'></td></tr>
		<tr class='bg-odd'><td><input type=button value='Forward to Future Date &raquo;' onclick='openwindow(\"tokens-forward.php?id=$id\")'></td></tr>
		<tr class='bg-even'><td><input type=submit name='closetoken' value='Close Query &raquo;'</td></tr>
		<tr class='bg-odd'><td><input type=button value='Send query to other User/Team &raquo;' onclick='openwindow(\"tokens-pass.php?id=$id\")'></td></tr>
		</table>
		</td>
		<td rowspan=2 valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
                <tr><td><input type=button value='Archive Actions' onclick='openwindow(\"tokens-action-archive.php?id=$id\")'></td><td colspan=2 align=right><input type=button value='View Archived Actions' onclick='openwindow(\"tokens-action-archive-view.php?id=$id\")'></td></tr>
		<tr><td colspan=3 align=center><h4>Actions to date</h4></td></tr>
		<tr><th>Date</th><th>Action</th><th>Done By</th></tr>
		$pactions
		</table>
		</td>
		<td colspan=2 valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><th colspan=2>Query Details</th></tr>
		<tr class='bg-odd'><td>Team & User</td><td>$tteamdata[name], $tokendata[username]</td></tr>
		<tr class='bg-even'><td>Created</td><td>$tokendata[opendate] By: $tokendata[openby]</td></tr>
		<tr class='bg-odd'><td>Last Worked On</td><td>$tokendata[lastdate] By: $tokendata[lastuser]</td></tr>
		$nextdate
		$ex1
		$ex2
		</table>
		</td>
	</tr>
	<tr>
		<td valign=top>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
                <tr><th>Query Criteria</th></tr>
		<tr class='bg-odd'><td>$listcats</td></tr>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		$tokens
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		$future_tokens
		</table>
		</td>
		<td></td>
		<td align=right valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><th colspan=2>$tokendata[csct] Information</th></tr>
		<tr class='bg-odd'><td>Acc Num</td><td>$accnum</td></tr>
		<tr class='bg-even'><td>Name</td><td><input name=name type=text size=20 value='$tokendata[name]'></td></tr>
		<tr class='bg-odd'><td>Contact</td><td><input name=con type=text size=20 value='$tokendata[con]'></td></tr>
		<tr class='bg-even'><td>Tel</td><td><input name=tel type=text size=20 value='$tokendata[tel]'></td></tr>
		<tr class='bg-odd'><td>Cell</td><td><input name=cel type=text size=20 value='$tokendata[cell]'></td></tr>
		<tr class='bg-even'><td>Fax</td><td><input name=fax type=text size=20 value='$tokendata[fax]'></td></tr>
		<tr class='bg-odd'><td>Email</td><td><input name=email type=text size=20 value='$tokendata[email]'></td></tr>
		<tr class='bg-even'><td>Balance</td><td align=right>".CUR." $balance</td></tr>
		$conpos
		<tr><th colspan=2>Address/Notes</th></tr>
		<tr class='bg-odd'><td colspan=2 align=center><textarea name=address rows=5 cols=23>$tokendata[address]</textarea></td></tr>
		<tr><td colspan=2><input type=submit value='Update Query Information &raquo;'></td></tr>
		</table>
		</td>
	</tr>
	</table>";

	return $out;

}

function updatecsc ($_POST) {
	extract($_POST);

        $cat+=0;
	$listcat+=0;
	$qteam+=0;
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 9, "Invalid Query ID.");
	$v->isOk ($sub, "string", 1, 300, "Invalid subject.");
	$v->isOk ($notes, "string", 0, 500, "Invalid notes.");
	$v->isOk ($name, "string", 1, 300, "Invalid name.");
	$v->isOk ($con, "string", 0, 300, "Invalid contact.");
	$v->isOk ($tel, "string", 0, 300, "Invalid tel.");
	$v->isOk ($cel, "string", 0, 300, "Invalid cell.");
	$v->isOk ($fax, "string", 0, 300, "Invalid fax.");
	$v->isOk ($email, "email", 0, 300, "Invalid email.");
	$v->isOk ($address, "string", 0, 300, "Invalid address.");
	$v->isOk ($oaction, "string", 0, 100, "Invalid action .");
	$v->isOk ($action, "string", 0, 100, "Invalid action.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return manage($_POST, $confirm."</li>");
	}

	$date=date("Y-m-d");

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query information from system.");

	if(pg_numrows($Ry)<1) {
		return manage($_POST,"<li class=err>Invalid query</li>");
	}

	$tokendata=pg_fetch_array($Ry);

	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

        $cdata=pg_fetch_array($Ri);

	$teams=explode("|",$cdata['teams']);

	if(!(in_array($tokendata['teamid'],$teams))) {
                return "The query number you typed in does not belong to your team.".seltoken();
	}

	$Sl="SELECT * FROM tcats WHERE id='$cat'";
	$Ry=db_exec($Sl) or errDie("Unable to get cat from system.");

	if(pg_numrows($Ry)<1) {
		return "Invalid cat.";
	}

	$catdata=pg_fetch_array($Ry);

	$catname=$catdata['name'];


	$Sl="UPDATE tokens SET name='$name',con='$con',tel='$tel',cell='$cel',fax='$fax',email='$email',address='$address',cat='$catname',catid='$cat',
	sub='$sub',notes='$notes',lastuser='".USER_NAME."',lastdate='$date',teamid='$qteam' WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to update query information.");

	$Sl="UPDATE crms SET listcat='$listcat' WHERE userid='".USER_ID."'";
	$Ry=db_exec($Sl) or errDie("Unable to update crm.");

        $time=date("H:i:s");

        if(strlen($oaction)>0) {

		$Sl="INSERT INTO token_actions(token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','$oaction','$date','$time','".USER_NAME."','".USER_ID."')";
		$Ry=db_exec($Sl) or errDie("Unable to insert record.");
	}

         if($action!="0") {

		$Sl="INSERT INTO token_actions(token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','$action','$date','$time','".USER_NAME."','".USER_ID."')";
		$Ry=db_exec($Sl) or errDie("Unable to insert record.");
	}

	if(isset($closetoken)) {
		header("Location: tokens-close.php?id=$id");
		exit;
	}

	db_conn('crm');

	if(isset($search)) {
		$find+=0;
		$Sl="SELECT * FROM tokens WHERE id='$find'";
		$Ry=db_exec($Sl) or errDie("Unable to find query.");
		if(pg_numrows($Ry)<1) {
			return manage($_POST,"<li class=err>Query number: '$find' cannnot be found</li>");
		}

		header("Location: tokens-manage.php?id=$find");
		exit;
	}

        header("Location: tokens-manage.php?id=$id");
	exit;
	return manage($_POST,"<li>Query information updated.</li>");
}
?>

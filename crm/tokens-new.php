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
		case "listr":
			$OUTPUT = listr($_POST);
			break;
		case "drop":
			$OUTPUT = drop($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT = select($_POST);
}

require("template.php");

function select($_POST,$errors="") {

	extract($_POST);

	global $_GET;
        extract($_GET);

	if(!(isset($value))) {
		$value='';
	}

	if(!(isset($poken))) {
		$poken=0;
	}

	$poken+=0;

	db_conn('crm');
	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry=db_exec($Sl) or errDie("Unable to get info from db.");

	if(pg_num_rows($Ry)<1) {
		return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
		</table>";
	}

	$crmdata=pg_fetch_array($Ry);

	if($crmdata['teamid']==0)  {
                return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
		</table>";
	}

	$Sl="SELECT * FROM teams WHERE id='$crmdata[teamid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get team data.");

	$teamdata=pg_fetch_array($Ry);

	$username=USER_NAME;
	$disdate=date("d-m-Y, l, G:i");

	$flags="<select name=flag>
	<option value='surname'>Company/Name</option>
	<option value='name'>Contact Name</option>
	<option value='tell'>Tel</option>
	<option value='cell'>Cell</option>
	</select>";

	$types="<select name=type>
	<option value='all'>All</option>
	<option value='cust'>Customers</option>
	<option value='supp'>Suppliers</option>
	</select>";

	$out="$errors
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=750>
	<tr><td colspan=2 align=center><h3>Select Person/Company making enquiry </h3></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='listr'>
	<input type=hidden name=poken value='$poken'>
	<tr><th colspan=2>Search</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>$flags</td><td><input type=text size=20 name=value value='$value'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Type</td><td>$types</td></tr>
	<tr><th colspan='3'>Options</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2 align=center><a href='../new_con.php?crm=yes'>Add Contact</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 align=center><a href='../customers-new.php?crm=yes'>Add Customer</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2 align=center><a href='../supp-new.php?crm=yes'>Add Supplier</a></td></tr>
	<tr><td colspan=3 align=right><input type=submit value='List &raquo;'></td></tr>
	</form>
	</table>
	<p>
                <table border=0 cellpadding='2' cellspacing='1'>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
                </table>";

	return $out;
}

function listr ($_POST) {
	extract($_POST);
	$flag=remval($flag);
	$value=remval($value);

	db_conn('cubit');

	if($type=="cust") {
		$wh="AND cust_id>0 ";
	} elseif($type=="supp") {
		$wh="AND supp_id>0 ";
	} else {
		$wh="";
	}

	$poken+=0;

	$i=0;

	if($flag!="name") {

		$Sl="SELECT * FROM cons WHERE  lower($flag) LIKE lower('%$value%') AND div='".USER_DIV."' $wh ORDER BY surname";
		$Ry=db_exec($Sl) or errDie("Unable to get data.");
		if(pg_num_rows($Ry)<1) {
			return "No contacts were found for the criteria you selected.".select($_POST);
		}

		if(pg_num_rows($Ry)>0) {
			//More than 20 relusts
			$out="<h3>Select Person/Company making enquiry </h3>
			<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th colspan='2'>Options</th>
			</tr>";

			while($cdata=pg_fetch_array($Ry)) {

				$Sl="SELECT * FROM conpers WHERE con='$cdata[id]' ORDER BY name";
				$Rt=db_exec($Sl) or errDie("Unable to get data.");

				$i++;

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$out.="<tr bgcolor='$bgcolor'><td>$cdata[surname]</td><td><a href='tokens-new.php?id=$cdata[id]&poken=$poken'>Add Query</a></td><td><a href='../conper-add.php?id=$cdata[id]&type=contact&crm=yes'>Add new Contact Person</a></td></tr>";

				while ($cpdata=pg_fetch_array($Rt)) {
					$i++;

					$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

					$out.="<tr bgcolor='$bgcolor'><td>*******$cpdata[name]</td><td><a href='tokens-new.php?id=$cdata[id]&conper=$cpdata[id]&poken=$poken'>Add Query</a></td></tr>";
				}
			}
		} else {
			////Less than 20 results
			$out="<h3>Select Person/Company making enquiry </h3>
			<table ".TMPL_tblDflts.">
			<form action ='".SELF."' method=post>
			<input type=hidden name=key value='drop'>
			<input type=hidden name=poken value='$poken'>
			<tr><th>Select Person/Company</th></tr>";

                        $cons="<select name=contact>";

			while($cdata=pg_fetch_array($Ry)) {

				$Sl="SELECT * FROM conpers WHERE con='$cdata[id]' ORDER BY name";
				$Rt=db_exec($Sl) or errDie("Unable to get data.");

				$i++;

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$cons.="<option value='$cdata[id]'>$cdata[surname]</option>";

				while ($cpdata=pg_fetch_array($Rt)) {
					$i++;

					$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

					$cons.="<option value='$cdata[id]|$cpdata[id]'>
						*******$cpdata[name]
					</option>";
				}
			}

			$cons.="</select>";
			$out.="<tr bgcolor='".TMPL_tblDataColor1."'><td>$cons</td></tr>";
			$out.="<tr><td align=right><input type=submit value='Enter query data &raquo;'></td></tr>
			</form>";
		}
	} else {

		$Sl="SELECT * FROM cons WHERE  div='".USER_DIV."' $wh ORDER BY surname";
		$Ry=db_exec($Sl) or errDie("Unable to get data.");
		if(pg_num_rows($Ry)<1) {
			return "No contacts were found for the criteria you selected."
				.select($_POST);
		}

		$out="<h3>Select Person/Company making enquiry </h3>
		<table ".TMPL_tblDflts.">
		<tr>
			<th>Name</th>
			<th>Options</th>
		</tr>";

		while($cdata=pg_fetch_array($Ry)) {

			$Sl="SELECT * FROM conpers WHERE con='$cdata[id]' AND  lower($flag) LIKE lower('%$value%')  ORDER BY name";
			$Rt=db_exec($Sl) or errDie("Unable to get data.");

			if(pg_num_rows($Rt)>0) {
				$i++;

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$out.="<tr bgcolor='$bgcolor'><td>$cdata[surname]</td><td><a href='tokens-new.php?id=$cdata[id]&poken=$poken'>Add Query</a></td><td><a href='../conper-add.php?id=$cdata[id]&type=contact&crm=yes'>||Add new Contact Person</a></td></tr>";

				while ($cpdata=pg_fetch_array($Rt)) {
					$i++;

					$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

					$out.="<tr bgcolor='$bgcolor'><td>*******$cpdata[name]</td><td><a href='tokens-new.php?id=$cdata[id]&conper=$cpdata[id]&poken=$poken'>Add Query</a></td></tr>";
				}
			}
		}
	}

	$out.="</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../new_con.php?crm=yes'>Add Contact</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../customers-new.php?crm=yes'>Add Customer</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../supp-new.php?crm=yes'>Add Supplier</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tokens-new.php?poken=$poken'>Try Again</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	</table>";

	return $out;

}

function drop($_POST) {
	extract($_POST);

	$poken+=0;

	$vals=explode("|",$contact);

	if(isset($vals[1])) {
		$vals[1]+=0;
		$vals[0]+=0;
		header("Location: tokens-new.php?id=$vals[0]&conper=$vals[1]&poken=$poken");
		exit;
	} else {
		header("Location: tokens-new.php?id=$vals[0]&poken=$poken");
		exit;
	}
}


function enter($_POST,$errors="") {

	extract($_POST);
        if(!isset($poken)) {
		$poken=0;
	}

	$poken+=0;

	if(isset($id)) {
		$contact=$id;
	}

	$contact+=0;

	if(!isset($sub)) {
		$sub="";
	}

	if(!isset($notes)) {
		$notes="";
	}

	if(!isset($conper)) {
		$conper=0;
	}

	$conper+=0;

	db_conn('crm');
	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry=db_exec($Sl) or errDie("Unable to get info from db.");

	if(pg_numrows($Ry)<1) {
		return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
		</table>";
	}

	$crmdata=pg_fetch_array($Ry);

	$cteams=explode("|",$crmdata['teams']);
	if(count($cteams)>1) {
        	$teamsel="<select name=team>";

		$Sl="SELECT id,name FROM teams WHERE div='".USER_DIV."' ORDER BY name";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		while($data=pg_fetch_array($Ri)) {

			//if(in_array($data['id'],$cteams)) {
				if($data['id']==$crmdata['teamid']) {
					$sel="selected";
				} else {
					$sel="";
				}
				$teamsel.="<option value='$data[id]' $sel>$data[name]</option>";
			//}
		}
		$teamsel.="</select>";
		$text="<tr bgcolor='".TMPL_tblDataColor2."'><td>Team</td><td colspan=2>$teamsel</td></tr>";
	} else {
		$text="<input type=hidden name=team value=0>";
	}


	$Sl="SELECT * FROM teams WHERE id='$crmdata[teamid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get team data.");

	$teamdata=pg_fetch_array($Ry);

	$username=USER_NAME;
	$disdate=date("d-m-Y, l, G:i");

	$Sl="SELECT id,name FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get cats from db.");

	if(pg_numrows($Ry)<1) {
		return "There are no query categories in the system. Please add them under settings.";
	}

	$cats="<select name=cat>";

	while($catdata=pg_fetch_array($Ry)) {
		$cats.="<option value='$catdata[id]'>$catdata[name]</option>";
	}

	$cats.="</select>";

	db_conn('cubit');

	$Sl="SELECT id,surname,name FROM cons WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get contacts from db.");

	$cdata=pg_fetch_array($Ry);

	if($conper>0) {

		$Sl="SELECT * FROM conpers WHERE id='$conper'";
		$Rc=db_exec($Sl) or errDie("Unable to get data from contacts.");

		$cpdata=pg_fetch_array($Rc);

		$ext="<tr bgcolor='".TMPL_tblDataColor1."'><td>Contact Person</td><td>$cpdata[name]</td></tr>";

	} else {
		$ext="";
	}

	if((strlen($sub)==0)&&(strlen($notes)==0)&&$poken>0) {
		db_conn('crm');
		$Sl="SELECT * FROM pokens WHERE id='$poken'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$pokendata=pg_fetch_array($Ri);

		$sub=$pokendata['sub'];
		$notes=$pokendata['notes'];
	}

	$out="$errors
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=750>
	<tr><td colspan=2 align=center><h3>New Query for: Team: $teamdata[name] | User: $username | Date: $disdate </h3></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='write'>
	<input type=hidden name=contact value='$contact'>
	<input type=hidden name=conper value='$conper'>
	<input type=hidden name=poken value='$poken'>
	<tr><th colspan=3>Equiry From</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Company/Name</td><td colspan=2>$cdata[surname]</td></tr>
	$ext
	<tr><th colspan=3>Query Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Category</td><td colspan=2>$cats</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Subject/Summary</td><td colspan=2><input type=text size=35 name=sub value='$sub'></td></tr>
	$text
	<tr><th colspan=3>Query Notes</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=3><textarea name=notes cols=50 rows=4>$notes</textarea></td></tr>
	<tr><td colspan=2><input type=submit name=unall value='Add as Unallocated and add another &raquo;'></td><td align=right><input type=submit value='Add & Manage &raquo;'></td></tr>
	</form>
	</table>
	<p>
                <table border=0 cellpadding='2' cellspacing='1'>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
                </table>";

	return $out;
}

function write($_POST) {

	extract($_POST);

        $team+=0;
	$contact+=0;
	$conper+=0;
	$cat+=0;
	$sub=remval($sub);
	$notes=remval($notes);
	$supplier=0;
	$customer=0;
	$conpos="";
	$poken+=0;

	$echeck=0;
	if($customer>0) {
		$echeck++;
	}

	if($supplier>0) {
		$echeck++;
	}

	if($contact>0) {
		$echeck++;
	}

	if($echeck!=1) {
		return enter($_POST,"<li class=err>Please select a customer OR a supplier OR a contact</li>");
	}

	if($customer>0) {
		$csct="Customer";
		$tab="customers";
		$wh="cusnum";
		$csc=$customer;
	} elseif($supplier>0) {
		$csct="Supplier";
		$tab="suppliers";
		$wh="supid";
		$csc=$supplier;
	} elseif($contact>0) {
		$csct="Contact";
		$tab="cons";
		$wh="id";
		$csc=$contact;
	} else {
		return enter($_POST,"<li class=err>Please select a customer OR a supplier OR a contact</li>");
	}

	db_conn('crm');
	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry=db_exec($Sl) or errDie("Unable to get crm data.");

	if(pg_numrows($Ry)<1) {
		return enter($_POST);
	}

	$crmdata=pg_fetch_array($Ry);

	db_conn('cubit');
	$Sl="SELECT * FROM $tab WHERE $wh='$csc'";
	$Ry=db_exec($Sl) or errDie("Unable to get $csct details.");

	if(pg_numrows($Ry)<1) {
		return enter($_POST,"Invalid $csct");
	}

	$cscdata=pg_fetch_array($Ry);

	switch($csct) {
		case "Customer":
			$name=$cscdata['cusname']." ".$cscdata['surname'];
			$accnum=$cscdata['accno'];
			$con=$cscdata['contname'];
			$tel=$cscdata['bustel'];
			if(strlen($tel)<1) {
				$tel=$cscdata['tel'];
			}
			$cel=$cscdata['cellno'];
			$fax=$cscdata['fax'];
			$email=$cscdata['email'];
			$address=$cscdata['addr1']."\n".$cscdata['addr2']."\n".$cscdata['addr3'];
			break;

		case "Supplier":
			$name=$cscdata['supname'];
			$accnum=$cscdata['supno'];
			$con=$cscdata['contname'];
			$tel=$cscdata['tel'];
			$cel="";
			$fax=$cscdata['fax'];
			$email=$cscdata['email'];
			$address=$cscdata['supaddr'];
			break;

		case "Contact":
                        $name=$cscdata['name']." ".$cscdata['surname'];
			$accnum=$cscdata['comp'];
			$con="";
			$tel=$cscdata['tell'];
			$cel=$cscdata['cell'];
			$fax=$cscdata['fax'];
			$email=$cscdata['email'];
			$address=$cscdata['padd'];
			break;

		default:
			return enter($_POST);
	}
	db_conn('cubit');

	if($conper>0) {
		$Sl="SELECT * FROM conpers WHERE id='$conper'";
		$Rj=db_exec($Sl) or errDie("Unable to get data.");

		$cpd=pg_fetch_array($Rj);

		$con=$cpd['name'];
		$tel=$cpd['tell'];
		$cel=$cpd['cell'];
		$fax=$cpd['fax'];
		$email=$cpd['email'];
		$address.="\n".$cpd['notes'];
		$conpos=$cpd['pos'];
	}

	$date=date("Y-m-d");

	db_conn('crm');

	$Sl="SELECT * FROM tcats WHERE id='$cat'";
	$Ry=db_exec($Sl) or errDie("Unable to get cat from system.");

	if(pg_numrows($Ry)<1) {
		return "Invalid cat.";
	}

	$catdata=pg_fetch_array($Ry);

	if($poken>0) {
		$Sl="DELETE FROM pokens WHERE id='$poken'";
		$Ri=db_exec($Sl) or errDie("Unable to delete.");
	}

	$catname=$catdata['name'];

	if($team>0) {
		$crmdata['teamid']=$team;
	}

	$checkteams=explode("|",$crmdata['teams']);

	if(!(isset($unall))and((in_array($crmdata['teamid'],$checkteams)))) {

		$Sl="INSERT INTO tokens(userid,username,teamid,cat,catid,openby,opendate,lastdate,nextdate,csct,csc,name,accnum,con,
		tel,cell,fax,email,address,sub,notes,conper,conpos)
		VALUES ('".USER_ID."','".USER_NAME."','$crmdata[teamid]','$catname','$cat','".USER_NAME."','$date','$date','$date',
		'$csct','$csc','$name','$accnum','$con','$tel','$cel','$fax','$email','$address','$sub','$notes','$conper','$conpos')";

		$Ry=db_exec($Sl) or errDie("Unable insert query.");

		$id = pglib_lastid("tokens", "id");

		header("Location: tokens-manage.php?id=$id");
		exit;
	} else {
                $Sl="INSERT INTO tokens(userid,username,teamid,cat,catid,openby,opendate,lastdate,nextdate,csct,csc,name,accnum,con,
		tel,cell,fax,email,address,sub,notes,conper,conpos)
		VALUES ('0','Unallocated','0','$catname','$cat','".USER_NAME."','$date','$date','$date',
		'$csct','$csc','$name','$crmdata[teamid]','$con','$tel','$cel','$fax','$email','$address','$sub','$notes','$conper','$conpos')";

		$Ry=db_exec($Sl) or errDie("Unable insert query.");

		header("Location: tokens-new.php");
		exit;
	}
}

?>

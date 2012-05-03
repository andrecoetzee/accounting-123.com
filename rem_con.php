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
		case "write":
			$OUTPUT = write_data ($_POST);
			break;
		default:
			$OUTPUT = view_data ($_GET);
	}
} else {
	$OUTPUT = view_data ($_GET);
}
# check department-level access

# display output
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
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

  db_conn('cubit');
   $user =USER_NAME;
  # write to db
  $Sql = "SELECT * FROM cons WHERE ((id='$id')and ((con='Yes' and by='$user' AND div = '".USER_DIV."') or(con='No' AND div = '".USER_DIV."')))";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");
  if(pg_numrows($Rslt)<1){return "Contact not Found";}
  $Data = pg_fetch_array($Rslt);



  $date= $Data['date'];





  $mon=substr($date,2,2);

  if ($mon==1){$td=31;$M='January';}
  if ($mon==2){$td=28;$M='February';}
  if ($mon==3){$td=31;$M='March';}
  if ($mon==4){$td=30;$M='April';}
  if ($mon==5){$td=31;$M='May';}
  if ($mon==6){$td=30;$M='June';}
  if ($mon==7){$td=31;$M='July';}
  if ($mon==8){$td=31;$M='August';}
  if ($mon==9){$td=30;$M='September';}
  if ($mon==10){$td=31;$M='October';}
  if ($mon==11){$td=30;$M='November';}                             //        and substr(date,7,4)='$year'
  if ($mon==12){$td=31;$M='December';}


   $Day=substr($date,0,2);
     $Day=$Day+0;
    $Year=substr($date,6,2);

    $Date=$Day." ".$M." "." ".$Year;


   $hadd=$Data['hadd'];
    $padd=$Data['padd'];



	$view_data =
"

<h3>Contact details</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=id value=$id>
<tr><th colspan=2>Personal details</th></tr>
<tr class='bg-odd'><td>Name</td><td align=center>$Data[name]</td></tr>
<tr class='bg-even'><td>Surname</td><td align=center>$Data[surname]</td></tr>
<tr class='bg-odd'><td>Company</td><td align=center>$Data[comp]</td></tr>
<tr class='bg-even'><td>Ref</td><td align=center>$Data[ref]</td></tr>
<tr class='bg-odd'><td>Date added</td><td align=center>$Date</td></tr>
<tr><th colspan=2>Contact details</th></tr>
<tr class='bg-even'><td>Telephone</td><td align=center>$Data[tell]</td></tr>
<tr class='bg-odd'><td>Cellphone</td><td align=center>$Data[cell]</td></tr>
<tr class='bg-even'><td>Facsimile</td><td align=center>$Data[fax]</td></tr>
<tr class='bg-odd'><td>Email</td><td align=center>$Data[email]</td></tr>
<tr><th colspan=2>Physical Address</th></tr>
<tr class='bg-even'><td colspan=2 align=center>".nl2br($hadd)."</td></tr>
<tr><th colspan=2>Postal Address</th></tr>
<tr class='bg-odd'><td colspan=2 align=center>".nl2br($padd)."</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='list_cons.php'>View other contacts</a></td></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $view_data;
}

# confirm new data
function con_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num",0 ,100, "Invalid number.");



	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

        db_conn('cubit');
        $Sql = "DELETE FROM cons WHERE id='$id' AND div = '".USER_DIV."'";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");

	$con_data =
"
<h3>Cantact Removed</h3>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='list_cons.php'>View other contacts</a></td></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>

";
        return $con_data;
}
# write new data
function write_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($name,"string", 1,100, "Invalid name.");
        $v->isOk ($surname,"string",1,100, "Invalid surname.");
        $v->isOk ($comp,"string",0,100, "Invalid company.");
        $v->isOk ($tell,"string",0,100, "Invalid telephone number.");
        $v->isOk ($cell,"string",0 ,100, "Invalid cell number.");
        $v->isOk ($fax,"string",0 ,100, "Invalid fax number.");
        $v->isOk ($email,"email",0 ,100, "Invalid email.");
        $v->isOk ($add1,"string",0 ,100, "Invalid address.");
        $v->isOk ($add2,"string",0 ,100, "Invalid address.");
        $v->isOk ($add3,"string",0 ,100, "Invalid address.");
        $v->isOk ($add4,"string",0 ,100, "Invalid address.");


	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		        return $confirmCust;
	}


  db_conn('cubit');

  # write to db
  $Sql = "INSERT INTO cons (name,surname,comp,ref,tell,cell,fax,email,add1,add2,add3,add4,div) VALUES ('$name','$surname','$comp','$ref','$tell','$cell','$fax','$email','$add1','$add2','$add3','$add4','".USER_DIV."')";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");


	$write_data =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Contact added</th></tr>
<tr class=datacell><td>$name has been added to Cubit.</td></tr>
</table>

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='".SELF."'>Add another contact</a></td></tr>
        <tr class='bg-odd'><td><a href='index_cons.php'>Contacts</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>

";
	return $write_data;
}
?>

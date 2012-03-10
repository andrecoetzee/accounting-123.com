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
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = con_data ($HTTP_POST_VARS);
			break;
                case "list":
			$OUTPUT = con_die ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = view_data ($HTTP_GET_VARS);
	}
} else {
	$OUTPUT = view_data ($HTTP_GET_VARS);
}
# check department-level access

# display output
require ("template.php");
# enter new data
function view_data ($HTTP_GET_VARS)
{
  foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

       if (isset($id)){ $v->isOk ($id,"num", 1,100, "Invalid num.");}

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
  # write to db                    ((id='$id')and ((con='Yes' and by='$user') or(con='No')))
  $Sql = "SELECT * FROM die WHERE ((id='$id') and(ref='$user'))";
  $Rslt = db_exec($Sql) or errDie ("Unable to access database.");
  $numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		return "Entry not found";

	}


  $Data = pg_fetch_array($Rslt);



$time=$Data['start']."-".$Data['last'];

$time=substr($time,0,2).":".substr($time,2,2)."-".substr($time,5,2).":".substr($time,7,2);

$view_data =
"

<h3>Entry Details</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=id value='$id'>
<input type=hidden name=date value='$Data[date]'>
<tr><th colspan=2>Appointment Details</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>For</td><td>$Data[ref]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>By</td><td>$Data[by]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$Data[date]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Time</td><td>$time</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Regarding</td><td>$Data[des]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Date Made</td><td>$Data[made]</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='die_view'>View other diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $view_data;
}

# confirm new data
function con_data ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num",0 ,100, "Invalid number.");
       $v->isOk ($date,"string",0 ,100, "Invalid date.");


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


        $Sql = "SELECT ref FROM die WHERE id='$id'";
	$Rslt = db_exec ($Sql) or errDie ("Unable to access database");
	$numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		return "Entry not found";

	}
        $user=USER_NAME;
        $Data = pg_fetch_array($Rslt);
          if ($Data['ref']!=$user) {
		return "Entry not found";

	}


        $Sql = "DELETE FROM die WHERE id='$id'";
        $Rslt = db_exec($Sql) or errDie ("Unable to access database.");

	$con_data =
"

<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=list>
<input type=hidden name=date value='$date'>
<tr><th>Entry removed</th></tr>
<tr class=datacell><td>The diary entry has been removed</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Diary &raquo;'></td></tr>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='die_view'>View diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";

        return $con_data;
}


 function con_die ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();


      //  $v->isOk ($mon,"num", 2,2, "Invalid month.");
        $v->isOk ($date,"string", 8,8, "Invalid date.");


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






      $user= USER_NAME;



     $mon=substr($date,2,2);
     $year=substr($date,4,4);
     $date=substr($date,2,6);
   //   print"D$date M$mon y$year";

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
   $Month=$M;
       // print $For;exit;
        db_conn('cubit');
        $Sql = "SELECT date,start,last,des,by,made,id FROM die WHERE ref='$user'and substr(date,3,6)='$date' ORDER BY substr(date,1,2) ASC,start";
	$Rslt = db_exec ($Sql) or errDie ("Unable to access database");
	$numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		$Tab = "No more entries in diary for selected month.";

	} else {
        $i=0;
        $Tab = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Date</th><th>Time</th><th>Description</th><th>Made By</th><th>Date Made</th><th>Option</th></tr>
		";



                while ($Data = pg_fetch_array($Rslt)) {

			if ($i % 2) {                                                              // every other row gets a diff color
				$bgColor = TMPL_tblDataColor1;
			} else {
				$bgColor = TMPL_tblDataColor2;
			}
                        $i=$i+1;
                        $date = $Data['date'];
                        $day=substr($date,0,2);
                        $day=$day+0;
                        $Date = $Data['made'];
                        $mon=substr($date,2,2);
                        $Day=substr($Date,0,2);
                        $Year=substr($Date,6,2);
                        $Day=$Day+0;




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
                         if ($mon==11){$td=30;$M='November';}
                         if ($mon==12){$td=31;$M='December';}

                        $M=substr($M,0,3);

                       // print "day:$day<br> mon:$mon<br> year:$year<br><br>";
                      $time=$Data['start']."-".$Data['last'];
                      $time=substr($time,0,2).":".substr($time,2,2)."-".substr($time,5,2).":".substr($time,7,2);




			$Tab .= "<tr bgcolor='$bgColor'><td>$day</td><td>$time</td><td>$Data[des]</td><td>$Data[by]</td><td>$Day $M $Year</td><td><a href='rem_die.php?id=$Data[id]'>Remove</td></tr>";
		};}
		$Tab .= "</table>";









	$con_die =
"
<h3>$user's Diary for $Month $year</h3>

$Tab

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='die_view'>View another date</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $con_die;
}

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

header("Location: diary/diary-index.php");
exit;

require ("settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = wri_die ($_POST);
			break;
		default:
			$OUTPUT = con_die ();
	}
} else {
	$OUTPUT = con_die ();
}

// print  USER_NAME;
# display output






require ("template.php");
# enter new data


# confirm new data
function con_die ()
{

      $user= USER_NAME;



    $mon=date("m");
    $day=date("d");  //if (strlen($day)<2){$day="0".$day;}
     $year=date("Y");
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
       // print $For;exit;                                                            and substr(date,1,2)=$day and substr(date,3,2)='$mon' and substr(date,5,4)='$year'
        db_conn('cubit');
        $Sql = "SELECT date,start,last,des,by,made,id FROM die WHERE ref='$user' and substr(date,1,2)='$day'and substr(date,3,2)='$mon'and substr(date,5,4)='$year' ORDER BY substr(date,1,2) ASC,start";
	$Rslt = db_exec ($Sql) or errDie ("Unable to access database");
	$numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		$Tab = "No entries in diary for selected month.";

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






   $year =date("Y");


	$con_die =
"
<h3>$user's Diary for $day $Month $year</h3>

$Tab

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr class='bg-odd'><td><a href='die_view'>View another date</a></td></tr>
        <tr class='bg-odd'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $con_die;
}
# write new data
function wri_die ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($For,"string", 1,200, "Invalid for.");
        $v->isOk ($des,"string", 1,200, "Invalid description.");
        $v->isOk ($time,"num", 2,2, "Invalid time.");
        $v->isOk ($user,"string", 1,50, "Invalid user.");
        $v->isOk ($date,"num", 8,8, "Invalid date.");
        $v->isOk ($Date,"num", 8,8, "Invalid date.");
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
        $Sql = "INSERT INTO die(ref,date,time,des,by,made) VALUES ('$For','$date','$time','$des','$user','$Date')";
	$Rslt = db_exec ($Sql) or errDie ("Unable to add to database.", SELF);
	if (pg_cmdtuples ($Rslt) < 1) {
		return "Unable to access database.";
	}

	$wri_die =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Appointment made</th></tr>
<tr class=datacell><td>An appointment has been added to $For's diary</td></tr>
</table>

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='".SELF."'>Make another appointment</a></td></tr>
        <tr class='bg-odd'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>

";
	return $wri_die;
}
?>

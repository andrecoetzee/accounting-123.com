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
			$OUTPUT = con_die ($_POST);
			break;
		case "write":
			$OUTPUT = wri_die ($_POST);
			break;
		default:
			$OUTPUT = get_die ();
	}
} else {
	$OUTPUT = get_die ();
}

// print  USER_NAME;
# display output






require ("template.php");
# enter new data
function get_die ()
{

       $For= USER_NAME;


   /*  $Whe="WHERE username='$user'";


        db_conn('cubit');

        $users = "<select size=1 name=For >";
	$sql = "SELECT username FROM users $Whe ORDER BY username";
	$ServRslt = db_exec ($sql) or errDie ("Unable to select users from database.");
	if (pg_numrows ($ServRslt) < 1) {return "No users found in database.";}
	while ($namesA = pg_fetch_array ($ServRslt)) {
	$users .= "<option value='$namesA[username]'>$namesA[username]</option>\n";}
	$users .= "</select>\n";

   */
       $m=date("m");  //  print $m;exit;
       if ($m==1) {$s1='selected';} else {$s1='';}
       if ($m==2) {$s2='selected';} else {$s2='';}
       if ($m==3) {$s3='selected';} else {$s3='';}
       if ($m==4) {$s4='selected';} else {$s4='';}
       if ($m==5) {$s5='selected';} else {$s5='';}
       if ($m==6) {$s6='selected';} else {$s6='';}
       if ($m==7) {$s7='selected';} else {$s7='';}
       if ($m==8) {$s8='selected';} else {$s8='';}
       if ($m==9) {$s9='selected';} else {$s9='';}
       if ($m==10) {$s10='selected';} else {$s10='';}
       if ($m==11) {$s11='selected';} else {$s11='';}
       if ($m==12) {$s12='selected';} else {$s12='';}



        $mons =" <select size=1 name=mon>;
                      <option $s1 value='01'>January</option>
                      <option $s2 value='02'>February</option>
                      <option $s3 value='03'>March</option>
                      <option $s4 value='04'>April</option>
                      <option $s5 value='05'>May</option>
                      <option $s6 value='06'>June</option>
                      <option $s7 value='07'>July</option>
                      <option $s8 value='08'>August</option>
                      <option $s9 value='09'>September</option>
                      <option $s10 value='10'>October</option>
                      <option $s11 value='11'>November</option>
                      <option $s12 value='12'>December</option>
                      </select>";

        $y=date("Y");  //    print $y;exit;
       if ($y==2004) {$s4='selected';} else {$s4='';}
       if ($y==2005) {$s5='selected';} else {$s5='';}
       if ($y==2006) {$s6='selected';} else {$s6='';}
       if ($y==2007) {$s7='selected';} else {$s7='';}
       if ($y==2008) {$s8='selected';} else {$s8='';}
       if ($y==2009) {$s9='selected';} else {$s9='';}
       if ($y==2010) {$s10='selected';} else {$s10='';}
       if ($y==2011) {$s11='selected';} else {$s11='';}
       if ($y==2012) {$s12='selected';} else {$s12='';}
       if ($y==2013) {$s13='selected';} else {$s13='';}
       if ($y==2014) {$s14='selected';} else {$s14='';}
       if ($y==2015) {$s15='selected';} else {$s15='';}
       if ($y==2016) {$s16='selected';} else {$s16='';}
       if ($y==2017) {$s17='selected';} else {$s17='';}
       if ($y==2018) {$s18='selected';} else {$s18='';}
       if ($y==2019) {$s19='selected';} else {$s19='';}
       if ($y==2020) {$s20='selected';} else {$s20='';}
       if ($y==2021) {$s21='selected';} else {$s21='';}
       if ($y==2022) {$s22='selected';} else {$s22='';}
       if ($y==2023) {$s23='selected';} else {$s23='';}




      $years =" <select size=1 name=year>;
                      <option value='2003'>2003</option>
                      <option $s4 value='2004'>2004</option>
                      <option $s5 value='2005'>2005</option>
                      <option $s6 value='2006'>2006</option>
                      <option $s7 value='2007'>2007</option>
                      <option $s8 value='2008'>2008</option>
                      <option $s9 value='2009'>2009</option>
                      <option $s10 value='2010'>2010</option>
                      <option $s11 value='2011'>2011</option>
                      <option $s12 value='2012'>2012</option>
                      <option $s13 value='2013'>2013</option>
                      <option $s14 value='2014'>2014</option>
                      <option $s15 value='2015'>2015</option>
                      <option $s16 value='2016'>2016</option>
                      <option $s17 value='2017'>2017</option>
                      <option $s18 value='2018'>2018</option>
                      <option $s19 value='2019'>2019</option>
                      <option $s20 value='2020'>2020</option>
                      <option $s21 value='2021'>2021</option>
                      <option $s22 value='2022'>2022</option>
                      <option $s23 value='2023'>2023</option>
                      </select>";


    $days =" <select size=1 name=day>
                      <option value='00'>All</option>
                      <option value='01'>1</option>
                      <option value='02'>2</option>
                      <option value='03'>3</option>
                      <option value='04'>4</option>
                      <option value='05'>5</option>
                      <option value='06'>6</option>
                      <option value='07'>7</option>
                      <option value='08'>8</option>
                      <option value='09'>9</option>
                      <option value='10'>10</option>
                      <option value='11'>11</option>
                      <option value='12'>12</option>
                      <option value='13'>13</option>
                      <option value='14'>14</option>
                      <option value='15'>15</option>
                      <option value='16'>16</option>
                      <option value='17'>17</option>
                      <option value='18'>18</option>
                      <option value='19'>19</option>
                      <option value='20'>20</option>
                      <option value='21'>21</option>
                      <option value='22'>22</option>
                      <option value='23'>23</option>
                      <option value='24'>24</option>
                      <option value='25'>25</option>
                      <option value='26'>26</option>
                      <option value='27'>27</option>
                      <option value='28'>28</option>
                      <option value='29'>29</option>
                      <option value='30'>30</option>
                      <option value='31'>31</option>
                      </select>";


	$get_die =
"
<h3>View Diary</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=For value='$For'>
<tr><th colspan=2>Select Date</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2 align=center>
<table border=0 cellpadding=1 cellspacing=1>
	<tr><td>
		$days
	</td><td>
		$mons
	</td><td>
		$years
	</td></tr>
	</table>
</td></tr>
<tr><td colspan=2 align=right><input type=submit value='View &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $get_die;
}

# confirm new data
function con_die ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($For,"string", 1,200, "Invalid for.");
        $v->isOk ($mon,"num", 2,2, "Invalid month.");
        $v->isOk ($day,"num", 2,2, "Invalid day.");
        $v->isOk ($year,"num", 4,4, "Invalid year.");


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

      if ($user!=$For)  {return "Sorry for you! You do not have permission to view $For's diary";}






  if ($mon==1){$M='January';}
  if ($mon==2){$M='February';}
  if ($mon==3){$M='March';}
  if ($mon==4){$M='April';}
  if ($mon==5){$M='May';}
  if ($mon==6){$M='June';}
  if ($mon==7){$M='July';}
  if ($mon==8){$M='August';}
  if ($mon==9){$M='September';}
  if ($mon==10){$M='October';}
  if ($mon==11){$M='November';}                             //        and substr(date,7,4)='$year'
  if ($mon==12){$M='December';}
   $Month=$M;
       // print $For;exit;






        if ($day=="00") {


        db_conn('cubit');
        $Sql = "SELECT date,start,last,des,by,made,id FROM die WHERE ref='$For'and substr(date,3,2)='$mon' and substr(date,5,4)='$year' ORDER BY substr(date,1,2) ASC,start";
	$Rslt = db_exec ($Sql) or errDie ("Unable to access database");
	$numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		$Tab = "No entries in diary for selected date.";

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
<h3>$For's Diary for $Month $year</h3>

$Tab

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>View another date</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";





             }    else {
        if (!(checkdate($mon,$day,$year))) {return "Please go back and select a valid date.<p><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct date'>";}

        ##################################
        db_conn('cubit');
        $Sql = "SELECT date,start,last,des,by,made,id FROM die WHERE ref='$For'and substr(date,3,2)='$mon' and substr(date,1,2)='$day' and substr(date,5,4)='$year' ORDER BY substr(date,1,2) ASC,start";
	$Rslt = db_exec ($Sql) or errDie ("Unable to access database");
	$numrows = pg_numrows($Rslt);

	if ($numrows < 1) {
		$Tab = "No entries in diary for selected date.";

	} else {
        $i=0;
        $Tab = "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Time</th><th>Description</th><th>Made By</th><th>Date Made</th><th>Option</th></tr>
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



			$Tab .= "<tr bgcolor='$bgColor'><td>$time</td><td>$Data[des]</td><td>$Data[by]</td><td>$Day $M $Year</td><td><a href='rem_die.php?id=$Data[id]'>Remove</td></tr>";
		};}
		$Tab .= "</table>";









	$con_die =
"
<h3>$For's Diary for $day $Month $year</h3>

$Tab

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>View another date</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";





             }




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
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Make another appointment</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>

";
	return $wri_die;
}
?>

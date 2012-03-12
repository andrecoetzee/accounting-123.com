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


       $user= USER_NAME;
       $dep = USER_DPT;



     // if (($dep=='Administrator') or ($dep=='Personal Assistant') or ($user=='Admin')) {$Whe ="";} else {return "You do not have permissoin to ;}



        db_conn('cubit');

        $users = "<select size=1 name=For style='width: 95%'>
             <option value='All'>All</option>
             <option value='Administrator'>Administrator</option>
             <option value='Case officer'>Case officer</option>
             <option value='Background Officer'>Background Officer</option>
             <option value='Financial Officer'>Financial Officer</option>
             <option value='Information Officer'>Information Officer</option>
             <option value='Personal Assistant'>Personal Assistant</option>
             <option value='Personnel Department'>Personnel Department</option>";



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
      $day=date("d");

    if ($day==1) {$s1='selected';} else {$s1='';}
    if ($day==2) {$s2='selected';} else {$s2='';}
    if ($day==3) {$s3='selected';} else {$s3='';}
    if ($day==4) {$s4='selected';} else {$s4='';}
    if ($day==5) {$s5='selected';} else {$s5='';}
    if ($day==6) {$s6='selected';} else {$s6='';}
    if ($day==7) {$s7='selected';} else {$s7='';}
    if ($day==8) {$s8='selected';} else {$s8='';}
    if ($day==9) {$s9='selected';} else {$s9='';}
    if ($day==10) {$s10='selected';} else {$s10='';}
    if ($day==11) {$s11='selected';} else {$s11='';}
    if ($day==12) {$s12='selected';} else {$s12='';}
    if ($day==13) {$s13='selected';} else {$s13='';}
    if ($day==14) {$s14='selected';} else {$s14='';}
    if ($day==15) {$s15='selected';} else {$s15='';}
    if ($day==16) {$s16='selected';} else {$s16='';}
    if ($day==17) {$s17='selected';} else {$s17='';}
    if ($day==18) {$s18='selected';} else {$s18='';}
    if ($day==19) {$s19='selected';} else {$s19='';}
    if ($day==20) {$s20='selected';} else {$s20='';}
    if ($day==21) {$s21='selected';} else {$s21='';}
    if ($day==22) {$s22='selected';} else {$s22='';}
    if ($day==23) {$s23='selected';} else {$s23='';}
    if ($day==24) {$s24='selected';} else {$s24='';}
    if ($day==25) {$s25='selected';} else {$s25='';}
    if ($day==26) {$s26='selected';} else {$s26='';}
    if ($day==27) {$s27='selected';} else {$s27='';}
    if ($day==28) {$s28='selected';} else {$s28='';}
    if ($day==29) {$s29='selected';} else {$s29='';}
    if ($day==30) {$s30='selected';} else {$s30='';}
    if ($day==31) {$s31='selected';} else {$s31='';}





       $days =" <select size=1 name=day>;
                      <option $s1 value='01'>1</option>
                      <option $s2 value='02'>2</option>
                      <option $s3 value='02'>2</option>
                      <option $s4 value='04'>4</option>
                      <option $s5 value='05'>5</option>
                      <option $s6 value='06'>6</option>
                      <option $s7 value='07'>7</option>
                      <option $s8 value='08'>8</option>
                      <option $s9 value='09'>9</option>
                      <option $s10 value='10'>10</option>
                      <option $s11 value='11'>11</option>
                      <option $s12 value='12'>12</option>
                      <option $s13 value='13'>13</option>
                      <option $s14 value='14'>14</option>
                      <option $s15 value='15'>15</option>
                      <option $s16 value='16'>16</option>
                      <option $s17 value='17'>17</option>
                      <option $s18 value='18'>18</option>
                      <option $s19 value='19'>19</option>
                      <option $s20 value='20'>20</option>
                      <option $s21 value='21'>21</option>
                      <option $s22 value='22'>22</option>
                      <option $s23 value='23'>23</option>
                      <option $s24 value='24'>24</option>
                      <option $s25 value='25'>25</option>
                      <option $s26 value='26'>26</option>
                      <option $s27 value='27'>27</option>
                      <option $s28 value='28'>28</option>
                      <option $s29 value='29'>29</option>
                      <option $s30 value='30'>30</option>
                      <option $s31 value='31'>31</option>
                      </select>";


                   $shours =" <select size=1 name=shour>
                      <option value='01'>1:</option>
                      <option value='02'>2:</option>
                      <option value='03'>3:</option>
                      <option value='04'>4:</option>
                      <option value='05'>5:</option>
                      <option value='06'>6:</option>
                      <option value='07'>7:</option>
                      <option value='08'>8:</option>
                      <option selected value='09'>9:</option>
                      <option value='10'>10:</option>
                      <option value='11'>11:</option>
                      <option value='12'>12:</option>
                      <option value='13'>13:</option>
                      <option value='14'>14:</option>
                      <option value='15'>15:</option>
                      <option value='16'>16:</option>
                      <option value='17'>17:</option>
                      <option value='18'>18:</option>
                      <option value='19'>19:</option>
                      <option value='20'>20:</option>
                      <option value='21'>21:</option>
                      <option value='22'>22:</option>
                      <option value='23'>23:</option>
                      <option value='24'>24:</option>
                      </select>";




             $smins =" <select size=1 name=smin>
                      <option value='00'>00</option>
                      <option value='15'>15</option>
                      <option value='30'>30</option>
                      <option value='45'>45</option>
                      </select>";




       $lhours =" <select size=1 name=lhour>
                      <option value='01'>1:</option>
                      <option value='02'>2:</option>
                      <option value='03'>3:</option>
                      <option value='04'>4:</option>
                      <option value='05'>5:</option>
                      <option value='06'>6:</option>
                      <option value='07'>7:</option>
                      <option value='08'>8:</option>
                      <option selected value='09'>9:</option>
                      <option value='10'>10:</option>
                      <option value='11'>11:</option>
                      <option value='12'>12:</option>
                      <option value='13'>13:</option>
                      <option value='14'>14:</option>
                      <option value='15'>15:</option>
                      <option value='16'>16:</option>
                      <option value='17'>17:</option>
                      <option value='18'>18:</option>
                      <option value='19'>19:</option>
                      <option value='20'>20:</option>
                      <option value='21'>21:</option>
                      <option value='22'>22:</option>
                      <option value='23'>23:</option>
                      <option value='24'>24:</option>
                      </select>";




             $lmins =" <select size=1 name=lmin>
                      <option value='00'>00</option>
                      <option value='15'>15</option>
                      <option value='30'>30</option>
                      <option value='45'>45</option>
                      </select>";






	$get_die =
"
<h3>New Appointment</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<tr><th colspan=2>Appointment details</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Group</td><td align=center>$users</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td align=center><input type=text size=20 name=des></td></tr>

<tr><th colspan=2>Appointment Time</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><th align=left>From</th><td align=center>
  <table border=0 cellpadding=1 cellspacing=1>
	<tr>
        <td>$shours</td>
        <td>$smins</td>
        </tr>
  </table>
</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><th align=left>To</th><td align=center>
  <table border=0 cellpadding=1 cellspacing=1>
	<tr>

        <td>$lhours</td>
        <td>$lmins</td>
        </tr>
  </table>
</td></tr>

<tr><th colspan=2>Appointment Date</th></tr>
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
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td><a href='index.php'>Main Menu</a></td></tr>
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
        $v->isOk ($des,"string", 1,200, "Invalid description.");
        $v->isOk ($shour,"num", 2,2, "Invalid start hour.");
        $v->isOk ($smin,"num", 2,2, "Invalid start mins.");
        $v->isOk ($lhour,"num", 2,2, "Invalid last hour.");
        $v->isOk ($lmin,"num", 2,2, "Invalid last mins.");
        $v->isOk ($day,"num", 2,2, "Invalid day.");
        $v->isOk ($mon,"num", 2,2, "Invalid month.");
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


      $date=$day.$mon.$year;

      $user= USER_NAME;


       $Date=date("dmY");


      if (!(checkdate($mon,$day,$year))) {return "Please go back and select a valid date.<p><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct date'>";}


  $start=$shour.$smin;
     $last=$lhour.$lmin;

      if ($last<=$start)  {return "Please go back and select a valid time.<p><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct time'>";}







	$con_die =
"
<h3>Confirm Appointment</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key       value=write>
<input type=hidden name=For      value='$For'>
<input type=hidden name=des       value='$des'>
<input type=hidden name=date      value='$date'>
<input type=hidden name=Date      value='$Date'>
<input type=hidden name=start     value='$start'>
<input type=hidden name=last      value='$last'>
<input type=hidden name=user      value='$user'>

<tr><th colspan=2>Appointment Details</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>For</td>        <td>$For</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Description</td><td>$des</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td>       <td>$date</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Time</td>       <td>$start-$last</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Made by</td>    <td>$user</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Date made</td>  <td>$Date</td></tr>

<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>Main Menu</a></td></tr>
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
        $v->isOk ($user,"string", 1,50, "Invalid user.");
        $v->isOk ($date,"num", 8,8, "Invalid date.");
        $v->isOk ($Date,"num", 8,8, "Invalid date.");
        $v->isOk ($start,"num", 4,4, "Invalid start.");
        $v->isOk ($last,"num", 4,4, "Invalid last.");
	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		        return $confirmCust;
	}



        if ($For=="All") {$Whe="";}
        if ($For=="Administrator") {$Whe="WHERE depart='Administrator'";}
        if ($For=="Case officer") {$Whe="WHERE depart='Case officer'";}
        if ($For=="Background Officer") {$Whe="WHERE depart='Background Officer'";}
        if ($For=="Financial Officer") {$Whe="WHERE depart='Financial Officer'";}
        if ($For=="Information Officer") {$Whe="WHERE depart='Information Officer'";}
        if ($For=="Personal Assistant") {$Whe="WHERE depart='Personal Assistant'";}
        if ($For=="Personnel Department") {$Whe="WHERE depart='Personnel Department'";}



        db_conn('cubit');

        $sql = "SELECT username FROM users $Whe ORDER BY username";
	$ServRslt = db_exec ($sql) or errDie ("Unable to select users from database.");
	if (pg_numrows ($ServRslt) < 1) {return "There are no users in that group.<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Make another group appointment</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>Main Menu</a></td></tr>
	</table>";}
	while ($namesA = pg_fetch_array ($ServRslt)) {
           $for = $namesA['username'];

          db_conn('ain');

        # write to db
        $Sql = "INSERT INTO die(ref,date,start,last,des,by,made) VALUES ('$for','$date','$start','$last','$des','$user','$Date')";
	$Rslt = db_exec ($Sql) or errDie ("Unable to add to database.", SELF);
	if (pg_cmdtuples ($Rslt) < 1) {
		return "Unable to access database.";
	}



        ;}



        if ($For=="All") {$Whe="";}
        if ($For=="Administrator") {$Whe=" Administrator";}
        if ($For=="Case officer") {$Whe=" Case officer";}
        if ($For=="Background Officer") {$Whe=" Background Officer";}
        if ($For=="Financial Officer") {$Whe=" Financial Officer";}
        if ($For=="Information Officer") {$Whe=" Information Officer";}
        if ($For=="Personal Assistant") {$Whe=" Personal Assistant";}
        if ($For=="Personnel Department") {$Whe=" Personnel Department";}




	$wri_die =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Appointment made</th></tr>
<tr class=datacell><td>An appointment has been added to all$Whe diaries</td></tr>
</table>

<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Make another group appointment</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>Main Menu</a></td></tr>
	</table>

";
	return $wri_die;
}
?>

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
		case "confirm":
			$OUTPUT = con_data ($_POST);
			break;
		case "write":
			$OUTPUT = write_data ($_POST);
			break;
		default:
			$OUTPUT = get_data ($_GET);
	}
} else {
	$OUTPUT = get_data ($_GET);
}

# display output
require ("template.php");
# enter new data
function get_data ($_GET)
{

foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($day,"num", 1,2, "Invalid day.");
	$v->isOk ($month,"num", 1,2, "Invalid month.");
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

	db_conn('cubit');

	$Out="<tr><td><table cellpadding='1' cellspacing='0'>";

	if (strlen($day)<2) {$day="0"."$day";}
	if (strlen($month)<2) {$month="0"."$month";}
	$date=$year.$month.$day;
	$datefor=$date;
	$userfor=USER_NAME;
	$h=0;
	$i=0;
	while ($h<24)
	{
		if ($h<10) {$h ="0".$h;}
		$m=0;
		if ($h==8 or $h==16) {$Out .="</table></td><td><table cellpadding='1' cellspacing='0'>";}
		while ($m<60)
		{
		if ($m==0) {$m="00";}
		$i++;
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$t="$h"."$m";

		$Sl = "SELECT des FROM die WHERE datefor='$datefor' AND time='$t' AND userfor='global'";
		$Rs = db_exec($Sl) or errDie ("Unable to access database.");
		$Data = pg_fetch_array($Rs);

		//Dropdown of reminder options
		$remops = "<select name=remops[$t]>";
		$remops .= "<option value='0'>Same Time</option>";
		$remops .= "<option value='1'>5 Mins</option>";
		$remops .= "<option value='2'>10 Mins</option>";
		$remops .= "<option value='3'>15 Mins</option>";
		$remops .= "<option value='4'>30 Mins</option>";
		$remops .= "<option value='5'>1 Hour</option>";
		$remops .= "<option value='6'>1 Day</option>";
		$remops .=  "</select>";


		$Out .="<tr bgcolor='$bgColor'><td>$h:$m</td><td align=center><table><tr><td><input type=text size=15 name=$t value='$Data[des]'></td><td>$remops</td></tr></table></td></tr>";
		$m=$m+30;
		}
	$h++;
	}

	if ($month==1){$td=31;$mname='January';}
	if ($month==2){$td=28;$mname='February';}
	if ($month==3){$td=31;$mname='March';}
	if ($month==4){$td=30;$mname='April';}
	if ($month==5){$td=31;$mname='May';}
	if ($month==6){$td=30;$mname='June';}
	if ($month==7){$td=31;$mname='July';}
	if ($month==8){$td=31;$mname='August';}
	if ($month==9){$td=30;$mname='September';}
	if ($month==10){$td=31;$mname='October';}
	if ($month==11){$td=30;$mname='November';}
	if ($month==12){$td=31;$mname='December';}

	$day=$day+0;
	$nicedate=$day." "."$mname"." ".$year;


	$get_data =
	"<table cellpadding='0' cellspacing='0'>
	 <form action='".SELF."' method=post>
	 <input type=hidden name=key value=confirm>
	 <input type=hidden name=date value='$date'>
	 <tr><th colspan=3>Dairy for: $nicedate</th></tr>
	 $Out
	 </table></td></tr>
	 <tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	 </form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <tr><th>Quick Links</th></tr>
	 <tr class='bg-odd'><td><a href='glodiary.php'>Global Diary</a></td></tr>
	 <tr class='bg-odd'><td><a href='index_die.php'>Diary</td>
	 <tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $get_data;
}

# confirm new data
function con_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = remval($value);
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

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
	$Out="<tr><td><table cellpadding='1' cellspacing='0'>";

	$h=0;
	$i=0;

 while ($h<24)
	{
		if ($h<10) {$h ="0".$h;}
		$m=0;
		if ($h==8 or $h==16) {$Out .="</table></td><td><table cellpadding='1' cellspacing='0'>";}
		while ($m<60)
		{
		if ($m==0) {$m="00";}
		$i++;
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$t="$h"."$m";
		$tt =$$t;
		$rem0=$remops[$t];

		$rem_year=substr($date,0,4);
		$rem_month=substr($date,4,2);
		$rem_day=substr($date,6,2);
		$rem_min=$m;
		$rem_hour=$h;

		switch ($rem0) {
		case "0":

			break;
		case "1":
			$rem_min=$rem_min-5;
			break;
		case "2":
			$rem_min=$rem_min-10;
			break;
		case "3":
			$rem_min=$rem_min-15;
			break;
		case "4":
			$rem_min=$rem_min-30;
			break;
		case "5":
			$rem_hour=$rem_hour-1;
			break;
		case "6":
			$rem_day=$rem_day-1;
			break;
		default:
		exit;
		}
		if ($rem_min<0) {$rem_min=$rem_min+60;$rem_hour=$rem_hour-1;}
		if ($rem_hour<0) {$rem_hour=$rem_hour+24;$rem_day=$rem_day-1;}
		if ($rem_day<0) {$rem_day=30;$rem_month=$rem_month-1;}
		if ($rem_month<0) {$rem_month=$rem_month+12;$rem_year=$rem_year-1;}
		$remdate = "$rem_year-$rem_month-$rem_day";
		$remtime = "$rem_hour"."$rem_min";

		$Out .="<input type=hidden size=25 name=rem_Options[$t] value='$rem0'><tr bgcolor='$bgColor'><td width='15%'>$h:$m) </td><td><table cellpadding='0' cellspacing='0'><tr><td><input type=hidden size=25 name=$t value='$tt'>$tt</td><td><input type=hidden name='remops[$t]' value='$remdate $remtime'>$remdate $remtime</td></tr></table></td></tr>";

		$m=$m+30;
		}
	$h++;
	}


	$get_data =
	"<table cellpadding='0' cellspacing='0'>
	 <form action='".SELF."' method=post>
	 <input type=hidden name=key value=write>
	 <input type=hidden name=date value='$date'>
	 <tr><th colspan=3>Dairy: $date</th></tr>
	 $Out
	 </table></td></tr>
	 <tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	 </form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <tr><th>Quick Links</th></tr>
	 <tr class='bg-odd'><td><a href='glodiary.php'>Global Diary</a></td></tr>
	 <tr class='bg-odd'><td><a href='index_die.php'>Diary</td>
	 <tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $get_data;
	
	
	
	

}
# write new data
function write_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = remval($value);
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	


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

	pglib_transaction("begin");

	$h=0;
	$datemade=date("Y-m-d");
	$datefor=$date;
	$userfor=USER_NAME;

	$Sl = "DELETE FROM die WHERE datefor='$datefor' AND userfor='global'";
	$Rs = db_exec($Sl) or errDie ("Unable to access database.");

	while ($h<24)
	{
		if ($h<10) {$h ="0".$h;}
		$m=0;
		while ($m<60)
		{
			if ($m==0) {$m="00";}
			$t="$h"."$m";
			$tt =$$t;
			$time=$t;
			$des=$tt;
			if (strlen($des)>0)
			{
				$rem_date=substr($remops[$t],0,10);
				$rem_time=substr($remops[$t],11,4);
				//print "date: $rem_date time: $rem_time<br>";

				$Sl = "INSERT INTO die (datemade,datefor,userfor,time,des,remop,remdate,remtime,rem) VALUES ('$datemade','$datefor','global','$time','$des','$rem_Options[$t]','$rem_date','$rem_time','0')";
				$Rs = db_exec($Sl) or errDie ("Unable to access database.");
			}
			$m=$m+30;
		}
		$h++;
	}
	pglib_transaction("commit");
	
	header ("Location: glodiary.php");
	exit;

	$write_data =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	 <tr><th>Diary modified</th></tr>
	 <tr class=datacell><td>Diary has been modified.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <tr><th>Quick Links</th></tr>
	 <tr class='bg-odd'><td><a href='glodiary.php'>Global Diary</a></td></tr>
	 <tr class='bg-odd'><td><a href='index_die.php'>Diary</td>
	 <tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";
	return $write_data;
}
?>

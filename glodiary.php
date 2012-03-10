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

# get settings
require("settings.php");


# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
                case "account_info":
			$OUTPUT = account_info($HTTP_POST_VARS);
			break;
                default:
			$OUTPUT = order($HTTP_POST_VARS);
	}
} elseif (isset($HTTP_GET_VARS["month"])) {
        if (isset($HTTP_GET_VARS["month"])) {$HTTP_POST_VARS["month"]=$HTTP_GET_VARS["month"];} else {exit;}
	if (isset($HTTP_GET_VARS["year"])) {$HTTP_POST_VARS["year"]=$HTTP_GET_VARS["year"];} else {exit;}
	$OUTPUT = order($HTTP_POST_VARS);
	}

else {
        # Display default output

	$OUTPUT = order($HTTP_POST_VARS);

}

# get templete
require("template.php");

function order($HTTP_POST_VARS,$errors="")
{
	$Out="";
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	db_conn("cubit");
	$date=date("Y-m-d");

	pglib_transaction("begin");

	if(!isset($month)){$month=date("m");}
	$gotmonth=$month;
	if ($month==1){$td=31;$month='January';}
	if ($month==2){$td=28;$month='February';}
	if ($month==3){$td=31;$month='March';}
	if ($month==4){$td=30;$month='April';}
	if ($month==5){$td=31;$month='May';}
	if ($month==6){$td=30;$month='June';}
	if ($month==7){$td=31;$month='July';}
	if ($month==8){$td=31;$month='August';}
	if ($month==9){$td=30;$month='September';}
	if ($month==10){$td=31;$month='October';}
	if ($month==11){$td=30;$month='November';}
	if ($month==12){$td=31;$month='December';}

	if(!isset($year)){$year=date("Y");}
	$gotyear=$year;

	$timestamp=strtotime("1 $month $year");
	$today = getdate($timestamp);
	$month = $today['month'];
	$mday = $today['mday'];
	$year = $today['year'];
	$week = $today['weekday'];
	$cdate="$month $year";
	$ctime="$week ".date("H:i");
	$nummonth=$gotmonth;
	$op=USER_NAME;

	$Diary="<tr>";
	$timestamp=strtotime("1 $month $year");
	$date=getdate($timestamp);
 	$temp=$date['wday'];
	if ($temp==0) {$temp=7;}

	while ($temp>1)
	{
		$Diary .="<td></td>";
		$temp=$temp-1;
	}


	$numday=1;
	$userfor=USER_NAME;

	while ((checkdate($nummonth,$numday,$year)))
	{
		if ($numday==date("d") and $nummonth==date("m") and $year==date("Y")) {$wes="3";} else {$wes="0";}
		$Diary .= "<td style='height:85;'><table width='100%' border=$wes><tr><td>
		<td valign=top background='images/".$numday.".gif' style='height:85;'><a href='glodiary-day.php?month=$nummonth&year=$year&day=$numday'>";

		$tempdate=$year."-".$nummonth."-".$numday;

		$Sl = "SELECT des,time FROM die WHERE datefor='$tempdate' AND userfor='global' ORDER BY time LIMIT 5";
		$Rs = db_exec($Sl) or errDie ("Unable to access database.");
		while($Tp = pg_fetch_array($Rs))
		{
			$AppTime=substr($Tp['time'],0,2).":".substr($Tp['time'],2,2);
			$Tp['des']=substr($Tp['des'],0,4);
			$Diary .="$AppTime) $Tp[des]<br>";
		}
		$fur =pg_numrows ($Rs);
		if ($fur==0) {$Diary .="_ADD APP_<br>__________<br>__________<br>__________<br>__________";$fur=6;}
		while($fur <=4)
		{
			$Diary .="__________<br>";
			$fur++;
		}

		$timestamp=strtotime("$numday $month $year");
		$date=getdate($timestamp);
 		$dayofweek=$date['wday'];
		if ($dayofweek==0) {$dayofweek=7;}

		$numday++;
		$Diary .="</a></td></tr></table>
		</td>";

		if ($dayofweek==7) {$Diary .="</tr><tr>";}

	}

	$mnum=$gotmonth;
	$my=$gotyear;
	$mnum++;
	if ($mnum==13) {$mnum=1;$my=$my+1;}


 	$ppy=$my;
	$ppmon=$mnum-2;
	if ($ppmon==0) {$ppy=$ppy-1;$ppmon=12;}
	if ($ppmon==-1) {$ppy=$ppy-1;$ppmon=11;}

	
	$bgColor =TMPL_tblDataColor2;
	pglib_transaction("commit");

	$account_dets =
	"
	<head>
	<style type='text/css'>
	<!--
	.die
	{
		font-family: ".TMPL_fntFamily.";
		background-color: $bgColor;
		font-size: ".TMPL_fntSize."pt;
		color: ".TMPL_fntColor.";
	}
	</style>
	</head>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=account_info>
	<table border=0 cellpadding=0 cellspacing=0>
	<tr bgcolor='".TMPL_tblDataColor2."'><th colspan=3 align=center><a href='glodiary.php?month=$ppmon&year=$ppy'>Previous Month</a></th><th colspan=2 align=center><h3>$cdate</h3></th><th colspan=2 align=center><a href='diary.php?month=$mnum&year=$my'>Next Month</a></th></tr>
	<tr><th align=center style='width:88'>Monday</th><th align=center style='width:88'>Tuesday</th><th align=center style='width:88'>Wednesday</th><th align=center style='width:88'>Thursday</th><th align=center style='width:88'>Friday</th><td class=datacell2 align=center style='width:88'>Saterday</td><td class=datacell align=center style='width:88'>Sunday</td></tr>
	$Diary

	</table>
	</form>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=30%>
	 <tr><td><br><br></tr>
	 <tr><th>Quick Links</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_die.php'>Diary</td>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</td>
	 </tr>
	</table>";



	return $account_dets;

}

?>

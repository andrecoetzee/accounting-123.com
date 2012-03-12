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

define("REGISTER_PHP", true);

require("settings.php");
require_lib("time");

if (!isset($_GET["key"])) $_GET["key"] = "info";

switch($_GET["key"]) {
	case "reg":
		$OUTPUT = reg();
		break;
	case "regmsg":
		$OUTPUT = regmsg();
		break;
	case "info":
	default:
		$OUTPUT = info();
		break;
}

require("template.php");




function info($err = "")
{

	global $_GET, $uselog;
	extract($_GET);

	if (!isset($regkey)) $regkey = "";

	// expire date
	db_con("cubit");
	$sql = "SELECT timestampval + '".EXPIRE_DAYS." days',
				DATE_TRUNC('days', AGE(timestampval + '".EXPIRE_DAYS." days', CURRENT_TIMESTAMP))
			FROM uselog WHERE name='firstday'";
	$rslt = db_exec($sql) or errDie("Error checking next expiration date.");

	if ( pg_num_rows($rslt) <= 0 ) {
		$expiredate = "Unknown";
	} else {
		list($y, $m, $d) = explode("-", preg_replace("/ .*/", "", pg_fetch_result($rslt, 0, 0)));
		$expiredate = date("d F Y", mktime(0, 0, 0, $m, $d, $y));

		$pg_expmsg = str_replace("mons", "months", pg_fetch_result($rslt, 0, 1));

		$expiredate .= " ($pg_expmsg)";
	}

	// client key
	$client_key = getkey();

	// registration status
	if (empty($uselog["registered"]["str"])) {
		$reg_status = "Not Registered";
		$expmsg = "";
		$step_one = "
			<tr bgcolor='".bgcolorg()."'>
				<td>
				<h4>1. Print, complete and fax <a href='clientform.pdf'><b>The Client Order Form</b></a> to
				the fax number at the top of the form.
				</td>
			</tr>";
		$step_two = "
			<tr bgcolor='".bgcolorg()."'>
				<td>
				<h4>2. A cubit consultant will contact you. Please read <input type='button' onClick='showPhonetical(this);' value='This Screen' /> to 
				the consultant.
				</td>
			</tr>";
		$step_three = "
			<tr bgcolor='".bgcolorg()."'>
				<td>
				<h4>3. Type the number the consultant supplies you into this box:
				<p align='center'><input type='text' size='30' name='regkey' value='$regkey' /></p>
				</td>
			</tr>";
		$register = "
			<tr>
				<td><input type='submit' value='Register' /></td>
			</tr>";
		$show_client_key = "
			<tr>
				<th>Client Side Key</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap='t' align='center'>$client_key</td>
			</tr>";
	} else {
		list($y, $m, $d) = explode("-", $uselog["registered"]["date"]);
		$reg_status = "Registered: ".date("d F Y", mktime(0, 0, 0, $m, $d, $y));
		$expmsg = "Again";
		$step_one = "
			<tr bgcolor='".bgcolorg()."'>
				<th> Cut and paste this key:</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap='t' align='center'><h4>$client_key</h4></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
				<h4>into your email client, and email it to support@cubit.co.za.
				You can also call your dealer to request a consultant to call you back.</h4>
				</td>
			</tr>";
		$step_two = "
			<tr bgcolor='".bgcolorg()."'>
				<td>
				<h4>For telephonic registration you may be required to read <input type='button' onClick='showPhonetical(this);' value='This Screen' />
				</td>
			</tr>";
		$step_three = "
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<h4>Type the number you receive into this box and click register:
					<p align='center'><input type='text' size='30' name='regkey' value='$regkey' /> <input type='submit' value='Register' />
					</p>
				</td>
			</tr>";
		$register = "";
		$show_client_key = "";
	}



	$nato["A"] = "Alfa";
	$nato["B"] = "Bravo";
	$nato["C"] = "Charlie";
	$nato["D"] = "Delta";
	$nato["E"] = "Echo";
	$nato["F"] = "Foxtrot";
	$nato["G"] = "Golf";
	$nato["H"] = "Hotel";
	$nato["I"] = "India";
	$nato["J"] = "Juliet";
	$nato["K"] = "Kilo";
	$nato["L"] = "Lima";
	$nato["M"] = "Mike";
	$nato["N"] = "November";
	$nato["O"] = "Oscar";
	$nato["P"] = "Papa";
	$nato["Q"] = "Quebec";
	$nato["R"] = "Romeo";
	$nato["S"] = "Sierra";
	$nato["T"] = "Tango";
	$nato["U"] = "Uniform";
	$nato["V"] = "Victor";
	$nato["W"] = "Whiskey";
	$nato["X"] = "XRay";
	$nato["Y"] = "Yankee";
	$nato["Z"] = "Zulu";
	$nato["0"] = "Zero";
	$nato["1"] = "One";
	$nato["2"] = "Two";
	$nato["3"] = "Three";
	$nato["4"] = "Four";
	$nato["5"] = "Five";
	$nato["6"] = "Six";
	$nato["7"] = "Seven";
	$nato["8"] = "Eight";
	$nato["9"] = "Nine";
	$nato["10"] = "Ten";
	$nato[" "] = "&nbsp;";
	$nato["-"] = "dash";

	$phonetic = "";
	for ($i = 0; $i < strlen($client_key); ++$i) {
		$phonetic .= $nato[strtoupper($client_key[$i])];
		if ($i > 0 && $client_key[$i-1] == "-" && $client_key[$i] == " ") {
			$phonetic .= "<br>";
		} else {
			$phonetic .= "&nbsp;&nbsp;";
		}
	}

//		<tr>
//			<th>Registration Status</th>
//			<td id='phonetic_show'></td>
//		</tr>
//		<tr bgcolor='".bgcolorg()."'>
//			<td nowrap='t' align='center'>$reg_status</td>
//		</tr>
//		<tr>
//			<th>Expires $expmsg</th>
//		</tr>
//		<tr bgcolor='".bgcolorg()."'>
//			<td nowrap='t' align='center'>$expiredate</td>
//		</tr>



	$OUTPUT = "
		<script>
			function showPhonetical(obj) {
				XPopupShow('$phonetic', getObject('phonetic_show'));
			}
		</script>
		<h3>Register Cubit</h3>
		$err
		<form method='GET' action='".SELF."'>
			<input type='hidden' name='key' value='reg' />
		<table ".TMPL_tblDflts." style='width: 450px;'>
			$step_one
			$step_two
			$step_three
			$show_client_key
			$register
		</table>
		</form>";
	return $OUTPUT;

}

function reg()
{

	global $uselog;
	global $_GET;
	extract($_GET);

	if (!empty($uselog["norefresh"]["str"])) return regmsg();

	if (!checkkey($regkey)) {
		return info("<li class='err'>Invalid Registration Key Entered.</li>");
	}

	db_con("cubit");

	$sql = "DELETE FROM uselog WHERE name LIKE 'reg_%'";
	$rslt = db_exec($sql) or errDie("Error registering (1).");

	db_con("cubit");

	$sql = "UPDATE uselog SET name=('reg_' || name)
		WHERE name='expired' OR name='firstday' OR name='lastday' OR name='firsttrans' OR name='randhash'";
	$rslt = db_exec($sql) or errDie("Error registering (2).");

	db_con("cubit");

	$sql = "DELETE FROM uselog WHERE name='daylogs' OR name='registered'";
	$rslt = db_exec($sql) or errDie("Error registering (3).");

	setUsage("registered", "$regkey");
	setUsage("norefresh", "true");
	return regmsg();

}



function regmsg()
{

	print "<script>document.location='main.php';</script>";

//	$OUTPUT = "
//	<h3>Registered</h3>
//	Thank you for your support. Enjoy using Cubit further.<br>
//	<br>
//	Click <a href='doc-index.php' target='_top' class='nav'>here</a> to continue.
//	<br>
//	Regards,<br>
//	The Cubit Team<br>";
//	return $OUTPUT;

}

function bindebug($year, $month, $day)
{

	print "year: ".base_convert($year, 10, 2)."<br>";
	print "month: ".base_convert($month, 10, 2)."<br>";
	print "day: ".base_convert($day, 10, 2)."<br><br>";

}



?>

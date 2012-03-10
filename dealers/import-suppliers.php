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

# get settings
require ("../settings.php");
require("../core-settings.php");

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS,$HTTP_POST_FILES);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} else {
	$OUTPUT = select_file();
}

	$OUTPUT .= "
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='index.php'>Dealer Section</a></td>
				</tr>
			</table>";

require("../template.php");




function select_file ()
{

	global $HTTP_POST_VARS;

	$qry = new dbQuery(DB_SQL,
		"SELECT SUM(debit) = 0 AND SUM(credit) = 0 AS res
		FROM core.trial_bal");
	$qry->run();

	if ($qry->fetch_result() == "f") {
		$OUTPUT = "<li class='err'>You cannot import data when you have
			already have entries in your accounting journal. Importing data
			is used for open balances only.</li>";
		return $OUTPUT;
	}

	$OUTPUT = "
			<h3>Import Suppliers</h3>
			<li class='err'>The data needs to be comma seperated (acc no,name,address,vat number,contact name,Business Tel,Fax No,Web Address),
			Ex: supp1,supplier1,address,1997/212/212,rep,011 888 9999,013 293 1223,www.supplier.co.za)</li>
			<form method='POST' enctype='multipart/form-data' action='".SELF."'>
				<input type='hidden' name='key' value='confirm'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>File details</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Please select supplier csv</td>
					<td><input type='file' name='compfile'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan='2' align='right'><input type='submit' value='Import &raquo;'></td>
				</tr>
			</form>
			</table>";
	return $OUTPUT;

}



function confirm($HTTP_POST_VARS,$HTTP_POST_FILES)
{

	extract($HTTP_POST_VARS);

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($HTTP_POST_FILES["compfile"]["tmp_name"], "r");

	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	$Sl = "
		CREATE TABLE import_data (
			des1 varchar, des2 varchar,
			des3 varchar, des4 varchar,
			des5 varchar, des6 varchar,
			des7 varchar, des8 varchar,
			des9 varchar, des10 varchar,
			des11 varchar, des12 varchar,
			des13 varchar, des14 varchar,
			des15 varchar, des16 varchar
		)";
	$Ri = @db_exec($Sl);

	$Sl = "DELETE FROM import_data";
	$Ri = db_exec($Sl) or errDie("Unable to clear import table");

	while (!feof($file) ) {
		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

		if(!isset($datas[7])) {
			continue;
		}

		$Sl = "
			INSERT INTO import_data (
				des1, des2, des3, des4, des5, 
				des6, des7, des8
			) VALUES (
				'$datas[0]', '$datas[1]', '$datas[2]', '$datas[3]', '$datas[4]', 
				'$datas[5]', '$datas[6]', '$datas[7]'
			)";
		$Rl = db_exec($Sl) or errDie("Unable to insert data.");
	}

	fclose($file);

	#get supplier department

	db_conn('exten');

	$get_deps = "SELECT * FROM departments ORDER BY deptname";
	$run_deps = db_exec($get_deps) or errDie("Unable to get department information.");
	if(pg_numrows($run_deps) < 1){
		return "<li class='err'>Unable to get departments information.</li>";
	}else {
		$deptdrop = "<select name='department'>";
		while ($darr = pg_fetch_array($run_deps)){
			$deptdrop .= "<option value='$darr[deptid]'>$darr[deptname]</option>";
		}
		$deptdrop .= "</select>";
	}

	$out = "
			<h3>Suppliers Import</h3>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Select Supplier Departments</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>$deptdrop</td>
				</tr>
				<tr>
					<th>Acc No</th>
					<th>Name</th>
					<th>Address Address</th>
					<th>Vat Number</th>
					<th>Contact Name</th>
					<th>Tell</th>
					<th>Fax</th>
					<th>Website</th>
				</tr>";

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Ri = db_exec($Sl);

	$i = 0;

	while($fd = pg_fetch_array($Ri)) {

		$out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$fd[des1]</td>
					<td>$fd[des2]</td>
					<td>$fd[des3]</td>
					<td>$fd[des4]</td>
					<td>$fd[des5]</td>
					<td>$fd[des6]</td>
					<td>$fd[des7]</td>
					<td>$fd[des8]</td>
				</tr>";

		$i++;

	}

	$out .= "
			<tr>
				<td colspan='3' align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $out;

}



//comma seperated(Standard Bank)
function write($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Rt = db_exec($Sl);

	$i = 0;

	$odate = date("Y-m-d");

	if(!isset($department) OR (strlen($department) < 1)){
		$department = "2";
	}

	while($fd = pg_fetch_array($Rt)) {

		//$out.="<tr bgcolor='$bgcolor'><td>$fd[des1]</td><td>$fd[des2]</td><td>$fd[des3]</td></tr>";

		$i++;

		db_conn('cubit');

		# Write to db
		$sql = "
			INSERT INTO suppliers (
				deptid, supno, supname, location, fcid, 
				currency, vatnum, supaddr, contname, tel, 
				fax, email, url, listid, bankname, 
				branname, brancode, bankaccno, balance, fbalance, 
				div, lead_source
			) VALUES (
				'$department', '$fd[des1]', '$fd[des2]', 'loc', '2', 
				'R', '$fd[des4]', '$fd[des3]', '$fd[des5]', '$fd[des6]', 
				'$fd[des7]', '', '$fd[des8]', '2', '', 
				'', '', '', 0, 0, 
				'".USER_DIV."', ''
			)";
		$supRslt = db_exec ($sql) or errDie ("Unable to add supplier to the system.", SELF);
		if (pg_cmdtuples ($supRslt) < 1) {
			return "<li class='err'>Unable to add supplier to database.</li>";
		}

		if ( ($supp_id = pglib_lastid("suppliers", "supid")) == 0 ) {
			return "<li class='err'>Unable to add supplier to contact list.</li>";
		}




		$Date = date("Y-m-d");

		db_conn('audit');
		$Sl = "SELECT * FROM closedprd ORDER BY id";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {

			db_conn($pd['prdnum']);

			$Sl = "
				INSERT INTO suppledger (
					supid, contra, edate, sdate, eref, descript, 
					credit, debit, div, dbalance, cbalance
				) VALUES (
					'$supp_id', '0', '$Date', '$Date', '0', 'Balance', 
					'0', '0', '".USER_DIV."', '0', '0'
				)";
			$Rj = db_exec($Sl) or errDie("Unable to insert cust balances");

		}

	}

	$out = "Done";
	return $out;

}




function safe($value)
{

	$value = str_replace("!","",$value);
	$value = str_replace("=","",$value);
	//$value = str_replace("#","",$value);
	$value = str_replace("%","",$value);
	$value = str_replace("$","",$value);
	//$value = str_replace("*","",$value);
	$value = str_replace("^","",$value);
	$value = str_replace("?","",$value);
	$value = str_replace("[","",$value);
	$value = str_replace("]","",$value);
	$value = str_replace("{","",$value);
	$value = str_replace("}","",$value);
	$value = str_replace("|","",$value);
	$value = str_replace(":","",$value);
	$value = str_replace("'","",$value);
	$value = str_replace("`","",$value);
	$value = str_replace("~","",$value);
	$value = str_replace("\\","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace(";","",$value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);
	$value = str_replace("$","",$value);
	return $value;

}




?>
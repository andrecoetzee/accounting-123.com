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

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST,$_FILES);
			break;
		case "write":
			$OUTPUT = write($_POST);
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
				<tr class='".bg_class()."'>
					<td><a href='../customers-view.php'>View Customers</a></td>
				</tr>
			</table>";

require("../template.php");




function select_file ()
{

	global $_POST;

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
		<h3>Import Customers</h3>
		<li class='err'>The data needs to be comma seperated (acc no,name,postal address,del address,vat number,contact name,Business Tel,Cell No,Fax No,E-mail,Web Address), Ex: cust1,customer1,P.O. Box 1,physical address,1997/212/212,rep,011 888 9999,083 999 9999,013 293 1223,cliet@website.co.za,www.client.co.za)</li>
		<form method='POST' enctype='multipart/form-data' action='".SELF."'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>File details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Please select customer csv</td>
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




function confirm($_POST,$_FILES)
{

	extract($_POST);

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($_FILES["compfile"]["tmp_name"], "r");

	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".select_file();
	}

	db_conn('cubit');

	$Sl = "
		CREATE TABLE import_data (
			des1 varchar, des2 varchar, des3 varchar, des4 varchar, 
			des5 varchar, des6 varchar, des7 varchar, des8 varchar, 
			des9 varchar, des10 varchar, des11 varchar, des12 varchar, 
			des13 varchar, des14 varchar, des15 varchar, des16 varchar
		)";
	$Ri = @db_exec($Sl);

	$Sl = "DELETE FROM import_data";
	$Ri = db_exec($Sl) or errDie("Unable to clear import table");

	while (!feof($file) ) {
		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

		if(!isset($datas[10])) {
			continue;
		}

		$Sl = "
			INSERT INTO import_data (
				des1, des2, des3, des4, des5, des6, 
				des7, des8, des9, des10, des11
			) VALUES (
				'$datas[0]', '$datas[1]', '$datas[2]', '$datas[3]', '$datas[4]', '$datas[5]', 
				'$datas[6]', '$datas[7]', '$datas[8]', '$datas[9]', '$datas[10]'
			)";
		$Rl = db_exec($Sl) or errDie("Unable to insert data.");
	}

	fclose($file);

	#get departments
	db_conn('exten');

	$get_deps = "SELECT * FROM departments ORDER BY deptname";
	$run_deps = db_exec($get_deps) or errDie("Unable to get department information.");
	if(pg_numrows($run_deps) < 1){
		return "<li class='err'>No Departments Found. Please Add A Department.</li>";
	}else {
		$depdrop = "<select name='department'>";
		while($darr = pg_fetch_array($run_deps)){
			$depdrop .= "<option value='$darr[deptname]'>$darr[deptname]</option>";
		}
		$depdrop .= "</select>";
	}

	$out = "
			<h3>Customers Import</h3>
			<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>Select Department</th>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='2'>$depdrop</td>
				</tr>
				".TBL_BR."
				<tr>
					<th>Acc No</th>
					<th>Name</th>
					<th>Postal Address</th>
					<th>Delivery Address</th>
					<th>VAT Number</th>
					<th>Contact Name</th>
					<th>Tell</th>
					<th>Cell</th>
					<th>Fax</th>
					<th>Email</th>
					<th>Website</th>
				</tr>";

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Ri = db_exec($Sl);

	$i = 0;

	$showbutton = "
				<tr>
					<td colspan='5' align='right'><input type='submit' value='Write &raquo;'></td>
				</tr>";

	while($fd = pg_fetch_array($Ri)) {

		#check if there is a conflict ...
		$get_check = "SELECT * FROM customers WHERE accno = '$fd[des1]' LIMIT 1";
		$run_check = db_exec($get_check) or errDie ("Unable to get customer information.");
		if (pg_numrows($run_check) > 0){
			$fd['des1'] = "<li class='err'>$fd[des1]</li>";
			$showbutton = "";
		}

		$out .= "
				<tr class='".bg_class()."'>
					<td>$fd[des1]</td>
					<td>$fd[des2]</td>
					<td>$fd[des3]</td>
					<td>$fd[des4]</td>
					<td>$fd[des5]</td>
					<td>$fd[des6]</td>
					<td>$fd[des7]</td>
					<td>$fd[des8]</td>
					<td>$fd[des9]</td>
					<td>$fd[des10]</td>
					<td>$fd[des11]</td>
				</tr>";

		$i++;

	}

	$out .= "
				<tr><td><br></td></tr>
				<tr>
					<td colspan='5'><li class='err'>Items in red indicate conflicts with current data. Please corrent the information in the import file.</li></td>
				</tr>
				$showbutton
			</form>
			</table>";
	return $out;

}




//comma seperated(Standard Bank)
function write($_POST)
{

	extract($_POST);

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Rt = db_exec($Sl);

	$i = 0;

	$odate = date("Y-m-d");

	if(!isset($department) OR (strlen($department) < 1)){
		$department = "Ledger 1";
	}

	while($fd = pg_fetch_array($Rt)) {

		//$out.="<tr bgcolor='$bgcolor'><td>$fd[des1]</td><td>$fd[des2]</td><td>$fd[des3]</td></tr>";

		$i++;

		db_conn('cubit');

		$sql = "
			INSERT INTO customers (
				deptid, accno, surname, title, init, location, 
				fcid, currency, category, class, addr1, paddr1, 
				vatnum, contname, bustel, tel, cellno, fax, 
				email, url, traddisc, setdisc, pricelist, chrgint, 
				overdue, intrate, chrgvat, credterm, odate, credlimit, 
				blocked, balance, div, deptname, classname, catname, 
				lead_source
			) VALUES (
				'2', '$fd[des1]', '$fd[des2]', '', '', 'loc', 
				'2', 'R', '2', '2', '$fd[des4]', '$fd[des3]', 
				'$fd[des5]', '$fd[des6]', '$fd[des7]', '', '$fd[des8]', '$fd[des9]', 
				'$fd[des10]', '$fd[des11]', '0', '0', '2', 'no', 
				'30', '0', 'yes', '0', '$odate', '0', 
				'no', '0', '".USER_DIV."', '$department', 'General', 'General', 
				''
			)";
		$custRslt = db_exec ($sql) or errDie ("Unable to add customer to system.", SELF);
		if (pg_cmdtuples ($custRslt) < 1) {
			return "<li class='err'>Unable to add customer to database.</li>";
		}

		if (($cust_id = pglib_lastid("customers", "cusnum")) == 0) {
			return "<li class='err'>Unable to add customer to contact list.</li>";
		}

// 		$sql = "INSERT INTO cons (surname,ref,tell,cell,fax,email,hadd,padd,date,cust_id,con,by,div)
// 		VALUES ('$surname','Customer','$bustel','$cellno','$fax','$email','$addr','$paddr','$odate','$cust_id','No','".USER_NAME."','".USER_DIV."')";
//
// 		$rslt = db_exec($sql) or errDie("Unable to add customer to contact list", SELF);



		$Date = date("Y-m-d");

		db_conn('audit');
		$Sl = "SELECT * FROM closedprd ORDER BY id";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {

			db_conn($pd['prdnum']);

			$Sl = "
				INSERT INTO custledger (
					cusnum, contra, edate, sdate, eref, descript, 
					credit, debit, div, dbalance, cbalance
				) VALUES (
					'$cust_id', '0', '$odate', '$Date', '0', 'Balance', 
					'0', '0', '".USER_DIV."', '0', '0'
				)";
			$Rj = db_exec($Sl) or errDie("Unable to insert cust balances");
		}

	}

	$out = "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Import Complete</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Customers have been successfully imported.</td>
				</tr>
			</table>
			";
	return $out;

}




function safe($value) {
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
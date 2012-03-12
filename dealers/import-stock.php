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
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='index.php'>Dealer Section</a></td>
		</tr>
	</table>";

require("../template.php");



function select_file ()
{

	global $_POST;

	$qry = new dbQuery(DB_SQL, "SELECT SUM(debit) = 0 AND SUM(credit) = 0 AS res FROM core.trial_bal");
	$qry->run();

	if ($qry->fetch_result() == "f") {
		$OUTPUT = "<li class='err'>You cannot import data when you have
			already have entries in your accounting journal. Importing data
			is used for open balances only.</li>";
		return $OUTPUT;
	}

	$OUTPUT = "
		<h3>Import Stock</h3>
		<li class='err'>The data needs to be comma seperated (code,description,cost price,selling price,units,balance)</td></li>
		<li class='err'>A Practical Example would therefore be:<br>
			Code = Stock Code<br>
			Description = Stock Description<br>
			Cost Price = Cost Price per Stock Item<br>
			Selling Price = Selling Price per stock Item<br>
			Units = Amount of Stock Items<br>
			Balance = Total of the cost price multiplied by the amount of items<br><br>
			example:<br>
			I have 100 number7 Cars in stock at a cost of R10 each that I sell for R15<br>
			I have 10  number3  -no description-  in stock at a cost of R20 each that I sell for R30<br><br>
			no7,cars,10,15,100,1000<br>
			no3,0,20,30,10,200<br></li>
		<form method='POST' enctype='multipart/form-data' action='".SELF."'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>File details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Please select stock csv</td>
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

		if(!isset($datas[2])) {
			continue;
		}

		$code = safe($datas[0]);
		$description = safe($datas[1]);
		$amount = sprint($datas[3]);
		$camount = sprint ($datas[2]);
		
		$balance = sprint ($datas[5]);
		$units = safe($datas[4]);

		$Sl = "
			INSERT INTO import_data (
				des1, des2, des3, des4, des5, des6
			) VALUES (
				'$code', '$description', '$amount', '$balance', '$units', '$camount'
			)";
		$Rl = db_exec($Sl) or errDie("Unable to insert data.");
	}

	fclose($file);

	#get stores

	db_conn('exten');

	$get_stores = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname";
	$run_stores = db_exec($get_stores) or errDie("Unable to get stores information.");
	if(pg_numrows($run_stores) < 1){
		return "Unable to get stores information.";
	}else {
		$storedrop = "<select name='store'>";
		while ($sarr = pg_fetch_array($run_stores)){
			$storedrop .= "<option value='$sarr[whid]'>$sarr[whname]</option>";
		}
		$storedrop .= "</select>";
	}

	$out = "
		<h3>Stock Import</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='3'>Select Store</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='3'>$storedrop</td>
				</tr>
				".TBL_BR."
				<tr>
					<th>Stock Code</th>
					<th>Description</th>
					<th>Cost Price</th>
					<th>Selling Price</th>
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
				<td>$fd[des6]</td>
				<td>$fd[des3]</td>
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
function write($_POST)
{

	extract($_POST);

	db_conn('cubit');

	$Sl = "SELECT * FROM import_data";
	$Rt = db_exec($Sl);

	$i = 0;

	if(!isset($store) OR (strlen($store) < 1)){
		$store = "2";
	}

	while($fd = pg_fetch_array($Rt)) {

		//$out.="<tr bgcolor='$bgcolor'><td>$fd[des1]</td><td>$fd[des2]</td><td>$fd[des3]</td></tr>";

		$i++;

		db_conn('cubit');

		$sql = "
			INSERT INTO stock (
				stkcod, serno, stkdes, prdcls, classname, csamt, 
				units, buom, suom, rate, shelf, row, 
				minlvl, maxlvl, csprice, selamt, exvat, catid, 
				catname, whid, blocked, type, serd, alloc, 
				com, bar, vatcode, div
			) VALUES (
				'$fd[des1]', '', '$fd[des2]', '2', 'General','0',  
				'0', '',   '',  '1',   '',    '',  
				'0',    '0',   '$fd[des6]', '$fd[des3]', 'no', '2', 
				'General', '$store', 'n', 'stk', 'no', '0', 
				'0', '', '2', '".USER_DIV."'
			)";
		//print $sql;
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);



		# Get last stock ID
		$stkid = pglib_lastid ("stock", "stkid");

		#add the stoc to temp table for tb import later ...
		$sql2 = "
			INSERT INTO stock_tbimport (
				stkid, stkcod, balance, units
			) VALUES (
				'$stkid', '$fd[des1]', '$fd[des4]', '$fd[des5]'
			)";
		$runsql2 = db_exec($sql2) or errDie ("Unable to store stock import information.");

		# Add this product to all pricelists
		db_conn("exten");
		$sql = "SELECT * FROM pricelist WHERE div = '".USER_DIV."'";
		$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($listRslt) > 0){
			while($list = pg_fetch_array($listRslt)){
				db_conn ("exten");
				$sql = "
					INSERT INTO plist_prices (
						listid, stkid, catid, clasid, price, 
						div, show
					) VALUES (
						'$list[listid]', '$stkid', '2', '2', '$fd[des3]', 
						'".USER_DIV."', 'Yes'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);
			}
		}

		$sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."'";
		$listRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($listRslt) > 0){
			while($list = pg_fetch_array($listRslt)){
				db_conn ("exten");
				$sql = "
					INSERT INTO splist_prices (
						listid, stkid, catid, clasid, price, div
					) VALUES (
						'$list[listid]', '$stkid', '2', '2', '0', '".USER_DIV."'
					)";
				$rslt = db_exec($sql) or errDie("Unable to insert price list items to Cubit.",SELF);
			}
		}

		db_conn('cubit');

		$Sl = "SELECT * FROM stock WHERE stkid='$stkid'";
		$Ri = db_exec($Sl) or errDie("Unable to get stock.");

		$data = pg_fetch_array($Ri);

		$date = date("Y-m-d");

		db_conn('audit');
		$Sl = "SELECT * FROM closedprd ORDER BY id";
		$Ri = db_exec($Sl);

		while($pd = pg_fetch_array($Ri)) {

			db_conn($pd['prdnum']);

			$Sl = "
				INSERT INTO stkledger (
					stkid, stkcod, stkdes, trantype, edate, 
					qty, csamt, balance, bqty, details, 
					div, yrdb
				) VALUES (
					'$data[stkid]', '$data[stkcod]', '$data[stkdes]', 'bal', '$date', 
					'$data[units]', '$data[csamt]', '$data[csamt]', '$data[units]', 'Balance', 
					'".USER_DIV."', '".YR_DB."'
				)";
			$Ro = db_exec($Sl);
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
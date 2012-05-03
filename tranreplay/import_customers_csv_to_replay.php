<?

require ("../settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = confirm_file ($_POST);
			break;
		case "write":
			$OUTPUT = write_file ($_POST);
			break;
		default:
			$OUTPUT = get_file ();
	}
}else {
	$OUTPUT = get_file ();
}

$OUTPUT .= "<br>"
	.mkQuickLinks(
		ql("../tranreplay/record-trans.php", "Add Replay Transaction"),
		ql("../tranreplay/export-xml.php", "Export Replay Transactions To File"),
		ql("../tranreplay/replay-file-trans.php", "Replay Transaction File")
	);

require ("../template.php");






function get_file ($err="")
{

	$display = "
		<h2>Import Debtor Transactions</h2>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Select File To Import</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='file' name='debt_import'></td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Next'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>File Must Be In CSV File With THe Following Format</th>
			</tr>
			<tr>
				<td><li class='err'>Customer Number,Debit Account (xxxx/xxx),Credit Account (xxxx/xxx),Reference Number,Date,Description,Amount</li></td>
			</tr>
			<tr>
				<td><li class='err'>Please ensure the description field is in clear text format, and contains no unnecessary characters.</li></td>
			</tr>
			<tr>
				<td><li class='err'>Eg. (!@#$%^&*-<>;':\"_+[]{})</li></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function confirm_file ($_POST)
{

	global $_FILES;

	$importfile = tempnam("/tmp", "cubitimport_");
	$file = fopen($_FILES["debt_import"]["tmp_name"], "r");

	if ( $file == false) {
		return "<li class='err'>Cannot read file.</li>".get_file();
	}

	db_conn('exten');

	$listing = "";
	$counter = 0;
	$auto_ref = 22;
	while (!feof($file) ) {
		$data = safe(fgets($file, 4096));
		$datas = explode(",",$data);

		if(!isset($datas[6])) {
			continue;
		}

		if(isset($datas[7])) {
			continue;
		}

		$cusacc = safe($datas[0]);
		$dtacc = safe($datas[1]);
		$ctacc = safe($datas[2]);
		$amount = sprint($datas[6]);
		$date = safe($datas[4]);
		$description = safe($datas[5]);
// 		$ref = safe($datas[3]);
		$ref = $auto_ref;

		db_connect ();

		#check for customer
		$get_cust = "SELECT * FROM customers WHERE accno = '$cusacc' LIMIT 1";
		$run_cust = db_exec($get_cust) or errDie("Unable to get customer information.");
		if(pg_numrows($run_cust) < 1){
			return get_file ("<li class='err'>Customer with account number: $cusacc Not Found.</li><br>");
		}
		$carr = pg_fetch_array($run_cust);

		db_conn ('core');

		#check for dt account
		$get_dt = "SELECT * FROM accounts WHERE topacc = '".substr($dtacc,0,strpos($dtacc,"/"))."' LIMIT 1";
		$run_dt = db_exec($get_dt) or errDie("Unable to get debit account information.");
		if(pg_numrows($run_dt) < 1){
			return get_file ("<li class='err'>Debit Account Details Not Found. Account :$dtacc</li><br>");
		}

		#check for ct account
		$get_ct = "SELECT * FROM accounts WHERE topacc = '".substr($ctacc,0,strpos($ctacc,"/"))."' LIMIT 1";
		$run_ct = db_exec($get_ct) or errDie("Unable to get credit account information.");
		if(pg_numrows($run_ct) < 1){
			return get_file ("<li class='err'>Credit Account Details Not Found. Account :$ctacc</li><br>");
		}

// Total sales for the month ending April 07
// VAT


		$listing .= "
			<input type='hidden' name='ids[]' value='$counter'>
			<input type='hidden' name='cusacc[]' value='$carr[cusnum]'>
			<input type='hidden' name='dtacc[]' value='$dtacc'>
			<input type='hidden' name='ctacc[]' value='$ctacc'>
			<input type='hidden' name='amount[]' value='$amount'>
			<input type='hidden' name='date[]' value='$date'>
			<input type='hidden' name='description[]' value='$description'>
			<input type='hidden' name='ref[]' value='$ref'>
			<tr class='".bg_class()."'>
				<td>$cusacc</td>
				<td>$dtacc</td>
				<td>$ctacc</td>
				<td>$amount</td>
				<td>$date</td>
				<td>$description</td>
				<td>$ref</td>
			</tr>";
		$counter++;
		$auto_ref++;
	}

	fclose($file);

	if (!isset($_FILES['debt_import'])){
		return "Cannot read file. (2)";
	}

	$display = "
		<h2>Confirm Data To Be Imported:</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<tr>
				<th>Customer</th>
				<th>Debit Account</th>
				<th>Credit Account</th>
				<th>Amount</th>
				<th>Date</th>
				<th>Description</th>
				<th>Reference</th>
			</tr>
			$listing
			".TBL_BR."
			<tr>
				<td  colspan='7' align='right'><input type='submit' value='Confirm'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function write_file ($_POST)
{

	extract ($_POST);
	
	foreach ($ids AS $i){

		db_conn('core');

		$get_deb_id = "SELECT accid FROM accounts WHERE topacc = '".substr($dtacc[$i],0,strpos($dtacc[$i],"/"))."' AND accnum = '".substr($dtacc[$i],strpos($dtacc[$i],"/")+1)."' LIMIT 1";
		$run_deb_id = db_exec($get_deb_id) or errDie("Unable to get debtor information.");
		if(pg_numrows($run_deb_id) < 1){
			$dtnum = 0;
		}else {
			$darr = pg_fetch_array($run_deb_id);
			$dtnum = $darr['accid'];
		}

		$get_ct_id = "SELECT * FROM accounts WHERE topacc = '".substr($ctacc[$i],0,strpos($ctacc[$i],"/"))."' AND accnum = '".substr($ctacc[$i],strpos($ctacc[$i],"/")+1)."' LIMIT 1";
		$run_ct_id = db_exec($get_ct_id) or errDie("Unable to get debtor information.");
		if(pg_numrows($run_ct_id) < 1){
			$ctnum = 0;
			$carr['accname'] = "NONE";
		}else {
			$carr = pg_fetch_array($run_ct_id);
			$ctnum = $carr['accid'];
		}

		$amount[$i] = sprint (abs ($amount[$i]));

		db_conn('exten');
		
		#make the journal
		$sql1 = "
			INSERT INTO tranreplay (
				ttype, debitacc, creditacc, tdate, refno, amount, vat, details, iid
			) VALUES (
				'journal', '$dtnum', '$ctnum', '$date[$i]', '$ref[$i]', '$amount[$i]', '0', '$description[$i]', '0'
			)";
		$run_sql1 = db_exec($sql1) or errDie("Unable to store replay transaction.");

		$lastid = pglib_lastid ("tranreplay","id");

		if($carr['accname'] == "Customer Control Account") 
			$amount[$i] = sprint (abs($amount[$i]) - (2*abs($amount[$i])));

		#make the cust statement entry
		$sql2 = "
			INSERT INTO tranreplay (
				ttype, debitacc, creditacc, tdate, refno, amount, vat, details, iid
			) VALUES (
				'debtor', '0', '0', '$date[$i]', '0', '$amount[$i]', '0', '$description[$i]', '$cusacc[$i]'
			)";
		$run_sql2 = db_exec($sql2) or errDie("Unable to store replay transaction.");
		
		
		if($amount[$i] > 0){
			$val1 = "0";
			$val2 = "$dtnum";
		}else {
			$val1 = "$dtnum";
			$val2 = "0";
		}
		$amount[$i] = sprint (abs($amount[$i]));

		#make the customer ledger
		$sql3 = "
			INSERT INTO tranreplay (
				ttype, creditacc, debitacc, tdate, refno, amount, vat, details, iid
			) VALUES (
				'debtor', '$val1', '$val2', '$date[$i]', '$ref[$i]', '$amount[$i]', '0', '$description[$i]', '$cusacc[$i]'
			)";
		$run_sql3 = db_exec($sql3) or errDie("Unable to store replay transaction.");
	}

	$display = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Import Complete</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Transaction(s) have been imported.</td>
			</tr>
		</table>";
	return $display;

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
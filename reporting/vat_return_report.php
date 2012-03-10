<?

require ("../settings.php");

if(isset($HTTP_POST_VARS["key"])){
	switch($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = generate_recommended ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write_report ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_period ();
	}
}else {
	$OUTPUT = get_period ();
}

require ("../template.php");




function get_period ()
{

	global $PRDMON;

	$finstartdate = mkdate(getYearOfFinPrd(1),$PRDMON[1],1);
	$finenddate = mkldate(getYearOfFinPrd(12),$PRDMON[12]);

	db_connect ();

	#get vat period setting
	$get_set = "SELECT * FROM settings WHERE label = 'VAT Period' LIMIT 1";
	$run_set = db_exec($get_set) or errDie("Unable to get vat period information.");
	if(pg_numrows($run_set) < 1){
		return "
			<li class='err'>Please Set VAT Period Setting Before Continuing.</li>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='../vat_period_setting.php'>Set VAT Period</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}else {

		$sarr = pg_fetch_array($run_set);
		$periodlength = $sarr['value'];
		$b = 1;


		$period_drop = "<select name='period'>";
		for($x = 1;$b < 12;$x = $x + $periodlength){

			$b = $b + $periodlength;
			#make sure we dont cross the 12 period limit
			if($b > 12) {

				#set period to the last
				$dob = $b -12;
				$b = 12;

				#make sure we get the last month (the -1 makes us lose 1)
//				$PRDMON[$b] = $PRDMON[$b] +1;
			}else {
				$dob = $b;
			}

			$start = date("Y-m-d",mktime(0,0,0,$PRDMON[$x],1,getYearOfFinPrd($x)));
			$end = date("Y-m-d",mktime(0,0,0,$PRDMON[$dob],0,getYearOfFinPrd($b)));

			$period_drop .= "<option value='$start|$end'>$start - $end</option>";
		}
		$period_drop .= "</select>";

	}



	#generate the periods dropdown based on vat period setting.

	$display = "
		<h2>Select VAT Period For Report</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<td><li class='err'>NOTE: Pre Generated Values Are The Cubit Recommened Values. </li></td>
			</tr>
			<tr>
				<td><li class='err'>However these values may be changed at will.</li></td>
			</tr>
			<tr>
				<th>Period</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$period_drop</td>
			</tr>
			<tr><td></td></tr>
			<tr>
				<th>Name For Report</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' name='returnname' value='".date("Y-m-d")." Report'></td>
			</tr>
			<tr><td></td></tr>
			<tr>
				<td align='right'><input type='submit' value='Next'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function generate_recommended ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($period) OR (strlen($period) < 1)){
		return "Invalid Period Length.";
	}

	if (!isset($rendering_date))
		$rendering_date = "";
	if (!isset($payment_amount))
		$payment_amount = "";
	if (!isset($remittance_rec_date))
		$remittance_rec_date = "";
	if (!isset($area))
		$area = "";
	if (!isset($acc_number1))
		$acc_number1 = "";
	if (!isset($acc_number2))
		$acc_number2 = "";
	if (!isset($acc_number3))
		$acc_number3 = "";
	if (!isset($acc_number4))
		$acc_number4 = "";
	if (!isset($tax_period_end1))
		$tax_period_end1 = "";
	if (!isset($tax_period_end2))
		$tax_period_end2 = "";
	if (!isset($date_received))
		$date_received = "";
	if (!isset($vat_area))
		$vat_area = "";
	if (!isset($vat_area2))
		$vat_area2 = "";
	if (!isset($field_4))
		$field_4 = "";
	if (!isset($field_4a))
		$field_4a = "";
	if (!isset($field_5))
		$field_5 = "";
	if (!isset($field_6))
		$field_6 = "";
	if (!isset($field_7))
		$field_7 = "";
	if (!isset($field_8))
		$field_8 = "";
	if (!isset($field_9))
		$field_9 = "";
	if (!isset($field_10))
		$field_10 = "";
	if (!isset($field_11))
		$field_11 = "";
	if (!isset($field_12))
		$field_12 = "";
	if (!isset($field_13))
		$field_13 = "";
	if (!isset($field_16))
		$field_16 = "";
	if (!isset($field_17))
		$field_17 = "";
	if (!isset($field_18))
		$field_18 = "";
	if (!isset($field_19))
		$field_19 = "";
	if (!isset($field_20))
		$field_20 = "";
	if (!isset($field_25))
		$field_25 = "";
	if (!isset($field_26))
		$field_26 = "";
	if (!isset($field_27))
		$field_27 = "";
	if (!isset($field_28))
		$field_28 = "";
	if (!isset($field_26_1))
		$field_26_1 = "";
	if (!isset($field_29))
		$field_29 = "";
	if (!isset($field_31))
		$field_31 = "";
	if (!isset($field_32))
		$field_32 = "";
	if (!isset($field_33))
		$field_33 = "";
	if (!isset($field_32_1))
		$field_32_1 = "";
	if (!isset($field_34))
		$field_34 = "";
	if (!isset($field_36))
		$field_36 = "";
	if (!isset($field_37))
		$field_37 = "";
	if (!isset($field_38))
		$field_38 = "";
	if (!isset($field_37_1))
		$field_37_1 = "";
	if (!isset($field_39))
		$field_39 = "";
	if (!isset($field_40))
		$field_40 = "";
	if (!isset($contact_telno))
		$contact_telno = "";
	if (!isset($contact_capacity))
		$contact_capacity = "";
	if (!isset($contact_date))
		$contact_date = "";

	$darr = explode ("|",$period);
	$start = $darr['0'];
	$end = $darr['1'];

	db_connect ();

	#first we check if there is already data for this date range ...
	$get_check = "SELECT * FROM vat_returns_archive WHERE ((start_date < '$start') AND (end_date > '$start')) OR ((start_date < '$end') AND (end_date > '$end')) OR ((start_date > '$start') AND (end_date < '$end')) OR ((start_date < '$start') AND (end_date > '$end'))";
	$run_check = db_exec($get_check) or errDie("Unable to get vat return check.");
	if(pg_numrows($run_check) > 0){
		return "Found Overlapping match.";
	}

	$get_exact = "SELECT * FROM vat_returns_archive WHERE start_date = '$start' AND end_date = '$end' LIMIT 1";
	$run_exact = db_exec($get_exact) or errDie("Unable to get vat return information.");
	if(pg_numrows($run_exact) > 0){
		return "Existing Data Found For This Date Range.";
	}

	#get the data to generate the report

###########[ COMPANY DETAILS]###########
	$get_comp = "SELECT * FROM compinfo LIMIT 1";
	$run_comp = db_exec($get_comp) or errDie("Unable to get company information.");
	if(pg_numrows($run_comp) < 1){
		#no company data ...
	}else {
		$carr = pg_fetch_array($run_comp);
		$registration_number = $carr['vatnum'];
		$enquire_telephone = $carr['tel'];
		$client_data1 = $carr['compname'];
		$client_data2 = $carr['paddr1'];
		$client_data3 = $carr['paddr2'];
		$client_data4 = $carr['paddr3'];
		$client_data5 = $carr['postcode'];
		$trading_name = $carr['compname'];
		$vat_registration_number = $carr['vatnum'];
	}
########################################


#############[ 1 ]###############
	$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date >= '$start' AND date <= '$end' AND type = 'OUTPUT' AND cid = '2'";
	$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
	$data=pg_fetch_array($Ry);
 
	$field_1 = sprint($data['amount']);
	$vat_1 = sprint($data['vat']);
##############[ /1 ]##############


#############[ 1a ]###############
	$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date >= '$start' AND date <= '$end' AND type = 'OUTPUT' AND cid = '3'";
	$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
	$data=pg_fetch_array($Ry);
 
	$field_1a = sprint($data['amount']);
	$vat_1a = sprint($data['vat']);
##############[ /1a ]##############


#############[ 2 ]###############
	$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date >= '$start' AND date <= '$end' AND type = 'OUTPUT' AND cid = '5'";
	$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
	$data=pg_fetch_array($Ry);
 
	$field_2 = sprint($data['amount']);
	$vat_2 = sprint($data['vat']);
##############[ /2 ]##############


#############[ 3 ]###############
	$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date >= '$start' AND date <= '$end' AND type = 'OUTPUT' AND cid = '6'";
	$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
	$data=pg_fetch_array($Ry);
 
	$field_3 = sprint($data['amount']);
	$vat_3 = sprint($data['vat']);
##############[ /3 ]##############



#############[ 14 ]###############
	$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date >= '$start' AND date <= '$end' AND type = 'INPUT' AND cid = '3'";
	$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
	$data=pg_fetch_array($Ry);
 
	$field_14 = sprint($data['amount']);
	$vat_14 = sprint(abs($data['vat']));
##############[ /14 ]##############


#############[ 15 ]###############
	$Sl="SELECT sum(amount) AS amount,sum(vat) AS vat FROM vatreport WHERE date >= '$start' AND date <= '$end' AND type = 'INPUT' AND cid = '2'";
	$Ry=db_exec($Sl) or errDie("Unable to get vat rec.");
	$data=pg_fetch_array($Ry);

	$field_15 = sprint($data['amount']);
	$vat_15 = sprint(abs($data['vat']));
##############[ /15 ]##############

###############[ OTHER VALS ]################
$endarr = explode("-",$end);
$taxperiod = $endarr[1].substr($endarr[0],2,2);

###########################################

	$display = "
		<center>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='returnname' value='$returnname'>
			<input type='hidden' name='from_date' value='$start'>
			<input type='hidden' name='to_date' value='$end'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td width='50%'></td>
				<td colspan='2'><font size='4'><b>VALUE-ADDED TAX</b></font></td>
				<td colspan='2' bgcolor='#DADADA' align='center'><font size='4'>VAT 201</font></td>
			</tr>
			<tr>
				<td width='50%'></td>
				<td colspan='2'></td>
				<td colspan='2' bgcolor='#DADADA' align='center'><font size='4'>Part 2</font></td>
			</tr>
			<tr>
				<td width='50%'></td>
				<td colspan='4'><hr></td>
			</tr>
			<tr>
				<td width='50%'></td>
				<td colspan='2'><font size='4'><b>Return of remittance of VAT</b></font></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td width='50%'></td>
				<td colspan='3'><input type='text' name='registration_number' value='$registration_number'> Registration Number</td>
			</tr>
			<tr>
				<td width='50%'></td>
				<td colspan='4' bgcolor='#DADADA' align='center'><b>Please use this telephone no for any enquiries</b></td>
			</tr>
			<tr>
				<td width='50%'></td>
				<td colspan='2'><input type='text' name='enquire_telephone' value='$enquire_telephone'></td>
			</tr>
			<tr>
				<td width='50%'><input type='text' size='40' name='client_data1' value='$client_data1'></td>
				<td colspan='2' bgcolor='#DADADA' align='center'>Last day for rendering return/payment</td>
				<td colspan='2'><input type='text' name='rendering_date' value='$rendering_date'></td>
			</tr>
			<tr>
				<td width='50%'><input type='text' size='40' name='client_data2' value='$client_data2'></td>
				<td colspan='2' bgcolor='#DADADA' align='center'>Amount of payment</td>
				<td colspan='2'>".CUR." <input type='text' name='payment_amount' value='$payment_amount'></td>
			</tr>
			<tr>
				<td width='50%'><input type='text' size='40' name='client_data3' value='$client_data3'></td>
				<td colspan='2' bgcolor='#DADADA' align='center'>Remittance received on</th>
				<td colspan='2'><input type='text' name='remittance_rec_date' value='$remittance_rec_date'></td>
			</tr>
			<tr>
				<td width='50%'><input type='text' size='40' name='client_data4' value='$client_data4'></td>
			</td>
			<tr>
				<td width='50%'><input type='text' size='7' name='client_data5' value='$client_data5'></td>
			</tr>
			<tr>
				<td></td>
				<td colspan='4' bgcolor='#DADADA' align='center'>Method of payment / indicate below</td>
			</tr>
			<tr>
				<td></td>
				<td colspan='4'>Cheque <input type='radio' name='payment_method' value='cheque'> Cash <input type='radio' name='payment_method' value='cash'> Bank/Internet payment <input type='radio' name='payment_method' value='bank'></td>
			</tr>
			<tr>
				<td></td>
				<td bgcolor='#DADADA' align='center'>Area</td>
				<td><input type='text' size='10' name='area' value='$area'></td>
				<td bgcolor='#DADADA' align='center'>Tax period</td>
				<td><input type='text' size='10' name='taxperiod' value='$taxperiod'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2' width='50%'><input type='text' size='40' name='trading_name' value='$trading_name'></td>
				<td colspan='3' bgcolor='#DADADA' align='center'>Account number for First National Bank Payments</td>
			</tr>
			<tr>
				<td colspan='2'></td>
				<td colspan='3'>
					<input type='text' name='acc_number1' value='$acc_number1'>
					<input type='text' size='3' name='acc_number2' value='$acc_number2'>
					<input type='text' size='4' name='acc_number3' value='$acc_number3'>
					<input type='text' size='25' name='acc_number4' value='$acc_number4'>
				</td>
			</tr>
			<tr>
				<td bgcolor='#DADADA' align='center'>Tax period ending</td>
				<td>
					<input type='text' size='25' name='tax_period_end1' value='$tax_period_end1'>
					<input type='text' size='12' name='tax_period_end2' value='$tax_period_end2'>
				</td>
				<td bgcolor='#DADADA' align='center'>Date received</td>
				<td><input type='text' size='31' name='date_received' value='$date_received'></td>
				<td bgcolor='#DADADA' align='center'>VAT 201</td>
			</tr>
			<tr>
				<td bgcolor='#DADADA' align='center'>VAT registration number</td>
				<td><input type='text' size='39' name='vat_registration_number' value='$vat_registration_number'></td>
				<td bgcolor='#DADADA' align='center'>Area</td>
				<td>
					<input type='text' size='20' name='vat_area' value='$vat_area'>
					<input type='text' size='8' name='vat_area2' value='$vat_area2'>
				</td>
				<td bgcolor='#DADADA' align='center'>PART 1</td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='4' width='15%'><b>CALCULATION OF OUTPUT TAX</b></td>
			</tr>
			<tr>
				<td colspan='4' width='15%'>Supply of goods and/or services by you:</td>
				<td colspan='2' bgcolor='#DADADA' align='center'>CONSIDERATION (INCLUDING VAT)</td>
				<td width='5%'></td>
				<td colspan='2' bgcolor='#DADADA' align='center'>VAT</td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Standard rate (excluding capital goods and/or services and accomodation)</td>
				<td width='5%' bgcolor='#DADADA' align='center'>1</td>
				<td width='20%'><input type='text' size='25' name='field_1' value='$field_1'></td>
				<td width='5%'>*(r/(100+r))</td>
				<td width='5%' bgcolor='#DADADA' align='center'>4</td>
				<td width='25%'><input type='text' size='25' name='field_4' value='$field_4'></td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Standard rate (only capital goods and/or services)</td>
				<td width='5%' bgcolor='#DADADA' align='center'>1A</td>
				<td width='20%'><input type='text' size='25' name='field_1a' value='$field_1a'></td>
				<td width='5%'>*(r/100+r)</td>
				<td width='5%' bgcolor='#DADADA' align='center'>4A</td>
				<td width='25%'><input type='text' size='25' name='field_4a' value='$field_4a'></td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Zero rate</td>
				<td width='5%' bgcolor='#DADADA' align='center'>2</td>
				<td width='20%'><input type='text' size='25' name='field_2' value='$field_2'></td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Exempt and non-supplies</td>
				<td width='5%' bgcolor='#DADADA' align='center'>3</td>
				<td width='20%'><input type='text' size='25' name='field_3' value='$field_3'></td>
			</tr>
			<tr>
				<td width='15%'><b>Supply of accomodation:</b></td>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>TOTAL AMOUNT (EXCLUDING VAT)</td>
				<td width='5%'></td>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>TAXABLE VALUE (EXCLUDING VAT)</td>
			</tr>
			<tr>
				<td width='15%'>Exceeding 28 days</td>
				<td width='5%' bgcolor='#DADADA' align='center'>5</td>
				<td width='15%'><input type='text' size='25' name='field_5' value='$field_5'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>* 60%</td>
				<td width='5%' bgcolor='#DADADA' align='center'>6</td>
				<td width='20%'><input type='text' size='25' name='field_6' value='$field_6'></td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Not exceeding 28 days</td>
				<td width='5%' bgcolor='#DADADA' align='center'>7</td>
				<td width='20%'><input type='text' size='25' name='field_7' value='$field_7'></td>
				<td width='5%'></td>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>VAT</td>
			</tr>
			<tr>
				<td width='15%' colspan='3'></td>
				<td width='5%'><b>TOTAL</b></td>
				<td width='5%' bgcolor='#DADADA' align='center'>8</td>
				<td width='20%'><input type='text' size='25' name='field_8' value='$field_8'></td>
				<td width='5%'>* r/100</td>
				<td width='5%' bgcolor='#DADADA' align='center'>9</td>
				<td width='25%'><input type='text' size='25' name='field_9' value='$field_9'></td>
			</tr>
			<tr>
				<td width='15%' colspan='4'><b>Adjustments:</b></td>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>CONSIDERATION (INCLUDING VAT)</td>
				<td width='5%'></td>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>VAT</td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Change in use and export of second-hand goods</td>
				<td width='5%' bgcolor='#DADADA' align='center'>10</td>
				<td width='20%'><input type='text' size='25' name='field_10' value='$field_10'></td>
				<td width='5%'>* r/100+r</td>
				<td width='5%' bgcolor='#DADADA' align='center'>11</td>
				<td width='25%'><input type='text' size='25' name='field_11' value='$field_11'></td>
			</tr>
			<tr>
				<td width='15%' colspan='4'>Other</td>
				<td width='5%'></td>
				<td width='20%'></td>
				<td width='5%'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>12</td>
				<td width='25%'><input type='text' size='25' name='field_12' value='$field_12'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td width='10%'></td>
				<td width='30%'></td>
				<td width='5%'></td>
				<td width='15%'></td>
			</tr>
			<tr>
				<td width='10%' bgcolor='#DADADA' align='center'><b>TOTAL A</b></td>
				<td width='30%' bgcolor='#DADADA'><b>TOTAL OUTPUT TAX (4+4A+9+11+12)</b></td>
				<td width='5%' bgcolor='#DADADA' align='center'>13</td>
				<td width='15%'><input type='text' size='25' name='field_13' value='$field_13'></td>
			</tr>
			<tr>
				<td width='10%' colspan='4'><b>B. CALCULATION OF INPUT TAX (Input tax in respect of):</b></td>
			</tr>
			<tr>
				<td width='10%' colspan='2'>Capital goods or serives imported by and/or supplied to you</td>
				<td width='5%' bgcolor='#DADADA' align='center'>14</td>
				<td width='15%'><input type='text' size='25' name='field_14' value='$vat_14'></td>
			</tr>
			<tr>
				<td width='10%' colspan='2'>Other goods or services imported by and/or supplied to you (not capital goods and/or services)</td>
				<td width='5%' bgcolor='#DADADA' align='center'>15</td>
				<td width='15%'><input type='text' size='25' name='field_15' value='$vat_15'></td>
			</tr>
			<tr>
				<td width='10%' colspan='4'><b>Tax on adjustments:</b></td>
			</tr>
			<tr>
				<td width='10%' colspan='2'>Change in use</td>
				<td width='5%' bgcolor='#DADADA' align='center'>16</td>
				<td width='15%'><input type='text' size='25' name='field_16' value='$field_16'></td>
			</tr>
			<tr>
				<td width='10%' colspan='2'>Bad debts</td>
				<td width='5%' bgcolor='#DADADA' align='center'>17</td>
				<td width='15%'><input type='text' size='25' name='field_17' value='$field_17'></td>
			</tr>
			<tr>
				<td width='10%' colspan='2'>Other</td>
				<td width='5%' bgcolor='#DADADA' align='center'>18</td>
				<td width='15%'><input type='text' size='25' name='field_18' value='$field_18'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td width='5%'></td>
				<td width='10%'></td>
				<td width='10%'></td>
				<td width='5%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
				<td width='10%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'><b>TOTAL B</b></td>
				<td width='10%' colspan='5' bgcolor='#DADADA'><b>TOTAL INPUT TAX (14+15+16+17+18)</b></td>
				<td width='5%' bgcolor='#DADADA' align='center'>19</td>
				<td width='10%'><input type='text' size='25' name='field_19' value='$field_19'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' colspan='4' align='center'>AMOUNT PAYABLE / REFUNDABLE</td>
				<td width='5%' bgcolor='#DADADA' colspan='3' align='center'>(TOTAL A - TOTAL B)</td>
				<td width='5%' bgcolor='#DADADA' align='center'>20</td>
				<td width='10%'><input type='text' size='25' name='field_20' value='$field_20'></td>
			</tr>
			<tr>
				<td width='5%' colspan='6'><b>C. CALCULATION OF DIESEL REFUND IN TERMS OF THE CUSTOMES AND EXCISE ACT</b></td>
				<td width='10%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>24</td>
				<td width='10%' bgcolor='#DADADA' colspan='2' align='center'>On Land</td>
				<td width='10%' colspan='4'></td>
				<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>DIESEL</td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>25</td>
				<td width='10%'>Total Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_25' value='$field_25'></td>
				<td width='5%'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>26</td>
				<td width='10%'>Non-Eligible Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_26' value='$field_26'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>27</td>
				<td width='10%'>Eligible Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_27' value='$field_27'></td>
				<td width='5%'>* 80% </td>
				<td width='5%' bgcolor='#DADADA' align='center'>28</td>
				<td width='10%'><input type='text' size='25' name='field_28' value='$field_28'></td>
				<td width='10%' align='right'>X <input type='text' size='7' name='field_26_1' value='$field_26_1'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>29</td>
				<td width='10%'><input type='text' size='25' name='field_29' value='$field_29'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>30</td>
				<td width='10%' bgcolor='#DADADA' colspan='2' align='center'>Offshore</td>
				<td width='5%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
				<td width='10%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>31</td>
				<td width='10%'>Total Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_31' value='$field_31'></td>
				<td width='5%'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>32</td>
				<td width='10%'>Non-Eligible Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_32' value='$field_32'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>33</td>
				<td width='10%'>Eligible Purchases</td>
				<td width='10%'><input type='text' size='25' name='field_33' value='$field_33'></td>
				<td width='5%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
				<td width='10%' align='right'>X <input type='text' size='7' name='field_32_1 ' value='$field_32_1 '></td>
				<td width='5%' bgcolor='#DADADA' align='center'>34</td>
				<td width='10%'><input type='text' size='25' name='field_34' value='$field_34'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>35</td>
				<td width='10%' bgcolor='#DADADA' colspan='2' align='center'>Rail & Harbour Services</td>
				<td width='5%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
				<td width='10%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>36</td>
				<td width='10%'>Total Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_36' value='$field_36'></td>
				<td width='5%'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>37</td>
				<td width='10%'>Non-Eligible Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_37' value='$field_37'></td>
				<td width='5%'></td>
				<td width='10%'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' align='center'>38</td>
				<td width='10%'>Eligible Purchases (l)</td>
				<td width='10%'><input type='text' size='25' name='field_38' value='$field_38'></td>
				<td width='5%'></td>
				<td width='5%'></td>
				<td width='10%'></td>
				<td width='10%' align='right'>X <input type='text' size='7' name='field_37_1' value='$field_37_1'></td>
				<td width='5%' bgcolor='#DADADA' align='center'>39</td>
				<td width='10%'><input type='text' size='25' name='field_39' value='$field_39'></td>
			</tr>
			<tr>
				<td width='5%' bgcolor='#DADADA' colspan='7'><b>TOTAL AMOUNT PAYABLE or REFUNDABLE 20-(29+34+39) or 20+(29+34+39)</b></td>
				<td width='5%' bgcolor='#DADADA' align='center'>40</td>
				<td width='10%'><input type='text' size='25' name='field_40' value='$field_40'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>Tel No: <input type='text' name='contact_telno' value='$contact_telno'></td>
				<td bgcolor='#DADADA'></td>
				<td>Capacity: <input type='text' name='contact_capacity' value='$contact_capacity'></td>
				<td bgcolor='#DADADA'>Date: <input type='text' name='contact_date' value='$contact_date'></td>
			</tr>
			<tr>
				<td>Contact Details for THIS return only</td>
				<td bgcolor='#DADADA'>Authorised person's signature</td>
			</tr>
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $display;

}




 function write_report ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$insert_sql = "
		INSERT INTO saved_vat201 (
			returnname, from_date, to_date, system_date, registration_number, enquire_telephone, client_data1, rendering_date, client_data2, payment_amount, client_data3, remittance_rec_date, client_data4, client_data5, payment_method, area, taxperiod, trading_name, acc_number1, acc_number2, acc_number3, acc_number4, tax_period_end1, tax_period_end2, date_received, vat_registration_number, vat_area, vat_area2, field_1, field_4, field_1a, field_4a, field_2, field_3, field_5, field_6, field_7, field_8, field_9, field_10, field_11, field_12, field_13, field_14, field_15, field_16, field_17, field_18, field_19, field_20, field_25, field_26, field_27, field_28, field_26_1, field_29, field_31, field_32, field_33, field_32_1, field_34, field_36, field_37, field_38, field_37_1, field_39, field_40, contact_telno, contact_capacity, contact_date
		) VALUES (
			'$returnname', '$from_date', '$to_date', 'now', '$registration_number', '$enquire_telephone', '$client_data1', '$rendering_date', '$client_data2', '$payment_amount', '$client_data3', '$remittance_rec_date', '$client_data4', '$client_data5', '$payment_method', '$area', '$taxperiod', '$trading_name', '$acc_number1', '$acc_number2', '$acc_number3', '$acc_number4', '$tax_period_end1', '$tax_period_end2', '$date_received', '$vat_registration_number', '$vat_area', '$vat_area2', '$field_1', '$field_4', '$field_1a', '$field_4a', '$field_2', '$field_3', '$field_5', '$field_6', '$field_7', '$field_8', '$field_9', '$field_10', '$field_11', '$field_12', '$field_13', '$field_14', '$field_15', '$field_16', '$field_17', '$field_18', '$field_19', '$field_20', '$field_25', '$field_26', '$field_27', '$field_28', '$field_26_1', '$field_29', '$field_31', '$field_32', '$field_33', '$field_32_1', '$field_34', '$field_36', '$field_37', '$field_38', '$field_37_1', '$field_39', '$field_40', '$contact_telno', '$contact_capacity', '$contact_date'
		)";
	$run_insert = db_exec($insert_sql) or errDie("Unable to store vat 201 information");

	return "Report Has Been Saved.";

}



?>

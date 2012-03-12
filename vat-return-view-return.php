<?

	require ("settings.php");

	if (!isset($_GET["vatid"])){
		$OUTPUT = "<li class='err'>Invalid Use Of Module. Invalid VAT Return ID.</li>";
	}else {
		$OUTPUT = generate_recommended ($_GET);
	}


//	print $OUTPUT;

	require ("template.php");



function generate_recommended ($_POST)
{

	extract ($_POST);

	if(!isset($vatid) OR (strlen($vatid) < 1)){
		return "Invalid Use Of Module. Invalid VAT Return ID.";
	}



	db_connect ();

	#get the data to generate the report
	$get_data = "SELECT * FROM saved_vat201 WHERE id = '$vatid' LIMIT 1";
	$run_data = db_exec($get_data) or errDie("Unabele to get vat 201 report information");
	if(pg_numrows($run_data) < 1){
		return "<li class='err'>Could Not Get VAT 201 Information.</li>";
	}else {
		$arr = pg_fetch_array($run_data);
		extract ($arr);
	}


	$display = "
			<center>
			<form action='".SELF."' method='POST'>
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
					<td colspan='3'>$registration_number Registration Number</td>
				</td>
				<tr>
					<td width='50%'></td>
					<td colspan='4' bgcolor='#DADADA' align='center'><b>Please use this telephone no for any enquiries</b></td>
				<tr>
					<td width='50%'></td>
					<td colspan='2'>$enquire_telephone</td>
				</td>
				<tr>
					<td width='50%'>$client_data1</td>
					<td colspan='2' bgcolor='#DADADA' align='center'>Last day for rendering return/payment</td>
					<td colspan='2'>$rendering_date</td>
				</td>
				<tr>
					<td width='50%'>$client_data2</td>
					<td colspan='2' bgcolor='#DADADA' align='center'>Amount of payment</td>
					<td colspan='2'>".CUR." $payment_amount</td>
				</td>
				<tr>
					<td width='50%'>$client_data3</td>
					<td colspan='2' bgcolor='#DADADA' align='center'>Remittance received on</th>
					<td colspan='2'>$remittance_rec_date</td>
				</td>
				<tr>
					<td width='50%'>$client_data4</td>
				</td>
				<tr>
					<td width='50%'>$client_data5</td>
				</td>
				<tr>
					<td></td>
					<td colspan='4' bgcolor='#DADADA' align='center'>Method of payment / indicate below</td>
				</tr>
				<tr>
					<td></td>
					<td colspan='4'>$payment_method</td>
				</tr>
				<tr>
					<td></td>
					<td bgcolor='#DADADA' align='center'>Area</td>
					<td>$area</td>
					<td bgcolor='#DADADA' align='center'>Tax period</td>
					<td>$taxperiod</td>
				</tr>
			</table>
			<table ".TMPL_tblDflts.">
				<tr>
					<td colspan='2' width='50%'>$trading_name</td>
					<td colspan='3' bgcolor='#DADADA' align='center'>Account number for First National Bank Payments</td>
				</tr>
				<tr>
					<td colspan='2'></td>
					<td colspan='3'>
						$acc_number1 
						$acc_number2 
						$acc_number3 
						$acc_number4 
					</td>
				</tr>				</td>
				<tr>
				<tr>
					<td bgcolor='#DADADA' align='center'>Tax period ending</td>
					<td>
						$tax_period_end1 
						$tax_period_end2 
					</td>
					<td bgcolor='#DADADA' align='center'>Date received</td>
					<td>$date_received</td>
					<td bgcolor='#DADADA' align='center'>VAT 201</td>
				</tr>
				<tr>
					<td bgcolor='#DADADA' align='center'>VAT registration number</td>
					<td>$vat_registration_number</td>
					<td bgcolor='#DADADA' align='center'>Area</td>
					<td>
						$vat_area 
						$vat_area2 
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
					<td width='20%'>$field_1</td>
					<td width='5%'>*(r/(100+r))</td>
					<td width='5%' bgcolor='#DADADA' align='center'>4</td>
					<td width='25%'>$field_4</td>
				</tr>
				<tr>
					<td width='15%' colspan='4'>Standard rate (only capital goods and/or services)</td>
					<td width='5%' bgcolor='#DADADA' align='center'>1A</td>
					<td width='20%'>$field_1a</td>
					<td width='5%'>*(r/100+r)</td>
					<td width='5%' bgcolor='#DADADA' align='center'>4A</td>
					<td width='25%'>$field_4a</td>
				</tr>
				<tr>
					<td width='15%' colspan='4'>Zero rate</td>
					<td width='5%' bgcolor='#DADADA' align='center'>2</td>
					<td width='20%'>$field_2</td>
				</tr>
				<tr>
					<td width='15%' colspan='4'>Exempt and non-supplies</td>
					<td width='5%' bgcolor='#DADADA' align='center'>3</td>
					<td width='20%'>$field_3</td>
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
					<td width='15%'>$field_5</td>
					<td width='5%' bgcolor='#DADADA' align='center'>* 60%</td>
					<td width='5%' bgcolor='#DADADA' align='center'>6</td>
					<td width='20%'>$field_6</td>
				</tr>
				<tr>
					<td width='15%' colspan='4'>Not exceeding 28 days</td>
					<td width='5%' bgcolor='#DADADA' align='center'>7</td>
					<td width='20%'>$field_7</td>
					<td width='5%'></td>
					<td width='5%' colspan='2' bgcolor='#DADADA' align='center'>VAT</td>
				</tr>
				<tr>
					<td width='15%' colspan='3'></td>
					<td width='5%'><b>TOTAL</b></td>
					<td width='5%' bgcolor='#DADADA' align='center'>8</td>
					<td width='20%'>$field_8</td>
					<td width='5%'>* r/100</td>
					<td width='5%' bgcolor='#DADADA' align='center'>9</td>
					<td width='25%'>$field_9</td>
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
					<td width='20%'>$field_10</td>
					<td width='5%'>* r/100+r</td>
					<td width='5%' bgcolor='#DADADA' align='center'>11</td>
					<td width='25%'>$field_11</td>
				</tr>
				<tr>
					<td width='15%' colspan='4'>Other</td>
					<td width='5%'></td>
					<td width='20%'></td>
					<td width='5%'></td>
					<td width='5%' bgcolor='#DADADA' align='center'>12</td>
					<td width='25%'>$field_12</td>
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
					<td width='15%'>$field_13</td>
				</tr>
				<tr>
					<td width='10%' colspan='4'><b>B. CALCULATION OF INPUT TAX (Input tax in respect of):</b></td>
				</tr>
				<tr>
					<td width='10%' colspan='2'>Capital goods or serives imported by and/or supplied to you</td>
					<td width='5%' bgcolor='#DADADA' align='center'>14</td>
					<td width='15%'>$field_14</td>
				</tr>
				<tr>
					<td width='10%' colspan='2'>Other goods or services imported by and/or supplied to you (not capital goods and/or services)</td>
					<td width='5%' bgcolor='#DADADA' align='center'>15</td>
					<td width='15%'>$field_15</td>
				</tr>
				<tr>
					<td width='10%' colspan='4'><b>Tax on adjustments:</b></td>
				</tr>
				<tr>
					<td width='10%' colspan='2'>Change in use</td>
					<td width='5%' bgcolor='#DADADA' align='center'>16</td>
					<td width='15%'>$field_16</td>
				</tr>
				<tr>
					<td width='10%' colspan='2'>Bad debts</td>
					<td width='5%' bgcolor='#DADADA' align='center'>17</td>
					<td width='15%'>$field_17</td>
				</tr>
				<tr>
					<td width='10%' colspan='2'>Other</td>
					<td width='5%' bgcolor='#DADADA' align='center'>18</td>
					<td width='15%'>$field_18</td>
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
					<td width='10%'>$field_19</td>
				</tr>
				<tr>
					<td width='5%' bgcolor='#DADADA' colspan='4' align='center'>AMOUNT PAYABLE / REFUNDABLE</td>
					<td width='5%' bgcolor='#DADADA' colspan='3' align='center'>(TOTAL A - TOTAL B)</td>
					<td width='5%' bgcolor='#DADADA' align='center'>20</td>
					<td width='10%'>$field_20</td>
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
					<td width='10%'>$field_25</td>
					<td width='5%'></td>
					<td width='5%' bgcolor='#DADADA' align='center'>26</td>
					<td width='10%'>Non-Eligible Purchases (l)</td>
					<td width='10%'>$field_26</td>
					<td width='5%'></td>
					<td width='10%'></td>
				</tr>
				<tr>
					<td width='5%' bgcolor='#DADADA' align='center'>27</td>
					<td width='10%'>Eligible Purchases (l)</td>
					<td width='10%'>$field_27</td>
					<td width='5%'>* 80% </td>
					<td width='5%' bgcolor='#DADADA' align='center'>28</td>
					<td width='10%'>$field_28</td>
					<td width='10%' align='right'>X $field_26_1</td>
					<td width='5%' bgcolor='#DADADA' align='center'>29</td>
					<td width='10%'>$field_29</td>
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
					<td width='10%'>$field_31</td>
					<td width='5%'></td>
					<td width='5%' bgcolor='#DADADA' align='center'>32</td>
					<td width='10%'>Non-Eligible Purchases (l)</td>
					<td width='10%'>$field_32</td>
					<td width='5%'></td>
					<td width='10%'></td>
				</tr>
				<tr>
					<td width='5%' bgcolor='#DADADA' align='center'>33</td>
					<td width='10%'>Eligible Purchases</td>
					<td width='10%'>$field_33</td>
					<td width='5%'></td>
					<td width='5%'></td>
					<td width='10%'></td>
					<td width='10%' align='right'>X $field_32_1</td>
					<td width='5%' bgcolor='#DADADA' align='center'>34</td>
					<td width='10%'>$field_34</td>
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
					<td width='10%'>$field_36</td>
					<td width='5%'></td>
					<td width='5%' bgcolor='#DADADA' align='center'>37</td>
					<td width='10%'>Non-Eligible Purchases (l)</td>
					<td width='10%'>$field_37</td>
					<td width='5%'></td>
					<td width='10%'></td>
				</tr>
				<tr>
					<td width='5%' bgcolor='#DADADA' align='center'>38</td>
					<td width='10%'>Eligible Purchases (l)</td>
					<td width='10%'>$field_38</td>
					<td width='5%'></td>
					<td width='5%'></td>
					<td width='10%'></td>
					<td width='10%' align='right'>X $field_37_1</td>
					<td width='5%' bgcolor='#DADADA' align='center'>39</td>
					<td width='10%'>$field_39</td>
				</tr>
				<tr>
					<td width='5%' bgcolor='#DADADA' colspan='7'><b>TOTAL AMOUNT PAYABLE or REFUNDABLE 20-(29+34+39) or 20+(29+34+39)</b></td>
					<td width='5%' bgcolor='#DADADA' align='center'>40</td>
					<td width='10%'>$field_40</td>
				</tr>
			</table>
			<table ".TMPL_Dflts.">
				<tr>
					<td>Tel No: $contact_telno</td>
					<td bgcolor='#DADADA'></td>
					<td>Capacity: $contact_capacity</td>
					<td bgcolor='#DADADA'>Date: $contact_date</td>
				</tr>
				<tr>
					<td>Contact Details for THIS return only</td>
					<td bgcolor='#DADADA'>Authorised person's signature</td>
					<td>Capacity</td>
					<td bgcolor='#DADADA'>Date</td>
				</tr>
			</table>
			</form>
			</center>
		";
	return $display;

}




?>

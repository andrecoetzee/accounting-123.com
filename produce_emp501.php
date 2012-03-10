<?

require ("settings.php");

if (isset($_REQUEST["key"])){
	switch ($_REQUEST["key"]){
		case "confirm":
			$OUTPUT = generate_form ();
			break;
		default:
			$OUTPUT = show_form ();
	}
}else {
	$OUTPUT = show_form ();
}

//require ("template.php");
require ("tmpl-print.php");




function show_form () 
{

	db_connect ();

	#get the db stuff ...


	$display = "
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts." border='1' width='900'>
			<tr>
				<td rowspan='2'>IMAGE</td>
				<td>Transaction Year (CCYY)</td>
				<td colspan='2'><input type='text' size='5' maxlength='4' name='input_transaction_year' value='$input_transaction_year'>
				EMPLOYER RECONCILIATION DECLARATION IMAGE</td>
				<td align='right'>EMP501 IMAGE</td>
			</tr>
			<tr>
				<---IMAGE--->
				<td>PAYE Ref No.</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_refno' value='$input_paye_refno'></td>
				<td>SDL Ref No.</td>
				<td><input type='text' size='11' maxlength='10' name='input_sdl_refno' value='$input_sdl_refno'></td>
				<td>UIF Ref No.</td>
				<td><input type='text' size='11' maxlength='10' name='input_uif_refno' value='$input_uif_refno'></td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts." border='1' width='900'>
			<tr>
				<td width='10%'>Trading Name</td>
				<td><input type='text' size='46' maxlength='45' name='input_tradingname' value='$input_tradingname'></td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts." border='1' width='900'>
			<tr>
				<td width='150' bgcolor='#8389ff' align='center'><b>Summary of Employer Liability</b></td>
				<td width='100' bgcolor='#8389ff' align='center'><b>PAYE</b></td>
				<td width='100' bgcolor='#8389ff' align='center'><b>SDL</b></td>
				<td width='100' bgcolor='#8389ff' align='center'><b>UIF</b></td>
				<td width='130' bgcolor='#8389ff' align='center'><b>Total Monthly Liability</b></td>
				<td width='50'>&nbsp;</td>
				<td bgcolor='#8389ff' align='center'><b>Total Payments</b></td>
			</tr>
			<tr>
				<td width='150'>March</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_march' value='$input_paye_march'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_march' value='$input_sdl_march'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_march' value='$input_uif_march'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_march' value='$input_liability_march'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_march' value='$input_payments_march'></td>
			</tr>
			<tr>
				<td width='150'>April</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_april' value='$input_paye_april'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_april' value='$input_sdl_april'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_april' value='$input_uif_april'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_april' value='$input_liability_april'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_april' value='$input_payments_april'></td>
			</tr>
			<tr>
				<td width='150'>May</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_may' value='$input_paye_may'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_may' value='$input_sdl_may'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_may' value='$input_uif_may'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_may' value='$input_liability_may'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_may' value='$input_payments_may'></td>
			</tr>
			<tr>
				<td width='150'>June</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_june' value='$input_paye_june'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_june' value='$input_sdl_june'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_june' value='$input_uif_june'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_june' value='$input_liability_june'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_june' value='$input_payments_june'></td>
			</tr>
			<tr>
				<td width='150'>July</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_july' value='$input_paye_july'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_july' value='$input_sdl_july'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_july' value='$input_uif_july'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_july' value='$input_liability_july'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_july' value='$input_payments_july'></td>
			</tr>
			<tr>
				<td width='150'>August</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_august' value='$input_paye_august'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_august' value='$input_sdl_august'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_august' value='$input_uif_august'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_august' value='$input_liability_august'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_august' value='$input_payments_august'></td>
			</tr>
			<tr>
				<td width='150'>September</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_september' value='$input_paye_september'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_september' value='$input_sdl_september'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_september' value='$input_uif_september'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_september' value='$input_liability_september'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_september' value='$input_payments_september'></td>
			</tr>
			<tr>
				<td width='150'>October</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_october' value='$input_paye_october'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_october' value='$input_sdl_october'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_october' value='$input_uif_october'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_october' value='$input_liability_october'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_october' value='$input_payments_october'></td>
			</tr>
			<tr>
				<td width='150'>November</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_november' value='$input_paye_november'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_november' value='$input_sdl_november'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_november' value='$input_uif_november'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_november' value='$input_liability_november'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_november' value='$input_payments_november'></td>
			</tr>
			<tr>
				<td width='150'>December</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_december' value='$input_paye_december'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_december' value='$input_sdl_december'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_december' value='$input_uif_december'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_december' value='$input_liability_december'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_december' value='$input_payments_december'></td>
			</tr>
			<tr>
				<td width='150'>January</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_january' value='$input_paye_january'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_january' value='$input_sdl_january'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_january' value='$input_uif_january'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_january' value='$input_liability_january'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_january' value='$input_payments_january'></td>
			</tr>
			<tr>
				<td width='150'>February</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_february' value='$input_paye_february'></td>
				<td><input type='text' size='9' maxlength='8' name='input_sdl_february' value='$input_sdl_february'></td>
				<td><input type='text' size='9' maxlength='8' name='input_uif_february' value='$input_uif_february'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_february' value='$input_liability_february'></td>
				<td width='50'>&nbsp;</td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_february' value='$input_payments_february'></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts." border='1' width='900'>
			<tr>
				<td width='150'>Annual Total</td>
				<td><input type='text' size='11' maxlength='10' name='input_paye_annual_total' value='$input_paye_annual_total'></td>
				<td><input type='text' size='11' maxlength='10' name='input_sdl_annual_total' value='$input_sdl_annual_total'></td>
				<td><input type='text' size='11' maxlength='10' name='input_uif_annual_total' value='$input_uif_annual_total'></td>
				<td><input type='text' size='11' maxlength='10' name='input_liability_annual_total' value='$input_liability_annual_total'></td>
				<td><input type='text' size='11' maxlength='10' name='input_payments_annual_total' value='$input_payments_annual_total'></td>
			</tr>
			<tr>
				<td width='150'>Difference - Liability & Certificate Totals</td>
				<td><input type='text' size='11' maxlength='10' name='input_difference' value='$input_difference'></td>
				<td>INPUT 2</td>
				<td>INPUT 3</td>
				<td>INPUT 4</td>
				<---NOTHING HERE--->
			</tr>
			<tr>
				<td width='150'>Total Value of Tax Certificates</td>
				<td><input type='text' size='11' maxlength='10' name='input_total_value_tax' value='$input_total_value_tax'></td>
				<td>INPUT 2</td>
				<td>INPUT 3</td>
				<td bgcolor='#8389ff' align='center'>DECLARED LIABILITY</td>
				<td bgcolor='#8389ff' align='center'>DUE BY/TO YOU</td>
			</tr>
			<tr>
				<td width='150'>Total Value of Electronic Tax Certificates</td>
				<td><input type='text' size='11' maxlength='10' name='input_total_value_electronic' value='$input_total_value_electronic'></td>
				<td colspan='2'>SOME MISC TEXT</td>
				<td>INPUT</td>
				<td>INPUT</td>
			</tr>
			<tr>
				<td width='150'>Total Value of Manual Tax Certificates</td>
				<td><input type='text' size='11' maxlength='10' name='input_total_value_manual' value='$input_total_value_manual'></td>
				<td rowspan='2' colspan='2'>TEXTBOX</td>
				<td rowspan='2'>DECLARATION</td>
				<td rowspan='2'>DECLARATION TEXT</td>
			</tr>
			<tr>
				<td width='150'>Date (CCYYMMDD)</td>
				<td><input type='text' size='9' maxlength='8' name='' value=''></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function generate_form () 
{



}


?>
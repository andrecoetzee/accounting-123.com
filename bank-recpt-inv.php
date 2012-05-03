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

# Get settings
require("../settings.php");
require("../core-settings.php");
require ("../libs/ext.lib.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "method":
			if(strlen($_POST["accnum"])==0) {
				# redirect if not local supplier
				if(!is_local("customers", "cusnum", $_POST["cusid"])){
					// print "SpaceBar";
					header("Location: bank-recpt-inv-int.php?cusid=$_POST[cusid]");
					exit;
				}
			}
			$OUTPUT = method($_POST["cusid"]);
			break;
		case "alloc":
			$OUTPUT = alloc($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = sel_cus($_POST);
	}
} elseif(isset($_GET["cusnum"])) {
	# Display default output
	$OUTPUT = method($_GET["cusnum"]);
} else {
	# Display default output
	$OUTPUT = sel_cus($_POST);
}

# get templete
require("../template.php");

# Insert details
function sel_cus($_POST)
{

	extract($_POST);

        // customers Drop down selections
        db_connect();
        $cust = "<select name='cusid'>";
        $sql = "SELECT cusnum,cusname,surname FROM customers WHERE div = '".USER_DIV."' ORDER BY surname,cusname";
        $cusRslt = db_exec($sql);
        $numrows = pg_numrows($cusRslt);
        if(empty($numrows)){
                return "
							<li> There are no Debtors in Cubit.<p>
							<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
								<tr><th>Quick Links</th></tr>
								<script>document.write(getQuicklinkSpecial());</script>
								<script>document.write(getQuicklinkSpecial());</script>
								<tr class='bg-odd'>
									<td><a href='../main.php'>Main Menu</a></td>
								</tr>
							</table>";
        }

	if(!isset($cusid)) {
		$cusid=0;
	}

        while($cus = pg_fetch_array($cusRslt)){
		if($cus['cusnum']==$cusid) {
			$sel="selected";
		} else {
			$sel="";
		}
                $cust .= "<option $sel value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
        }
        $cust .="</select>";

        // layout
        $add = "
					<h3>New Bank Receipt</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='method'>
						<tr>
							<th colspan='2'>Select Customer</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Customers</td>
							<td>$cust</td>
						</tr>
						<tr class='".bg_class()."'>
							<td colspan='2' align='center'>OR</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Input customer account number</td>
							<td><input type='text' name='accnum' size='10'></td>
						</tr>
						<tr>
							<td></td>
							<td valign='center'><input type='submit' value='Enter Details >'></td>
						</tr>
					</table>";

        # main table (layout with menu)
        $OUTPUT = "
        			<center>
					<table width='100%'>
						<tr>
							<td width='65%' align='left'>$add</td>
							<td valign='top' align='center'>
					        	<table ".TMPL_tblDflts." width='65%'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
									<script>document.write(getQuicklinkSpecial());</script>
									<tr class='".bg_class()."'>
										<td><a href='../main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>";
        return $OUTPUT;

}


# Insert details
function method($cusid)
{

    # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	global $_POST;

	extract($_POST);

	if(isset($accnum)) {
		$accnum=remval($accnum);
		if(strlen($accnum)>0) {
			db_conn('cubit');

			$Sl="SELECT * FROM customers WHERE lower(accno)=lower('$accnum')";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "<li class='err'>Invalid account number</li>".sel_cus($_POST);
			}

			$cd=pg_fetch_array($Ri);

			$cusid=$cd['cusnum'];
		}
	}

	// customers Drop down selections
        db_connect();
      	$sql = "SELECT cusname,surname,accno,contname,tel FROM customers WHERE cusnum ='$cusid' AND div = '".USER_DIV."'";
        $cusRslt = db_exec($sql);
        $numrows = pg_numrows($cusRslt);
        if(empty($numrows)){
                return "<li> Invalid Debtor.";
        }
        $cus = pg_fetch_array($cusRslt);
        $cust = "$cus[cusname] $cus[surname]";

	// layout
        $add = "
					<h3>New Bank Receipt</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='alloc'>
						<input type='hidden' name='cusid' value='$cusid'>
						<tr>
							<th colspan='2'>Receipt Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account</td>
							<td valign='center'>
								<select name='bankid'>";

        db_connect();
        $sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname,bankname";
        $banks = db_exec($sql);
        $numrows = pg_numrows($banks);

        if(empty($numrows)){
                return "<li class='err'> There are no accounts held at the selected Bank.
                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }

        while($acc = pg_fetch_array($banks)){
                $add .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
        }

	if(!isset($all)) {
		$all="0";
	}

	$as1="";
	$as2="";
	$as3="";

	if($all==0) {
		$as1="selected";
	} elseif($all==1) {
		$as2="selected";
	} elseif($all==2) {
		$as3="selected";
	}

	$alls = "
				<select name='all'>
					<option value='0' $as1>Auto</option>
					<option value='1' $as2>Allocate To Age Analysis</option>
					<option value='2' $as3>Allocate To Each invoice</option>
				</select>";

	if(!isset($descript)) {
		$descript="";
		$cheqnum="";
		$amt="";
	}


        $add .= "
		        				</select>
		        			</td>
		        		</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td>
								<input type=text size=2 name=day maxlength=2 value='".date("d")."'>-
								<input type=text size=2 name=mon maxlength=2 value='".date("m")."'>-
								<input type=text size=4 name=year maxlength=4 value='".date("Y")."'>
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Received from</td>
							<td valign='center'>$cust</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Description</td>
							<td valign='center'><textarea col='18' rows='3' name='descript'>$descript</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cheque Number</td>
							<td valign='center'><input size='20' name='cheqnum' value='$cheqnum'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." <input type='text' size='13' name='amt' value='$amt'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Allocation</td>
							<td>$alls</td>
						</tr>
						<tr>
							<td><input type='submit' name='back' value='&laquo; Correction'></td>
							<td valign='center' align='right'><input type='submit' value='Allocate >'></td>
						</tr>
					</form>
					</table>";

	 $printCust = "
						<h3>Debtors Age Analysis</h3>
						<table ".TMPL_tblDflts.">
							<tr>
								<th>Acc no.</th>
								<th>Contact Name</th>
								<th>Tel No.</th>
								<th>Current</th>
								<th>30 days</th>
								<th>60 days</th>
								<th>90 days</th>
								<th>120 days</th>
								<th>Total Outstanding</th>
							</tr>";

	$curr = age($cusid, 29);
	$age30 = age($cusid, 59);
	$age60 = age($cusid, 89);
	$age90 = age($cusid, 119);
	$age120 = age($cusid, 149);

	# Customer total
	$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

	# Alternate bgcolor
	$printCust .= "
						<tr class='".bg_class()."'>
							<td>$cus[accno]</td>
							<td>$cus[contname]</td>
							<td>$cus[tel]</td>
							<td>".CUR." $curr</td>
							<td>".CUR." $age30</td>
							<td>".CUR." $age60</td>
							<td>".CUR." $age90</td>
							<td>".CUR." $age120</td>
							<td>".CUR." $custtot</td>
						</tr>";

	$printCust .= "<tr><td><br></td></tr></table>";

       $OUTPUT = "
       				<center>
					<table width='100%'>
						<tr>
							<td width='65%' align='left'>$add</td>
							<td valign='top' align='center'>
								<table ".TMPL_tblDflts." width='65%'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<script>document.write(getQuicklinkSpecial());</script>
									<script>document.write(getQuicklinkSpecial());</script>
									<tr class='".bg_class()."'>
										<td><a href='../main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</center>
					$printCust";
        return $OUTPUT;

}



# confirm
function alloc($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($back)) {
		return sel_cus($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($mon, "num", 1,2, "Invalid Date month.");
	$v->isOk ($year, "num", 1,4, "Invalid Date Year.");
	if(strlen($year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	if(($amt<0.01)){$v->isOk ($amt, "float", 5, 1, "Amount too small.");}
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	$date = $day."-".$mon."-".$year;
	if(!checkdate($mon, $day, $year)){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		//$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm."</li>".method($cusid);
	}

	$amt=sprint($amt);

        $out=0;

	$confirm = "
					<h3>New Bank Receipt</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='accnum' value=''>
						<input type='hidden' name='bankid' value='$bankid'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='all' value='$all'>
						<input type='hidden' name='cusid' value='$cusid'>
						<input type='hidden' name='day' value='$day'>
						<input type='hidden' name='mon' value='$mon'>
						<input type='hidden' name='year' value='$year'>
						<input type='hidden' name='descript' value='$descript'>
						<input type='hidden' name='cheqnum' value='$cheqnum'>
						<input type='hidden' name='amt' value='$amt'>";

        $i=0;

	# Get bank account name
        db_connect();
        $sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
        $bankRslt = db_exec($sql);
        $bank = pg_fetch_array($bankRslt);

	# Customer name
	$sql = "SELECT cusname,surname FROM customers WHERE cusnum = '$cusid' AND div = '".USER_DIV."'";
        $cusRslt = db_exec($sql);
        $cus = pg_fetch_array($cusRslt);

        $confirm .= "
        				<tr>
        					<th>Field</th>
        					<th>Value</th>
        				</tr>
						<tr class='".bg_class()."'>
							<td>Account</td>
							<td>$bank[accname] - $bank[bankname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Date</td>
							<td valign='center'>$date</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Received from</td>
							<td valign='center'>$cus[cusname] $cus[surname]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Description</td>
							<td valign='center'>".nl2br($descript)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cheque Number</td>
							<td valign='center'>$cheqnum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." $amt</td>
						</tr>";

	if($all==0)
	{
		$out=$amt;
		// Connect to database
		db_connect();
		$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		$i = 0;
		while(($inv = pg_fetch_array($prnInvRslt))and($out>0))
		{
			if($i==0)
			{
				$confirm .= "
								<tr><td colspan='2'><br></td></tr>
								<tr>
									<td colspan='2'><h3>Outstanding Invoices</h3></td>
								</tr>
								<tr>
									<th>Invoice</th>
									<th>Outstanding Amount</th>
									<th>Terms</th>
									<th>Date</th>
									<th>Amount</th>
								</tr>";
			}
			# alternate bgcolor and write list
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$invid = $inv['invid'];
			$confirm .= "
							<tr bgcolor='$bgColor'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
								<td>".CUR." $inv[balance]</td>
								<td>$inv[terms] days</td>
								<td>$inv[odate]</td>";
			if($out>=$inv['balance']) {$val=$inv['balance'];$out=$out-$inv['balance'];}
			else {$val=$out;$out=0;}
			$i++;
			$val=sprint($val);
			$confirm .= "<td><input type=hidden name='paidamt[$invid]' size=10 value='$val'>".CUR." $val</td></tr>";
		}

		$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND div = '".USER_DIV."' ORDER BY odate ASC";
		$prnInvRslt = db_exec($sql);
		while(($inv = pg_fetch_array($prnInvRslt))and($out>0))
		{
			if($i==0)
			{
				$confirm .= "
								<tr><td colspan='2'><br></td></tr>
								<tr>
									<td colspan='2'><h3>Outstanding Invoices</h3></td>
								</tr>
								<tr>
									<th>Invoice</th>
									<th>Outstanding Amount</th>
									<th></th>
									<th>Date</th>
									<th>Amount</th>
								</tr>";
			}
			# alternate bgcolor and write list
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$invid = $inv['invid'];
			$confirm .= "
							<tr bgcolor='$bgColor'>
								<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td>
								<td>".CUR." $inv[balance]</td>
								<td></td>
								<td>$inv[odate]</td>";
			if($out>=$inv['balance']) {$val=$inv['balance'];$out=$out-$inv['balance'];}
			else {$val=$out;$out=0;}
			$i++;
			$val=sprint($val);
			$confirm .= "<td><input type=hidden name='paidamt[$invid]' value='$val'><input type=hidden name=itype[$invid] value='Yes'>".CUR." $val</td></tr>";
		}
		$out=sprint($out);

		if($out>0) {

			/* START OPEN ITEMS */

			$ox="";

			db_conn('cubit');

			$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$Ri=db_exec($Sl) or errDie("Unable to get open items.");

			$open_out=$out;

			$i=0;

			while($od=pg_fetch_array($Ri)) {
				if($open_out==0) {
					continue;
				}
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$oid=$od['id'];
				if($open_out>=$od['balance']) {
					$open_amount[$oid]=$od['balance'];
					$open_out=sprint($open_out-$od['balance']);
					$ox.= "
							<tr bgcolor='$bgColor'>
								<td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
							</tr>";
				} elseif($open_out<$od['balance']) {
					$open_amount[$oid]=$open_out;
					$open_out=0;
					$ox.= "
							<tr bgcolor='$bgColor'>
								<td><input type=hidden size=20 name=open[$oid] value='$od[id]'>$od[type]</td>
								<td>".CUR." $od[balance]</td>
								<td>$od[date]</td>
								<td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>".CUR." $open_amount[$oid]</td>
							</tr>";
				}
				$i++;
			}



			if(open()) {


				$confirm .= "<tr><td colspan=2><br></td></tr>
				<tr><td colspan=2><h3>Outstanding Transactions</h3></td></tr>
				<tr><th>Description</th><th>Outstanding Amount</th><th>Date</th><th>Amount</th></tr>";


				$confirm.=$ox;

				$bout=$out;
				$out=$open_out;
				if($out>0) {
					$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
				}

				$out=$bout;


			} else  {$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
		}
	}

	if($all==1)
	{
		$confirm .= "<tr><td><table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td><br></td></tr>
		<tr><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days</th><th>Total Outstanding</th></tr>";

		$curr = age($cusid, 29);
		$age30 = age($cusid, 59);
		$age60 = age($cusid, 89);
		$age90 = age($cusid, 119);
		$age120 = age($cusid, 149);

		# Customer total
		$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

		if(!isset($OUT1)) {
			$OUT1="";
			$OUT2="";
			$OUT3="";
			$OUT4="";
			$OUT5="";
		}

		# Alternate bgcolor
		$confirm .= "<tr class='bg-odd'><td>".CUR." $curr</td><td>".CUR." $age30</td><td>".CUR." $age60</td><td>".CUR." $age90</td><td>".CUR." $age120</td><td>".CUR." $custtot</td></tr>";
		$confirm .= "<tr class='bg-odd'><td><input type=text size=7 name=out1 value='$OUT1'></td><td><input type=text size=7 name=out2 value='$OUT2'></td><td><input type=text size=7 name=out3 value='$OUT3'></td><td><input type=text size=7 name=out4 value='$OUT4'></td><td><input type=text size=7 name=out5 value='$OUT5'></td><td></td></tr>";

		$confirm .= "<tr><td><br></td></tr></table></td></tr>";
	}

	if($all==2)
	{
		// Connect to database
		db_connect();
		$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND div = '".USER_DIV."'";
		$prnInvRslt = db_exec($sql);
		$tempi=pg_numrows($prnInvRslt);
		if(pg_numrows($prnInvRslt) < 1){
			$sql = "SELECT invnum FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND div = '".USER_DIV."'";
			$prnInvRslt = db_exec($sql);

			if(open()) {
				if(pg_numrows($prnInvRslt) < 1){
					$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
					$Ri=db_exec($Sl) or errDie("Unable to get open items.");
					if(pg_numrows($Ri) < 1){
						return "<li class=err> There are no outstanding invoices for the selected debtor in Cubit.<br>
						To make a payment in advance please select Auto Allocation</li>".method($cusid);
					}
				}
			} else {
				return "<li class=err> There are no outstanding invoices for the selected debtor in Cubit.<br>
				To make a payment in advance please select Auto Allocation</li>".method($cusid);
			}

		} elseif ($tempi>0) {
			$confirm .= "<tr><td colspan=2><br></td></tr>
			<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
			<tr><th>Invoice</th><th>Outstanding Amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";
			$i = 0; // for bgcolor
			while($inv = pg_fetch_array($prnInvRslt)){
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				$val='';
				if(pg_numrows($prnInvRslt)==1) {$val=$amt;}
				if(isset($paidamt[$i])) {
					$val=$paidamt[$i];
				}
				$confirm .= "<td><input type=text name='paidamt[$invid]' size=10 value='$val'></td></tr>";
				$i++;
			}
		}

		$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND div = '".USER_DIV."'";
		$prnInvRslt = db_exec($sql);
		if(pg_numrows($prnInvRslt)>0) {
			$confirm .= "<tr><td colspan=2><br></td></tr>
			<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
			<tr><th>Invoice</th><th>Outstanding Amount</th><th></th><th>Date</th><th>Amount</th></tr>";
			//$i = 0; // for bgcolor
			while($inv = pg_fetch_array($prnInvRslt)){
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				$val='';
				if(pg_numrows($prnInvRslt)==1) {$val=$amt;}
				if(isset($paidamt[$i])) {
					$val=$paidamt[$i];
				}
				$confirm .= "<td><input type=text name='paidamt[$invid]' size=10 value='$val'><input type=hidden name=itype[$invid] value='YnYn'></td></tr>";
				$i++;
			}
		}


		if(open()) {
			db_conn('cubit');

			$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$Ri=db_exec($Sl) or errDie("Unable to get open items.");

			//$open_out=$out;
			$ox="";

			$i=0;

			while($od=pg_fetch_array($Ri)) {
				$oid=$od['id'];



				if(!isset($open_amount[$oid])) {
					$open_amount[$oid]="";
				}
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
				<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=text name='open_amount[$oid]' value='$open_amount[$oid]'>
				</td></tr>";

				$i++;
			}
			$confirm .= "<tr><td colspan=2><br></td></tr>
			<tr><td colspan=2><h3>Outstanding Transactions</h3></td></tr>
			<tr><th>Description</th><th>Outstanding Amount</th><th>Date</th><th>Amount</th></tr>$ox";
		}
	}

	$confirm .= "<input type=hidden name=out value='$out'>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>
        <p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

        return $confirm;
}

# confirm
function confirm($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		unset($back);
		return method($cusid);
	}

	if(!isset($out1)) {$out1='';}
	if(!isset($out2)) {$out2='';}
	if(!isset($out3)) {$out3='';}
	if(!isset($out4)) {$out4='';}
	if(!isset($out5)) {$out5='';}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(currant).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");

	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	if(isset($invids))
	{
		foreach($invids as $key => $value){
			if($paidamt[$invids[$key]] < 0.01){
				continue;
			}
			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No. [$key]");
			$v->isOk ($paidamt[$invids[$key]], "float", 1, 20, "Invalid amount to be paid. [$key]");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}

		$out +=0;
		$out1 +=0;
		$out2 +=0;
		$out3 +=0;
		$out4 +=0;
		$out5 +=0;

		$OUT1=$out1;
		$OUT2=$out2;
		$OUT3=$out3;
		$OUT4=$out4;
		$OUT5=$out5;

		$_POST['OUT1']=$OUT1;
		$_POST['OUT2']=$OUT2;
		$_POST['OUT3']=$OUT3;
		$_POST['OUT4']=$OUT4;
		$_POST['OUT5']=$OUT5;
		return $confirm."</li>".alloc($_POST);
	}
	$out +=0;
	$out1 +=0;
	$out2 +=0;
	$out3 +=0;
	$out4 +=0;
	$out5 +=0;

	$OUT1=$out1;
	$OUT2=$out2;
	$OUT3=$out3;
	$OUT4=$out4;
	$OUT5=$out5;



	# check invoice payments
	$tot = 0;
	if(isset($invids))
	{
		foreach($invids as $key => $value){
			if($paidamt[$invids[$key]] < 0.01){
				continue;
			}
			$tot += $paidamt[$invids[$key]];
		}
	}

	if(isset($open_amount)) {
		$tot += array_sum($open_amount);
	}

	$tot=sprint($tot);
	$amt=sprint($amt);
		$out=sprint($out);
		if(sprint(($tot+$out+$out1+$out2+$out3+$out4+$out5) - $amt) != 0){
				$_POST['OUT1']=$OUT1;
				$_POST['OUT2']=$OUT2;
				$_POST['OUT3']=$OUT3;
				$_POST['OUT4']=$OUT4;
				$_POST['OUT5']=$OUT5;
				return "<li class=err>The total amount for Invoices not equal to the amount received. Please check the details.</li>".alloc($_POST);
		}


	$confirm ="<h3>New Bank Receipt</h3>
        <h4>Confirm entry (Please check the details)</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=bankid value='$bankid'>
        <input type=hidden name=date value='$date'>
        <input type=hidden name=cusid value='$cusid'>
        <input type=hidden name=descript value='$descript'>
        <input type=hidden name=cheqnum value='$cheqnum'>
	<input type=hidden name=all value='$all'>
	<input type=hidden name=out value='$out'>
	<input type=hidden name=day value='$day'>
        <input type=hidden name=mon value='$mon'>
        <input type=hidden name=year value='$year'>
	<input type=hidden name=OUT1 value='$OUT1'>
	<input type=hidden name=OUT2 value='$OUT2'>
	<input type=hidden name=OUT3 value='$OUT3'>
	<input type=hidden name=OUT4 value='$OUT4'>
	<input type=hidden name=OUT5 value='$OUT5'>
	<input type=hidden name=amt value='$amt'>";


	# Get bank account name
        db_connect();
        $sql = "SELECT accname,bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
        $bankRslt = db_exec($sql);
        $bank = pg_fetch_array($bankRslt);

	# Customer name
	$sql = "SELECT cusname,surname FROM customers WHERE cusnum = '$cusid' AND div = '".USER_DIV."'";
        $cusRslt = db_exec($sql);
        $cus = pg_fetch_array($cusRslt);

	$confirm .="<tr><th>Field</th><th>Value</th></tr>
        <tr class='bg-odd'><td>Account</td><td>$bank[accname] - $bank[bankname]</td></tr>
        <tr class='bg-even'><td>Date</td><td valign=center>$date</td></tr>
        <tr class='bg-odd'><td>Received from</td><td valign=center>$cus[cusname] $cus[surname]</td></tr>
        <tr class='bg-even'><td>Description</td><td valign=center>$descript</td></tr>
        <tr class='bg-odd'><td>Cheque Number</td><td valign=center>$cheqnum</td></tr>
        <tr class='bg-even'><td>Amount</td><td valign=center>".CUR." $amt</td></tr>";

	$bgColor =TMPL_tblDataColor2;
	if($all==0)
	{
		// Layout
		$confirm .= "<tr><td colspan=2><br></td></tr>
		<tr>
			<td colspan='2'><h3>Invoices</h3></td>
		</tr>
		<!--<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>-->
		<tr>
			<th>Invoice Number</th>
			<th>Outstanding amount</th>
			<th>Terms</th>
			<th>Date</th>
			<th>Amount</th>
		</tr>";

		$i = 0; // for bgcolor
		if(isset($invids)) {
			foreach ($invids as $key => $value) {
				if ($paidamt[$invids[$key]] < 0.01) {
					continue;
				}

				db_connect();

				$ii=$invids[$key];
				if(!isset($itype[$ii])) {

					# Get all the details
					$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err> - Invalid ord number $invids[$key].";
					}
					$inv = pg_fetch_array($invRslt);

					$invid = $inv['invid'];

					# alternate bgcolor and write list
					$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
					$confirm .= "
					<tr bgcolor='$bgColor'>
						<td><input type='hidden' size='20' name='invids[]' value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
					$confirm .= "<td>".CUR." <input type=hidden name='paidamt[]' size=7 value='$paidamt[$invid]'>$paidamt[$invid]</td></tr>";
					$i++;
				} else {

					# Get all the details
					$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec($sql) or errDie("Unable to access database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err> - Invalid ord number $invids[$key].";
					}
					$inv = pg_fetch_array($invRslt);

					$invid = $inv['invid'];

					# alternate bgcolor and write list
					$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
					$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
					$confirm .= "<td>".CUR." <input type=hidden name='paidamt[]' size=7 value='$paidamt[$invid]'> <input type=hidden name=itype[$invid] value='y'>$paidamt[$invid]</td></tr>";
					$i++;
				}
			}
		}

		if ($out > 0) {
			/* START OPEN ITEMS */
			$ox="";

			db_conn('cubit');
			$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$Ri=db_exec($Sl) or errDie("Unable to get open items.");

			$open_out=$out;

			$i=0;

			while($od=pg_fetch_array($Ri)) {
				if($open_out==0) {
					continue;
				}
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$oid=$od['id'];
				if($open_out>=$od['balance']) {
					$open_amount[$oid]=$od['balance'];
					$open_out=sprint($open_out-$od['balance']);
					$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
					<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
					".CUR." $open_amount[$oid]</td></tr>";
				} elseif($open_out<$od['balance']) {
					$open_amount[$oid]=$open_out;
					$open_out=0;
					$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$od[id]'>$od[type]</td>
					<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
					".CUR." $open_amount[$oid]</td></tr>";
				}
				$i++;
			}



			if(open()) {


				$confirm .= "<tr><td colspan=2><br></td></tr>
				<tr><td colspan=2><h3>Outstanding Transactions</h3></td></tr>
				<tr><th>Description</th><th>Outstanding Amount</th><th>Date</th><th>Amount</th></tr>";


				$confirm.=$ox;

				$bout=$out;
				$out=$open_out;
				if($out>0) {
					$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
				}

				$out=$bout;


			} else  {$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
		}

	}
	if($all==1)
	{
		$age30 = age($cusid, 59);
		$age60 = age($cusid, 89);
		$age90 = age($cusid, 119);
		$age120 = age($cusid, 149);

		$i = 0;
		if($out1>0)
		{
			// Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND age = 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND odate >='".extlib_ago(29)."' AND odate <='".extlib_ago(-1)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				if($out1>=$inv['balance']) {$val=$inv['balance'];$out1=$out1-$inv['balance'];}
				else {$val=$out1;$out1=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'>".CUR." $val</td></tr>";
				$i++;
			}

			// Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND age = 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND sdate >='".extlib_ago(29)."' AND sdate <='".extlib_ago(-1)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out1>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th></th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				if($out1>=$inv['balance']) {$val=$inv['balance'];$out1=$out1-$inv['balance'];}
				else {$val=$out1;$out1=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'><input type=hidden name=itype[$invid] value='n'>".CUR." $val</td></tr>";
				$i++;
			}

			$out1=sprint($out1);
			if($out1>0) {$confirm .="<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will credit the client's account with ".CUR." $out1 (Current) </b></td></tr>";}
		}
		if($out2>0)
		{
			if($out2>$age30){

				$_POST['OUT1']=$OUT1;
				$_POST['OUT2']=$OUT2;
				$_POST['OUT3']=$OUT3;
				$_POST['OUT4']=$OUT4;
				$_POST['OUT5']=$OUT5;

				return "<li class=err>You cannot allocate ".CUR." $out2 to 30 days, the client's 30 days balance is only ".CUR." $age30</li>".alloc($_POST);
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND age = 1 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND odate >='".extlib_ago(59)."' AND odate <='".extlib_ago(29)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				if($out2>=$inv['balance']) {$val=$inv['balance'];$out2=$out2-$inv['balance'];}
				else {$val=$out2;$out2=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'>".CUR." $val</td></tr>";
				$i++;
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND age = 1 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND sdate >='".extlib_ago(59)."' AND sdate <='".extlib_ago(29)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out2>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th></th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				if($out2>=$inv['balance']) {$val=$inv['balance'];$out2=$out2-$inv['balance'];}
				else {$val=$out2;$out2=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'><input type=hidden name=itype[$invid] value='no'>".CUR." $val</td></tr>";
				$i++;
			}
			$out2=sprint($out2);
			if($out2>0) {$confirm .="<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will credit the client's account with ".CUR." $out2 (30 days)</b></td></tr>";}
		}
		if($out3>0)
		{
			if($out3>$age60) {

				$_POST['OUT1']=$OUT1;
				$_POST['OUT2']=$OUT2;
				$_POST['OUT3']=$OUT3;
				$_POST['OUT4']=$OUT4;
				$_POST['OUT5']=$OUT5;

				return "<li class=err>You cannot allocate ".CUR." $out3 to 60 days, the client only owe you ".CUR." $age60 </li>".alloc($_POST);

			}
			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND age = 2 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND odate >='".extlib_ago(89)."' AND odate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				if($out3>=$inv['balance']) {$val=$inv['balance'];$out3=$out3-$inv['balance'];}
				else {$val=$out3;$out3=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'>".CUR." $val</td></tr>";
				$i++;
			}
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND age = 2 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND sdate >='".extlib_ago(89)."' AND sdate <='".extlib_ago(59)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out3>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th></th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				if($out3>=$inv['balance']) {$val=$inv['balance'];$out3=$out3-$inv['balance'];}
				else {$val=$out3;$out3=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'><input type=hidden name=itype[$invid] value='1'>".CUR." $val</td></tr>";
				$i++;
			}
			$out3=sprint($out3);
			if($out3>0) {$confirm .="<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will credit the client's account with ".CUR." $out3 (60 days)</b></td></tr>";}
		}
		if($out4>0)
		{
			if($out4>$age90) {
				$_POST['OUT1']=$OUT1;
				$_POST['OUT2']=$OUT2;
				$_POST['OUT3']=$OUT3;
				$_POST['OUT4']=$OUT4;
				$_POST['OUT5']=$OUT5;

				return "<li class=err>You cannot allocate ".CUR." $out4 to 90 days, the client only owe you ".CUR." $age90</li>".alloc($_POST);
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND age = 3 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND odate >='".extlib_ago(119)."' AND odate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				if($out4>=$inv['balance']) {$val=$inv['balance'];$out4=$out4-$inv['balance'];}
				else {$val=$out4;$out4=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'>".CUR." $val</td></tr>";
				$i++;
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND age = 3 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND sdate >='".extlib_ago(119)."' AND sdate <='".extlib_ago(89)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out4>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th></th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				if($out4>=$inv['balance']) {$val=$inv['balance'];$out4=$out4-$inv['balance'];}
				else {$val=$out4;$out4=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'><input type=hidden name=itype[$invid] value='2'>".CUR." $val</td></tr>";
				$i++;
			}
			$out4=sprint($out4);
			if($out4>0) {$confirm .="<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will credit the client's account with ".CUR." $out4 (90 days)</b></td></tr>";}
		}
		if($out5>0)
		{
			if($out5>$age120) {
				$_POST['OUT1']=$OUT1;
				$_POST['OUT2']=$OUT2;
				$_POST['OUT3']=$OUT3;
				$_POST['OUT4']=$OUT4;
				$_POST['OUT5']=$OUT5;

				return "<lI class=err>You cannot allocate ".CUR." $out5 to 120 days, the client only owe you ".CUR." $age120</li>".alloc($_POST);

			}
			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND age = 4 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE cusnum = '$cusid' AND printed = 'y' AND balance>0 AND odate >='".extlib_ago(149)."' AND odate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				if($out5>=$inv['balance']) {$val=$inv['balance'];$out5=$out5-$inv['balance'];}
				else {$val=$out5;$out5=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'>".CUR." $val</td></tr>";
				$i++;
			}

			# Connect to database
			if(div_isset("DEBT_AGE", "mon")){
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND age = 4 AND div = '".USER_DIV."' ORDER BY odate ASC";
			}else{
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE cusid = '$cusid' AND done = 'y' AND balance>0 AND sdate >='".extlib_ago(149)."' AND sdate <='".extlib_ago(119)."' AND div = '".USER_DIV."' ORDER BY odate ASC";
			}
			db_connect();
			$prnInvRslt = db_exec($sql);
			while(($inv = pg_fetch_array($prnInvRslt))and($out5>0))
			{
				if($i==0)
				{
					$confirm .= "<tr><td colspan=2><br></td></tr>
					<tr><td colspan=2><h3>Outstanding Invoices</h3></td></tr>
					<tr><th>Invoice</th><th>Outstanding Amount</th><th></th><th>Date</th><th>Amount</th></tr>";
				}
				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$invid = $inv['invid'];
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				if($out5>=$inv['balance']) {$val=$inv['balance'];$out5=$out5-$inv['balance'];}
				else {$val=$out5;$out5=0;}

				$confirm .= "<td><input type=hidden name='paidamt[]' size=10 value='$val'><input type=hidden name=itype[$invid] value='my'>".CUR." $val</td></tr>";
				$i++;
			}
			$out5=sprint($out5);
			if($out5>0) {$confirm .="<tr bgcolor='$bgColor'><td colspan=5><b>A general transaction will credit the client's account with ".CUR." $out5 (120 days)</b></td></tr>";}
		}
	}

	if($all==2)
	{
		// Layout
		$confirm .= "<tr><td colspan=2><br></td></tr>
		<tr><td colspan=2><h3>Invoices</h3></td></tr>
		<!--<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>-->
		<tr><th>Invoice Number</th><th>Outstanding amount</th><th>Terms</th><th>Date</th><th>Amount</th></tr>";

		$i = 0; // for bgcolor
		foreach($invids as $key => $value){
			if($paidamt[$invids[$key]] < 0.01){
				continue;
			}

			$ii=$invids[$key];
			if(!isset($itype[$ii])) {

				db_connect();
				# Get all the details
				$sql = "SELECT invnum,invid,balance,terms,odate FROM invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1) {
					return "<li class=err> - Invalid ord number $invids[$key].";
				}
				$inv = pg_fetch_array($invRslt);

				$invid = $inv['invid'];

				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td>$inv[terms] days</td><td>$inv[odate]</td>";
				$confirm .= "<td>".CUR." <input type=hidden name='paidamt[]' size=7 value='$paidamt[$invid]'>$paidamt[$invid]</td></tr>";
				$i++;
			} else {

				db_connect();
				# Get all the details
				$sql = "SELECT invnum,invid,balance,sdate as odate FROM nons_invoices WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
				$invRslt = db_exec($sql) or errDie("Unable to access database.");
				if (pg_numrows ($invRslt) < 1) {
					return "<li class=err> - Invalid ord number $invids[$key].";
				}
				$inv = pg_fetch_array($invRslt);

				$invid = $inv['invid'];

				# alternate bgcolor and write list
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$confirm .= "<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=invids[] value='$inv[invid]'>$inv[invnum]</td><td>".CUR." $inv[balance]</td><td></td><td>$inv[odate]</td>";
				$confirm .= "<td>".CUR." <input type=hidden name='paidamt[]' size=7 value='$paidamt[$invid]'><input type=hidden name=itype[$invid] value='PcP'>$paidamt[$invid]</td></tr>";
				$i++;
			}
		}

		if(open()) {
			db_conn('cubit');

			$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
			$Ri=db_exec($Sl) or errDie("Unable to get open items.");

			//$open_out=$out;
			$ox="";

			$i=0;

			while($od=pg_fetch_array($Ri)) {
				$oid=$od['id'];

				if($open_amount[$oid]==0) {
					continue;
				}

				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
				<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
				".CUR." $open_amount[$oid]</td></tr>";

				$i++;
			}
			$confirm .= "<tr><td colspan=2><br></td></tr>
			<tr><td colspan=2><h3>Outstanding Transactions</h3></td></tr>
			<tr><th>Description</th><th>Outstanding Amount</th><th>Date</th><th>Amount</th></tr>$ox";
		}
	}
		$confirm .= "<input type=hidden name=out1 value='$out1'>
	<input type=hidden name=out2 value='$out2'>
	<input type=hidden name=out3 value='$out3'>
	<input type=hidden name=out4 value='$out4'>
	<input type=hidden name=out5 value='$out5'>
		<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo'></td></tr>
        </form></table>
        <p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

        return $confirm;
}

# write
function write($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		unset($_POST["back"]);
		return alloc($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($all, "num", 1,1, "Invalid allocation.");
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1, 14, "Invalid Date.");
	$v->isOk ($out, "float", 1, 10, "Invalid out amount.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amt, "float", 1, 10, "Invalid amount.");
	$v->isOk ($cusid, "num", 1, 10, "Invalid customer number.");
	$v->isOk ($out1, "float", 0, 10, "Invalid paid amount(currant).");
	$v->isOk ($out2, "float", 0, 10, "Invalid paid amount(30).");
	$v->isOk ($out3, "float", 0, 10, "Invalid paid amount(60).");
	$v->isOk ($out4, "float", 0, 10, "Invalid paid amount(90).");
	$v->isOk ($out5, "float", 0, 10, "Invalid paid amount(120).");

	if(isset($invids))
	{
		foreach($invids as $key => $value){
   			$v->isOk ($invids[$key], "num", 1, 50, "Invalid Invoice No.");
			$v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# get hook account number
        core_connect();
        $sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
        $rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
        # check if link exists
        if(pg_numrows($rslt) <1){
                return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
        }
        $bank = pg_fetch_array($rslt);

	db_connect();
	# Customer name
	$sql = "SELECT cusnum,deptid,cusname,surname FROM customers WHERE cusnum = '$cusid' AND div = '".USER_DIV."'";
        $cusRslt = db_exec($sql);
        $cus = pg_fetch_array($cusRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT debtacc FROM departments WHERE deptid ='$cus[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	# date format
	$sdate = explode("-", $date);
	$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
	$cheqnum = 0 + $cheqnum;
	$pay = "";

	$accdate=$sdate;

	# Paid invoices
	$invidsers = "";
	$rinvids = "";
	$amounts = "";
	$invprds = "";
	$rages = "";

	db_connect();

	if($all==0)
	{
		# Begin updates
		# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# update the customer (make balance less)
			$sql = "UPDATE customers SET balance = (balance - '$amt'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			if(isset($invids))
			{
				foreach($invids as $key => $value)
				{
					$ii=$invids[$key];
					if(!isset($itype[$ii])) {

						# Get debt invoice info
						$sql = "SELECT prd,invnum FROM invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class=err>Invalid Invoice Number.";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE invoices SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$sql = "UPDATE  open_stmnt SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] +=0;
						# record the payment on the statement
						$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key], "c");
						db_connect();

						$rinvids .= "|$invids[$key]";
						$amounts .= "|$paidamt[$key]";
						if($inv['prd']==0) {
							$inv['prd']=PRD_DB;
						}
						$invprds .= "|$inv[prd]";
						$rages .= "|0";
						$invidsers .= " - $inv[invnum]";
					} else {

						# Get debt invoice info
						$sql = "SELECT prd,invnum,descrip,age FROM nons_invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class=err>Invalid Invoice Number.";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE nons_invoices SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$sql = "UPDATE  open_stmnt SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] +=0;
						# record the payment on the statement
						$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$key], "c");
						db_connect();

						recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$accdate);

						$rinvids .= "|$invids[$key]";
						$amounts .= "|$paidamt[$key]";
						$invprds .= "|0";
						$rages .= "|$inv[age]";
						$invidsers .= " - $inv[invnum]";
					}
				}
			}

			# record the payment record
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$refnum = getrefnum($accdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				return "Invalid bank acc.";
			}
			$link=pg_fetch_array($Rx);

			writetrans($link['accnum'],$dept['debtacc'], $accdate, $refnum, $amt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");
			db_conn('cubit');

			if($out>0) {

				/* START OPEN ITEMS */

				$ox="";

				db_conn('cubit');

				$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
				$Ri=db_exec($Sl) or errDie("Unable to get open items.");

				$open_out=$out;

				$i=0;

				while($od=pg_fetch_array($Ri)) {
					if($open_out==0) {
						continue;
					}
					$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
					$oid=$od['id'];
					if($open_out>=$od['balance']) {
						$open_amount[$oid]=$od['balance'];
						$open_out=sprint($open_out-$od['balance']);
						$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
						<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
						".CUR." $open_amount[$oid]</td></tr>";

						$Sl="UPDATE open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
						$Ri=db_exec($Sl) or errDie("Unable to update statement.");

					} elseif($open_out<$od['balance']) {
						$open_amount[$oid]=$open_out;
						$open_out=0;
						$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$od[id]'>$od[type]</td>
						<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
						".CUR." $open_amount[$oid]</td></tr>";

						$Sl="UPDATE open_stmnt SET balance=balance-'$open_amount[$oid]' WHERE id='$oid'";
						$Ri=db_exec($Sl)or errDie("Unable to update statement.");
					}
					$i++;
				}



				if(open()) {


// 					$confirm .= "<tr><td colspan=2><br></td></tr>
// 					<tr><td colspan=2><h3>Outstanding Transactions</h3></td></tr>
// 					<tr><th>Description</th><th>Outstanding Amount</th><th>Date</th><th>Amount</th></tr>";


					//$confirm.=$ox;

					$bout=$out;
					$out=$open_out;
					if($out>0) {
						$sql = "INSERT INTO open_stmnt(cusnum, invid, amount, balance, date, type, st, div) VALUES('$cus[cusnum]', '0', '-$out', '-$out', '$sdate', 'Payment Received', 'n', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);
						//$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";
					}

					$out=$bout;


				} else  {//$confirm .="<tr class='bg-even'><td colspan=4><b>A general transaction will credit the client's account with ".CUR." $out </b></td></tr>";}
			}

			}

			if($out>0) {
			recordCT($out, $cus['cusnum'],0,$accdate);
			$Sl = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','0','".($out*(-1))."','$sdate', 'Payment Received.', '".USER_DIV."')";
			$Rs= db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

			custledger($cus['cusnum'], $bank['accnum'], $sdate, "PAYMENT", "Payment received.", $out, "c");
			db_connect();
		}
		# Commit updates
		# pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}

	db_connect();

	if($all==1)
	{
		# Begin updates
		# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# update the customer (make balance less)
			$sql = "UPDATE customers SET balance = (balance - '$amt'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			if(isset($invids))
			{
				foreach($invids as $key => $value)
				{
					$ii=$invids[$key];
					if(!isset($itype[$ii])) {

						# Get debt invoice info
						$sql = "SELECT prd,invnum FROM invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class=err>Invalid Invoice Number.";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE invoices SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$sql = "UPDATE  open_stmnt SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] +=0;
						# record the payment on the statement
						$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key], "c");
						db_connect();

						$rinvids .= "|$invids[$key]";
						$amounts .= "|$paidamt[$key]";
						if($inv['prd']==0) {
                                                        $inv['prd']=PRD_DB;
                                                }
						$invprds .= "|$inv[prd]";
						$rages .= "|0";
						$invidsers .= " - $inv[invnum]";

					} else {

						# Get debt invoice info
						$sql = "SELECT prd,invnum,descrip,age FROM nons_invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
						$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
						if (pg_numrows ($invRslt) < 1) {
							return "<li class=err>Invalid Invoice Number.";
						}
						$inv = pg_fetch_array($invRslt);

						# reduce the money that has been paid
						$sql = "UPDATE nons_invoices SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						$inv['invnum'] +=0;
						# record the payment on the statement
						$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."')";
						$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

						$sql = "UPDATE  open_stmnt SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
						$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

						custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$key], "c");
						db_connect();

						recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$accdate);

						$rinvids .= "|$invids[$key]";
						$amounts .= "|$paidamt[$key]";
						$invprds .= "|0";
						$rages .= "|$inv[age]";
						$invidsers .= " - $inv[invnum]";

					}
				}
			}

			# record the payment record
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$refnum = getrefnum($accdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				return "Invalid bank acc.";
			}
			$link=pg_fetch_array($Rx);

			writetrans($link['accnum'],$dept['debtacc'], $accdate, $refnum, $amt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");

			db_conn('cubit');

			if(($out1+$out2+$out3+$out4+$out5)>0) {
				$Sl = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','0','".(($out1+$out2+$out3+$out4+$out5)*(-1))."','$sdate', 'Payment Received.', '".USER_DIV."')";
				$Rs= db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

				custledger($cus['cusnum'], $bank['accnum'], $sdate, "PAYMENT", "Payment received.", ($out1+$out2+$out3+$out4+$out5), "c");
				db_connect();
			}

			if($out1>0) {recordCT($out1, $cus['cusnum'],0,$accdate);}
			if($out2>0) {recordCT($out2, $cus['cusnum'],1,$accdate);}
			if($out3>0) {recordCT($out3, $cus['cusnum'],2,$accdate);}
			if($out4>0) {recordCT($out4, $cus['cusnum'],3,$accdate);}
			if($out5>0) {recordCT($out5, $cus['cusnum'],4,$accdate);}
		# Commit updates
		# pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}


	if($all==2)
	{
		# Begin updates
		//pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
			# update the customer (make balance less)

			$sql = "UPDATE customers SET balance = (balance - '$amt'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# Debtors
			foreach($invids as $key => $value)
			{
				$ii=$invids[$key];
				if(!isset($itype[$ii])) {

					# Get debt invoice info
					$sql = "SELECT prd,invnum FROM invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$inv = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE invoices SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE  open_stmnt SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Invoice No. $inv[invnum]', '".USER_DIV."')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Invoice No. $inv[invnum]", $paidamt[$key], "c");
					db_connect();

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					if($inv['prd']==0) {
						$inv['prd']=PRD_DB;
					}
					$invprds .= "|$inv[prd]";
					$rages .= "|0";
					$invidsers .= " - $inv[invnum]";
				} else {

					# Get debt invoice info
					$sql = "SELECT prd,invnum,descrip,age FROM nons_invoices WHERE invid ='$invids[$key]' AND div = '".USER_DIV."'";
					$invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
					if (pg_numrows ($invRslt) < 1) {
						return "<li class=err>Invalid Invoice Number.";
					}
					$inv = pg_fetch_array($invRslt);

					# reduce the money that has been paid
					$sql = "UPDATE nons_invoices SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$invids[$key]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					$sql = "UPDATE  open_stmnt SET balance = (balance - $paidamt[$key]::numeric(13,2)) WHERE invid = '$inv[invnum]' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','$inv[invnum]','".($paidamt[$key] - ($paidamt[$key] * 2))."','$sdate', 'Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]', '".USER_DIV."')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank['accnum'], $sdate, $inv['invnum'], "Payment for Non Stock Invoice No. $inv[invnum] - $inv[descrip]", $paidamt[$key], "c");
					db_connect();

					recordCT($paidamt[$key], $cus['cusnum'],$inv['age'],$accdate);

					$rinvids .= "|$invids[$key]";
					$amounts .= "|$paidamt[$key]";
					$invprds .= "|0";
					$rages .= "|$inv[age]";
					$invidsers .= " - $inv[invnum]";
				}
			}

			if(open()) {
				db_conn('cubit');

				$Sl="SELECT * FROM open_stmnt WHERE balance>0 AND cusnum='$cusid' ORDER BY date";
				$Ri=db_exec($Sl) or errDie("Unable to get open items.");

				//$open_out=$out;
				$ox="";

				$i=0;

				while($od=pg_fetch_array($Ri)) {
					$oid=$od['id'];

					if($open_amount[$oid]==0) {
						continue;
					}

					$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
					$ox.="<tr bgcolor='$bgColor'><td><input type=hidden size=20 name=open[$oid] value='$oid'>$od[type]</td>
					<td>".CUR." $od[balance]</td><td>$od[date]</td><td><input type=hidden name='open_amount[$oid]' value='$open_amount[$oid]'>
					".CUR." $open_amount[$oid]</td></tr>";

					$i++;

					$sql = "UPDATE  open_stmnt SET balance = (balance - $open_amount[$oid] ::numeric(13,2)) WHERE id = '$oid' AND div = '".USER_DIV."'";
					$payRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

					# record the payment on the statement
					$sql = "INSERT INTO stmnt(cusnum, invid, amount, date, type, div) VALUES('$cus[cusnum]','0','".-$open_amount[$oid] ."','$sdate', 'Payment received', '".USER_DIV."')";
					$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

					custledger($cus['cusnum'], $bank['accnum'], $sdate, 0, "Payment received", $open_amount[$oid] , "c");
					db_connect();

					recordCT($open_amount[$oid], $cus['cusnum'],0,$accdate);
				}

			}

			# record the payment record
			$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, cusnum, rinvids, amounts, invprds, rages, div) VALUES ('$bankid', 'deposit', '$sdate', '$cus[cusname] $cus[surname]', 'Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]', '$cheqnum', '$amt', 'no', '$dept[debtacc]', '$cus[cusnum]', '$rinvids', '$amounts', '$invprds', '$rages', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$refnum = getrefnum($accdate);

			db_conn('core');
			$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
			$Rx=db_exec($Sl) or errDie("Uanble to get bank acc.");
			if(pg_numrows($Rx)<1) {
				return "Invalid bank acc.";
			}
			$link=pg_fetch_array($Rx);

			writetrans($link['accnum'],$dept['debtacc'], $accdate, $refnum, $amt, "Payment for Invoices $invidsers from customer $cus[cusname] $cus[surname]");


		# Commit updates
		//pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
	}


	db_conn('cubit');
	/* start moving invoices */

	# move invoices that are fully paid
	$sql = "SELECT * FROM invoices WHERE balance = 0 AND printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
	$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

	while($invb = pg_fetch_array($invbRslt))
	{
		if($invb['prd'] == 0)
			$invb['prd'] = PRD_DB;
		db_conn($invb['prd']);

		# Insert invoice to period DB
		$sql = "INSERT INTO invoices(invid,invnum, deptid, cusnum, deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, age, comm, discount, delivery, printed, done, username, docref, div,prd,delvat)";
		$sql .= " VALUES('$invb[invid]','$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]', '$invb[chrgvat]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' , '$invb[total]', '0', '$invb[age]', '$invb[comm]', '$invb[discount]', '$invb[delivery]', 'y', 'y', '".USER_NAME."', '$invb[docref]','".USER_DIV."','$invb[prd]','$invb[delvat]')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		db_connect();
		$sql = "INSERT INTO movinv(invtype, invnum, prd, docref, div) VALUES('inv', '$invb[invnum]', '$invb[prd]', '$invb[docref]', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			db_conn($invb['prd']);

			$stkd['vatcode']+=0;
			$stkd['account']+=0;

			# insert invoice items
			$sql = "INSERT INTO inv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, div,vatcode,account,description) VALUES
			('$invb[invid]', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '".USER_DIV."','$stkd[vatcode]','$stkd[account]','$stkd[description]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		db_connect();
		# Remove those invoices from running DB
		$sql = "DELETE FROM invoices WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		# Remove those invoice items from running DB
		$sql = "DELETE FROM inv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* end moving invoices */

        # status report
		$write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>Bank Receipt</th></tr>
        <tr class=datacell><td>Bank Receipt added to cash book.</td></tr>
        </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=50%>$write</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
        <tr><th>Quick Links</th></tr>
        <tr class='bg-odd'><td><a href='bank-pay-add.php'>Add Bank Payment</a></td></tr>
        <tr class='bg-odd'><td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td></tr>
        <tr class='bg-odd'><td><a href='bank-recpt-inv.php'>Add Customer Payment</a></td></tr>
		<tr class='bg-odd'><td><a href='cashbook-view.php'>View Cash Book</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
        </table>
        </td></tr></table>";

        return $OUTPUT;
}

function age($cusnum, $days)
{
	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	if(div_isset("DEBT_AGE", "mon")){
		switch($days){
			case 29:
				return ageage($cusnum, 0);
			case 59:
				return ageage($cusnum, 1);
			case 89:
				return ageage($cusnum, 2);
			case 119:
				return ageage($cusnum, 3);
			case 149:
				return ageage($cusnum, 4);
		}
	}

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;
}

function ageage($cusnum, $age){
	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint($sum['sum'] + $sumb ['sum']) + 0;
}


# records for CT
function recordCT($amount, $cusnum, $age,$date="")
{
	/*
	db_connect();

	if($date=="") {
		$date=date("Y-m-d");
	}

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance > 0 AND div = '".USER_DIV."' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] > $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance - '$amount'::numeric(13,2)) WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount =0 ;
				}else{
					# remove small ones
					//if($dat['balance'] > $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					//}
				}
			}
		}
		if($amount > 0){
			$amount = ($amount * (-1));

			/* Make transaction record for age analysis
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		$amount = ($amount * (-1));

		/* Make transaction record for age analysis
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	*/

	db_connect();

	if($date=="") {
		$date=date("Y-m-d");
	}

	$amount = ($amount * (-1));

	$sql = "INSERT INTO custran(cusnum, odate, balance, div,age) VALUES('$cusnum', '$date', '$amount', '".USER_DIV."','$age')";
	$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
}
?>

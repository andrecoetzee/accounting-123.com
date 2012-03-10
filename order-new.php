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
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($HTTP_GET_VARS["stkid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
}elseif (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
                case "details":
			$OUTPUT = details($HTTP_POST_VARS);
			break;

                case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
			break;

                default:
				$OUTPUT = slctStk();
	}
} else {
        # Display default output
        $OUTPUT = slctStk();
}

# get templete
require("template.php");

# Default view
function slctStk()
{
        # Select Stock
		db_connect();
		$stock = "<select name='stkid'>";
        $sql = "SELECT stkid,stkdes,stkcod FROM stock";
        $stkRslt = db_exec($sql);
        if(pg_numrows($stkRslt) < 1){
                return "There is no stock found in Cubit.";
        }else{
                while($stk = pg_fetch_array($stkRslt)){
                        $stock .= "<option value='$stk[stkid]'>($stk[stkcod]) $stk[stkdes]</option>";
                }
        }
        $stock .="</select>";

		//layout
		$slct = "
		<h3>Select Stock</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=details>
		<tr><th>Select Stock</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td valign=center>$stock</td></tr>
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'><input type=submit value='Buy &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $slct;
}

function details($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($stkid, "num", 1, 50, "Invalid Stock ID.");

    # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

		# Select Stock
		db_connect();
		$sql = "SELECT stkid,stkdes,stkcod,buom,suom FROM stock WHERE stkid = '$stkid'";
        $stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $stk = pg_fetch_array($stkRslt);
        }

		# select bank account
		$bank = "<select name=bankacc>";
		$sql = "SELECT * FROM bankacct ORDER BY accname ASC";
		$banks = db_exec($sql);

		if(pg_numrows($banks) < 1){
				return "<li class=err> There are no bank accounts found in Cubit.
				<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
		}
		while($acc = pg_fetch_array($banks)){
				$bank .= "<option value=$acc[bankid]>$acc[accname]</option>";
		}
		$bank .="</select>";

        // Layout
		$view = "<h3>Stock Order</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name=stkid value='$stkid'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
        	<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock description</td><td>$stk[stkdes]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier</td><td><input type=text size=20 name='supplier'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel No.</td><td><input type=text size=14 name='suptel'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td><input type=text size=14 name='supfax'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Address</td><td><textarea rows=5 cols=18 name='addr'></textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order Date</td><td valign=center><input type=text size=2 name=oday maxlength=2 value='".date("d")."'>-<input type=text size=2 name=omon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=oyear maxlength=4 value='".date("Y")."'> DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Date</td><td valign=center><input type=text size=2 name=dday maxlength=2 value=''>-<input type=text size=2 name=dmon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=dyear maxlength=4 value='".date("Y")."'> DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Buying units</td><td><input type=text size=10 name='buom'> x $stk[buom]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Selling units</td><td><input type=text size=10 name='suom'> x $stk[suom]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Cost Amount</td><td>".CUR." <input type=text size=9 name='csamt'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account</td><td>$bank</td></tr>
			<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Add &raquo'></td></tr>
        </table>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        	<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </form>
        </table>";

        return $view;
}

# confirm
function confirm($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($stkid, "num", 1, 50, "Invalid Stock ID.");
		$v->isOk ($supplier, "string", 1,255, "Invalid supplier name.");
		$v->isOk ($suptel, "string", 1,20, "Invalid supplier tel no.");
		$v->isOk ($supfax, "string", 0,20, "Invalid supplier fax no.");
		$v->isOk ($addr, "string", 0,500, "Invalid supplier address.");
		$v->isOk ($oday, "num", 1,2, "Invalid order Date day.");
		$v->isOk ($omon, "num", 1,2, "Invalid order Date month.");
		$v->isOk ($oyear, "num", 1,4, "Invalid order Date Year.");
		$v->isOk ($dday, "num", 1,2, "Invalid delivery Date day.");
		$v->isOk ($dmon, "num", 1,2, "Invalid delivery Date month.");
		$v->isOk ($dyear, "num", 1,4, "Invalid delivery Date Year.");
		$v->isOk ($csamt, "float", 1, 20, "Invalid cost amount.");
		$v->isOk ($buom, "num", 0, 20, "Invalid buying units.");
        $v->isOk ($suom, "num", 0, 20, "Invalid selling units.");
		$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account.");
		$odate = $oday."-".$omon."-".$oyear;
		$deldate = $dday."-".$dmon."-".$dyear;

        if(!checkdate($omon, $oday, $oyear)){
                $v->isOk ($odate, "num", 1, 1, "Invalid date.");
        }
		if(!checkdate($dmon, $dday, $dyear)){
                $v->isOk ($deldate, "num", 1, 1, "Invalid date.");
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

		$buom += 0;
		$suom += 0;

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid'";
        $stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $stk = pg_fetch_array($stkRslt);
		}

		if($buom < 1 && $suom < 1){
				return "<li calss=err> Your units ordered calculate to zero. Please enter valid values.";
		}

		# Get bank account name
        db_connect();
        $sql = "SELECT * FROM bankacct WHERE bankid = '$bankacc'";
        $bankRslt = db_exec($sql);

        if(pg_numrows($bankRslt) < 1){
                return "<li class=err>ERROR : Invalid Bank Account Number.
                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }
        $bank = pg_fetch_array($bankRslt);

		// Layout
		$confirm = "<h3>Stock Order</h3>
        <h4>Confirm Entry</h4>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name=stkid value='$stkid'>
		<input type=hidden name=supplier value='$supplier'>
		<input type=hidden name=suptel value='$suptel'>
		<input type=hidden name=supfax value='$supfax'>
		<input type=hidden name=addr value='$addr'>
		<input type=hidden name=odate value='$odate'>
		<input type=hidden name=deldate value='$deldate'>
		<input type=hidden name=csamt value='$csamt'>
		<input type=hidden name=buom value='$buom'>
		<input type=hidden name=suom value='$suom'>
		<input type=hidden name=bankacc value='$bankacc'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
        	<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock description</td><td>$stk[stkdes]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier</td><td>$supplier</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel No.</td><td>$suptel</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td>$supfax</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Address</td><td><pre>$addr</pre></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order Date</td><td>$odate</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Date</td><td>$deldate</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Buying units</td><td>$buom x $stk[buom]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Selling units</td><td>$suom x $stk[suom]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Cost Amount</td><td>".CUR." $csamt</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account</td><td>$bank[accname]</td></tr>
			<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        </table>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        	<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </form>
        </table>";

	return $confirm;
}

# write
function write($HTTP_POST_VARS)
{

		//processes
		db_connect();
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($stkid, "num", 1, 50, "Invalid Stock ID.");
		$v->isOk ($supplier, "string", 1,255, "Invalid supplier name.");
		$v->isOk ($suptel, "string", 1,20, "Invalid supplier tel no.");
		$v->isOk ($supfax, "string", 0,20, "Invalid supplier fax no.");
		$v->isOk ($addr, "string", 0,500, "Invalid supplier address.");
		$v->isOk ($odate, "date", 1,14, "Invalid Order Date.");
		$v->isOk ($deldate, "date", 1,14, "Invalid Delivery Date.");
		$v->isOk ($csamt, "float", 1, 20, "Invalid cost amount.");
		$v->isOk ($buom, "num", 0, 20, "Invalid buying units.");
        $v->isOk ($suom, "num", 0, 20, "Invalid selling units.");
		$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account.");

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

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid'";
        $stkRslt = db_exec($sql) or errDie("Unable to access stock database.", SELF);
		if(pg_numrows($stkRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $stk = pg_fetch_array($stkRslt);
        }

		# Calculate total units bought
		$units = 0;
		if($buom > 0){
			$units += ($buom * $stk['rate']);
		}
		if($suom > 0){
			$units += $suom;
		}

		$refnum = getrefnum();
/*refnum*/

		// Get Bank account [the traditional way re: hook of hook]
        core_connect();
        $sql = "SELECT * FROM bankacc WHERE accid = '$bankacc'";
        $rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
        # check if link exists
        if(pg_numrows($rslt) <1){
                return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
        }
        $bank = pg_fetch_array($rslt);
        $bankaccid = $bank["accnum"];

		// Update stock
		db_connect();
		$sql = "UPDATE stock SET ordered = (ordered + '$units') WHERE stkid = '$stkid'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		$fdate = explode("-", $odate);
        $fdate = $fdate[2]."-".$fdate[1]."-".$fdate[0];
		$fdeldate = explode("-", $deldate);
        $fdeldate = $fdeldate[2]."-".$fdeldate[1]."-".$fdeldate[0];

		// insert into stock orders
		$Sql = "INSERT INTO orders(stkid, supplier, tel, fax, addr, orddate, deldate, buom, suom, csamt, bankacc, recved) VALUES('$stkid', '$supplier', '$suptel','$supfax', '$addr', '$fdate', '$fdeldate', '$buom', '$suom', '$csamt','$bankacc', 'no')";
		$Rslt = db_exec($Sql) or errDie("Unable to insert order into Cubit.",SELF);

		# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
		# writetrans($stk['accid'], $bankaccid, $date, $refnum, $csamt, "bought $units x $stk[stkdes]");

		$write ="
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Stock Order Recorded</th></tr>
			<tr class=datacell><td>Order for, <b>$stk[stkdes] ($stk[stkcod])</b> has been successfully added to Cubit.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stock-view.php'>View Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $write;
}
?>

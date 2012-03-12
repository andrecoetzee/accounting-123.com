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
if (isset($_GET["stkid"])) {
	$OUTPUT = details($_GET);
}elseif (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "details":
			$OUTPUT = details($_POST);
			break;

                case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
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
		$stockcat = "<select name='catid'>";
        $sql = "SELECT catid,cat,catcod FROM stockcat ORDER BY cat ASC";
        $catRslt = db_exec($sql);
        if(pg_numrows($catRslt) < 1){
                return "There is no stock found in Cubit.";
        }else{
                while($cat = pg_fetch_array($catRslt)){
                        $stockcat .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
                }
        }
        $stockcat .="</select>";

		//layout
		$slct = "
		<h3>Select Category</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=details>
		<tr><th>Select Category</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td valign=center>$stockcat</td></tr>
		<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'><input type=submit value='Add >'></td></tr>
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

function details($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($catid, "num", 1, 50, "Invalid category ID.");

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
		$sql = "SELECT catid,cat FROM stockcat WHERE catid = '$catid'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid Category ID.";
        }else{
                $cat= pg_fetch_array($catRslt);
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
		$view = "<h3>Buy Stock</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center><input type=text size=2 name=day maxlength=2 value='".date("d")."'>-<input type=text size=2 name=mon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=year maxlength=4 value='".date("Y")."'> DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account</td><td>$bank</td></tr>
		</table>
		<p>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Select</th><th>Stock Code</th><th>Description</th><th>Buying unit of measure</th><th>Selling unit of measure</th><th>Cost Amount</th></tr>";

			# Select Stock
			db_connect();
			$sql = "SELECT * FROM stock WHERE catid = '$catid'";
        	$stkRslt = db_exec($sql);
        	if(pg_numrows($stkRslt) < 1){
                return "There is no stock found in Cubit.";
        	}else{
                while($stk = pg_fetch_array($stkRslt)){
                        $view .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=checkbox name='sel[]' value='$stk[stkid]'></td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=text size=10 name='buom[".$stk['stkid']."]'> x $stk[buom]</td>
						<td><input type=text size=10 name='suom[".$stk['stkid']."]'> x $stk[suom]</td><td>".CUR." <input type=text size=9 name='csamt[".$stk['stkid']."]'></td></tr>";
                }
        	}

        $view .="
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
function confirm($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
		$v->isOk ($day, "num", 1,2, "Invalid order Date day.");
		$v->isOk ($mon, "num", 1,2, "Invalid order Date month.");
		$v->isOk ($year, "num", 1,4, "Invalid order Date Year.");
		$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account.");
		$date = $day."-".$mon."-".$year;

        if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
        }
		if(isset($sel)){
			foreach($sel as $key => $value){
				$v->isOk ($sel[$key], "num", 1, 50, "Invalid Stock ID.");
				$v->isOk ($csamt[$value], "float", 1, 20, "Invalid cost amount.");
				$v->isOk ($buom[$value], "num", 0, 20, "Invalid buying units.");
        		$v->isOk ($suom[$value], "num", 0, 20, "Invalid selling units.");
				$buom[$value] += 0;
				$suom[$value] += 0;
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
		$confirm = "<h3>Buy Stock</h3>
        <h4>Confirm Entry</h4>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name=date value='$date'>
		<input type=hidden name=bankacc value='$bankacc'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$date</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account</td><td>$bank[accname]</td></tr>
        </table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Stock Code</th><th>Description</th><th>Buying unit of measure</th><th>Selling unit of measure</th><th>Cost Amount</th></tr>";

		db_connect();
		if(isset($sel)){
			foreach($sel as $key => $value){
				# skip if nothing is being bought
				if($buom[$value] < 1 && $suom[$value] < 1){
					continue;
				}

				# get stock det
				$sql = "SELECT * FROM stock WHERE stkid = '$sel[$key]'";
        		$stkRslt = db_exec($sql);
        		$stk = pg_fetch_array($stkRslt);

				$confirm .= "<tr bgcolor='".TMPL_tblDataColor1."'><input type=hidden size=10 name='sel[]' value='$sel[$key]'><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden size=10 name='buom[]' value='$buom[$value]'>$buom[$value] x $stk[buom]</td>
				<td><input type=hidden size=10 name='suom[]' value='$suom[$value]'>$suom[$value] x $stk[suom]</td><td>".CUR." <input type=hidden size=9 name='csamt[]' value='$csamt[$value]'>$csamt[$value]</td></tr>";
			}
		}

		$confirm .= "
		<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        </table>
		<p>
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
function write($_POST)
{

		//processes
		db_connect();
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($date, "date", 1,14, "Invalid Date.");
		$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account.");

		if(isset($sel)){
			foreach($sel as $key => $value){
				$v->isOk ($sel[$key], "num", 1, 50, "Invalid Stock ID.");
				$v->isOk ($csamt[$key], "float", 1, 20, "Invalid cost amount.");
				$v->isOk ($buom[$key], "num", 0, 20, "Invalid buying units.");
        		$v->isOk ($suom[$key], "num", 0, 20, "Invalid selling units.");
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

		if(isset($sel)){
			foreach($sel as $key => $value){
				db_connect();
				# Select Stock
				$sql = "SELECT stkid,stkdes,rate,accid,stkcod FROM stock WHERE stkid = '$sel[$key]'";
				$stkRslt = db_exec($sql) or errDie("Unable to access stock database.", SELF);
				if(pg_numrows($stkRslt) < 1){
						return "<li> Invalid Stock ID.";
				}else{
						$stk = pg_fetch_array($stkRslt);
				}

				# Calculate total units bought
				$units[$key] = 0;
				if($buom[$key] > 0){
					$units[$key] += ($buom[$key] * $stk['rate']);
				}
				if($suom[$key] > 0){
					$units[$key] += $suom[$key];
				}
				# date format
		        $date = explode("-", $stkp['date']);
        		$sdate = $date[2]."-".$date[1]."-".$date[0];

				// Update stock
				$sql = "UPDATE stock SET units = (units + '$units[$key]'), csamt = (csamt + '$csamt[$key]') WHERE stkid = '$stk[stkid]'";
				$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

				// insert into stockk purchased
				$Sql = "INSERT INTO stock_purch(stkid, date, units, cost) VALUES('$stk[stkid]', '$sdate', '$units[$key]', '$csamt[$key]')";
				$Rslt = db_exec($Sql) or errDie("Unable to insert stock to Cubit.",SELF);

				# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
				writetrans($stk['accid'], $bankaccid, $date, $refnum, $csamt[$key], "bought $units[$key] x $stk[stkdes]");
			}
		}

		$write ="
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Bought Stock Recorded</th></tr>
			<tr class=datacell><td>Bought Stock, $stk[stkdes] ($stk[stkcod]) has been successfully added to Cubit.</td></tr>
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

# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
function writetrans($dtacc, $ctacc, $date, $refnum, $amount, $details)
{
        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($date, "date", 1, 14, "Invalid date.");
        $v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class=err>".$e["msg"];
		}
		$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

        # date format
        $date = explode("-", $date);
        $date = $date[2]."-".$date[1]."-".$date[0];

        # begin sql transaction
        # pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

                // Insert the records into the transect table
                db_conn(PRD_DB);
                $sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '$details')";
                $transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

                // Update the balances by adding appropriate values to the trial_bal Table
                core_connect();
                $ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc'";
                $dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc'";
                $ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
                $dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

        # commit sql transaction
        # pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
}
?>

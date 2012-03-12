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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "remove":
			$OUTPUT = remove($_POST);
			break;
		default:
			$OUTPUT = add();
	}
} elseif(isset($_GET["id"])) {
        # Display default output
        $OUTPUT = add($_GET["id"]);
}

# get templete
require("../template.php");



# Insert details
function add($id)
{

	db_conn('cubit');
	$sql = "SELECT * FROM batch_cashbook WHERE cashid='$id'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

	$cd=pg_fetch_array($accntRslt);

	extract($cd);
	# Accounts Drop down selections
        core_connect();

	# Income accounts ($inc)
        $glacc = "<select name='accinv'>";
        $sql = "SELECT * FROM accounts WHERE accid='$accinv'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
     $acc = pg_fetch_array($accRslt);
			# Check Disable
			$glacc=$acc['accname'];


	db_connect();
        $sql = "SELECT * FROM bankacct WHERE bankid='$bankid'";
        $banks = db_exec($sql);
        if(pg_numrows($banks) < 1){
                return "<li class=err> There are no accounts held at the selected Bank.
                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }

	$acc = pg_fetch_array($banks);
         $bank = "$acc[accname] - $acc[bankname] ($acc[acctype])";


	# layout
        $add = "
        			<h3>Delete BAtch cashbook entry</h3>
					<table ".TMPL_tblDflts." width='80%'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='remove'>
						<input type='hidden' name='id' value='$id'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Bank Account</td>
							<td valign='center'>$bank</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Paid to/Received From</td>
							<td valign='center'>$name</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Description</td>
							<td>$descript</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cheque Number</td>
							<td valign='center'>$cheqnum</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." $amount</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='top'>Contra Account</td>
							<td>$glacc</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td></td>
							<td valign='center' align='right'><input type='submit' value='Delete &raquo;'></td>
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
								</table>
							</td>
						</tr>
			        </table>";
		return $OUTPUT;

}



# write
function remove($_POST)
{

	# processes
	db_connect();

	# Get vars
	extract ($_POST);

	$id+=0;

	if(isset($back)) {
		return add($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();



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

	db_conn('cubit');
	$sql = "DELETE FROM batch_cashbook WHERE cashid='$id'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

	# Status report
		$write = "
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<th>Batch Cashbook entry deleted</th>
						</tr>
						<tr class='datacell'>
							<td>Batch Cashbook entry has been deleted</td>
						</tr>
					</table>";


	# Main table (layout with menu)
	$OUTPUT = "
				<center>
				<table width = 90%>
					<tr valign='top'>
						<td width='50%'>$write</td>
						<td align='center'>
						<table ".TMPL_tblDflts." width='80%'>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='bank-pay-add.php'>Add Bank Payment</a></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='bank-recpt-add.php'>Add Bank Receipt</a></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='cashbook-view.php'>View Cash Book</a></td>
							</tr>
							<script>document.write(getQuicklinkSpecial());</script>
						</table>
						</td>
					</tr>
				</table>";
	return $OUTPUT;

}


?>
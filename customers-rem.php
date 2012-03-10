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

##
# customers-rem.php :: remove existing customers
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "rem":
			$OUTPUT = write($HTTP_POST_VARS);
			break;

		default:
			if(isset($HTTP_GET_VARS['cusnum'])){
					$OUTPUT = confirm($HTTP_GET_VARS['cusnum']);
			}else{
					$OUTPUT = "<li class=err> Invalid use of module.";
			}
	}
} else {
	if(isset($HTTP_GET_VARS['cusnum'])){
			$OUTPUT = confirm($HTTP_GET_VARS['cusnum']);
	}else{
			$OUTPUT = "<li class=err> Invalid use of module.";
	}
}

# get template
require("template.php");

# Default view
function confirm($cusnum)
{
        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1,255, "Invalid customer number.");

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

        # Query server for customer info
        db_connect();
        $sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND balance=0";
	$CustRslt = db_exec ($sql) or errDie ("Unable to view customers");
	$numrows = pg_numrows ($CustRslt);
	if ($numrows < 1) {
		return "<li class=err>Invalid Customer Number.";
	}
        $cust = pg_fetch_array($CustRslt);
        foreach($cust as $key => $value){
                $$key = $value;
        }

        //layout
        $confirm = "
        <h3>Remove Customers</h3>
        <h4>Confirm Entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=rem>
        <input type=hidden name=cusnum value='$cusnum'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Name</td><td valign=center>$cusname</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td rowspan=3 valign=top>Customer Address</td><td valign=center>$addr1</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td>$addr2</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td>$addr3</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td rowspan=3 valign=top>Customer Postal Address</td><td valign=center>$paddr1</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td>$paddr2</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td>$paddr3</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No.</td><td valign=center>$tel</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td valign=center>$fax</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>E-mail Address</td><td valign=center>$email</td></tr>
        <tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Remove >'></td></tr>
        </form>
        </table>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        <tr><th>Quick Links</th></tr>
        <tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customer</a></td></tr>
        <tr bgcolor='#88BBFF'><td><a href='customers-view.php'>View Customers</a></td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </tr>
        </table>";

        return $confirm;
}

# remove customer
function write($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1,255, "Invalid customer number.");

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

        # remove customer
        db_connect();
        $sql = "DELETE FROM customers WHERE cusnum = '$cusnum'";
        $rslt = db_exec($sql) or errDie("Unable to remove customer.",SELF);

        # status report
	$rem ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Customer successfully deleted</th></tr>
        <tr class=datacell><td>Customer number, $cusnum, successfully deleted.</td></tr>
        </table><br><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=20%>
        <tr><th>Quick Links </th></tr>
        <tr class=datacell><td align=center><a href='customers-view.php'>View Other customers</td></tr>
	<tr class=datacell><td align=center><a href='main.php'>Main Menu</td></tr>
        </table>";

        return $rem;
}
?>

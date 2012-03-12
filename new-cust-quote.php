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
# customers-new.php :: Add new customer
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
			$OUTPUT = view();
	}
}elseif(isset($_GET["err"])){
        # get vars from _GET
        foreach($_GET as $key => $value){
                $$key = $value;
        }
        $OUTPUT = view ($cusname, $addr1, $addr2, $addr3, $paddr1, $paddr2, $paddr3, $tel, $fax, $email, $err);
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("template.php");

# Default view
function view($cusname="", $addr1="", $addr2="", $addr3="",  $paddr1="", $paddr2="", $paddr3="", $tel="", $fax="", $email="", $err="")
{
        //layout
        $view = "<h3>Add New Customer</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        $err
                <tr><th>Field</th><th>Value</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td>Customer Name</td><td valign=center><input type=text size=20 name=cusname value='$cusname'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td rowspan=3 valign=top>Customer Delivery Address</td><td valign=center><input type=text size=20 name=addr1 value='$addr1'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr2 value='$addr2'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr3 value='$addr3'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td rowspan=3 valign=top>Customer Postal Address</td><td valign=center><input type=text size=20 name=paddr1 value='$paddr1'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td><input type=text size=20 name=paddr2 value='$paddr2'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td><input type=text size=20 name=paddr3 value='$paddr3'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No.</td><td valign=center><input type=text size=10 name=tel value='$tel'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td valign=center><input type=text size=10 name=fax value='$fax'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>E-mail Address</td><td valign=center><input type=text size=20 name=email value='$email'></td></tr>
                <tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add >'></td></tr>
        </table>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
                <tr><th>Quick Links</th></tr>
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
        $v->isOk ($cusname, "string", 1, 50, "Invalid Customer name.");
	$v->isOk ($addr1, "string", 0, 255, "Invalid customer delivery address(Line 1).");
        $v->isOk ($addr2, "string", 0, 255, "Invalid customer delivery address(Line 2).");
        $v->isOk ($addr3, "string", 0, 255, "Invalid customer delivery address(Line 3).");
        $v->isOk ($paddr1, "string", 0, 255, "Invalid customer postal address(Line 1).");
        $v->isOk ($paddr2, "string", 0, 255, "Invalid customer postal address(Line 2).");
        $v->isOk ($paddr3, "string", 0, 255, "Invalid customer postal address(Line 3).");
        $v->isOk ($tel, "string", 0,14, "Invalid telephone number.");
        $v->isOk ($fax, "string", 0,14, "Invalid fax number.");
        $v->isOk ($email, "email", 0,255, "Invalid E-mail address.");

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "-".$e["msg"]."<br>";
		}
                $Errors = "<tr><td class=err colspan=2>$confirm</td></tr>
                <tr><td colspan=2><br></td></tr>";
                header("Location: ".SELF."?cusname=$cusname&addr1=$addr1&addr2=$addr3&addr3=$addr3&paddr1=$paddr1&paddr2=$paddr2&paddr3=$paddr3&tel=$tel&fax=$fax&email=$email&err=$Errors");
                exit;
	}


         // layout
        $confirm =
        "<h3>New Customer</h3>
        <h4>Confirm entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=45%>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=cusname value='$cusname'>
        <input type=hidden name=addr1 value='$addr1'>
        <input type=hidden name=addr2 value='$addr2'>
        <input type=hidden name=addr3 value='$addr3'>
        <input type=hidden name=paddr1 value='$paddr1'>
        <input type=hidden name=paddr2 value='$paddr2'>
        <input type=hidden name=paddr3 value='$paddr3'>
        <input type=hidden name=tel value='$tel'>
        <input type=hidden name=fax value='$fax'>
        <input type=hidden name=email value='$email'>
                <tr><th width=40%>Field</th><th width=60%>Value</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td width=70%>Customer Name</td><td valign=center>$cusname</td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Customer Delivery Address</td><td valign=center>$addr1<br>$addr2<br>$addr3</td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Customer Postal Address</td><td valign=center>$paddr1<br>$paddr2<br>$paddr3</td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No.</td><td valign=center>$tel</td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td valign=center>$fax</td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>E-mail Address</td><td valign=center>$email</td></tr>
                <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Add Client &raquo'></td></tr>
        </form>
        </table>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
                <tr><th>Quick Links</th></tr>
                <script>document.write(getQuicklinkSpecial());</script>
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
        $v->isOk ($cusname, "string", 1, 50, "Invalid Customer name.");
		$v->isOk ($addr1, "string", 0, 255, "Invalid customer delivery address(Line 1).");
        $v->isOk ($addr2, "string", 0, 255, "Invalid customer delivery address(Line 2).");
        $v->isOk ($addr3, "string", 0, 255, "Invalid customer delivery address(Line 3).");
        $v->isOk ($paddr1, "string", 0, 255, "Invalid customer postal address(Line 1).");
        $v->isOk ($paddr2, "string", 0, 255, "Invalid customer postal address(Line 2).");
        $v->isOk ($paddr3, "string", 0, 255, "Invalid customer postal address(Line 3).");
        $v->isOk ($tel, "string", 0,14, "Invalid telephone number.");
        $v->isOk ($fax, "string", 0,14, "Invalid fax number.");
        $v->isOk ($email, "email", 0,255, "Invalid E-mail address.");

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

        # write customer to DB
        // check the customer name
        db_connect();
        $sql = "SELECT * FROM customers WHERE cusname = '$cusname'";
        $rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

        if(pg_numrows($rslt) > 0){
                return "<li class=err> A Customer with customer name $cusname already exist";
        }

        // insert the customer
        $sql = "INSERT INTO customers(cusname, addr1, addr2, addr3, paddr1, paddr2, paddr3, tel, fax, email) ";
        $sql .= "VALUES('$cusname', '$addr1', '$addr2', '$addr3',  '$paddr1', '$paddr2', '$paddr3', '$tel', '$fax', '$email')";
        $rslt = db_exec($sql) or errDie("Unable to insert customer to Cubit.",SELF);

		$cusnum = pglib_lastid("customers", "cusnum");

        $write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>New customer added to database</th></tr>
        <tr class=datacell><td>New customer, $cusname has been successfully added to Cubit.</td></tr>
		<tr><td><br></td></tr>
		<tr class=datacell><td><form action='quote-new.php' method=get><input type=hidden name=cusnum value='$cusnum'><input type=submit value='Continue to Quote'></form></td></tr>
		</table>
        <p>
        <table border=0 cellpadding='2' cellspacing='1'>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='customers-view.php'>View Customers</a></td></tr>
                <tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customers</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $write;
}
?>

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
# credit-invoice-details.php :: Module to view & print invoices
##

require ("settings.php");

# print invoice info
$OUTPUT = printInv ($_GET['ordnum']);

require ("template.php");


# Print the invoice info
function printInv($ordnum){

        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($ordnum, "num", 1, 50, "Invalid order number.");

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

        # Get invoice info
        db_connect();
        $sql = "SELECT * FROM credit_invoices WHERE ordnum ='$ordnum'";
        $invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}
        $inv = pg_fetch_array($invRslt);

        # Get all Vars
        foreach($inv as $key => $value){
                $$key = $value;
        }

        # format date
        $invdate = explode("-", $invdate);
        $invdate = $invdate[2]."-".$invdate[1]."-".$invdate[0];
        $orddate = explode("-", $orddate);
        $orddate = $orddate[2]."-".$orddate[1]."-".$orddate[0];

        $printInv ="<center><table border=0 cellpadding=5 cellspacing=0 width='91%'>
        <tr><td width='35%' align=center>
	        <img src='".COMP_LOGO."' width=230 height=47 alt='".COMP_NAME."'>
        </td><td align=right>
	        ".COMP_ADDRESS."
	        <br>Tel : ".COMP_TEL."
	        <br>Fax : ".COMP_FAX."
        </td><tr>
        <tr><td width='35%' valign=top>
	        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%' border=1>
	        <tr><th class=h4>CUSTOMER ADDRESS</th></tr>
	        <tr><td align=center>
        		<table border=0 cellpadding=10 cellspacing=0>
		        <tr><td>
			        <b>$cusname</b>
			        <p>$addr1<br>$addr2<br>$addr3
                                <p>$tel<br>$fax<br>$email<br>
		        </td></tr>
		        </table>
	        </td></tr>
	        </table>
        </td><td>
        	<!-- commeted out
                <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%' border=1>
        	<tr><th class=h4>DELIVERY ADDRESS</th></tr>
        	<tr><td align=center>
		        <table border=0 cellpadding=10 cellspacing=0>
		        <tr><td>
        			<b>-Customer name-</b>
		                <p>-Customer's Delivery Address-
		        </td></tr>
		</table>
	        </td></tr>
	        </table>
                /commente out -->

        </td></tr>
        <tr><td colspan=2>
        	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%' border=1>
        	<tr><th>INVOICE No.</th><th>SALESPERSON</th><th>ORDER DATE</th><th>INVOICE DATE</th><th>TERMS</th></tr>
        	<tr><td align=center>$ordnum</td><td align=center>$salesrep</td><td align=center>$orddate</td><td align=center>$invdate</td><td align=center>$terms days</td></tr>
        	</table>
        </td></tr>
        </table>
        <br>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='90%' border=1>
        <tr><th>DESCRIPTION</th><th>QTY</th><th>UNIT COST</th><th width=20%>SUBTOTAL</th></tr>";

        # Get Items
        $items  = explode("\n", $orddes);

        #  get item info
        foreach($items as $key => $item){
                $items[$key] = explode(" [|] ",$items[$key]);
        }

        # print "<pre>";var_dump($items);"</pre>"; exit;

        # Show each Item
        foreach($items as $key => $item){
                $printInv .= "<tr><td>".stripslashes($item[0])."</td><td>$item[1]</td><td>$item[2]</td><td align=right>".CUR." $item[3]</td></tr>";
        }

        # calculate SUBTOT (100%)
        $SUBTOT = sprintf("%01.2f",($grdtot/(100+TAX_VAT)*100));

        # calculate VAT
        $VAT = ($grdtot - $SUBTOT);

        $printInv .= "<tr><td colspan=3 align=right><b>SUBtotal</b></td><td align=right>$SUBTOT</td></tr>
        <tr><td colspan=3 align=right><b>VAT @ ".TAX_VAT."%</b></td><td align=right>$VAT</td></tr>
        <tr><td colspan=3 align=right><b>GRAND total</b></td><td align=right><b>$grdtot</b></td></tr>
        </table></center>
        <blockquote> <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
        <tr><th>VAT No.</th><td align=center>".COMP_VATNO."</td></tr>
        </table>";

        $OUTPUT = $printInv;
        #  Print the invoice and exit
        require ("tmpl-print.php");
}
?>

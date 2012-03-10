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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
                case "viewsaved":
			$OUTPUT = viewsaved($HTTP_POST_VARS);
			break;
                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("../template.php");


# Default view
function view()
{

		# dates drop downs
		$months = array("1"=>"January","2"=>"February", "3"=>"March", "4"=>"April", "5"=>"May", "6"=>"June", "7"=>"July", "8"=>"August", "9"=>"September", "10"=>"October", "11"=>"November", "12"=>"December");

		$fmonth = extlib_cpsel("fmonth", $months, date("m"));
		$lmonth = extlib_cpsel("lmonth", $months, date("m"));

	    // Layout
        $view = "
        <h3>View Saved Bank Reconciliations</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=viewsaved>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Bank Account</td>
        <td valign=center><select name=bankid>";

		db_connect();
        $sql = "SELECT * FROM bankacct";
        $banks = db_exec($sql);
        $numrows = pg_numrows($banks);

        if(empty($numrows)){
                return "<li class=err> There are no accounts held at the selected Bank.
                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }

        while($acc = pg_fetch_array($banks)){
                $view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname]</option>";
        }

        $view .= "</select></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>From :</td><td valign=center><input type=text name=fday size=2 maxlength=2 value='1'> - $fmonth - <input type=text name=fyear size=4 maxlength=4 value=".date("Y")."></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>To :</td><td valign=center><input type=text name=lday size=2 maxlength=2 value='".date("d")."'> - $lmonth - <input type=text name=lyear size=4 maxlength=4 value=".date("Y")."></td></tr>
		<tr><td><br></td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='View &raquo'></td></tr>
        </table>
        <p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	        <tr><th>Quick Links</th></tr>
            <script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $view;
}

# view cash book
function viewsaved($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}

		# validate input
		require_lib("validate");
		$v = new  validate ();
        $v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
        $v->isOk ($fday, "num", 1, 2, "Invalid Day for the 'From' date.");
        $v->isOk ($fmonth, "num", 1, 2, "Invalid month for the 'From' date..");
        $v->isOk ($fyear, "num", 1, 4, "Invalid year for the 'From' date..");
        $v->isOk ($lday, "num", 1, 2, "Invalid Day for the 'To' date.");
        $v->isOk ($lmonth, "num", 1, 2, "Invalid month for the 'To' date..");
        $v->isOk ($lyear, "num", 1, 4, "Invalid year for the 'To' date..");

        # lets mix the date
        $from = sprintf("%02.2d",$fday)."-".sprintf("%02.2d",$fmonth)."-".$fyear;
        $to = sprintf("%02.2d",$lday)."-".sprintf("%02.2d",$lmonth)."-".$lyear;

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

        # get bank details
        $bankRslt = get("cubit", "accname,bankname", "bankacct", "bankid", $bankid);
        $bank = pg_fetch_array($bankRslt);
}
?>

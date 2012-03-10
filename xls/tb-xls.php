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

# decide what to do
if (isset($HTTP_GET_VARS["id"])) {
	$OUTPUT = bal($HTTP_GET_VARS["id"]);
} else {
	# Display error
	$OUTPUT = "<li> Error : Invalid Trial Balance Number.";
}

require ("../template.php");

function bal($id)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 20, "Invalid Trial Balance number.");

		# display errors, if any
		if ($v->isError ()) {
			$theseErrors = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$theseErrors .= "<li class=err>".$e["msg"];
			}
			$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $theseErrors;
		}

		# connect to core DB
        core_connect();

        # get the Trial Balance
        $sql = "SELECT * FROM save_trial_bal WHERE id = '$id' AND div = '".USER_DIV."'";
        $balRslt = db_exec($sql) or errDie("Unable to retrieve Trial Balance from the Database",SELF);
        if(pg_numrows($balRslt) < 1){
                return "<center><li> Invalid Trial Balance Number.";
        }

        $bal = pg_fetch_array($balRslt);
		$balance = base64_decode($bal['output']);

		$OUTPUT = $balance;
        
	include("temp.xls.php");
	Stream("TB", $OUTPUT);
}

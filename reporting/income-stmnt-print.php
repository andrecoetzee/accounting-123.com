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
	$OUTPUT = inc($HTTP_GET_VARS["id"]);
} else {
	# Display error
	$OUTPUT = "<li> Error: Invalid Statement Number.";
}

require ("../template.php");

function inc($id)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 20, "Invalid Income Statement number.");

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

        # get the income statement
        $sql = "SELECT * FROM save_income_stmnt WHERE id = '$id' AND div = '".USER_DIV."'";
        $incRslt = db_exec($sql) or errDie("Unable to retrieve income statement from the Database",SELF);
        if(pg_numrows($incRslt) < 1){
                return "<center><li> Invalid Income Statement Number.";
        }

        $inc = pg_fetch_array($incRslt);
		$income = base64_decode($inc['output']);

		$OUTPUT = $income;
        require("../tmpl-print.php");
}

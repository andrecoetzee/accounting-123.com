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
if (isset($_GET["id"])) {
	$OUTPUT = bal($_GET["id"]);
} else {
	# Display error
	$OUTPUT = "<li> Error: Invalid Balance Sheet Number.";
}

require ("../template.php");

function bal($id) {
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($id, "num", 1, 20, "Invalid Cash Flow Statement number.");

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
        $cf = new dbSelect("save_cashflow", "core", grp(
        	m("cols", "output, date_trunc('day', gentime) as gentime"),
			m("where", "id='$id'")
		));
		$cf->run();

        if($cf->num_rows() < 1){
                return "<center><li> Invalid Cash Flow Statement Number.</li></center>";
        }

        $stmnt = $cf->fetch_array();
		$OUTPUT = base64_decode($stmnt['output']);

		if (isset($_GET["xls"])) {
			$cftime = preg_replace("/ 00:00.*/", "", $stmnt["gentime"]);
			require_lib("xls");
			Stream("cashflow-$cftime", $OUTPUT);
		} else {
			require("../tmpl-print.php");
		}
}

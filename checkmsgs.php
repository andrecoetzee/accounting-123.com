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

require ("settings.php");

// remove all '
if ( isset($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_POST_VARS[$key] = str_replace("'", "", $value);
	}
}
if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = str_replace("'", "", $value);
	}
}

# decide what to do
$OUTPUT = check_messages();

require("template.php");

# enter new data

function check_messages() {
	$qry = new dbSelect("req", "cubit", grp(
		m("cols", "1"),
		m("where", "recipient='".USER_NAME."' AND alerted IS NULL")
	));
	$qry->run();

	if ($qry->num_rows() == 0) {
		return "NO MESSAGES";
	} else {
		$cols = grp(
			m("alerted", "1")
		);

		$upd = new dbUpdate("req", "cubit", $cols, "recipient='".USER_NAME."'");
		$upd->run(DB_UPDATE);

		$qry->setOpt(grp(
			m("where", "recipient='".USER_NAME."' AND viewed='0'")
		));
		$qry->run();

		return "MSGS: ".$qry->num_rows();
	}
}

?>

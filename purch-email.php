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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "email":
			$OUTPUT = email($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

require ("template.php");

function email($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);
	
	require_lib("validate");
	$v = new validate;
	foreach ($email as $purid=>$supid) {
		$v->isOk($purid, "num", 1, 9, "Invalid purchase id.");
	}
	
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}
	
	$i = 0;
	$supp_out = "";
	
	foreach ($email_sel as $purid) {
		$OUTPUT = "
	
	// Layout
	$OUTPUT = "<h3>Email Supplier Orders</h3>
	<form method='post' action='".SELF."'>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th>Supplier</th>
			<th>Email Address</th>
		</tr>
		$supp_out
	</table>";
	
	return $OUTPUT;
}	
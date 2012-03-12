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

# If this script is called by itself, abort

require("../../settings.php");
require("../https_urlsettings.php");

if ( isset($_POST) && is_array($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_GET[$key] = $value;
	}
}

if ( isset($_GET["key"]) ) {
	switch ( $_GET["key"] ) {
		case "search":
			if ( isset($_GET["search"]) ) {
				$OUTPUT = search();
			}
			break;
		default:
			$OUTPUT = enter("");
	}
} else {
	$OUTPUT = enter("");
}

require("../../template.php");

function enter($err) {
	global $_GET;
	extract($_GET);

	$fields["idnum"] = "";

	foreach ( $fields as $key => $value ) {
		if ( ! isset($$key) ) $$key = $value;
	}

	$OUTPUT = "
	<h3>Search Black / White List Registry</h3>
	$err
	<form method=post action='".SELF."'>
	<input type=hidden name=key value=search>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th colspan=2>Information</th>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>ID / Passport Number / Registration Number</td>
		<td><input type=text name=idnum value='$idnum'></td>
	</tr>
	<tr>
		<td colspan=2 align=center><input type=submit name='search' value='Search'></td>
	</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function search() {
	global $_GET;
	extract($_GET);

	require_lib("validate");
	$v = & new Validate();

	$v->isOk($idnum, "string", 0, 100, "Invalid id / registration number.");

	if ( $v->isError() ) {
		$err = "";
		foreach ( $v->getErrors() as $key => $value ) {
			$err .= "<li class=err>$value[msg]</li>";
		}

		return enter($err);
	}

	// post the search request
	$search_request = @file(urler(BLACKLIST_SEARCH_URL."?idnum=$idnum&".sendhash()));

	if ( $search_request == false ) {
		$site_msg = "<li class=err>Connection to server failed. Check you internet connection and try again.</li>";
	} else {
		$site_msg = "";
		$status = 0; // 0 = none, 1 = read message
		foreach ( $search_request as $value ) {
			$value = str_replace("\n", "", $value);
			switch ($value) {
			case "<DR_E>":
				break;

			case "<DR_M>":
				$status = 1;
				break;
			case "</DR_M>":
				$status = 0;
				break;

			case "</DR_E>":
				$status = 0;
				break;

			default:
				if ( $status == 1 ) {
					$site_msg .= "$value\n";
				}
			}
		}
	}

	$OUTPUT = "
	<h3>Search Black / White List Registry</h3>";

	foreach($_GET as $key => $value) {
		if ( $key != "key" ) $OUTPUT .= "<input type=hidden name='$key' value='$value'>";
	}

	$OUTPUT .= "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th>Data Returned</th>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td valign=top>$site_msg</td>
	</tr>
	</table>";

	return $OUTPUT;
}

?>

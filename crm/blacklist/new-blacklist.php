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

if ( isset($HTTP_POST_VARS) && is_array($HTTP_POST_VARS) ) {
	foreach ( $HTTP_POST_VARS as $key => $value ) {
		$HTTP_GET_VARS[$key] = $value;
	}
}

if ( isset($HTTP_GET_VARS["key"]) ) {
	switch ( $HTTP_GET_VARS["key"] ) {
		case "submit":
			if ( isset($HTTP_GET_VARS["submit"]) ) {
				$OUTPUT = submit();
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
	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	$fields["idnum"] = "";
	$fields["name"] = "";
	$fields["surname"] = "";
	$fields["comment"] = "";
	$fields["personname"] = "";
	$fields["persontel"] = "";

	foreach ( $fields as $key => $value ) {
		if ( ! isset($$key) ) $$key = $value;
	}

	$OUTPUT = "
	<h3>Create Black / White List Registry Entry</h3>
	$err
	<form method=post action='".SELF."'>
	<input type=hidden name=key value=submit>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th colspan=2>Details</th>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>ID / Passport Number / Registration Number</td>
		<td><input type=text name=idnum value='$idnum'></td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td>Full Name</td>
		<td><input type=text name=name value='$name'></td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>Surname</td>
		<td><input type=text name=surname value='$surname'></td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td>Paying Habits</td>
		<td>
			<input type=radio name=paying value='bad'> Bad<br>
			<input type=radio name=paying value='average'> Average<br>
			<input type=radio name=paying value='good'> Good
		</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>Person Type</td>
		<td>
			<input type=radio name=person value='bad'> Bad<br>
			<input type=radio name=person value='average'> Average<br>
			<input type=radio name=person value='good'> Good
		</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td>Comments (100 characters)</td>
		<td><textarea name=comment>$comment</textarea></td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>Listing Person Name</td>
		<td><input type=text name=personname value='$personname'></td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td>Contact Number of Listing Person</td>
		<td><input type=text name=persontel value='$persontel'></td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>Will you let the person do Business with you again?</td>
		<td>
			<input type=radio name=dobusiness value=yes> Yes<br>
			<input type=radio name=dobusiness value=no> No
		</td>
	</tr>
	<tr>
		<td colspan=2 align=center><input type=submit name='submit' value='Submit'></td>
	</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function submit() {
	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	require_lib("validate");
	$v = & new Validate();

	$v->isOk($idnum, "string", 0, 100, "Invalid id / registration number.");
	$v->isOk($name, "string", 0, 100, "Invalid tenant full name.");
	$v->isOk($surname, "string", 0, 100, "Invalid tenant surname.");
	$v->isOk($comment, "string", 0, 100, "Invalid tenant comment.");
	$v->isOk($personname, "string", 0, 100, "Invalid listing person name.");
	$v->isOk($persontel, "string", 0, 100, "Invalid listing person telephone number.");
	if ( isset($paying) ) $v->isOk($paying, "string", 0, 100, "Invalid Paying quality selection.");
	if ( isset($person) ) $v->isOk($person, "string", 0, 100, "Invalid Person quality selection.");
	if ( isset($dobusiness) ) $v->isOk($dobusiness, "string", 0, 100, "Invalid 'Do Business' value.");
	if ( ! isset($paying) ) $v->addError("", "Invalid Paying quality selection.");
	if ( ! isset($person) ) $v->addError("", "Invalid Person quality selection.");
	if ( ! isset($dobusiness) ) $v->addError("", "Invalid 'Do Business' value.");

	if ( $v->isError() ) {
		$err = "";
		foreach ( $v->getErrors() as $key => $value ) {
			$err .= "<li class=err>$value[msg]</li>";
		}

		return enter($err);
	}

	$comment = str_replace("=", "|", base64_encode($comment));

	// post the search request
	$search_request = @file(urler(BLACKLIST_SUBMIT_URL."?idnum=$idnum&name=$name&surname=$surname&comment=$comment&personname=$personname&persontel=$persontel&paying=$paying&person=$person&dobusiness=$dobusiness&".sendhash()));

	if ( $search_request == false ) {
		$site_msg = "<li class=err>Connection to server failed. Check you internet connection and try again.</li>";
	} else {
		$site_msg = "";
		$status = 0; // 0 = none, 1 = read message
		foreach ( $search_request as $value ) {
			$value = str_replace("\n", "", $value);
			switch (trim($value)) {
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

		$site_msg = nl2br($site_msg);
	}

	$OUTPUT = "
	<h3>Create Black / White List Registry Entry</h3>";

	foreach($HTTP_GET_VARS as $key => $value) {
		if ( $key != "key" ) $OUTPUT .= "<input type=hidden name='$key' value='$value'>";
	}

	$OUTPUT .= "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th>Data Returned</th>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>$site_msg</td>
	</tr>
	</table>";

	return $OUTPUT;
}

?>

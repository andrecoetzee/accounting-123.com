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

require ("../settings.php");

if ($HTTP_POST_VARS) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enter();
        }
} else {
	$OUTPUT = enter();
}

$OUTPUT .= "<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
</table>";

require ("../template.php");

##
# functions
##

# Enter settings
function enter()
{
	# get the last ids
	db_connect();
	$rslt = db_exec("SELECT * FROM seq");

	$invnum = 0;
	$crednote = 0;
	$purchnum = 0;

	while ( $row = pg_fetch_array($rslt) ) {
		switch ( $row["type"] ) {
			case "inv":
				$invnum = $row["last_value"];
				break;
			case "pur":
				$purchnum = $row["last_value"];
				break;
			case "note":
				$crednote = $row["last_value"];
				break;
		}
	}

	$enter = "<h3>Cubit Settings</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
		<tr><th>Setting</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Last Invoice No.</td><td><input type=text size=5 name=linvid value='$invnum'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Last Invoice Credit Note No.</td><td><input type=text size=5 name=lnoteid value='$crednote'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Last Purchase No.</td><td><input type=text size=5 name=lpurnum value='$purchnum'></td></tr>
		<tr><td><br></td></tr>
		<tr><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</form>
	</table>";

	return $enter;
}

# confirm entered info
function confirm($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($linvid, "num", 1, 6, "Invalid Last Invoice number.");
	$v->isOk ($lnoteid, "num", 1, 6, "Invalid Last Credit Note Number.");
	$v->isOk ($lpurnum, "num", 1, 6, "Invalid Last Purchase Number.");

    # display errors, if any
	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class=err colspan=2>$theseErrors</td></tr>
		<tr><td colspan=2><br></td></tr>";
		return $Errors;
	}

	# check if it is note less
	db_connect();
	# get the last ids
	$rslt = db_exec("SELECT * FROM seq");

	$invnum = 0;
	$crednote = 0;
	$purchnum = 0;

	while ( $row = pg_fetch_array($rslt) ) {
		switch ( $row["type"] ) {
			case "inv":
				$invnum = $row["last_value"];
				break;
			case "pur":
				$purchnum = $row["last_value"];
				break;
			case "note":
				$crednote = $row["last_value"];
				break;
		}
	}

	if ($linvid < $invnum){
		return "<li class=err> Last invoice number cannot be less than current invoice number.";
	}
	if ($lnoteid < $crednote){
		return "<li class=err> Last Credit note number cannot be less than current credit note number.";
	}
	if ( $lpurnum < $purchnum ) {
		return "<li class=err> Last Purchase Number cannot be less than current purchase number.";
	}

	$confirm ="<h3>Cubit Settings</h3>
	<h4>Confirm</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=linvid value='$linvid'>
	<input type=hidden name=lnoteid value='$lnoteid'>
	<input type=hidden name=lpurnum value='$lpurnum'>
	<tr><th>Setting</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Last Invoice No.</td><td>$linvid</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Last Invoice Credit Note No.</td><td>$lnoteid</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Last Purchase No.</td><td>$lpurnum</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=right colspan=2><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input type=submit value='Confirm &raquo'></td></tr>
	</form>
	</table>";

        return $confirm;
}

# write user to db
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($linvid, "num", 1, 6, "Invalid Last Invoice nubmer.");
	$v->isOk ($lnoteid, "num", 1, 6, "Invalid Last Credit Note Number.");
	$v->isOk ($lpurnum, "num", 1, 6, "Invalid Purchase Number.");

    # display errors, if any
	if ($v->isError ()) {
        $theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "-".$e["msg"]."<br>";
		}

		$Errors = "<tr><td class=err colspan=2>$theseErrors</td></tr>
		<tr><td colspan=2><br></td></tr>";
		return entererr($accc, $Errors);
	}

	# connect to db
	db_connect ();

	# change settings
        $rslt = db_exec("DELETE FROM seq WHERE type='inv'");
	if ( pg_cmdtuples($rslt) <= 0 )
		return "<li class=err> Error updating sequences for invoice id </li><br>";
	$rslt = db_exec("INSERT INTO seq (type, last_value) VALUES('inv', '$linvid')");

        $rslt = db_exec("DELETE FROM seq WHERE type='note'");
	if ( pg_cmdtuples($rslt) <= 0 )
		return "<li class=err> Error updating sequences for credit note </li><br>";
	$rslt = db_exec("INSERT INTO seq (type, last_value) VALUES('note', '$lnoteid')");

        $rslt = db_exec("DELETE FROM seq WHERE type='pur'");
	if ( pg_cmdtuples($rslt) <= 0 )
		return "<li class=err> Error updating sequences for purchase id </li><br>";
	$rslt = db_exec("INSERT INTO seq (type, last_value) VALUES('pur', '$lpurnum')");


	# status report
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Cubit Settings</th></tr>
		<tr class=datacell><td>Setting have been successfully added to Cubit.</td></tr>
	</table>";

	return $write;
}
?>

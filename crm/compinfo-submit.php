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
# compinfo-view.php :: View & edit company info
##

# get settings
require ("settings.php");
require ("https_urlsettings.php");

if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmInfo ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeInfo ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = showInfo ();
	}
} else {
	$OUTPUT = showInfo ();
}

# display output
require ("template.php");

# print Info from db
function showInfo ()
{
	# connect to db
	db_connect ();
        $sql = "SELECT * FROM compinfo WHERE div = '".USER_DIV."'";
        $compRslt = db_exec($sql) or errDie("Unable to retrieve company details from Cubit.", SELF);
        $comp = pg_fetch_array($compRslt);

	db_conn("cubit");

	$sql = "SELECT * FROM cubitnet_sitesettings WHERE div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error reading settings.");

	if ( pg_num_rows($rslt) < 1 )
		$OUTPUT = "<li class=err>Cubit Internet Settings not set up yet.</li>";

	$siteset = pg_fetch_array($rslt);

        # start table, etc
	$showInfo =
        "<h3>View Company Details</h3>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form ENCTYPE='multipart/form-data' action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Company Name</td><td><input type=text size=20 name=compname value='$comp[compname]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Company Slogan</td><td><input type=text size=20 name=slogan value='$comp[slogan]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Registration Number</td><td><input type=text size=20 name=regnum value='$comp[regnum]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Number</td><td><input type=text size=20 name=vatnum value='$comp[vatnum]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>PAYE Ref</td><td><input type=text size=20 name=paye value='$comp[paye]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Change Logo Image</td><td>Yes<input type=radio name=changelogo value=yes> - No<input type=radio name=changelogo value=no checked=yes>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Logo Image</td><td><input type=file size=20 name=logo>&nbsp;&nbsp;&nbsp;width = 230 height=47</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td rowspan=4 valign=top>Address</td><td><input type=text size=20 name=addr1 value='$comp[addr1]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr2 value='$comp[addr2]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr3 value='$comp[addr3]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr4 value='$comp[addr4]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td rowspan=3 valign=top>Postal Address</td><td><input type=text size=20 name=paddr1 value='$comp[paddr1]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td><input type=text size=20 name=paddr2 value='$comp[paddr2]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td><input type=text size=20 name=paddr3 value='$comp[paddr3]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Postal Code</td><td><input type=text size=8 name=pcode value='$comp[pcode]'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No.</td><td><input type=text size=14 name=tel value='$comp[tel]'>(code) XXX-XXXX</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td><input type=text size=14 name=fax value='$comp[fax]'>(code) XXX-XXXX</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>E-mail Address</td><td><input type=text size=14 name=email value='$siteset[cn_email]'></td></tr>
        <tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
        </form>
        </table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

        return $showInfo;
}

# print Info from db
function showerr ($HTTP_POST_VARS, $err="")
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# start table, etc
	$showerr =
	"<h3>View Company Details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form ENCTYPE='multipart/form-data' action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<tr><td colspan=2>$err</td></tr>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Company Name</td><td><input type=text size=20 name=compname value='$compname'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Company Slogan</td><td><input type=text size=20 name=slogan value='$slogan'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Registration Number</td><td><input type=text size=20 name=regnum value='$regnum'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Number</td><td><input type=text size=20 name=vatnum value='$vatnum'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>PAYE Ref</td><td><input type=text size=20 name=paye value='$paye'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Change Logo Image</td><td>Yes<input type=radio name=changelogo value=yes> - No<input type=radio name=changelogo value=no checked=yes>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Logo Image</td><td><input type=file size=20 name=logo>&nbsp;&nbsp;&nbsp;width = 230 height=47</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td rowspan=4 valign=top>Address</td><td><input type=text size=20 name=addr1 value='$addr1'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr2 value='$addr2'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr3 value='$addr3'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td><input type=text size=20 name=addr4 value='$addr4'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td rowspan=3 valign=top>Postal Address</td><td><input type=text size=20 name=paddr1 value='$paddr1'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td><input type=text size=20 name=paddr2 value='$paddr2'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td><input type=text size=20 name=paddr3 value='$paddr3'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Postal Code</td><td><input type=text size=8 name=pcode value='$pcode'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No.</td><td><input type=text size=14 name=tel value='$tel'>(code) XXX-XXXX</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td><input type=text size=14 name=fax value='$fax'>(code) XXX-XXXX</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>E-mail Address</td><td><input type=text size=14 name=email value='$email'></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $showerr;
}

function confirmInfo ($HTTP_POST_VARS)
{
        # get $HTTP_POST_FILES global var for uploaded files
        global $HTTP_POST_FILES;

        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
        require_lib("validate");
	$v = new validate ();
	$v->isOk ($compname, "string", 1, 255, "Invalid company name.");
	$v->isOk ($slogan, "string", 0, 255, "Invalid slogan.");
	$v->isOk ($regnum, "regnum", 0, 255, "Invalid Comapny registration number.");
	$v->isOk ($vatnum, "num", 0, 255, "Invalid VAT number.");
	$v->isOk ($paye, "string", 0, 30, "Invalid paye number.");
	$v->isOk ($changelogo, "string", 1, 3, "Invalid change logo selection.");
	$v->isOk ($addr1, "string", 1, 255, "Invalid address (line 1).");
	$v->isOk ($addr2, "string", 1, 255, "Invalid address (line 2.");
	$v->isOk ($addr3, "string", 1, 255, "Invalid address (line 3.");
	$v->isOk ($addr4, "string", 0, 255, "Invalid address (line 4.");
	$v->isOk ($paddr1, "string", 0, 255, "Invalid postal address (line 1.");
	$v->isOk ($paddr2, "string", 0, 255, "Invalid postal address (line 2.");
	$v->isOk ($paddr3, "string", 0, 255, "Invalid postal address (line 3.");
	$v->isOk ($pcode, "num", 1, 20, "Invalid postal code.");
	$v->isOk ($tel, "string", 1, 20, "Invalid telephone number.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax number.");
	$v->isOk ($email, "email", 0, 255, "Invalid email address.");

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return showerr($HTTP_POST_VARS, $confirm);
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        # deal with logo image
        if ($changelogo == "yes") {
		if (empty ($HTTP_POST_FILES["logo"])) {
			return showerr($HTTP_POST_VARS, "<li class=err> Please select an image to upload from your hard drive.");
		}
		if (is_uploaded_file ($HTTP_POST_FILES["logo"]["tmp_name"])) {
			# Check file ext
			if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $HTTP_POST_FILES["logo"]["type"], $extension)) {
				$imgtype = $type = $HTTP_POST_FILES["logo"]["type"];

				// open file in "read, binary" mode
				$img = "";
				$file = fopen ($HTTP_POST_FILES['logo']['tmp_name'], "rb");
				while (!feof ($file)) {
					// fread is binary safe
					$img .= fread ($file, 1024);
				}
				fclose ($file);
				# base 64 encoding
				$img = base64_encode($img);

				db_connect();
				$query = "SELECT * FROM compinfo";
				$Rslt = db_exec($query);

				if(pg_numrows($Rslt) > 0){
					$sql = "UPDATE compinfo SET img = '$img', imgtype = '$type' WHERE div = '".USER_DIV."'";
				}else{
					$sql = "INSERT INTO compinfo (img, imgtype, div) VALUES('$img','$type', '".USER_DIV."')";
				}

				# write img to DB
				$rslt = db_exec($sql) or errDie("Unable to upload company logo Image to DB.",SELF);

				# to show IMG
				$logoimg = "<br><img src='compinfo/getimg.php' width=230 height=47><br><br>";
				$logo = "compinfo/getimg.php";
			}else {
				return showerr($HTTP_POST_VARS, "<li class=err>Please note that we only accept images of the types PNG,GIF and JPEG.");
			}
		} else {
			return showerr($HTTP_POST_VARS, "Unable to upload file, Please check file permissions.");
		}
	} else {
		$logo = "";
		if(strlen(COMP_LOGO) > 1){
				$logoimg = "<br><img src='".COMP_LOGO."' width=230 height=47><br><br>";
		}else{
				$logoimg = "<br>No Logo Image uploaded<br><br>";
		};
		$img = "";
		$imgtype = "";
	}

	db_conn("cubit");
	$sql = "SELECT setting_value FROM cubitnet_settings WHERE setting_name='cubitnet_hash'";
	$rslt = db_exec($sql) or errDie("Error reading hash.");

	if ( pg_num_rows($rslt) < 1 ) {
		$OUTPUT = "<li class=err>Cubit Internet Settings not set up yet.</li>";

		// lets do the template! bwahahahahhahahahahha->he_is_looking(hide==false?"die":"sleep");
		if ( is_file("template.php") ) require("template.php");
		if ( is_file("../template.php") ) require("../template.php");
		if ( is_file("../../template.php") ) require("../../template.php");
	} else {
		$hash = pg_fetch_result($rslt, 0, 0);
		$hash = substr( $hash, 0, 32 );
	}

		#Layout
		$confirmInfo =
		"<h3>Company Details</h3>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".COMPINFO_URL."' method=post>
		<input type=hidden name=compname value='$compname'>
		<input type=hidden name=slogan value='$slogan'>
		<input type=hidden name=fhash value='$hash'>
		<input type=hidden name=regnum value='$regnum'>
		<input type=hidden name=vatnum value='$vatnum'>
		<input type=hidden name=changelogo value='$changelogo'>
		<input type=hidden name=logo value='$img'>
		<input type=hidden name=logo_type value='$imgtype'>
		<input type=hidden name=addr1 value='$addr1'>
		<input type=hidden name=addr2 value='$addr2'>
		<input type=hidden name=addr3 value='$addr3'>
		<input type=hidden name=addr4 value='$addr4'>
		<input type=hidden name=paddr1 value='$paddr1'>
		<input type=hidden name=paddr2 value='$paddr2'>
		<input type=hidden name=paddr3 value='$paddr3'>
		<input type=hidden name=pcode value='$pcode'>
		<input type=hidden name=tel value='$tel'>
		<input type=hidden name=fax value='$fax'>
		<input type=hidden name=paye value='$paye'>
		<input type=hidden name=email value='$email'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Company Name</td><td>$compname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Company Slogan</td><td>$slogan</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Registration Number</td><td>$regnum</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Number</td><td>$vatnum</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>PAYE Ref</td><td>$paye</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Logo Image</td><td bgcolor='#ffffff' align=center>$logoimg</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td rowspan=4 valign=top>Address</td><td>$addr1</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td>$addr2</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td>$addr3</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><!-- rowspan --><td>$addr4</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td rowspan=3 valign=top>Postal Address</td><td>$paddr1</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td>$paddr2</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><!-- rowspan --><td>$paddr3</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Postal Code</td><td>$pcode</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Telephone No.</td><td>$tel</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td>$fax</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>E-mail Address</td><td>$email</td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		</form>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

        return $confirmInfo;
}

# write paye bracket changes to db
function writeInfo ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input & format confirm
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($compname, "string", 1, 255, "Invalid company name.");
	$v->isOk ($slogan, "string", 0, 255, "Invalid slogan.");
	$v->isOk ($regnum, "regnum", 0, 255, "Invalid Comapny registration number.");
	$v->isOk ($vatnum, "num", 0, 255, "Invalid VAT number.");
	$v->isOk ($addr1, "string", 1, 255, "Invalid address (line 1).");
	$v->isOk ($addr2, "string", 1, 255, "Invalid address (line 2.");
	$v->isOk ($addr3, "string", 1, 255, "Invalid address (line 3.");
	$v->isOk ($addr4, "string", 0, 255, "Invalid address (line 4.");
	$v->isOk ($paddr1, "string", 0, 255, "Invalid postal address (line 1.");
	$v->isOk ($paddr2, "string", 0, 255, "Invalid postal address (line 2.");
	$v->isOk ($paddr3, "string", 0, 255, "Invalid postal address (line 3.");
	$v->isOk ($pcode, "num", 1, 20, "Invalid postal code.");
	$v->isOk ($tel, "string", 1, 20, "Invalid telephone number.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
        $query = "SELECT * FROM compinfo WHERE div = '".USER_DIV."'";
        $Rslt = db_exec($query);

        if(pg_numrows($Rslt) > 0){
                if ($changelogo == "yes") {
                        $sql = "UPDATE compinfo SET compname='$compname', regnum='$regnum', vatnum='$vatnum', slogan='$slogan', logoimg='$logo', addr1='$addr1', addr2='$addr2',paye='$paye',
                        addr3='$addr3', addr4='$addr4', paddr1='$paddr1', paddr2='$paddr2', paddr3='$paddr3', pcode='$pcode', tel='$tel', fax='$fax' WHERE div = '".USER_DIV."'";
                }else{
                        $sql = "UPDATE compinfo SET compname='$compname', regnum='$regnum', vatnum='$vatnum', slogan='$slogan', addr1='$addr1', addr2='$addr2',paye='$paye',
                        addr3='$addr3', addr4='$addr4', paddr1='$paddr1', paddr2='$paddr2', paddr3='$paddr3', pcode='$pcode', tel='$tel', fax='$fax' WHERE div = '".USER_DIV."'";
                }
        }else{
                $sql = "INSERT INTO compinfo (compname, regnum, vatnum, slogan, logoimg, addr1, addr2, addr3, addr4, paddr1, paddr2, paddr3, pcode, tel, fax, div,paye)
                VALUES('$compname', '$regnum', '$vatnum', '$slogan', '$logo', '$addr1', '$addr2', '$addr3', '$addr4', '$paddr1', '$paddr2', '$paddr3', '$pcode', '$tel', '$fax', '".USER_DIV."','$paye')";
        }

        # Write info to Database
        db_connect();
        $rslt = db_exec($sql) or errDie("Unable to add company details to Cubit.", SELF);

        $writeInfo = "
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Company Details</th></tr>
        <tr class=datacell><td>The Company Details have been successfully added to Cubit.</td></tr>
        </table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

        return $writeInfo;
}
?>

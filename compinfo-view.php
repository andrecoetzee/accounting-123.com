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
require ("settings.php");

if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirmInfo ($_POST);
			break;
		case "write":
			$OUTPUT = writeInfo ($_POST);
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

	# start table, etc
	$showInfo = "
		<h3>View Company Details</h3>
		<table ".TMPL_tblDflts.">
		<form ENCTYPE='multipart/form-data' action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Company Name</td>
				<td><input type='text' size='20' name='compname' value='$comp[compname]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company Slogan</td>
				<td><input type='text' size='20' name='slogan' value='$comp[slogan]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Registration Number</td>
				<td><input type='text' size='20' name='regnum' value='$comp[regnum]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Number</td>
				<td><input type='text' size='20' name='vatnum' value='$comp[vatnum]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>PAYE Ref</td>
				<td><input type='text' size='20' name='paye' value='$comp[paye]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>SDL No</td>
				<td><input type='text' size='20' name='sdl' value='$comp[sdl]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>UIF No</td>
				<td><input type='text' size='20' name='uif' value='$comp[uif]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Diplomatic Indemnity</td>
				<td>
					Yes<input type='radio' name='diplomatic_indemnity' value='Y'><b> - </b>
					No<input type='radio' name='diplomatic_indemnity' value='N' checked>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Logo Image</td>
				<td><input type='file' size='20' name='logo'>&nbsp;&nbsp;&nbsp;width=230 height=47</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Delete Logo</td>
				<td><input type='checkbox' name='dellogo' value='yes'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>POS Logo Image</td>
				<td><input type='file' size='20' name='logo2'>&nbsp;&nbsp;&nbsp;width=230 height=47</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='4' valign='top'>".REQ."Address</td>
				<td><input type='text' size='20' name='addr1' value='$comp[addr1]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='addr2' value='$comp[addr2]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='addr3' value='$comp[addr3]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='addr4' value='$comp[addr4]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='3' valign='top'>Postal Address</td>
				<td><input type='text' size='20' name='paddr1' value='$comp[paddr1]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='paddr2' value='$comp[paddr2]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='paddr3' value='$comp[paddr3]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td><input type='text' size='8' name='pcode' value='$comp[postcode]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Telephone No.</td>
				<td><input type='text' size='14' name='tel' value='$comp[tel]'>(code) XXX-XXXX</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Fax No.</td>
				<td><input type='text' size='14' name='fax' value='$comp[fax]'>(code) XXX-XXXX</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks();
	return $showInfo;

}




# print Info from db
function showerr ($_POST, $err="")
{

	# Get vars
	extract ($_POST);

	if(isset($dellogo) AND (strlen($dellopo) > 1)){
		$dellogosel = "checked='yes'";
	}else {
		$dellogosel = "";
	}


	# start table, etc
	$showerr = "
		<h3>View Company Details</h3>
		<table ".TMPL_tblDflts.">
		<form ENCTYPE='multipart/form-data' action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<td colspan='2'>$err</td></tr>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company Name</td>
				<td><input type='text' size='20' name='compname' value='$compname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company Slogan</td>
				<td><input type='text' size='20' name='slogan' value='$slogan'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Registration Number</td>
				<td><input type='text' size='20' name='regnum' value='$regnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Number</td>
				<td><input type='text' size='20' name='vatnum' value='$vatnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>PAYE Ref</td>
				<td><input type='text' size='20' name='paye' value='$paye'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>SDL No</td>
				<td><input type='text' size='20' name='sdl' value='$sdl'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>UIF No</td>
				<td><input type='text' size='20' name='uif' value='$uif'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Diplomatic Indemnity</td>
				<td>Yes <input type='radio' name='diplomatic_indemnity' value='Y'> - No<input type='radio' name='diplomatic_indemnity' value='N' checked></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Logo Image</td>
				<td><input type='file' size='20' name='logo'>&nbsp;&nbsp;&nbsp;width=230 height=47</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Delete Logo</td>
				<td><input type='checkbox' name='dellogo' $dellogosel value='yes'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>POS Logo Image</td>
				<td><input type='file' size='20' name='logo2'>&nbsp;&nbsp;&nbsp;width=230 height=47</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='4' valign='top'>".REQ."Address</td>
				<td><input type='text' size='20' name='addr1' value='$addr1'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='addr2' value='$addr2'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='addr3' value='$addr3'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='addr4' value='$addr4'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='3' valign='top'>Postal Address</td>
				<td><input type='text' size='20' name='paddr1' value='$paddr1'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='paddr2' value='$paddr2'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td><input type='text' size='20' name='paddr3' value='$paddr3'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td><input type='text' size='8' name='pcode' value='$pcode'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Telephone No.</td>
				<td><input type='text' size='14' name='tel' value='$tel'>(code) XXX-XXXX</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Fax No.</td>
				<td><input type='text' size='14' name='fax' value='$fax'>(code) XXX-XXXX</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks();
	return $showerr;

}


function confirmInfo ($_POST)
{

	# get $_FILES global var for uploaded files
	global $_FILES;

        # get vars
	extract ($_POST);

    require_lib("validate");
   
	$v = new validate ();
	$v->isOk ($compname, "string", 1, 255, "Invalid company name.");
	$v->isOk ($slogan, "string", 0, 255, "Invalid slogan.");
	$v->isOk ($regnum, "regnum", 0, 255, "Invalid Comapny registration number.");
	$v->isOk ($vatnum, "num", 0, 255, "Invalid VAT number.");
	$v->isOk ($paye, "string", 0, 30, "Invalid paye number.");
	$v->isOk ($sdl, "string", 0, 30, "Invalid sdl number.");
	$v->isOk ($uif, "string", 0, 30, "Invalid uif number.");
	$v->isOk ($addr1, "string", 1, 255, "Invalid address (line 1).");
	$v->isOk ($addr2, "string", 1, 255, "Invalid address (line 2.");
	$v->isOk ($addr3, "string", 1, 255, "Invalid address (line 3.");
	$v->isOk ($addr4, "string", 0, 255, "Invalid address (line 4.");
	$v->isOk ($paddr1, "string", 0, 255, "Invalid postal address (line 1.");
	$v->isOk ($paddr2, "string", 0, 255, "Invalid postal address (line 2.");
	$v->isOk ($paddr3, "string", 0, 255, "Invalid postal address (line 3.");
	$v->isOk ($pcode, "string", 0, 20, "Invalid postal code.");
	$v->isOk ($tel, "string", 1, 20, "Invalid telephone number.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax number.");
	$v->isOk ($diplomatic_indemnity, "string", 1, 3, "Invalid diplomatic indemnity selection.");

	if(isset($dellogo) AND (strlen($dellogo) > 0))
		$v->isOk ($dellogo, "string", 0, 20, "Invalid remove logo option.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return showerr($_POST, $confirm);
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	#set some default vars ...
	$logo = "";
	$logo2 = "";
	$logoimg = "<br>No Logo Image uploaded<br><br>";
	$logoimg2 = "<br>No POS Logo Image uploaded<br><br>";


	if (is_uploaded_file ($_FILES["logo"]["tmp_name"])) {

		# Check file ext
		if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $_FILES["logo"]["type"], $extension)) {
			$type = $_FILES["logo"]["type"];

			// open file in "read, binary" mode
			$img = "";
			$file = fopen ($_FILES['logo']['tmp_name'], "rb");
			while (!feof ($file)) {
				// fread is binary safe
				$img .= fread ($file, 1024);
			}
			fclose ($file);
			# base 64 encoding
			$img = base64_encode($img);

			db_connect();

			#match db ?
			$query = "SELECT * FROM compinfo WHERE div = '".USER_DIV."'";
			$Rslt = db_exec($query);
			if(pg_numrows($Rslt) < 1){
				#no entry ... just add it+info and end here
				$sql = "INSERT INTO compinfo (img, imgtype, div) VALUES('$img','$type', '".USER_DIV."')";
				$run_sql = db_exec($sql);
			}else {
				#compare 2 images ...
				$carr = pg_fetch_array($Rslt);
				if($carr['img'] == $img){
					#images match ... dont update/add now
				}else {
					#images differ ... update entry in db with new images
					$update_sql = "UPDATE compinfo SET img = '$img', imgtype = '$type' WHERE div = '".USER_DIV."'";
					$run_update = db_exec($update_sql);
				}
			}

			# to show IMG
			$logoimg = "<br><img src='compinfo/getimg.php' width=230 height=47><br><br>";
			$logo = "compinfo/getimg.php";
		}else {
			return showerr($_POST, "<li class='err'>Please note that we only accept images of the types PNG,GIF and JPEG.</li>");
		}
	}



	if (is_uploaded_file ($_FILES["logo2"]["tmp_name"])) {
		# Check file ext
		if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $_FILES["logo2"]["type"], $extension)) {
			$type = $_FILES["logo2"]["type"];

			// open file in "read, binary" mode
			$img = "";
			$file = fopen ($_FILES['logo2']['tmp_name'], "rb");
			while (!feof ($file)) {
				// fread is binary safe
				$img .= fread ($file, 1024);
			}
			fclose ($file);
			# base 64 encoding
			$img = base64_encode($img);

			db_connect();

			#match db ?
			$query = "SELECT * FROM compinfo WHERE div = '".USER_DIV."'";
			$Rslt = db_exec($query);
			if(pg_numrows($Rslt) < 1){
				#no entry ... just add it+info and end here
				$sql = "INSERT INTO compinfo (img2, imgtype2, div) VALUES('$img','$type', '".USER_DIV."')";
				$run_sql = db_exec($sql);
			}else {
				#compare 2 images ...
				$carr = pg_fetch_array($Rslt);
				if($carr['img2'] == $img){
					#images match ... dont update/add now
				}else {
					#images differ ... update entry in db with new images
					$update_sql = "UPDATE compinfo SET img2 = '$img', imgtype2 = '$type' WHERE div = '".USER_DIV."'";
					$run_update = db_exec($update_sql);
				}
			}

			# to show IMG
			$logoimg2 = "<br><img src='compinfo/getimg2.php' width='230' height='47'><br><br>";
			$logo2 = "compinfo/getimg2.php";
		}else {
			return showerr($_POST, "<li class='err'>Please note that we only accept images of the types PNG,GIF and JPEG.</li>");
		}
	}


	if(isset($dellogo) AND (strlen($dellogo) > 0)){
		#remove logo
		db_connect ();
		$remlogo = "UPDATE compinfo SET imgtype = '', img = '' WHERE div = '".USER_DIV."'";
		$run_rem = db_exec($remlogo) or errDie("Unable to remove company logo");
	}else {
		#do nothing
	}

	#Layout
	$confirmInfo = "
		<h3>Company Details</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='compname' value='$compname'>
			<input type='hidden' name='slogan' value='$slogan'>
			<input type='hidden' name='regnum' value='$regnum'>
			<input type='hidden' name='vatnum' value='$vatnum'>
			<input type='hidden' name='logo' value='$logo'>
			<input type='hidden' name='logo2' value='$logo2'>
			<input type='hidden' name='addr1' value='$addr1'>
			<input type='hidden' name='addr2' value='$addr2'>
			<input type='hidden' name='addr3' value='$addr3'>
			<input type='hidden' name='addr4' value='$addr4'>
			<input type='hidden' name='paddr1' value='$paddr1'>
			<input type='hidden' name='paddr2' value='$paddr2'>
			<input type='hidden' name='paddr3' value='$paddr3'>
			<input type='hidden' name='pcode' value='$pcode'>
			<input type='hidden' name='tel' value='$tel'>
			<input type='hidden' name='fax' value='$fax'>
			<input type='hidden' name='paye' value='$paye'>
			<input type='hidden' name='sdl' value='$sdl'>
			<input type='hidden' name='uif' value='$uif'>
			<input type='hidden' name='diplomatic_indemnity' value='$diplomatic_indemnity'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company Name</td>
				<td>$compname</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Company Slogan</td>
				<td>$slogan</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Registration Number</td>
				<td>$regnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Number</td>
				<td>$vatnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>PAYE Ref</td>
				<td>$paye</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>SDL No</td>
				<td>$sdl</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>UIF No</td>
				<td>$uif</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Diplomatic Indemnity</td>
				<td>$diplomatic_indemnity</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Logo Image</td>
				<td bgcolor='#ffffff' align='center'>$logoimg</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>POS Logo Image</td>
				<td bgcolor='#ffffff' align='center'>$logoimg2</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='4' valign='top'>Address</td>
				<td>$addr1</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td>$addr2</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td>$addr3</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td>$addr4</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td rowspan='3' valign='top'>Postal Address</td>
				<td>$paddr1</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td>$paddr2</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<!-- rowspan -->
				<td>$paddr3</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Postal Code</td>
				<td>$pcode</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Telephone No.</td>
				<td>$tel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Fax No.</td>
				<td>$fax</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks();
	return $confirmInfo;

}



# write paye bracket changes to db
function writeInfo ($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($back)) {
		return showerr($_POST);
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
	$v->isOk ($pcode, "string", 0, 20, "Invalid postal code.");
	$v->isOk ($tel, "string", 0, 20, "Invalid telephone number.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax number.");
	$v->isOk ($diplomatic_indemnity, "string", 1, 3, "Invalid diplomatic indemnity selection.");
	$v->isOk ($paye, "string", 0, 30, "Invalid paye number.");
	$v->isOk ($sdl, "string", 0, 30, "Invalid sdl number.");
	$v->isOk ($uif, "string", 0, 30, "Invalid uif number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirmCust = "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$query = "SELECT * FROM compinfo WHERE div = '".USER_DIV."'";
	$Rslt = db_exec($query);

	if(pg_numrows($Rslt) > 0){
		$sql = "
			UPDATE compinfo 
			SET compname='$compname', regnum='$regnum', vatnum='$vatnum', slogan='$slogan', addr1='$addr1', addr2='$addr2', 
				paye='$paye', diplomatic_indemnity='$diplomatic_indemnity', addr3='$addr3', addr4='$addr4', paddr1='$paddr1', 
				paddr2='$paddr2', paddr3='$paddr3', postcode='$pcode', tel='$tel', fax='$fax', sdl='$sdl', uif='$uif' 
			WHERE div = '".USER_DIV."'";
        }else {
		$sql = "
			INSERT INTO compinfo (
				compname, regnum, vatnum, slogan, addr1, addr2, paye, diplomatic_indemnity, 
				addr3, addr4, paddr1, paddr2, paddr3, postcode, tel, fax, div, 
				sdl, uif
			) VALUES (
				'$compname', '$regnum', '$vatnum', '$slogan', '$addr1', '$addr2', '$paye', '$diplomatic_indemnity', 
				'$addr3', '$addr4', '$paddr1', '$paddr2', '$paddr3', '$pcode', '$tel', '$fax', '".USER_DIV."', 
				'$sdl', '$uif'
			)";
	}

	# Write info to Database
	db_connect();
	$rslt = db_exec($sql) or errDie("Unable to add company details to Cubit.", SELF);

	$writeInfo = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Company Details</th>
			</tr>
			<tr class='datacell'>
				<td>The Company Details have been successfully added to Cubit.</td>
			</tr>
		</table>"
		.mkQuickLinks();
	return $writeInfo;

}


?>
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

require("../settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT ="Invalid";
	}
} elseif(isset($_GET["id"])&&isset($_GET["type"])) {
	$OUTPUT = enter($_GET);
} else {
	return "Invalid .";
}

require("gw-tmpl.php");

function enter($_GET) {
	extract($_GET);

	$id+=0;

	if(isset($type)) {

		if($type=="cust") {
			$field="cust_id";
		} elseif($type=="supp") {
			$field="supp_id";
		} else {
			$field="id";
		}

		db_conn('cubit');
		$Sl="SELECT * FROM cons WHERE $field='$id'";
		$Ry=db_exec($Sl) or errDie("Unable to get con info.");

		if(pg_num_rows($Ry)<1) {
//			return "Invalid contact.";
			$name="";
			$mainname="None";
			$surname="";
			$pos="";
			$notes="";
			$tell="";
			$cell="";
			$fax="";
			$email="";
		}else {

			$data=pg_fetch_array($Ry);

			extract($data);

			$name="";
			$mainname = $data["surname"];
			$surname="";
			$pos="";
			$notes="";
			$tell="";
			$cell="";
			$fax="";
			$email="";
		}
	} else {
		db_conn('cubit');
		$Sl="SELECT * FROM cons WHERE id='$id'";
		$Ry=db_exec($Sl) or errDie("Unable to get con info.");

		if(pg_num_rows($Ry)<1) {
			return "Invalid contact.";
		}

		$data=pg_fetch_array($Ry);
		$mainname=$data['surname'];
	}

	if(isset($crm)) {
		$ex="<input type=hidden name=crm value=''>";
	} else {
		$ex="";
	}


	$out = "
				<h3>New Contact at $mainname</h3>
				<br>
				<table cellpadding='2' cellspacing='0' class='shtable'>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='id' value='$id'>
					$ex
					<tr>
						<th colspan='2'>Personal details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Main Contact</td>
						<td>$mainname</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>".REQ."Name</td>
						<td align='center'><input type='text' size='27' name='name' value='$name'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Position</td>
						<td align='center'><input type='text' size='27' name='pos' value='$pos'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Upload Image</td>
						<td align='center'>
							Yes <input type='radio' name='upload_img' value='yes' />
							No <input type='radio' name='upload_img' value='no' />
						</td>
					</tr>
					<tr>
						<th colspan='2'>Contact details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Telephone</td>
						<td align='center'><input type='text' size='27' name='tell' value='$tell'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Cellphone</td>
						<td align='center'><input type='text' size='27' name='cell' value='$cell'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Facsimile</td>
						<td align='center'><input type='text' size='27' name='fax' value='$fax'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Email</td>
						<td align='center'><input type='text' size='27' name='email' value='$email'></td>
					</tr>
					<tr>
						<th colspan='2'>Notes</th>
					</tr>
					<tr class='".bg_class()."'>
						<td colspan='2'><textarea name='notes' rows='4' cols='35'>$notes</textarea></td>
					</tr>
				</table>
				<p>
					<input type='submit' value='Confirm &raquo;'>
				</form>
				<p>
				<table cellpadding='2' cellspacing='0' class='shtable'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='list_cons.php'>List contacts</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='list_cons.php'>Contacts</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
			return $out;

}



function confirm($_POST)
{

	extract($_POST);

	$id+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($name, "string", 1, 100, "Invalid name.");
	$v->isOk ($pos, "string", 0, 100, "Invalid position.");
	$v->isOk ($tell, "string", 0, 100, "Invalid tel.");
	$v->isOk ($cell, "string", 0, 100, "Invalid cel.");
	$v->isOk ($fax, "string", 0, 100, "Invalid fax.");
	$v->isOk ($email, "email", 0, 100, "Invalid email.");
	$v->isOk ($notes, "string", 0, 200, "Invalid notes.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.enter($_POST);
	}



	db_conn('cubit');
	$Sl="SELECT * FROM cons WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get con info.");

	if(pg_num_rows($Ry)<1) {
		return "Invalid contact.";
	}

	$data=pg_fetch_array($Ry);
	$mainname=$data['surname'];

	if(isset($crm)) {
		$ex="<input type='hidden' name='crm' value=''>";
	} else {
		$ex="";
	}

	if ($upload_img == "yes") {
		$img = "
					<tr class='".bg_class()."'>
						<td>Upload Image</td>
						<td><input type='file' name='img_file'></td>
					</tr>";
	} else {
		$img = "
					<tr class='".bg_class()."'>
						<td>Upload Image</td>
						<td>No</td>
					</tr>";
	}

	$out = "
				<h3>New Contact at $mainname</h3>
				<br>
				<table cellpadding='2' cellspacing='0' class='shtable'>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='id' value='$id'>
					<input type='hidden' name='upload_img' value='$upload_img' />
					$ex
					<tr><th colspan='2'>Personal details</th></tr>
					<tr class='".bg_class()."'>
						<td>Main Contact</td>
						<td>$mainname</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Name</td>
						<td align='center'><input type='hidden' name='name' value='$name'>$name</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Position</td>
						<td align='center'><input type='hidden' name='pos' value='$pos'>$pos</td>
					</tr>
					$img
					<tr>
						<th colspan='2'>Contact details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Telephone</td>
						<td align='center'><input type='hidden' name='tell' value='$tell'>$tell</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Cellphone</td>
						<td align='center'><input type='hidden' name=cell value='$cell'>$cell</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Facsimile</td>
						<td align='center'><input type='hidden' name='fax' value='$fax'>$fax</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Email</td>
						<td align='center'><input type='hidden' name='email' value='$email'>$email</td>
					</tr>
					<tr>
						<th colspan='2'>Notes</th>
					</tr>
					<tr class='".bg_class()."'>
						<td colspan='2'><input type='hidden' name='notes' value='$notes'><pre>$notes</pre></td>
					</tr>
				</table>
				<p>
					<input type='submit' value='Write &raquo;'>
				</form>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='list_cons.php'>List contacts</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='../main.php'>Main Menu</a></td>
					</tr>
				</table>";
        return $out;

}



function write($_POST)
{

	extract($_POST);

	$id+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($name, "string", 1, 100, "Invalid name.");
	$v->isOk ($pos, "string", 0, 100, "Invalid position.");
	$v->isOk ($tell, "string", 0, 100, "Invalid tel.");
	$v->isOk ($cell, "string", 0, 100, "Invalid cel.");
	$v->isOk ($fax, "string", 0, 100, "Invalid fax.");
	$v->isOk ($email, "email", 0, 100, "Invalid email.");
	$v->isOk ($notes, "string", 0, 200, "Invalid notes.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.enter($_POST);
	}


	db_conn('cubit');
	$Sl="SELECT * FROM cons WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get con info.");

	if(pg_num_rows($Ry)<1) {
		return "Invalid contact.";
	}

	$Sl="INSERT INTO conpers (con,name,pos,tell,cell,fax,email,notes,div) VALUES('$id','$name','$pos','$tell','$cell','$fax','$email','$notes','".USER_DIV."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert conper.");

	$conid=pglib_lastid("conpers", "id");

	if(isset($crm)) {
		header("Location: crm/tokens-new.php?id=$id&conper=$conid");
		exit;
	}
	// Write the image (if any)
	if ($upload_img == "yes") {
		if (preg_match("/(image\/jpeg|image\/png|image\/gif)/",
			$_FILES["img_file"]["type"], $extension)) {
			$img = "";
			$fp = fopen ($_FILES["img_file"]["tmp_name"], "rb");
			while (!feof($fp)) {
				$img .= fread($fp, 1024);
			}
			fclose($fp);
			$img = base64_encode($img);

			$sql = "INSERT INTO cubit.scons_img (con_id, type, file, size)
			VALUES ('$con_id', '".$_FILES["img_file"]["type"]."', '$img',
				'".$_FILES["img_file"]["size"]."')";
			$ci_rslt = db_exec($sql) or errDie("Unable to add contact image.");
		} else {
			return "<li class='err'>
				Please note we only accept PNG, GIF and JPEG images.
			</li>";
		}
	}

	$out = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Contact added</th>
					</tr>
					<tr class='datacell'>
						<td>$name has been added to Cubit.</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='conper-add.php?type=conn&id=$id'>Add another contact</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='list_cons.php'>Contacts</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $out;

}


?>
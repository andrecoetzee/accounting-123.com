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

if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;

		default:
			if(isset($_GET['file'])){
					$OUTPUT =confirm2($_GET['file']);
			}else{
					$OUTPUT = show ();
			}
	}
} else {
	if(isset($_GET['file'])){
		$OUTPUT =confirm2($_GET['file']);
	}else{
		$OUTPUT = show ();
	}
}

# display output
require ("template.php");

# print Info from db
function show ()
{
		# start table, etc
		$show =
        "<h3>Upload accounts file</h3>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form ENCTYPE='multipart/form-data' action='".SELF."' method=post>
			<input type=hidden name=key value=confirm>
			<tr><th>Field</th><th>Value</th></tr>
			<tr class='bg-even'><td>Accounts File</td><td><input type=file size=20 name=accfile></td></tr>
			<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
			</form>
        </table>";

        return $show;
}

function confirm ($_POST)
{
        # get $_FILES global var for uploaded files
        global $_FILES;

        # get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # deal with logo image
        if (empty ($_FILES["accfile"])) {
			return "<li class=err> Please select a file to upload from your hard drive.";
		}
		if (is_uploaded_file ($_FILES["accfile"]["tmp_name"])) {
			// open the file
			$file = file($_FILES["accfile"]["tmp_name"]);
			// Layout
			$analyze = "<center><h3>File analysis</h3>
			<form action='".SELF."' method=post name=form>
        	<input type=hidden name=key value=write>
        	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr class='bg-even'><td>Department name</td><td><input type=text name=depname value='unknown' size=30></td></tr>
			<tr><th>Account number</th><th>Account name</th></tr>";
			foreach($file as $key => $value){
				$info = explode(",", $value);
				if(count($info) < 3){
					$analyze .= "<tr class='bg-even'><td colspan=2 align=center>$info[0]</td></tr>";
				}else{
					foreach($info as $key2 => $infos){
						$info[$key2] = str_replace("\"", "", $info[$key2]);
					}
					$analyze .= "<tr class='bg-odd'><td><input type=hidden name=accnum[] value='$info[1]'>$info[1]</td><td><input type=hidden name=accname[] value='$info[2]'>$info[2]</td></tr>";
				}
			}
			$analyze .= "<tr><td><br></td></tr>
			<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
			</table></form>";
		}
		return $analyze;
}

function confirm2($filename)
{
		$filename = "../".$filename;

		# check if folder exist
		if(!file_exists ($filename)){
			return "<li> File does not exist.";
		}
		# check if folder is a folder
		if(is_dir($filename)){
			return "<li>SElected file is a directory.";
		}

		$file = file($filename);

		// Layout
		$analyze = "<center><h3>File analysis</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>Account number</th><th>Account name</th></tr>";
		foreach($file as $key => $value){
			$info = explode(",", $value);
			if(count($info) < 3){
				$analyze .= "<tr class='bg-even'><td colspan=2 align=center>$info[0]</td></tr>";
			}else{
				foreach($info as $key2 => $infos){
					$info[$key2] = str_replace("\"", "", $info[$key2]);
				}
				$analyze .= "<tr class='bg-odd'><td>$info[1]</td><td>$info[2]</td></tr>";
			}
		}
		$analyze .= "</table>";

	return $analyze;
}

function write ($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($depname, "string", 1, 255, "Invalid department name.");

		// default department
		core_connect();
		$sql = "INSERT INTO defdep(depname) VALUES('$depname')";
		$Rs = db_exec($sql) or die("Unable to add DefDept");

		# get next ordnum
        $depid = pglib_lastid ("defdep", "depid");

		foreach($accnum as $key => $value){
			list($topacc, $accnum) = explode("/", $value);
			$sql = "INSERT INTO defacc(depid, topacc, accnum, accname) VALUES('$depid', '$topacc', '$accnum', '$accname[$key]')";
			$Rs = db_exec($sql) or die("Unable to add Defacc's");

		}

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

		$write = "<li> Inserted";

        return $write;
}
?>

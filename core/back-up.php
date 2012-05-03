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
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			$OUTPUT = confirm();
			break;
	}
}else{
	$OUTPUT = confirm();
}

# get templete
require("template.php");

# View details
function confirm()
{
		# check if folder exist
		if(!file_exists ("../backup")){
			return "<li> Back up folder doesn't exits.";
		}
		# check if folder is a folder
		if(!is_dir("../backup")){
			return "<li>/<cubit>/backup is a file. Back up folder doesn't exits.";
		}
		$fspace = round(((diskfreespace("../backup")/1024)/1024), 2);

		// Layout
		$confirm = "<center><h3>Save Backup</h3>
        <h4>Details</h4>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr><th>Before you continue please note the following</th></tr>
			<tr class='bg-odd'><td><li> Make sure that the folder /<path tp cubit>/backup/ is owned by or can be written into by the user running you postgres(eg. wwwrun)</td></tr>
			<tr class='bg-odd'><td><li> Make sure that you have enough space left on the hard drive (+-100 MB recommanded)</td></tr>
			<tr><td><br></td></tr>
			<tr class='bg-odd'><td>Currently available space is : $fspace MB</td></tr>
			<tr><td><input type=submit value='Confirm &raquo'></td></tr>
		</table>
		<br><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        	<tr><th>Quick Links</th></tr>
			<tr class='bg-even'><td><a href='main.php'>Main Menu</a></td></tr>
         </form>
        </table>";

	return $confirm;
}

# write
function write()
{
		/*
		//processes
		db_connect();
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();

        # display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>".$e["msg"];
			}
			$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}
		*/

		// system command
		$date = date("d-m-Y");
		$shell = exec("pg_dumpall -c -U postgres > ../backup/cubit-$date.sql");

		# check if file exists
		if(!file_exists ("../backup/cubit-$date.sql")){
			return "<li>Failed to make backup : $shell.";
		}

		$write ="
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>System Backup</th></tr>
			<tr class=datacell><td>Backup File <b>cubit-$date.sql</b> has been successfully saved.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-even'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

		return $write;
}
?>

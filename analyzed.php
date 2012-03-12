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

$OUTPUT = analyze("../accstruct/".$_GET["file"]);

# get templete
require("template.php");

# View details
function analyze($filename)
{
		# check if folder exist
		if(!file_exists ($filename)){
			return "<li> File does not exist.";
		}
		# check if folder is a folder
		if(is_dir($filename)){
			return "<li> Selected file is a directory.";
		}

		$file = file($filename);

		// Layout
		$analyze = "<center><h3>File analysis</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>Account number</th><th>Account name</th></tr>";
		foreach($file as $key => $value){
			$info = explode(",", $value);
			if(count($info) < 3){
				$analyze .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2 align=center>$info[0]</td></tr>";
			}else{
				foreach($info as $key2 => $infos){
					$info[$key2] = str_replace("\"", "", $info[$key2]);
				}
				$analyze .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$info[1]</td><td>$info[2]</td></tr>";
			}
		}
		$analyze .= "</table>";

	return $analyze;
}
?>

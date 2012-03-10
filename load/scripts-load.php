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

# Display default output
$OUTPUT = load($HTTP_GET_VARS['data']);

# get templete
require("template.php");

function load($data)
{
        if(!($file = file ($data))){
                return "Failed to open File";
        }

        db_connect();
        $q = "";
        foreach($file as $key => $value){
                $row = explode("\t",$value);
                print strtoupper($row[0])." => $row[1]<br><br>";

                $sql = "INSERT INTO scripts(script, name) VALUES('".rtrim(strtoupper($row["1"]))."','".rtrim($row[0])."')";
                $q .="<br>".$sql;
                $rslt = db_exec($sql);
        }
        # clean '\n' from the script names
        $sql = "UPDATE scripts SET name = trim(both '\n' from name)";
        $rslt = db_exec($sql);

print "<hr>$q";
}

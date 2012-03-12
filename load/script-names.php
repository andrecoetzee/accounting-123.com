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
$OUTPUT = edit($_GET['file']);

# get templete
require("template.php");

# view all tennants information for editing
function edit($file)
{
        if(!($file = file ($file))){
                return "<font color=red><b><li>Failed to open File";
        }

         # open/create results file
        if(!($ofile = fopen("script-names.dat","w+"))){
                return "Unable to create/open results file";
        }

        $tab = "<center><table bgcolor=#ffffff width=60%>
        <tr><th>Script Name</th></tr>";

        foreach($file as $key => $value){
                # get basename
                $ScrName = basename($value);

                if($ScrName != "template.php" || $ScrName != "settings.php" || $ScrName != "core-settings.php"){
                        # write to table
                        $tab .= "<tr><td>$ScrName</td></tr>";

                        # write to file
                        fwrite($ofile, "$ScrName");
                }
        }
        $tab .= "</table>";

        return $tab;
}

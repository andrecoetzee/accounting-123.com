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
$OUTPUT = compare($file1,$file2);

# get templete
require("template.php");

# compare file one to file two
function compare($file1, $file2)
{
        # open file 1
        if(!($file1 = file ($file1))){
                return "Failed to open File 1";
        }

        foreach($file1 as $key => $value){
                $file1[$key] = rtrim($value);
        }

        # open file 2
        if(!($file2 = file ($file2))){
                return "Failed to open File 2";
        }

        foreach($file2 as $key => $value){
                $file2[$key] = rtrim($value);
        }


        # open/create results file
        if(!($diff = fopen("diff.dat","w+"))){
                return "Unable to create/open results file";
        }

        foreach($file1 as $key => $line){
                if (!(in_array ($line, $file2))){
                        print "$line<br>";
                        fwrite($diff,"$line\n");
                }
        }
}

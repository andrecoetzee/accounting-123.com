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
$OUTPUT = download();

# get templete
require("template.php");

# view all tennants information for editing
function download()
{
         # open/create results file
        if(!($file = fopen("db_scripts.dat","w+"))){
                return "Unable to create/open results file";
        }

        # query the DB
        db_connect();
        $sql = "SELECT * FROM scripts";
        $rslt = db_exec($sql);

        # get records
        while($rec = pg_fetch_array($rslt)){
                foreach($rec as $key => $value){
                        $$key = $value;
                }
                print "$script\t$name<br>";
                fwrite($file,"$name\n");
        }
}
?>

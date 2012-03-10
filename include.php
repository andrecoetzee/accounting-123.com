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

//Global Variables
$host = "localhost";
$user = "postgres";
$pass = "i56kfm";
$db = "cubit";

//connection function
function conn(){
     global $host,$user,$pass,$db;
        $connection = pg_connect("dbname=" .$db." user=".$user." password=".$pass);
    if (!$connection) {
       die("Could not open connection to database server");
    }
       return $connection;
}

function head($title){
         echo "<html>
        <head>
        <title> ::::: ".$title." ::::: </title>
        <style type='text/css'>
        <!--
        	body
        	{
                font-family: sans-serif;
        	background-color: #EEEFFF;
        	font-size: 10pt;
		color: #000000;
        	}
        -->
        </style>
        </head>
        <body>";
}

function footer(){
  echo  "<div align=center>
        <br>
        <br>
        <font size='-2' color='darkgrey'>(c)2002 Cubit Accounting cc
        <br>
        All Rights Reserved
        <br>
        Cubit, and the cubit logo are registered trademarks of Cubit Accounting cc
        </font>
        </div align=center>
        </body>
        </html>";
}

function nevbar(){
echo "<br><div align=center>
      <a href='javascript:history.back()'>Back</a> |
      <a href='fixed_assets_module.php'>Home</a>
      </div>";
}

function error($err)
{
echo "
<br><br>
<center>
<b>Request not Done</b><br><br>
<table width=300 cellspacing=0 border=1 cellpadding=0 align='center'>
    <tr><td><font color=red>ERROR !</font></td></tr>
    <tr>
        <td bgcolor='lightGray' align=center><br>
           <font size='+1' color=red>  ".$err."</font><BR><br>
        </td>
    </tr>
</table>";
nevbar();
die(footer());
}
?>

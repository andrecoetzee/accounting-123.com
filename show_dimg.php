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
        // Settings for db, etc
        require ("settings.php");

        global $_GET;
        
        if (!isset($_GET["picid"]))
        	die ("");
        
        // Get image binary from db
        db_connect();
        $sql = "SELECT image_data,image_type FROM display_images WHERE id = '$_GET[picid]' LIMIT 1";
        $imgRslt = db_exec ($sql) or errDie ("Unable to retrieve image from database",SELF);
        $imgBin = pg_fetch_array ($imgRslt);

        $img = base64_decode($imgBin["image_data"]);
        $mime = $imgBin["image_type"];

        header ("Content-Type: ". $mime ."\n");
        header ("Content-Transfer-Encoding: binary\n");
        header ("Content-length: " . strlen ($img) . "\n");

        //send file contents
        print $img;
?>

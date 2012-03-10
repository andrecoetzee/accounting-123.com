<?php


// Settings for db, etc
require ("../settings.php");

if(!isset($_GET["id"])) {
	exit;
}

$id = $_GET["id"];
$id += 0;

// Get image binary from db
$sql = "SELECT type, file FROM cubit.cons_img WHERE con_id='$id'";
$imgRslt = db_exec ($sql) or errDie ("Unable to retrieve image from database",SELF);
$imgBin = pg_fetch_array ($imgRslt);

$img = base64_decode($imgBin["file"]);
$mime = $imgBin["type"];

header ("Content-Type: ". $mime ."\n");
header ("Content-Transfer-Encoding: binary\n");
header ("Content-length: " . strlen ($img) . "\n");

//send file contents
print $img;


?>
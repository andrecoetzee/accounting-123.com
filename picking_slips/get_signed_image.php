<?php

require("../settings.php");

extract ($_REQUEST);

$sql = "
SELECT file_type, file_name, file
FROM cubit.pslip_signed_index
	LEFT JOIN cubit.pslip_signed_files
		ON pslip_signed_index.id=pslip_signed_files.id
WHERE sordid='$sordid'";
$file_rslt = db_exec($sql) or errDie("Unable to retrieve image.");
list($file_type, $file_name, $file) = pg_fetch_array($file_rslt);

header ("Content-Type: ". $file_type ."\n");
header ("Content-Transfer-Encoding: binary\n");
header ("Content-length: " . strlen ($file) . "\n");

print base64_decode($file);


?>
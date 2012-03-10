<?

require("../settings.php");
require("./parsexml.php");

header("Content-Type: application/octet-stream");
//header("Content-Length: ".strlen(makeXML() +11).");
header("Content-Transfer-Encoding: binary");
header("Content-Disposition: attachment; filename=\"replay-batch.xml\"");
print makeXML();


?>
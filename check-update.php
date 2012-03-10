<?

$o = print_r($GLOBALS, true);

file_put_contents("/tmp/update.cubit", "d:\n$o");
?>

#!/opt/php/bin/php
<?
if ( empty($argv[1]) || empty($argv[2]) || empty($argv[3]) ) {
        print "FOOL! $argv[0] <file_in> <klip string> <dest string>\n\n";
        exit();
}

$fin = "$argv[1].buf";
$fout = $argv[1];
$str_klip = $argv[2];
$str_dest = $argv[3];

if ( ! rename($fout, $fin) ) {
	print "Error creating buffer file.\n";
	exit();
}

if ( ! ($fd_in = @fopen($fin, "r")) ) {
        print "Error opening input file!\n";
        exit();
}

if ( ! ($fd_out = @fopen($fout, "w")) ) {
        print "Error opening output file!\n";
        exit();
}

while ( ! feof($fd_in) ) {
        $buf = fread($fd_in, 4096);

        fwrite($fd_out, str_replace($str_klip, $str_dest, $buf));
}

fclose($fd_in);
fclose($fd_out);

if ( ! unlink($fin) ) {
	print "Error removing buffer file.\n";
	exit();
}

print "Replaced $argv[1]\n";

?>

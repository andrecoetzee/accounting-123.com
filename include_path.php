<?

$root = getcwd();
$cd = opendir($root);
$includes = array();
while ($f = readdir($cd)) {
	if ($f[0] != "." && is_dir("$root/$f")) {
		$includes[] = "$root/$f";
	}
}
closedir($cd);

$i = implode(PATH_SEPARATOR, $includes);

ini_set("include_path", $i.PATH_SEPARATOR.".");

print "<br>";
print ini_get('include_path');
?>

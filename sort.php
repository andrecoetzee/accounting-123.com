// sorter
$sortarr = & $out; // where $out = array name to sort
for ( $j = 0; $j < count($sortarr); $j++ ) {
	for ( $i = 0; $i < count($sortarr) - 1; $i++ ) {
		if ( $sortarr[$i] < $sortarr[$i + 1] ) {
			$buf = $sortarr[$i];
			$sortarr[$i] = $sortarr[$i + 1];
			$sortarr[$i + 1] = $buf;
		}
	}
}

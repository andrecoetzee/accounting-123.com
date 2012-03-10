<?

if (!defined("XLS_LIB")) {
	define("XLS_LIB", true);

function Stream($filename, $output) {
	StreamXLS($filename, $output);
}

function StreamXLS($filename, $OUT){
	header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	header("Pragma: no-cache" );
	header("Content-type: application/x-msexcel" );
	header("Content-Disposition: attachment; filename=\"$filename.xls\"" );
	header("Content-Description: PHP Generated XLS Data" );

    if (true) {
	    /* find the start of the first row */
		$sp = strpos($OUT, "<tr");
		$ep = strpos($OUT, "</tr", $sp);

		/* count the cells */
		$tmp = substr($OUT, $sp, $ep - $sp);
		$th = substr_count($tmp, "<th");
		$td = substr_count($tmp, "<td");

		$cells = $th + $td;

		/* count the extra columns (colspans) */
		preg_match_all("/colspan='?([0-9]+)'?/", $tmp, $m);

		foreach ($m[1] as $amt) {
			$cells += $amt - 1;
		}

		if ($cells >= 3) {
			$cn = 2;
			$dt = $cells - 2;
		} else {
			$cn = 1;
			$dt = 1;
		}
    } else {
    	$cn = 1;
    	$dt = 1;
    }

	$compinfo = "
	<tr>
		<th align='left' colspan='$cn'>".COMP_NAME."</th>
		<th align='right' colspan='$dt'>Date: ".date("Y-m-d")."</th>
	</tr>";

	$OUT = preg_replace("/(<table[^>]*) border=[^ >]+/", "\\1", $OUT);
	$OUT = preg_replace("/<table([^>]*)>/", "<table \\1 border='0'>", $OUT);
	$OUT = preg_replace("/<table([^>]*)>/", "<table \\1>$compinfo", $OUT, 1);
	print $OUT;
	exit(0);
}

} /* LIB END */
?>

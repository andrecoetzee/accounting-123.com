<?

# streaming function
function Stream($filename, $output){
	header ( "Expires: Mon, 28 Aug 1984 05:00:00 GMT" );
	header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	header ( "Pragma: no-cache" );
	header ( "Content-type: application/x-msexcel" );
	header ( "Content-Disposition: attachment; filename=$filename.xls" );
	header ( "Content-Description: PHP Generated XLS Data" );
	print $output;
	exit();
}

?>

<?

$pattern = "/[\d{2,2}]/";
$val = "1222222222222222222222222222222222222222222222222222222222222222222";

if(preg_match($pattern, $val)){
	print "valid\n";
}else{
	print "invalid\n";
}
?>

<?php

require_lib("encrypt");

function pick_slip_barcode($invid, $pinv=0)
{
	$invid = str_pad($invid, 10, "0", STR_PAD_LEFT);

	$enc = new Encryption;
	//$barcode = $enc->encrypt("MiDMaCoR", $invid);
	$barcode = base64_encode($invid);
	
	$barcode = preg_replace("/=/", "", $barcode);

	if ($pinv) {
		$barcode_img = getBarcode($barcode, "code128");
	} else {
		$barcode_img = "../manufact/barcode" . getBarcode($barcode, "code128");
	}
	$barcode_img = preg_replace("/ /", "", $barcode_img);
	
	return $barcode_img;
}

function decrypt_barcode($barcode)
{
	$decrypt = base64_decode($barcode);
	
	//$dec = new Encryption;
	//$decrypt = $dec->decrypt("MiDMaCoR", $decrypt);

	return $decrypt;
}

?>
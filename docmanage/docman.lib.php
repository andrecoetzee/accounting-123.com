<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#


# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "docman.lib.php") {
	exit;
}

# Document types
$DOCLIB_DOCTYPES = array(
					"inv" => "Invoice",
					"ninv" => "Non-Stock Invoice",
					"note" => "Credit Note",
					//"sord" => "Sales Order",
					//"cord" => "Consciment Order",
					"prec" => "Petty Cash Receipt",
					"pur" => "Purchase",
					"ipur" => "International Purchase",
					"npur" => "Non-Stock purchase",
					"empl" => "Employee Document");

# Document types Input
$DOCLIB_DOCTYPESIN = array(
					"inv" => "Invoice",
					"ninv" => "Non-Stock Invoice",
					"note" => "Credit Note",
					"prec" => "Petty Cash Receipt",
					"pur" => "Purchase",
					"ipur" => "International Purchase",
					"npur" => "Non-Stock purchase",
					"empl" => "Employee");


# Extra Input
function xin($typeid, $xin = ""){
	global $DOCLIB_DOCTYPESIN;
	if(!preg_match("/\d/", $typeid)){
		return "<tr class='bg-odd'><td>$DOCLIB_DOCTYPESIN[$typeid] No.</td>
		<td><input type=text size=4 name=xin value='$xin'></td></tr>";
	}
}

# For Confirm => extra input
function xinc($typeid, $xin){
	global $DOCLIB_DOCTYPESIN;
	if(!preg_match("/\d/", $typeid)){
		return "<tr class='bg-odd'><td>$DOCLIB_DOCTYPESIN[$typeid] No.</td>
		<td><input type=hidden name=xin value='$xin'>$xin</td></tr>";
	}
}

# View document of type and input
function doclib_getdocs($typeid, $xin){
	db_conn(YR_DB);
	$sql = "SELECT * FROM documents WHERE typeid = '$typeid' AND xin = '$xin' AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
	$ret = "";
	if(pg_numrows($rs) > 0){
		$ret = "<table border=0 cellspacing=1 cellpadding=3>
		<tr class='bg-even'>";
		for($i = 0; $doc = pg_fetch_array($rs); $i++){
			$ret .= "<td><a href='docmanage/docdload.php?docid=$doc[docid]'>$doc[docname]</a></td>";
		}
		$ret .= "</tr>
		</table>";
	}
	return $ret;
}

# Encoding function
function doclib_encode($data, $strength){

	# bzip2 compression
	# $data = bzcompress($data, $strength);

	# base 64 encoding
	$data = base64_encode($data);

	# return data
	return $data;
}

# Decoding function
function doclib_decode($data){

	# base 64 decoding
	$data = base64_decode($data);

	# bzip2 decompression
	# $data = bzdecompress($data);

	# return data
	return $data;
}

# Streaming function
function stream($filename, $output, $mime){
	header ( "Expires: Mon, 28 Aug 1984 05:00:00 GMT" );
	header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	header ( "Pragma: no-cache" );
	header ( "Content-type: $mime" );
	header ( "Content-Disposition: attachment; filename=$filename" );
	print $output;
	exit();
}
?>

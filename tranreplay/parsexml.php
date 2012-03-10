<?

require("./replayobj.php");

global $complete; $complete = array(
	"DEBTOR" => "",
	"CREDITOR" => "",
	"STOCK" => "",
	"JOURNAL" => ""
);

global $stack; $stack = array();
global $stack_counter; $stack_counter = 0;
global $stack_ptr; $stack_ptr = false;
global $inc_active; $inc_active = false;

function parseXML($filename) {
	if (!is_readable($filename)) {
		return false;
	}

	$XML = file_get_contents($filename);

	$parser = xml_parser_create();
	xml_set_element_handler($parser, "startElement", "endElement");
	xml_parse($parser, $XML, true);
	xml_parser_free($parser);
}

function dump() {
	print "<xmp>";
	global $complete;
	var_dump($complete);
	print "\naccounts:\n";
	var_dump(clsIncludes::$accounts);
	print "\ncurrency:\n";
	var_dump(clsIncludes::$currency);
	print "\ndepartment:\n";
	var_dump(clsIncludes::$department);
	print "\npricelist:\n";
	var_dump(clsIncludes::$pricelist);
	print "\nstore:\n";
	var_dump(clsIncludes::$store);
	print "\nvatcode:\n";
	var_dump(clsIncludes::$vatcode);
	print "</xmp>";
}

function prepareXMLObjects() {
	global $complete;
	$sql = "SELECT * FROM exten.tranreplay";
	$rslt = db_exec($sql);
	
	while ($t = pg_fetch_assoc($rslt)) {
		switch ($t["ttype"]) {
			case "journal":
				$func = "makeGeneralLedger";
				break;
			case "creditor":
				$func = "makeSuppLedger";
				break;
			case "debtor":
				$func = "makeCustLedger";
				break;
			case "stock":
				$func = "makeStockLedger";
				break;
		}
		
		$tp = strtoupper($t["ttype"]);
		
		$v = new clsLedger($tp);
		$complete["JOURNAL"][$v->id] = $v;
		
		switch ($t["ttype"]) {
			case "journal":
				$v->$func($t["debitacc"], $t["creditacc"], $t["tdate"], $t["refno"],
						$t["amount"], $t["vat"], $t["details"]);
				break;
			case "creditor":
			case "debtor":
			case "stock":
				if ($t["debitacc"] != "0") {
					$acct = "d";
					$acc = $t["debitacc"];
				} else {
					$acct = "c";
					$acc = $t["creditacc"];
				}
				
				$v->$func($t["iid"], $acct, $acc, $t["tdate"], $t["refno"],
						$t["amount"], $t["vat"], $t["details"]);
		}
	}
}

function makeXML() {
	global $complete;
	
	prepareXMLObjects();
	
	$XML = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n";
	$XML .= "<transactiondata>\n\n";
	
	$incs = array(
		"accounts",
		"currency",
		"department",
		"pricelist",
		"store",
		"vatcode"
	);
	
	/* includes */
	foreach ($incs as $inctype) {
		$XML .= "\t<includes type=\"$inctype\">\n";
		
		foreach (clsIncludes::${$inctype} as $id => $r) {
			if (!is_array($r)) {
				continue;
			}
			
			$attr = array();
			foreach ($r as $n => $v) {
				if($n == "id")
					continue;
				$attr[] = "$n=\"$v\"";
			}
			
			$XML .= "\t\t<incdef id=\"$id\" ".implode(" ", $attr)." />\n";
		}
		
		$XML .= "\t</includes>\n\n";
	}

//print "<pre>";
//var_dump($complete);
//print "</pre>";

	/* main objects */
	foreach ($complete as $otype => $objarr) {
		if(!is_array($objarr) OR (strlen ($objarr) < 1)){
			continue;
		}

		if ($otype == "JOURNAL") {			
			foreach ($objarr as $id => $oobj) {
				$XML .= "\t<journal type=\"".$oobj->type."\">\n";
				
				foreach ($oobj->cols as $n => $v) {
					if (is_null($v)) {
						continue;
					}
// || $n == "iid"
					if ($n == "debitacc" || $n == "creditacc") {
						$XML .= "\t\t<jinfo name=\"$n\" include=\"$v\" />\n";
					} else {
						$XML .= "\t\t<jinfo name=\"$n\" value=\"$v\" />\n";
					}
				}
			
				$XML .= "\t</journal>\n\n";
			}
		} else {
			$stype = strtolower($otype);

			foreach ($objarr as $id => $oobj) {
				$XML .= "\t<$stype id=\"".$oobj->id."\">\n";

				foreach ($oobj->cols as $n => $v) {
					if (is_null($v)) {
						continue;
					}
					
					if (isset(clsInfoObj::$typeIncCols[$otype][$n])) {
						$XML .= "\t\t<iinfo name=\"$n\" include=\"$v\" />\n";
					} else {
						$XML .= "\t\t<iinfo name=\"$n\" value=\"$v\" />\n";
					}
				}
				
				$XML .= "\t</$stype>\n\n";
			}
		}
	}
	
	$XML .= "</transactiondata>\n";
	
	return $XML;
}

function startElement($parser, $name, $pattrs) {	
	global $stack, $stack_counter, $stack_ptr, $complete, $inc_active;
	
	if ($name == "TRANSACTIONDATA") {
		return;
	}

	$newobj = false;
	switch ($name) {
		case "DEBTOR":
		case "CREDITOR":
		case "STOCK":
			$newobj = new clsInfoObj($name, $pattrs["ID"]);
			break;
			
		case "JOURNAL":
			$newobj = new clsLedger($pattrs["TYPE"]);
			break;
			
		case "INCLUDES":
			$inc_active = $pattrs["TYPE"];
			break;
			
		case "INCDEF":
			clsIncludes::addXML($inc_active, $pattrs);
			break;
			
		default:
			if ($stack_ptr !== false) {
				$stack_ptr->xmlStartElement($parser, $name, $pattrs);
			}
	}
	
	if ($newobj !== false) {
		++$stack_counter;
		$stack[$stack_counter] = $stack_ptr = $newobj;
	}
}

function endElement($parser, $name) {
	global $stack, $stack_counter, $stack_ptr, $complete, $inc_active;
	
	if ($name == "TRANSACTIONDATA") {
		return;
	}
	
	switch ($name) {
		case "DEBTOR":
		case "CREDITOR":
		case "JOURNAL":
		case "STOCK":
			$complete[$name][$stack_ptr->id] = $stack_ptr;
			unset($stack[$stack_counter]);
			--$stack_counter;
			break;
			
		case "INCLUDES":
			$inc_active = false;
			break;
			
		default:
			if ($stack_ptr !== false) {
				$stack_ptr->xmlEndElement($parser, $name);
			}
	}
}

?>
<?

require("../settings.php");
require("./parsexml.php");

//parsefile("sampletr.xml");
function export() {
	
	$xml = makeXML();
	print "<xmp>$xml</xmp>";
}

function import() {
	global $complete;
	
	parseXML("sampletr.xml");
	
	foreach ($complete["JOURNAL"] as $jobjs) {
		// go through each journal
		foreach ($jobjs as $journal) {
			$parms = $journal->cols;
			
			if ($parms["debitacc"] != "0") {
				$debitacc = clsIncludes::$accounts[$parms["debitacc"]];
			}
			
			if ($parms["creditacc"] != "0") {
				$creditacc = clsIncludes::$accounts[$parms["creditacc"]];
			}
			
			switch ($journal->type) {
				case "DEBTOR":
					$debtor = $complete["DEBTOR"][$parms["iid"]]->cols;
					break;
				case "CREDITOR":
					$creditor = $complete["CREDITOR"][$parms["iid"]]->cols;
					break;
				case "STOCK":
					$stock = $complete["STOCK"][$parms["iid"]]->cols;
					break;
				case "JOURNAL":
					break;
			}
		}
	}
}

?>
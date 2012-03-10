<?

$active_tag = array();

function sElement($parser, $name, $attrs) {
	global $active_tag;
	foreach ($attrs as $k => $v) {
		$attrs[$k] = "$k=\"$v\"";
	}
	//print "$name (".implode(", ", $attrs).")<br />";
	array_push($active_tag, $name);
	print "<br />[[s:$name]]<br />";
}

function eElement($parser, $name) {
	global $active_tag;
	array_pop($active_tag);
	print "<br />[[e:$name]]<br />";
}

function cdElement($parser, $data) {
	global $active_tag;
	//print " - (".$active_tag[count($active_tag) - 1].") data: $data<br />";
}

$xml =
'<start>
	<mid num="1">mid1data</mid>
	<mid num="2">mid2data is even more</mid>
	<flickme a="1" b="2" c="3" />
</start>';

$parser = xml_parser_create();
xml_set_element_handler($parser, "sElement", "eElement");
xml_set_character_data_handler($parser, "cdElement");
xml_parse($parser, $xml, true);
xml_parser_free($parser);
?>
<?
$cubit_header = "reporting/ledger_export.php:Export General Ledger";

require("../settings.php");
require_lib("xls");

$OUTPUT = export();

require("../template.php");

function export() {

	$oc = array();

	/* add (if any) last closed year */
	$qry = new dbSelect("year", "core", grp(
		m("cols", "yrdb"),
		m("where", "closed='y'"),
		m("order", "yrname DESC"),
		m("limit", "1")
	));
	$qry->run();

	if ($qry->num_rows() > 0) {
		$qry->fetch_array();

		add_tbdata($oc, $qry->d["yrdb"], 1);
	}

	/* add current year */
	add_tbdata($oc, "core");

	/* generate output information */
	$headings = "<td rowspan='1'></td>";
	$subhead = "";
	$accinfo = array();

	/* go through each period */
	foreach ($oc as $name => $data) {
		$headings .= "<td colspan='1'>$name</td>";
		//$subhead .= "<th>Debit</th><th>Credit</th>";

		/* go through each account for current period */
		foreach ($data as $accname => $accdata) {
			if (!isset($accinfo[$accname])) {
				$accinfo[$accname] = "<td>$accname</td>";
			}

			$accinfo[$accname] .= "<td align='right'>".fsmoney($accdata["debit"] - $accdata["credit"])."</td>";
		}
	}

	/* build output information */
	$OUT = "<table>";
	$OUT .= "<tr>$headings</tr>\n";
	//$OUT .= "<tr>$subhead</tr>\n";
	$OUT .= "<tr>".implode("</tr>\n<tr>", $accinfo)."</tr>";
	$OUT .= "</table>";

	/* stream information */
	$months = count($oc); // number of months of data we gathered
	StreamXLS("GeneralLedger$months", $OUT);
	return $OUT;
}

function add_tbdata(&$oc, $schema, $yearsback = 0) {
	global $MONPRD, $PRDMON;

	/* fetch prev year trial bal data */
	$tb = new dbSelect("trial_bal_actual", $schema, grp(
		m("where", "period!='0'"),
		m("order", "period, acctype, topacc, accnum")
	));
	$tb->run();

	$cprd = false;
	$cprd_name = false;
	while ($row = $tb->fetch_array()) {
		/* the period in the table data changed */
		if ($cprd != $row["period"]) {
			$cprd = $row["period"];

			$year = getYearOfFinMon($PRDMON[$cprd]) - $yearsback;
			$mon = getMonthNameS($PRDMON[$cprd]);
			$cprd_name = "$mon $year";

			$oc[$cprd_name] = array();
		}

		if ($row["period"] == 1 && $row["acctype"] != "B") {
			$hcode = new dbSelect("trial_bal", $schema, grp(
				m("where", "period='1' AND accid='$row[accid]'"),
				m("limit", 1)
			));
			$hcode->run();

			$row = $hcode->fetch_array();
		}

		/* this is such an ugly hack... close your eyes or thou shalt go blind! */
		else if ($row["period"] == 1 && $row["topacc"] == "5200" && $row["accnum"] == "000") {
			/* calculate previous year profit/loss */
			$sql = "SELECT SUM(tb.credit) AS credit, SUM(tb.debit) AS debit
					FROM core.accounts acc LEFT JOIN $schema.trial_bal tb
						ON acc.accid=tb.accid AND acc.div=tb.div
					WHERE (acc.acctype='I' OR acc.acctype='E') AND acc.div='".USER_DIV."'
						AND tb.period='0'";
			$qry = new dbSql($sql);
			$qry->run();

			/* then deduct from debit/credit of retained income/accumulated loss */
			$qry->fetch_array();

			$row["debit"] -= $qry->d["debit"];
			$row["credit"] -= $qry->d["credit"];
		}

		/* store data */
		$oc[$cprd_name]["$row[topacc]/$row[accnum] $row[accname]"] = array(
			"debit" => $row["debit"],
			"credit" => $row["credit"]
		);
	}

	#sort array to make some sense
	$oc = natksort($oc);

}

function natksort($array) {
	// Like ksort but uses natural sort instead
	$keys = array_keys($array);

	if(!isset($newarr))
		$newarr = array ();

	while (count($newarr) < count($keys)){
		foreach ($keys as $each){
			$arr = explode (" ",$each);
			$month = $arr[0];
			$year = $arr[1];
			
			$months=array ("Jan"=>1, "Feb"=>2, "Mar"=>3, "Apr"=>4, "May"=>5, "Jun"=>6, "Jul"=>7, "Aug"=>8, "Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
			$thisdate = date("Y-m-d",mktime(0,0,0,$months[$month],1,$year));
			
			$newarr[$thisdate] = $each;
	  	}
	}

	ksort ($newarr);

	foreach ($newarr as $null => $k)
		$new_array[$k] = $array[$k];

	return $new_array;
}
?>
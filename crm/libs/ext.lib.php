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
if (basename (getenv ("SCRIPT_NAME")) == "ext.lib.php") {
	exit;
}

if(!defined("EXT_LIB_PHP")){
	define("EXT_LIB_PHP", 0x0f);

	function viewemppic ($emp){

		$emp+=0;

		$Sl="SELECT * FROM eimgs WHERE emp='$emp'";
			$Ry=db_exec($Sl);
			$data=pg_fetch_array($Ry);

		$img = base64_decode($data["image"]);
			$mime = $data["imagetype"];

			header ("Content-Type: ". $mime ."\n");
			header ("Content-Transfer-Encoding: binary\n");
			header ("Content-length: " . strlen ($img) . "\n");

		return $img;

	}

	# some useful fuctions
	function com_invoice ($rep,$amount,$com,$inv){
		if($com>0) {
			$date=date("Y-m-d");
			$Sl="INSERT INTO coms_invoices (rep,invdate,inv,amount,com) VALUES('$rep','$date','$inv','$amount','$com')";
			$Rx=db_exec($Sl);
		}
	}

	# reformat date
	function ext_rdate($date){
		$date  = explode("-", $date);
		$date = "$date[2]-$date[1]-$date[0]";
		return $date;
	}

	function ext_amt($amt){
		list($amts, $dec) = explode(".", $amt);
		$ret = "";
		// $amts = strrev($amts);
		for($i = 0; $i < strlen(rtrim($amts)); $i++){
			$ret .= $amts[$i];
			$ret .= ($i % 3) ? "" : ",";
		}
		// $ret = strrev($ret);
		return "$ret.$dec";
	}

	# check whether a certain value already exits in a table
	function ext_ex($tab,$field,$val){
		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
		return TRUE;}
		else {
		return FALSE;}
	}

	/**
	* check whether a certain value already exits in a table
	*
	* @ignore
	*/
	function barext_ex($tab,$field,$val){
		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND div = '".USER_DIV."' AND active = 'yes'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	# check whether a certain value already exits in a table
	function ext_undex($tab,$field,$val){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}
		else {return FALSE;}
	}

	# check whether a certain value already exits in a table
	function ext_ex2($tab, $field, $val, $field2, $val2){

		$ExtSl = "SELECT ".$val." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."='".$val2."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}
	function ext_undex2($tab, $field, $val, $field2, $val2){

		$ExtSl = "SELECT ".$val." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."='".$val2."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	# check whether a certain value already exits in a table
	function ext_exx($tab, $field, $val, $field2, $op, $val2){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."$op'".$val2."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	# check whether a certain value already exits in a table
	function ext_undexx($tab, $field, $val, $field2, $op, $val2){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."$op'".$val2."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}


	# check whether a certain value already exits in a table
	function ext_exx3($tab, $field, $val, $field1, $val1, $field2, $op, $val2){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field1."='".$val1."' AND ".$field2."$op'".$val2."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	# check whether a certain value already exits in a table
	function ext_ex3($tab, $field, $val, $field2, $val2, $field3, $val3){

		$ExtSl = "SELECT ".$val." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."='".$val2."' AND ".$field3."='".$val3."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	# writes a commision record for salesrep
	function coms($rep,$amount,$per,$credit="") {
		db_connect();
		$per +=0;
		if($per > 0){$amount = round(($amount*$per/100),2);}else {$amount=0;}
		$amount +=0;
		if($credit!="") {$amount = $amount*-1;}
		if((!isset($rep))or(!isset($amount))or(!isset($per))){errDie("Invalid use of function");}
		$date=date("Y-m-d");

		$Sl = "SELECT rep FROM coms WHERE date='$date' AND rep='$rep' AND div = '".USER_DIV."'";
		$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Sales People from database.");
		if(pg_numrows($Rs) > 0)
		{
			$Sl = "UPDATE coms SET amount=amount + '$amount' WHERE date='$date' AND rep='$rep' AND div = '".USER_DIV."'";
			$Rs = @db_exec ($Sl) or errDie ("Unable to update commision for salesman");
		}
		else
		{
			$Sl = "INSERT INTO coms(rep,amount,date, div) VALUES ('$rep','$amount','$date', '".USER_DIV."')";
			$Rs = @db_exec ($Sl) or errDie ("Unable to add commision for salesman");
		}

		return $amount;
	}

	# make a drop down from a database, selected or not
	function ext_dbsel($varname, $tab,$val,$dis,$err,$sel=""){

		$ExtSl = "SELECT ".$val.",".$dis." FROM ".$tab." WHERE div = '".USER_DIV."' ORDER BY ".$dis."";
		$ExtRs = db_exec($ExtSl) or errDie("<li>Invalid option for dropdown.");
		if(pg_numrows($ExtRs) < 1){errDie ("<li>$err.");}

		$select = "<select name=$varname>";
		while($tp = pg_fetch_array($ExtRs)){
			if($tp[$val]==$sel) {$sl="selected";} else {$sl="";}
			$select .= "<option $sl value='$tp[$val]'>$tp[$dis]</option>";
		}

		$select .= "</select>";

		return $select;
	}

	function ext_dateEntry($prif){
		$day = date("d");
		$vday = $prif."day";
		$mon = date("m");
		$vmon = $prif."mon";
		$year = date("Y");
		$vyear = $prif."year";

		return "<input type=text size=2 name=$vday maxlength=2  value='$day'>-<input type=text size=2 name=$vmon maxlength=2  value='$mon'>-<input type=text size=4 name=$vyear maxlength=4 value='$year'>";
	}

	function ext_ddateEntry($date, $prif){
		list($year, $mon, $day) = explode("-", $date);

		$vday = $prif."day";
		$vmon = $prif."mon";
		$vyear = $prif."year";

		return "<input type=text size=2 name=$vday maxlength=2  value='$day'>-<input type=text size=2 name=$vmon maxlength=2  value='$mon'>-<input type=text size=4 name=$vyear maxlength=4 value='$year'>";
	}

	function ext_rdateEntry($date, $prif){
		list($day, $mon, $year) = explode("-", $date);

		$vday = $prif."day";
		$vmon = $prif."mon";
		$vyear = $prif."year";

		return "<input type=text size=2 name=$vday maxlength=2  value='$day'>-<input type=text size=2 name=$vmon maxlength=2  value='$mon'>-<input type=text size=4 name=$vyear maxlength=4 value='$year'>";
	}



	function ext_chkdate($v, $day, $mon, $year){
		$rdate = $day."-".$mon."-".$year;
		if(!checkdate($mon, $day, $year)){
			$v->isOk ($rdate, "num", 1, 1, "Invalid job Date.");
		}
		return $rdate;
	}

	# make a drop down from a database, selected or not
	function ext_unddbsel($varname, $tab, $val, $dis, $err, $sel=""){

		$ExtSl = "SELECT ".$val.",".$dis." FROM ".$tab." ORDER BY ".$dis."";
		$ExtRs = @db_exec($ExtSl) or errDie("<li class=err> - Invalid option for dropdown.");
		if(pg_numrows($ExtRs) < 1){errDie ("<li class=err>  - $err.");}

		$select = "<select name=$varname>";
		while($tp = pg_fetch_array($ExtRs))
		{
			if($tp[$val]==$sel) {$sl="selected";} else {$sl="";}
			$select .= "<option $sl value='$tp[$val]'>$tp[$dis]</option>";
		}
		$select .= "</select>";

		return $select;
	}

	# get other values in same row in db
	function ext_dbget($tab, $field, $val, $dis1, $dis2 = ""){
		if (strlen($dis2)>0) {$Dis2 = ",".$dis2;} else {$Dis2 = "";}
		$ExtSl = "SELECT ".$dis1." ".$Dis2." FROM ".$tab." WHERE ".$field."='".$val."' AND div = '".USER_DIV."' LIMIT 1";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for display.");
		if(pg_numrows($ExtRs) < 1){return "<li>Not Found.";}
		else
		{
			$tp = pg_fetch_array($ExtRs);
			$Out=$tp[$dis1];
			if ($dis2!="") {$Out =$Out.", ".$tp[$dis1];}
		}
		return $Out;
	}

	/**
	* get other values in same row in db
	*
	* @ignore
	*/
	function barext_dbget($tab, $field, $val, $dis1, $dis2 = ""){
		if (strlen($dis2)>0) {$Dis2 = ",".$dis2;} else {$Dis2 = "";}
			$ExtSl = "SELECT ".$dis1." ".$Dis2." FROM ".$tab." WHERE ".$field."='".$val."' AND div = '".USER_DIV."' AND active = 'yes' LIMIT 1";
			$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for display.");
			if(pg_numrows($ExtRs) < 1){
				return "<li>Not Found.";
			}else{
				$tp = pg_fetch_array($ExtRs);
				$Out=$tp[$dis1];
				if ($dis2!="") {$Out =$Out.", ".$tp[$dis1];}
			}
			return $Out;
	}

	# make a drop down from an array
	function extlib_mksel($varname, $aray){
		$sel = "<select name=$varname>";
		foreach($aray as $key => $value){
			$sel .= "<option value='$key'>$value</option>";
		}
		$sel .= "</select>";
		return $sel;
	}

	# make a selected drop down from array
	function extlib_cpsel($varname, $aray, $filter){
		$seled = "<select name=$varname>";
		foreach($aray as $key => $value){
			if($key == $filter){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$seled .= "<option value='$key' $sel>$value</option>";
		}
		$seled .= "</select>";
		return $seled;
	}

	# make a selected drop down from array
	function extlib_rstr($data, $len){
		if(strlen($data) > $len){
			$len = ($len - 3);
			$data = substr($data, 0, $len);
			$data = $data."...";
		}
		return $data;
	}

	function extlib_ago($days){
		$daysAgo = date("Y-m-d",mktime (0,0,0,date("m")  ,date("d")-$days,date("Y")));
		return $daysAgo;
	}

	function extlib_mkprodarr($tab, $field, $val){
		$ret  = "";
		$sql = "SELECT stkid FROM $tab WHERE $field = '$val'";
		$rs = db_exec($sql);
		while($ids = pg_fetch_array($rs)){
			$stkRs = get("cubit", "stkcod", "stock", "stkid", $ids['stkid']);
			$stk = pg_fetch_array($stkRs);
			$ret .= "|$stk[stkcod]";
		}
		return $ret;
	}

	function extlib_mksprodarr($tab, $def, $field, $val){
		$ret  = "";
		$sql = "SELECT $def FROM $tab WHERE $field = '$val'";
		$rs = db_exec($sql);
		while($ids = pg_fetch_array($rs)){
			$ret .= "|$ids[$def]";
		}
		return $ret;
	}

	function extlib_mknprodarr($invid, $def){
		$ret  = "";
		$sql = "SELECT $def FROM nons_inv_items WHERE invid = '$invid'";
		$rs = db_exec($sql);
		while($ids = pg_fetch_array($rs)){
			$ret .= "|$ids[$def]";
		}
		return $ret;
	}

	function extlib_phparray($strarr){
		$ret = array();
		$arr = explode("|", $strarr);
		for($i = 1; $i < count($arr); $i++){
			$ret[] = $arr[$i];
		}
		return $ret;
	}
	/*----- Serials *********************************************************************************************************/

	function ext_arrayTrim($array){
		for($i = 0; $i < count($array); $i++){
			$array[$i] = rtrim($array[$i]);
		}
		return $array;
	}

	function ext_getserials($stkid){
		$ret = array();
		for($i = 0; $i < 10; $i++){
			$rs = undget("cubit", "*", "serial$i", "stkid", $stkid);
			while($ser = pg_fetch_array($rs)){
				$ret[] = $ser;
			}
		}
		sort($ret);
		return $ret;
	}

	function ext_getavserials($stkid){
		$ret = array();
		for($i = 0; $i < 10; $i++){
			$rs = undget2x("cubit", "*", "serial$i", "stkid", $stkid, "rsvd", "!=", "y");
			while($ser = pg_fetch_array($rs)){
				$ret[] = $ser;
			}
		}
		sort($ret);
		return $ret;
	}

	function ext_delserials($stkid){
		db_connect();
		for($i = 0; $i < 10; $i++){
			# Remove > insert (updating)
			$sql = "DELETE FROM serial$i WHERE stkid = '$stkid'";
			$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
		}
	}

	function ext_resvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "UPDATE serial$tab SET rsvd = 'y' WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
	}

	function ext_findSer($serno){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "SELECT * FROM serial$tab WHERE lower(serno) = lower('$serno')";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
		if(pg_numrows($rs) > 0){
			$ret = array();
			while($ser = pg_fetch_array($rs)){
				$ret[] = $ser;
			}
			return $ret;
		}else{
			return false;
		}
	}

	function ext_unresvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "UPDATE serial$tab SET rsvd = 'n' WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
	}

	function ext_unInvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "INSERT INTO serial$tab(serno, stkid, rsvd) VALUES('$serno', '$stkid', 'n')";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
	}

	function ext_invSer($serno, $stkid, $cusname = "", $invnum = 0){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "DELETE FROM serial$tab WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");

		if($invnum > 0){
			$sql = "INSERT INTO serialrec(serno, stkid, edate, cusname, invnum, div) VALUES('$serno', '$stkid', now(), '$cusname', '$invnum', '".USER_DIV."')";
			$rs = db_exec ($sql) or errDie ("Unable to retrieve serial number records in database.");
		}
	}

	function ext_chkSerial($serno, $stkid){
		db_connect();
		for($i = 0; $i < 10; $i++){
			if(ext_undex2("serial$i", "lower(serno)", "lower($serno)", "stkid", $stkid))
				return true;
		}
		return false;
	}

	function ext_isSerial($tab, $name, $filter){
		db_connect();
		$rs = undget("cubit", "serd", "$tab", "$name", $filter);
		$rec = pg_fetch_array($rs);
		if($rec['serd'] == 'yes' || $rec['serd'] == 'y')
			return true;
		else
			return false;
	}

	function ext_schkSerial($serno, $stkid){
		db_connect();
		for($i = 0; $i < 10; $i++){
			if(ext_undexx("serial$i", "serno", $serno, "stkid", "!=", $stkid))
				return true;
		}
		return false;
	}

	function ext_serStk($serno){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		$rs = undget("cubit", "stkid", "serial$tab", "serno", $serno);
		$ser = pg_fetch_array($rs);

		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$ser[stkid]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		$stk = pg_fetch_array ($stkRslt);

		return $stk;
	}

	function ext_remBlnk($array){
		$ret = array();
		foreach($array as $skey => $val){
			if(strlen($val) > 0)
				$ret[] = $val;
		}
		return $ret;
	}

	function ext_isUnique($array){
		$array = ext_arrayTrim($array);
		if(count($array) != count(array_unique ($array)))
			return false;
		else
			return true;
	}


	/*---- Costing centeres -----------------------------------------------------------------------------------------------*/

	// Cost centers tran type for accounts
	function cc_TranTypeAcc($dtaccid, $ctaccid)
	{
		$dtacc = getAcc($dtaccid);
		$ctacc = getAcc($ctaccid);

		if($dtacc['acctype'] == 'I' && $ctacc['acctype'] == 'I'){
			return false;
		}
		if($dtacc['acctype'] == 'E' && $ctacc['acctype'] == 'E'){
			return false;
		}
		if($dtacc['acctype'] == 'B' && $ctacc['acctype'] == 'B'){
			return false;
		}

		if($dtacc['acctype'] == 'E' && $ctacc['acctype'] == 'I'){
			return "dtct";
		}
		if($dtacc['acctype'] == 'E' && $ctacc['acctype'] == 'B'){
			return "ct";
		}

		if($dtacc['acctype'] == 'I' && $ctacc['acctype'] == 'E'){
			return "dtct";
		}
		if($dtacc['acctype'] == 'I' && $ctacc['acctype'] == 'B'){
			return "ct";
		}
		if($dtacc['acctype'] == 'B' && ($ctacc['acctype'] == 'I' || $ctacc['acctype'] == 'E')){
			return "dt";
		}
	}

	/*---- Inter -----------------------------------------------------------------------------------------------------------*/

	# Get refnum to use
	function getSymbol($fcid){
		db_connect();
		$sql = "SELECT * FROM currency WHERE fcid = '$fcid'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit",SELF);

		# Check if currency exists
		if(pg_numrows($rslt) < 1){
			errDie("<li class=err> ERROR : Invalid currency.");
		}

		$curr = pg_fetch_array($rslt);

		$curr['name'] = $curr['descrip'];
		return $curr;
	}

	# Get refnum to use
	function getRate($fcid){
		db_connect();
		$sql = "SELECT * FROM currency WHERE fcid = '$fcid'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit",SELF);

		# Check if currency exists
		if(pg_numrows($rslt) < 1){
			errDie("<li class=err> ERROR : Invalid currency.");
		}
		$curr = pg_fetch_array($rslt);

		return $curr['rate'];
	}

	function getDef_fcid(){
		db_connect();
		$sql = "SELECT * FROM currency WHERE def = 'y'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit",SELF);
		# if no default get first one found
		if(pg_numrows($rslt) < 1){
			$sql = "SELECT * FROM currency";
			$rslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit",SELF);
			if(pg_numrows($rslt) < 1)
				errDie("<li class=err> ERROR : There is no currency in Cubit, please add currency first.");
		}
		$curr = pg_fetch_array($rslt);
		return $curr['fcid'];
	}

	function xrate_change($fcid, $nrate){
		db_connect();
		$nrate = sprint($nrate);
		$sql = "UPDATE currency SET rate = '$nrate' WHERE fcid = '$fcid'";
		$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : change",SELF);
	}

	function xrate_update($fcid, $nrate, $tab, $key){
		db_connect();
		$sql = "SELECT $key, balance, fbalance, (balance/fbalance) as crate FROM $tab WHERE fbalance > 0 AND fcid = '$fcid'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

		while($rec = pg_fetch_array($rslt)){
			$nbal = sprint($rec['fbalance'] * $nrate);

			$sql = "UPDATE $tab SET balance = '$nbal' WHERE $key = '$rec[$key]'";
			$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : $tab",SELF);
		}
	}

	function acc_xrate_update($fcid, $nrate, $tab, $key, $accid){
		$bacc = getacc($accid);
		$placc = getAccn('999', '999');
		$date = date("d-m-Y");
		$refnum = getrefnum();

		db_connect();
		$sql = "SELECT $key, balance, fbalance, (balance/fbalance) as crate FROM $tab WHERE fbalance <> 0 AND fcid = '$fcid'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

		while($rec = pg_fetch_array($rslt)){
			$nbal = sprint($rec['fbalance'] * $nrate);

			db_connect();
			$sql = "UPDATE $tab SET balance = '$nbal' WHERE $key = '$rec[$key]'";
			$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : $tab",SELF);

			if($nbal > $rec['balance']){
				$diff = sprint($nbal - $rec['balance']);
				// Journal entry (? accounts)
				writetrans($bacc['accid'], $placc['accid'], $date, $refnum, $diff, "Exchange rate profit.");
			}else{
				$diff = sprint($rec['balance'] - $nbal);
				// Journal entry (? accounts)
				writetrans($placc['accid'], $bacc['accid'], $date, $refnum, $diff, "Exchange rate loss.");
			}
		}
	}

	function bank_xrate_update($fcid, $nrate){
		$placc = getAccn('999', '999');
		$date = date("d-m-Y");
		$refnum = getrefnum();

		db_connect();
		$sql = "SELECT bankid, balance, fbalance, (balance/fbalance) as crate FROM bankacct WHERE fcid = '$fcid' AND fbalance <> 0";
		$rslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

		while($rec = pg_fetch_array($rslt)){
			$baccid = getbankaccid($rec['bankid']);

			$nbal = sprint($rec['fbalance'] * $nrate);

			db_connect();
			$sql = "UPDATE bankacct SET balance = '$nbal' WHERE bankid = '$rec[bankid]'";
			$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : Bank accounts",SELF);

			if($nbal > $rec['balance']){
				$diff = sprint($nbal - $rec['balance']);
				// Journal entry (? accounts)
				writetrans($baccid, $placc['accid'], $date, $refnum, $diff, "Exchange rate profit.");
			}else{
				$diff = sprint($rec['balance'] - $nbal);
				// Journal entry (? accounts)
				writetrans($placc['accid'], $baccid, $date, $refnum, $diff, "Exchange rate loss.");
			}
		}
	}

	function cus_xrate_update($fcid, $nrate){
		$placc = getAccn('999', '999');
		$date = date("d-m-Y");
		$refnum = getrefnum();

		db_conn("exten");
		$sql = "SELECT deptid,debtacc FROM departments";
		$drslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

		while($dept = pg_fetch_array($drslt)){
			db_connect();
			$sql = "SELECT cusnum, balance, fbalance, (balance/fbalance) as crate FROM customers WHERE fbalance <> 0 AND fcid = '$fcid' AND deptid = '$dept[deptid]'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

			while($rec = pg_fetch_array($rslt)){
				$nbal = sprint($rec['fbalance'] * $nrate);

				db_connect();
				$sql = "UPDATE customers SET balance = '$nbal' WHERE cusnum = '$rec[cusnum]'";
				$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : $tab",SELF);

				if($nbal > $rec['balance']){
					$diff = sprint($nbal - $rec['balance']);
					// Journal entry (? accounts)
					writetrans($dept['debtacc'], $placc['accid'], $date, $refnum, $diff, "Exchange rate profit.");
				}else{
					$diff = sprint($rec['balance'] - $nbal);
					// Journal entry (? accounts)
					writetrans($placc['accid'], $dept['debtacc'], $date, $refnum, $diff, "Exchange rate loss.");
				}
			}
		}
	}

	function sup_xrate_update($fcid, $nrate){
		$placc = getAccn('999', '999');
		$date = date("d-m-Y");
		$refnum = getrefnum();

		db_conn("exten");
		$sql = "SELECT deptid,credacc FROM departments";
		$drslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

		while($dept = pg_fetch_array($drslt)){
			db_connect();
			$sql = "SELECT supid, balance, fbalance, (balance/fbalance) as crate FROM suppliers WHERE fbalance <> 0 AND fcid = '$fcid' AND deptid = '$dept[deptid]'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

			while($rec = pg_fetch_array($rslt)){
				$nbal = sprint($rec['fbalance'] * $nrate);

				db_connect();
				$sql = "UPDATE suppliers SET balance = '$nbal' WHERE supid = '$rec[supid]'";
				$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : $tab",SELF);

				if($nbal > $rec['balance']){
					$diff = sprint($nbal - $rec['balance']);
					// Journal entry (? accounts)
					writetrans($dept['credacc'], $placc['accid'], $date, $refnum, $diff, "Exchange rate loss.");
				}else{
					$diff = sprint($rec['balance'] - $nbal);
					// Journal entry (? accounts)
					writetrans($placc['accid'], $dept['credacc'], $date, $refnum, $diff, "Exchange rate profit.");
				}
			}
		}
	}

	function is_local($table, $key, $val){
		db_connect();
		return ext_ex2($table, $key, $val, "location", "loc");
	}

	function is_localb($table, $key, $val){
		db_connect();
		return ext_ex2($table, $key, $val, "btype", "loc");
	}

	/*---- End Inter -------------------------------------------------------------------------------------------------------*/
}
?>

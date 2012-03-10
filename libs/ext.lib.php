<?
/**
 * Generally used functions/constants, login logic also
 * @package Cubit
 * @subpackage Ext
 */
if (!defined("EXT_LIB")) {
	define("EXT_LIB", true);
	/**
	* generates a colour from red (0%) to yellow (50%) to green (100%)
	* based on a percentage value
	*
	* @param int $percentage the percentage you wish to get a colour for.
	* @return int returns a hexadecimal colour value
	*/
	function ext_progressColor($percentage)
	{
			$red = 255;
			$green = 0;
			$blue = 0;

			$amount = ($percentage / 100) * 510;

			// Increase green until the colour is yellow then start decreasing red,
			// until we've got green
			for ($i = 0; $i < $amount; $i++) {
					if ($green < 255) {
							$green++;
					} else {
							$red--;
					}
			}

			$red = str_pad(dechex($red), 2, "0", STR_PAD_LEFT);
			$green = str_pad(dechex($green), 2, "0", STR_PAD_LEFT);
			$blue = str_pad(dechex($blue), 2, "0", STR_PAD_LEFT);

			$color = '#'.$red.$green.$blue;
			return $color;
	}

	function cust_bank_id($cusnum)
	{
		if (!empty($cusnum)) {
			$sql = "SELECT bankid FROM cubit.customers WHERE cusnum='$cusnum'";
			$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
			$bankid = pg_fetch_result($cust_rslt, 0);
			
			if (empty($bankid) || $bankid == 0) {
				return getdSetting("BANK_DET");
			} else {
				return $bankid;
			}
		} else {
			return getdSetting("BANK_DET");
		}
	}

	function stock_is_blocked($stkid)
	{
		$sql = "SELECT blocked FROM cubit.stock WHERE stkid='$stkid'";
		$stock_rslt = db_exec($sql)
			or errDie("Unable to check if stock is blocked.");
		$blocked = pg_fetch_result($stock_rslt, 0);

		if ($blocked == "y" || $blocked == 1) {
			return 1;
		} else {
			return 0;
		}
	}

	function getFileSize($size)
	{
		define("KILOBYTE", 1024);
		define("MEGABYTE", KILOBYTE * 1024);
		define("GIGABYTE", MEGABYTE * 1024);

		if ($size < KILOBYTE) {
			return $size."B";
		} elseif ($size < MEGABYTE) {
			return sprint(($size / KILOBYTE))."K";
		} elseif ($size < GIGABYTE) {
			return sprint(($size / MEGABYTE))."M";
		} else {
			return sprint(($size / GIGABYTE))."G";
		}
	}

	/**
	 * returns the string "selected='t'" if condition is true, else ""
	 *
	 * @param $condition
	 * @return string
	 */
	function fsel($cond) {
		return $cond ? "selected='t'" : "";
	}

	/**
	 * returns the string "checked='t'" if condition is true, else ""
	 *
	 * @param $condition
	 * @return string
	 */
	function fcheck($cond) {
		return $cond ? "checked='t'" : "";
	}

	/**
	 * finds the specified file in the cubit directory
	 *
	 * @param string $file filename
	 * @param bool $isdir is a directory (optional)
	 * @return string
	 */
	function relpath($file, $isdir = false) {
		if ($isdir) {
			if (is_dir("$file")) return $file;
			else if (is_dir("../$file")) return "../$file";
			else if (is_dir("../../$file")) return "../../$file";
			else if (is_dir("../../../$file")) return "../../../$file";
			else return false;
		} else {
			if (is_file("$file")) return $file;
			else if (is_file("../$file")) return "../$file";
			else if (is_file("../../$file")) return "../../$file";
			else if (is_file("../../../$file")) return "../../../$file";
			else return false;
		}
	}

	/**
	 * redirects to selected script using html header.
	 *
	 * uses relpath() to find the script.
	 *
	 * @param string $script script name relative to root of cubit
	 */
	function redir($script) {
		header("Location: ".relpath($script)."");
		exit(0);
	}

	/**
	 * returns an html form to post a supplied array
	 *
	 * to specify an initial name for the array, specify it with $mdn. for example
	 * to export _POST and have the name _POST in all the name='' options, specify
	 * _POST with $mdn.
	 *
	 * @param array $ar array to convert
	 * @param string $mdn used internally by the function for multi dimensional arrays
	 * @param int $level level limiter so we dont enter infinite loops (like with $GLOBALS)
	 * @return string
	 */
	function array2form($ar, $mdn = false, $level = 0) {
		if ($level == 100) return "";
		$html = "";

		foreach ($ar as $n => $v) {
			if ($mdn !== false) {
				$n = "$mdn"."[$n]";
			}

			if (is_array($v) && $n != "GLOBALS") {
				$html .= array2form($v, $n, ++$level);
			} else {
				$html .= "<input type='hidden' name='$n' value='$v' />";
			}
		}

		return $html;
	}

	/**
	 * returns an xml form of a supplied array
	 *
	 * @param string $tagname base tag name
	 * @param string $par_varname parameter name for key names
	 * @param string $extra extra tag parameters
	 * @param array $ar array to convert
	 * @param string $mdn used internally by the function for multi dimensional arrays
	 * @param int $level level limiter so we dont enter infinite loops (like with $GLOBALS)
	 * @return string
	 */
	function array2xml($tagname, $par_varname, $extra, $ar, $mdn = false, $level = 0) {
		if ($level == 4) return "";
		$xml = "";

		foreach ($ar as $n => $v) {
			if ($mdn !== false) {
				$n = "$mdn"."[$n]";
			}

			// if we loop into globals, we are gonna go on for fucking ever without
			// a bloody purpose.
			if (is_array($v) && $n != "GLOBALS") {
				$xml .= array2xml($tagname, $par_varname, $extra, $v, $n, ++$level);
			} else {
				$type = gettype($v);
				if (is_resource($v)) {
					$e = get_resource_type($v);
				} else {
					$e = "";
				}
				$xml .= "\t<$tagname type=\"$type\" $e $par_varname=\"$n\" $extra>".xmldata($v)."</$tagname>\n";
			}
		}

		return $xml;
	}

	/**
     * posts a message to a user
     *
     * @param string $recipient
     * @param string $msg
     * @param bool
     */
    function msgSend($recipient, $msg) {
        $cols = grp(
            m("sender", defined("USER_NAME") ? USER_NAME : "Cubit"),
            m("recipient", $recipient),
            m("message", $msg),
            m("timesent", raw("CURRENT_TIMESTAMP")),
            m("viewed", "0")
        );

        $upd = new dbUpdate("req", "cubit", $cols);
        $upd->run(DB_INSERT);
    }

	/**
	 * returns an html GET request uri using a supplied array
	 *
	 * @param array $ar array to convert
	 * @param string $mdn used internally by the function for multi dimensional arrays
	 * @param int $level level limiter so we dont enter infinite loops (like with $GLOBALS)
	 * @return string
	 */
	function array2get($ar, $mdn = false, $level = 0) {
		if ($level == 100) return "";
		$html = array();
		foreach ($ar as $n => $v) {
			if ($mdn !== false) {
				$n = "$mdn"."[$n]";
			}

			if (is_array($v) && $n != "GLOBALS") {
				$html[] = array2get($v, $n, ++$level);
			} else {
				$html[] = "$n=$v";
			}
		}

		return implode("&", $html);
	}

	/**
	 * returns the selected item of an array created by exploding a string
	 *
	 * @ignore
	 */
	function explode_get($str, $exp, $k) {
		$arr = explode($exp, $str);
		return $arr[$k];
	}

	/**
	 * uses global counter to determine next background color
	 *
	 * @param bool $reset whether or not to reset the global counter back to zero
	 * @return string
	 */
	function bgcolorg($reset = false) {
		global $BGCOLOR_COUNTER;
		if ($reset) $BGCOLOR_COUNTER = 0;

		return ($BGCOLOR_COUNTER++ % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	}

	/**
	 * uses supplied variable to determine background color
	 *
	 * @param int $BGCOLOR_COUNTER counter supplied from outside
	 * @return string
	 */
	function bgcolorc($BGCOLOR_COUNTER) {
		return ($BGCOLOR_COUNTER % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	}

	/**
	 * uses and increases supplied variable to determine background color
	 *
	 * will increase the counter even in calling variable scope (passed by reference)
	 *
	 * @param int $BGCOLOR_COUNTER counter supplied from outside (increased in outside)
	 * @return string
	 */
	function bgcolor(&$BGCOLOR_COUNTER) {
		return ($BGCOLOR_COUNTER++ % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	}

	/**
	 * takes a dbquery (or inherited) object and builds a select list from it
	 *
	 * Notes:
	 * (1) with key and display make the format like #col where col is the column
	 * in the db, ex. #id:#name and #bankname (#bankacc). to specify a #
	 * just use ##.
	 *
	 * (2) if you have an option which when selected specifies any, pass the any
	 * parameter in the form "key:display value".
	 *
	 * (3) to group the options (with optgroup), add a column to the select
	 * named optgroup, containing the group. Also the query has to be sorted
	 * by this column before any other columns.
	 *
	 * @param string $obj dbquery object
	 * @param string $name form field name
	 * @param string $sel selected item key
	 * @param string $key form key for select items
	 * @param string $disp form display for select items
	 * @param string $anyopt in case of an "any" option specify this (dflt: false)
	 * @param string $opt extra options for the html element
	 * @return string html select box
	 */
	function db_mksel(dbQuery &$obj, $name, $sel, $key, $disp, $anyopt = false, $opt = "") {
		/* build the array of columsn for key and display */
		$c_key = array();
		$c_disp = array();

		$key = preg_replace("/##/", "\xFF", $key); // move all the ##
		if (!preg_match_all("/#([\\d\\w_]+)/", $key, $c_key)) {
			return false;
		}
		$key = preg_replace("/[\\xFF]/", "#", $key);

		$disp = preg_replace("/##/", "\xFF", $disp); // move all the ##
		if (!preg_match_all("/#([\\d\\w_]+)/", $disp, $c_disp)) {
			return false;
		}
		$disp = preg_replace("/[\\xFF]/", "#", $disp);

		$c_key = $c_key[1];
		$c_disp = $c_disp[1];

		if (preg_match("/id='[^']+'/", $opt)) {
			$id = "";
		} else {
			$id = "id='$name'";
		}

		$OUT = "<select name='$name' $id $opt>";

		/* an "any" element */
		if ($anyopt !== false && $anyopt != "") {
			$anyopt = explode(":", $anyopt);

			if (count($anyopt) > 1) {
				$OUT .= "<option value='$anyopt[0]'>$anyopt[1]</option>";
			}
		}

		$prev_optgroup = false;

		/* loop through the items */
		while ($r = $obj->fetch_array()) {
			/* do the grouping if an optgroup column or the optgroup changed */
			if (isset($r["optgroup"]) && $prev_optgroup != $r["optgroup"]) {
				if ($prev_optgroup !== false) {
					$OUT .= "</optgroup>";
				}

				$OUT .= "<optgroup label='$r[optgroup]'>";

				$prev_optgroup = $r["optgroup"];
			}

			$o_key = $key;
			foreach ($c_key as $col) {
				$o_key = preg_replace("/#$col/", $r[$col], $o_key);
			}

			$o_disp = $disp;
			foreach ($c_disp as $col) {
				$o_disp = preg_replace("/#$col/", $r[$col], $o_disp);
			}

			if ($o_key == $sel) {
				$s = "selected";
			} else {
				$s = "";
			}


			$OUT .= "<option $s value='$o_key'>$o_disp</option>";
		}

		if ($prev_optgroup !== false) {
			$OUT .= "</optgroup>";
		}

		$OUT .= "</select>";

		return $OUT;
	}

	/**
	 * makes an account selection drop down
	 *
	 * acctype can be one of ACCTYPE_ALL, ACCTYPE_B, ACCTYPE_E, ACCTYPE_I.
	 * false is the same as ACCTYPE_ALL (dflt).
	 *
	 * @param string $name field name
	 * @param int $accid selected account id
	 * @param int $acctype account types to list
	 * @param bool $grouped should accounts be grouped by type (optgroup)?
	 * @param string $opt extra form field options
	 */
	function mkAccSelect($name, $accid, $acctype = false, $grouped = true, $opt = false) {
		/* account types */
		if ($acctype !== false) {
			$acctypes = array();

			if ($acctype & ACCTYPE_B) {
				$acctypes[] = "acctype='B'";
			}

			if ($acctype & ACCTYPE_I) {
				$acctypes[] = "acctype='I'";
			}

			if ($acctype & ACCTYPE_E) {
				$acctypes[] = "acctype='E'";
			}

			if ($acctype & ACCTYPE_IE) {
				$acctypes[] = "acctype='I'";
				$acctypes[] = "acctype='E'";
			}

			$acctypes = implode(" OR ", $acctypes);
		} else {
			$acctypes = "(true)";
		}

		/* cols/grouping */
		if ($grouped) {
			$grouping = ",
			CASE
				WHEN acctype='B' THEN 'Balance'
				WHEN acctype='I' THEN 'Income'
				WHEN acctype='E' THEN 'Expense'
			END AS optgroup";
		} else {
			$grouping = "";
		}

		$sort_set = getCSetting ("ACCOUNT_SORT_ORDER");

		$order = FALSE;
		if (!isset ($sort_set) OR strlen ($sort_set) < 1 OR $sort_set == "number"){
			$order = TRUE;
			$sorting = "acctype, topacc, accnum";
		}else {
			$sorting = "acctype, accname, topacc, accnum";
		}
		/* query */
		$acc = new dbSelect("accounts", "core", grp(
			m("cols", "* $grouping"),
			m("where", $acctypes),
			m("order", "$sorting")
		));
		$acc->run();

		$O = "
		<select name='$name' $opt>";



		$p_grp = false;
		while ($r = $acc->fetch_array()) {
			if (isb($r["accid"])) {
				continue;
			}

			// new additional check added when all dropdowns were converted to this function.
			if(isDisabled($r['accid']))
				continue;

			if ($r["optgroup"] != $p_grp) {
				if ($p_grp !== false) {
					$O .= "</optgroup>";
				}

				$O .= "<optgroup label='$r[optgroup]'>";

				$p_grp = $r["optgroup"];
			}

			$sel = fsel($r["accid"] == $accid);

			if ($order){
				$O .= "<option $sel value='$r[accid]'>$r[topacc]/$r[accnum] - $r[accname]</option>";
			}else {
				$O .= "<option $sel value='$r[accid]'>$r[accname] - $r[topacc]/$r[accnum]</option>";
			}



		}

		$O .= "
		</optgroup>
		</select>";

		return $O;
	}

	/**
	 * queries the invoice/statement templates for script name
	 *
	 * @param string $tmpl template name
	 * @return string
	 */
	function templateScript($tmpl) {
		$qry = new dbSelect("template_settings", "cubit", grp(
		m("where", "template='$tmpl' AND div='".USER_DIV."'"),
		m("limit", 1)
		));
		$qry->run();

		if ($qry->num_rows() <= 0) {
			return false;
		} else {
			return $qry->fetch_result();
		}
	}

	/**
	 * returns table invoice is in (invoice type can be determined from this)
	 *
	 * @param int invid
	 * @return string
	 */
	function ext_invtbl_id($invid) {
		$invtbl = array(
			"cubit.invoices",
			"cubit.nons_invoices",
			"\"1\".pinvoices",
			"\"2\".pinvoices",
			"\"3\".pinvoices",
			"\"4\".pinvoices",
			"\"5\".pinvoices",
			"\"6\".pinvoices",
			"\"7\".pinvoices",
			"\"8\".pinvoices",
			"\"9\".pinvoices",
			"\"10\".pinvoices",
			"\"11\".pinvoices",
			"\"12\".pinvoices",
			"cubit.pinvoices"
		);

		foreach ($invtbl as $t) {
			if (ext_ex($t, "invid", $invid)) {
				return $t;
			}
		}
	}

	/**
	 * returns table invoice is in (invoice type can be determined from this)
	 *
	 * @param int invid
	 * @return string
	 */
	function ext_invtbl_num($invnum) {
		$invtbl = array(
			"cubit.invoices",
			"\"1\".invoices",
			"\"2\".invoices",
			"\"3\".invoices",
			"\"4\".invoices",
			"\"5\".invoices",
			"\"6\".invoices",
			"\"7\".invoices",
			"\"8\".invoices",
			"\"9\".invoices",
			"\"10\".invoices",
			"\"11\".invoices",
			"\"12\".invoices",
			"cubit.nons_invoices",
			"\"1\".pinvoices",
			"\"2\".pinvoices",
			"\"3\".pinvoices",
			"\"4\".pinvoices",
			"\"5\".pinvoices",
			"\"6\".pinvoices",
			"\"7\".pinvoices",
			"\"8\".pinvoices",
			"\"9\".pinvoices",
			"\"10\".pinvoices",
			"\"11\".pinvoices",
			"\"12\".pinvoices",
			"cubit.pinvoices"
		);

		foreach ($invtbl as $t) {
			if (ext_ex($t, "invnum", $invnum)) {
				return $t;
			}
		}
	}

	/***********************************************************************
	* UNSORTED FOLLOWING
	* UNSORTED FOLLOWING
	* UNSORTED FOLLOWING
	* UNSORTED FOLLOWING
	* UNSORTED FOLLOWING
	***********************************************************************/
	/**
	 * @ignore
	 */
	function isSetting($label){
		$label = strtoupper($label);
		$setRs = undget("cubit", "label", "set", "upper(label)", $label);
		if(pg_numrows($setRs) > 0){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @ignore
	 */
	function getSetting($label){
		$label = strtoupper($label);
		$setRs = undget("cubit", "value", "set", "upper(label)", $label);
		if(pg_numrows($setRs) > 0){
			$set = pg_fetch_array($setRs);
			return $set['value'];
		}else{
			return false;
		}
	}

	/**
	 * @ignore
	 */
	function getCSetting($label){
		$label = strtoupper($label);
		$setRs = undget("cubit", "value", "settings", "constant", $label);
		if(pg_numrows($setRs) > 0){
			$set = pg_fetch_array($setRs);
			return $set['value'];
		}else{
			return false;
		}
	}

	function setCSetting($label, $val) {
		$cols = grp(
		m("value", $val)
		);

		$wh = wgrp(
		m("constant", $label)
		);

		$qry = new dbUpdate("settings", "cubit", $cols, $wh);
		$qry->run(DB_UPDATE);
	}

	/**
	 * @ignore
	 */
	function getdSetting($label){
		$label = strtoupper($label);
		$setRs = get("cubit", "value", "set", "upper(label)", $label);
		if(pg_num_rows($setRs) > 0){
			$set = pg_fetch_array($setRs);
			return $set['value'];
		}else{
			return "";
		}
	}

	/**
	 * @ignore
	 */
	function get($database,$field,$table,$filtername, $filter,$order=""){
		db_conn("cubit");
		$sql = "SELECT ".$field." FROM \"$database\".".$table." WHERE  div = '".USER_DIV."' AND ".$filtername."='".$filter."' $order";
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
	function nget($database,$field,$table,$filtername, $filter){
		$sql = "SELECT ".$field." FROM ".$table." WHERE  div = '".USER_DIV."' AND ".$filtername."=".$filter."";
		db_conn($database) or errDie ("Unable to connect to database server.", SELF);
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
	function unget($database,$field,$table,$filtername, $filter){
		$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."'";
		db_conn($database) or errDie ("Unable to connect to database server.", SELF);
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
	function undget($database,$field,$table,$filtername, $filter){
		$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."'";
		db_conn($database) or errDie ("Unable to connect to database server.", SELF);
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
	function undget2($database,$field,$table,$filtername, $filter, $filtername2, $filter2){
		$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."' AND ".$filtername."='".$filter."'";
		db_conn($database) or errDie ("Unable to connect to database server.", SELF);
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
	function undget2x($database,$field,$table,$filtername, $filter, $filtername2, $op2 ,$filter2){
		$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."' AND ".$filtername2."$op2'".$filter2."'";
		db_conn($database) or errDie ("Unable to connect to database server.", SELF);
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
	function get2x($database,$field,$table,$filtername, $filter, $filtername2, $op2 ,$filter2){
		$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."' AND ".$filtername2."$op2'".$filter2."' AND div = '".USER_DIV."'";
		db_conn($database) or errDie ("Unable to connect to database server.", SELF);
		$rslt = db_exec($sql);
		return $rslt;
	}

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function com_invoice ($rep,$amount,$com,$inv,$date = false,$nocalc = false){
		db_conn('exten');
		$Sl="SELECT * FROM salespeople WHERE salesp='$rep'";
		$Ri=db_exec($Sl);

		$data=pg_fetch_array($Ri);

		if ($nocalc === false) {
			if($data['com']>0) {
				$com = sprint($amount*$data['com']/100);
			}else {
				$com = sprint($amount*($com/100));
			}
		}

		if($com!=0) {
			db_conn('cubit');
			if ($date === false) {
				$date = date("Y-m-d");
			}
			$Sl="INSERT INTO coms_invoices (rep,invdate,inv,amount,com)
				VALUES('$rep','$date','$inv','$amount','$com')";
			$Rx=db_exec($Sl);
		}
	}

	/**
	 * reformat date
	 *
	 * @ignore
	 */
	function ext_rdate($date){
		$date  = explode("-", $date);
		$date = "$date[2]-$date[1]-$date[0]";
		return $date;
	}

	/**
	 * @ignore
	 */
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

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @param string $tab table
	 * @param string $field column to match
	 * @param string $val value column should have
	 * @param string $schema [optional] a schema
	 */
	function ext_ex($tab,$field,$val,$schema = false){
		if ($schema === false) {
			$schema = "";
		} else {
			$schema = "\"$schema\".";
		}
		$ExtSl = "SELECT 1 FROM $schema$tab WHERE ".$field."='".$val."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if (pg_numrows($ExtRs) > 0) {
			return true;
		} else {
			return false;
		}
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

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @ignore
	 */
	function ext_undex($tab,$field,$val){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}
		else {return FALSE;}
	}

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @ignore
	 */
	function ext_ex2($tab, $field, $val, $field2, $val2){

		$ExtSl = "SELECT ".$val." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."='".$val2."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	/**
	 * @ignore
	 */
	function ext_undex2($tab, $field, $val, $field2, $val2){

		$ExtSl = "SELECT ".$val." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."='".$val2."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @ignore
	 */
	function ext_exx($tab, $field, $val, $field2, $op, $val2){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."$op'".$val2."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @ignore
	 */
	function ext_undexx($tab, $field, $val, $field2, $op, $val2){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."$op'".$val2."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @ignore
	 */
	function ext_exx3($tab, $field, $val, $field1, $val1, $field2, $op, $val2){

		$ExtSl = "SELECT ".$field." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field1."='".$val1."' AND ".$field2."$op'".$val2."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	/**
	 * check whether a certain value already exits in a table
	 *
	 * @ignore
	 */
	function ext_ex3($tab, $field, $val, $field2, $val2, $field3, $val3){

		$ExtSl = "SELECT ".$val." FROM ".$tab." WHERE ".$field."='".$val."' AND ".$field2."='".$val2."' AND ".$field3."='".$val3."' AND div = '".USER_DIV."'";
		$ExtRs = @db_exec($ExtSl) or errDie("<li>Invalid option for check.");
		if(pg_numrows($ExtRs) > 0){
			return TRUE;
		}else {
			return FALSE;
		}
	}

	/**
	 * writes a commision record for salesrep
	 *
	 * @ignore
	 */
	function coms($rep,$amount,$per,$credit="",$date = false)
	{
		$per += 0;

		//if ($per > 0) {
		$amount = round(($amount*$per/100),2);
		//} else {
		//	$amount = $amount;
		//}

		$amount += 0;

		if ($credit!="") {
			$amount = $amount*-1;
		}

		if ($date === false) {
			$date=date("Y-m-d");
		}

		$Sl = "SELECT rep FROM cubit.coms WHERE date='$date' AND rep='$rep' AND div = '".USER_DIV."'";
		$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Sales People from database.");
		if(pg_numrows($Rs) > 0) {
			$Sl = "UPDATE cubit.coms SET amount=amount + '$amount' WHERE date='$date' AND rep='$rep' AND div = '".USER_DIV."'";
			$Rs = @db_exec ($Sl) or errDie ("Unable to update commision for salesman");
		} else {
			$Sl = "INSERT INTO cubit.coms(rep,amount,date, div) VALUES ('$rep','$amount','$date', '".USER_DIV."')";
			$Rs = @db_exec ($Sl) or errDie ("Unable to add commision for salesman");
		}

		return $amount;
	}

	/**
	 * make a drop down from a database, selected or not
	 *
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function ext_dateEntry($prif){
		$day = date("d");
		$vday = $prif."day";
		$mon = date("m");
		$vmon = $prif."mon";
		$year = date("Y");
		$vyear = $prif."year";

		return "<input type=text size=2 name=$vday maxlength=2  value='$day'>-<input type=text size=2 name=$vmon maxlength=2  value='$mon'>-<input type=text size=4 name=$vyear maxlength=4 value='$year'>";
	}

	/**
	 * @ignore
	 */
	function ext_ddateEntry($date, $prif){
		list($year, $mon, $day) = explode("-", $date);

		$vday = $prif."day";
		$vmon = $prif."mon";
		$vyear = $prif."year";

		return "<input type=text size=2 name=$vday maxlength=2  value='$day'>-<input type=text size=2 name=$vmon maxlength=2  value='$mon'>-<input type=text size=4 name=$vyear maxlength=4 value='$year'>";
	}

	/**
	 * @ignore
	 */
	function ext_rdateEntry($date, $prif){
		list($day, $mon, $year) = explode("-", $date);

		$vday = $prif."day";
		$vmon = $prif."mon";
		$vyear = $prif."year";

		return "<input type=text size=2 name=$vday maxlength=2  value='$day'>-<input type=text size=2 name=$vmon maxlength=2  value='$mon'>-<input type=text size=4 name=$vyear maxlength=4 value='$year'>";
	}

	/**
	 * @ignore
	 */
	function ext_chkdate($v, $day, $mon, $year){
		$rdate = $day."-".$mon."-".$year;
		if(!checkdate($mon, $day, $year)){
			$v->isOk ($rdate, "num", 1, 1, "Invalid job Date.");
		}
		return $rdate;
	}

	/**
	 * make a drop down from a database, selected or not
	 *
	 * @ignore
	 */
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

	/**
	 * get other values in same row in db
	 *
	 * @ignore
	 */
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
	function serext_dbget($tab, $field, $val, $dis1, $dis2 = ""){
		if (strlen($dis2)>0) {$Dis2 = ",".$dis2;} else {$Dis2 = "";}
		$active = "AND rsvd='n'";
		$ExtSl = "SELECT ".$dis1." ".$Dis2." FROM ".$tab." WHERE ".$field."='".$val."' $active LIMIT 1";
		$ExtRs = db_exec($ExtSl) or errDie("<li>Invalid option for display.");
		if(pg_numrows($ExtRs) < 1){
			return 0;
		}else{
			$tp = pg_fetch_array($ExtRs);
			$Out=$tp[$dis1];
			if ($dis2!="") {$Out =$Out.", ".$tp[$dis1];}
		}
		return $Out;
	}

	/**
	* @ignore
	*/
	function serext_dbnum($tab, $field, $val, $dis1, $dis2 = ""){
		if (strlen($dis2)>0) {$Dis2 = ",".$dis2;} else {$Dis2 = "";}
		$active = "AND rsvd='n'";
		$ExtSl = "SELECT ".$dis1." ".$Dis2." FROM ".$tab." WHERE ".$field."='".$val."' $active LIMIT 1";
		$ExtRs = db_exec($ExtSl) or errDie("<li>Invalid option for display.");
		return pg_numrows($ExtRs);
	}

	/**
	* @ignore
	*/
	function barext_dbnum($tab, $field, $val, $dis1, $dis2 = ""){
		if (strlen($dis2)>0) {$Dis2 = ",".$dis2;} else {$Dis2 = "";}
		if ($tab == "stock") {
			$active = "";
		} else {
			$active = "AND active = 'yes'";
		}
		$ExtSl = "SELECT ".$dis1." ".$Dis2." FROM ".$tab." WHERE ".$field."='".$val."' AND div = '".USER_DIV."' $active LIMIT 1";
		$ExtRs = db_exec($ExtSl) or errDie("<li>Invalid option for display.");
		return pg_numrows($ExtRs);
	}

	/**
	* @ignore
	*/
	function barext_dbget($tab, $field, $val, $dis1, $dis2 = ""){
		if (strlen($dis2)>0) {$Dis2 = ",".$dis2;} else {$Dis2 = "";}
		if ($tab == "stock") {
			$active = "";
		} else {
			$active = "AND active = 'yes'";
		}
		$ExtSl = "SELECT ".$dis1." ".$Dis2." FROM ".$tab." WHERE ".$field."='".$val."' AND div = '".USER_DIV."' $active LIMIT 1";
		$ExtRs = db_exec($ExtSl) or errDie("<li>Invalid option for display.");
		if(pg_numrows($ExtRs) < 1){
			return 0;
		}else{
			$tp = pg_fetch_array($ExtRs);
			$Out=$tp[$dis1];
			if ($dis2!="") {$Out =$Out.", ".$tp[$dis1];}
		}
		return $Out;
	}

	# make a drop down from an array
	/**
	 * @ignore
	 */
	function extlib_mksel($varname, $aray, $filter=0){
		return extlib_cpsel($varname, $aray, $filter);
	}

	/**
	 * generates a month selection list with year after month
	 *
	 * @param string $name name of html form field
	 * @param int $curr selected month number
	 * @param bool $noyear don't display year
	 * @param bool $py whether or not we are generating for the previous year
	 * @return string
	 */
	function finMonList($name, $curr="", $noyear = false, $py = false) {
		global $PRDMON, $MONPRD;

		if ($py) {
			$py = getFinYear() - (int)substr($py, 1);
		} else {
			$py = 0;
		}

		$month_to_sel = "<select name='$name'>";
		for ($i = 1; $i <= 12; $i++) {
			$mon = $PRDMON[$i];

			$fyear = getYearOfFinMon($mon) - $py;

			if ($curr == $mon) {
				$selected = "selected";
			} else {
				$selected = "";
			}

			$month_to_sel .= "<option value='$mon' $selected>".date("F", mktime(0,0,0,$mon,1,2000)).(!$noyear?" $fyear":"")."</option>";
		}
		$month_to_sel .= "</select>";

		return $month_to_sel;
	}

	/**
	 * account category (ALL accounts)
	 */
	define("ACC_ALL", false);

	/**
	 * account category expenditure
	 */
	define("ACC_EXPENSE", "E");

	/**
	 * account category expenditure
	 */
	define("ACC_INCOME", "I");

	/**
	 * account category expenditure
	 */
	define("ACC_BALANCE", "B");

	/**
	 * creates a list of accounts of selected category
	 *
	 * account category constants: ACC_EXPENSE, ACC_INCOME, ACC_BALANCE, ACC_ALL
	 *
	 * more than one account category can be chosen by appending them in
	 * the same ways as appending strings
	 *
	 * alternatively all accounts can be chosen by leaving parameter out or
	 * passing ACC_ALL
	 *
	 * @param $name string name of html field
	 * @param $acc_cat string account category
	 * @param $accid int selected account id
	 * @return string
	 */
	function finAccList($name, $acc_cat = false, $accid = "") {
		if ($acc_cat === false || empty($acc_cat)) {
			$acc_cat = ACC_EXPENSE.ACC_INCOME.ACC_BALANCE;
		}

		$ls = array();
		for ($i = 0; $i < strlen($acc_cat); ++$i) {
			$ls[] = "acctype='$acc_cat[$i]'";
		}

		$ls = implode(" OR ", $ls);

		$acc = new dbSelect("accounts", "core", array(
		"cols" => "accid, topacc, accnum, accname, acctype",
		"where" => $ls,
		"order" => "acctype, accname, topacc, accnum"
		));
		$acc->run();

		$acclist = "
		<select name='$name'>";

		$prevcat = false;
		while ($accinfo = $acc->fetch_array()) {
			if ($prevcat != $accinfo["acctype"]) {
				$prevcat = $accinfo["acctype"];

				switch ($accinfo["acctype"]) {
					case "I":
						$at = "Income";
						break;
					case "E":
						$at = "Expense";
						break;
					case "B":
						$at = "Balance";
						break;
				}

				$acclist .= "
					</optgroup>
					<optgroup label='$at'>";
			}

			if ($accinfo["accid"] == $accid) {
				$sel = "selected='t'";
			} else {
				$sel = "";
			}

			$acclist .= "
				<option $sel value='$accinfo[accid]'>
					$accinfo[topacc]/$accinfo[accnum] $accinfo[accname]
				</option>";
		}

		$acclist .= "
			</optgroup>
		</select>";

		return $acclist;
	}

	# make a selected drop down from array
	/**
	 * @ignore
	 */
	function extlib_cpsel($varname, $aray, $filter, $opt=""){
		$seled = "<select name='$varname' id='$varname' $opt>";
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

	# make a selected drop down from array with a all feature
	/**
	 * @ignore
	 */
	function extlib_cpsel_all($varname, $aray, $filter, $all="", $opt=""){
		$seled = "<select name='$varname' id='$varname' $opt>";
		$seled .= "<option value='0'>All $all</option>";
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
	/**
	 * @ignore
	 */
	function extlib_rstr($data, $len){
		//if(strlen($data) > $len){
		//	$len = ($len - 3);
		//	$data = substr($data, 0, $len);
		//	$data = $data."...";
		//}
		return $data;
	}

	/**
	 * @ignore
	 */
	function extlib_ago($days){
		$daysAgo = date("Y-m-d",mktime (0,0,0,date("m")  ,date("d")-$days,date("Y")));
		return $daysAgo;
	}

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function extlib_mksprodarr($tab, $def, $field, $val){
		$ret  = "";
		$sql = "SELECT $def FROM $tab WHERE $field = '$val'";
		$rs = db_exec($sql);
		while($ids = pg_fetch_array($rs)){
			$ret .= "|$ids[$def]";
		}
		return $ret;
	}

	/**
	 * @ignore
	 */
	function extlib_mknprodarr($invid, $def){
		$ret  = "";
		$sql = "SELECT $def FROM nons_inv_items WHERE invid = '$invid'";
		$rs = db_exec($sql);
		while($ids = pg_fetch_array($rs)){
			$ret .= "|$ids[$def]";
		}
		return $ret;
	}

	/**
	 * @ignore
	 */
	function extlib_phparray($strarr){
		$ret = array();
		$arr = explode("|", $strarr);
		for($i = 1; $i < count($arr); $i++){
			$ret[] = $arr[$i];
		}
		return $ret;
	}
	/*----- Serials *********************************************************************************************************/

	/**
	 * @ignore
	 */
	function ext_arrayTrim($array){
		for($i = 0; $i < count($array); $i++){
			$array[$i] = rtrim($array[$i]);
		}
		return $array;
	}

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function ext_getPurSerials($purnum){
		$ret = array();
		$rs = get("cubit", "*", "pserec", "purnum", $purnum);
		while($ser = pg_fetch_array($rs)){
			$ret[] = $ser;
		}
		sort($ret);
		return $ret;
	}

	/**
	 * @ignore
	 */
	function ext_getPurSerStk($purnum, $stkid){
		$ret = array();
		$rs = get2x("cubit", "*", "pserec", "purnum", $purnum, "stkid", "=", $stkid);
		while($ser = pg_fetch_array($rs)){
			$ret[] = $ser;
		}
		sort($ret);
		return $ret;
	}

	/**
	 * @ignore
	 */
	function ext_delserials($stkid){
		db_connect();
		for($i = 0; $i < 10; $i++){
			# Remove > insert (updating)
			$sql = "DELETE FROM serial$i WHERE stkid = '$stkid'";
			$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
		}
	}

	/**
	 * @ignore
	 */
	function ext_resvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "UPDATE serial$tab SET rsvd = 'y' WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
	}

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function ext_findAvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "SELECT * FROM serial$tab WHERE lower(serno) = lower('$serno') AND stkid = '$stkid' AND rsvd != 'y'";
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

	/**
	 * @ignore
	 */
	function ext_unresvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "UPDATE serial$tab SET rsvd = 'n' WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
	}

	/**
	 * @ignore
	 */
	function ext_unInvSer($serno, $stkid){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "INSERT INTO serial$tab(serno, stkid, rsvd) VALUES('$serno', '$stkid', 'n')";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");
	}

	/**
	 * @ignore
	 */
	function ext_invSer($serno, $stkid, $cusname = "", $invnum = 0, $tdate="now"){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "DELETE FROM serial$tab WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");

		if($invnum > 0){
			$sql = "INSERT INTO serialrec(serno, stkid, edate, cusname, invnum, typ, tdate, div) VALUES('$serno', '$stkid', now(), '$cusname', '$invnum', 'inv', '$tdate', '".USER_DIV."')";
			$rs = db_exec ($sql) or errDie ("Unable to retrieve serial number records in database.");
		}
	}

	/**
	 * @ignore
	 */
	function ext_InSer($serno, $stkid, $cusname = "", $invnum = 0, $type, $tdate="now"){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "INSERT INTO serial$tab(serno, stkid, rsvd) VALUES('$serno', '$stkid', 'n')";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");

		$sql = "INSERT INTO serialrec(serno, stkid, edate, cusname, invnum, typ, tdate, div) VALUES('$serno', '$stkid', now(), '$cusname', '$invnum', '$type', '$tdate', '".USER_DIV."')";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial number records in database.");
	}

	/**
	 * @ignore
	 */
	function ext_OutSer($serno, $stkid, $cusname = "", $invnum = 0, $type){
		$TABS = array("1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", '8' => '8', "9" => "9", "0" => "0", 'a' => '0', "b" => '0', "c" => '0', 'd' => '1', "e" => '1', "f" => '1', 'g' => '2', "h" => '2', "i" => '2', 'j' => '3', "k" => '3', "l" => '3', "m" => '4', "n" => '4', "o" => '4', "p" => '5', "q" => '5', "r" => '5', "s" => '6', "t" => '6', "u" => '6', "v" => '7', "w" => '7', "x" => '7', "y" => '8', "z" => '8');
		$tab = $TABS[strtolower($serno[0])];

		db_connect();
		# updating
		$sql = "DELETE FROM serial$tab WHERE serno = '$serno' AND stkid = '$stkid'";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial numbers in database.");

		$sql = "INSERT INTO serialrec(serno, stkid, edate, cusname, invnum, typ, div) VALUES('$serno', '$stkid', now(), '$cusname', '$invnum', '$type', '".USER_DIV."')";
		$rs = db_exec ($sql) or errDie ("Unable to retrieve serial number records in database.");
	}

	/**
	 * @ignore
	 */
	function ext_chkSerial($serno, $stkid){
		db_connect();
		for($i = 0; $i < 10; $i++){
			if(ext_undex2("serial$i", "lower(serno)", "lower($serno)", "stkid", $stkid))
			return true;
		}
		return false;
	}

	/**
	 * @ignore
	 */
	function ext_isSerial($tab, $name, $filter){
		db_connect();
		$rs = undget("cubit", "serd", "$tab", "$name", $filter);
		$rec = pg_fetch_array($rs);
		if($rec['serd'] == 'yes' || $rec['serd'] == 'y')
		return true;
		else
		return false;
	}

	/**
	 * @ignore
	 */
	function ext_schkSerial($serno, $stkid){
		db_connect();
		for($i = 0; $i < 10; $i++){
			if(ext_undexx("serial$i", "serno", $serno, "stkid", "!=", $stkid))
			return true;
		}
		return false;
	}

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function ext_remBlnk($array){
		$ret = array();
		foreach($array as $skey => $val){
			if(strlen($val) > 0)
			$ret[] = $val;
		}
		return $ret;
	}

	/**
	 * @ignore
	 */
	function ext_isUnique($array){
		$array = ext_arrayTrim($array);
		if(count($array) != count(array_unique ($array)))
		return false;
		else
		return true;
	}

	/*---- Costing centeres -----------------------------------------------------------------------------------------------*/

	// Cost centers tran type for accounts
	/**
	 * @ignore
	 */
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
	/**
	 * @ignore
	 */
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
	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function xrate_change($fcid, $nrate){
		db_connect();
		$nrate = sprint($nrate);
		$sql = "UPDATE currency SET rate = '$nrate' WHERE fcid = '$fcid'";
		$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : change",SELF);
	}

	/**
	 * returns the currency rate by code
	 *
	 * @param int $cc currency code
	 */
	function xrate_get($cc) {
		$c = new dbSelect("currency", "cubit", grp(
		m("cols", "rate"),
		m("where", "curcode='$cc'")
		));
		$c->run();

		if ($c->num_rows() <= 0) {
			return 0;
		} else {
			return $c->fetch_result();
		}
	}

	/**
	 * @ignore
	 */
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

	/**
	 * @ignore
	 */
	function acc_xrate_update($fcid, $nrate, $tab, $key, $accid){
		$bacc = getacc($accid);
		$placc = getAccn('999', '999');
		db_conn('core');

		$Sl="SELECT * FROM accounts WHERE accname='Exchange Rate Profit/Loss'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			print "There is no account for Exchange Rate Profit/Loss, please create an account called 'Exchange Rate Profit/Loss'";
			exit;
		}

		$placc=pg_fetch_array($Ri);
		//$placc=$ad['accid'];

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

	/**
	 * @ignore
	 */
	function bank_xrate_update($fcid, $nrate){
		$placc = getAccn('999', '999');
		db_conn('core');

		$Sl="SELECT * FROM accounts WHERE accname='Exchange Rate Profit/Loss'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			print "There is no account for Exchange Rate Profit/Loss, please create an account called 'Exchange Rate Profit/Loss'";
			exit;
		}

		$placc=pg_fetch_array($Ri);
		//$placc=$ad['accid'];

		$date = date("d-m-Y");
		$refnum = getrefnum();

		db_connect();
		$sql = "SELECT bankid, balance, fbalance, (balance/fbalance) as crate FROM bankacct WHERE fcid = '$fcid' AND fbalance <> 0 AND btype!='loc'";
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

	/**
	 * @ignore
	 */
	function cus_xrate_update($fcid, $nrate){
		$placc = getAccn('999', '999');
		db_conn('core');

		$Sl="SELECT * FROM accounts WHERE accname='Exchange Rate Profit/Loss'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			print "There is no account for Exchange Rate Profit/Loss, please create an account called 'Exchange Rate Profit/Loss'";
			exit;
		}

		$placc=pg_fetch_array($Ri);
		//$placc=$ad['accid'];

		$date = date("d-m-Y");
		$sdate = date("Y-m-d");
		$refnum = getrefnum();

		db_conn("exten");
		$sql = "SELECT deptid,debtacc FROM departments";
		$drslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

		while($dept = pg_fetch_array($drslt)){
			db_connect();
			$sql = "SELECT cusnum, balance, fbalance, (balance/fbalance) as crate FROM customers WHERE fbalance <> 0 AND fcid = '$fcid' AND deptid = '$dept[deptid]'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve balances from Cubit",SELF);

			while($rec = pg_fetch_array($rslt)){
				// $nbal = ($rec['fbalance'] * $nrate);
				// print "$nbal > $rec[balance] : $rec[fbalance]<br>";;

				$nbal = sprint($rec['fbalance'] * $nrate);

				db_connect();
				$sql = "UPDATE customers SET balance = '$nbal' WHERE cusnum = '$rec[cusnum]'";
				$uRs = db_exec($sql) or errDie("Unable to retrieve update exchange rate : $tab",SELF);

				if($nbal > $rec['balance']){
					$diff = sprint($nbal - $rec['balance']);
					// Journal entry (? accounts)
					writetrans($dept['debtacc'], $placc['accid'], $date, $refnum, $diff, "Exchange rate profit.");
					# Make ledge record
					custledger($rec['cusnum'], $placc['accid'], $sdate, 0, "Exchange rate profit.", $diff, "d");
				}elseif($nbal < $rec['balance']){
					$diff = sprint($rec['balance'] - $nbal);
					// Journal entry (? accounts)
					writetrans($placc['accid'], $dept['debtacc'], $date, $refnum, $diff, "Exchange rate loss.");
					# Make ledge record
					custledger($rec['cusnum'], $placc['accid'], $sdate, 0, "Exchange rate loss.", $diff, "c");
				}
			}
		}
	}

	/**
	 * @ignore
	 */
	function sup_xrate_update($fcid, $nrate){
		$placc = getAccn('999', '999');

		db_conn('core');

		$Sl="SELECT * FROM accounts WHERE accname='Exchange Rate Profit/Loss'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			print "There is no account for Exchange Rate Profit/Loss, please create an account called 'Exchange Rate Profit/Loss'";
			exit;
		}

		$placc=pg_fetch_array($Ri);
		//$placc=$ad['accid'];

		$date = date("d-m-Y");
		$sdate = date("Y-m-d");
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
					writetrans($placc['accid'], $dept['credacc'], $date, $refnum, $diff, "Exchange rate loss.");
					# Make ledge record
					suppledger($rec['supid'], $placc['accid'], $sdate, 0, "Exchange rate loss.", $diff, 'c');
				}elseif($nbal < $rec['balance']){
					$diff = sprint($rec['balance'] - $nbal);
					// Journal entry (? accounts)
					writetrans($dept['credacc'], $placc['accid'], $date, $refnum, $diff, "Exchange rate profit.");
					# Make ledge record
					suppledger($rec['supid'], $placc['accid'], $sdate, 0, "Exchange rate profit.", $diff, 'd');
				}
			}
		}
	}

	/**
	 * @ignore
	 */
	function is_local($table, $key, $val){
		db_connect();
		return ext_ex2($table, $key, $val, "location", "loc");
	}

	/**
	 * @ignore
	 */
	function is_localb($table, $key, $val){
		db_connect();
		return ext_ex2($table, $key, $val, "btype", "loc");
	}
	
	function get_excl_stock ($stkid){
		
		$stkid += 0;

		if ($stkid == 0) return false;

		db_connect ();
		$get_stk = "SELECT selamt,vatcode FROM cubit.stock WHERE stkid = '$stkid'";
		$run_stk = db_exec($get_stk) or errDie ("Unable to get stock information.");
		if (pg_numrows($run_stk) < 1){
			return false;
		}else {
			$arr = pg_fetch_array ($run_stk);

			$get_stkvat = "SELECT vat_amount FROM vatcodes WHERE id = '$arr[vatcode]' LIMIT 1";
			$run_stkvat = db_exec($get_stkvat) or errDie ("Unable to get stock vat amount.");
			if (pg_numrows($run_stkvat) < 1)
				$vatp = 0;
			else 
				$vatp = pg_fetch_result ($run_stkvat,0,0);

			$amt = $arr['selamt'] * 100 / (100 + $vatp);
		}
			
		return sprint ($amt);
	}

	/*---- End Inter -------------------------------------------------------------------------------------------------------*/
} /* LIB END */
?>

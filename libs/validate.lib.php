<?
if (!defined("VALIDATE_LIB")) {
	define("VALIDATE_LIB", true);

if (class_exists ("validate")) {
	return 0;
}

class validate {
	var $_errors;

	function validate () {
		$this->resetErrors();
	}

	function isOk ($value, $datatype, $min, $max, $msg) {
		if (!is_scalar($value)) {
			die("Can not operate on non-scalar. $msg");
		}

		$invert_match = true;
		$invalidated = false;
		switch ($datatype) {
				case "num":
					$pattern = "/[^\d]/";
					break;

				case "float":
					$max = 40;
					$pattern = "/[^-\d\.]/";
					if(preg_match($pattern,$value) || substr_count($value, ".") > 1 || (strlen ($value) < $min) || (strlen ($value) > $max)){
						$this->addError($value,$msg);
						return false;
					} else if (strpos($value, "-") !== false && $value[0] != "-") {
						$this->addError($value,$msg);
						return false;
					} else{
						return true;
					}
					break;

				case "perc":
					$pattern = "/[^\d\.\%]/";
					break;

				case "date":
					// two types: ymd, dmy
					$pattern = "/([\d]{4}-[\d]{1,2}-[\d]{1,2}|[\d]{1,2}-[\d]{1,2}-[\d]{4})/";
					$invert_match = false;

					/* dates only HAVE certain ranges */
					if ($min > 0) {
						$min = 8;
						$max = 10;
					} else if (empty($value)) {
						return true;
					}

					$a = explode("-", $value);
					if (count($a) != 3) {
						$invalidated = true;
						break;
					}
					list($d1, $d2, $d3) = $a;

					if (empty($d1) || empty($d2) || empty($d3)) {
						$invalidated = true;
						break;
					}

					// y-m-d
					if (strlen($d1) == 4) {
						$y = $d1;
						$m = $d2;
						$d = $d3;
					} else if (strlen($d3) == 4) {
						// d-m-y
						if ($d2 <= 12) {
							$d = $d1;
							$m = $d2;
							$y = $d3;
						}
						// m-d-y
						else {
							$m = $d1;
							$d = $d2;
							$y = $d3;
						}
					} else {
						$invalidated = true;
						break;
					}
					
					if (!is_numeric($m) || !is_numeric($d) || !is_numeric($y)) {
						$invalidated = true;
						break;
					} else if (!checkdate($m, $d, $y)) {
						$invalidated = true;
						break;
					}

					break;

				case "string":
					$pattern = "/[^\w\s\.\/\&\+\(\):,-@\.]/";
					break;

				case "regnum":
					$pattern = "/[^\w\s\.\(\)\/,-]/";
					break;

				case "email":
					$pattern = '/^[a-zA-Z0-9-_\.]+@([a-zA-Z0-9-\.]+)+$/';
					// temp but it works
					if (preg_match($pattern,$value) && (strlen ($value) >= $min) && (strlen ($value) <= $max))
						return true;
					else if ( $min == 0 && strlen($value) == 0)
						return true;
					else {
						$this->addError($value,$msg);
						return false;
					}
					break;

				case "url":
					$pattern = "/[^\w\.\(\)-]/";
					break;

				case "phone":
					$pattern = "/[^\d\(\)\s-]/";
					break;

				default:
					$pattern = "/[^\w\s\.,-]/";
		}

		if (strlen($value) < $min || strlen ($value) > $max) {
			$invalidated = true;
		}

		if (preg_match($pattern, $value)) {
			if ($invert_match) {
				$invalidated = true;
			}
		} else if (!$invert_match) {
			$invalidated = true;
		}

		if ($invalidated) {
			$this->addError($value,$msg);
			return false;
		} else {
			return true;
		}
	}

	# adds an error
	function addError($value,$msg) {
		$this->_errors[] = array ("value" => $value, "msg" => $msg);
	}

	# do the passwords match?
	function pwMatch ($value, $value2, $msg)
	{
		if ($value == $value2) {
			return true;
		} else {
			$this->_errors[] = array ("value" => $value, "msg" => $msg);
			return false;
		}
	}

	function chkDate($day, $mon, $year, $msg){
		$rdate = $day."-".$mon."-".$year;

		if(!$this->isOk ($day, "num", 1, 2, $msg)) return $rdate;
		if(!$this->isOk ($mon, "num", 1, 2, $msg)) return $rdate;
		if(!$this->isOk ($year, "num", 4, 4, $msg)) return $rdate;
		if(!checkdate($mon, $day, $year)){
			$this->isOk ($rdate, "num", 1, 1, $msg);
		}
		return $rdate;
	}

	function chkrDate($rdate, $msg){
		list($day, $mon, $year) = explode("-", $rdate);

		if(!$this->isOk ($day, "num", 1, 2, $msg)) return $rdate;
		if(!$this->isOk ($mon, "num", 1, 2, $msg)) return $rdate;
		if(!$this->isOk ($year, "num", 4, 4, $msg)) return $rdate;
		if(!checkdate($mon, $day, $year)){
			$this->isOk ($rdate, "num", 1, 1, $msg);
		}
		return $rdate;
	}

	# return current errors
	function getErrors()
	{
		return $this->_errors;
	}

	function genErrors() {
		$e = array();
		foreach ($this->_errors as $k => $v) {
			$e[] = "<li class='err'>$v[msg]</li>";
		}
		return implode("\n", $e);
	}

	# have any errors occurred?
	function isError ()
	{
		if (sizeof ($this->_errors) > 0)
		{
			return true;
		} else {
			return false;
		}
	}

	# reset errors
	function resetErrors ()
	{
		$this->_errors = array();
	}
}

} /* LIB END */

?>
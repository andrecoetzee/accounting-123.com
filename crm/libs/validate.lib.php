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

##
# Input validator (Inspired by a devshed.com tutorial)
##

if (class_exists ("validate")) {
	return 0;
}

class validate
{
	##
	# Local stuff
	##

	var $_errors;

	##
	# Public stuff
	##

	# constructor :: reset error list
	function validate ()
	{
		$this->resetErrors();
	}

	# checks length & characters
	function isOk ($value, $datatype, $min, $max, $msg)
	{
		# reject non scalars
		if (!is_scalar ($value)) {
			die ("Can not operate on non-scalar.$msg");
		}
		switch ($datatype) {
				case "num":
					$pattern = "/[^\d]/";
					break;
				case "float":
					$pattern = "/[^\d\.]/";
					break;
				case "perc":
					$pattern = "/[^\d\.\%]/";
					break;
				case "date":
					$pattern = "/[^\d-]/";
					break;
				case "string":
					$pattern = "/[^\w\s\.\(\),-]/";
					break;
				case "regnum":
					$pattern = "/[^\w\s\.\(\)\/,-]/";
					break;
				case "email":
					$pattern = '/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';

					// temp but it works
					if (preg_match($pattern,$value) && (strlen ($value) >= $min) && (strlen ($value) <= $max))
						return true;
					else if ( $min == 0 && strlen($value) == 0)
						return true;
					else {
						$this->addError($value,$msg);
						return false;
					}
					// temp

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

		if ((preg_match ($pattern, $value)) || (strlen ($value) < $min) || (strlen ($value) > $max)) {
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
?>

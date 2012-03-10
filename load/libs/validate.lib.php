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
			die ("Can not operate on non-scalar.");
		}
		switch ($datatype) {
			case "num":
				$pattern = "/[^\d]/";
				break;
			case "float":
				$pattern = "/[^\d\.]/";
				break;
			case "date":
				$pattern = "/[^\d-]/";
				break;
			case "string":
				$pattern = "/[^\w\s\.,-]/";
				break;
			case "email":
				$pattern = "/[^\w\.@-]/";
                                break;
			default:
				$pattern = "/[^\w\s\.,-]/";
		}

		if ((preg_match ($pattern, $value)) || (strlen ($value) < $min) || (strlen ($value) > $max)) {
			$this->_errors[] = array ("value" => $value, "msg" => $msg);
			return false;
		} else {
			return true;
		}
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

	/*
	# is it a valid email?
	function isEmailAddress ($value, $minlen, $maxlen, $msg)
	{
		$pattern = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/";
		if ((preg_match ($pattern, $value)) && (strlen ($value) > $minlen) && (strlen ($value) < $maxlen)) {
			return true;
		} else {
			$this->_errors[] = array ("value" => $value, "msg" => $msg);
			return false;
		}
	}
	*/

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

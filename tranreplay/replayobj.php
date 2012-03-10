<?

class clsIncludes {
	static public $accounts = array();
	static public $currency = array();
	static public $department = array();
	static public $pricelist = array();
	static public $store = array();
	static public $vatcode = array();
	
	static function incExist($type, $id) {
		$type = strtolower($type);

		if (is_null(clsIncludes::$${type})) {
			invalid_use("Invalid include type.");			
		}
		
		$v = clsIncludes::$${type};
		return isset($v[$id]);
	}
	
	static function addXML($type, $data) {
		$type = strtolower($type);

		$v = &self::${$type};
		$v[$data["ID"]] = $data;
	}
	
	static function add_account($id) {
		$sql = "SELECT * FROM core.accounts WHERE accid='$id'";
		$rslt = db_exec($sql);
		
		if (pg_num_rows($rslt) > 0) {
			self::$accounts[$id] = pg_fetch_assoc($rslt);
		}
	}
	
	static function add_vatcode($id) {
		$sql = "SELECT * FROM cubit.vatcodes WHERE id='$id'";
		$rslt = db_exec($sql);
		
		if (pg_num_rows($rslt) > 0) {
			self::$vatcode[$id] = pg_fetch_assoc($rslt);
		}
	}
	
	static function add_store($id) {
		$sql = "SELECT * FROM exten.warehouses WHERE whid='$id'";
		$rslt = db_exec($sql);
		
		if (pg_num_rows($rslt) > 0) {
			self::$store[$id] = pg_fetch_assoc($rslt);
		}
	}
	
	static function add_department($id) {
		$sql = "SELECT * FROM exten.departments WHERE deptid='$id'";
		$rslt = db_exec($sql);
		
		if (pg_num_rows($rslt) > 0) {
			self::$department[$id] = pg_fetch_assoc($rslt);
		}
	}
	
	static function add_pricelist($id) {
		$sql = "SELECT * FROM exten.pricelist WHERE listid='$id'";
		$rslt = db_exec($sql);
		
		if (pg_num_rows($rslt) > 0) {
			self::$pricelist[$id] = pg_fetch_assoc($rslt);
		}
	}
	
	static function add_currency($id) {
		$sql = "SELECT * FROM cubit.currency WHERE fcid='$id'";
		$rslt = db_exec($sql);
		
		if (pg_num_rows($rslt) > 0) {
			self::$currency[$id] = pg_fetch_assoc($rslt);
		}
	}
}

class clsInfoObj {
	static public $typeIncCols = array(
		"DEBTOR" => array(
			"deptid" => "department",
			"fcid" => "currency",
			"pricelist" => "pricelist"
		),
		"CREDITOR" => array(
			"deptid" => "department",
			"fcid" => "currency",
			"listid" => "pricelist"
		),
		"STOCK" => array(
			"whid" => "store",
			"vatcode" => "vatcode"
		)
	);
	
	public $id;
	public $type;
	public $cols = array();
	
	private $stack = array(), $stack_counter = 0, $stack_ptr = false;
	
	function clsInfoObj($type, $id) {
		$this->type = $type;
		$this->id = $id;
	}
	
	/**
	 * builds the class from a row array returned from database
	 *
	 * @param array $row
	 * @return boolean true on success
	 */
	function dbMake($sql) {
		$rslt = db_exec($sql) or errDie("Error reading transaction for stock.");
		
		if (pg_num_rows($rslt) <= 0) {
			return false;
		}

		$row = pg_fetch_assoc($rslt);
		
		foreach ($row as $cn => $cv) {
			/* if this column is a composite column/include, add it */
			if (isset(self::$typeIncCols[$this->type][$cn])) {
				$incname = self::$typeIncCols[$this->type][$cn];
				
				if (!clsIncludes::incExist($incname, $cv)) {
					$funcname = "add_$incname";
					clsIncludes::$funcname($cv);
				}
			}
			
			/* store the value */
			$this->cols[$cn] = $cv;
		}
		
		return true;
	}
	
	/**
	 * interprets xml for debtor
	 *
	 * @param resource $parser
	 * @param string $name
	 * @param array $pattrs
	 */
	function xmlStartElement($parser, $name, $pattrs) {
		if ($name == "IINFO") {
			/* composite column */
			if (isset(self::$typeIncCols[$this->type][$pattrs["NAME"]])) {
				$this->cols[$pattrs["NAME"]] = $pattrs["INCLUDE"];
			} else {
				$this->cols[$pattrs["NAME"]] = $pattrs["VALUE"];
			}
		}
	}
	
	/**
	 * interprets xml for debtor (end tag)
	 *
	 * @param resoure $parser
	 * @param string $name
	 * @ignore
	 */
	function xmlEndElement($parser, $name) {}
}

class clsLedger {
	public $type;
	public $id;
	private static $ledger_cnt = 0;
	public $cols = array();
	
	private $stack = array(), $stack_counter = 0, $stack_ptr = false;
	
	function clsLedger($type) {
		$this->type = $type;
		$this->id = ++self::$ledger_cnt;
	}
	
	/**
	 * builds the class from a row array returned from database
	 *
	 * @param array $row
	 */
	function addJournal($row) {
		foreach ($row as $cn => $cv) {
			/* if this column is a composite column/include, add it */
			if ($cn == "debitacc" || $cn == "creditacc") {
				if (!clsIncludes::incExist($incname, $cv)) {
					clsIncludes::add_account($cv);
				}
			}
			
			/* store the value */
			$this->cols[$cn] = $cv;
		}
	}
	
	/**
	 * makes a general ledger entry
	 * 
	 * @param int $debitacc debit account id
	 * @param int $creditacc credit account id
	 * @param string $date date of transaction
	 * @param int $refno transaction reference number
	 * @param float $amount transaction amount
	 * @param char $vat [y/n] vat yes/no
	 * @param string $details details of transaction
	 * @return boolean true on success
	 */
	function makeGeneralLedger($debitacc, $creditacc, $date, $refno, $amount, $vat, $details) {
		global $complete;
		
		$this->cols = array(
			"debitacc" => $debitacc,
			"creditacc" => $creditacc,
			"date" => $date,
			"refno" => $refno,
			"amount" => $amount,
			"vat" => $vat,
			"details" => $details
		);
		
		if (!clsIncludes::incExist("accounts", $debitacc)) {
			clsIncludes::add_account($debitacc);
		}
		
		if (!clsIncludes::incExist("accounts", $creditacc)) {
			clsIncludes::add_account($creditacc);
		}
		
		return true;
	}
	
	/**
	 * makes a customer ledger entry
	 * 
	 * @param int $cusnum customer id
	 * @param char $type [c/d] whether contra should be debited/credited
	 * @param int $contra contra account
	 * @param string $date date of transaction
	 * @param int $refno transaction reference number
	 * @param float $amount transaction amount
	 * @param char $vat [y/n] vat yes/no
	 * @param string $details details of transaction
	 * @return boolean true on success
	 */
	function makeCustLedger($cusnum, $type, $contra, $date, $refno, $amount, $vat, $details) {
		global $complete;
		
		if ($type == "c") {
			$debitacc = null;
			$creditacc = $contra;
		} else {
			$debitacc = $contra;
			$creditacc = null;
		}
		
		$this->cols = array(
			"iid" => $cusnum,
			"debitacc" => $debitacc,
			"creditacc" => $creditacc,
			"date" => $date,
			"refno" => $refno,
			"amount" => $amount,
			"details" => $details
		);
		
		if (!is_null($debitacc) && !clsIncludes::incExist("accounts", $debitacc)) {
			clsIncludes::add_account($debitacc);
		}
		
		if (!is_null($creditacc) && !clsIncludes::incExist("accounts", $creditacc)) {
			clsIncludes::add_account($creditacc);
		}
		
		if (!isset($complete["DEBTOR"][$cusnum])) {
			$complete["DEBTOR"][$cusnum] = new clsInfoObj("DEBTOR", $cusnum);
			return $complete["DEBTOR"][$cusnum]->dbMake(
				"SELECT * FROM cubit.customers WHERE cusnum='$cusnum'"
			);
		}
		
		return true;
	}
	
	/**
	 * makes a supplier ledger entry
	 * 
	 * @param int $supid supplier id
	 * @param char $type [c/d] whether contra should be debited/credited
	 * @param int $contra contra account
	 * @param string $date date of transaction
	 * @param int $refno transaction reference number
	 * @param float $amount transaction amount
	 * @param char $vat [y/n] vat yes/no
	 * @param string $details details of transaction
	 * @return boolean true on success
	 */
	function makeSuppLedger($supid, $type, $contra, $date, $refno, $amount, $vat, $details) {
		global $complete;
		
		if ($type == "c") {
			$debitacc = null;
			$creditacc = $contra;
		} else {
			$debitacc = $contra;
			$creditacc = null;
		}
		
		$this->cols = array(
			"iid" => $supid,
			"debitacc" => $debitacc,
			"creditacc" => $creditacc,
			"date" => $date,
			"refno" => $refno,
			"amount" => $amount,
			"details" => $details
		);
		
		if (!is_null($debitacc) && !clsIncludes::incExist("accounts", $debitacc)) {
			clsIncludes::add_account($debitacc);
		}
		
		if (!is_null($creditacc) && !clsIncludes::incExist("accounts", $creditacc)) {
			clsIncludes::add_account($creditacc);
		}
		
		if (!isset($complete["CREDITOR"][$supid])) {
			$complete["CREDITOR"][$supid] = new clsInfoObj("CREDITOR", $supid);
			return $complete["CREDITOR"][$supid]->dbMake(
				"SELECT * FROM cubit.suppliers WHERE supid='$supid'"
			);
		}
		
		return true;
	}
	
	/**
	 * makes a stock ledger entry
	 * 
	 * @param int $stkid stock id
	 * @param char $type [c/d] whether contra should be debited/credited
	 * @param int $contra contra account
	 * @param string $date date of transaction
	 * @param int $refno transaction reference number
	 * @param float $amount transaction amount
	 * @param char $vat [y/n] vat yes/no
	 * @param string $details details of transaction
	 * @return boolean true on success
	 */
	function makeStockLedger($stkid, $type, $contra, $date, $refno, $amount, $vat, $details) {
		global $complete;
		
		if ($type == "c") {
			$debitacc = null;
			$creditacc = $contra;
		} else {
			$debitacc = $contra;
			$creditacc = null;
		}
		
		$this->cols = array(
			"iid" => $stkid,
			"debitacc" => $debitacc,
			"creditacc" => $creditacc,
			"date" => $date,
			"refno" => $refno,
			"amount" => $amount,
			"details" => $details
		);
		
		if (!is_null($debitacc) && !clsIncludes::incExist("accounts", $debitacc)) {
			clsIncludes::add_account($debitacc);
		}
		
		if (!is_null($creditacc) && !clsIncludes::incExist("accounts", $creditacc)) {
			clsIncludes::add_account($creditacc);
		}
		
		if (!isset($complete["STOCK"][$stkid])) {
			$complete["STOCK"][$stkid] = new clsInfoObj("STOCK", $stkid);
			return $complete["STOCK"][$stkid]->dbMake(
				"SELECT * FROM cubit.stock WHERE stkid='$stkid'"
			);
		}
		
		return true;
	}
	
	/**
	 * interprets xml 
	 *
	 * @param resource $parser
	 * @param string $name
	 * @param array $pattrs
	 */
	function xmlStartElement($parser, $name, $pattrs) {
		if ($name == "JINFO") {
			/* composite column */
			if ($pattrs["NAME"] == "debitacc" || $pattrs["NAME"] == "creditacc") {
				$this->cols[$pattrs["NAME"]] = $pattrs["INCLUDE"];
			} else {
				$this->cols[$pattrs["NAME"]] = $pattrs["VALUE"];
			}
		}
	}
	
	/**
	 * interprets xml for debtor (end tag)
	 *
	 * @param resoure $parser
	 * @param string $name
	 * @ignore
	 */
	function xmlEndElement($parser, $name) {}
}

?>
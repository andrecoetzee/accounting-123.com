<?
/**
 * Objects/function for form handling
 *
 * @package Cubit
 * @subpackage Forms
 * @todo add custom button field type.
 * @todo make radio/select field validation not required ranges/datatypes. simply
 * make them check the supplied value with those values in the lists.
 */

if (!defined("FORM_LIB")) {
define("FORM_LIB", true);

global $CFORM_COUNTER;
$CFORM_COUNTER = 0;

/**
 * form handler class
 */
class cForm {
	/**
	 * form name
	 */
	var $frmname;

	/**
	 * sets the active key
	 */
	var $key;

	/**
	 * stores the key order
	 */
	var $keys;

	/**
	 * title
	 */
	var $title;

	/**
	 * title msg (a optional message under the title)
	 */
	var $title_msg;

	/**
	 * error messages
	 */
	var $errors;

	/**
	 * post/get
	 *
	 * @var string
	 */
	var $method;

	/**
	 * target script
	 *
	 * @var string
	 */
	var $action;

	/**
	 * whether we are accepting file uploads/not
	 *
	 * @var bool
	 */
	var $dataform;

	/**
	 * display columns
	 *
	 * @var int
	 */
	var $cols;

	/**
	 * active main table column
	 *
	 * @var int
	 */
	var $active_col;

	/**
	 * active main table row
	 *
	 * @var int
	 */
	var $active_row;

	/**
	 * hidden fields array
	 *
	 * @var array
	 */
	var $hfields;

	/**
	 * fields array
	 *
	 * @var array
	 */
	var $fields;

	/**
	 * automatically add ids equal to name
	 */
	var $auto_ids;

	/**
	 * the name of the request variable
	 */
	var $_REQ_NAME;

	/**
	 * the reference to _POST/_GET/_REQUEST (dflt _REQUEST)
	 */
	var $_REQ;

	/**
	 * has a button been added? not, then autoadd
	 *
	 * adds a submit to the end, and a "correction" if _REQ[key] is set
	 */
	var $has_buttons;

	/**
	 * this is an correction form
	 */
	var $correction;

	/**
	 * returns array to pass on with serialize()
	 *
	 */
	function __sleep() {
		$vars = array(
			"frmname",
			"key",
			"keys",
			"title",
			"errors",
			"method",
			"action",
			"dataform",
			"cols",
			"active_col",
			"active_row",
			"hfields",
			"fields",
			"auto_ids",
			"has_buttons",
			"correction"
		);

		return $vars;
	}

	/**
	 * contructor
	 *
	 * @param string $method post/get
	 * @param string $action target script (dflt: self)
	 * @param int $cols number of columns for display (dflt: 1)
	 * @param bool $auto_ids automatically add ids to fields
	 */
	function __construct($method = "post", $action = false, $cols = 1, $auto_ids = false) {
		$this->setReqVar("_REQUEST");

		$this->title_msg = "";

		/* determine where to get the values from (if it is in request variables
			use them instead, iow restoring a form object */
		if (!isset($this->_REQ["cubit_form"])) {
			if ($action === false) {
				$action = SELF;
			}

			$this->title = "";

			$this->method = strtolower($method);
			$this->action = $action;
			$this->cols = $cols;
			$this->active_col = 1;
			$this->active_row = 1;
			$this->auto_ids = $auto_ids;

			$this->fields = array();
			$this->hfields = array();
			$this->errors = array();
			$this->keys = array();

			$this->dataform = false;
			$this->has_buttons = false;
		} else {
			/* restore object */
			/* @var $obj cForm */
			$obj = unserialize(base64_decode($this->_REQ["cubit_form"]));

			/* use restored object's values */
			$this->title = $obj->title;

			$this->method = $obj->method;
			$this->action = $obj->action;
			$this->cols = $obj->cols;
			$this->active_col = 1;
			$this->active_row = 1;
			$this->auto_ids = $obj->auto_ids;

			$this->fields = $obj->fields;
			$this->hfields = $obj->hfields;
			$this->errors = $obj->errors;
			$this->keys = $obj->keys;

			$this->dataform = $obj->dataform;
			$this->has_buttons = false;

			/* populate values with the from request variable */
			$this->populate();
		}

		/* generate a form name */
		global $CFORM_COUNTER;
		$n = preg_replace("/\.php$/", "", $action);
		$n = preg_replace("/[^a-zA-Z0-9_]/", "_", $n);
		$this->frmname = "$n".(++$CFORM_COUNTER);
	}
	
	/**
	 * sets the method/action, leaving either parameter as false will leave it unchanged
	 * 
	 * @param string $method
	 * @param string $action
	 */
	function setFormParm($method = false, $action = false) {
		if ($method !== false) {
			$this->method = $method;
		}
		
		if ($action !== false) {
			$this->action = $action;
		}
	}

	/**
	 * populates added fields with values from request
	 */
	private function populate() {
		foreach ($this->fields as $ifldname => $fldopt) {
			$fldname = base64_decode($ifldname);
			//$fldname = "testme[3][4]";

			/* remove all buttons */
			if ($fldopt["type"] == "ctrlbutton") {
				unset($this->fields[$ifldname]);
				continue;
			}

			/* set the passed on flag (not really used) */
			if (isset($fldopt["passedon"])) {
				$this->fields[$ifldname]["passedon"] = true;
			}

			/* populate values with that in request */
			/* BLOODY UGLY HACK TO ACCESS ARRAYS */
			if (preg_match("/^([^\[]+)((\[[^\]]+\])+)\$/", $fldname, $allss)) {
				// get array variable name
				$arrname = $allss[1];

				// break array subscripts into array
				preg_match_all("/\[([^\]]+)\]/", $allss[2], $ss);

				// loop through array and assign end value
				if (isset($this->_REQ[$arrname])) {
					$val = $this->_REQ[$arrname];

					foreach ($ss[1] as $arrss) {
						if (isset($val[$arrss])) {
							$val = $val[$arrss];
						}
					}

					// we dont want to assign the value "Array"
					if (is_array($val)) {
						$val = "";
					}

					$this->fields[$ifldname]["value"] = $val;
				}
				//eval("print \"<br />\$_REQUEST[$arrname]$allss[2]<br />--\";");
				//eval("print \"<br />\".\$_REQUEST[\"$arrname\"]$allss[2].\"<br />--\";");
			} else if ($this->fields[$ifldname]["type"] == "date" 
						&& isset($this->_REQ["${fldname}_year"])) {
				$this->fields[$ifldname]["year"] = $this->_REQ["${fldname}_year"];
				$this->fields[$ifldname]["month"] = $this->_REQ["${fldname}_month"];
				$this->fields[$ifldname]["day"] = $this->_REQ["${fldname}_day"];
			} else if (isset($this->_REQ[$fldname])) {
				$this->fields[$ifldname]["value"] = $this->_REQ[$fldname];
			}
			/* if we are not set and field is a checkbox, set value to "off" */
			else if ($fldopt["type"] == "checkbox") {
				$this->fields[$ifldname]["value"] = "off";
			}
		}
	}

	/**
	 * sets which array we reading request variables from
	 *
	 * if $r is false, it will simply reinitialize _REQ with whatever _REQ_NAME
	 * is set to.
	 *
	 * @param string $r variable name
	 */
	public function setReqVar($r = false) {
		if ($r !== false) {
			$this->_REQ_NAME = $r;
		} else {
			$r = $this->_REQ_NAME;
		}

		/* bypass the the super global security so we can assign the request
			variable. php prevents you from using super globals in variable
			variables. */
		eval("\$this->_REQ = &\$$r;");
	}

	/**
	 * merely adds a bunch of options to the field
	 *
	 * if $opt contains an id opt and auto_ids is enabled, the opt id will take
	 * priority and will be the one used for this form field. this is the only
	 * $opt value which will overwrite an existing one. if for example you pass
	 * "name" option, it will merely be ignored.
	 *
	 * @param string $iname internal name (base64 encoded)
	 * @param array $opts
	 */
	private function addopts($iname, $opts) {
		$this->fields[$iname]["opts"] = array();
		$f = &$this->fields[$iname]["opts"];

		if ($this->auto_ids && !isset($opts["id"])) {
			$opts["id"] = $iname;
		}

		foreach ($opts as $o_name => $o_val) {
			if (!isset($f[$o_name])) {
				$f[$o_name] = $o_val;
			}
		}
	}

	/**
	 * sets the active column/row
	 *
	 * @param int $col
	 */
	public function setcell($col, $row = 1) {
		$this->active_col = $col;
		$this->active_row = $row;
	}

	/**
	 * adds and sets the "key" field
	 *
	 * @param string $val
	 */
	public function setkey($k) {
		/* add the key to the keys sequence if it is new */
		if (($pos = array_search($k, $this->keys)) === false) {
			$this->keys[] = $k;
		}

		/* set the key and the hidden field */
		$this->key = $k;

		/* remove the previous key field first */
		$iname = base64_encode("key");
		if (isset($this->hfields[$iname])) {
			unset($this->hfields[$iname]);
		}
		$this->add_hidden("key", $k, "string");
	}

	/**
	 * sets the title
	 */
	public function settitle($val) {
		$this->title = $val;
	}

	/**
	 * sets an optional msg to display under title
	 */
	public function setmsg($val) {
		$this->title_msg = $val;
	}

	/**
	 * returns whether field is of a selected category (input/special/etc..)
	 *
	 * field categories is just a way to distinguish them in the way they are
	 * handled, like "input" fields having "required marks" and special fields
	 * not being confirmed etc..
	 *
	 * @param string $type check if field is of this category
	 * @param string $ifldname internal field name
	 * @return bool
	 */
	private function fldis($type, $ifldname) {
		/* fallthru matching switch */
		switch ($type) {
			/* a special field (heading, message, ctrlbutton, layout, etc.) */
			case "special":
				switch ($this->fields[$ifldname]["type"]) {
					case "ctrlbutton":
					case "heading":
					case "message":
					case "layout":
						return true;
				}
				break;

			/* data fields like file uploads */
			case "data":
				if ($this->fields[$ifldname]["type"] == "file") {
					return true;
				}
				break;

			/* fields that take character/zerolength input (not like selections
				which always have a value) */
			case "input":
				switch ($this->fields[$ifldname]["type"]) {
					case "file":
					case "text":
					case "textarea":
						return true;;
				}

				break;
		}

		return false;
	}
	
	/**
	 * removes any fields whose names match this
	 * 
	 * @param string $fldname 
	 * @param bool $isarr field is an array
	 */
	function clean_fields($fldname, $isarr = false) {
		if ($isarr) {
			$rx = "/^$fldname(\[[^\]]+\])+\$/";
		} else {
			$rx = "/^$fldname\$/";
		}
		
		foreach ($this->fields as $ifldname => $fldopt) {
			$fldname = base64_decode($ifldname);
			
			if (preg_match($rx, $fldname)) {
				unset($this->fields[$ifldname]);
			}
		}
	}

	/**
	 * adds a layout for manipulating template
	 *
	 * layouts are table rows and cells. for field/disp pair the pair is linked by
	 * "id"
	 *
	 * labels are as follows:
	 * 	%disp[id]  - will be replaced by fields display value (some fields dont have this)
	 *  %fld[id]   - where field will go
	 *  %fldonly   - where field will go, doesn't need a display pair partner
	 *  %bg        - replaced in sequence by bgcolor='".bgcolor($i)."'
	 *
	 * @todo make the pair validation (make sure pairs are pairs)
	 * @param string $layout
	 * @param bool $sticky
	 * @param string $iname name for field
	 * @return bool valid layout or not
	 */
	public function add_layout($layout, $sticky = false, $iname = false) {
		/* validate layout and determine field capacity */
		if (!preg_match_all("/%fld(only|\[[a-z0-9A-Z]+\])/", $layout, $fld)) {
			$capcount = 0;
		} else {
			$capcount = count($fld[0]);
		}

		/* generate internal name */
		if ($iname === false) {
			$iname = "layout_".md5($layout);
		}
		
		$iname = base64_encode($iname);

		/* reset to blank (will overwrite previous one) */
		$this->fields[$iname] = array();
		$f = &$this->fields[$iname];

		$f["type"] = "layout";
		$f["data"] = $layout;
		$f["capacity"] = $capcount; // number of fields layout can take
		$f["dispkey"] = $this->key;
		$f["sticky"] = $sticky;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;
	}

	/**
	 * adds a heading
	 *
	 * @param string $disp display value
	 */
	public function add_heading($disp) {
		$iname = base64_encode("heading_".strtolower(preg_replace("/[^a-zA-Z0-9]/", "_", $disp)));

		/* reset to blank (will overwrite previous one) */
		$this->fields[$iname] = array();
		$f = &$this->fields[$iname];

		$f["type"] = "heading";
		$f["disp"] = $disp;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;
	}

	/**
	 * adds a message to the form layout
	 *
	 * @param string $disp display value
	 */
	public function add_message($disp, $name = false) {
		if ($name === false) {
			$iname = base64_encode("message_".strtolower(md5($disp)));
		} else {
			$iname = base64_encode("message_".strtolower(md5($name)));
		}

		/* reset to blank (will overwrite previous one) */
		$this->fields[$iname] = array();
		$f = &$this->fields[$iname];

		$f["type"] = "message";
		$f["disp"] = $disp;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;
	}

	/**
	 * add control buttons like submit/reset (displayed at bottom of all tables)
	 *
	 * @param mixed $disp displays
	 * @param mixed $type submit/reset
	 * @param mixed $name names (false means no name)
	 * @param mixed $align cell text alignment
	 * @param mixed $onclick actions (false means no onclick event)
	 */
	function add_ctrlbtn($disp, $type, $name = false, $o = array()) {
		$this->has_buttons = true;

		//print "$disp:$type:$name<br />";
		/* create a temporary name to generate internal name */
		if ($name === false) {
			$tname = strtolower("btn_$disp");
			$tname = preg_replace("/[^a-z0-9_]/", "_", $tname);
		} else {
			$tname = $name;
		}

		$iname = base64_encode($tname);

		/* reset to blank (will overwrite previous one) */
		$this->fields[$iname] = array();
		$f = &$this->fields[$iname];

		if(!isset($onclick))
			$onclick = "";

		$f["type"] = "ctrlbutton";
		$f["dispkey"] = $this->key;
		$f["btype"] = $type;
		$f["disp"] = $disp;
		$f["name"] = $name;
		$f["onclick"] = $onclick;

		$this->addopts($iname, $o);
	}

	/**
	 * add text field
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param string $val dflt value
	 * @param string $type email/string/allstring
	 * @param string $r range
	 * @param array $opt extra html options in array form: name=>value
	 */
	public function add_text($disp, $name, $val, $type, $r, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* build field */
		$f["type"] = "text";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		if (!isset($f["value"])) {
			$f["value"] = $val;
		}
		$f["datatype"] = $type;
		list($f["min"], $f["max"]) = explode(":", $r);
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		$this->addopts($iname, $o);
	}

	/**
	 * add text area field
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param string $val dflt value
	 * @param string $r range
	 * @param int $rows
	 * @param int $cols
	 * @param array $o extra html options in array form: name=>value
	 */
	public function add_textarea($disp, $name, $val, $r, $rows = 4, $cols = 20, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* build field */
		$f["type"] = "textarea";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		if (!isset($f["value"])) {
			$f["value"] = $val;
		}
		$f["datatype"] = "allstring";
		list($f["min"], $f["max"]) = explode(":", $r);
		$f["rows"] = $rows;
		$f["cols"] = $cols;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		$this->addopts($iname, $o);
	}

	/**
	 * add select field
	 *
	 * list should be array in form value=>display. if list is grouped it should
	 * be 2 dimensional in form "group label"=>"list" where list array is in form
	 * value=>display.
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param string $val dflt value
	 * @param array/dbList $list list of options/dbList object
	 * @param string $type data type
	 * @param string $r range
	 * @param bool $grouped grouped/not
	 * @param array $o extra html options in array form: name=>value
	 */
	public function add_select($disp, $name, $val, $list, $type, $r, $grouped = false, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* build field */
		$f["type"] = "select";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		if (!isset($f["value"])) {
			$f["value"] = $val;
		}
		$f["list"] = $list;
		$f["datatype"] = $type;
		list($f["min"], $f["max"]) = explode(":", $r);
		$f["grouped"] = $grouped;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		//print_r($this->fields);

		$this->addopts($iname, $o);
	}

	/**
	 * add radio selection field
	 *
	 * list should be array in form value=>display.
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param string $val dflt value
	 * @param array $list list of options
	 * @param string $type data type
	 * @param string $r range
	 * @param array $o extra html options in array form: name=>value
	 */
	public function add_radio($disp, $name, $val, $list, $type, $r, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* build field */
		$f["type"] = "radio";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		if (!isset($f["value"])) {
			$f["value"] = $val;
		}
		$f["list"] = $list;
		$f["datatype"] = $type;
		list($f["min"], $f["max"]) = explode(":", $r);
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		$this->addopts($iname, $o);
	}

	/**
	 * add checkbox field
	 *
	 * on confirm screen there are two types of displays, set display and yes/no
	 * display.
	 *
	 * 1. Yes/No - will display the $disp and Yes if set and No if not set.
	 * 2. Set Disp - will display $disp if set, and nothing if not set. will be displayed
	 * where the field should go.
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param bool $val checked/not when $key is not set
	 * @param bool $yesno (default is yesno)
	 * @param array $o extra html options in array form: name=>value
	 */
	public function add_checkbox($disp, $name, $val, $yesno = true, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* just the text representation */
		if ($val) {
			$val = "on";
		} else {
			$val = "off";
		}

		/* build field */
		$f["type"] = "checkbox";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		$f["datatype"] = "string";
		$f["yesno"] = $yesno;
		$f["min"] = 2;
		$f["max"] = 3;
		if (!isset($f["value"])) {
			$f["value"] = $val;
		}
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		$this->addopts($iname, $o);
	}

	/**
	 * add date selection field
	 *
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @param bool $array fields is part of an array?
	 * @param array $o extra html options in array form: name=>value
	 */
	public function add_date($disp, $name, $year, $month, $day, $array = false, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* build field */
		$f["type"] = "date";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		$f["datatype"] = "date";
		if (!isset($f["year"])) {
			$f["year"] = $year;
			$f["month"] = $month;
			$f["day"] = $day;
		}
		$f["array"] = $array;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		$this->addopts($iname, $o);
	}

	/**
	 * add file selection field
	 *
	 * @see cForm::addopts()
	 * @param string $disp
	 * @param string $name
	 * @param string $val dflt value
	 * @param string $type email/string/allstring
	 * @param int $min
	 * @param int $max
	 * @param array $opt extra html options in array form: name=>value
	 */
	public function add_file($disp, $name, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->fields[$iname])) {
			$this->fields[$iname] = array();
		}
		$f = &$this->fields[$iname];

		/* build field */
		$f["type"] = "file";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["disp"] = $disp;
		$f["name"] = $name;
		$f["pos_col"] = $this->active_col;
		$f["pos_row"] = $this->active_row;

		$this->addopts($iname, $o);

		/* uses data encoding */
		$this->dataform = true;
	}

	/**
	 * add hidden field
	 *
	 * @see cForm::addopts()
	 * @param string $name
	 * @param string $val value
	 * @param int $list list of options
	 * @param string $type data type
	 * @param string $r range
	 * @param array $o extra html options in array form: name=>value
	 */
	public function add_hidden($name, $val, $type, $o = array()) {
		$iname = base64_encode($name);

		/* reset to blank (will overwrite previous one) */
		if (!isset($this->hfields[$iname])) {
			$this->hfields[$iname] = array();
		}
		$f = &$this->hfields[$iname];

		/* build field */
		$f["type"] = "hidden";
		$f["dispkey"] = $this->key;
		$f["passedon"] = false;
		$f["name"] = $name;
		if (!isset($f["value"])) {
			$f["value"] = $val;
		}
		$f["datatype"] = $type;
		$f["min"] = $f["max"] = strlen($val);
		//$f["pos_col"] = $this->active_col;
		//$f["pos_row"] = $this->active_row;

		$this->hfields[$iname]["opts"] = array();
		$fo = &$this->hfields[$iname]["opts"];

		if ($this->auto_ids && !isset($o["id"])) {
			$o["id"] = $name;
		}

		foreach ($o as $o_name => $o_val) {
			if (!isset($fo[$o_name])) {
				$fo[$o_name] = $o_val;
			}
		}
	}

	/**
	 * returns the form name
	 *
	 * @return string
	 */
	public function getname() {
		return $this->frmname;
	}

	/**
	 * returns passon for the object
	 *
	 * @return string
	 */
	public function getpasson() {
		$d = base64_encode(serialize($this));
		$this->setReqVar();
		return "<input type='hidden' name='cubit_form' value='$d' />";
	}

	/**
	 * builds html from extra options
	 *
	 * @param $opts $fields array from which "opts" element will be used
	 * @param $idpfx a prefix for the "id field"
	 */
	private function getfld_extraopts($opts, $idpfx = "") {
		$o = array();

		foreach ($opts["opts"] as $k => $v) {
			if ($k == "id") {
				$k .= $idpfx;
			}
			$o[] = "$k='$v'";
		}

		return implode(" ", $o);
	}

	/**
	 * builds html for type: text
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_text($name, $opts) {
		$o = $this->getfld_extraopts($opts);
		return "<input type='text' maxlength='$opts[max]' name='$name' value='$opts[value]' $o />";
	}

	/**
	 * builds html for type: textarea
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_textarea($name, $opts) {
		$o = $this->getfld_extraopts($opts);
		return "<textarea name='$name' rows='$opts[rows]' cols='$opts[cols]' $o
			/>$opts[value]</textarea>";
	}

	/**
	 * builds html for type: select
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_select($name, $opts) {
		$o = $this->getfld_extraopts($opts);

		if (is_array($opts["list"])) {
			$OUT = "<select name='$name' $o />";

			if ($opts["grouped"]) {
				foreach ($opts["list"] as $grpname => $grpdata) {
					$OUT .= "<optgroup label='$grpname'>";

					foreach ($grpdata as $k => $v) {
						if ($k == $opts["value"]) {
							$sel = "selected";
						} else {
							$sel = "";
						}

						$OUT .= "<option $sel value='$k'>$v</option>";
					}

					$OUT .= "</optgroup>";
				}
			} else {
				foreach ($opts["list"] as $k => $v) {
					if ($k == $opts["value"]) {
						$sel = "selected";
					} else {
						$sel = "";
					}

					$OUT .= "<option $sel value='$k'>$v</option>";
				}
			}

			$OUT .= "</select>";
		}
		/* dbList object, fetch the list */
		else if (is_object($opts["list"])) {
			$OUT = $opts["list"]->get($name, $opts["value"], $o);
		}
		/* invalid option, return false */
		else {
			$OUT = false;
		}

		return $OUT;
	}

	/**
	 * builds html for type: radio
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_radio($name, $opts) {
		$o = $this->getfld_extraopts($opts);

		foreach ($opts["list"] as $k => $v) {
			if ($k == $opts["value"]) {
				$sel = "checked='t'";
			} else {
				$sel = "";
			}

			$OUT .= "<input type='radio' name='$name' $sel value='$k' $o> $v";
		}

		return $OUT;
	}

	/**
	 * builds html for type: checkbox
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_checkbox($name, $opts) {
		$sel = $opts["value"] == "on" ? "checked='t'" : "";
		$o = $this->getfld_extraopts($opts);
		return "<input type='checkbox' name='${name}' $sel $o />";
	}

	/**
	 * builds html for type: date
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_date($name, $opts) {
		$OUT = "";

		$o = $this->getfld_extraopts($opts, "_day");
		$OUT .= "<input size='2' type='text' name='${name}_day' id='${name}_day' value='$opts[day]' $o />&nbsp;";

		$o = $this->getfld_extraopts($opts, "_month");
		$OUT .= "<input size='2' type='text' name='${name}_month' id='${name}_month' value='$opts[month]' $o />&nbsp;";

		$o = $this->getfld_extraopts($opts, "_year");
		$OUT .= "<input size='4' type='text' name='${name}_year' id='${name}_year' value='$opts[year]' $o />&nbsp;";

		$OUT .= mkDateSelectB($name, $opts["array"]);

		return $OUT;
	}

	/**
	 * builds html for type: file
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_file($name, $opts) {

		//manual error handling
		if(!isset($opts['value'])) $opts['value'] = "";

		$o = $this->getfld_extraopts($opts);
		return "<input type='file' name='$name' value='$opts[value]' $o />";
	}

	/**
	 * builds html for type: hidden
	 *
	 * @param string $name
	 * @param array $opts
	 */
	private function getfld_hidden($name, $opts) {
		$o = $this->getfld_extraopts($opts);
		/* this if for the pass on of date fields */
		if ($opts["type"] == "date") {
			$out = "
				<input type='hidden' name='${name}_year' value='$opts[year]' $o />
				<input type='hidden' name='${name}_month' value='$opts[month]' $o />
				<input type='hidden' name='${name}_day' value='$opts[day]' $o />";
			return $out;
		} else {
			return "<input type='hidden' name='$name' value='$opts[value]' $o />";
		}
	}

	/**
	 * updates layout with supplied field.
	 *
	 * takes the background counter by reference to update backgrounds properly.
	 *
	 * @param string &$layout
	 * @param int &$bg
	 * @param string $disp field display data
	 * @param string $fldname real field name
	 */
	private function layout_update(&$layout, &$bg, $disp, $fldname) {
		/* update background colors */
		while (preg_match("/%bg/", $layout)) {
			$layout = preg_replace("/%bg/", "bgcolor='".bgcolor($bg)."'", $layout, 1);
		}

		/* negative look behind
			so we dont replace the %fld when actual field name is "fld" or "fld[x]" and
			we would have %%CUBIT_FLD%fld%% or %%CUBIT_FLD%fld[x]%% or %%CUBIT_DSP%fld[x]%%
		*/
		$neglb = "(?<!CUBIT_(?:DSP|FLD))";

		/* find next layout ID */
		preg_match("/$neglb%fld(only|\[([0-9a-zA-Z]+)\])/", $layout, $m);
		$type = $m[1];

		/* field has no display */
		if ($type == "only") {
			$layout = preg_replace("/$neglb%fldonly/", "%%CUBIT_FLD%$fldname%%", $layout, 1);
		}
		/* field has display */
		else {
			$id = $m[2];
			$layout = preg_replace("/$neglb%disp\[$id\]/", "%%CUBIT_DSP%$fldname%%", $layout, 1);
			$layout = preg_replace("/$neglb%fld\[$id\]/", "%%CUBIT_FLD%$fldname%%", $layout, 1);
		}
	}

	/**
	 * makes the template used by getfrm_input() and getfrm_confirm()
	 */
	private function getfrm_tmpl() {
		$OUT = "<h3>$this->title</h3>";

		if (!empty($this->title_msg)) {
			$OUT .= "$this->title_msg<br /><br />";
		}

		/* add defaults buttons if none was added */
		if (!$this->has_buttons) {
			/* if this is a confirm screen */
			if (array_search($this->key, $this->keys) > 0) {
				$this->add_ctrlbtn("&laquo; Correction", "submit", "btn_back");
			}

			$this->add_ctrlbtn("Submit", "submit", "btn_submit");
		}

		/* form encoding */
		if ($this->dataform === true) {
			$et = "enctype='multipart/form-data'";
		} else {
			$et = "";
		}

		/* validation errors */
		$errs = "";
		if (isset($this->errors[$this->key])) {
			foreach ($this->errors[$this->key] as $ifldname => $flderr) {
				$errs .= "<li class='err'>$flderr</li>";
			}
		}

		/* start output */
		$totcols = $this->cols * 2;
		$OUT .= "
		<form name='$this->frmname' method='$this->method' action='$this->action' $et>";

		/* class information */
		$OUT .= $this->getpasson();

		$OUT .= "%%CUBIT_FLD%HIDDEN%%";

		/* main table layout */
		$CELLOUT = array();

		/* column table layout */
		$OUT .= "
		<table ".TMPL_tblDflts.">";

		if (!empty($errs)) {
			$OUT .= "
			<tr>
				<th colspan='$totcols'>There are problems with values of the following fields:</th>
			<tr>
			<tr bgcolor='".bgcolorc(0)."'>
				<td colspan='$totcols'>$errs</td>
			</tr>";
		}

		/* add fields */
		$i = 0; // bgcolor counter
		$maxcols = 1; // maximum number of columns of any row
		$ctrlbtns = array(); // store ctrlbutton names as they are found
		$layout = ""; // buffer for layout filling
		$layout_capacity = false; // capacity of current layout to still be filled
		foreach ($this->fields as $ifldname => $fldopt) {
			$fldname = base64_decode($ifldname);

			#manual error handling
			if(!isset($fldopt["pos_col"])) $fldopt["pos_col"] = "";
			if(!isset($fldopt["pos_row"])) $fldopt["pos_row"] = "";
			
			/* count max columns */
			if ($fldopt["pos_col"] > $maxcols) {
				$maxcols = $fldopt["pos_col"];
			}

			/* create pointer to the output cell variable */
			if (!isset($CELLOUT[$fldopt["pos_row"]])) {
				$CELLOUT[$fldopt["pos_row"]] = array(
					$fldopt["pos_col"] => ""
				);
			} else if (!isset($CELLOUT[$fldopt["pos_row"]][$fldopt["pos_col"]])) {
				$CELLOUT[$fldopt["pos_row"]][$fldopt["pos_col"]] = "";
			}

			$fOUT = &$CELLOUT[$fldopt["pos_row"]][$fldopt["pos_col"]];

			/* heading type */
			if ($fldopt["type"] == "heading") {
				$fOUT .= "
				<tr>
					<th colspan='2'>$fldopt[disp]</th>
				</tr>";
			}
			/* message type */
			else if ($fldopt["type"] == "message") {
				$fOUT .= "
				<tr bgcolor='".bgcolor($i)."'>
					<td colspan='2'>$fldopt[disp]</td>
				</tr>";
			}
			/* control button type */
			else if ($fldopt["type"] == "ctrlbutton") {
				if ($fldopt["dispkey"] == $this->key) {
					$ctrlbtns[] = $ifldname;
				}
			}
			/* layout, add to it */
			else if ($fldopt["type"] == "layout") {
				if ($this->key == $fldopt["dispkey"] || $fldopt["sticky"]) {
					//print_r($fldopt);
					if ($fldopt["capacity"] == 0) {
						$fOUT .= $fldopt["data"];
					} else {
						$layout .= $fldopt["data"];
						$layout_capacity += $fldopt["capacity"];
					}
				}
			}
			/* normal field types */
			else {
				/* find positions in sequence of each key */
				$cur_kpos = array_search($this->key, $this->keys);
				$fld_kpos = array_search($fldopt["dispkey"], $this->keys);

				/* only display field of current step is after/greater than
					the field's step */
				if ($fld_kpos <= $cur_kpos) {
					/* if an error for this field exists, highlight it */
					if (isset($this->errors[$this->key][$ifldname])) {
						//$this->fields[$fldname]["opts"]["style"] = "border: 2px solid red;";
						$this->fields[$ifldname]["opts"]["class"] = "frmerr";
						$errstyle = "class='frmerr_l'";
					} else {
						$errstyle = "";
					}

					/* create output tmpl for field by using layout */
					if ($layout_capacity !== false) {
						--$layout_capacity;
						$this->layout_update($layout, $i, $fldopt["disp"], $fldname);

						/* last field added, add to output and disable layout gen */
						if ($layout_capacity == 0) {
							$fOUT .= $layout;

							$layout = "";
							$layout_capacity = false;
						}
					}
					/* create standard row for field */
					else {
						$fOUT .= "
						<tr bgcolor='".bgcolor($i)."'>
							<td>%%CUBIT_DSP%$fldname%%</td>
							<td>%%CUBIT_FLD%$fldname%%</td>
						</tr>";
					}
				}
			}
		}

		/* now put the form field tables into rows/columns */

		foreach ($CELLOUT as $rownum => $rowcols) {
			$OUT .= "
			<tr>";

			for ($i = 1; $i <= $maxcols; ++$i) {
				if (isset($rowcols[$i])) {
					$OUT .= "
						<td valign='top'>
						<table ".TMPL_tblDflts." width='100%'>
							$rowcols[$i]
						</table>
						</td>";
				} else {
					$OUT .= "<td>&nbsp;</td>";
				}
			}

			$OUT .= "
			</tr>";

			if (count($rowcols) > $maxcols) {
				$maxcols = count($rowcols);
			}
		}

		/* add control buttons */
		$btndata = array();
		foreach ($ctrlbtns as $ifldname) {
			$fldopt = $this->fields[$ifldname];

			if ($fldopt["name"] !== false) {
				$n = "name='$fldopt[name]'";
			} else {
				$n = "";
			}

			$btndata[] = "<input type='$fldopt[btype]' value='$fldopt[disp]' $n />";
		}

		$btndata = implode(" ", $btndata);

		$OUT .= "
		<tr>
			<td colspan='$maxcols' align='right' nowrap='t'>$btndata</td>
		</tr>";

		$OUT .= "
		</table>
		</form>";

		return $OUT;
	}

	/**
	 * returns html for input form
	 *
	 * @return array
	 */
	public function getfrm_input() {
		$OUT = $this->getfrm_tmpl();

		/* add hidden fields */
		$hidden = array();
		foreach ($this->hfields as $ifldname => $fldopt) {
			$fldname = base64_decode($ifldname);

			$hidden[] = $this->getfld_hidden($fldname, $fldopt);
		}

		foreach ($this->fields as $ifldname => $fldopt) {
			$fldname = base64_decode($ifldname);

			if (!$this->fldis("special", $ifldname)) {
				if ($fldopt["dispkey"] == $this->key) {
					$func = "getfld_".$fldopt["type"];

					if (isset($fldopt["min"]) && $fldopt["min"] > 0
							&& $this->fldis("input", $ifldname)) {
						$req = REQ." ";
					} else {
						$req = "";
					}

					$disp = "$req$fldopt[disp]";
					$data = $this->$func($fldname, $fldopt);
				} else {
					$hidden[] = $this->getfld_hidden($fldname, $fldopt);

					if ($fldopt["type"] == "select" || $fldopt["type"] == "radio") {
						$disp = $fldopt["disp"];

						if (is_array($fldopt["list"])) {
							$data = $fldopt["list"][$fldopt["value"]];
						} else if (is_object($fldopt["list"])) {
							$data = $fldopt["list"]->getDisp($fldopt["value"]);
						} else {
							$data = "";
						}
					} else if ($fldopt["type"] == "checkbox") {
						/* yesno display */
						if ($fldopt["yesno"] == true) {
							$disp = $fldopt["disp"];

							if ($fldopt["value"] == "on") {
								$data = "Yes";
							} else {
								$data = "No";
							}
						}
						/* set display */
						else {
							$disp = "";

							if ($fldopt["value"] == "on") {
								$data = $fldopt["disp"];
							} else {
								$data = "";
							}
						}
					} else if ($fldopt["type"] == "date") {
						$disp = $fldopt["disp"];
						$data = "$fldopt[year]-$fldopt[month]-$fldopt[day]";
					} else {
						$disp = $fldopt["disp"];
						$data = $fldopt["value"];
					}
				}


				$OUT = preg_replace("/%%CUBIT_DSP%".preg_quote($fldname)."%%/", $disp, $OUT);
				$OUT = preg_replace("/%%CUBIT_FLD%".preg_quote($fldname)."%%/", $data, $OUT);
			}
		}

		$OUT = preg_replace("/%%CUBIT_FLD%HIDDEN%%/", implode("\n", $hidden), $OUT);

		return $OUT;
	}

	/**
	 * returns text to describe what the field MAY contain
	 *
	 * @param string $ifldname internal field name
	 * @return string
	 */
	static function valexpl($datatype) {
		switch ($datatype) {
			case "num":
				$ret = "Integer";
				break;

			case "float":
				$ret = "Real Number";
				break;

			case "date":
				$ret = false;
				break;

			case "string":
				$ret = "Letters, numbers, spaces and ()&.:,+-@";
				break;

			case "email":
				$ret = "Email Address";
				break;

			case "url":
				$ret = "Web Address";
				break;

			case "phone":
				$ret = "Numbers, parentheses, spaces, plus and minus";
				break;

			default:
				$ret = false;
		}

		if ($ret !== false) {
			return "Required format: $ret";
		} else {
			return "";
		}
	}

	/**
	 * validates the variabless, returns true on error.
	 *
	 * why do we have to pass the key? so we only return true for errors
	 * if the field the error is reported on is in the function prior to the
	 * one we are calling the validation function. this way we only fall back to
	 * a function to display the error if we are at a function later than that one.
	 *
	 * @param string $key key to validate
	 * @return bool
	 */
	public function validate($key) {
		/* clear all the errors (we recheck everything) */
		$this->errors = array();

		foreach ($this->fields as $ifldname => $fldopt) {
			/* this check makes sure that a field only gets validated
				when validation is or has been initiated for that field's step.

				this prevents validation errors from being displayed when
				a field is for example required, but left blank because the
				"back" button was pressed. in this case when you get to this
				field's step, it is validated and marked as an error because it
				is left blank. just a "nice to have". */
			if (!(isset($fldopt["validated"])
					|| !isset($fldopt["dispkey"])
					|| $fldopt["dispkey"] == $key)) {
				continue;
			}

			/* dont validate buttons/headings/messages */
			if (!$this->fldis("special", $ifldname)) {
				$this->validateField($ifldname, $fldopt);
			}
		}

		/* return whether there are errors in the previous step.
			a previous step is defined as the fields made for the
			"key pointing to this step" */
		return $this->validateErrors($key);
	}

	/**
	 * does the actual validation for variables
	 *
	 * @param string $ifldname
	 * @param string $fldopts
	 * @return bool
	 */
	private function validateField($ifldname, $fldopts) {
		$invalidated = false;
		$fldkey = $fldopts["dispkey"];
		$disp = $fldopts["disp"];

		if ($this->fldis("data", $ifldname)) {
			$fldname = base64_decode($ifldname);

			if (!ucfs::valid($fldname)) {
				$invalidated = ucfs::ferror($fldname);
			}
		} else {
			/* retrieve data from field options */
			if ($this->fields[$ifldname]["type"] == "date") {
				$value = "$fldopts[year]-$fldopts[month]-$fldopts[day]";
			} else {
				$value = $fldopts["value"];
			}
			$datatype = $fldopts["datatype"];
			if(!isset($fldopts["min"]))
				$fldopts["min"] = "";
			if(!isset($fldopts["max"]))
				$fldopts["max"] = "";
			$min = $fldopts["min"];
			$max = $fldopts["max"];

			/* mark field as being validated */
			$this->fields[$ifldname]["validated"] = true;

			$invalidated = cForm::validateValue($value, $datatype, $min, $max);
		}

		/* store error if any */
		if ($invalidated !== false) {
			if (!isset($this->errors[$fldkey])) {
				$this->errors[$fldkey] = array();
			}

			$this->errors[$fldkey][$ifldname] = "$disp. $invalidated";
		} else {
			return true;
		}
	}

	/**
	 * validates a value and returns error string, or false if field is valid.
	 *
	 * @param string $value
	 * @param string $datatype
	 * @param int $min
	 * @param int $max
	 */
	static function validateValue($value, $datatype, $min, $max) {
		$invert_match = true;
		$invalidated = false;

		switch ($datatype) {
			case "num":
				$pattern = "/[^\d]/";
				break;

			case "float":
				$pattern = "/[^\d\.]/";
				break;

			case "date":
				// two types: ymd, dmy
				$pattern = "/([\d]{4}-[\d]{1,2}-[\d]{1,2}|[\d]{1,2}-[\d]{1,2}-[\d]{4})/";
				$invert_match = false;

				/* dates only HAVE certain ranges */
				$max = 10;
				if ($min > 0) {
					$min = 8;
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

				if (strlen($d1) == 4) {
					$y = $d1;
					$m = $d2;
					$d = $d3;
				} else if (strlen($d3) == 4) {
					if ($d2 < 12) {
						$d = $d1;
						$m = $d2;
						$y = $d3;
					} else {
						$m = $d1;
						$d = $d2;
						$y = $d3;
					}
				} else {
					$invalidated = true;
					break;
				}

				if (!checkdate($m, $d, $y)) {
					$invalidated = "Impossible date";
					break;
				}

				break;

			case "string":
				$pattern = "/[^\w\s\.\/\&\+\\(\\)\\:,-@\.]/";
				break;
				
			case "allstring":
				$pattern = "/[^\w\s\.\/\&\+\\(\\)\\:\\!,-@\.]/";
				break;

			case "email":
				$pattern = '/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';
				$invert_match = false;
				break;

			case "url":
				$pattern = "/[^\w\.\(\)-]/";
				break;

			case "phone":
				$pattern = "/[^\d\(\)\s\+-]/";
				break;

			default:
				$pattern = "/[^\w\s\.,-]/";
		}
		
		/* validate field */
		if (strlen($value) < $min) {
			if ($min == 1) {
				$invalidated = "Required field.";
			} else {
				$invalidated = "Needs to be at least $min characters, is ".strlen($value).".";
			}
		} else if (strlen($value) > $max) {
			$invalidated = "May not exceed $max characters, is ".strlen($value).".";
		} else if (preg_match($pattern, $value)) {
			if ($invert_match) {
				$invalidated = cForm::valexpl($datatype);
			}
		} else if (!$invert_match && strlen($value) > 0) {
			$invalidated = cForm::valexpl($datatype);
		}

		if ($invalidated === true) {
			$invalidated = "Invalid value.";
		}

		return $invalidated;
	}

	/**
	 * returns true if errors have occured, optionally for specified key
	 *
	 * if we check for a specific key, we check that key and all the keys
	 * below it. otherwise we might skip an error in a step much further back.
	 *
	 * @param $key
	 */
	private function validateErrors($key = false) {
		/* for a specific key downwards */
		if ($key !== false) {
			$keypos = array_search($key, $this->keys);
			if ($keypos === false) {
				return true;
			}
			for ($i = $keypos; $i >= 0; --$i) {
				$key = $this->keys[$i];
				if (isset($this->errors[$key])) {
					if (count($this->errors[$key]) > 0) {
						return true;
					}
				}
			}
		} else {
			foreach ($this->errors as $a) {
				if (count($a) > 0) {
					return true;
				}
			}
		}

		return false;
	}
}

/**
 * takes the values of array1 and puts them into array2.
 *
 * uses the array in first field to fill up the referenced array in second field
 * with default values. used for default form values, for ex. $fields is an array
 * containing default values, then fillFields($fields, $_POST);
 * <b>IGNORE THIS</b>. Rather just use extract($FIELDS, EXTR_SKIP);
 *
 * @param array $fields fields array
 * @param array $gp get/post array
 */
function fillFields($fields, &$ar) {
	foreach ($fields as $k => $v) {
		if (!isset($ar[$k])) $ar[$k] = $v;
	}
}

} /* LIB END */

?>
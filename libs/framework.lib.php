<?
/**
 * Objects/function for the cubit framework
 *
 * @package Cubit
 * @subpackage Framework
 */

/* EXAMPLES

////
//// running the framework - a sample app
////
	cFramework::run("enter"); // first parameter is entry function name
	cFramework::parse();
	
	function enter(&$frm) {
		...
		$frm->setkey("confirm");
		...
	}
	
	function confirm(&$frm) {
		...
	}
	
////
//// function name doesn't match key name - how to map functions to keys
////
	// let's say you have 4 different keys which uses the same function
	// the keys are for ex. "Export to Spreadsheet", "Print", "Save", "View"
	cFramework::mapkey("export to spreadsheet", "view");
	cFramework::mapkey("print", "view");
	cFramework::mapkey("save", "view");
	
	// this should do the trick, the key's will map to the function view()
	// you don't need to map the "view" key, as it will go there anyway
	// also key case doesn't matter, as they are always converted
	// to lowercase
	
////
//// quick links after every function 
////
	// you can run this function anytime before cFramework::parse()
	cFramework::quicklinks(
		ql("../cust-credit-stockinv.php", "New Invoice"),
		ql("../invoice-view.php", "View Invoices")
	);
*/

if (!defined("FRAMEWORK_LIB")) {
define("FRAMEWORK_LIB", true);

/* framework class */
class cFramework {
	/* key -> function mappings */
	var $function_maps;
	
	/* form class */
	var $frm;
	
	/* resulting output */
	var $o;
	
	/**
	 * constructor
	 *
	 */
	function __construct() {
		$this->keys = array();
		$this->frm = new cForm();
		$this->o = array();
	}
	
	/**
	 * maps a key to a function, returns false if function not found.
	 * 
	 * function should take one parameter, a cForm object and 
	 * optionally a cFramework object.
	 * 
	 * @param string $key
	 * @param string $funcname
	 * @return bool
	 */
	static function mapkey($key, $funcname) {
		global $FRAMEWORK;
		
		if (!is_callable($funcname)) {
			return false;
		}
		
		$FRAMEWORK->function_maps[strtolower($key)] = $funcname;
	
		return true;
	}
	
	/**
	 * starts the script by calling the next function
	 * 
	 * function names should equal key's, or should be mapped by the
	 * keymap function.
	 * 
	 * this function replaces the switches in the beginning
	 * of each script. it initializes the cForm object and calls
	 * the function specified by the key.
	 * 
	 * @param string $firstfunc first function to call
	 */
	static function run($firstfunc) {
		global $FRAMEWORK;
		
		/* map the "-f" key to $firstfunc */
		$FRAMEWORK->function_maps["-f"] = $firstfunc;
		
		if (!isset($_REQUEST["key"])) {
			$key = "-f";
		} else {
			/* back button clicked */
			if (isset($_REQUEST["btn_back"])) {
				/* look for the current target key's position */
				$ppos = array_search($_REQUEST["key"], $FRAMEWORK->frm->keys);
				
				/* key not found */
				if ($ppos === false) {
					invalid_use("No correction function found.");
				}
				
				/* if we didn't come from the second step 
					(in which case we want to go back to the $firstfunc) */
				if ($ppos >= 2) {
					$key = $FRAMEWORK->frm->keys[$ppos - 2];
				} else {
					$key = "-f";
				}
			} else {
				$key = $_REQUEST["key"];
			}
		}
		
		if (isset($FRAMEWORK->function_maps[strtolower($key)])) {
			$funcname = $FRAMEWORK->function_maps[strtolower($key)];
		} else {
			$funcname = $key;
		}
		
		if (!is_callable($funcname)) {
			invalid_use("Function \"$funcname()\" not found.");
		}
		
		$FRAMEWORK->o["result"] = $funcname($FRAMEWORK->frm);
	}
	
	/**
	 * makes quick links together with the ql() function
	 *
	 * @see ql()
	 * @param ... unlimited return values of ql()
	 * @return string html with quick links table
	 */
	static function quickLinks() {
		global $FRAMEWORK;

		$OUT = "
		<table ".TMPL_tblDflts.">
	    <tr>
	    	<th>Quick Links</th>
	    </tr>";
	
		foreach (func_get_args() as $arg) {
			$disp = $arg[1];
	
			$OUT .= "
			<tr class='quicklinks'>
				<td><a ".($disp[0]?"target='_blank'":"")." href='$arg[0]'>$disp[1]</a></td>
			</tr>";
		}
	
		$OUT .= "
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	
		$FRAMEWORK->o["quicklinks"] = $OUT;
	}
	
	/**
	 * parses the output and includes template.php
	 *
	 */
	static function parse() {
		global $FRAMEWORK;
		
		if (!isset($FRAMEWORK->o["result"])) {
			$FRAMEWORK->o["result"] = "";
		}
		
		if (!isset($FRAMEWORK->o["quicklinks"])) {
			cFramework::quickLinks();
		}
		
		$OUTPUT = ""
			. $FRAMEWORK->o["result"]
			. "<br />" 
			. $FRAMEWORK->o["quicklinks"];
			
		parse($OUTPUT);
	}
};

}

?>
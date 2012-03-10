<?
/**
 * Objects/function for form handling
 *
 * @package Cubit
 * @subpackage Forms
 */
if (!defined("FORMS_LIB")) {
	define("FORMS_LIB", true);

/**
 * fills in defaults values into second array using first array
 *
 * uses the array in first field to fill up the referenced array in second field
 * with default values using array. used for default form values
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
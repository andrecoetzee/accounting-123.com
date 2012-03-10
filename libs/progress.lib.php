<?
/**
 * Quick db queries. Using without or with "false" id values will return dbSelect
 * object for all rows in db
 *
 * @package Cubit
 * @subpackage Progress
 */

if (!defined("PROGRESS_LIB")) {
	define("PROGRESS_LIB", true);
	
/**
 * displays progress bar
 *
 * @param string $tmpl template script to use (newtemplate.php/template.php)
 */
function displayProgress($tmpl) {
	define("TEMPLATE_PARTIAL", true);
	define("PROGRESS_BAR", true);

	$tmpl = relpath($tmpl);

	$OUT = "
		<div id='wait_bar_container'>
		<table width='100%' height='100%'>
			<tr>
				<td align=center valign=middle>
					<font size='2' color='white'>
					Please wait while your company is being created. This may take several minutes.</font><br>
					<div id='wait_bar_parent' style='border: 1px solid black; width:100px'>
						<div id='wait_bar' style='font-size: 15pt'>...</div>
					</div>
				</td>
			</tr>
		</table>
		</div>

		<script>
			wait_bar = getObjectById('wait_bar')
			die_bar = false;
			function moveWaitBar() {
				if ( wait_bar.innerHTML == '................')
					wait_bar.innerHTML = '.';
				else
					wait_bar.innerHTML = wait_bar.innerHTML + '.';

				if (!die_bar) {
					setTimeout('moveWaitBar()', 50);
				}
			}

			function stopWaitBar() {
				document.getElementById('wait_bar_container').innerHTML = '';
				die_bar = true;
			}

			setTimeout('moveWaitBar()', 100);
		</script>";

	$OUTPUT = "";
	include($tmpl);

	partialOut($OUT);
}

/**
 * stops the progress bar (in case of say a validation error)
 *
 */
function stopProgress() {
	partialOut("<script>stopWaitBar();</script>");
}
}

?>

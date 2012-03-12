<?

	require ("settings.php");

	$OUTPUT = show_image ($_GET);

	require ("template.php");



function show_image ($_POST)
{

	extract ($_POST);

	if (!isset($picid)){
		return "";
	}

	db_connect ();

	$get_img = "SELECT type,ident_id FROM display_images WHERE id = '$picid' LIMIT 1";
	$run_img = db_exec($get_img) or errDie ("Unable to get image information.");
	if (pg_numrows($run_img) < 1){
		#image not found ??
		$previous = "";
		$next = "";
	}else {
		$arr = pg_fetch_array ($run_img);

		$previous = "";
		$next = "";

		#check for any additional images for this member
		
		#get prev button
		$get_other = "SELECT id FROM display_images WHERE type = '$arr[type]' AND ident_id = '$arr[ident_id]' AND id < '$picid' ORDER BY id desc LIMIT 1";
		$run_other = db_exec($get_other) or errDie ("Unable to get images information.");
		if (pg_numrows($run_other) > 0)
			$previous = "<input type='button' onCLick=\"document.location='view_image.php?picid=".pg_fetch_result($run_other,0,0)."'\" value='Previous'>";

		$get_other = "SELECT id FROM display_images WHERE type = '$arr[type]' AND ident_id = '$arr[ident_id]' AND id > '$picid' LIMIT 1";
		$run_other = db_exec($get_other) or errDie ("Unable to get images information.");
		if (pg_numrows($run_other) > 0)
			$next = "<input type='button' onCLick=\"document.location='view_image.php?picid=".pg_fetch_result($run_other,0,0)."'\" value='Next'>";

	}

	$buttons = "<tr height='20%' valign='bottom'><td width='40%' align='right'>$previous</td><td>$next</td></tr>";

	$display = "
					<table ".TMPL_tblDflts." height='95%' width='100%'>
						<tr height='80%'>
							<td colspan='2'><img src='show_dimg.php?picid=$picid' width='160' height='185' border='1'></td>
						</tr>
						$buttons
					</table>
				";
	return $display;


}


?>
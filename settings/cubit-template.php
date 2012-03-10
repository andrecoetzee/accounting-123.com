<?

	require ("../settings.php");

	if (isset($HTTP_POST_VARS["key"])){
		$OUTPUT = write_tmpl_settings ($HTTP_POST_VARS);
	}else {
		$OUTPUT = get_tmpl_settings ();
	}
	
	require ("../template.php");
	


function get_tmpl_settings ($err="",$reload=FALSE)
{

	if ($reload)
		header ("Location: cubit-template.php");
		//print "<script>document.location.refresh ();</script>";
		


	$tmpl_settings = array (
		"var_TMPL_fntSize" => "Template default font-size",
		"var_TMPL_h3FntSize" => "Template heading size",
		"var_TMPL_h4FntSize" => "Template heading size",
		"var_TMPL_tblCellPadding" => "Template table cellpadding",
		"var_TMPL_tblCellSpacing" => "Template table cellspacing",
		"var_TMPL_fntColor" => "Template - Default font-color",
		"var_TMPL_h3Color" => "Template large heading color",
		"var_TMPL_h4Color" => "Template heading color",
		"var_TMPL_lnkColor" => "Template default link-color",
		"var_TMPL_lnkHvrColor" => "Template default link-color (hover)",
		"var_TMPL_navLnkColor" => "Template navigation-link color",
		"var_TMPL_navLnkHvrColor" => "Template navigation-link color (hover)",
		"var_TMPL_tblDataColor1" => "Template data row color",
		"var_TMPL_tblDataColor2" => "Template data row color (alternative)",
		"var_TMPL_tblHdngBg" => "Template table-heading background color",
		"var_TMPL_tblHdngColor" => "Template table heading font color",
		"var_TMPL_bgColor" => "Template background-color",
		"var_TMPL_hrColor" => "Horizontal rule color"
	);

	db_connect ();
	$defaults = array ();

	#get the entries from db ...
	$get_tmpl = "SELECT * FROM template_colors";
	$run_tmpl = db_exec($get_tmpl) or errDie ("Unable to get template color information.");
	if (pg_numrows($run_tmpl) > 0){
		$defaults = array ();
		while ($arr = pg_fetch_array ($run_tmpl)){
			$tmp = "var_".$arr['setting'];
			$defaults[$tmp] = $arr['value'];
		}
	}else {
		$defaults = array (
			"var_TMPL_fntSize" => "10",
			"var_TMPL_h3FntSize" => "12",
			"var_TMPL_h4FntSize" => "10",
			"var_TMPL_tblCellPadding" => "2",
			"var_TMPL_tblCellSpacing" => "1",
			"var_TMPL_fntColor" => "#000000",
			"var_TMPL_h3Color" => "#FFFFFF",
			"var_TMPL_h4Color" => "#FFFFFF",
			"var_TMPL_lnkColor" => "#0000DD",
			"var_TMPL_lnkHvrColor" => "#FF0000",
			"var_TMPL_navLnkColor" => "#CCCCCC",
			"var_TMPL_navLnkHvrColor" => "#FFFFFF",
			"var_TMPL_tblDataColor1" => "#88BBFF",
			"var_TMPL_tblDataColor2" => "#77AAEE",
			"var_TMPL_tblHdngBg" => "#114488",
			"var_TMPL_tblHdngColor" => "#FFFFFF",
			"var_TMPL_bgColor" => "#4477BB",
			"var_TMPL_hrColor" => "#000000"
		);
	}

		$maindefaults = array (
			"var_TMPL_fntSize" => "10",
			"var_TMPL_h3FntSize" => "12",
			"var_TMPL_h4FntSize" => "10",
			"var_TMPL_tblCellPadding" => "2",
			"var_TMPL_tblCellSpacing" => "1",
			"var_TMPL_fntColor" => "#000000",
			"var_TMPL_h3Color" => "#FFFFFF",
			"var_TMPL_h4Color" => "#FFFFFF",
			"var_TMPL_lnkColor" => "#0000DD",
			"var_TMPL_lnkHvrColor" => "#FF0000",
			"var_TMPL_navLnkColor" => "#CCCCCC",
			"var_TMPL_navLnkHvrColor" => "#FFFFFF",
			"var_TMPL_tblDataColor1" => "#88BBFF",
			"var_TMPL_tblDataColor2" => "#77AAEE",
			"var_TMPL_tblHdngBg" => "#114488",
			"var_TMPL_tblHdngColor" => "#FFFFFF",
			"var_TMPL_bgColor" => "#4477BB",
			"var_TMPL_hrColor" => "#000000"
		);


//print "<pre>";
//var_dump ($defaults);
//print "</pre>";




	$entries = "
					<tr>
						<th>Setting</th>
						<th>Value</th>
						<th></th>
						<th colspan='2'>Default</th>
					</tr>
				";

	foreach ($tmpl_settings AS $constant => $description){

		$description = ucwords($description);

		if(!isset($$constant))
			$$constant = $defaults[$constant];

		$val = $$constant;

		if (substr($val,0,1) == "#")
			$showentry = "<td width='5' bgcolor='$val'>  </td>";
		else 
			$showentry = "<td width='5'></td>";

		$entries .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$description</td>
							<td><input type='text' size='35' name='$constant' value='$val'></td>
							$showentry
							<td>$maindefaults[$constant]</td>
							<td><input type='button' onClick=\"document.form1.$constant.value='$maindefaults[$constant]';\" value='Restore'></td>
						</tr>
					";
	}

	$display = "
					<script>
						function restoreAll (){
							document.form1.var_TMPL_fntSize.value = '10';
							document.form1.var_TMPL_h3FntSize.value = '12';
							document.form1.var_TMPL_h4FntSize.value = '10';
							document.form1.var_TMPL_tblCellPadding.value = '2';
							document.form1.var_TMPL_tblCellSpacing.value = '1';
							document.form1.var_TMPL_fntColor.value = '#000000';
							document.form1.var_TMPL_h3Color.value = '#FFFFFF';
							document.form1.var_TMPL_h4Color.value = '#FFFFFF';
							document.form1.var_TMPL_lnkColor.value = '#0000DD';
							document.form1.var_TMPL_lnkHvrColor.value = '#FF0000';
							document.form1.var_TMPL_navLnkColor.value = '#CCCCCC';
							document.form1.var_TMPL_navLnkHvrColor.value = '#FFFFFF';
							document.form1.var_TMPL_tblDataColor1.value = '#88BBFF';
							document.form1.var_TMPL_tblDataColor2.value = '#77AAEE';
							document.form1.var_TMPL_tblHdngBg.value = '#114488';
							document.form1.var_TMPL_tblHdngColor.value = '#FFFFFF';
							document.form1.var_TMPL_bgColor.value = '#4477BB';
							document.form1.var_TMPL_hrColor.value = '#000000';
						}
					</script>

					<h2>Change Cubit Appearance</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form1'>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<td>
								<table ".TMPL_tblDflts.">
									$entries
									<tr>
										<td colspan='4' align='right'><input type='button' onClick='restoreAll();' value='Restore All'></td>
									</tr>
									".TBL_BR."
									<tr>
										<td colspan='2' align='right'><input type='submit' value='Confirm'></td>
									</tr>
								</table>
							</td>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									".TBL_BR."
									<tr>
										<th colspan='2'>Color Examples</th>
									</tr>
									<tr bgcolor='#94a1ff'>
										<td>Light Blue</td>
										<td>#94a1ff</td>
									</tr>
									<tr bgcolor='#feff9a'>
										<td>Light Yellow</td>
										<td>#feff9a</td>
									</tr>
									<tr bgcolor='#b5ffa9'>
										<td>Light Green</td>
										<td>#b5ffa9</td>
									</tr>
									<tr bgcolor='#ffe5a6'>
										<td>Light Orange</td>
										<td>#ffe5a6</td>
									</tr>
									<tr bgcolor='#d4a6ff'>
										<td>Light Purple</td>
										<td>#d4a6ff</td>
									</tr>
								</table>
							</td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function write_tmpl_settings ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$array = array (
		"TMPL_fntSize",
		"TMPL_h3FntSize",
		"TMPL_h4FntSize",
		"TMPL_tblCellPadding",
		"TMPL_tblCellSpacing",
		"TMPL_fntColor",
		"TMPL_h3Color",
		"TMPL_h4Color",
		"TMPL_lnkColor",
		"TMPL_lnkHvrColor",
		"TMPL_navLnkColor",
		"TMPL_navLnkHvrColor",
		"TMPL_tblDataColor1",
		"TMPL_tblDataColor2",
		"TMPL_tblHdngBg",
		"TMPL_tblHdngColor",
		"TMPL_bgColor",
		"TMPL_hrColor"
	);

	foreach ($array AS $each){
		$val = "var_$each";
		$upd_sql = "UPDATE template_colors SET value = '".$$val."' WHERE setting = '$each'";
		$run_udp = db_exec($upd_sql) or errDie ("Unable to update template colors.");
	}
	

	return get_tmpl_settings("<li class='err'>Template settings have been updated.</li>",true);

}



?>
<?

require ("../settings.php");

if (isset($_POST["key"]))
	$OUTPUT = write_setting ();
else 
	$OUTPUT = show_setting ();

$OUTPUT .= mkQuickLinks(
	ql ("../sorder-new.php","New Sales Order"),
	ql ("../nons-sorder-new.php","New Non Stock Sales Order"),
	ql ("../sorder-view.php","View Sales Orders",""),
	ql ("../nons-sorder-view.php","View Non Stock Sales Orders")
);

require ("../template.php");


function show_setting ($err="")
{

	#get current setting ...
	$neg_setting = getCsetting ("SORDER_NEG_STOCK");
	if (!isset($neg_setting) OR strlen($neg_setting) < 1){
		$neg_setting = "yes";
	}

	$check1 = "";
	$check2 = "";
	if ($neg_setting == "yes")
		$check1 = "checked=yes";
	else 
		$check2 = "checked=yes";

	$display = "
		<h4>Change Showing Of Negative Stock On Sales Order Setting</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			$err
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Display Negative Stock On Sales Orders</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='neg_setting' value='yes' $check1>Yes</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='neg_setting' value='no' $check2>No</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'><input type='submit' value='Write'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function write_setting ()
{

	extract ($_POST);

	if (!isset($neg_setting))
		$neg_setting = "yes";

	db_connect ();

	#check if settings exists ...
	$get_check = "SELECT value FROM settings WHERE constant = 'SORDER_NEG_STOCK' LIMIT 1";
	$run_check = db_exec($get_check) or errDie ("Unable to check setting information.");
	if (pg_numrows($run_check) < 1){
		#nothing found!
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'SORDER_NEG_STOCK', 'Show Negative Stock On Sales Order', '$neg_setting', 'static', 'allstring', '1', '250', '0', 'f'
			)";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to update setting information. (1)");
	}else {
		#found! update!
		$upd_sql = "UPDATE settings SET value = '$neg_setting' WHERE constant = 'SORDER_NEG_STOCK'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update setting information. (2)");
	}

	return show_setting("<li class='err'>Setting has been updated.</li><br>");

}



?>
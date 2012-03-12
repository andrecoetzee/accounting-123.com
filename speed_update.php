<?

	require ("settings.php");

	if(isset($_POST["key"])){
		$OUTPUT = start_maint();
	}else {
		$OUTPUT = run_maint ();
	}

	require ("template.php");


function run_maint()
{

	$display = "
		<table ".TMPL_tblDflts." width='40%'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<td><li class='err'>Please Wait For This Process To Complete.</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><li class='err'>
					This process should complete in an hour or two, if it does not complete and does 
					not display successful completion then please edit the php.ini file and change (increase) 
					the resource limits. Please do not change these settings without consulting your dealer 
					as you may break your software.
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Process'></td>
			</tr>
		</form>
		</tr>";
	return $display;

}


function start_maint ()
{

	custom_db("cubit");

	pg_exec("VACUUM");
	pg_exec("VACUUM FULL");
	pg_exec("VACUUM ANALYZE");
	pg_exec("REINDEX DATABASE cubit");

	$get_comps = "SELECT * FROM companies WHERE status = 'active'";
	$run_comps = pg_exec($get_comps) or errDie("Unable to get active companies");
	if(pg_numrows($run_comps) < 1){
		return "<li class='err'>No Active Companies Found To Process.</li>";
	}else {
		#process the blk1 db
		custom_db("cubit_blk1");

		pg_exec("VACUUM");
		pg_exec("VACUUM FULL");
		pg_exec("VACUUM ANALYZE");
		pg_exec("REINDEX DATABASE cubit_blk1");

		#process the active companies
		while ($carr = pg_fetch_array($run_comps)){
			$company = "cubit_".$carr['code'];

			custom_db("$company");

			pg_exec("VACUUM");
			pg_exec("VACUUM FULL");
			pg_exec("VACUUM ANALYZE");
			pg_exec("REINDEX DATABASE $company");
		}
	}

	$display = "
		<table ".TMPL_tblDflts.">
			<tr>
				<td><li class='err'>Process Has Been Completed.</li></td>
			</tr>
		</table>";
	return $display;

}



function custom_db($db)
{

	$link = @pg_connect("user=".DB_MUSER." password=".DB_MPASS." ".DB_HOST." dbname=$db")
	or die ("Unable to find main database. Cubit cannot start.");

}


?>
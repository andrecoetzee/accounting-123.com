<?

	require ("settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch($HTTP_POST_VARS["key"]){
			case "confirm":
				$OUTPUT = confirm_loan ($HTTP_POST_VARS);
				break;
			case "write":
				$OUTPUT = write_loan ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = get_loan ();
		}
	}else {
		$OUTPUT = get_loan ();
	}

	require ("template.php");


function get_loan ()
{


	$display = "
			<h2>Add New Loan Type</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<th>Loan Type</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='text' name='loan_type'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Next'></td>
				</tr>
			</table><br>"
	.mkQuickLinks(
		ql("salwages/loan_apply.php", "Apply for Loan"),
		ql("loan_type_add.php", "Add Loan Type"),
		ql("loan_type_view.php", "View Loan Types")
	);

	return $display;

}


function confirm_loan ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$display = "
			<h2>Confirm New Loan Type</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='loan_type' value='$loan_type'>
				<tr>
					<th>Loan Type</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$loan_type</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Confirm'></td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("loan_type_add.php", "Add Loan Type"),
				ql("loan_type_view.php", "View Loan Types")
			);
	return $display;

}


function write_loan ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$insert_sql = "INSERT INTO loan_types (loan_type) VALUES ('$loan_type')";
	$run_insert = db_exec($insert_sql) or errDie("Unable to store loan type information");

	return "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Information Saved</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Loan Type Has Been Added</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("salwages/loan_apply.php", "Add Loan Application"),
				ql("loan_type_add.php", "Add Loan Type"),
				ql("loan_type_view.php", "View Loan Types")
			);


}

?>
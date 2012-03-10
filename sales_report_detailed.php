<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";
	$fields["f_stock"] = "";
	$fields["f_cust"] = "";

	$OUTPUT = "<center>
	<h3>Detailed Sales Report</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Filter</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td rowspan='2'>
				<input type='submit' value='Search'
				style='height: 100%; font-weight: bold;' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>
				Stock<input type='checkbox' name='f_stock' value='checked' $f_stock />
				Customers<input type='checkbox' name='f_cust' value='checked' $f_cust />
			</td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}
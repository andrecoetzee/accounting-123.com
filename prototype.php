<?

	require ("settings.php");

	$OUTPUT = "<center><h3>Inventory Ledger</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
	<tr><th>DATE</th><th>DETAILS</th><th>QTY</th><th>COST AMOUNT</th><th>BALANCE</th></tr>
	<tr class='bg-even'><td colspan=5>(Stock Code) Stock Description</td></tr>
	<tr class='bg-even'>
		<td><br></td>
		<td>Balance Brought Forward</td>
		<td>10</td>
		<td>430</td>
		<td>430</td>
	</tr>
	<tr class='bg-odd'>
		<td>12-12-2333</td>
		<td>Sold To customer : TT traders - Invoice no. 3</td>
		<td>4</td>
		<td>-230</td>
		<td>200</td>
	</tr>
	<tr class='bg-odd'>
		<td>12-12-2333</td>
		<td>Sold To customer : TT traders - Invoice no. 4</td>
		<td>2</td>
		<td>-100</td>
		<td>100</td>
	</tr>
	<tr class='bg-even'>
		<td><br></td>
		<td>Total for period March to Date :</td>
		<td>4</td>
		<td>100</td>
		<td>100</td>
	</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table></center>";

	require ("template.php");
?>

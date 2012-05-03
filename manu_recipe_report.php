<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	$sql = "
	SELECT DISTINCT(m_stock_id)
	FROM cubit.recipies
		LEFT JOIN cubit.stock ON recipies.m_stock_id=stock.stkid
	WHERE stkcod ILIKE '$search%' OR stkdes ILIKE '$search%'";
	$recipies_rslt = db_exec($sql) or errDie("Unable to retrieve recipes.");

	$recipe_out = "";
	while (list($m_stkid) = pg_fetch_array($recipies_rslt)) {
		$sql = "SELECT stkcod, stkdes FROM cubit.stock WHERE stkid='$m_stkid'";
		$m_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		list($m_stkcod, $m_stkdes) = pg_fetch_array($m_stock_rslt);

		$recipe_out .= "
		<tr>
			<th>($m_stkcod) $m_stkdes</th>
		</tr>";

		// Retrieve items
		$sql = "
		SELECT stkcod, stkdes
		FROM cubit.recipies
			LEFT JOIN cubit.stock ON recipies.s_stock_id=stock.stkid
		WHERE m_stock_id='$m_stkid'";
		$s_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		while (list($s_stkcod, $s_stkdes) = pg_fetch_array($s_stock_rslt)) {
			$recipe_out .= "
			<tr class='".bg_class()."'>
				<td><li>($s_stkcod) $s_stkdes</li></td>
			</tr>";
		}
	}

	if (empty($recipe_out)) {
		$recipe_out = "
		<tr class='".bg_class()."'>
			<td><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>Manufacturing Recipes</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Search Recipe</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Search' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		$recipe_out
	</table>
	</center>";

	return $OUTPUT;
}

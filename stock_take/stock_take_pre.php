<?php

require ("settings.php");

define ("OFFSET_SIZE", 10);

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "pretake_display":
			$OUTPUT = pretake_display();
			break;
		case "pretake_print":
			$OUTPUT = pretake_print();
			break;
		case "pretake_update":
			$OUTPUT = pretake_update();
			break;
	}
} else {
	$OUTPUT = pretake_display();
}

$OUTPUT .= mkQuicklinks (
	ql("stock-add.php", "Add Stock"),
	ql("stock-view.php", "View Stock")
);

require ("template.php");

function pretake_display()
{
	$OUTPUT = "
	<center>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='pretake_update' />
	<input type='hidden' name='offset' value='0' />
	<input type='hidden' name='limit' value='".OFFSET_SIZE."' />
	<input type='hidden' name='new' value='1' />
	<table ".TMPL_tblDflts.">
		<tr bgcolor='".bgcolorg()."'>
			<td>
				This will start a new <em>Stock Take</em> and remove all previous
				uncompleted pages <input type='submit' value='OK' />
			</td>
		</tr>
	</table>
	</form>
	</center>";
	
	return $OUTPUT;
}

function pretake_print()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["offset"] = 0;
	$fields["limit"] = OFFSET_SIZE;
	
	extract ($fields, EXTR_SKIP);
	
	$sql = "SELECT stkid, stkcod, stkdes FROM cubit.stock
			ORDER BY stkcod ASC LIMIT $limit OFFSET $offset";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	$stock_out = "";
	while (list($stkid, $stkcod, $stkdes) = pg_fetch_array($stock_rslt)) {
		$stock_out .= "
		<tr>
			<td>$stkcod</td>
			<td>$stkdes</td>
			<td width='10%' style='border-bottom: 1px solid #000'>&nbsp;</td>
		</tr>";
	}
	
	$OUTPUT = "
	<style>
		th { text-align: left }
	</style>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<td><h2>Pre Stock Take</h2></td>
			<td align='right'><h3>Page ".page_number($offset, $limit)."</h3>
		</tr>
	</table>
	<table ".TMPL_tblDflts." width='100%'>
		<tr>
			<th align='left'>Stock Code</th>
			<th align='left'>Stock Description</th>
			<th align='left'>Quantity</th>
		</tr>
		$stock_out
	</table>";
	
	require ("tmpl-print.php");
}

function pretake_update()
{
	extract ($_REQUEST);
	
	pglib_transaction("BEGIN");
	
	$page = page_number($offset);
	
	if (isset($new) && $new) {
		$sql = "DELETE FROM cubit.stock_take";
		db_exec($sql) or errDie("Unable to remove old stock take.");
	}
	
	$sql = "SELECT stkid FROM cubit.stock 
			ORDER BY stkcod ASC LIMIT $limit OFFSET $offset";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	while (list($stkid) = pg_fetch_array($stock_rslt)) {
		$sql = "INSERT INTO cubit.stock_take (stkid, page)
				VALUES ('$stkid', '$page')";
		db_exec($sql) or errDie("Unable to add to stock take.");
	}
	
	$sql = "SELECT stkid FROM cubit.stock
			ORDER BY stkcod ASC LIMIT $limit OFFSET $offset";
	db_exec($sql) or errDie("Unable to retrieve stock take.");
	
	$next_page = $page + 1;
	$next_offset = page_offset($next_page);
	
	if ($next_page <= total_pages()) {
		$button = "<input type='submit' value='Page $next_page' />";
	} else {
		$button = "
		<input type='button' value='Post Stock Take'
		onclick='javascript:move(\"stock_take_post.php\")' />";
	}
	
	pglib_transaction("COMMIT");
	
	$OUTPUT = "
	<script>
		printer(\"".SELF."?key=pretake_print&offset=$offset&limit=$limit\");
	</script>
	<center>
	<h3>Pre Stock Take</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='pretake_update' />
	<input type='hidden' name='limit' value='$limit' />
	<input type='hidden' name='offset' value='$next_offset' />
	$button
	</form>
	</center>";
	
	return $OUTPUT;
}
	

function page_number($offset)
{
	$sql = "SELECT count(stkid) FROM cubit.stock";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_count = pg_fetch_result($stock_rslt, 0);
	
	return intval(($offset / OFFSET_SIZE) + 1);
}

function page_offset($page_num)
{
	return ($page_num - 1) * OFFSET_SIZE;
}

function total_pages()
{
	$sql = "SELECT count(stkid) FROM cubit.stock";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve total pages.");
	$stock_count = pg_fetch_result($stock_rslt, 0);
	
	return intval(($stock_count / OFFSET_SIZE) + 1);
}
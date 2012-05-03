<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#
require ("settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printStk($_POST);
			break;
		 case "export":
			$OUTPUT = export($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
    # Display default output
    $OUTPUT = slct();
}

require ("template.php");




# Default view
function slct()
{

	# Select the stock category
	db_connect();
	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no stock categories in Cubit.";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
		}
	}
	$cats .= "</select>";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
		}
	}
	$class .= "</select>";

	$wh_rslt = new dbSelect("warehouses", "exten");
	$wh_rslt->run();

	$warehouses = "<select name='whid' style='width: 167'>";
	while ($wh_data = $wh_rslt->fetch_array()) {
		$warehouses .= "<option value='$wh_data[whid]'>$wh_data[whname]</option>";
	}
	$warehouses .= "</select>";

	/* cut code
	<input type=hidden name=key value=view>
	<tr><th colspan=2>Store</th></tr>
	<tr class='bg-odd'><td align=center colspan=2>$whs</td></tr>
	<tr><td><br></td></tr>
	*/

	// Layout
	$view = "
		<h3>Stock Levels</h3>
		<table cellpadding='5'>
			<tr>
				<td>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='view'>
						<tr>
							<th colspan='2'>Criteria</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>Only show stock below minimun level</td>
							<td valign='bottom'><input type='checkbox' name='min' checked></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>By Category</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$cats</td>
							<td valign='bottom'><input type='submit' name='cat' value='View'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>By Classification</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$class</td>
							<td valign='bottom'><input type='submit' name='class' value='View'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>By Store</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$warehouses</td>
							<td valign='bottom'><input type='submit' name='warehouse' value='View' /></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<th colspan='2'>All Categories, Classifications and Stores</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center' colspan='2'>
								<input type='submit' name='all' value='View All'>
							</td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-report.php'>Stock Reports</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}




# show stock
function printStk ($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($min)) {
		$wh = "AND (units-alloc)<minlvl AND minlvl!=0";
	} else {
		$wh = "";
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE catid = '$catid' AND div = '".USER_DIV."' $wh ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE prdcls = '$clasid' AND div = '".USER_DIV."' $wh  ORDER BY stkcod ASC";
	} elseif(isset($warehouse)) {
		$v->isOk ($whid, "num", 1, 50, "Invalid Store.");
		$searchs = "SELECT * FROM stock WHERE whid='$whid' AND div='".USER_DIV."' $wh ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE div = '".USER_DIV."' $wh  ORDER BY stkcod ASC";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	# Set up table to display in
	$printStk = "
		<h3>Stock Levels</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Class</th>
				<th>Category</th>
				<th>Available</th>
				<th>Min Level</th>
				<th>Max Level</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "
			<li class='err'> There are no stock items found.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-lvl-rep.php'>Back</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-report.php'>Stock Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}

	$tot_avail = 0;

	while ($stk = pg_fetch_array ($stkRslt)) {
		# Calculate available
		$avail = sprint3($stk['units'] - $stk['alloc']);

		$printStk .= "
			<tr class='".bg_class()."'>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>$stk[classname]</td>
				<td align='right'>$stk[catname]</td>
				<td align='right'>$avail</td>
				<td align='right'>$stk[minlvl]</td>
				<td align='right'>$stk[maxlvl]</td>
			</tr>";
		$i++;

		if ($avail > 0)
			$tot_avail += $avail;
	}

	if(isset($min)) {
		$ex = "<input type='hidden' name='min' value='yes'>";
	} else {
		$ex = "";
	}

	if(isset($cat)) {
		$ex .= "<input type='hidden' name='cat' value='yes'>";
		$ex .= "<input type='hidden' name='catid' value='$catid'>";
	}

	if(isset($class)) {
		$ex .= "<input type='hidden' name='class' value='yes'>";
		$ex .= "<input type='hidden' name='clasid' value='$clasid'>";
	}

	if(isset($all)) {
		$ex .= "<input type='hidden' name='all' value='yes'>";
	}

	$printStk .= "
			<tr class='".bg_class()."'>
				<td colspan='4'>Total:</td>
				<td align='right'>".sprint3($tot_avail)."</td>
			</tr>
			<tr><td><br></td></tr>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='export'>
			$ex
			<tr>
				<td colspan='2'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</form>
		</table>
	    <p>
		<table ".TMPL_tblDflts." width='15%'>
	        <tr><td><br></td></tr>
	        <tr>
	        	<th>Quick Links</th>
	        </tr>
			<tr class='".bg_class()."'>
				<td><a href='stock-report.php'>Stock Reports</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $printStk;

}




function export ($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($min)) {
		$wh = "AND (units-alloc)<minlvl AND minlvl!=0";
	} else {
		$wh = "";
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE catid = '$catid' AND div = '".USER_DIV."' $wh ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE prdcls = '$clasid' AND div = '".USER_DIV."' $wh  ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE div = '".USER_DIV."' $wh  ORDER BY stkcod ASC";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}



	# Set up table to display in
	$printStk = "
		<h3>Stock Levels</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Code</th>
				<th>Description</th>
				<th>Class</th>
				<th>Category</th>
				<th>Available</th>
				<th>Min Level</th>
				<th>Max Level</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "
			<li class='err'> There are no stock items found.</li>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-lvl-rep.php'>Back</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='stock-report.php'>Stock Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}

	$tot_avail = 0;
	while ($stk = pg_fetch_array ($stkRslt)) {
		# Calculate available
		$avail = sprint3($stk['units'] - $stk['alloc']);

		$printStk .= "
			<tr>
				<td>$stk[stkcod]</td>
				<td>$stk[stkdes]</td>
				<td>$stk[classname]</td>
				<td align='right'>$stk[catname]</td>
				<td align='right'>$avail</td>
				<td align='right'>$stk[minlvl]</td>
				<td align='right'>$stk[maxlvl]</td>
			</tr>";
		$i++;

		if ($avail > 0)
			$tot_avail += $avail;
	}

	$printStk .= "
			<tr>
				<td colspan='4'>Total:</td>
				<td align='right'>".sprint3($tot_avail)."</td>
			</tr>
		</table>";

	$OUTPUT = $printStk;

	include("xls/temp.xls.php");
	Stream("Report", $OUTPUT);

}



?>

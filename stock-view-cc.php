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

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whid'>";
	$sql = "SELECT * FROM warehouses ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "There are no Warehouses found in Cubit.";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
		}
	}
	$whs .="</select>";

	# Select the stock category
	db_connect();
	$cats= "<select name='catid'>";
	$sql = "SELECT catid,cat,catcod FROM stockcat ORDER BY cat ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			return "<li>There are no stock categories in Cubit.";
	}else{
			while($cat = pg_fetch_array($catRslt)){
					$cats .= "<option value='$cat[catid]'>($cat[catcod]) $cat[cat]</option>";
			}
	}
	$cats .="</select>";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
			return "<li>There are no Classifications in Cubit.";
	}else{
			while($clas = pg_fetch_array($clasRslt)){
					$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
			}
	}
	$class .="</select>";

	//layout
	$view = "<h3>View Stock</h3>
	<table cellpadding=5><tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post name=form>
			<input type=hidden name=key value=view>
			<tr><th colspan=2>Store</th></tr>
			<tr class='bg-odd'><td align=center colspan=2>$whs</td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Category</th></tr>
			<tr class='bg-odd'><td align=center>$cats</td><td valign=bottom><input type=submit name=cat value='View'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Classification</th></tr>
			<tr class='bg-odd'><td align=center>$class</td><td valign=bottom><input type=submit name=class value='View'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>All Categories and Classifications</th></tr>
			<tr class='bg-odd'><td align=center colspan=2><input type=submit name=all value='View All'></td></tr>
			</form>
		</table>
	</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-even'><td><a href='main.php'><</a></td></tr>
	</table>";

	return $view;
}

# show stock
function printStk ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND catid = '$catid' ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND prdcls = '$clasid' ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' ORDER BY stkcod ASC";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printStk = "
    <h3>View Stock</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Code</th><th>Description</th><th>Class</th><th>On Hand</th><th>Cost Amount</th><th>Allocated</th><th>On order</th><th colspan=6>Options</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $stkRslt = db_exec ($searchs) or errDie ("Unable to retrieve stocks from database.");
	if (pg_numrows ($stkRslt) < 1) {
		return "<li class=err> There are no stock items found.</li>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
			<tr><td><br></td></tr>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-even'><td><a href='stock-view-cc.php'>Retry</a></td></tr>
			<tr class='bg-odd'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}

	while ($stk = pg_fetch_array ($stkRslt)) {
		# get category account name
		db_connect();
		$sql = "SELECT cat FROM stockcat WHERE catid = '$stk[catid]'";
		$catRslt = db_exec($sql);
		$cat = pg_fetch_array($catRslt);

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printStk .= "<tr bgcolor='$bgColor'><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$stk[prdcls]</td><td align=right>$stk[units] x $stk[suom]</td><td align=right>".CUR." $stk[csamt]</td><td align=right>$stk[alloc] x $stk[suom]</td><td align=right>$stk[ordered] x $stk[suom]</td>
		<td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>View Report</a></td><td><a href='stock-det.php?stkid=$stk[stkid]'>Details</a></td><td><a href='stock-edit.php?stkid=$stk[stkid]'>Edit</a></td><td><a href='pos.php?id=$stk[stkid]'>Allocate Barcode</a></td>";

		if($stk['blocked'] == 'y'){
			$printStk .= "<td><a href='stock-unblock.php?stkid=$stk[stkid]'>Unblock</a></td>";
		}else{
			$printStk .= "<td><a href='stock-block.php?stkid=$stk[stkid]'>Block</a></td>";
		}

		if(($stk['units'] < 1) && ($stk['alloc'] < 1)){
			$printStk .= "<td><a href='stock-rem.php?stkid=$stk[stkid]'>Remove</a></td>";
		}elseif($stk['alloc'] > 0){
			$printStk .= "<td><a href='#' onclick='openwindow(\"stock-alloc.php?stkid=$stk[stkid]\")'>View Allocation</a></td></tr>";
		}else{
			$printStk .= "<td></td></tr>";
		}
		$i++;
	}

	$printStk .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printStk;
}
?>

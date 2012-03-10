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


if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "view":
			$OUTPUT = printStk($HTTP_POST_VARS);
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

	db_connect ();

	$get_defwh = "SELECT * FROM set WHERE label = 'DEF_WH' LIMIT 1";
	$run_defwh = db_exec($get_defwh) or errDie("Unable to get default store information");
	if(pg_numrows($run_defwh) < 1){
		$defwhid = "";
	}else {
		$darr =	pg_fetch_array($run_defwh);
		$defwhid = $darr['value'];
	}

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whid'>";
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."' ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "There are no Warehouses found in Cubit.";
	}else{
		while($wh = pg_fetch_array($whRslt)){
			if($wh['whid'] == $defwhid){
				$whs .= "<option value='$wh[whid]' selected>($wh[whno]) $wh[whname]</option>";
			}else {
				$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
		}
	}
	$whs .="</select>";

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
	$cats .="</select>";

	# Select classification
	$class = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
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
	$view = "<h3>Stock Taking</h3>
	<table cellpadding=5><tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='".SELF."' method=post name=form>
			<input type=hidden name=key value=view>
			<tr><th colspan=2>Store</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2>$whs</td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Category</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$cats</td><td valign=bottom><input type=submit name=cat value='View'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>By Classification</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$class</td><td valign=bottom><input type=submit name=class value='View'></td></tr>
			<tr><td><br></td></tr>
			<tr><th colspan=2>All Categories and Classifications</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td align=center colspan=2><input type=submit name=all value='View All'></td></tr>
			</form>
		</table>
	</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# show stock
function printStk ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whid, "num", 1, 50, "Invalid Warehouse.");

	if(isset($cat)){
		$v->isOk ($catid, "num", 1, 50, "Invalid Category.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND catid = '$catid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
	}elseif(isset($class)){
		$v->isOk ($clasid, "num", 1, 50, "Invalid Classification.");
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND prdcls = '$clasid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
	}elseif(isset($all)){
		$searchs = "SELECT * FROM stock WHERE whid = '$whid' AND div = '".USER_DIV."' ORDER BY stkcod ASC";
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
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stock-taking.php'>Back</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stock-add.php'>Add Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}

	# Set up table to display in
	$printStk = "
    <center><h3>Stock Taking</h3>
    <table cellpadding='2' cellspacing='0' border=1 width=750 bordercolor='#000000'>
    <tr><th width=100>CODE</th><th width=350>DESCRIPTION</th><th width=150>SERIAL No.</th><th width=50>UNITS</th><th width=100 colspan=2></th></tr>";

	while ($stk = pg_fetch_array ($stkRslt)){
		// $printStk .= "<tr valign=top><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td><br></td><td>$stk[units]</td><td><br></td><td><br></td></tr>";
		if($stk['serd'] == "yes"){
			# get serial numbers
			$sers = ext_getavserials($stk['stkid']);
			if(count($sers) < $stk['units']){
				$noalloc = ($stk['units'] - count($sers));
				$printStk .= "<tr valign=top><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>Not Allocated</td><td align=right><br></td><td><br></td><td><br></td></tr>";
			}
			foreach($sers as $skey => $ser){
				$printStk .= "<tr valign=top><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>$ser[serno]</td><td align=right><br></td><td><br></td><td><br></td></tr>";
			}
		}else{
			$printStk .= "<tr valign=top><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td>Not Serialized</td><td align=right><br></td><td><br></td><td><br></td></tr>";
		}
	}

	$printStk .= "</table>";

	$OUTPUT = $printStk;
	require("tmpl-print.php");
}
?>

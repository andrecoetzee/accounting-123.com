<?

# Some debtors control functions
// require settings.php with this script

function isdebtors($accid){
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE debtacc = '$accid'";
	$deptRslt = db_exec($sql) or errDie("Could not retrieve departments Information from the Database.",SELF);
	if(pg_numrows($deptRslt) > 0){
		return true;
	}else{
		return false;
	}
}

function debtors($tran, $cacc){
	db_connect();
	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class=err> There are no Customers in Cubit.";
	}
	$custs = "<select name=cusnum>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";

	$debtors = "
	<h3>You selected a debtors account</h3>
	<h4>Select Customer</h4>
	<form action='cust-trans.php' method=get>
	<input type=hidden name=tran value='$tran'>
	<input type=hidden name=cacc value='$cacc'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Select Customer</td><td>$custs</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=center><input type=submit value='Continue &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../customers-view.php'>View Customers</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $debtors;
}
?>
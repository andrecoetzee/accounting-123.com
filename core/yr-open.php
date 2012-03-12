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

# prd-close.php :: close current period
##

# get settings
require("settings.php");
exit;
# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "view":
			$OUTPUT = view();
			break;
		default:
			$OUTPUT = main();
	}
} else {
        # Display default output
        $OUTPUT = main();
}

# Get templete
require("template.php");

function main()
{
	db_conn("audit");
	$sql = "SELECT * FROM closedprd";
	$prdRslt = db_exec($sql);

	core_connect("core");
	$sql = "SELECT * FROM active";
	$rslt = db_exec($sql);
	$rows = pg_numrows($rslt);
	if(pg_numrows($rslt) > 0){
		$act = Pg_fetch_array($rslt);
		db_conn($act['prddb']);
		$sql = "SELECT * FROM transect";
		$tranRslt = db_exec($sql);
	}else{
		$tranRslt = $prdRslt;
	}

	if(pg_numrows($tranRslt) > 0 || pg_numrows($prdRslt) > 0){
		$main = "
		<br>
		<br>
		<center>
		<h3>Opening a Financial Year</h3>
		( i ) A year has already been open, Close this year to open another year. ( i )
		<br>
		<br>
		<br>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>
		</center>";
	}else{

		$main = "
		<br>
		<br>
		<center>
		<h3>Opening a Financial Year</h3>
		Today's Date is  <b>".date("D d M Y")."</b><br>Are your sure you want to open a financial year on this date?
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=view>
		<br>
		<input type=button value='Cancel' Onclick='javascript:history.back()'>
		<input type=submit value='OK'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>
		</form>
		</center>";
	}

	return $main;
}

function view()
{
	$view = "
	<center>
	<h3>Please Select a financial year</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<tr><th>Financial year names</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
	<td align=center>
	<select name=yrname>";
	core_connect();
	$sql = "SELECT * FROM year ORDER BY yrname";
	$yrs = db_exec($sql);
	if(pg_numrows($yrs) < 1){
		return "<center><li class=err><b>Financial Year names were not found on the Database.<br>Please Follow cubit instalation and set financial year names.";
	}
	$i=0;

	while($yr = pg_fetch_array($yrs)){
		$view .= "<option value='$yr[yrname]'>$yr[yrname]</option>";
	}

	$view .= "
	<select>
	</td></tr>
	</table>
	<br>
	<input type=submit value='Enter >'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $view;
}


function confirm($_POST)
{
        # get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
        require_lib("validate");
		$v = new  validate ();
		$v->isOk ($yrname, "string", 1, 14, "Invalid Year Name.");

        # display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>".$e["msg"];
			}
			$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}

		core_connect();
		$sql = "SELECT * FROM year WHERE yrname='$yrname'";
		$yrs = db_exec($sql);
		$yr = pg_fetch_array($yrs);
		if($yr['closed'] == 'y'){
			return "<center><li class=err>ERROR : The Selected Financial year : <b>$yrname</b> has been closed.
			<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		}


		$confirm ="
		<center>
		<h3> Open Financial year </h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=write>
		<input type=hidden name=yrname value=$yrname>
		<input type=hidden name=yrdb value=$yr[yrdb]>
		<tr><th>Financial year name</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
		<td align=center>$yrname</td>
		</tr>
		</table>
		<br>
		<input type=submit value='Enter >'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

		return $confirm;
}

function write($_POST){
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($yrname, "string", 1, 14, "Invalid Year Name.");
	$v->isOk ($yrdb, "string", 1, 4, "Invalid Year Database.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	// Get first period name from range get range
	core_connect();
	$sql = "SELECT * FROM range";
	$Rslt = db_exec($sql);
	if(pg_numrows($Rslt) < 1){
		$OUTPUT = "<center><li class=err>ERROR : The Financial year Period range was not found on Database, Please make sure that everything is set during instalation.";
		require("template.php");
	}
	$range = Pg_fetch_array($Rslt);

	// Months array
	$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

	// Update Active Year Db and name
	db_conn("core");

	$sql = "UPDATE active SET yrdb = '$yrdb', yrname = '$yrname',  prddb = '$range[start]', prdname='".$months[$range['start']]."'";
	$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);

	if(pg_cmdtuples($rslt) < 1){
		$sql = "INSERT INTO active (yrdb, yrname, prddb, prdname) VALUES ('$yrdb', '$yrname', '$range[start]', '".$months[$range['start']]."')";
		$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);
	}

	$write ="<center>
	<br>
	<h3> Selected Financial year has been opened and activated</h3>
	<input type=button value='Main' Onclick=document.location='../main.php'></a>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}


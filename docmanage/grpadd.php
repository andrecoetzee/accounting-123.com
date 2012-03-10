<?
require("../settings.php");

#decide what to do

if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = con_data ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write_data ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_data ();
	}
} else {
	$OUTPUT = get_data ();
}

#display output
require("../template.php");
#enter new data
function get_data ()
{
        global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	if(!(isset($grpname))) {
		$grpname="";
		$unit="";
			
	}//end if
	
	//
	
	$S1="SELECT * FROM grpadd ORDER BY grpname ";
	$Ri=db_exec($S1) or errDie("Unable to get data.");
	
	
	if(pg_num_rows($Ri)<1) {
		return "no Group selected.";
	}//end if
	
	
	// Set up table to display in
	//$cons="<table>";
	$cons = "
		<h3>Group Details</h3>
		<td align=center>
		<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300 bgcolor='".TMPL_tblDataColor1."' >
		<tr><th>Group Name</th><th colspan=2>Options</th></tr>";

	while($data=pg_fetch_array($Ri)) {
		$cons.="<tr><td bgcolor='".TMPL_tblDataColor1."'>$data[grpname]</td><td><a
		 href='grpedit.php?id=$data[id]'>Edit</a></td><td><a 
	         href='grprem.php?id=$data[id]'>Delete</td></tr>";
	}//end while
	
$get_data="
	 <h3>Add New Group</h3>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <input type=hidden name=key value=confirm>
	 <tr><th colspan=2>Group Details</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Group Name</td><td align=center><input type=text size=27 name=grpname value='$data[grpname]'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."' ><td >Group Unit</td><td align=center>
		<select name=unit  value='$unit' size='3' multiple='1'>
		<option value='none'>None</option>
		<option value='default' selected>Default</option>
		</select></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>$cons";
	
	
	return $get_data;
}
//get errors	
function enter_err($HTTP_POST_VARS,$err=""){

        global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	if(!(isset($grpname))) {
		$grpname="";
		$unit="";
	}
	
	//
	
	$S1="SELECT * FROM grpadd ORDER BY grpname ";
	$Ri=db_exec($S1) or errDie("Unable to get data.");
	
	
	if(pg_num_rows($Ri)<1) {
		return "no group selected.";
	}
	
	// Set up table to display in
	//$cons="<table>";
	$cons = "
		<h3>Group Details</h3>
		<td align=center>
		<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300 bgcolor='".TMPL_tblDataColor1."' >
		<tr><th>GroupName</th><th colspan=2>Options</th></tr>";

	while($data=pg_fetch_array($Ri)) {
		$cons.="<tr><td bgcolor='".TMPL_tblDataColor1."'>$data[grpname]</td><td><a
		 href='grpedit.php?id=$data[id]'>Edit</a></td><td><a 
	         href='grprem.php?id=$data[id]'>Delete</td></tr>";
		 
	}
	
$get_data="
	 <h3>New Group Details</h3>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <tr><td>$err<br></td></tr>
	 <input type=hidden name=key value='confirm'>
	 <tr><th colspan=2>Group Details</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Group Name</td><td align=center><input type=text size=27 name=grpname value='$grpname'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."' ><td >Group Unit</td><td align=center>
		<select name=unit  value='$unit' size='3' multiple='1'>
		<option value='none'>None</option>
		<option value='default' selected>Default</option>
		</select></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>$cons";
	
	
	return $get_data;	
}

#confirm new data	
function con_data ($HTTP_POST_VARS)
{
# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	
	//confirm inserted data
	# validate input
	require_lib("validate");
	$v = new validate ();
	# Limit field lengths as per database settings
	
	$v->isOk ($grpname, "string", 1, 15, "Invalid group name.");
	$v->isOk ($unit, "string", 1, 15, "Invalid group unit.");
	
	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"]."</li>";
		}
		//get errors
		return enter_err($HTTP_POST_VARS, $theseErrors);
		exit;
		
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

	$con_data="<h3>Confirm Group Details</h3>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value='write'>
		<input type=hidden name=grpname value='$grpname'>
		<input type=hidden name=unit value='$unit'>
		
		<tr><th colspan=2>Group Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Group name</td><td align=center>$grpname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Group unit</td><td align=center>$unit</td></tr>
		<tr><td colspan=2 align=left><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";
	
	return $con_data;
}

# write new data
function write_data ($HTTP_POST_VARS)
{
	//$date=date("Y-m-d");
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	
	db_conn('cubit');
	$Sl="INSERT INTO grpadd(grpname) VALUES ('$grpname')";
	$Ri=db_exec($Sl) or errDie("unable to insert into grpadd.");
	
$write_data="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Group Added</th></tr>
	<tr class=datacell><td>$grpname has been added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Document Management</a></td></tr>
        
	</table>";

	return $write_data; 
	
}
?>

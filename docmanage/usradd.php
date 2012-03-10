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

	if(!(isset($username))) {
		$username="";
		$name="";
		$email="";
		$cell="";
		
		
	}//end if
	
	//
	
	$S1="SELECT * FROM usradd ORDER BY username ";
	$Ri=db_exec($S1) or errDie("Unable to get data.");
	
	
	if(pg_num_rows($Ri)<1) {
		return "no user selected.";
	}//end if
	
	
	// Set up table to display in
	//$cons="<table>";
	$cons = "
		<h3>User Details</h3>
		<td align=center>
		<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300 bgcolor='".TMPL_tblDataColor1."' >
		<tr><th>Name</th><th colspan=2>Options</th></tr>";

	while($data=pg_fetch_array($Ri)) {
		$cons.="<tr><td bgcolor='".TMPL_tblDataColor1."'>$data[username]</td><td><a
		 href='usredit.php?id=$data[id]'>Edit</a></td><td><a 
	         href='usrem.php?id=$data[id]'>Remove</td></tr>";
	}//end while
	
	$Cons ="<select size=1 name=Con>
        <option value='No'>No</option>
        <option selected value='Yes'>Yes</option>
        </select>";
	
	$Grps ="<select size=1 name=Con>
        <option value='Grp1'>Group test</option>
        <option selected value='Grp2'>Gruop test2</option>
        </select>";
	
	
$get_data="
	 <h3>Add New User</h3>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <input type=hidden name=key value=confirm>
	 <tr><th colspan=2>Personal Details</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Username</td><td><input type=text size=20 name=username value='$data[username]'> must not contain spaces</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Password</td><td><input type=password size=20 name=password></td></tr>
         <tr bgcolor='".TMPL_tblDataColor1."'><td>Confirm password</td><td><input type=password size=20 name=password2></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td><input type=text size=20 name=name value='$name'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td><input type=text size=20 name=email value='$email'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Cellphone</td><td><input type=text size=20 name=cell value='$cell'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Additional</td><td>Email Notification<input type=checkbox name=notify></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Private</td><td align=center>$Cons</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Initial Group</td><td align=center>$Grps</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>$cons";
	
	
	return $get_data;
}
//get errors	
function enter_err($HTTP_POST_VARS,$err=""){

        global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	if(!(isset($username))) {
		$username="";
		$name="";
		$email="";
		$cell="";
	}
	
	//
	
	$S1="SELECT * FROM usradd ORDER BY username ";
	$Ri=db_exec($S1) or errDie("Unable to get data.");
	
	
	if(pg_num_rows($Ri)<1) {
		return "no user selected.";
	}
	
	// Set up table to display in
	//$cons="<table>";
	$cons = "
		<h3>User Details</h3>
		<td align=center>
		<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300 bgcolor='".TMPL_tblDataColor1."' >
		<tr><th>Name</th><th colspan=2>Options</th></tr>";

	while($data=pg_fetch_array($Ri)) {
		$cons.="<tr><td bgcolor='".TMPL_tblDataColor1."'>$data[username]</td><td><a
		 href='usredit.php?id=$data[id]'>Edit</a></td><td><a 
	         href='usrem.php?id=$data[id]'>Remove</td></tr>";
		 
	}
	
	$Cons ="<select size=1 name=Con>
        <option value='No'>No</option>
        <option selected value='Yes'>Yes</option>
        </select>";
	
	$Grps ="<select size=1 name=Con>
        <option value='Grp1'>Group test</option>
        <option selected value='Grp2'>Gruop test2</option>
        </select>";
	
$get_data="
	 <h3>New User Details</h3>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <tr><td>$err<br></td></tr>
	 <input type=hidden name=key value='confirm'>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Username</td><td><input type=text size=20 name=username value='$username'> must not contain spaces</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Password</td><td><input type=password size=20 name=password></td></tr>
         <tr bgcolor='".TMPL_tblDataColor1."'><td>Confirm password</td><td><input type=password size=20 name=password2></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td><input type=text size=20 name=name value='$name'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td><input type=text size=20 name=email value='$email'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Cellphone</td><td><input type=text size=20 name=cell value='$cell'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Additional</td><td>Email Notification<input type=checkbox name=notify></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Private</td><td align=center>$Cons</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Initial Group</td><td align=center>$Grps</td></tr>
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
	$v->isOk ($username,"string", 0, 20, "Invalid  username name.");
	$v->isOk ($name, "string", 1, 20, "Invalid name.");
	$v->isOk ($email, "email", 1, 30, "Invalid email address.");
	$v->isOk ($cell, "string", 1, 20, "Invalid mobile no.");
	$v->isOk ($Cons,"string",2 ,3, "Invalid private.");
	$v->isOk ($Grps,"string",2 ,3, "Invalid groups   .");
	
	
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

	$con_data="<h3>Confirm User Details</h3>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value='write'>
		<input type=hidden name=username value='$username'>
		<input type=hidden name=name value='$name'>
		<input type=hidden name=email value='$email'>
		<input type=hidden name=cell value='$cell'>
		<input type=hidden name=Cons value='$Cons'>
		<input type=hidden name=Grps value='$Grps'>
		
		
		<tr><th colspan=2>User Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Username</td><td align=center>$username</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center>$name</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td align=center>$email</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Cellphone</td><td align=center>$cell</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Private</td><td align=center>$Cons</td></tr>
	 	<tr bgcolor='".TMPL_tblDataColor1."'><td>Initial Group</td><td align=center>$Grps</td></tr>
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
	$Sl="INSERT INTO usradd(username,name,email,cell) VALUES ('$username','$name','$email','$cell')";
	$Ri=db_exec($Sl) or errDie("unable to insert into usradd.");
	
$write_data="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>User Added</th></tr>
	<tr class=datacell><td>$username has been added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'> Document Management</a></td></tr>
        
	</table>";

	return $write_data; 
	
}
?>

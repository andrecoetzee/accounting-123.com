<?
require ("../settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = con_data ($_POST);
			break;
		case "write":
			$OUTPUT = write_data ($_POST);
			break;
		default:
			$OUTPUT = get_data ($_GET);
	}
} else {
	$OUTPUT = get_data ($_GET);
}

# display output
require ("../template.php");
# enter new data
function get_data ($_GET)
{

foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	
require_lib("validate");	
  db_conn('cubit');

  # write to db
  $S1 = "SELECT * FROM usradd WHERE id='$id'";
  $Ri = db_exec($S1) or errDie ("Unable to access database.");
  if(pg_numrows($Ri)<1){return "User not Found";
  }
  $Data = pg_fetch_array($Ri);
  $cons="<table>";
	
	while($data=pg_fetch_array($Ri)) {
		$cons.="<tr><td bgcolor='".TMPL_tblDataColor1."'>$data[username]</td><td><a href='usredit.php?id=$data[id]'>Edit</a></td></td></tr>";
	}
	
	$cons.="</table>";

	$Cons ="<select size=1 name=Con>
        <option value='No'>No</option>
        <option selected value='Yes'>Yes</option>
        </select>";
	
	$Grp ="<select size=1 name=Con>
        <option value='Grp1'>Group test</option>
        <option selected value='Grp2'>Group test2</option>
        </select>";

$get_data="
	 <h3>Add New User</h3>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <input type=hidden name=key  value='confirm'>
	 <input type=hidden name=id value=$id>
	 <tr><th colspan=2>Personal Details</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Username</td><td><input type=text size=20 name=username value='$Data[username]'> must not contain spaces</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Password</td><td><input type=password size=20 name=password></td></tr>
         <tr bgcolor='".TMPL_tblDataColor1."'><td>Confirm password</td><td><input type=password size=20 name=password2></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td><input type=text size=20 name=name value='$Data[name]'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td><input type=text size=20 name=email value='$Data[email]'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Cellphone</td><td><input type=text size=20 name=cell value='$Data[cell]'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Additional</td><td>Email Notification<input type=checkbox name=notify></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."'><td>Private</td><td align=center>$Cons</td></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Initial Group</td><td align=center>$Grp</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='usradd.php'>Add User</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='usredit.php'>Edit User </a></td></tr>
	</table>";
	
	
	return $get_data;
}

#confirm new data	
function con_data ($_POST)
{
# get vars
	foreach ($_POST as $key => $value) {
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
	
	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"]."</li>";
		}
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
		<input type=hidden name=id  value='$id'>
		
		<tr><th colspan=2>User Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Username</td><td align=center>$username</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center>$name</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Email</td><td align=center>$email</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Cellphone</td><td align=center>$cell</td></tr>
		<tr><td colspan=2 align=left><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";
	
	return $con_data;
}

# write new data
function write_data ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	
	
	db_conn('cubit');

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class=err>Unable to edit user(TB)</li>";
	}

	$Sl="SELECT * FROM usradd WHERE id='$id'";
	$Ri=db_exec($Sl) or errDie("Unable to get user details.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid user.";
	}

	$cdata=pg_fetch_array($Ri);

	# write to db
	$S1 = "UPDATE usradd SET username='$username',name='$name',email='$email',cell='$cell' WHERE id  = '$id'";
	$Ri = db_exec($S1) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Ri);


	if (!pglib_transaction("COMMIT")) {
		return "<li class=err>Unable to edit user. (TC)</li>";
	}

	$write_data="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>User Added</th></tr>
	<tr class=datacell><td>$username has been added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='docman-index.php'>Document Management</a></td></tr>
	</table>";

	return $write_data; 
}
?>

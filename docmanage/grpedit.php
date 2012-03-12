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
  $S1 = "SELECT * FROM grpadd WHERE id='$id'";
  $Ri = db_exec($S1) or errDie ("Unable to access database.");
  if(pg_numrows($Ri)<1){return "Group not Found";
  }
  $Data = pg_fetch_array($Ri);
  $cons="<table>";
	
	while($data=pg_fetch_array($Ri)) {
		$cons.="<tr><td bgcolor='".TMPL_tblDataColor1."'>$data[grpname]</td><td><a href='grpedit.php?id=$data[id]'>Edit</a></td></td></tr>";
	}
	
	$cons.="</table>";



$get_data="
	 <h3>Add New Group</h3>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <input type=hidden name=key  value='confirm'>
	 <input type=hidden name=id value=$id>
	 <tr><th colspan=2>Group Details</th></tr>
	 <tr bgcolor='".TMPL_tblDataColor1."'><td>Group Name</td><td align=center><input type=text size=27 name=grpname value='$Data[grpname]'></td></tr>
	 <tr bgcolor='".TMPL_tblDataColor2."' ><td >Group Unit</td><td align=center>
		<select name=unit  value='$Data[unit]' size='3' multiple='1'>
		<option value='none'>None</option>
		<option value='default' selected>Default</option>
		</select></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='grpadd.php'>Add Group</a></td></tr>
	
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
	$v->isOk ($grpname,"string", 0, 15, "Invalid  group name.");
	$v->isOk ($unit, "string", 1, 15, "Invalid group unit.");
	
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
	
	$con_data="<h3>Confirm Group Details</h3>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value='write'>
		<input type=hidden name=grpname value='$grpname'>
		<input type=hidden name=unit value='$unit'>
		<input type=hidden name=id  value='$id'>
		
		<tr><th colspan=2>Group Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Group Name</td><td align=center>$grpname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Document type</td><td align=center>$unit</td></tr>
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
		return "<li class=err>Unable to edit group(TB)</li>";
	}

	$Sl="SELECT * FROM grpadd WHERE id='$id'";
	$Ri=db_exec($Sl) or errDie("Unable to get group details.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid group.";
	}

	$cdata=pg_fetch_array($Ri);

	# write to db
	$S1 = "UPDATE grpadd SET grpname='$grpname',unit='$unit' WHERE id  = '$id'";
	$Ri = db_exec($S1) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Ri);


	if (!pglib_transaction("COMMIT")) {
		return "<li class=err>Unable to edit group. (TC)</li>";
	}

	$write_data="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Group Added</th></tr>
	<tr class=datacell><td>$grpname has been added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='docman-index.php'>Document Management</a></td></tr>
	</table>";

	return $write_data; 
}
?>

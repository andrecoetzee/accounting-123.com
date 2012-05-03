<?
require ("../settings.php");

// store the post vars in get vars, so that both vars can be accessed at once
// it is done this was around, so post vars get's higher priority and overwrites duplicated in get vars
if ( isset($_POST) ) {
	foreach( $_POST as $arr => $arrval ) {
		$_GET[$arr] = $arrval;
	}
}

// see what to do
if (isset ($_GET["key"])) {
	switch ($_GET["key"]) {
		case "delete":
		case "confirm_delete":
			$OUTPUT = deleteFolder();
			break;
		default:
			$OUTPUT = viewFolder ();
	}
} else {
	$OUTPUT = viewFolder ();
}

# display output
require ("../template.php");
# enter new data
function viewFolder () {
	global $_GET;
	global $user_admin;

  foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# 
  require_lib("validate");	
  db_conn('cubit');

  # write to db
  $S1 = "SELECT * FROM foladd WHERE id='$id'";
  $Ri = db_exec($S1) or errDie ("Unable to access database.");
  if(pg_numrows($Ri)<1){return "Folder not Found";
  }
  $Data = pg_fetch_array($Ri);

  

$busy_deleting = isset($_GET["key"]) && $_GET["key"] == "confirm_delete";

// only show this when not deleting
$viewFolder = "";
if ( ! ($busy_deleting) )
	$viewFolder .="<center><h3>Folder details</h3></center>";

	db_conn('cubit');


	$i=0;
	$conpers="";


	$Sl="SELECT * FROM foladd WHERE id='$Data[id]' ORDER BY folname";
	$Ri=db_exec($Sl) or errDie("Unable to get folders from db.");



$viewFolder .= "
<br>
<center>
	 <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	 <form action='".SELF."' method='post'>
	 <input type=hidden name=key  value='confirm'>
	 <tr class='bg-odd'><td>Folder Name</td><td align=center><input type=text size=27 name=folname value='$Data[folname]'></td></tr>
	 <tr class='bg-even' ><td >Document Type</td><td align=center>
		<select name=doctype  value='$Data[doctype]' size='5' multiple='1'>
		<option value='def' selected>Default</option>
		<option value='inv' >Invoice</option>
		<option value='emp1'>Employee Document</option>
		<option value='pur' >Purchases</option>
		</select></td></tr>
	</form>
	</table>";
	
// check if own entry own entry, and if it is, create the delete field, so the delete field doesn't display
// when it is not your contact
if ( $Data['folname'] == USER_NAME || $user_admin) {
	$DeleteField = "<a class=nav href=\"folrem.php?key=confirm_delete&id=$Data[id]\">
				Remove</a>";
} else {
	$DeleteField = "";
}
if ( $Data['folname'] == USER_NAME || $user_admin) {
	$AddField = "<a class=nav href=\"foladd.php?key=confirm_delete&id=$Data[id]\">
				Add</a>";
} else {
	$AddField = "";
}

// only add the following when not deleting
if ( ! ($busy_deleting) ) {
	$viewFolder .= "
	<tr>
		<td align=center colspan=2><font size=2><b>
			<a class=nav target=mainframe href=\"foledit.php?id=$Data[id]\" onClick='setTimeout(window.close,50);' >Edit </a> &nbsp;
			$DeleteField
		</b></font></td>
	</tr>
	<tr>
		<td align=center colspan=2><font size=2><b>
			<a class=nav target=mainframe href=\"foladdd.php?type=conn&id=$Data[id]\" onClick='setTimeout(window.close,50);' >Add </a> &nbsp;
		</b></font></td>
	</tr>";
}

$viewFolder .= "
</table>
$conpers
<p></center>";

return $viewFolder;
}


// function that deletes a contact
function deleteFolder() {
	global $_GET, $_SESSION;
	global $user_admin;

	$OUTPUT = "";

	if ( isset($_GET["key"]) && isset($_GET["id"]) ) {
		$id=$_GET["id"];
		$key=$_GET["key"];

		// first make sure it is this person's contact, or that the user is root
		if ( ! $user_admin ) {
			$rslt = db_exec("SELECT * FROM foladd WHERE id='$id' AND
				( by='$_SESSION[USER_NAME]' )");
			if ( pg_num_rows($rslt) <= 0 ) {
				return "You are not allowed to delete this folder entry!";
			}
		}
//two butons
		// check if a confirmation or deletion should occur (confirm_delete let's the cofirmation display)
		if ( $key == "confirm_delete" ) {
			$Sl="SELECT * FROM foladd WHERE id='$id'";
			$Ri=db_exec($Sl) or errDie("Unable to get folder details.");
			$cdata=pg_fetch_array($Ri);

			$OUTPUT .= "<font size=2><b>Are you sure you want to delete this folder:</b></font><br>";
			$OUTPUT .= viewFolder();
			$OUTPUT .= "
				<table><tr><td align=center>
					<form method=post action='".SELF."'>
						<input type=hidden name=key value='delete'>
						<input type=hidden name=id value='$id'>
						<input type=submit value=yes>
						
					</form>
				</td></tr></table>";
		} else if ( $key == "delete" ) {
			// delete it !!!!!!!
			$Ri = db_exec("DELETE FROM foladd WHERE id='$id' ");
			if ( pg_cmdtuples($Ri) <= 0 ) {
				$OUTPUT .= "Error Deleting Entry<br> Please check that it exists, else contact Cubit<br>";
			} else {
				$OUTPUT .= "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
			}
		}
	} else {
			$OUTPUT .= "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	}
	
	$link="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='docman-index.php'>Document Management</a></td></tr>";
	
	print $link;

	return $OUTPUT;
}

?>

<?

require ("../settings.php");

$OUTPUT = viewEmp ();


require ("../template.php");

##
# Functions
##

# view documents in db
function viewEmp ()
{
	# Connect to db
	db_connect ();

	# Get documents from db
	$documents = "";
	$documents1="";
	$i1 = 0;
	$i = 1;
	$sql = "SELECT * FROM document ORDER BY docid";
	$empRslt = db_exec ($sql) or errDie ("Unable to select documents from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No documents in database.<p>
	       ";
	}
	$enterEmp= 
	" <table width='40%' border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<td align=left nowrap><font size=2><b>
				</td>
			<td align=right nowrap>
				<form method=post action='".SELF."'>
					<input type=hidden name=fields value='typeid,typename,filename,options'>
					
					</form>
			</td>
		</tr>
		<tr>
			</td>
			<td width='20%' align=right nowrap>
				<form method=post action='".SELF."'>
				</select>
				</form>
			</td>
		</tr>
		<tr>
			<td width='100%' colspan=2>
				<table width='100%'>
					<tr>";
	while ($docid = pg_fetch_array ($empRslt)) {
		$documents .= "<tr class='".bg_class()."'><td>$docid[docid]</td><td>$i</td><td>$docid[typeid]</td><td>$docid[typename]</td><td>$docid[filename]</td><td><a href='editdel.php?docid=$docid[docid]'>Edit</a></td><td><a href='rem_condel.php?docid=$docid[docid]'>Completly Remove Contact</a></td><td><a href='newdel.php?docid=$docid[docid]'>Copy Contact Back to Main List</a></td></tr>\n";
		
		$i++;
		$i1++;
	}

	# Set up table & form
	$enterEmp .=
	"<h3>Employees</h3>

	<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>id</th><th>Nr.</th><th>Typeid</th><th>Type Nmae</th><th>Filename</th><th>Edit</th><th>Remove</th><th>Copy Contact Back to Main List</th></tr>
	$documents
	<tr class='".bg_class()."'><td colspan=8>Total: $i1</td></tr>
	</table>
	<p>
	<table border=7 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	";

	return $enterEmp;
}
?>

<?

require ("../settings.php");
require_lib("docman");

$OUTPUT = viewDoc ();


require ("../template.php");

##
# Functions
##

# view documents in db
function viewDoc ()
{
	# Connect to db
	db_connect ();

	# Get documents from db
	$documents = "";
	$documents1="";
	$i1 = 0;
	$i = 1;
	$sql = "SELECT * FROM document ORDER BY docid";
	$docRslt = db_exec ($sql) or errDie ("Unable to select documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		return "No documents in database.<p>
	       ";
	}
	$enterDoc= 
	" <table width='40%' border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<td align=left nowrap><font size=2><b>
				</td>
			<td align=right nowrap>
				<form method=post action='".SELF."'>
					<input type=hidden name=fields value='typeid,typename,filename'>
					
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
	while ($docid = pg_fetch_array ($docRslt)) {
		$documents .= "<tr class='".bg_class()."'><td>$docid[docid]</td><td>$i</td><td>$docid[typeid]</td><td>$docid[typename]</td><td>$docid[filename]</td>";
		
		$i++;
		$i1++;
	}

	# Set up table & form
	$enterDoc.=
	"<h3> Archieved Documents</h3>

	<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>id</th><th>Type</th><th>Type Name</th><th>Typename</th><th>Filename</th></tr>
	$documents
	<tr class='".bg_class()."'><td colspan=5>Total: $i1</td></tr>
	</table>
	
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='docman-index.php'> Document Management</a></td></tr>
	";

	return $enterDoc;
}
?>

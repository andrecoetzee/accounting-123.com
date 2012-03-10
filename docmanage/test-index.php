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


require ("../settings.php");
if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "view":
			$OUTPUT = printCat($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
	}
	


$OUTPUT = "<center>
<table border=0 width='90%'><tr>
<td valign=top width='33%'><table width='90%'>
<tr><td align=center><h3>Document Management</h3></td></tr>
<tr ><th colspan=2>Document Details</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href ='docadd.php' class=nav><b>Add New Document</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href ='docview.php' class=nav><b>View Documents</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href='doctypeadd.php' class=nav><b>Add Document Type</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href ='doctypeview.php' class=nav><b>View Document Type</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href='foladd.php' class=nav><b>Add New Folder</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href='usradd.php' class=nav><b> User Management</b></a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td nowrap><a href='grpadd.php' class=nav><b> Group Management</b></a></td></tr>
</table></td>";


	function OUTPUT ($HTTP_POST_VARS)
	{
	extract($HTTP_POST_VARS);

	$typeid=remval($typeid);;
	# Set up table to display in

$OUTPUT.="<td valign=top width='33%'>
<table border=0 width='90%'>
<tr><td align=center nowrap><h3>Document Data</h3></td></tr>
<tr><th>Type</th><th>Ref</th><th>Document</th><th>Date</th><th>Description</th><th>Filename</th></tr>";

if($typeid!='0') {
		$whe="AND typeid='$typeid' ";
	} else {
		$whe="";
	}

	# Connect to database
	//db_conn (YR_DB);
	db_conn ("yr2");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM documents WHERE div = '".USER_DIV."' $whe ORDER BY docname ASC";
	$docRslt = db_exec ($sql) or errDie ("Unable to retrieve Documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		return "<li>There are no Documents in Cubit.</li>
			 <p>
			 <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='docadd.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='docview.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='docman-index.php'>Back</a></td></tr>
		</table>";
		}
	while($doc = pg_fetch_array ($docRslt)) {
		# Alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$OUTPUT.="<tr bgcolor='$bgColor'><td>$doc[typename]</td><td>$doc[docref]</td><td>$doc[docname]</td><td>$doc[docdate]</td><td>$doc[descrip]</td><td>$doc[filename]</td><td><a href='docedit.php?docid=$doc[docid]'>Edit</a></td>";
		$OUTPUT.="<td><a href='docdload.php?docid=$doc[docid]'>Download</a></td><td><a href='docrem.php?docid=$doc[docid]'>Remove</a></td></tr>";
		$i++;
	}
$OUTPUT.= "</table></td>";

return $OUTPUT;
}

$OUTPUT.="<td valign=top width='33%'>
<table width='90%'>
<tr><td align=center nowrap><h3>Document Action</h3></td></tr>
<tr><th>Actions</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='view.php'>VIEW</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='email.php'>E-MAIL</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='checkout.php'>CHECKOUT</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='delete.php'>DELETE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='history.php'>HISTORY</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='move.php'>MOVE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a  href='subscribe.php'>SUBSCRIBE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='discussion.php'>DISCUSSION</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='archive.php'>ARCHIVE</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='link.php'>LINK</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='publish.php'>PUBLISH</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>

</table></td>
";


require ("../template.php");
?>

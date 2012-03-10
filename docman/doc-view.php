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
require ("../core-settings.php");
require_lib("docman");

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "view":
			$OUTPUT = printCat($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
} else {
	$OUTPUT = select();
}

require ("../template.php");

function select() {

	global $DOCLIB_DOCTYPES;

	if(!isset($typeid)){
                $xin = "";
                $xins = $xin;
                $typeid = "";
                $docref = "";
                $docname = "";
                $day = date("d");
                $mon = date("m");
                $year = date("Y");
                $descrip = "";
        }else{
                $xin = (isset($xin)) ? $xin : "";
                $xins = $xin;
                $xin = xin($typeid, $xin);
        }

	db_conn("yr2");
        $typs= "<select name='typeid'>
	<option value=0>All</option>";
        # User types
        $sql = "SELECT * FROM doctypes WHERE div = '".USER_DIV."' ORDER BY typename ASC";
        $typRslt = db_exec($sql);
        if(pg_numrows($typRslt) < 1){
                if(strlen($typeid) < 1)
                        $typeid = "inv";
                $xin = xin($typeid, $xins);
        }else{
                while($typ = pg_fetch_array($typRslt)){
                        $sel = "";
                        if($typ['typeid'] == $typeid)
                                $sel = "selected";
                        $typs .= "<option value='$typ[typeid]' $sel>($typ[typeref]) $typ[typename]</option>";
                }
        }
        # Built-in types
        foreach($DOCLIB_DOCTYPES as $tkey => $val){
                $sel = "";
                if($tkey == $typeid)
                        $sel = "selected";
                $typs .= "<option value='$tkey' $sel>$DOCLIB_DOCTYPES[$tkey]</option>";
        }
        $typs .="</select>";


	$out="<h3>View Documents</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='view'>
	<tr><th colspan=2>View Options</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Document Types</td><td>$typs</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='View &raquo;'></td></tr>
	</form>
	</table><p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-add.php'>Add Document</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $out;
}

# show stock
function printCat ($HTTP_POST_VARS)
{
	extract($HTTP_POST_VARS);
	global $user_admin;

	$typeid=remval($typeid);;
	# Set up table to display in
	$printCat = "
    <h3>Documents</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Type</th><th>Ref</th><th>Document</th><th>Date</th><th>Description</th><th>Filename</th><th colspan=3>Options</th></tr>";

	if($typeid!='0') {
		$whe="AND typeid='$typeid' ";
	} else {
		$whe="";
	}

	// Check if user is admin
	db_conn("cubit");
	$sql = "SELECT admin FROM users WHERE userid='".USER_ID."'";
	$admRslt = db_exec($sql) or errDie("Unable to retrieve user admin status from Cubit.");
	$admin = pg_fetch_result($admRslt, 0);

	if (!$admin) {
		$adm = "AND docaccess='Yes'";
	} else {
		$adm = "";
	}

	# Connect to database
	//db_conn (YR_DB);
	db_conn ("yr2");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM documents WHERE div = '".USER_DIV."' $whe $adm ORDER BY docname ASC";
	$docRslt = db_exec ($sql) or errDie ("Unable to retrieve Documents from database.");
	if (pg_numrows ($docRslt) < 1) {
		return "<li>There are no Documents in Cubit.</li>
			 <p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-add.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}
	while($doc = pg_fetch_array ($docRslt)) {
		# Alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printCat .= "<tr bgcolor='$bgColor'>
			<td>$doc[typename]</td>
			<td>$doc[docref]</td>
			<td>$doc[docname]</td>
			<td>$doc[docdate]</td>
			<td>$doc[descrip]</td>
			<td>$doc[filename]</td>
			<td><a href='doc-edit.php?docid=$doc[docid]'>Edit</a></td>";
		$printCat .= "<td><a href='doc-dload.php?docid=$doc[docid]'>Download</a></td>
			<td><a href='doc-rem.php?docid=$doc[docid]'>Remove</a></td>
		</tr>";
		$i++;
	}

	$printCat .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-add.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $printCat;
}
?>

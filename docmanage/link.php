<?
require ("../settings.php");
require ("../core-settings.php");
require_lib("docman");

$Unit ="<select size=1 name=Unit>
        <option selected value='none'>None</option>
        <option  value='defunit'>Default Unit</option>
        </select>";
	
$User ="<select size=1 name=User>
        <option value='admin'>Admin</option>
	<option value='unit admin'>Unit Admin</option>
        <option selected value='none'>None</option>
        </select>";
	
$OUTPUT = "
	<h3>Linking Document</h3>
	<form name=form1 ENCTYPE='multipart/form-data' action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Document Title</td><td><input type=text size=20 name=docname ></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Initial Group</td><td align=center>$Unit</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Private</td><td align=center>$User</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Temp Document</td><td><input type=file size=20 name=doc></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit name=conf value='Link &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tdocview.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='docman-index.php'>Document Management</a></td></tr>
	</table>";
require("../template.php");
?>
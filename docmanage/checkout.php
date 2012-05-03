<?
require ("../settings.php");
require ("../core-settings.php");
require_lib("docman");

$OUTPUT = "
	<h3>Checkin Document</h3>
	<form name=form1 ENCTYPE='multipart/form-data' action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<h4>Select the file to check in</h4>
	<tr class='bg-odd'><td>File</td><td><input type=file size=20 name=doc></td></tr>
	<tr><td><br></td></tr>
	<tr class='bg-even'><td>Comment</td><td><input type=text size=20 name=docname ></td></tr>
	<tr><td colspan=2 align=right><input type=submit name=conf value='Checkin &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='tdocview.php'>View Documents</a></td></tr>
		<tr class='bg-odd'><td><a href='docman-index.php'>Document Management</a></td></tr>
	</table>";
require("../template.php");
?>
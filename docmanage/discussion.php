<?
require("../settings.php");
$OUTPUT = "
	<center>
	<table width='100%'>
	<tr><td align=left valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=confirm>
		<tr ><th colspan=2>Document Details</th></tr>
		<tr class='bg-odd'><td nowrap><a href ='docman-index.php' class=nav><b>Add New Document</b></a></td></tr>
		
	</td>
	</tr>
	</form>
	</table>
	</td>";
require("../template.php");
?>
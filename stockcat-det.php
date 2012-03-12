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

# get settings
require("settings.php");

if (isset($_GET['catid'])){
	$OUTPUT = confirm ($_GET['catid']);
} else {
	$OUTPUT = "<li> - Invalid use of module";
}

# get template
require("template.php");

# confirm
function confirm($catid)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($catid, "num", 1, 50, "Invalid stock category id.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>-".$e["msg"]."<br>";
			}
					return $confirm;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stockcat WHERE catid = '$catid' AND div = '".USER_DIV."'";
        $catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($catRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $cat = pg_fetch_array($catRslt);
        }

		# get stock vars
		foreach ($cat as $key => $value) {
			$$key = $value;
		}

		// Layout
		$confirm =
		"<h3>Stock Category</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Category Code</td><td>$catcod</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Category Name</td></td><td>$cat</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Description</td><td><pre>$descript</pre></td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='stockcat-edit.php?catid=$catid'>Edit</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='stockcat-view.php'>View Stock Category</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";


		return $confirm;
}
?>

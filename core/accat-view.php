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
require("core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
                case "viewcat":
			$OUTPUT = viewcat($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("template.php");

# Default view
function view()
{
//layout
$view = "
<h3>View Accounts Categories</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
<form action='".SELF."' method=post name=form>
<input type=hidden name=key value=viewcat>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Account type</td><td valign=center>
<select name='type'>
<option value=I>Income</option>
<option value=E>Expenditure</option>
<option value=B>Balance</option>
</select></td></tr>
<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='View Categories>'></td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
</table>


</form>
</table>";
        return $view;
}

# View Categories
function viewcat($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid Account type.");

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        core_connect();
        switch($type){
                case "I":
                        $tab = "Income";
                        break;
                case "E":
                        $tab = "Expenditure";
                        break;
                case "B":
                        $tab = "Balance";
                        break;
                default:
                        return "<li>Invalid Category type";
        }

        // Set up table to display in
        $OUTPUT = "<center>
	<h3>View Account Categories</h3>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Category ID</th><th>Category Name</th></tr>
	";

	// get categories
        $sql = "SELECT * FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid";
        $catRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account category details from database.", SELF);
	$numrows = pg_numrows ($catRslt);


        if ($numrows < 1) {
		$OUTPUT = "There are no Categories on the selected account type.";
		require ("template.php");
	}


         # display all Categories
        for ($i=0; $i < $numrows; $i++) {
		$cat = pg_fetch_array ($catRslt, $i);
                #get vars from acc as the are in db
                foreach ($cat as $key => $value) {
		        $$key = $value;
	        }

                # alternate bgcolor
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $OUTPUT .= "<tr bgcolor='$bgColor'><td>$catid</td><td>$catname</td><td><a href='accat-edit.php?catid=$catid'>Edit</a></td>";

                # check if accounts are available on catagory
                $sql = "SELECT * FROM accounts WHERE catid = '$catid' AND acctype='$type' AND div = '".USER_DIV."'";
                $check = db_exec($sql) or errDie("Could not retrieve accounts details from database",SELF);
                $rows = pg_numrows($check);

                if($rows > 0){
                        $OUTPUT .= "</tr>";
                }else{
                        # type to lowercase letters
                        $OUTPUT .= "<td><a href='accat-rem.php?catid=$catid&type=$type'>Delete</a></td></tr>";
                }
	}
        $OUTPUT .= "</table>\n


<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
<tr><td>
<br>
</tr></td>
<tr><th>Quick Links</th></tr>
<tr class=datacell><td align=center><a href='accat-new.php'>Add New Account Category</td></tr>
<script>document.write(getQuicklinkSpecial());</script>


</table>";

        return $OUTPUT;
}
?>

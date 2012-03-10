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
                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
                        break;

                default:
                        if(isset($HTTP_GET_VARS['cusnum']) AND isset($HTTP_GET_VARS['editid'])){
                                $OUTPUT = confirm($HTTP_GET_VARS['cusnum'],$HTTP_GET_VARS['editid']);
                        }else{
                                $OUTPUT = "<li class=err> Invalid use of module.";
                        }
        }
} else {
        if(isset($HTTP_GET_VARS['cusnum']) AND isset($HTTP_GET_VARS['editid'])){
                $OUTPUT = confirm($HTTP_GET_VARS['cusnum'],$HTTP_GET_VARS['editid']);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module.";
        }
}

# get templete
require("template.php");

# Default view
function confirm($cusnum,$editid)
{

	db_conn ("cubit");

	$get_branch = "SELECT * FROM customer_branches WHERE id = '$editid' AND cusnum = '$cusnum' AND div = '".USER_DIV."' LIMIT 1";
	$run_branch = db_exec($get_branch);
	if(pg_numrows($run_branch) < 1){
		return "Invalid use of module";
	}

	$arr = pg_fetch_array($run_branch);
	extract ($arr);

	$display = "
			<form action='".SELF."' method=post>
			<table cellpadding='0' cellspacing='".TMPL_tblCellSpacing."'  width=100%>
				<input type=hidden name=key value='write'>
				<input type=hidden name=editid value='$editid'>
				<input type=hidden name=cusnum value='$cusnum'>
				<tr>
					<td><h4>Confirm Customer Branch Removal</h4></td>
				</tr>
				<tr><td><br></td></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td>Branch Name</td><td>$branch_name</td></tr>
				<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch Address</td><td>".nl2br($branch_descrip)."</td></tr>
				<tr><td><br></td></tr>
				<tr><td colspan=2 align=right><input type=submit value='Confirm & Close &raquo;'></td></tr>
			</table>
			</form>
		";
	return $display;

}


# write new data
function write ($HTTP_POST_VARS)
{
        # get vars
        foreach ($HTTP_POST_VARS as $key => $value) {
                $$key = $value;
        }

        # validate input
        require_lib("validate");
        $v = new  validate ();
        $v->isOk ($cusnum, "num", 1, 10, "Invalid Customer Number.");
        $v->isOk ($editid, "num", 1, 10, "Invalid Customer ID.");
       
	 # display errors, if any
        if ($v->isError ()) {
                $confirmCust = "";
                $errors = $v->getErrors();
                foreach ($errors as $e) {
                        $confirmCust .= "<li class=err>".$e["msg"];
                }
                $confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
                return $confirmCust;
        }

	db_conn ("cubit");

	$insert_sql = "DELETE FROM customer_branches WHERE cusnum = '$cusnum' AND div = '".USER_DIV."' AND id = '$editid'";
	$run_insert = db_exec($insert_sql);

	return "<script>
			window.close ();
		</script>"; 

}

?>

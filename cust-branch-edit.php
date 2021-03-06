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

##
# customers-new.php :: Add new invoice
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
        switch ($_POST["key"]) {
                case "details":
                        $OUTPUT = details($_POST);
                        break;

              //  case "confirm":
              //          $OUTPUT = confirm($_POST);
               //         break;

                case "write":
                        $OUTPUT = write($_POST);
                        break;

                default:
                        if(isset($_GET['cusnum']) AND isset($_GET['editid'])){
                                $OUTPUT = edit($_GET['cusnum']);
                        }else{
                                $OUTPUT = "<li class='err'> Invalid use of module.";
                        }
        }
} else {
        if(isset($_GET['cusnum']) AND isset($_GET['editid'])){
                $OUTPUT = edit($_GET['cusnum'],$_GET['editid']);
        }else{
                $OUTPUT = "<li class='err'> Invalid use of module.";
        }
}

# get templete
require("template.php");

# Default view
function edit($cusnum,$editid)
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
			<form action='".SELF."' method='POST'>
			<table ".TMPL_tblDflts."  width='100%'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='editid' value='$editid'>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<tr>
					<td><h4>Edit Customer Branch</h4></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th colspan='2'>Details</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Branch Name</td>
					<td><input type='text' size='30' name='branch_name' value='$branch_name'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Branch Address</td>
					<td><textarea name='branch_descrip' cols='30' rows='5'>$branch_descrip</textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan='2' align='right'><input type='submit' value='Save & Close &raquo;'></td></tr>
			</table>
			</form>
		";
	return $display;

}

function enter_err ($_POST, $err="")
{

        # Get vars
        foreach ($_POST as $key => $value) {
                $$key = $value;
        }

        $display = "
                        <form action='".SELF."' method=post>
                        <table cellpadding='0' cellspacing='".TMPL_tblCellSpacing."'  width=100%>
				<input type=hidden name='key' value='write'>
				<input type=hidden name=editid value='$editid'>
				<input type=hidden name='cusnum' value='$cusnum'>
				<tr>
					<td><h4>Add Customer Branch</h4></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th colspan='2'>Details</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Branch Name</td>
					<td><input type=text size='30' name=branch_name value='$branch_name'></td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Branch Description</td>
					<td><textarea name=branch_descrip cols='30' rows='5'>$branch_descrip</textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan=2 align=right><input type=submit value='Add & Close &raquo;'></td>
				</tr>
			</table>
			</form>
	";
	return $display;

}
/*
# confirm new data
function confirm ($_POST)
{
        # Get vars
        foreach ($_POST as $key => $value) {
                $$key = $value;
        }
        # validate input
        require_lib("validate");
        $v = new  validate ();
        $v->isOk ($branch_name, "string", 1, 255, "Invalid branch name.");
        $v->isOk ($branch_descrip, "string", 1, 255, "Invalid branch description.");

        # display errors, if any
        if ($v->isError ()) {
                $confirm = "";
                $errors = $v->getErrors();
                foreach ($errors as $e) {
                        $confirm .= "<li class=err>".$e["msg"];
                }
                return enter_err($_POST, $confirm);
                exit;
                $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
                return $confirm;
        }

        $confirm =
        "<form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=branch_name value='$branch_name'>
        <input type=hidden name=branch_descrip value='$branch_descrip'>
        <input type=hidden name=cusnum value='$cusnum'>
        <table cellpadding='0' cellspacing='".TMPL_tblCellSpacing."'  width=100%>
		<tr>
			<td><h4>Add Customer Branch</h4></td>
		</tr>
		<tr><td><br></td></tr>
		<tr class='bg-odd'><td>Branch Name</td><td>$branch_name</td></tr>
		<tr class='bg-even'><td>Branch Description</td><td>".nl2br($branch_descrip)."</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table>
	</form>
                ";
        return $confirm;

}
*/

# write new data
function write ($_POST)
{
        # get vars
        foreach ($_POST as $key => $value) {
                $$key = $value;
        }

        if(isset($back)) {
                return enter_err($_POST);
        }

        # validate input
        require_lib("validate");
        $v = new  validate ();
        $v->isOk ($cusnum, "num", 1, 255, "Invalid Department.");
        $v->isOk ($branch_name, "string", 1, 255, "Invalid branch name.");
        $v->isOk ($branch_descrip, "string", 0, 255, "Invalid branch description.");
       
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

	$insert_sql = "UPDATE customer_branches SET branch_name = '$branch_name', branch_descrip = '$branch_descrip' WHERE cusnum = '$cusnum' AND div = '".USER_DIV."' AND id = '$editid'";
	$run_insert = db_exec($insert_sql);

	return "<script>
			window.close ();
		</script>"; 

//	return "Branch added";
}

?>

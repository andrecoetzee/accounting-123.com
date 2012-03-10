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
        if(isset($HTTP_GET_VARS['cusnum'])){
                $OUTPUT = details($HTTP_GET_VARS['cusnum']);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module.";
        }

# get templete
require("template.php");

# Default view
function details($cusnum)
{

	db_conn("cubit");

	$get_branches = "SELECT * FROM customer_branches WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$run_branches = db_exec($get_branches);

	if(pg_numrows($run_branches) < 1){
		return "
				<li class='err'>This customer has no branches.</li>
				<br>
				<input type='button' value='[X] Close' onClick='window.close();'>";
	}

	$i = 0;

	$listing = "";
	while ($arr = pg_fetch_array($run_branches)){
		$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$arr[branch_name]</td>
					<td>".nl2br($arr['branch_descrip'])."</td>
					<td><a href='cust-branch-edit.php?cusnum=$cusnum&editid=$arr[id]'>Edit</a></td>
					<td><a href='cust-branch-del.php?cusnum=$cusnum&editid=$arr[id]'>Remove</a></td>
				</tr>";
		$i++;
	}

	$display = "
			<form action='".SELF."' method='POST'>
			<table ".TMPL_tblDflts."  width='100%'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='cusnum' value='$cusnum'>
				<tr>
					<td><h4>View Customer Branches</h4></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th>Branch Name</th>
					<th>Branch Description</th>
					<th colspan='2'>Options</th>
				</tr>
				$listing
			</table>
			</form>
		";
	return $display;

}

?>

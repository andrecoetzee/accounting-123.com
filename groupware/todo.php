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
require("../settings.php");

foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "account_info":
			$OUTPUT = account_info($_POST);
			break;
		case "archive":
			$OUTPUT = archive();
			break;
        default:
        case "order":
			$OUTPUT = order($_POST);
	}
} elseif (isset($_GET["id"])) {
        # Display default output
	$_POST["id"]=$_GET["id"];
	if (isset($_GET["tripid"])) {$_POST["tripid"]=$_GET["tripid"];}
	if (isset($_GET["proid"])) {$_POST["proid"]=$_GET["proid"];}
	if (isset($_GET["proid"])) {$_POST["busy"]="No";}
	$OUTPUT = order($_POST);
	}

else {
        # Display default output

	$OUTPUT = order($_POST);

}

# get templete
require("gw-tmpl.php");

function order($_POST,$errors="")
{
	$Out="";
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	db_conn("cubit");
	$date=date("Y-m-d");

	pglib_transaction("begin");

	$cdate=date("D, d M Y");
	$datemade=date("Y-m-d");
	$timemade=date("H:i");
	$op=USER_NAME;

	if(!isset($con)){$con='';}
	if(!isset($name)){$name='';}
	if(!isset($notes)){$notes='';}
	if(!isset($comp)){$comp='';}

	$Pals="";
	$Sl = "SELECT * FROM todos WHERE com='No' and op='$op' ORDER BY id DESC";
	$Rs = db_exec ($Sl) or errDie ("Unable to view clients");
	$numrow=pg_numrows($Rs);
	if (pg_numrows ($Rs) < 1) {$Trips="";}
	else
	{
		$i=0;
		while($Tp = pg_fetch_array($Rs))
		{
			$i++;
			$Tpdes=substr($Tp['timemade'],0,2).":".substr($Tp['timemade'],2,2);
			$class = ($i % 2) ? "even" : "odd";
			$Pals .= "<tr class='$class'><td>$Tp[datemade]</td><td>$Tpdes</td><td>$Tp[des]</td><td><input type=checkbox name=done[$Tp[id]] OnClick='javascript:document.form.submit();'></td></tr>";
		}
	}

	pglib_transaction("commit");

	$account_dets =
	"<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=account_info>
	<table cellpadding='2' cellspacing='0' class='shtable'>
	<tr class='odd'><th colspan=3 align=left>TO DO LIST ($numrow)</th></tr>
	 <tr class='odd'><td width='20%'>CURRENT DATE</td><td>$cdate</td></tr>
	</table>
	<p></p>
	<table cellpadding='2' cellspacing='0' class='shtable'>
	 <tr>
	 	<th>DATE</th>
	 	<th>TIME</th>
	 	<th>DESCRIPTION</th>
	 	<th>DONE</th>
	 </tr>
	 <tr class='even'>
	 	<td><input type=hidden name=datemade value='$datemade'>$datemade</td>
	 	<td><input type=hidden name=timemade value='$timemade'>$timemade</td><td><input type=text size=20 name=des value=''></td><td> &nbsp; </td></tr>

	 $Pals
	</table>
	<p></p>
	<input type=submit value='Update &raquo'>
	</form>

	<script>
		setOnload
	</script>";
	return $account_dets;

}

# Write Account Info
function account_info($_POST)
{
	$Out="";
	#get & send vars
	foreach ($_POST as $key => $value) {

		$$key = remval($value);
		$Out .="<input type=hidden name=$$key value='$value'>";
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();

        # display errors, if any
	if ($v->isError ()) {
		$errors = "";
		$Errors = $v->getErrors();
		foreach ($Errors as $e) {
			$errors .= "<li class=err>".$e["msg"];
		}
		$errors .= "<input type=hidden name=errors value='$errors'>";
		return order($_POST,$errors);
	}

	if (isset($cc)){$com="Yes";} else {$com="No";}
	$op=USER_NAME;

	db_conn("cubit");

	if ((strlen($des)>0))
	{
		$Sl = "INSERT INTO todos (datemade,timemade,op,des,com) VALUES ('$datemade','$timemade','$op','$des','$com')";
		$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);
	}

	if(isset($done))
	{

		#get & send vars
		foreach ($done as $key => $value) {
			$Sl = "UPDATE todos SET com='Yes' WHERE id='$key'";
			$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);

		}
	}

	return order($_POST);

}

?>

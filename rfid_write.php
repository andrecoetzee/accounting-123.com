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
require("libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "write_bars":
			$OUTPUT = write($_POST);
			break;
		case "get_bars":
			$_POST["setfocus"] = "barcodes";
			$OUTPUT = read_bars ($_POST);
			break;
                case "rfid":
			$OUTPUT = rfid_write($_POST);
			break;
                default:
			$_POST["setfocus"] = "clength";
			$OUTPUT = get_length($_POST);
	}
} elseif (isset($_GET["id"])) {
        # Display default output
	$_POST["id"]=$_GET["id"];

	//Sends the trip id to edit trip
	if (isset($_GET["tripid"])) {$_POST["tripid"]=$_GET["tripid"];}

	//Sends the product id to edit product
	if (isset($_GET["proid"])) {$_POST["proid"]=$_GET["proid"];}

	//Just a way to ensure that the product is loaded only once for editing
	if (isset($_GET["proid"])) {$_POST["busy"]="No";}
	$_POST["setfocus"] = "clength";
	$OUTPUT = get_length($_POST);
	}

else {
        # Display default output
	$_POST["setfocus"] = "clength";
	$OUTPUT = get_length($_POST);

}

# get templete
require("template.php");

function get_length ($_POST)
{

	$Out="";

        # get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 10, "Invalid client No.");

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

	db_conn("cubit");

	$Sl = "SELECT * FROM possets WHERE div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to add supplier to the system.", SELF);

	if (pg_numrows ($Rs) < 1)
	{
		return "
			Please set the point of sale settings under the stock settings
			<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=30%>
				<tr><td><br><br></tr>
				<tr><th>Quick Links</th></tr>
				<tr class='bg-odd'><td><a href='stock-view.php'>View Stock</td></tr>
				<tr class='bg-odd'><td><a href='pos-set.php'>Set Point Of Sale Setting</td></tr>
				<script>document.write(getQuicklinkSpecial());</script></tr>
			</table>";
	}

	$Dets = pg_fetch_array($Rs);
	if($Dets['opt']=="Yes")
	{
		return "With your current setting you set the barcode at add/edit stock.<br> To change this setting use the point of sale settings under the stock settings";
	}


	$Sl = "SELECT stkcod,stkdes,units,alloc FROM stock WHERE stkid='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to view stock");
	if(pg_numrows($Rs)<1) {return "Invalid Stock id.";}
	$St = pg_fetch_array($Rs);
	$Av=$St['units']-$St['alloc'];

	$i=0;
	$bars="";

	$Wob=$Av-$i;
	if ($Wob>0)
	{
		$but="<tr><td valign='center'><input type='submit' value='Start'></td></tr>";
	}
	else
	{
		$but="<li class='err'>There is no stock for which to allocate a barcode</li>";
	}

	$display = "
			<h2>Select Length Of Barcode</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form1'>
				<input type='hidden' name='key' value='get_bars'>
				<input type='hidden' name='id' value='$id'>

				<tr>
					<th>Barcode Character Length</th>
				</tr>
				<tr>
					<td class='bg-odd'><input type='text' size='6' name='clength'></td>
				</tr>
				<tr><td><br></td></tr>
				$but
			</form>
			</table>";
	return $display;

}


function read_bars ($_POST,$errs = "")
{

	extract ($_POST);

	$display = "
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form1'>
				$errs
				<input type='hidden' name='key' value='write_bars'>
				<input type='hidden' name='clength' value='$clength'>
				<input type='hidden' name='id' value='$id'>
				<tr>
					<th>Barcode Length</th>
				</tr>
				<tr>
					<td class='bg-odd'>$clength</td>
				</tr>
				<tr>
					<th>Barcodes In Single Line (Will be split based on specified Width)</th>
				</tr>
				<tr>
					<td><textarea name='barcodes' cols='60' rows='5'></textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Process'></td>
				</tr>
			</td>
			</table>
		";
	return $display;

}



# Write Barecode Info
function write($_POST)
{

	$Out="";

	#get & send vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
		$Out .= "<input type=hidden name=$$key value='$value'>";
	}

	$blength = strlen($barcodes);
	$start = 0;
	while ($start < $blength){
		$value = substr($barcodes,$start,$clength);
		$start = $start + $clength;
		if(strlen($value) == $clength)
			$bars[] = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();

	foreach($bars as $each){
		$v->isOk ($each, "num", 1, $clength, "Invalid bar code.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$errors = "";
		$Errors = $v->getErrors();
		foreach ($Errors as $e) {
			$errors .= "<li class=err>".$e["msg"]."</li>";
		}
		$errors .= "<input type=hidden name=errors value='$errors'>";
		return read_bars($_POST,$errors);
	}

	#we can only add as many barcodes as there is stock, so find the max and reduce the array if it exceeds the max

	pglib_transaction("begin");

	$Sl = "SELECT stkcod,stkdes,units,alloc FROM stock WHERE stkid='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to view stock");
	if(pg_numrows($Rs)<1) {return "Invalid Stock id.";}
	$St = pg_fetch_array($Rs);
	$Av=$St['units']-$St['alloc'];

	$i=0;

	$Sl = "SELECT code FROM ss0 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss1 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss2 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss3 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss4 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss5 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss6 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss7 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss8 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	$Sl = "SELECT code FROM ss9 WHERE stock='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
	while($Tp = pg_fetch_array($Rs)){$i++;}

	pglib_transaction("commit");

	$Wob=$Av-$i;
	if ($Wob>0){
		#doesnt exceed, do nothing
		foreach($bars as $each){
			$bars2[] = $each;
		}
	}else {
		$bars2 = array ();
		for($count = 0;  $count <= $Wob; $count++){
			$bars2[] = $bars[$count];
		}
	}

	db_conn("cubit");

	foreach($bars2 as $each){
		switch (substr($each,(strlen($each)-1),1)) {
				case "0":
					$tab="ss0";
					break;
				case "1":
					$tab="ss1";
					break;
				case "2":
					$tab="ss2";
					break;
				case "3":
					$tab="ss3";
					break;
				case "4":
					$tab="ss4";
					break;
				case "5":
					$tab="ss5";
					break;
				case "6":
					$tab="ss6";
					break;
				case "7":
					$tab="ss7";
					break;
				case "8":
					$tab="ss8";
					break;
				case "9":
					$tab="ss9";
					break;
				default:
					return order($_POST,"The code you selected is invalid");
			}

		if (barext_ex($tab,'code',$each)or(strlen($each)==0)) {
			return read_bars($_POST,"<li class='err'>The code you selected already exits in the system.</li>");
		}else {
			$getcheck = "SELECT * FROM ".$tab." WHERE code = '$me' AND active = 'no'";
			$runcheck = db_exec($getcheck) or errDie("Unable to get serial number check");
			if(pg_numrows($runcheck) < 1){
				$Sl = "INSERT INTO ".$tab." (code,stock,div) VALUES ('$each','$id','".USER_DIV."')";
				$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);
			}else {
				$arr = pg_fetch_array($runcheck);
				$Sl = "UPDATE ".$tab." SET active = 'yes' WHERE code = '$arr[code]' AND stock = '$arr[stock]' AND div = '$arr[div]'";
				$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);
			}
		}
	}

	#print and reload parent window

	return "All Possible RFID Barcodes Have Been Imported<br><br
		<input type='button' onClick='javascript:window.close();' value='Close'>
		<script>
			opener.document.formName.submit()
		</script>";

}

function rfid_write ($_POST)
{

	extract ($_POST);

	if(!isset($id))
		return "Invalid ID.";

	print "<script>window.open('pos_rfid.php','newwindow','height=500,width=700,toolbar=no,menubar=no,scrollbars=no')</script>";

}

?>

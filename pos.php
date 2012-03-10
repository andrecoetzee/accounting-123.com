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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "rfid":
			$OUTPUT = rfid_write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = order($HTTP_POST_VARS);
	}
} elseif (isset($HTTP_GET_VARS["id"])) {
	# Display default output
	$HTTP_POST_VARS["id"]=$HTTP_GET_VARS["id"];
	//Sends the trip id to edit trip
	if (isset($HTTP_GET_VARS["tripid"])) {$HTTP_POST_VARS["tripid"]=$HTTP_GET_VARS["tripid"];}
	//Sends the product id to edit product
	if (isset($HTTP_GET_VARS["proid"])) {$HTTP_POST_VARS["proid"]=$HTTP_GET_VARS["proid"];}
	//Just a way to ensure that the product is loaded only once for editing
	if (isset($HTTP_GET_VARS["proid"])) {$HTTP_POST_VARS["busy"]="No";}
	$OUTPUT = order($HTTP_POST_VARS);
}else {
	# Display default output
	$OUTPUT = order($HTTP_POST_VARS);
}

# get templete
require("template.php");




function order($HTTP_POST_VARS,$errors="")
{

	$Out = "";

    # get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($id, "num", 1, 10, "Invalid client No.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	pglib_transaction("BEGIN");

	$Sl = "SELECT stkcod,stkdes,units,alloc,bar FROM stock WHERE stkid='$id' AND div = '".USER_DIV."'";
	$Rs = db_exec ($Sl) or errDie ("Unable to view stock");
	if(pg_numrows($Rs)<1) {return "Invalid Stock id.";}
	$St = pg_fetch_array($Rs);
	$Av = sprint3($St['units'] - $St['alloc']);

	$i = 0;
	$bars = "";
	
	for ($x = 0; $x <= 9; ++$x) {
		$Sl = "SELECT code FROM ss$x WHERE stock='$id' AND div = '".USER_DIV."' AND active = 'yes'";
		$Rs = db_exec ($Sl) or errDie ("Unable to retrieve barcodes from db");
		while($Tp = pg_fetch_array($Rs)) {
			$i++;
			$bars .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$Tp[code]</td>
					<td><a href='pos-rem.php?id=$Tp[code]&rid=$id'>Remove</a></td>
				</tr>";
		}
	}

	pglib_transaction("commit");

	$Wob = sprint3($Av - $i);
	if ($Wob > 0) {
		$in = "
			<tr>
				<th colspan=2>Barcodes</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bar Code</td>
				<td><input type='text' size='20' name='me' value=''></td>
			</tr>";
		$rfidfrm = "
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='rfid'>
				<input type='hidden' name='id' value='$id'>
				<tr>
					<td><input type='submit' value='Receive RFID Batch'></td>
				</tr>
			</form>";
	}else {
		$in = "<tr><th colspan='2'>Barcodes</th></tr>";
		$rfidfrm = "";
	}

	
	$but = "<tr><td valign='center'><input type='submit' value='Update >>>'></td></tr>";

	$account_dets = "
		$errors
		<h3>Bar Code Allocation</h3>
		<form method='POST' name='formName'>
			<input type='hidden' name='reload' value='window'>
		</form>
		<table ".TMPL_tblDflts." width='500'>
		<tr>
			<th colspan='2'>Stock Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Code</td>
			<td> $St[stkcod]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Description</td>
			<td> $St[stkdes]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Available</td>
			<td> $Av</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>With Barcode</td>
			<td> $i</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Without Barcode</td>
			<td> $Wob</td>
		</tr>
		$rfidfrm
		<form action='".SELF."' method='post' name='form'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='id' value='$id'>
			$but
			<tr>
				<th colspan='2'>All Items of \"$St[stkcod]\" Share One Barcode</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap='t'>Items this Barcode:</td>
				<td><input type='text' name='sharecode' value='$St[bar]' /></td>
				<td class='err'>This setting will cause the barcodes listed below to be ignored.</td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='center'><h3>OR</h3></td>
			</tr>
			$in
			$bars
			$but
		</form>
		<table ".TMPL_tblDflts." width='30%'>
			<tr><td><br><br></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='stock-view.php'>View Stock</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $account_dets;

}



# Write Barecode Info
function write($HTTP_POST_VARS)
{

	$Out = "";

	#get & send vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
		$Out .= "<input type='hidden' name=$$key value='$value'>";
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($id, "num", 1, 100, "Invalid stock item id.");
	if (isset($me)) {
    	$v->isOk($me, "string", 0, 100, "Invalid bar code.");
	}
    $v->isOk($sharecode, "string", 0, 100, "Invalid shared bar code.");

	# display errors, if any
	if ($v->isError ()) {
		return order($HTTP_POST_VARS,$v->genErrors());
	}

	$cols = grp(
		m("bar", $sharecode)
	);

	$wh = "stkid='$id'";

	$qry = new dbUpdate("stock", "cubit", $cols, $wh);
	$qry->run(DB_UPDATE);

	if (isset($me) && strlen($me) > 0) {
		db_conn("cubit");
		switch (substr($me,(strlen($me)-1),1)) {
			case "0":
				$tab = "ss0";
				break;
			case "1":
				$tab = "ss1";
				break;
			case "2":
				$tab = "ss2";
				break;
			case "3":
				$tab = "ss3";
				break;
			case "4":
				$tab = "ss4";
				break;
			case "5":
				$tab = "ss5";
				break;
			case "6":
				$tab = "ss6";
				break;
			case "7":
				$tab = "ss7";
				break;
			case "8":
				$tab = "ss8";
				break;
			case "9":
				$tab = "ss9";
				break;
			default:
				return order($HTTP_POST_VARS,"The code you selected is invalid");
		}
	
		if (barext_ex($tab,'code',$me)or(strlen($me) == 0)) {
				return order($HTTP_POST_VARS,"The code you selected aready exits in the system.");
		}else {
			$getcheck = "SELECT * FROM ".$tab." WHERE code = '$me' AND active = 'no'";
			$runcheck = db_exec($getcheck) or errDie("Unable to get serial number check");
			if(pg_numrows($runcheck) < 1){
				$Sl = "INSERT INTO ".$tab." (code,stock,div) VALUES ('$me','$id','".USER_DIV."')";
				$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);
			}else {
				$arr = pg_fetch_array($runcheck);
				$Sl = "UPDATE ".$tab." SET active = 'yes' WHERE code = '$arr[code]' AND stock = '$arr[stock]' AND div = '$arr[div]'";
				$Rs = db_exec ($Sl) or errDie ("Unable to update database.", SELF);
			}
	
		}
	}
	return order($HTTP_POST_VARS);

}



function rfid_write ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($id))
		return "Invalid ID.";

//window.location='stock-view.php'
//window.open('rfid_write.php?id=$id','newwindow','height=500,width=700,toolbar=no,menubar=no
//,scrollbars=no')
	print "
		<script>
			var windowReference;

			function openPopup() {
				windowReference = window.open('rfid_write.php?id=$id','windowName','height=500,width=700,toolbar=no,menubar=no,scrollbars=no');
				if (!windowReference.opener)
					windowReference.opener = self;
				}

				window.location='pos.php?id=$id'
				openPopup();
		</script>";

}


?>
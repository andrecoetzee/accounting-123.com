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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "Submit":
			$OUTPUT = slctAcc($_POST);
			break;

                case "Add More Sub Headings":
			$OUTPUT = moresub($_POST);
			break;

                case "confirm":
			if(isset($_POST['sub'])){
				$OUTPUT = confirm($_POST);
			}else{
				$OUTPUT = slctAccR($_POST);
			}
			break;

                case "write":
			$OUTPUT = write($_POST);
			break;

                default:
			$OUTPUT = cook();
	}
} else {
        # Display default output
        $OUTPUT = cook();
}

# get templete
require("template.php");

function cook()
{
	$cRslt = get("core", "*", "bal_sheet", "type", "OESUB");
	if (pg_numrows ($cRslt) < 1) {
		header("Location: set-bal-sheet.php");
		$err = "<li class=err>There are no Balance sheet settings in Cubit.";
		return $err;
	}

	# Get Owners Equity Sub Headings
	$oesRslt = get("core", "*", "bal_sheet", "type", "OESUB");
	while($oes = pg_fetch_array($oesRslt)){
		$sql = "SELECT * FROM bal_sheet WHERE type ='OEACC' AND ref = '$oes[ref]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
		$oesub[$oes['ref']] = $oes['value'];

		while($acc = pg_fetch_array($accRslt)){
			$oeacc[$oes['ref']][] = $acc['value'];
		}
	}

	# Get Assets Sub Headings
	$assRslt = get("core", "*", "bal_sheet", "type", "ASSSUB");
	while($ass = pg_fetch_array($assRslt)){
		$sql = "SELECT * FROM bal_sheet WHERE type ='ASSACC' AND ref = '$ass[ref]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql) or errDie("Unable to retrieve balance sheet settings from the Database.",SELF);
		$asssub[$ass['ref']] = $ass['value'];

		while($acc = pg_fetch_array($accRslt)){
			$assacc[$ass['ref']][] = $acc['value'];
		}
	}

	# Owners equity
	$VARS['oesub'] = $oesub;
	$VARS['oeacc'] = $oeacc;

	if(!isset($asssub)) {
		//$asssub="";
	}
	if(!isset($assacc)) {
                //$assacc="";
        }

	# assets
	//$VARS['asssub'] = $asssub;
	//$VARS['assacc'] = $assacc;


	if(isset($asssub)) {
		$VARS['asssub'] = $asssub;
	}
	if(isset($assacc)) {
                $VARS['assacc'] = $assacc;
        }



	return slctAccR($VARS);
}

# select accounts (restrict)
function slctAccR($_POST)
{
         # get vars
        foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # validate input
		require_lib("validate");
		$v = new  validate ();
        # validate array input
        foreach($oesub as $key => $sub){
                $v->isOk ($sub, "string", 0, 255, "Invalid Owners Equity Sub Heading number $key.");
        }

		if(isset($asssub)) {
			foreach($asssub as $key => $sub){
				$v->isOk ($sub, "string", 0, 255, "Invalid Assets Sub Heading number $key.");
			}
        }

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

		# print "<pre>"; var_dump($selacc);exit;

		// Set up table to display in
        $slctAcc = "<center>
		<h3>Select Accounts</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='60%'>
        <tr><th>Owners Equity</th><tr>";
        $i = 0;
        foreach($oesub as $ref => $sub){
			if(strlen($sub)){
				$slctAcc .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><input type=hidden name=oesub[] value='$sub'><b>$sub</b></td></tr>
				<tr bgcolor='".TMPL_tblDataColor1."'><td>";
				$accRslt = get("core","*","accounts","acctype", "B");
				while($oeaccs = pg_fetch_array($accRslt)){
					#get vars (accnum, accname)
					foreach($oeaccs as $key => $value){
							$$key = $value;
					}

					# check if the account was selected somewhere
					if(isset($assacc)){
						if(check($accid, $assacc, $ref)){
							continue;
						}
					}
					if(isset($oeacc)){
						if(check2D($accid, $oeacc, $ref)){
							continue;
						}
					}

					# keep checked
					if(isset($oeacc[$ref])){
						if(in_array($accid, $oeacc[$ref])){
							$sel = "checked=yes";
						}else{
							$sel = "";
						}
					}else{
						$sel = "";
					}
					$slctAcc .= "<input type=checkbox name=oeacc[$i][] value='$accid' $sel onChange='javascript:document.form.submit();'> $accname<br>";
				}
				$i++;
			}else{
				continue;
			}
        }
        $slctAcc .= "<tr><th>Assets</th></tr>";
        $i = 0;
	if(isset($asssub)) {
        foreach($asssub as $ref => $sub){
			if(strlen($sub)){
				$slctAcc .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><input type=hidden name=asssub[] value='$sub'><b>$sub<b></td></tr>
							<tr bgcolor='".TMPL_tblDataColor1."'><td>";

				$accRslt = get("core","*","accounts","acctype", "B");
				while($assaccs = pg_fetch_array($accRslt)){
					#get vars (accnum, accname)
					foreach($assaccs as $key => $value){
							$$key = $value;
					}

					# check if the account was selected somewhere
					if(isset($assacc)){
						if(check2D($accid, $assacc, $ref)){
							continue;
						}
					}
					if(isset($oeacc)){
						if(check($accid, $oeacc)){
							continue;
						}
					}

					# keep checked
					if(isset($assacc[$ref])){
						if(in_array($accid, $assacc[$ref])){
							$sel = "checked=yes";
						}else{
							$sel = "";
						}
					}else{
						$sel = "";
					}
					$slctAcc .= "<input type=checkbox name=assacc[$i][] value='$accid' $sel onChange='javascript:document.form.submit();'> $accname<br>";
				}
				$i++;
			}else {
				continue;
			}
        }
	}

        $slctAcc .= "</table>
        <input type=submit value='Update'> <input type=submit name=sub value='Submit'></form>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $slctAcc;
}

function check2D($val, $arr, $ex){
	$ret = false;
	foreach($arr as $ref => $sub){
		if(in_array($val, $sub) && $ref != $ex){
			$ret = true;
		}
	}
	return $ret;
}

function check($val, $arr){
	$ret = false;
	foreach($arr as $ref => $sub){
		if(in_array($val, $sub)){
			$ret = true;
		}
	}
	return $ret;
}


# Confirm
function confirm($_POST)
{
        # get vars
        foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # validate input
		require_lib("validate");
		$v = new  validate ();
    	## Received arrays
        # oesub[]  =>  Equity Sub heading accounts
        # oeacc[][]  => Equity Accounts
        # asssub[]  => Assets sub heading accounts
        # assacc[][] => Assets Accounts
        ##

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

        if(empty($asssub)){
                return "<li class=err>Invalid sub heading(s) under Assets";
        }

        if(empty($oesub)){
                return "<li class=err>Invalid sub heading(s) under Owners Equity";
        }


        # check if any accounts have been selected on all specified sub-headings
        $i = 0;
        while($i <= (count($oesub)-1)){
			if(!isset($oeacc[$i])){
					return "Please Select at least one account under <b>$oesub[$i]</b> or leave the sub heading box blank on the first page.";
			}
			$i++;
        }

        $i = 0;
        while($i <= (count($asssub)-1)){
			if(!isset($assacc[$i])){
				return "Please Select at least one account under <b>$asssub[$i]</b> or leave the sub heading box blank on the first page.";
			}
			$i++;
        }

        # Set up table to display in
        $confirm = "
        <center>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
        <tr><th>Owners Equity</th></tr>";
        $i = 0;
        # Strip subs
        foreach($oesub as $k => $sub){
			$confirm .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><input type=hidden name=oesub[] value='$sub'><b>$sub</b></td></tr>
						<tr bgcolor='".TMPL_tblDataColor1."'><td>";

			# Strip accounts
			foreach($oeacc[$k] as $key => $accnum){
				$accRslt = get("core","accname","accounts","accid",$accnum);
				$accname = pg_fetch_array($accRslt);
				$confirm .= "<input type=hidden name=oeacc[$k][] value='$accnum'> $accname[accname]<br>";
			}
        }
        $confirm .= "</td></tr>
        <tr><th>Assets</th></tr>";

        $i = 0;
        # Strip subs
        foreach($asssub as $k => $sub){
			$confirm .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><input type=hidden name=asssub[] value='$sub'><b>$sub</b></td></tr>
						<tr bgcolor='".TMPL_tblDataColor1."'><td>";

			# Strip accounts
			foreach($assacc[$k] as $key => $accnum){
				$accRslt = get("core","accname","accounts","accid",$accnum);
				$accname = pg_fetch_array($accRslt);
				$confirm .= "<input type=hidden name=assacc[$k][] value='$accnum'> $accname[accname]<br>";
			}
        }

        $confirm .= "</td></tr></table>
        <input type=button value='< Cancel' onClick='javascript:history.back();'> <input type=submit value='Confirm >'>
        </form>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</tr>
		</table>";

        return $confirm;
}


# write settings
function write($_POST)
{
        # get vars
        foreach ($_POST as $key => $value) {
			$$key = $value;
		}

        # validate input
		require_lib("validate");
		$v = new  validate ();
		## Received arrays
		# oesub[]  =>  Equity Sub heading accounts
        # oeacc[][]  => Equity Accounts
        # asssub[]  => Assets sub heading accounts
        # assacc[][] => Assets Accounts
        ##

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

        # check if any accounts have been selected on all specified sub-headings
        $i = 0;
        while($i <= (count($oesub)-1)){
			if(!isset($oeacc[$i])){
				return "Please Select at least one account under <b>$oesub[$i]</b> or leave the sub heading box empty on the first page.";
			}
			$i++;
        }

        $i = 0;
        while($i <= (count($asssub)-1)){
			if(!isset($assacc[$i])){
				return "Please Select at least one account under <b>$asssub[$i]</b> or leave the sub heading box empty on the first page.";
			}
			$i++;
        }

        ## NOTE !!!
        # All arrays have been received successfully,
        # That is if the script passes the above Check list.
        # Huuuh Huuuh !!!!!! :-)
        ##

		core_connect();
        // Lets get dirty
        # First Empty the Table (Warning Was Given)
        $sql = "DELETE FROM bal_sheet WHERE div = '".USER_DIV."'";
        $emptyRslt = db_exec($sql) or errDie("Unable to clean the balance sheet settings table before writing.",SELF);

        # Write Owner's Equity sub headigns and their accounts
        foreach($oesub as $ref => $sub){
			$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('OESUB', '$ref', '$sub', '".USER_DIV."')";
			$bsRslt = db_exec($query) or errDie("Unable to insert Balance Sheet settings to database",SELF);
			foreach($oeacc[$ref] as $k => $accnum){
				$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('OEACC','$ref','$accnum', '".USER_DIV."')";
				$accRslt = db_exec($query) or errDie("Unable to insert Balance sheet settings to Cubit.",SELF);
			}

        }

        # Write Assets sub headings and their Accounts
        foreach($asssub as $ref => $sub){
			$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('ASSSUB', '$ref', '$sub', '".USER_DIV."')";
			$bsRslt = db_exec($query) or errDie("Unable to insert Balance Sheet settings to database",SELF);
			foreach($assacc[$ref] as $k => $accnum){
				$query = "INSERT INTO bal_sheet(type, ref, value, div) VALUES('ASSACC','$ref','$accnum', '".USER_DIV."')";
				$accRslt = db_exec($query) or errDie("Unable to insert Balance sheet settings to Cubit.",SELF);
			}

        }

        // Status Report
        $write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Balance Sheet Settings</th></tr>
			<tr class=datacell><td>The Selected Balance Sheet Settings were successfully added to Cubit.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

        return $write;
}
?>

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

if ( ! isset($_POST["key"]) ) {
	$_POST["key"] = "vatreg";
}

switch ( $_POST["key"] ) {
case "confirm":
	$OUTPUT = confirm();
	break;
case "write":
	$OUTPUT = write();
	break;
case "select":
	$OUTPUT = select();
	break;
case "vatreg":
default:
	$OUTPUT = vatreg();
	break;
}

require("template.php");

function vatreg($err="") {
	global $_POST;
	extract($_POST);
	
	db_conn("cubit");
	$sql = "SELECT value AS vatreg FROM settings WHERE constant='VAT_REG'";
	$rslt = db_exec($sql) or errDie("Error reading vat registration settings.");
	
	$fields = pg_fetch_array($rslt);
	
	foreach ( $fields as $k => $v ) {
		if ( ! isset($$k) ) $$k = $v;
	}
	
	$OUTPUT = "
	<h3>Vat Registered</h3>
	$err
	<form method=post action='".SELF."'>
	<input type=hidden name=key value=select>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th colspan=2>Are you VAT Registered?</th>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td><input type=radio value=no name=vatreg ".($vatreg=="no"?"checked":"")."></td>
		<td>No</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td><input type=radio value=yes name=vatreg ".($vatreg=="yes"?"checked":"")."></td>
		<td>Yes</td>
	</tr>
	<tr>
		<td colspan=2 align=right><input type=submit value='Next >'></td>
	</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function select($err="") {
	global $_POST;
	extract($_POST);
	
	if ( $vatreg == "no" ) {
		return confirm();
	} else if ( $vatreg != "yes" ) {
		return vatreg("<li class=err>Invalid vat option selected.</li>");
	}
	
	db_conn("cubit");
	$sql = "SELECT value AS prdcat FROM settings WHERE constant='TAX_PRDCAT'";
	$rslt = db_exec($sql) or errDie("Error reading tax period category.");
	
	$fields = array_merge(pg_fetch_array($rslt), array("cate_mon"=>date("m")));
	
	foreach ( $fields as $k => $v ) {
		if ( ! isset($$k) ) $$k = $v;
	}
	
	// if category e and not split yet (straight from db), cat e form: "e [mon#]"
	if ( strlen($prdcat) > 1 ) {
		$cate_mon = substr($prdcat, 1);
		$prdcat = $prdcat[0];
	}
	
	$OUTPUT = "
	<h3>Tax Period</h3>";
	
	if ( $err != "" ) {
		$OUTPUT .= "$err";
	} else {
		$OUTPUT .= "	<li class=err>When selecting a Tax period you will be reminded to do your Tax 
			transactions at the end of each such period. If you do not require, do not change this setting.</li>";
	}
	
	$selmon = "<select name=cate_mon>";
	for ( $i = 1; $i <= 12; ++$i ) {
		if ( $cate_mon == $i ) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$selmon .= "<option value='$i' $sel>".date("F", mktime(0, 0, 0, $i, 1, 2000))."</option>";
	}
	$selmon .= "</select>";
	
	$OUTPUT .= "
	<form method=post action='".SELF."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=vatreg value='$vatreg'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr>
		<th colspan=2>Select Tax Period Category</th>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td><input type=radio value=none name=prdcat ".($prdcat=="none"?"checked":"")."></td>
		<td>None</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td><input type=radio value=a name=prdcat ".($prdcat=="a"?"checked":"")."></td>
		<td>Category A</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td><input type=radio value=b name=prdcat ".($prdcat=="b"?"checked":"")."></td>
		<td>Category B</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td><input type=radio value=c name=prdcat ".($prdcat=="c"?"checked":"")."></td>
		<td>Category C</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td><input type=radio value=d name=prdcat ".($prdcat=="d"?"checked":"")."></td>
		<td>Category D</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td><input type=radio value=e name=prdcat ".($prdcat=="e"?"checked":"")."></td>
		<td>Category E</td>
		<td>Select: $selmon</td>
	</tr>
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td><input type=radio value=f name=prdcat ".($prdcat=="f"?"checked":"")."></td>
		<td>Category F</td>
	</tr>
	<tr>
		<td colspan=2 align=right><input type=submit value='Next >'></td>
	</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function confirm() {
	global $_POST;
	extract($_POST);

	if ( $vatreg == "yes" ) {
		switch ( $prdcat ) {
		case "a":
		case "b":
		case "c":
		case "d":
		case "e":
		case "f":
			/**********************************************/
			/*										*/
			/* 		convert character to uppercase		*/
			/*										*/
			/**********************************************/
			$cat = chr(ord($prdcat) ^ (1 << 5));
			break;
		case "none":
			$cat = "None";
			break;
		default:
			return select("<li class=err>Invalid Tax period category selected.</li>");
			break;
		}
		
		if ( $prdcat == "e" ) {
			if ( $cate_mon < 1 || $cate_mon > 12 ) {
				return select("<li class=err>Invalid month selected.</li>");
			}
			$mon = date("F", mktime(0, 0, 0, $cate_mon, 1, 2000));
		}
	} else if ( $vatreg != "no" ) {
		return vatreg("<li class=err>Invalid Vat registration option selected.</li>");
	}
	
	if ( $vatreg == "yes" ) {
		$OUTPUT = "
		<h3>Tax Period</h3>";
	} else {
		$OUTPUT = "
		<h3>Vat Registered</h3>";
	}
	
	$OUTPUT .= "
	<form method=post action='".SELF."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=cate_mon value='$cate_mon'>
	<input type=hidden name=vatreg value='$vatreg'>";
	
	if ( $vatreg == "yes" ) {
		$OUTPUT .= "<input type=hidden name=prdcat value='$prdcat'>";
	} 
	
	$OUTPUT .= "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>";
	
	if ( $vatreg == "yes" ) {
		$OUTPUT .= "
		<tr>
			<th colspan=2>Selected Tax Period Category</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>Category</td>
			<td>$cat</td>
		</tr>";
	} else {
		$OUTPUT .= "
		<tr>
			<th colspan=2>Vat Registered</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>Not Vat Registered</td>
		</tr>";
	}
	
	$OUTPUT .= "
	<tr>
		<td colspan=2 align=right><input type=submit value='Finish >'></td>
	</table>";
	
	return $OUTPUT;
}

function write() {
	global $_POST;
	extract($_POST);
	
	if ( $vatreg == "yes" ) {
		switch ( $prdcat ) {
		case "a":
		case "b":
		case "c":
		case "d":
		case "e":
		case "f":
			/**********************************************/
			/*										*/
			/* 		convert character to uppercase		*/
			/*										*/
			/**********************************************/
			$cat = chr(ord($prdcat) ^ (1 << 5));
			break;
		case "none":
			$cat = "None";
			break;
		default:
			return select("<li class=err>Invalid Tax period category selected.</li>");
			break;
		}
		
		if ( $prdcat == "e" ) {
			if ( $cate_mon < 1 || $cate_mon > 12 ) {
				return select("<li class=err>Invalid month selected.</li>");
			}
		}
	} else if ( $vatreg != "no" ) {
		return vatreg("<li class=err>Invalid Vat registration option selected.</li>");
	}
		
	db_conn("cubit");
	$sql = "UPDATE settings SET value='$vatreg' WHERE constant='VAT_REG'";
	$rslt = db_exec($sql) or errDie("Error updating vat registration settings.");
	
	if ( $vatreg == "yes" ) {
		$sql = "UPDATE settings SET value='14' WHERE constant='TAX_VAT' AND value='0'";
		$rslt = db_exec($sql) or errDie("Error updating vat registered setting.");
	
		if ( $prdcat == "e" ) {
			$save = "$prdcat$cate_mon";
		} else {
			$save = "$prdcat";
		}
			
		$sql = "UPDATE settings SET value='$save' WHERE constant='TAX_PRDCAT'";
		$rslt = db_exec($sql) or errDie("Error updating tax period category.");
		
		$OUTPUT = "
		<h3>Tax Period</h3>
		Successfully updated your Tax period.";
	} else {
		$sql = "UPDATE settings SET value='0' WHERE constant='TAX_VAT'";
		$rslt = db_exec($sql) or errDie("Error updating vat registered setting.");
		
		$OUTPUT = "
		<h3>Vat Registered</h3>
		Successfully updated your Vat registration settings.";
	}
	
	return $OUTPUT;
}

?>

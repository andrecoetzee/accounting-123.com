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

require ('settings.php'); 
require ('core-settings.php');

// Decide what to do
if (isset($HTTP_POST_VARS['key'])) {
	switch ($HTTP_POST_VARS['key']) {
		case 'write':
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
		case 'enter':
			$OUTPUT = enter();
	}
} else {
	$OUTPUT = enter();
}

require("template.php");

/// entry function
function enter($args="")
{
	// Fetch saved data from Cubit
	db_conn("core");
	$sql = "SELECT * FROM save_journal WHERE userid='".USER_ID."' AND div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error reading journal data.");

	$saved_data = "";
	$i = 0;

	$deb_totl = 0.00;
	$cred_totl = 0.00;

	db_conn("core");
	$sql = "SELECT accid, topacc, accnum, accname FROM accounts WHERE div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error reading accounts.");

	$arr_gen = array();
	while ( $row = pg_fetch_array($rslt) ) {
		$arr_gen[$row["accid"]] = "$row[topacc]/$row[accnum] $row[accname]";
	}

	db_conn("cubit");
	$sql = "SELECT cusnum, cusname, surname FROM customers WHERE div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error fetching customer list.");

	$arr_cus = array();
	while ( $row = pg_fetch_array($rslt) ) {
		$arr_cus[$row["cusnum"]] = "$row[surname], $row[cusname]";
	}

	db_conn("cubit");
	$sql = "SELECT supid, supname FROM suppliers WHERE div='".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Error reading list of suppliers.");

	$arr_sup = array();
	while ( $row = pg_fetch_array($rslt) ) {
		$arr_sup[$row["supid"]] = "$row[supname]";
	}

	$ledger_lst = array(1=>'General Ledger', 2=>'Customer Ledger', 3=>'Employee Ledger');

	$bgcolor = "";
	while ($jrn = pg_fetch_array($rslt)) {
		// Get the background color for the selected row
		$bgcolor = ($i % 2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
		$i++;

		$deb_totl += $jrn['debit'];
		$cred_totl += $jrn['credit'];
		
		// Date
		$date = explode("-", $jrn['date']);

		// Ledger
		$ledgers = extlib_cpsel("ledger[$jrn[id]]", $ledger_lst, $jrn["ledger"], "onClick='update_accs($jrn[ledger], $jrn[id]);'");
		$gen_accs = extlib_cpsel("accid[$jrn[id]]", $arr_gen, $jrn["accid"], $jrn["ledger"]!=1?"style='visibility: hidden;'":"");
		$stk_accs = extlib_cpsel("cusnum[$jrn[id]]", $arr_cus, $jrn["cusnum"], $jrn["ledger"]!=2?"style='visibility: hidden;'":"");
		$cus_accs = extlib_cpsel("supid[$jrn[id]]", $arr_sup, $jrn["supid"], $jrn["ledger"]!=3?"style='visibility: hidden;'":"");

		// Display the saved data
		$saved_data .= "
		<tr bgcolor='$bgcolor'>
			<td>
		  		<input type=checkbox name=rem[$jrn[id]]>
		  	</td>
		  	<td>
		  		<input type=text size=2 name=date_day value='$date[2]'> -
		    	<input type=text size=2 name=date_month value='$date[1]'> -
		  		<input type=text size=4 name=date_year value='$date[0]'>
		  	</td>
		  	<td>$ledgers</td>
		  	<td>
				<input type=text name=acc_txt[$jrn[id]] value='' style='width: 180'><br>
		  		$gen_accs
		  		$stk_accs
		  		$cus_accs
		  	</td>
		  	<td align=center>
		    	<input type=text size=20 name=details[$jrn[id]] value='$jrn[details]'>
		  	</td>
		  	<td align=center>
		    	<input type=text size=4 name=refnum[$jrn[id]] value='$jrn[refnum]' align=center>
		  	</td>
		  	<td align=center>
		    	<input type=text size=7 name=debit[$jrn[id]] value='$jrn[debit]' align=right>
		  	</td>
		  	<td align=center>
		    	<input type=text size=7 name=credit[$jrn[id]] value='$jrn[credit]' align=right>
		  	</td>
		    	<input type=hidden name=id[] value='$jrn[id]'>
		</tr>";
	}

	$new_data = "";
	if ($args == "addline") {
		$fields["ndate_day"] = date("d");
		$fields["ndate_month"] = date("m");
		$fields["ndate_year"] = date("Y");
		$fields["nledger"] = 1;
		$fields["naccid"] = 0;
		$fields["ncusnum"] = 0;
		$fields["nsupid"] = 0;
		$fields["details"] = "";
		$fields["acc_txt"] = "";
		$fields["refnum"] = 0;
		$fields["debit"] = 0;
		$fields["credit"] = 0;

		foreach ( $fields as $k => $v ) {
			if ( ! isset($$k) ) $$k = $v;
		}

		$ledgers = extlib_cpsel("nledger", $ledger_lst, $jrn["ledger"], "onClick='update_accs($jrn[ledger], -1);'");
		$gen_accs = extlib_cpsel("naccid", $arr_gen, $naccid, $nledger!=1?"style='visibility: hidden;'");
		$cus_accs = extlib_cpsel("ncusnum", $arr_cus, $ncusnum, $nledger!=2?"style='visibility: hidden;'");
		$sup_accs = extlib_cpsel("nsupid", $arr_sup, $nsupid, $nledger!=3?"style='visibility: hidden;'");

		$bgcolor = ($bgcolor == TMPL_tblDataColor1) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		
		// New Data
		$new_data .= "<tr bgcolor='$bgcolor'>
	  	<td>
	    	<input type=checkbox name=nrem>
	  	</td>
	  	<td>
	    	<input type=text size=2 name=ndate_day value='".date("d")."'> -
	    	<input type=text size=2 name=ndate_month value='".date("m")."'> -
	    	<input type=text size=4 name=ndate_year value='".date("Y")."'>
	  	</td>
	  	<td>$ledgers</td>
	  	<td>
			<input type=text name=nacc_txt value='' style='width: 180'><br>
	  		$gen_accs
	  		$cus_accs
	  		$sup_accs
	  	</td>
	  	<td align=center>
	    	<input type=text size=20 name='ndetails' value=''>
	  	</td>
	  	<td align=center>
	    	<input type=text size=4 name='nrefnum' value='0'>
	  	</td>
	  	<td align=center>
	    	<input type=text size=7 name='ndebit' value='0.00' align=right>
	  	</td>
	  	<td align=center>
	    	<input type=text size=7 name='ncredit' value='0.00' align=right>
	  	</td>
		</tr>";
	}

	$bgcolor = ($bgcolor == TMPL_tblDataColor1) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	
	// Start the output
	$OUTPUT = "<h3 align=center>Journal Transactions</h3>
	<form method=post action='".SELF."'>
	<table align=center border=0 cellspacing='".TMPL_tblCellSpacing." cellpadding='".TMPL_tblCellPadding."'>
		<tr>
			<th> </th>
	    	<th>Date</th>
	    	<th>Ledger</th>
	    	<th>Ledg / Cust / Supp Acc</th>
	    	<th>Details</th>
	    	<th>Ref No.</th>
	    	<th>Debit - ".CUR."</th>
	    	<th>Credit - ".CUR."</th>
	  	</tr>
	  	$saved_data
	  	$new_data
	  	<tr>
	  		<td colspan=6>
				<input type=hidden name=key value='write'>
				<input type=submit name=remove value='Remove selected'>
				<input type=submit name=addline value='Add Line'>
				<input type=submit name=save value='Save'>
				<input type=submit name=process value='Process &raquo'>
			</td>
			<td bgcolor='$bgcolor' align=center><b>".sprint($deb_totl)."</b></td>
			<td bgcolor='$bgcolor' align=center><b>".sprint($cred_totl)."</b></td>
		</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function write($HTTP_POST_VARS)
{
	extract ($HTTP_POST_VARS);

	// Remove
	if (isset($remove)) {
		if (isset($rem)) {
			foreach ($rem as $key => $val) {
				db_conn("core");
				$sql = "DELETE FROM save_journal WHERE id='$key'";
				$rslt = db_exec($sql) or errDie("Error updating journal data (DEL).");
			}
		}
		return enter();
	}

	// Save data
	if (isset($id)) {
		foreach ($id as $val) {
			$date = "$date_year-$date_month-$date_day";
			db_conn("core");
			$sql = "UPDATE save_journal	SET date='$date', ledger='$ledger[$val]', accid='$accid[$val]', details='$details[$val]', refnum='$refnum[$val]', debit='$debit[$val]', credit='$credit[$val]' WHERE id='$val'";
			$rslt = db_exec($sql) or errDie("Error updating journal data (UPD).");
		}
	}

	if (isset($naccid) && !isset($nrem)) {
		$ndate = "$ndate_year-$ndate_month-$ndate_day";
		db_conn("core");
		$sql = "INSERT INTO save_journal (date, ledger, accid, details, refnum, debit, credit, userid, div) VALUES ('$ndate', '$nledger', '$naccid', '$ndetails', '$nrefnum', '$ndebit', '$ncredit', '".USER_ID."', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Error updating journal data (INS).");
	}

	if ( isset($addline) ) {
		return enter("addline");
	}

	// Process
	if ( isset($process) ) {
		db_conn("core");
		$sql = "SELECT * FROM save_journal WHERE userid='".USER_ID."' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Error reading data for process.");

		while ( $row = pg_fetch_array($rslt) ) {
			if ( $row["debit"] > 0 ) {
				writetrans($row["accid"], 0, $row["date"], $row["refnum"], $row["debit"], $row["details"]);
			} else if ( $row["credit"] > 0 ) {
				writetrans(0, $row["accid"], $row["date"], $row["refnum"], $row["debit"], $row["details"]);
			}
		}

		db_conn("core");
		$sql = "DELETE FROM save_journal WHERE userid='".USER_ID."' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Error updating journal data (PROCDEL)");

		return "<h3>Journal Transactions</h3>Successfully processed transactions.";
	}
	
	return enter();
}
?>
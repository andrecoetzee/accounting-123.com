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

require("settings.php");

if(isset($_POST["key"])) {
	$_POST["key"] = strtolower($_POST["key"]);
	switch($_POST["key"]) {
		default:
		case "report":
			$OUTPUT = report();
			break;
		case "print":
			$OUTPUT = print_report();
			break;
	}
} else {
	$OUTPUT=seluse();
}

require("template.php");

function seluse()
{
	$mets = "
			<select name='met'>
				<option value='all'>All</option>
				<option value='Cash'>Cash</option>
				<option value='Cheque'>Cheque</option>
				<option value='Credit Card'>Credit Card</option>
			</select>";


	db_conn("cubit");
	$Sl="SELECT DISTINCT by FROM payrec ORDER BY by";
	$Ry=db_exec($Sl) or errDie("Unable to get users from pos rec.");

	$users = "
			<select name='user'>
				<option value='0'>All</option>";

	while($data=pg_fetch_array($Ry)) {
		$users .= "<option value='$data[by]'>$data[by]</option>";
	}

	$users .= "</select>";

	$Out = "
			<h3>POS Report</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='report'>
				<tr>
					<th colspan='2'>Report Criteria</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>User</td>
					<td>$users</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date</td>
					<td>".mkDateSelect("date")."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Type</td>
					<td>$mets</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Starting Amount</td>
					<td><input type='text' size='20' name='amount'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Display Sales</td>
					<td>
						Yes <input type='radio' name='disp_sales' value='yes' checked \><b> | </b>
						No <input type='radio' name='disp_sales' value='no' \>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right'>
						<input type='submit' value='View Report &raquo;'>
					</td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr><th>Quick Links</th></tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='reporting/index-reports.php'>Financials</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='reporting/index-reports-other.php'>Other Reports</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

	return $Out;
}

function report()
{

	extract($_POST);

	$date = $date_year."-".$date_month."-".$date_day;
        $amount+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($user, "string", 1, 50, "Invalid user.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($disp_sales, "string", 1, 3, "Invalid display sales selection.");

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid order date.");
	}

	$met=remval($met);

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}



	if($user!="0") {
		$whe=" AND by='$user'";
		$whe_sales = "AND username='$user'";
	} else {
		$whe="";
		$whe_sales = "";
	}

	if($met!="all") {
		$whe.=" AND method='$met'";
	} else {
		$whe.="";
	}

	// Display the sales
	if ($disp_sales == "yes") {

		if(substr($date_month,0,1) == "0")
			$date_month = substr($date_month,1,1);

		// Retrieve the sales if neccessary
//		db_conn((int)$date_month); // connect to the disired period
		db_connect ();
//		$sql = "SELECT * FROM \"$date_month\".pinvoices WHERE odate = '$date' $whe_sales ORDER BY invid ASC";
		$sql = "SELECT 'inv' AS type, invid, invnum, '0' AS noteid, '0' AS notenum, iprd AS prd
					FROM prd_pinvoices WHERE odate = '$date' $whe_sales
				UNION
				SELECT 'note' AS type, n.invid, n.invnum, n.noteid, n.notenum,p.prd::text AS prd
					FROM \"$date_month\".inv_notes n, cubit.payrec p 
					WHERE n.noteid=p.note AND n.invnum=p.inv AND odate='$date' $whe_sales
				ORDER BY invid ASC";
		$pinv_rslt = db_exec($sql) or errDie("Unable to retrieve the pos sales from Cubit.");

		$i = 0;
		$sales_out = "<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th colspan='6'>Sales</th>
			</tr>
			<tr>
				<th>Stock Code</th>
				<th width='10%'>Unit Price</th>
				<th width='5%'>Qty</th>
				<th width='10%'>Discount</th>
				<th width='5%'>VAT Code</th>
				<th width='10%'>Total</th>
			</tr>";

		$totals = array();
		while ($pinv_data = pg_fetch_array($pinv_rslt)) {
//			db_conn((int)$date_month);
			db_conn($pinv_data['prd']);
			if ($pinv_data["type"] == "inv") {
				$sql = "SELECT * FROM pinv_items WHERE invid='$pinv_data[invid]'";
			} else {
				$sql = "SELECT * FROM inv_note_items WHERE noteid='$pinv_data[noteid]'";
			}
			$item_rslt = db_exec($sql) or errDie("Unable to retrieve pos sales items from Cubit.");

			$totals["unitcost"] = 0;
			$totals["disc"] = 0;
			$totals["amt"] = 0;

			if ($pinv_data["type"] == "inv") {
				$sales_out .= "
				<tr>
					<th colspan='6' align='left'>Invoice No: $pinv_data[invnum]</th>
				</tr>";
			} else {
				$sales_out .= "
				<tr>
					<th colspan='6' align='left'>Credit Node No: $pinv_data[notenum] (Invoice: $pinv_data[invnum])</th>
				</tr>";
			}

			while ($item_data = pg_fetch_array($item_rslt)) {
				db_conn("cubit");
				if ($item_data["stkid"] == "0") {
					$stock_data["stkcod"] = $item_data["description"];
				} else {
					$sql = "SELECT * FROM stock WHERE stkid='$item_data[stkid]'";
					$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock information from Cubit.");
					$stock_data = pg_fetch_array($stock_rslt);
				}
				
				if ($pinv_data["type"] == "note") {
					$item_data["unitcost"] = $item_data["amt"] / $item_data["qty"];
				}
				
				$vatcode_info = qryVatcode($item_data["vatcode"]);

				$sales_out .= "
					<tr bgcolor='".bgcolor($i)."'>
						<td>$stock_data[stkcod]</td>
						<td align='right'>".sprint($item_data["unitcost"])."</td>
						<td align='center'>$item_data[qty]</td>
						<td align='right'>".sprint($item_data["disc"])."</td>
						<td align='center'>$vatcode_info[code]</td>
						<td align='right'>".sprint($item_data["amt"])."</td>
					</tr>";

				// Add to the totals
				$totals["unitcost"] += $item_data["unitcost"];
				$totals["disc"] += $item_data["disc"];
				$totals["amt"] += $item_data["amt"];
			}
			$sales_out .= "
				<tr bgcolor='".bgcolor($i)."'>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["unitcost"])."</b></td>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["disc"])."</b></td>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["amt"])."</b></td>
				</tr>";
		}
		
		/* credit notes */
		// Retrieve the sales if neccessary
		db_conn((int)$date_month); // connect to the disired period
		$sql = "SELECT n.* FROM \"$date_month\".inv_notes n, cubit.payrec p 
				WHERE n.noteid=p.note AND n.invnum=p.inv AND odate='$date' $whe_sales
				ORDER BY noteid ASC";
		/*$pinv_rslt = db_exec($sql) or errDie("Unable to retrieve the pos sales from Cubit.");
		
		while ($pinv_data = pg_fetch_array($pinv_rslt)) {
			db_conn((int)$date_month);
			$sql = "SELECT * FROM inv_note_items WHERE noteid='$pinv_data[noteid]'";
			$item_rslt = db_exec($sql) or errDie("Unable to retrieve pos sales items from Cubit.");

			$totals["unitcost"] = 0;
			$totals["disc"] = 0;
			$totals["amt"] = 0;

			$sales_out .= "
						<tr>
							<th colspan='6' align='left'>Credit Node No: $pinv_data[notenum] (Invoice: $pinv_data[invnum])</th>
						</tr>";

			while ($item_data = pg_fetch_array($item_rslt)) {
				db_conn("cubit");
				$sql = "SELECT * FROM stock WHERE stkid='$item_data[stkid]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock information from Cubit.");
				$stock_data = pg_fetch_array($stock_rslt);
				
				$item_data["unitcost"] = $item_data["amt"] / $item_data["qty"];

				$sales_out .= "
					<tr bgcolor='".bgcolor($i)."'>
						<td>$stock_data[stkcod]</td>
						<td align='right'>".sprint($item_data["unitcost"])."</td>
						<td align='center'>$item_data[qty]</td>
						<td align='right'>".sprint($item_data["disc"])."</td>
						<td align='center'>$item_data[vatcode]</td>
						<td align='right'>".sprint($item_data["amt"])."</td>
					</tr>";

				// Add to the totals
				$totals["unitcost"] += $item_data["unitcost"];
				$totals["disc"] += $item_data["disc"];
				$totals["amt"] += $item_data["amt"];
			}
			$sales_out .= "
				<tr bgcolor='".bgcolor($i)."'>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["unitcost"])."</b></td>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["disc"])."</b></td>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["amt"])."</b></td>
				</tr>";
		}*/
		$sales_out .= "</table>";
	} else {
		$sales_out = "";
	}

	db_conn("cubit");
	$sql = "SELECT * FROM payrec WHERE date='$date' $whe";
	$rslt = db_exec($sql) or errDie("Unable to retrieve pos report from Cubit.");

	$cash = $cheque = $credit_card = $credit = $sales = 0;
	while ($rec_data = pg_fetch_array($rslt)) {
		switch (strtolower($rec_data["method"])) {
			case "cash":
				$cash += $rec_data["amount"];
				break;
			case "cheque":
				$cheque += $rec_data["amount"];
				break;
			case "credit card":
				$credit_card += $rec_data["amount"];
				break;
			case "credit":
				$credit += $rec_data["amount"];
				break;
		}
		$sales += $rec_data["amount"];
	}

	db_conn('cubit');
	$Sl="SELECT sum(amount) FROM payrec WHERE date='$date' $whe";
	$Ry=db_exec($Sl) or errDie("Unable to get pos rec.");
	$data=pg_fetch_array($Ry);

	$amount=sprint($amount);
	$expected=sprint($amount+$sales);

	if (!$user) {
		$user_out = "All Users";
	} else {
		$user_out = $user;
	}

	$Report = "
		<h3>POS Report: $date - $user_out</h3>
		$sales_out
		<table ".TMPL_tblDflts." style='width: 100%'>
			<tr>
				<th colspan=2>Report</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Starting Amount</td>
				<td align='right' width='10%'>".CUR." $amount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cash</td>
				<td align='right' width='10%'>".sprint($cash)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque</td>
				<td align='right' width='10%'>".sprint($cheque)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Credit Card</td>
				<td align='right' width='10%'>".sprint($credit_card)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Credit</td>
				<td align='right' width='10%'>".sprint($credit)."</td>
			</td>
			<tr bgcolor='".bgcolorg()."'>
				<td>Expected Amount</td>
				<td align='right' width='10%'><b>".CUR." $expected</b></td>
			</tr>
		</table>
		<p>
		<center>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='report'>
			<input type='hidden' name='met' value='$met'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='user' value='$user'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='disp_sales' value='$disp_sales' \>
			<input type='submit' name='key' value='Print'>
		</form>
		</center>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='reporting/index-reports-other.php'>Other Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	
	return $Report;
}

function print_report() {

	extract($_POST);

	$date = $date_year."-".$date_month."-".$date_day;
        $amount+=0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($user, "string", 1, 50, "Invalid user.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($disp_sales, "string", 1, 3, "Invalid display sales selection.");

	if(!checkdate($date_month, $date_day, $date_year)){
                $v->isOk ($date, "num", 1, 1, "Invalid order date.");
        }

	$met=remval($met);

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>$e[msg]</li>";
		}
		return $confirm;
	}

	if($user!="0") {
		$whe=" AND by='$user'";
		$whe_sales = "AND username='$user'";
	} else {
		$whe="";
		$whe_sales = "";
	}

	if($met!="all") {
		$whe.=" AND method='$met'";
	} else {
		$whe.="";
	}

	// Display the sales
	if ($disp_sales == "yes") {
		// Retrieve the sales if neccessary
		db_conn((int)$date_month); // connect to the disired period
		$sql = "SELECT * FROM pinvoices WHERE odate='$date' $whe_sales ORDER BY invid ASC";
		$pinv_rslt = db_exec($sql) or errDie("Unable to retrieve the pos sales from Cubit.");

		$i = 0;
		$sales_out = "<table cellpadding='1' cellspacing='0' width='100%' style='border: 1px solid #000'>
			<tr>
				<th colspan='6' align='center'>Sales</th>
			</tr>
			<tr>
				<td style='border-bottom: 1px solid #000;'>
					<b>Stock Code</b>
				</td>
				<td width='10%' align='right' style='border-bottom: 1px solid #000;'>
					<b>Unit Price</b>
				</td>
				<td width='10%' align='center' style='border-bottom: 1px solid #000;'>
					<b>Qty</b>
				</td>
				<td width='10%' align='right' style='border-bottom: 1px solid #000;'>
					<b>Discount</b>
				</td>
				<td width='10%' align='center' style='border-bottom: 1px solid #000;'>
					<b>VAT Code</b>
				</td>
				<td width='10%' align='right' style='border-bottom: 1px solid #000;'>
					<b>Total</b>
				</td>
			</tr>";

		$totals = array();
		while ($pinv_data = pg_fetch_array($pinv_rslt)) {
			db_conn((int)$date_month);
			$sql = "SELECT * FROM pinv_items WHERE invid='$pinv_data[invid]'";
			$item_rslt = db_exec($sql) or errDie("Unable to retrieve pos sales items from Cubit.");

			$totals["unitcost"] = 0;
			$totals["disc"] = 0;
			$totals["amt"] = 0;

			$sales_out .= "
						<tr>
							<td colspan='6' align='left'><b>- Invoice No: $pinv_data[invnum]</b></td>
						</tr>";

			while ($item_data = pg_fetch_array($item_rslt)) {
				db_conn("cubit");
				$sql = "SELECT * FROM stock WHERE stkid='$item_data[stkid]'";
				$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock information from Cubit.");
				$stock_data = pg_fetch_array($stock_rslt);

				$sales_out .= "
					<tr>
						<td>$stock_data[stkcod]</td>
						<td align='right'>".sprint($item_data["unitcost"])."</td>
						<td align='center'>$item_data[qty]</td>
						<td align='right'>".sprint($item_data["disc"])."</td>
						<td align='center'>$item_data[vatcode]</td>
						<td align='right'>".sprint($item_data["amt"])."</td>
					</tr>";

				// Add to the totals
				$totals["unitcost"] += $item_data["unitcost"];
				$totals["disc"] += $item_data["disc"];
				$totals["amt"] += $item_data["amt"];
			}
			$sales_out .= "
				<tr>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["unitcost"])."</b></td>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["disc"])."</b></td>
					<td>&nbsp</td>
					<td align='right'><b>".sprint($totals["amt"])."</b></td>
				</tr>";
		}
		$sales_out .= "</table>";
	} else {
		$sales_out = "";
	}

	db_conn("cubit");
	$sql = "SELECT * FROM payrec WHERE date='$date' $whe";
	$rslt = db_exec($sql) or errDie("Unable to retrieve pos report from Cubit.");

	$cash = $cheque = $credit_card = $credit = $sales = 0;
	while ($rec_data = pg_fetch_array($rslt)) {
		switch (strtolower($rec_data["method"])) {
			case "cash":
				$cash += $rec_data["amount"];
				break;
			case "cheque":
				$cheque += $rec_data["amount"];
				break;
			case "credit card":
				$credit_card += $rec_data["amount"];
				break;
			case "credit":
				$credit += $rec_data["amount"];
				break;
		}
		$sales += $rec_data["amount"];
	}

	db_conn('cubit');
	$Sl="SELECT sum(amount) FROM payrec WHERE date='$date' $whe";
	$Ry=db_exec($Sl) or errDie("Unable to get pos rec.");
	$data=pg_fetch_array($Ry);

	$amount=sprint($amount);
	$expected=sprint($amount+$sales);

	if (!$user) $user = "All Users";

	$OUTPUT = "
		<h3>POS Report: $date - $user</h3>
		$sales_out
		<table ".TMPL_tblDflts." width='100%' style='border: 1px solid #000'>
		<tr>
			<th colspan=2>Report</th>
		</tr>
		<tr>
			<td>Starting Amount</td>
			<td align='right' width='10%'>".CUR." $amount</td>
		</tr>
		<tr>
			<td>Cash</td>
			<td align='right' width='10%'>".sprint($cash)."</td>
		</tr>
		<tr>
			<td>Cheque</td>
			<td align='right' width='10%'>".sprint($cheque)."</td>
		</tr>
		<tr>
			<td>Credit Card</td>
			<td align='right' width='10%'>".sprint($credit_card)."</td>
		</tr>
		<tr>
			<td>Credit</td>
			<td align='right' width='10%'>".sprint($credit)."</td>
		</td>
		<tr>
			<td>Expected Amount</td>
			<td align='right' width='10%'><b>".CUR." $expected</b></td>
		</tr>
		</table>";

	require ("tmpl-print.php");
}


?>
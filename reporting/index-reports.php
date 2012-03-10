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

require ("../settings.php");

$OUTPUT = "
	<center>
	<table border='0' width='90%'>
		<tr>
			<td valign='top' width='33%'>
				<table width='90%'>
					<tr><td align='center'><h3>Banking</h3></td></tr>
					<tr><td align='center'><b><a href=# onClick=printer2('reporting/bank-recon') class=nav>Bank Reconciliation</a></b></td></tr>
					<tr><td align='center'><b><a href='not-banked.php' class=nav>List Outstanding Bank Payments/Receipts</a></b></td></tr>
					<tr><td align='center'><b><a href='banked.php' class=nav>Cash Book Analysis of Payments/Receipts</a></b></td></tr>
					<tr><td align='center'><b><a href='bank-recon-saved.php' class=nav>View Saved Bank Reconciliations</a></b></td></tr>
				</table>
			</td>
			<td valign=top width='33%'>
				<table border=0 width='90%'>
					<tr><td align='center'><h3>Other</h3></td></tr>
					<tr><td align='center'><b><a href='stock-ledger.php' class='nav'>Inventory Ledger</a></b></td></tr>
					<tr><td align='center'><b><a href='../salwages/employee-ledger.php' class='nav'>Employee Ledger</a></b></td></tr>
					<tr><td align='center'><b><a href='reports-vat.php' class='nav'>View VAT Report</a></b></td></tr>
					<tr><td align='center'><b><a href='reports-vat-sum.php' class='nav'>View VAT Summary Report</a></b></td></tr>
					<tr><td align='center'><b><a href='vat-ledger-report.php' class='nav'>View VAT Ledger Report</a></b></td></tr>
					<tr><td align='center'><b><a href='vat_return_report.php' class='nav'>VAT 201</a></b></td></tr>
					<tr><td align='center'><b><a href='../vat-report-view.php' class='nav'>View Saved VAT 201's</a></b></td></tr>
					<tr><td align='center'><b><a href='../core/period-view.php' class='nav'>View Current Period</a></b></td></tr>
					<tr><td align='center'><b><a href='../pos-report-user.php' class='nav'>POS Cash Report</a></b></td></tr>
					<tr><td align='center'><b><a href='../pos-report-sales.php' class='nav'>POS Sales Report</a></b></td></tr>
				</table>
			</td>
			<td valign='top' width='33%'>
				<table width='90%'>
					<tr><td align='center'><h3>Debtors & Creditors</h3></td></tr>
					<tr><td align='center'><b><a href='debt-age-analysis.php' class='nav'>Debtors Age Analysis</a></b></td></tr>
					<tr><td align='center'><b><a href='cust-ledger.php' class='nav'>Debtors Ledger</a></b></td></tr>
					<tr><td align='center'><b><a href='cred-age-analysis.php' class='nav'>Creditors Age Analysis</a></b></td></tr>
					<tr><td align='center'><b><a href='supp-ledger.php' class='nav'>Creditors Ledger</a></b></td></tr>
				</table>
			</td>
		</tr>

		<tr>
			<td valign='top' width='33%'>
				<table width='90%'>
					<tr><td align='center'><h3>Accounts</h3></td></tr>
					<tr><td align='center'><b><a href='allcat.php' class='nav'>ALL Categories and Related Accounts</a></b></td></tr>
					".TBL_BR."
					".TBL_BR."
					<tr>
						<td align='center'>"
							.mkQuickLinks(
								ql("index-reports.php", "All Report Options"),
								ql("index-reports-banking.php", "Banking Reports"),
								ql("index-reports-stmnt.php", "Current Year Financial Statements"),
								ql("index-reports-debtcred.php", "Debtors & Creditors Reports"),
								ql("index-reports-journal.php", "General Ledger Reports"),
								ql("index-reports-other.php", "Other Reports"),
								ql("../core/acc-new2.php", "Add New Journal Account")
							)."
						</td>
					</tr>
				</table>
			</td>
			<td valign='top' width='33%'>
				<table border='0' width='90%'>
					<tr><td align='center'>
						<a href='javascript:popupSized(\"../health_report.php\", \"Health Report\", screen.width, screen.height)'>
							<img src='../images/cubithealth3.jpg' border='no' />
						</a>
					</td></tr>
					<tr><td align='center'><h3>Financial Statements</h3></td></tr>
					<tr><td align='center'><b><a href='trial_bal.php' class='nav'>Generate Trial Balance</a></b></td></tr>
					<tr><td align='center'><b><a href='trial_bal-view.php' class='nav'>View Saved Trial Balances</a></b></td></tr>
					<tr><td align='center'><b><a href='income-stmnt.php' class='nav'>Generate Income Statement</a></b></td></tr>
					<tr><td align='center'><b><a href='income-stmnt-view.php' class='nav'>View Saved Income Statements</a></b></td></tr>
					<tr><td align='center'><b><a href='bal-sheet.php' class='nav'>Generate Balance Sheet</a></b></td></tr>
					<tr><td align='center'><b><a href='bal-sheet-view.php' class='nav'>View Saved Balance Sheets</a></b></td></tr>
					<tr><td align='center'><b><a href='cash-flow.php' class='nav'>Generate Statement of Cash Flow</a></b></td></tr>
					<tr><td align='center'><b><a href='cash-flow-view.php' class='nav'>View Saved Cash Flow Statements</a></b></td></tr>
				</table>
			</td>
			<td valign='top' width='33%'>
				<table width='90%'>
					<tr><td align='center'><h3>General Ledger</h3></td></tr>
					<tr><td align='center'><b><a href='ledger-summary.php' class='nav'>Ledger Report</a></b></td></tr>
					<tr><td align='center'><b><a href='ledger.php' class='nav'>Individual Ledger Accounts</a></b></td></tr>
					<tr><td align='center'><b><a href='ledger-prd.php' class='nav'>Period Range General Ledger</a></b></td></tr>
					<tr><td align='center'><b><a href='alltrans-refnum.php' class='nav'>Detailed General Ledger</a></b></td></tr>
					<tr><td align='center'><b><a href='ledger-ytd.php' class='nav'>Year Review General Ledger</a></b></td></tr>
					<tr><td align='center'><b><a href='ledger_export.php' class='nav'>Export Account Movement Report</a></b></td></tr>
					<tr><td>&nbsp;</td></tr>
					<tr><td align='center'><h3>Journals</h3></td></tr>
					<tr><td align='center'><b><a href='alltrans.php' class='nav'>All Journal Entries</a></b></td></tr>
					<tr><td align='center'><b><a href='trans-amt.php' class='nav'>All Journal Entries By Ref no.</a></b></td></tr>
					<tr><td align='center'><b><a href='alltrans-prd.php' class='nav'>All Journal Entries (Period Range)</a></b></td></tr>
					<tr><td align='center'><b><a href='acc-trans.php' class='nav'>Journal Entries Per Account</a></b></td></tr>
					<tr><td align='center'><b><a href='acc-trans-prd.php' class='nav'>Journal Entries Per Account (Period Range)</a></b></td></tr>
					<tr><td align='center'><b><a href='accsub-trans.php' class='nav'>Journal Entries Per Main Account</a></b></td></tr>
					<tr><td align='center'><b><a href='cat-trans.php' class='nav'>Journal Entries Per Category</a></b></td></tr>
				</table>
			</td>
		</tr>
	</table>";
	require ("../template.php");

?>

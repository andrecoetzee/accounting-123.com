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
				<table border='0' width='100%'>
					<tr>
						<td valign=top width='100%' align='center'>
							<table border='0' width='100%'>
								<tr>
									<td align='center'><h3>Other</h3></td>
								</tr>
								<tr>
									<td align='center'><b><a href='stock-ledger.php' class=nav>Inventory Ledger</a></b></td>
								</tr>
								<tr>
									<td align='center'><b><a href='../salwages/employee-ledger.php' class=nav>Employee Ledger</a></b></td>
								</tr>
								<tr>
									<td align='center'><b><a href='reports-vat.php' class=nav>View VAT Report</a></b></td>
								</tr>
								<tr>
									<td align='center'><b><a href='vat_return_report.php' class=nav>VAT 201 Report</a></b></td>
								</tr>
								<tr>
									<td align='center'><b><a href='../core/period-view.php' class=nav>View Current Period</a></b></td>
								</tr>
								<tr>
									<td align='center'><b><a href='../pos-report-user.php' class=nav>POS Cash Report</a></b></td>
								</tr>
								<tr>
									<td align='center'><b><a href='../pos-report-sales.php' class=nav>POS Sales Report</a></b></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>"
				.mkQuickLinks(
					ql("index-reports.php", "All Report Options"),
					ql("index-reports-banking.php", "Banking Reports"),
					ql("index-reports-stmnt.php", "Current Year Financial Statements"),
					ql("index-reports-debtcred.php", "Debtors & Creditors Reports"),
					ql("index-reports-journal.php", "General Ledger Reports")
				);

require ("../template.php");
?>

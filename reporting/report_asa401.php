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
//require("core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
	$OUTPUT = show_report ();


# get templete
require("../template.php");

function show_report ()
{

	$display = "
			<center>
			<h2>ASA 401 assistance reports (System CIS Risk Management)</h2>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
				<tr><td align='center'><a href='report_asa401.php' style='color:white'>Automatically generated transaction system reports - Cubit v2.8</a></td></tr>
				<tr><td align='center'><a href='auditor_record.php' style='color:white'>Electronic Source Documentation and audit trial</a></td></tr>
				<tr><td align='center'><a href='report_asa401.php' style='color:white'>IT and access control report (With system Variances) - Cubit v2.8</a></td></tr>
				<tr><td align='center'><a href='report_asa401.php' style='color:white'>Mass updated file and data identification tool - report - Cubit v2.8</a></td></tr>
				<tr><td align='center'><a href='report_asa401.php' style='color:white'>IAPS 1008 variance identification (Access control error report, database integrity variance) - Cubit v2.8</a></td></tr>
				<tr><td align='center'><a href='report_asa401.php' style='color:white'>Viral and security breach report - Cubit v2.8</a></td></tr>
				<tr><td align='center'><a href='report_asa401.php' style='color:white'>Data Test Exports: Random, Systematic, Block, Monetary - Cubit v2.8</a></td></tr>
			</table>"
			.mkQuickLinks()
			."</center>";
	return $display;

}








?>

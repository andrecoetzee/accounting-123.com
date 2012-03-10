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

/*
# HEADER
$HEADER = "<<< Header Here >>>";

# TABLE HEADER
$HEADINGS = array('tit' => "DeTaiLs",'tit1' => "DeTaiLs 1",'tit2' => "DeTaiLs 1",'tit3' => "DeTaiLs 1");

# DATA
$DATA[] = array('tit' => "Account number : whatever", 'tit1' => "Account number : whatever", 'tit2' => "Account number : whatever", 'tit3' => "Account number : whatever", 'tit4' => "Account number : whatever");
$DATA[] = array('tit' => "Account number : whatever", 'tit1' => "Account number : whatever", 'tit2' => "Account number : whatever", 'tit3' => "Account number : whatever", 'tit4' => "Account number : whatever");
$DATA[] = array('tit' => "Account number : whatever", 'tit1' => "Account number : whatever", 'tit2' => "Account number : whatever", 'tit3' => "Account number : whatever", 'tit4' => "Account number : whatever");
$DATA[] = array('tit' => "Account number : whatever", 'tit1' => "Account number : whatever", 'tit2' => "Account number : whatever", 'tit3' => "Account number : whatever", 'tit4' => "Account number : whatever");
*/

# in case heads is not set
if(!isset($HEAD)){
	$HEAD = "";
}
if(!isset($HEAD2)){
	$HEAD2 = "";
}


/* Start PDF Layout */

include("../pdf-settings.php");
$pdf =& new Cezpdf();
$pdf ->selectFont($set_mainFont);

# put a line top and bottom on all the pages
$all = $pdf->openObject();
$pdf->saveState();
$pdf->setStrokeColor(0,0,0,1);
# $pdf->line(20,40,578,40);
# $pdf->line(20,822,578,822);
$pdf->addText(20,24,6,'Cubit Accounting');
$pdf->restoreState();
$pdf->closeObject();

# note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
# or 'even'.
$pdf->addObject($all,'all');

# Heading
$pdf->ezText("<b>$HEADER</b>", $set_txtSize+2, array('justification'=>'centre'));

# A new line
$pdf->ezText("\n", $set_txtSize-4);

# The Table
$pdf->ezTable($DATA, $HEADINGS, $HEAD, $set_repTblOpt);

# A new line
$pdf->ezText("\n", $set_txtSize-4);

# The Small Table
if(isset($DATA2) && is_array($DATA2)){
	$pdf->ezTable($DATA2, $HEADINGS, $HEAD2, $set_repTblOpt);
}

# Send stream
$pdf ->ezStream();

exit();
?>

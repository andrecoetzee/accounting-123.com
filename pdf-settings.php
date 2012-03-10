<?
/**
 * Functions and commonly used values for PDF Documents
 * @package Cubit
 * @subpackage PDF
 */
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



# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "pdf-settings.php") {
	exit;
}

include ('pdflibs/class.ezpdf.php');
/* Some settings */

# Fonts
$set_mainFont = 'pdflibs/fonts/Times-Roman.afm';
$set_codeFont = 'pdflibs/fonts/Courier.afm';

# Page width
$set_pgWidth = 560;
$set_pgHeight = 782;

# Y,X coordinates off the center
$set_pgXCenter = 297.14;
$set_pgYCenter = 420.95;

$set_tlX = 18;
$set_tlY = 752;
$set_txtSize = 14;

$set_ttlY = 782;

# Table options
$set_maxTblOpt  = array('showLines'=> 2, 'shaded'=> 0, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize-4, 'xOrientation'=>'center', 'xPos' => 'center', 'width' => $set_pgWidth, 'maxWidth' => $set_pgWidth);
$set_maxTblOptNl  = array('showLines'=> 1, 'shaded'=> 0, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize, 'xOrientation'=>'center', 'xPos' => 'center', 'width' => $set_pgWidth, 'maxWidth' => $set_pgWidth);
$set_repTblOpt  = array('showLines'=> 2, 'shaded'=> 0, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize-3, 'xOrientation'=>'center', 'xPos' => 'center', 'width' => ($set_pgWidth - ($set_pgWidth/4)), 'maxWidth' => $set_pgWidth);
$set_repTblOptSm  = array('showHeadings'=> 0, 'showLines'=> 2, 'shaded'=> 2, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize-3, 'xOrientation'=>'center', 'xPos' => 'center', 'maxWidth' => $set_pgWidth);

$set_tubTblOpt  = array('showLines'=> 0, 'shaded'=> 0, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize-4, 'xOrientation'=>'center', 'xPos' => 'center', 'width' => ($set_pgWidth - ($set_pgWidth/16)), 'maxWidth' => $set_pgWidth, 'rowGap' => 0);
$set_tubTblOpt2  = array('showLines'=> 0, 'shaded'=> 0, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize-4, 'xOrientation'=>'center', 'xPos' => 'center', 'width' => ($set_pgWidth - ($set_pgWidth/16)), 'maxWidth' => $set_pgWidth, 'rowGap' => 0, 'cols'=> array('first' => array('width'=>150), 'second' => array('width'=>150), 'third' => array('width'=>110), 'forth' => array('width'=>150)));
$set_tubTblOpt3  = array('showLines'=> 0, 'shaded'=> 0, 'innerLineThickness' => 0.5, 'fontSize'=> $set_txtSize-4, 'xOrientation'=>'center', 'xPos' => 'center', 'width' => ($set_pgWidth - ($set_pgWidth/16)), 'maxWidth' => $set_pgWidth, 'rowGap' => 0, 'cols'=> array('first' => array('width'=>260), 'second' => array('width'=>150), 'third' => array('width'=>150)));
/* Some useful fuctions */


/**
 * DEPRECATED: Truncates long string for PDF usage, use makewidth() instead
 *
 * @param string $data string on which you wish to perform the operation
 * @param integer $len length which to truncate to
 * @return string truncated string
 */
# Fix long string for pdf usage
function pdf_lstr($data, $len = 40){
	$data = str_replace("\n", " ", $data);

	if(strlen($data) > $len){
		$len = ($len - 3);
		$data = substr($data, 0, $len);
		$data = $data."...  ";
	}
	return $data;
}

/**
 * @ignore
 */
function pdf_addnl(&$pdf, $x, $y , $txtsize, $str){
	$str = str_replace("<br>", "\n", $str);
	$str = explode("\n", $str);
	foreach($str as $key => $line){
		$ys = ($y - ($txtsize * ($key+1)));
		$pdf->addText($x, $ys, $txtsize, "$line");
	}
	return $key+2;
}

/**
 * Easier way to draw pdf tables, with height and more humane coordinates.
 *
 * @param ezPdf $ezPdf Pdf object on which you wish to perform the operation
 * @param array $contents with the contents of the table
 * @param integer $x x coordinate
 * @param integer $y y coordinate
 * @param integer $width width of the table
 * @param integer $heigt heigt of the table
 * @param array $cols extra options to customize the table's columns
 * @param bool $headings determines if we should display the table headings
 * @return array table's x and y coordinates
 */
function drawTable($ezPdf, $contents, $x, $y, $width, $height, $cols=null, $headings=0)
{
        global $set_pgWidth, $set_pgHeight;
        $A4_WIDTH = 595.28;

        // Add new lines to the contents to define the height
        $ic = $height - count($contents);

        $keys = array();
        foreach ($contents as $uKey => $uVal) {
                foreach ($contents[$uKey] as $lKey => $lVal) {
                        $keys[] = $lKey;
                }
        }

        for ($i = 0; $i < $ic; $i++) {
                array_push($contents, array($keys[0]=>""));
        }

        // Start the coordinates from the top, rather than the bottom
        $xpos = $x + $width + ($A4_WIDTH - $set_pgWidth);
        $ypos = $set_pgHeight - $y;

        $ezPdf->ezSetY($ypos);

        $bottom_pos = @$ezPdf->ezTable($contents, '', '', array('showHeadings'=>$headings,
                'shaded'=>0, 'width'=>$width, 'xPos'=>$xpos, 'xOrientation'=>'left',
                'rowGap'=>1, 'cols'=>$cols));

        return (array('y'=>$set_pgHeight - $bottom_pos, 'x'=>$x + $width));
}

function drawTable2($ezPdf, $contents, $x, $y, $width, $height, $cols=null, $headings=0)
{
        global $set_pgWidth, $set_pgHeight;
        $A4_WIDTH = 595.28;

        // Add new lines to the contents to define the height
        $ic = $height - count($contents);

        $keys = array();
        foreach ($contents as $uKey => $uVal) {
                foreach ($contents[$uKey] as $lKey => $lVal) {
                        $keys[] = $lKey;
                }
        }

        for ($i = 0; $i < $ic; $i++) {
                array_push($contents, array($keys[0]=>""));
        }

        // Start the coordinates from the top, rather than the bottom
        $xpos = $x + $width + ($A4_WIDTH - $set_pgWidth);
        $ypos = $set_pgHeight - $y;

        $ezPdf->ezSetY($ypos);

        $bottom_pos = @$ezPdf->ezTable($contents, '', '', array('showHeadings'=>$headings,
                'shaded'=>0, 'width'=>$width, 'xPos'=>$xpos, 'xOrientation'=>'left',
                'rowGap'=>1, 'fontSize'=>7, 'titleFontSize'=>8, 'cols'=>$cols));

        return (array('y'=>$set_pgHeight - $bottom_pos, 'x'=>$x + $width));
}

/**
 * Draws a string of text on the pdf
 *
 * @param ezPdf $ezPdf Pdf object on which you wish to perform the operation
 * @param string $text text message to display
 * @param integer $size size of the font
 * @param integer $xpos x position
 * @param integer $ypos y position
 */
function drawText($ezPdf, $text, $size, $xpos, $ypos)
{
        global $set_pgHeight, $set_pgWidth;
        $A4_WIDTH = 595.28;

        $xpos = $xpos + ($A4_WIDTH - $set_pgWidth) - 5;

        $ezPdf->addText($xpos, ($set_pgHeight - $ypos), $size, $text);
        return (array('y'=>$ypos - $size + $size, 'x'=>$xpos));
}


/**
 * Limits the width of a string and appends '...' to the string
 *
 * @param ezPdf $ezPdf Pdf object on which you wish to perform the operation
 * @param string $targetw target width
 * @param integer $fontsize size of the font
 * @param string $str text content of the string
 * @return string the truncated string
 */
function makewidth($ezPdf, $targetw, $fontsize, $str) {
    if ($ezPdf->getTextWidth($fontsize, $str) < $targetw) {
        return $str;
    }

    while (!($ezPdf->getTextWidth($fontsize, "$str...") < $targetw)) {
        $str = substr($str, 0, strlen($str) - 1);
    }

    return "$str...";
}

/**
 * returns max amount of chars to meet width
 *
 * similiar to make width but returns at what character the string will have
 * an acceptable width
 *
 * @param ezPdf $ezPdf Pdf object on which you wish to perform the operation
 * @param string $targetw target width
 * @param integer $fontsize size of the font
 * @param string $str text content of the string
 * @return int
 */
function maxwidth($ezPdf, $targetw, $fontsize, $str) {
	if ($ezPdf->getTextWidth($fontsize, $str) < $targetw) {
        return strlen($str);
    }

    while (!($ezPdf->getTextWidth($fontsize, $str) < $targetw)) {
        $str = substr($str, 0, strlen($str) - 1);
    }

    return strlen($str);
}

/**
 * fixes a paragraph to not over take max lines or width at font size
 *
 * @param ezPdf $ezPdf Pdf object on which you wish to perform the operation
 * @param string $maxlines maximum amount of lines
 * @param string $maxwidth maximum width
 * @param integer $fontsize size of the font
 * @param string $str text content of the string
 * @return string the truncated string
 */
function fixparag($pdf, $maxlines, $maxwidth, $fontsize, $str) {
	if (strlen($str) == 0) return $str;
	$txtleft = preg_replace("/[\n]/", " ", $str);

	$lines = array();
	$done = false;
	while (count($lines) < $maxlines && !$done) {
		$mc = maxwidth(&$pdf, $maxwidth, $fontsize, $txtleft);

		// run until end of a word.
		while ($txtleft[$mc - 1] != ' ' && $mc < strlen($txtleft)) ++$mc;

		if ($mc == strlen($txtleft)) {
			$done = true;
		}

		$lines[] = substr($txtleft, 0, $mc);
		$txtleft = substr($txtleft, $mc);
	}

	if (strlen($txtleft) > 0) {
		$lines[$maxlines - 1] .= "...";
	}

	return preg_replace("/[\s][\s]/", " ", implode("\n", $lines));
}
?>

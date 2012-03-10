<?php @ob_start();
$bdata="120000000789";
$height="50";
$scale="2";
$bgcolor="#FFFFEC";
$color="#333366";
$file="";
$type="png";
$encode="";

if(isset($_POST['Genrate']))
{
	$encode=$_POST['encode'];
	$bdata=$_POST['bdata'];
	$height=$_POST['height'];
	$scale=$_POST['scale'];
	$bgcolor=$_POST['bgcolor'];
	$color=$_POST['color'];
	$file=$_POST['file'];
	$type=$_POST['type'];
}

?>
<HTML>
<HEAD>
<TITLE>Barcode Generator</TITLE>
<STYLE>
<!--

body,td{
	font-family:verdana;
	font-size:12px;
	font-weight:normal;
	color:#000066;
}
input {
	border:1px solid #336699;
}
.note{
	font-size:10px;
	color:#CC0000;
}
-->
</STYLE>
</HEAD>

<BODY>
<TABLE style='border:1px solid #330066'>
<TR>
	
	<TD>
	<TABLE style='border:1px solid #990000'>
	<form action='' method='POST'>
	<TR>
		<TD><B>Select Encoding</B></TD>
		<TD>:</TD>
		<TD><SELECT NAME="encode">
		<OPTION value='UPC-A' <?=$encode=='UPC-A'?'selected':''?>>UPC-A</OPTION>
		<OPTION value='EAN-13' <?=$encode=='EAN-13'?'selected':''?>>EAN-13</OPTION>
		<OPTION value='EAN-8' <?=$encode=='EAN-8'?'selected':''?>>EAN-8</OPTION>
		<OPTION value='UPC-E' <?=$encode=='UPC-E'?'selected':''?>>UPC-E</OPTION>
		<OPTION value='S205' <?=$encode=='S205'?'selected':''?>>STANDARD 2 OF 5</OPTION>
		<OPTION value='I2O5' <?=$encode=='I2O5'?'selected':''?>>INDUSTRIAL 2 OF 5</OPTION>
		<OPTION value='I25' <?=$encode=='I25'?'selected':''?>>INTERLEAVED</OPTION>
		<OPTION value='POSTNET' <?=$encode=='POSTNET'?'selected':''?>>POSTNET</OPTION>
		<OPTION value='CODABAR' <?=$encode=='CODABAR'?'selected':''?>>CODABAR</OPTION>
		<OPTION value='CODE128' <?=$encode=='CODE128'?'selected':''?>>CODE128</OPTION>
		<OPTION value='CODE39' <?=$encode=='CODE39'?'selected':''?>>CODE39</OPTION>
		<OPTION value='CODE93' <?=$encode=='CODE93'?'selected':''?>>CODE93</OPTION>
		</SELECT></TD>
	</TR>
	<TR>
		<TD><B>Barcode Data</B></TD>
		<TD>:</TD>
		<TD><input name='bdata' value='<?=$bdata?>'></TD>
	</TR>
	<TR>
		<TD><B>Barcode Height</B></TD>
		<TD>:</TD>
		<TD><input name='height' value='<?=$height?>'></TD>
	</TR>
	<TR>
		<TD><B>Scale</B></TD>
		<TD>:</TD>
		<TD><input name='scale' value='<?=$scale?>'></TD>
	</TR>
	<TR>
		<TD><B>Background Color</B></TD>
		<TD>:</TD>
		<TD><input name='bgcolor' value='<?=$bgcolor?>'></TD>
	</TR>
	<TR>
		<TD><B>Bar Color</B></TD>
		<TD>:</TD>
		<TD><input name='color' value='<?=$color?>'></TD>
	</TR>
	<TR>
		<TD><B>File Name</B><span class='note'>*</span></TD>
		<TD>:</TD>
		<TD><input name='file' size=9 value='<?=$file?>'>
		<SELECT NAME="type">
		<option value='png'>PNG</option>
		<option value='gif'>GIF</option>
		<option value='jpg'>JPEG</option>
		</SELECT>
		</TD>
	</TR>
	<TR>
		<TD align='center' colspan=3>
		<input type="submit" name='Genrate' value='Submit'>
		</TD>
	</TR>
	<TR>
		<TD align='left' colspan=3 class='note'>
		* Give file name if you want to save the barcode <br>else leave blank.
		</TD>
	</TR>
	</form>
	</TABLE>
	</TD>
	<TD height="100%"><TABLE style='border:1px solid #336666;width:300px;height:100%;'>
	<TR>
		<TD align='center'>
		<?php
		$qstr = "";
		if(isset($_POST['Genrate']))
		{
			if(empty($_POST['file']))
			{
				foreach($_POST as $key=>$value)
					$qstr.=$key."=".urlencode($value)."&";
				echo "<img src='barcode.php?$qstr'>";
			}
			else
			{
				include("barcode.php");
				echo "<img src='".$_POST['file'].".".$_POST['type']."'>";
			}
		}
		?>
		</TD>
	</TR>
	</TABLE></TD>
</TR>
</TABLE>
</BODY>
</HTML>

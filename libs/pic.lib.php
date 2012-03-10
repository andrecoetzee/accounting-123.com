<?
if (!defined("PIC_LIB")) {
	define("PIC_LIB", true);

function Index ($det)
	{
		$i=count($det);
		$tot=$i;
		if($i==6) {$c=3;$wd=33.33;}
		elseif($i==7) {$c=4;$wd=25;}
		elseif($i==8) {$c=4;$wd=25;}
		else{$c=5;$wd=20;}
		if($i==2) {$wd=50;}
		if($i==3) {$wd=33.33;}
		if($i==4) {$wd=25;}


		$index="<table border=0 cellspacing=0 cellpadding=7 width='90%' align=center>";
		$lc=0;
		$i=0;
		while($i<$tot)
		{
			$data=explode("|",$det[$i]);
			if($lc==0){$index .="<tr>";}
			if( !( isset($data[4]) ) || $data[4]=="") $data[4]=$data[3]."sh";

			if (isset($data[5]) && $data[5] != "logout")
				$top_frame_loader="onClick='loadTopMenu(\"".$data[5]."\");'";
			else
				$top_frame_loader="";

			if ( $data[5] == "logout" )
				$t_frame = "_top";
			else
				$t_frame = "mainframe";

			$index .="<td valign=top align=center width='$wd%'><a href=$data[2] target=$t_frame class=nav ".$top_frame_loader." onMouseOver='imgSwop($i, \"images/$data[4].gif\");' onMouseOut='imgSwop($i, \"images/$data[3].gif\");'><img src='images/$data[3].gif' border=0 alt='$data[0]' title='$data[1]' name=$i><br>$data[0]</a></td>";

			$lc++;
			$i++;
			if($lc==$c) {$index .="</tr>";$lc=0;}
			elseif($i==$tot) {$index .="</tr>";}
		}
		$index .="
			<tr>
				<td align=center colspan=10><a href=license.html class=nav target='mainframe'><br><br>Cubit License</a></td>
			</tr>
		</table>";

		return $index;

	}


} /* LIB END */

?>

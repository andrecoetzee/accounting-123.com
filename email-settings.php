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

require ("settings.php");

$OUTPUT = settings();

require("template.php");

function settings() {
	extract($_POST);

	db_conn('cubit');

	$err="";

	$save=false;

	if(isset($from)) {
		$save=true;
		
		require_lib("validate");
		$v = new validate();
		$v->isOk($sig, "string", 0, 255, "Invalid signature.");
		$v->isOk($from, "email", 1, 255, "Invalid from e-mail address.");
		$v->isOk($reply, "email", 0, 255, "Invalid reply e-mail address.");
		$v->isOk($host, "string", 1, 255, "Invalid smtp server. You need to fill in the SMTP HOST field, you can get this from your ISP.<br>
				Examples: smtp.saix.net OR smtp.mweb.co.za");
		
		if ($v->isError()) {
			$err = $v->genErrors();
		} else {
			$sig=remval($sig);
			$from=remval($from);
			$reply=remval($reply);
			$host=remval($host);
	
			$Sl="SELECT * FROM esettings";
			$Ri=db_exec($Sl);
	
			if(pg_num_rows($Ri)<1) {
				$Sl="INSERT INTO esettings(sig,fromname,reply,smtp_host,smtp_auth,smtp_user,smtp_pass) VALUES
				('$sig','$from','$reply','$host','0','','')";
				$Ri=db_exec($Sl);
			} else {
				$Sl="UPDATE esettings SET sig='$sig',fromname='$from',reply='$reply',smtp_host='$host'";
				$Ri=db_exec($Sl);
			}
	
			r2sListRestore("emailsettings");
		}
	}

	$Sl="SELECT * FROM esettings";
	$Ri=db_exec($Sl);

	$sd=pg_fetch_array($Ri);

	if(!$save) {
		$ex="<li class=err>Please set your email settings & then click 'Update'</li>";
	} else {
		$ex="<li class=err>Email settings saved</li>";
	}

	if(pg_num_rows($Ri)<1) {
		$sd['sig'] = "";
		$sd['fromname'] = "";
		$sd['reply'] = "";
		$sd['smtp_host']="smtp.saix.net";
		$exx="<li class=err>These are default settings. If these settings do not work, contact your ISP for correct details.</li>";
	} else {
		$exx="";
	}
	
	$sd = array_merge($sd, $_POST);

	if (!isset ($retdata))
		$retdata = "";

	$out = "<h3>Email Settings</h3>
	$exx
	$ex
	<br />
	$err
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method=post>
	$retdata
	<tr>
		<th colspan='2'>Settings</th>
	</tr>
	<tr class='".bg_class()."'>
		<td align='center' colspan='2'><b>An asterisk (".REQ.") symbol marks required fields.</b></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Signature</td>
		<td><input type='text' size='25' name='sig' value='$sd[sig]'></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>".REQ." From E-mail Address</td>
		<td><input type='text' size='25' name='from' value='$sd[fromname]'></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Reply To E-mail Address</td>
		<td><input type='text' size='25' name='reply' value='$sd[reply]'></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>".REQ." SMTP Server</td>
		<td><input type='text' size='25' name='host' value='$sd[smtp_host]'></td>
	</tr>
	<tr>
		<td colspan=2 align=right><input type=submit value='Update &raquo;'></td>
	</tr>
	</form>
	</table>
	<p>
	<table boder=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $out;

}

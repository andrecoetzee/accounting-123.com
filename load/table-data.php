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

if ($_POST) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

require ("template.php");

# Enter $sql
function enter ()
{

	$enter = "<h3> Enter Sql Statement</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>Sql</td><td><input type=text size=40 name=sql></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td>Database</td><td><input type=text size=20 name=db></td></tr>
                <tr><td align=right colspan=2><input type=submit value='Exec &raquo'></td></tr>
        </table>
        </form>";

	return $enter;
}

# confirm entered info
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($db, "string", 1, 20, "Invalid database.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"];
		}
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $theseErrors;
	}

        # connect to db
	db_conn($db);

        # ???? SQL (uppercase all the stupid sql keywords (\s space) ????
        $sql = str_replace("\\", "", $sql);
        $sql = preg_replace("/select\s/i", "SELECT ", $sql);
        $sql = preg_replace("/\sfrom\s/i", " FROM ", $sql);
        $sql = preg_replace("/delete\s/i", "DELETE ", $sql);
        $sql = preg_replace("/\swhere\s/i", " WHERE ", $sql);
        $sql = preg_replace("/\sand\s/i", " AND ", $sql);
        $sql = preg_replace("/\sor\s/i", " OR ", $sql);
        $sql = preg_replace("/\slike\s/i", " LIKE ", $sql);
        $sql = preg_replace("/\sasc/i", " ASC", $sql);
        $sql = preg_replace("/\sdesc/i", " DESC", $sql);
        $sql = preg_replace("/\sby\s/i", " BY ", $sql);
        $sql = preg_replace("/\sorder\s/i", " ORDER ", $sql);
        $sql = preg_replace("/\slimit\s/i", " LIMIT ", $sql);
        $sql = preg_replace("/update\s/i", "UPDATE ", $sql);
        $sql = preg_replace("/\sset\s/i", " SET ", $sql);
        $sql = preg_replace("/\svalues/i", " VALUES ", $sql);

        $Rs = db_exec($sql) or die("Unable to access Cubit $db.");

        $fldnum = pg_numfields ($Rs);
        for($i = 0; $i < $fldnum; $i++){
                $flds[$i] = pg_fieldname($Rs, $i);
        }


	$confirm = "<center><h3>Result Analysis</h3>
        <h4>Database: $db </h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th colspan=$fldnum align=center>Sql [ ".pg_numrows($Rs)." rows affected ]</th></tr>
                <tr bgcolor='". TMPL_tblDataColor1."'><td colspan=$fldnum align=center>$sql;</td></tr>
                <tr><td colspan=$fldnum><br></td></tr>";

                foreach($flds as $key => $value){
                        $confirm .= "<th>$value</th>";
                }
                $confirm .= "</tr>";

                //List the produced Data
                $i=0;
                if(pg_numrows($Rs) > 0){
                        while($data = pg_fetch_array($Rs)){
                                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                                $confirm .="<tr bgcolor='$bgColor'>";

                                foreach($flds as $key => $value){
                                        $confirm .= "<td>$data[$value]</td>";
                                }
                                $confirm .= "</tr>";
                                $i++;
                        }
                }else{
                        $confirm .= "<tr bgcolor='". TMPL_tblDataColor1."'><td colspan=$fldnum align=center>There are results for you query</td></tr>";
                }
        $confirm .="</table>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <a name='down'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><td colspan=2><hr></td></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'><td>SQL</td><td><input type=text size=60 name=sql value='$sql'></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td>Database</td><td><input type=text size=20 name=db value='$db'></td></tr>
                <tr><td align=right colspan=2><input type=submit value='Exec &raquo'></td></tr>
                <tr><td colspan=2><hr></td></tr>
        </table>
        </form><br><br><br>";

        return $confirm;
}
?>

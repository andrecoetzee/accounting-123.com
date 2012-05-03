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
require("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "addview":
			$OUTPUT = addview($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("template.php");

# Default view
function view()
{
//layout
$view = "
<h3>Add Financial Year <b>1</b> Periods</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
<form action='".SELF."' method=post name=form>
<input type=hidden name=key value=addview>
<tr><th>Field</th><th>Value</th></tr>
<input type=hidden size=20 name=finyear value=1>
<tr class='bg-odd'><td>Period 1</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd1 checked=yes><input type=text size=14 maxlength=14 name=prd1 value=January></td></tr>
<tr class='bg-even'><td>Period 2</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd2 checked=yes><input type=text size=14 maxlength=14 name=prd2 value=February></td></tr>
<tr class='bg-odd'><td>Period 3</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd3 checked=yes><input type=text size=14 maxlength=14 name=prd3 value=March></td></tr>
<tr class='bg-even'><td>Period 4</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd4 checked=yes><input type=text size=14 maxlength=14 name=prd4 value=April></td></tr>
<tr class='bg-odd'><td>Period 5</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd5 checked=yes><input type=text size=14 maxlength=14 name=prd5 value=May></td></tr>
<tr class='bg-even'><td>Period 6</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd6 checked=yes><input type=text size=14 maxlength=14 name=prd6 value=June></td></tr>
<tr class='bg-odd'><td>Period 7</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd7 checked=yes><input type=text size=14 maxlength=14 name=prd7 value=July></td></tr>
<tr class='bg-even'><td>Period 8</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd8 checked=yes><input type=text size=14 maxlength=14 name=prd8 value=August></td></tr>
<tr class='bg-odd'><td>Period 9</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd9 checked=yes><input type=text size=14 maxlength=14 name=prd9 value=September></td></tr>
<tr class='bg-even'><td>Period 10</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd10 checked=yes><input type=text size=14 maxlength=14 name=prd10 value=October></td></tr>
<tr class='bg-odd'><td>Period 11</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd11 checked=yes><input type=text size=14 maxlength=14 name=prd11 value=November></td></tr>
<tr class='bg-even'><td>Period 12</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd12 checked=yes><input type=text size=14 maxlength=14 name=prd12 value=December></td></tr>
<tr class='bg-odd'><td>Period 13</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd13><input type=text size=14 maxlength=14 name=prd13 value=Additional1></td></tr>
<tr class='bg-even'><td>Period 14</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd14><input type=text size=14 maxlength=14 name=prd14 value=Additional2></td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Names &raquo'></td></tr>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>

<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
</table>


</form>
</table>";
        return $view;
}

function addview($_POST)
{
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd1, "string", 0, 14, "Invalid 1st Period year.");
        $v->isOk ($prd2, "string", 0, 14, "Invalid 2nd Period year.");
        $v->isOk ($prd3, "string", 0, 14, "Invalid 3rd Period year.");
        $v->isOk ($prd4, "string", 0, 14, "Invalid 4th Period year.");
        $v->isOk ($prd5, "string", 0, 14, "Invalid 5th Period year.");
        $v->isOk ($prd6, "string", 0, 14, "Invalid 6th Period year.");
        $v->isOk ($prd7, "string", 0, 14, "Invalid 7th Period year.");
        $v->isOk ($prd8, "string", 0, 14, "Invalid 8th Period year.");
        $v->isOk ($prd9, "string", 0, 14, "Invalid 9th Period year.");
        $v->isOk ($prd10, "string", 0, 14, "Invalid 10th Period year.");
        $v->isOk ($prd11, "string", 0, 14, "Invalid 11th Period year.");
        $v->isOk ($prd12, "string", 0, 14, "Invalid 12th Period year.");
        $v->isOk ($prd13, "string", 0, 14, "Invalid 13th Period year.");
        $v->isOk ($prd14, "string", 0, 14, "Invalid 14th Period year.");

        if(!isset($cprd)){
                return "<li> Please select the period that you want to use. (click the checkboxes on the left).";
        }

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        if($finyear > 10){
        return "<br><center><b>All Periods has been set successfully for financial Years</b>
        <p><a href='yr-open.php' class=nav>Open a Financial Year</a>";
        }


#Write to Database
$yrdb="yr".$finyear;
write($_POST,$yrdb);

//layout
$view = "
<h3>Written Periods For Financial year <b>$finyear</b></h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100%>
<tr><td valign=top>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
<form action='".SELF."' method=post name=form>
<input type=hidden name=key value=confirm>
<tr><th>Field</th><th>Value</th></tr>";

        foreach($cprd as $key => $value){
                $bgColor = ($key % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                $view .= "<tr bgcolor='$bgColor'><td>Period ".($key+1)."</td><td valign=center>".$$value."</td></tr>";
        }

$view .= "
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><br></td></tr>
</form>
</table>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>

<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
</form>
</table>
</td>";
$finyear = ($finyear+1);
$view .="
<td valign=top>
<h3>Add Periods For Financial year <b>$finyear</b></h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
<form action='".SELF."' method=post name=form>
<input type=hidden name=key value=addview>
<tr><th>Field</th><th>Value</th></tr>
<input type=hidden size=20 name=finyear value=$finyear>
<tr class='bg-odd'><td>Period 1</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd1 checked=yes><input type=text size=14 maxlength=14 name=prd1 value=January></td></tr>
<tr class='bg-even'><td>Period 2</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd2 checked=yes><input type=text size=14 maxlength=14 name=prd2 value=February></td></tr>
<tr class='bg-odd'><td>Period 3</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd3 checked=yes><input type=text size=14 maxlength=14 name=prd3 value=March></td></tr>
<tr class='bg-even'><td>Period 4</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd4 checked=yes><input type=text size=14 maxlength=14 name=prd4 value=April></td></tr>
<tr class='bg-odd'><td>Period 5</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd5 checked=yes><input type=text size=14 maxlength=14 name=prd5 value=May></td></tr>
<tr class='bg-even'><td>Period 6</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd6 checked=yes><input type=text size=14 maxlength=14 name=prd6 value=June></td></tr>
<tr class='bg-odd'><td>Period 7</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd7 checked=yes><input type=text size=14 maxlength=14 name=prd7 value=July></td></tr>
<tr class='bg-even'><td>Period 8</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd8 checked=yes><input type=text size=14 maxlength=14 name=prd8 value=August></td></tr>
<tr class='bg-odd'><td>Period 9</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd9 checked=yes><input type=text size=14 maxlength=14 name=prd9 value=September></td></tr>
<tr class='bg-even'><td>Period 10</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd10 checked=yes><input type=text size=14 maxlength=14 name=prd10 value=October></td></tr>
<tr class='bg-odd'><td>Period 11</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd11 checked=yes><input type=text size=14 maxlength=14 name=prd11 value=November></td></tr>
<tr class='bg-even'><td>Period 12</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd12 checked=yes><input type=text size=14 maxlength=14 name=prd12 value=December></td></tr>
<tr class='bg-odd'><td>Period 13</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd13><input type=text size=14 maxlength=14 name=prd13 value=Additional1></td></tr>
<tr class='bg-even'><td>Period 14</td><td valign=center><input type=checkbox size=20 name=cprd[] value=prd14><input type=text size=14 maxlength=14 name=prd14 value=Additional2></td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Names &raquo'></td></tr>
</form>
</table>
</td></tr>
</table>
";
        return $view;
}

# write
function write($_POST,$yrdb)
{
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd1, "string", 0, 14, "Invalid 1st Period year.");
        $v->isOk ($prd2, "string", 0, 14, "Invalid 2nd Period year.");
        $v->isOk ($prd3, "string", 0, 14, "Invalid 3rd Period year.");
        $v->isOk ($prd4, "string", 0, 14, "Invalid 4th Period year.");
        $v->isOk ($prd5, "string", 0, 14, "Invalid 5th Period year.");
        $v->isOk ($prd6, "string", 0, 14, "Invalid 6th Period year.");
        $v->isOk ($prd7, "string", 0, 14, "Invalid 7th Period year.");
        $v->isOk ($prd8, "string", 0, 14, "Invalid 8th Period year.");
        $v->isOk ($prd9, "string", 0, 14, "Invalid 9th Period year.");
        $v->isOk ($prd10, "string", 0, 14, "Invalid 10th Period year.");
        $v->isOk ($prd11, "string", 0, 14, "Invalid 11th Period year.");
        $v->isOk ($prd12, "string", 0, 14, "Invalid 12th Period year.");
        $v->isOk ($prd13, "string", 0, 14, "Invalid 13th Period year.");
        $v->isOk ($prd14, "string", 0, 14, "Invalid 14th Period year.");

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        db_conn($yrdb);
        //Empty info Table
        $sql = "TRUNCATE TABLE info";
        $rslt = db_exec($sql) or errDie("Unable to Empty the info Table on year Database \"$yrdb\"", SELF);

        $i=1;
        foreach ($cprd as $key => $value) {
                $sql = "INSERT INTO info VALUES('".$$value."','$i','n')";
                $rslt = db_exec($sql) or errDie("Could not insert Period names to year Database",SELF);
                $i = ($i +1);
        }

        return "<h3> Periods For Financial Year Has Been Written</h3>";
}
?>

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

##
# load-batch-file.php :: Save Batch File to DB
##

# get settings
require ("settings.php");

if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctFile ();
	}
} else {
	$OUTPUT = slctFile ();
}

# display output
require ("template.php");

# print Info from db
function slctFile ()
{
	# start table, etc
	$slctFile =
        "<center>
        <h3>Add accounts from a tab delimited file</h3>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form ENCTYPE='multipart/form-data' action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>File</td><td><input type=file size=20 name=tfile></td></tr>
        <tr><td colspan=2 align=center><input type=submit value='Save Accounts &raquo;'></td></tr>
        </form>
        </table>";

        return $slctFile;
}

function confirm ($HTTP_POST_VARS)
{
        # get $HTTP_POST_FILES global var for uploaded files
        global $HTTP_POST_FILES;

        # save File
        if (empty ($HTTP_POST_FILES["tfile"])) {
		return "<li class=err> Please Select A Text File .";
	}

        # die if uploaded file greater than 30k
	## if ($HTTP_POST_FILES["tfile"]["size"] > 20000) {
	##	return "<li>Uploaded file is too large. Limit = 30k.";
	## }

        # check if file has been uploaded
        if (is_uploaded_file ($HTTP_POST_FILES["tfile"]["tmp_name"])) {

                # open temp file
                $file = file($HTTP_POST_FILES['tfile']['tmp_name']);
                $tmpname = $HTTP_POST_FILES['tfile']['tmp_name'];
                $filename = $HTTP_POST_FILES['tfile']['name'];
                if(!copy($tmpname, dirname($tmpname)."/".$filename)){
                        return "Unable to copy file to temporary location";
                }
        }else {
		return "Could not upload the file to the DB, Please check file Permissions";
	}

        $tab = "<center><h3>Batch File Analysis</h3>
        <table bgcolor=#ffffff width=80%>
        <tr><th>Date</th><th>Ref No.</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Details</th><th>Author</th></tr>";

        foreach($file as $key => $value){

                # explode the line into vars( in order )
                list($date[$key], $refnum[$key], $dtaccname[$key], $dtaccnum[$key], $ctaccname[$key], $ctaccnum[$key], $amount[$key], $details[$key], $author[$key]) = explode(";", $value);

                #  if(count($tran) < 2){
                #        return "<li>Invalid file format";
                # }

                # wrtie to table
                $tab .= "<tr><td>$date[$key]</td><td>$refnum[$key]</td><td>$dtaccname[$key]</td><td>$ctaccname[$key]</td><td>$amount[$key]</td><td>$details[$key]</td><td>$author[$key]</td></tr>";
        }
        $tab .= "</table>";

        #Layout
        $confirmInfo =
        "<center>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=tmpname value='$tmpname'>
        <input type=hidden name=filename value='$filename'>
        $tab
        <br>
        <br>
        <input type=button value='&laquo; Cancel' onClick='JavaScript:history.back();'>&nbsp;&nbsp; <-> &nbsp;&nbsp;<input type=submit value='Save Trans &raquo;'>
        </form>";

        return $confirmInfo;
}

# wrote
function write ($HTTP_POST_VARS)
{
        # strip the vars
        foreach($HTTP_POST_VARS as $key => $value){
                $$key = $value;
        }

        # open temp file
        if($file = file(dirname($tmpname)."/".$filename)){
                # delete the temporary file
                unlink(dirname($tmpname)."/".$filename);
        }else{
                return "Unable to open file $filename on the temp Directory";
        }

        # number of accounts
        $numrec = 0;

        # connect to core
        core_connect();

        # start inserting
        pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

        foreach($file as $key => $value){

                # Explode the line into vars( in order )
                list($date[$key], $refnum[$key], $dtaccname[$key], $dtaccnum[$key], $ctaccname[$key], $ctaccnum[$key], $amount[$key], $details[$key], $author[$key]) = explode(";", $value);

                # Account numbers
                $dtaccno = explode("/", $dtaccnum[$key]);
                $ctaccno = explode("/", $ctaccnum[$key]);

                # get DT account ID
                $dtaccRs = get("core","accid","accounts","topacc","$dtaccno[0]' AND accnum = '$dtaccno[1]");
                if(pg_numrows($dtaccRs) < 1){
                        return "<li> Accounts number : $ctaccno[0]/$ctaccno[1] does not exist";
                }
                $dtacc  = pg_fetch_array($dtaccRs);
                $dtaccid[$key] = $dtacc['accid'];

                # get CT account ID
                $ctaccRs = get("core","accid","accounts","topacc","$ctaccno[0]' AND accnum = '$ctaccno[1]");
                if(pg_numrows($ctaccRs) < 1){
                        return "<li> Accounts number : $ctaccno[0]/$ctaccno[1] does not exist";
                }
                $ctacc  = pg_fetch_array($ctaccRs);
                $ctaccid[$key] = $ctacc['accid'];

                #  if(count($tran) < 2){
                #        return "<li>Invalid file format";
                # }

                # Wrtie to table
                # $tab .= "<tr><td>$date[$key]</td><td>$refnum[$key]</td><td>$dtaccname[$key]</td><td>$ctaccname[$key]</td><td>$amount[$key]</td><td>$details[$key]</td><td>$author[$key]</td></tr>";

                # Insert Into the batch table
                $sql = "INSERT INTO batch(date, debit, credit, refnum, amount, author, details) VALUES('$date[$key]', '$dtaccid[$key]', '$ctaccid[$key]', '$refnum[$key]', '$amount[$key]', '$author[$key]', '$details[$key]')";
                $transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

                $numrec++;
        }

        # commit sql transaction
        pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

        $tab = "<center><table bgcolor=#ffffff width=50%>
        <tr><th>Transactions Saved to system batch file</th></tr>
        <tr><td class=datacell align=center><b>$numrec</b> Transections from the file : <b>$filename</b> have been succsessfuly added to the system batch.</b></td></tr>
        </table>";

        return $tab;
}
?>

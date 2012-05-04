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
# index-invoices-new.php :: Module to record invoices
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "select":
			$OUTPUT = select($_POST);
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

##
# Functions
##

# select customers
function view ()
{
	# Set up table to display in
	$view = "
        <h3>New Quote</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden  name=key value=select>
        <tr><th>Field</th><th>Value</th></tr>
        <tr  class='bg-even'><td>Select Customer Initials</td>
        <td>
        <select name=alpha>
        <option value='a'>A</option>
        <option value='b'>B</option>
        <option value='c'>C</option>
        <option value='d'>D</option>
        <option value='e'>E</option>
        <option value='f'>F</option>
        <option value='g'>G</option>
        <option value='h'>H</option>
        <option value='i'>I</option>
        <option value='j'>J</option>
        <option value='k'>K</option>
        <option value='l'>L</option>
        <option value='m'>M</option>
        <option value='n'>N</option>
        <option value='o'>O</option>
        <option value='p'>P</option>
        <option value='q'>Q</option>
        <option value='r'>".CUR."</option>
        <option value='s'>S</option>
        <option value='t'>T</option>
        <option value='u'>U</option>
        <option value='v'>V</option>
        <option value='w'>W</option>
        <option value='x'>X</option>
        <option value='y'>Y</option>
        <option value='z'>Z</option>
        </select>
        </td></tr>
        <tr  class='bg-odd'><td><input type=submit value=Submit></td><td valign=center align=center><a href='customers-new.php' target=mainframe>New Customer</a></td></tr>
        </form></table>
		<tr><td></td><td valign=center></td></tr>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

        return $view;
}

function select($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # Set uppercase
        $ALPHA = strtoupper($alpha);

        # Connect to database
	db_connect ();

        # Query server for customer info
	$sql = "SELECT * FROM customers WHERE cusname LIKE '$alpha%' OR cusname LIKE '$ALPHA%' ORDER BY cusname";
	$prnCustRslt = db_exec ($sql) or errDie ("Unable to view customers");
	$numrows = pg_numrows ($prnCustRslt);
	if ($numrows < 1) {
		return "<li class=err>No customer names starting with <b>$ALPHA</b> in database.";
        }

        $select = "<h4>Select a customer</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Customer no.</th><th>Customer name</th></tr>";

        // display customers to choose from
		for ($i=0; $i < $numrows; $i++) {
			$myCust = pg_fetch_array ($prnCustRslt);
			$select .= "<tr class='".bg_class()."'><td align=center>$myCust[cusnum]</td><td align=center><a href='quote-new.php?cusnum=$myCust[cusnum]'>$myCust[cusname]</a></td></tr>";
		}

		$select .= "</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customer</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

	return $select;
}
?>

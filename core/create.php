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

	// Creating an account
	function create($topacc, $accnum, $accname, $catid, $acctype, $vat)
	{
			# Check Account name on selected type and category
			$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum'";
			$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
			if (pg_numrows($cRslt) > 0) {
				return 1;
			}

			$sql = "SELECT * FROM accounts WHERE accname = '$accname'";
			$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
			if (pg_numrows($cRslt) > 0) {
				return 2;
			}

			# write to DB
			$Sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat) VALUES ('$topacc', '$accnum', '$accname', '$acctype', '$catid', '$vat')";
			$accRslt = db_exec ($Sql) or errDie ("Unable to add Account to Database.", SELF);

			# get last inserted id for new acc
			$accid = pglib_lastid ("accounts", "accid");

			# insert account into trial Balance
			$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat) VALUES('$accid', '$topacc', '$accnum', '$accname', '$vat')";
			$trialRslt = db_exec($query);

		# return Zero on success
		return 0;
	}
?>

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
		// ---- Regular Expresions ---- //

        // split the phrase by any number of commas or space characters,
        // which include " ", \r, \t, \n and \f
        $keys = preg_split ("/[\s,]+/", "hypertext language, programming");

        print "<pre>"; print_r($keys);

        // get host name from URL
        preg_match("/^(http:\/\/)?([^\/]+)/i", "http://www.php.net/index.html", $matches);
        print_r($matches);

?>

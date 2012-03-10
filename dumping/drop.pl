#!/usr/bin/perl

# path to psql binary
$psql = "/usr/bin/psql";
-e $psql or $psql = "/usr/local/bin/psql";
-e $psql or $psql = "/usr/local/pgsql/bin/psql";
-e $psql or $psql = "/var/lib/pgsql/bin/psql";
-e $psql or $psql = "psql";

if ( length($ARGV[0]) < 1 ) {
	print "USAGE: $0 <dbcode 1>, <dbcode 2>, .... <dbcode 10>\n";
	exit;
}

# Open a pipe to postres
open (PSQL, "|$psql -U postgres template1");

print PSQL "DROP DATABASE cubit;";

for ( $i = 0 ; $i < 10; $i++ ) {
	if ( length($ARGV[$i]) > 0 ) {
		print PSQL "DROP DATABASE \"cubit_$ARGV[$i]\";";
	}
}

# Close
close(PSQL);

sub diemsg {
	print $_[0];
	exit;
}

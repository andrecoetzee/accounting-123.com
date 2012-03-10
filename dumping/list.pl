#!/usr/bin/perl

$psql = "/usr/bin/psql";
-e $psql or $psql = "/var/lib/pgsql/bin/psql";
-e $psql or $psql = "/usr/local/pgsql/bin/psql";
-e $psql or $psql = "psql";

open(PGSQL, "|$psql -U postgres template1");

print PGSQL "\\l";

close(PGSQL);

<?

# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

define("CUBIT_VERSION", "3.4");
define("CUBIT_BUILD", "1");

/* internal version num */
/* This Defines The Build Number In The Key Generator */
/**
0 - Pre 2.72
1 - 2.72
2 - 2.73 (Supposed)
3 - 2.74
4 - 2.75
5 - 2.76
6 - 2.81
7 - 2.82
8 - 2.83
9 - 2.8
10 - 2.84
11 - 2.85
12 - 2.86
13 - 2.88
14 - 2.87
15 - 2.89
16 - 2.90
17 - 3.0
18 - 3.01
19 - 3.1
20 - 3.2
21 - 3.21
22 - 3.22
23 - 3.3
24 - 3.35
25 - 3.4
26 - 3.42
27 - 3.45
**/
define("CUBIT_IV", "23");

/* internal version release */
define("CUBIT_IVR", "X");

/**
 * cubit: A
 * property: P
 * manufactering: M
 * hospitality: H
 * coastal: C
 * travel: T
 * legal: L
 * medical: E
 * custom: U
 * BetaA: X
 * BetaB: Y
 * BetaC: Z
 */

/**
 * cubit addon modules
 *
 * ex property, manufact. this name used in this list has to be the same
 * as that of the subdirectory where the files are located. to have setup.php
 * perform extra setups simply create a file called setup-addon.php in this
 * subdirectory and all the lines will be executed when setup.php runs.
 */
$CUBIT_MODULES = array(
	"transheks",
	"hire"
);

?>

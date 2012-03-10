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

if (class_exists ("salescalc")) {
	return 0;
}

class salescalc
{
	##
	# Local stuff
	##

	var $_errors;
	var $_charges;
	var $_subtotal;
	var $_taxex;
	var $_vatinc;
	var $_traddisc;
	var $_traddiscmt;
	var $_traddiscm;
	var $_total;
	var $_vat;

	##
	# Public stuff
	##

	# constructor :: reset error list
	function salescalc ($subtot, $taxex, $delchrg, $traddisc, $chrgvat)
	{
		# the subtotal
		$subtotal = $subtot;

		# add del charge
		$subtotal += $delchrg;

		# get amount excluding vat
		$VATP = TAX_VAT;
		if($chrgvat == "exc"){
			$vatb = sprint(($VATP/100) * ($subtotal - $taxex));
			$subtotal = sprint($subtotal);
		}elseif($chrgvat == "inc"){
			$vatb = sprint((($subtotal - $taxex)/($VATP + 100)) * $VATP);
			$subtotal = sprint($subtotal - $vatb);
			$subtot = sprint($subtot - $vatb);
		}else{
			$vatb = "0.00";
			$subtotal = sprint($subtotal);
		}

		# Minus trade discount from taxex and minus
		$traddiscmt = 0.00;
		if($traddisc > 0)
			$traddiscmt = sprint(($traddisc/100) * $taxex);
		$taxex -= $traddiscmt;

		# Calc trade discount on (subtotal - vat) and minus
		$traddiscm = 0.00;
		if($traddisc > 0)
			$traddiscm = sprint(($traddisc/100) * $subtotal);
		$subtotal -= $traddiscm;

		if($chrgvat == "nov"){
			$vat= 0;
		}else{
			$vat = sprint(($VATP/100) * ($subtotal - $taxex));
		}

		$total = sprint($subtotal + $vat);

		$this->$_delchrg = sprint($delchrg);
		$this->$_subtotal = sprint($subtotal);
		$this->$_taxex = sprint($taxex);
		$this->$_chrgvat = sprint($chrgvat);
		$this->$_traddisc = sprint($traddisc);
		$this->$_traddiscmt = sprint($traddiscmt);
		$this->$_traddiscm = sprint($traddiscm);
		$this->$_total = sprint($total);
		$this->$_vat = sprint($vat);
	}
}
?>

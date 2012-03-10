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

function sh_salescalc ($subtot, $taxex, $delchrg, $traddisc, $chrgvat)
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

	$ret['delchrg'] = sprint($delchrg);
	$ret['subtotal'] = sprint($subtotal);
	$ret['subtot'] = sprint($subtot);
	$ret['taxex'] = sprint($taxex);
	$ret['chrgvat'] = sprint($chrgvat);
	$ret['traddisc'] = sprint($traddisc);
	$ret['traddiscmt'] = sprint($traddiscmt);
	$ret['traddiscm'] = sprint($traddiscm);
	$ret['total'] = sprint($total);
	$ret['vat'] = sprint($vat);

	return $ret;
}

class salescalc
{
	##
	# Local stuff
	##

	var $errors;
	var $charges;
	var $subtotal;
	var $taxex;
	var $vatinc;
	var $traddisc;
	var $traddiscmt;
	var $traddiscm;
	var $total;
	var $vat;

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

		$this->delchrg = sprint($delchrg);
		$this->subtotal = sprint($subtotal);
		$this->taxex = sprint($taxex);
		$this->chrgvat = sprint($chrgvat);
		$this->traddisc = sprint($traddisc);
		$this->traddiscmt = sprint($traddiscmt);
		$this->traddiscm = sprint($traddiscm);
		$this->total = sprint($total);
		$this->vat = sprint($vat);
	}
}
?>

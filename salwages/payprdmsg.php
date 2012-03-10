<?

require_once("../settings.php");

if (AJAX) {
	$OUTPUT = get_payprdmsg($_GET["payprd"], $_GET["newval"]);
	parse();
}

function get_payprdmsg($payprd, $newval =  false) {
	$dispmsg = getCSetting("EMP_SALMSG");

	$isset = strpos($dispmsg, $payprd);

	if ($newval !== false) {
		/* must be enabled, and setting not in there yet */
		if ($newval == "true" && $isset === false) {
			$dispmsg = "$dispmsg$payprd";
		}
		/* must be disabled, and setting is there  */
		else if ($newval == "false" && $isset !== false) {
			$dispmsg = preg_replace("/$payprd/", "", $dispmsg);
		}

		setCSetting("EMP_SALMSG", $dispmsg);
		$isset = strpos($dispmsg, $payprd);
	}

	if ($isset === false) {
		$payprd_msg = "";
	} else {
		switch ($payprd) {
			case "d":
				$payprd_msg = "The first step is for the employer to nominate how many
					working hours there are in a week (This is done when adding/editing
					the employee). Normally this is 40 hours. In a year there are normally
					2080 working hours (40 * 52). The system is configured so that the
					number of the working day is identified each time that an employee
					is paid on a daily basis.<br />
					<br />
					EXAMPLE 1: If the employee performs work
					on 15 March 2007 he will commence working on working day number 11. 
					The employee year starts 1 March 2007 and ends on 28 February 2008.
					In this example there would be 10 previous working days of 8 hours each.
					Thus, working hours are split between prior 10 March 2007 and subsequent
					to that date: 80 Hours prior and 2000 after 14 March.<br />
					<br />
					EXAMPLE 2: An employee's taxable income on 3 July 2007, that is,
					on working day number 89 is R1200. He has taxable income of R105600 in 
					the previous 88 days, in respect of which his previous employer has 
					deducted PAYE amounting in total to R24398. The annual equivalent of 
					R105600 plus a potential 172 days with his new employer would be R312000 
					(105600 + [172*1200]). Tax on this annual equivalent amounts to R72085. 
					Thus in respect of 172 day he would have to pay R47687 - or R277.25 per 
					day. If the employee has no previous employment, that is, it is his first 
					job, the PAYE deduction on 3 July 2007 would be as follows: Tax on his 
					potential taxable income of R206400 (1200 * 172 days) from his new employer
					would be R37305 - or R216.89 per day.";
				break;
			case "w":
				$payprd_msg = "The first step is for the employer to nominate how
					many working hours there are in a week (This is done when adding/editing
					the employee). Normally this is 40 hours. In a year there are 52 weeks.
					The system will identify the week number. Note that the first week in the
					2008 tax year ends on 2 March 2007.<br />
					<br />
					EXAMPLE: If the employee commences work in the week ending 16 March 2007
					he will be working in week number 3.<br />
					If his taxable income for the whole week number 3 is R6000, and he has
					R12000 taxable income in week number 1 and 2 with a former employer who
					deducted a total PAYE of R2772.50, it means that he would have earned 
					R18000 during the first 3 weeks. The annual equivalent of this amount 
					is R312000 (R18000 * 52 / 3).<br />
					<br />
					The	tax payable on R312 000 is R72 085. In week 3 the employee must pay a
					portion of this amount, 3 / 52, which equals R4158.75. PAYE paid in
					prior periods, period 1 in this example of R2772.50, must be deducted
					from R4158.75 = R1386.25. PAYE to be deducted in week 3 amounts to R1386.25.";
				break;
			case "f":
				$payprd_msg = "The first step is for the employer to nominate how
					many working hours there are in a week (This is done when adding/editing
					the employee). Normally this is 40 hours. In a year there are 26 fortnights.
					The system will identify the fortnight number. Note that the first 
					fortnight in the 2008 tax year ends on 16 March 2007.<br />
					<br />
					EXAMPLE: If the employee commences work in the week ending 16 March 2007
					he will be commencing duties in the second fortnightly period, which ends
					on 30 March 2007.<br />
					If his taxable income for the whole fortnight number 1 was R12000, and he
					has R12000 taxable income in fortnight number 1 with a former employer who
					deducted PAYE of R2772.50, it means that he would have earned R24000 
					during the first 2 fortnights. The annual equivalent of R12000 every 
					fortnight amounts to R312000 (R12000 * 26).<br />
					<br />
					The	tax payable on R312 000 is R72 085. In fortnight number 2 the employee 
					must pay a portion of this amount, 2 / 26, which equals R5545.00. PAYE paid 
					in prior periods, period 1 in this example of R2772.50, must be deducted
					from R5545.00 = R2772.50. PAYE to be deducted in fortnight number 2 amounts
					to R2772.50.";
				break;
			case "m":
				$payprd_msg = "Monthly";
		}
	}

	if (!empty($payprd_msg)) {
		$payprd_msg = "<li class='err'>$payprd_msg</li>";
	}

	return $payprd_msg;
}

?>
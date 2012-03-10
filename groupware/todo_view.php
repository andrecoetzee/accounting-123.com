<?php

require ("../settings.php");

$OUTPUT = display();

require ("gw-tmpl.php");

function display()
{
	extract ($_REQUEST);

	$fields["date_day"] = date("d");
	$fields["date_month"] = date("m");
	$fields["date_year"] = date("Y");

	extract ($fields, EXTR_SKIP);

	$date = "$date_day-$date_month-$date_year";



	$OUTPUT = "<h3>View Todo $date</h3>
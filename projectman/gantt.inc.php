<?php

class Gantt
{
	private $project_id;

	public function __construct($project_id)
	{
		$this->project_id = $project_id;
	}

	public function generate_monthly($start_epoch, $end_epoch)
	{
		$start_month = (int)date("m", $start_epoch);
		$end_month = (int)date("m", $end_epoch);

		$start_year = (int)date("Y", $start_epoch);
		$end_year = (int)date("Y", $end_epoch);

		$months = array();

		for ($i = $start_year; $i <= $end_year; $i++) {

			// Check when we should end the month, if we are working between
			// a few years and we are not in the last year, end at 12
			if ($i < $end_year) {
				$end = 12;
			} else {
				$end = $end_month;
			}

			// What should we start the year with, if we are not in the
			// starting year, start with 1
			if ($i > $start_year) {
				$start = 1;
			} else {
				$start = $start_month;
			}

			// Add all the items in a 2 dimensional array [year][month]
			for ($j = $start; $j <= $end; $j++) {
				$months[$i][] = $j;
			}
		}

		$out = "<tr><th rowspan='2'>ACTIVITY</th>";

		// Create the year columns

		$total_columns = 0;
		foreach ($months as $year=>$lv2) {
			$columns = count ($months[$year]);
			$total_columns += $columns;

			$out .= "<th colspan='$columns'>$year</th>";
		}

		$out .= "<th rowspan='2'>BUDGET</th>";
		$out .= "<th rowspan='2'>ACTUAL</th>";
		$out .= "<tr bgcolor='".bgcolorg()."'>";

		$months_list = array("jan", "feb", "mar", "apr", "may", "jun", "jul",
			"aug", "sep","oct", "nov", "dec");

		// Create the month columns
		foreach ($months as $year=>$lv2) {
			foreach ($months[$year] as $month) {
				$out .= "<td>".ucfirst($months_list[$month-1])."</td>";
			}
		}

		$out .= "</tr>";

		$start_dt = date("Y-m-d", $start_epoch);
		$end_dt = date("Y-m-d", $end_epoch);

		$sql = "
		SELECT *,
			extract('epoch' FROM start_time) as e_start,
			extract('epoch' FROM end_time) as e_end
		FROM project.tasks
		WHERE project_id='".$this->project_id."'
			AND ((start_time BETWEEN '$start_dt 00:00:00' AND '$end_dt 23:59:59')
				OR (end_time BETWEEN '$start_dt 00:00:00' AND '$end_dt 23:59:59'))";
		$task_rslt = db_exec($sql) or errDie("Unable to retrieve tasks.");

		$task_out = "";

		$bb = "style='cursor: pointer; border-bottom: 1px solid #4477BB;'";

		$colors = array ("magenta", "red", "lime", "yellow", "orange", "black");
		while ($task_data = pg_fetch_array($task_rslt)) {
			$start_month = (int)date("m", $task_data["e_start"]);
			$end_month = (int)date("m", $task_data["e_end"]);

			$start_year = (int)date("Y", $task_data["e_start"]);
			$end_year = (int)date("Y", $task_data["e_end"]);

			$total_months = $end_month - $start_month;

			if ($col_i == count($colors)-1) {
				$col_i = 0;
			} else {
				$col_i++;
			}

			$task_disp = "";
			foreach ($months as $year=>$lv2) {
				foreach ($months[$year] as $month) {
					if ($month >= $start_month && $month <= $end_month
					    && $year >= $start_year && $year <= $end_year) {
						$task_disp .= "<td bgcolor='$colors[$col_i]' $bb>&nbsp;</td>";
					} else {
						$task_disp .= "<td bgcolor='#ffffff' $bb>&nbsp;</td>";
					}
				}
			}

			$task_out .= "<tr bgcolor='".bgcolorg()."'>
				<td>$task_data[name]</td>
				$task_disp
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>";
		}

		$out .= $task_out;

		return $out;
	}

	function generate_daily($start_epoch, $end_epoch)
	{
		return "<li class='err'>Feature available in Cubit 2.8</li>";
	}

	function generate_weekly($start_epoch, $end_epoch)
	{
		return "<li class='err'>Feature available in Cubit 2.8</li>";
	}
}

?>
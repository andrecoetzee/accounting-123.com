<?

require("../settings.php");

if (!isset($_POST["key"])) {
	$_POST["key"] = "enter";
}

switch ($_POST["key"]) {
	case "write":
		$OUTPUT = write();
		break;
	case "enter":
	default:
		$OUTPUT = enter();
}

parse();



function enter()
{

	extract($_POST);
	
	$qry = new dbSelect("config", "trh", grp(
		m("where", "readonly='f'")
	));
	$qry->run();

	while ($row = $qry->fetch_array()) {
		if (!isset(${$row["name"]})) {
			${$row["name"]} = array(
				"desc" => $row["description"],
				"value" => $row["value"],
				"type" => $row["type"]
			);
		}
	}

	$display = array(
		"General Settings" => array(
			"INTERVAL",
			"MANAGEUSER"
		),
		"SMTP Settings" => array(
			"SMTP_SERVER",
			"SMTP_USER",
			"SMTP_PASS",
			"SMTP_FROM"
		),
		"POP3 Settings" => array(
			"POP3_SERVER",
			"POP3_USER",
			"POP3_PASS"
		)
	);

	$cat = false;

	$OUT = "
	<h3>Transactioning Configuration</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<table ".TMPL_tblDflts." width='400'>";

	$pc = false;
	foreach ($display as $cat => $cnames) {
		if ($cat != $pc) {
			if ($pc != false) {
				$OUT .= TBL_BR;
			}

			$OUT .= "
			<tr>
				<th colspan='2'>$cat</th>
			</tr>";
		}

		$i = 0;
		foreach ($cnames as $vname) {
			if (!isset($vname)) {
				$vname = "";
			}

			$OUT .= "
			<input type='hidden' name='${vname}[desc]' value='".${$vname}["desc"]."' />
			<input type='hidden' name='${vname}[type]' value='".${$vname}["type"]."' />
			<tr bgcolor='".bgcolor($i)."'>
				<td>".${$vname}["desc"]."</td>
				<td>";

			switch (${$vname}["type"]) {
				case "yn":
					$OUT .= "
					<select name='${vname}[value]'>
						<option value='y' ".(${$vname}["value"] != "n").">Yes</option>
						<option value='n' ".(${$vname}["value"] == "n").">Yes</option>
					</select>";

					break;

				case "passwd":
					$OUT .= "
					<input type='password' name='${vname}[value]' value='".${$vname}["value"]."' />";

					break;
					
				case "ulist":
					$uq = qryUsers();
					$OUT .= db_mksel($uq, "${'vname'}[value]", ${'vname'}["value"], "#userid", "#username");
					break;

				case "str":
				default:
					$OUT .= "
					<input type='text' name='${vname}[value]' value='".${$vname}["value"]."' />";

					break;
			}

			$OUT .= "
				</td>
			</tr>";
		}
	}

	$OUT .= "
	<tr>
		<td colspan='2' align='right'><input type='submit' value='Save' /></td>
	</tr>
	</table>
	</form>";
	return $OUT;

}



function write() {
	extract($_POST);

	$qry = new dbSelect("config", "trh", grp(
		m("where", "readonly='f'")
	));
	$qry->run();

	$upd = new dbUpdate("config", "trh");

	while ($row = $qry->fetch_array()) {
		if (isset(${$row["name"]})) {
			$cols = grp(
				m("value", ${$row["name"]}["value"])
			);

			$upd->setOpt($cols, "name='$row[name]'");
			$upd->run(DB_UPDATE);
		}
	}
	
	r2sListRestore("trh_comminit");

	$OUT = "
	<h3>Transactioning Configuration</h3>
	Successfully updated configuration.";
	return $OUT;

}



?>
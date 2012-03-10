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

if (defined("CUBIT_IMPORTCOMP")) {
	require_once("newsettings.php");
} else {
	require_once("settings.php");
}


$OUTPUT = "";

// CHECK FOR PATH OF psql EXECUTABLE

db_con("cubit");
if( isset($HTTP_POST_VARS["psql_location"]) && (strlen($HTTP_POST_VARS["psql_location"]) > 0) ) {
	$psql_exec = $HTTP_POST_VARS['psql_location'];

	if (validpath($psql_exec)) {
		savepath($psql_exec);
	} else {
		$OUTPUT .= "<li class=err>Invalid Location.</li>";
	}
}

$Rx = db_exec("SELECT locat FROM psql_location") or errDie("Unable to get ".PSQL_EXE." location from db.");
$selffind = false;
if( pg_numrows($Rx) < 1 && ($selffind = tryfind()) === false) {
    enterlocation();
} else {
	if ($selffind) {
		$psql_exec = $selffind;
		savepath($psql_exec);
	} else {
		$psql_exec = base64_decode( pg_fetch_result($Rx, 0, 0) );

        // current location is invalid
        if (!is_executable($psql_exec)) {
            if (($selffind = tryfind()) === false) {
                enterlocation();
            } else {
                $psql_exec = $selffind;
                savepath($psql_exec);
            }
        }
    }
}

// if called by self, show template
if (basename (getenv ("SCRIPT_NAME")) == "psql_path.php") {
	$OUTPUT = "<h3>PostgreSQL Location</h3>
		Location successfully set to: $psql_exec<br />
		<br />
		Change Location:
		<form action='".SELF."' method=post>
		<input type=text size=20 name=psql_location>
		<input type=submit value='Write &raquo;'>
		</form>";
	require("template.php");
}

function tryfind() {
	switch (PLATFORM) {
		case "windows":
			$cacls = relpath("dumping/cacls.bat");
			if ($cacls !== false) {
				$c = file($cacls);
				if (preg_match("/cacls.exe (.*)\\\\data \/E/", $c[1], $matches)) {
					$try = "$matches[1]\\PostgreSQL\\bin";

					if (validpath("$try")) {
						return $try;
					}
				}
			}

			$cd = getcwd();
			while ($p = strrpos("$cd", "\\")) {
				$cd = substr($cd, 0, $p);
				if (validpath("$cd\\PostgreSQL\\bin")) {
					return "$cd\\PostgreSQL\\bin";
				}
			}

			if (validpath("C:\\Cubit\\PostgreSQL\\bin")) {
				return "C:\\Cubit\\PostgreSQL\\bin";
			}
			break;
		case "linux":
			$cd = getcwd();
			while ($p = strrpos("$cd", "/")) {
				$cd = substr($cd, 0, $p);
				if (validpath("$cd/pgsql/bin")) {
					return "$cd/pgsql/bin";
				}
			}

			$paths = array(
				"/usr/local/cubit/pgsql/bin",
				"/usr/bin",
				"/usr/local/bin",
				"/usr/local/pgsql/bin",
				"/var/lib/pgsql/bin",
				"/var/pgsql/bin"
			);

			foreach ($paths as $p) {
				if (validpath("$p")) return $p;
			}
			break;
	}

	return false;
}

function validpath($p) {
	switch (PLATFORM) {
		case "windows":
			$p = "$p\\";
			break;
		case "linux":
			$p = "$p/";
			break;
	}

	if (is_executable("$p".PG_DUMP_EXE)
			&& is_executable("$p".PSQL_EXE)) {
		return true;
	}
	return false;
}

function savepath($psql_exec) {
	$Sl = "DELETE FROM psql_location";
	$Rx = db_exec($Sl) or errDie("Unable to remove ".PSQL_EXE." location.");

	$Sl = "INSERT INTO psql_location (locat) VALUES ('".base64_encode($psql_exec)."')";
	$Rx = db_exec($Sl) or errDie("Unable to insert location.");
}

function enterlocation() {
	$OUTPUT .= "<h3>PostgreSQL location</h3>
	<b>Binary name:</b> ".PSQL_EXE."<br>
	<p>Please enter the location of the PostgreSQL executable displayed above. This will be in the 'bin' subdirectory of your PostgreSQL directory. If you do not know this contact your 	integrator or use the Find command to locate the file.</p>
	<li class='err'>NOTE: You should only specify the directory in which the binary is located and not the path to the file itself!</li><br><br>
	<form action='".SELF."' method=post>
	<input type=text size=20 name=psql_location>
	<input type=submit value='Write &raquo;'>
	</form>";
	require("template.php");
}

// END CHECK FOR PATH OF psql EXECUTABLE

?>

<?

/**
 * used by r2s_id() to specify the generation of a POST variable passon
 *
 * @see r2s_id()
 */
define("R2S_POST", "post");

/**
 * used by r2s_id() to specify the generation of a GET variable passon
 *
 * @see r2s_id()
 */
define("R2S_GET", "get");

/**
 * this will initiate a "return2step" session and return r2s session id
 *
 * return2step is where you need to go to another script for whatever reason
 * and wish to return to where you where after that script completes
 *
 * @param string $page page to return to
 * @return int r2s session id
 */
function r2s_init($page = false) {
	if ($page === false) {
		$page = SELF;
	}

	if (!isset($_SESSION["RET2STEP_SEQ"])) {
		$_SESSION["RET2STEP_SEQ"] = 1;
	}
	$seq = $_SESSION["RET2STEP_SEQ"]++;

	$_SESSION["R2S_PAGE_$seq"] = $page;
	$_SESSION["R2S_POST_$seq"] = serialize($_POST);
	$_SESSION["R2S_GET_$seq"] = serialize($_GET);

	return $seq;
}

/**
 * returns r2s_id, or if specified creates post/get passon
 *
 * if an argument is supplied, function checks if r2s_id is set in either POST
 * or GET. if set in both POST and GET, POST takes priority. specify $pg with
 * R2S_POST or R2S_GET. GET doesn't return the & char.
 *
 * @see R2S_POST
 * @see R2S_GET
 * @param string $pg what to return post/get, dflt = post
 * @return string
 */
function r2s_id($pg = false) {
	if (isset($_POST["r2s_id"])) {
		$i = $_POST["r2s_id"];
	} else if (isset($_GET["r2s_id"])) {
		$i = $_GET["r2s_id"];
	} else {
		return false;
	}

	if ($pg === false) {
		return $i;
	}

	switch (strtolower($pg)) {
		case R2S_POST:
			return "<input type='hidden' name='r2s_id' value='$i'>";
		case R2S_GET:
			return "r2s_id=$i";
		default:
			return false;
	}
}

/**
 * this will destroy a "return2step" session
 *
 * @ignore
 * @param $seq r2s session id
 */
function r2s_destroy($seq) {
	if (isset($_SESSION["R2S_PAGE_$seq"])) unset($_SESSION["R2S_PAGE_$seq"]);
	if (isset($_SESSION["R2S_GET_$seq"])) unset($_SESSION["R2S_GET_$seq"]);
	if (isset($_SESSION["R2S_POST_$seq"])) unset($_SESSION["R2S_POST_$seq"]);

	return true;
}

/**
 * returns to an "return2step" session
 *
 * @param int $seq r2s session id
 */
function r2s_return($seq) {
	if (!isset($_SESSION["R2S_PAGE_$seq"])) {
		return;
	}

	$page = $_SESSION["R2S_PAGE_$seq"];
	$post = unserialize($_SESSION["R2S_POST_$seq"]);
	$get = unserialize($_SESSION["R2S_GET_$seq"]);

	$gets = array();
	foreach ($get as $n => $v) {
		$gets[] = "$n=$v";
	}
	$get = implode("&", $gets);
	
	$OUTPUT = "
	<body>
	<form name='r2sfrm$seq' method='post' action='$page?$get'>";

	$OUTPUT .= array2form($post);

	$OUTPUT .= "
	</form>
	<script>
		document.r2sfrm$seq.submit();
	</script>
	</body>";

	r2s_destroy($seq);

	require(relpath("template.php"));
}

/**
 * makes an r2s snapshot and stores in the r2s named list under selected name
 *
 * the list of r2s's allow you to associate a name to an r2sid, for ex.
 * every time you list invoices a new r2s is made, if an error say occurs
 * when processing the selected invoices you can just click the link to
 * go back to the exact same listing.
 *
 * @param string $name name to store under
 */
function r2sListSet($name, $page = false) {
	/* remove previous one first */
	if (isset($_SESSION["R2S_NAMED"][$name])) {
		r2s_destroy($_SESSION["R2S_NAMED"][$name]);
	}

	$_SESSION["R2S_NAMED"][$name] = r2s_init($page);
}

/**
 * restores to a named r2s state if it exists
 *
 * the list of r2s's allow you to associate a name to an r2sid, for ex.
 * every time you list invoices a new r2s is made, if an error say occurs
 * when processing the selected invoices you can just click the link to
 * go back to the exact same listing.
 *
 * @param string $name r2s name
 */
function r2sListRestore($name) {
	if (($r2sid = r2sListCheck($name)) !== false) {
		r2s_return($r2sid);
	}
}

/**
 * checks in the r2s list and returns r2sid matching the specified name
 *
 * the list of r2s's allow you to associate a name to an r2sid, for ex.
 * every time you list invoices a new r2s is made, if an error say occurs
 * when processing the selected invoices you can just click the link to
 * go back to the exact same listing.
 *
 * @param string $name r2s name
 * @return r2sid
 */
function r2sListCheck($name) {
	if (isset($_SESSION["R2S_NAMED"][$name])) {
		return $_SESSION["R2S_NAMED"][$name];
	} else {
		return false;
	}
}

/**
 * returns link to restore named r2s, or alternate link if it doesn't exist
 *
 * the list of r2s's allow you to associate a name to an r2sid, for ex.
 * every time you list invoices a new r2s is made, if an error say occurs
 * when processing the selected invoices you can just click the link to
 * go back to the exact same listing.
 *
 * @param string $name r2s name
 * @param string $alternate alternate filename
 * @return string
 */
function r2sListLink($name, $alternate = "main.php") {
	if (($r2sid = r2sListCheck($name)) !== false) {
		return relpath("r2srestore.php") . "?r2sid=$r2sid";
	} else {
		return $alternate;
	}
}

/**
 * destroys r2s by name
 *
 * the list of r2s's allow you to associate a name to an r2sid, for ex.
 * every time you list invoices a new r2s is made, if an error say occurs
 * when processing the selected invoices you can just click the link to
 * go back to the exact same listing.
 */
function r2sListDestroy($name) {
	if (($r2sid = r2sListCheck($name)) !== false) {
		r2s_destroy($r2sid);
		unset($_SESSION["R2S_NAMED"][$name]);
	}
}

/**
 * displays progress bar (used in long lasting actions like company creation)
 *
 * remember to first modify GET/POST so they point to desired action in the
 * script which will execute the long lasting action.
 *
 * @param string $page script name to load
 */
//function displayProgress($page = false) {
	//r2sListSet("PROGRESS_BAR", $page);

	//redir("progress.php");


//}

?>

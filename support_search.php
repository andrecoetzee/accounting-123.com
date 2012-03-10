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


# get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if(isset($HTTP_GET_VARS["id"])){
	$OUTPUT = show_question ($HTTP_GET_VARS["id"]);
}elseif (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = results_db();
			break;
		default:
			$OUTPUT = search_db();
	}
}else {
	# Display default output
	$OUTPUT = search_db();
}

# Get templete
require("template.php");

function search_db ()
{

	$display = "
			<h2>Support Database Search</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<th>Search By Keyword(s) Or Question</th>
				</tr>
				<tr>
					<td><input type='text' name='keyword' size='50'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Search'></td>
				</tr>
			</form>
			</table>
		";
	return $display;

}

function results_db ()
{

	global $HTTP_POST_VARS;
	extract ($HTTP_POST_VARS);

	#fist we process the query
	#stip out unneccessary tags,etc
	$keyword = str_replace("?", "", $keyword);
	$keyword = str_replace("'", "", $keyword);
	$keyword = str_replace("\"", "", $keyword);
	$keyword = str_replace(".", "", $keyword);
	$keyword = str_replace(",", "", $keyword);
	$keyword = str_replace("-", "", $keyword);
	$keyword = str_replace("*", "", $keyword);
	$keyword = str_replace("$", "", $keyword);
	$keyword = str_replace("#", "", $keyword);
	$keyword = str_replace("!", "", $keyword);

	#if it is a question we need it seperated
	$qarr = explode(" ",strtolower($keyword));

//	var_dump ($qarr);

	db_connect ();


###############[ FIRST TRY AN EXACT MATCH ]################
	#complile the search ..
	$search = " WHERE ";
	foreach($qarr as $each){
		$search .= "(lower(content) LIKE '%$each%') AND ";
	}
	$search = substr($search,0,-4);

	$get_search = "SELECT id,heading FROM supp_db_questions $search";
	$run_search = db_exec($get_search) or errDie("Unable to get search results");
	if(pg_numrows($run_search) < 1){
		$do_loose = TRUE;
		$exclude = "";
	}else {
		$do_loose = FALSE;
		$exclude = " AND ";
		$results = "";
		while ($sarr = pg_fetch_array($run_search)){
			$results .= "<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='support_search.php?id=$sarr[id]'>$sarr[heading]</a></td></tr>";
			$exclude .= " (id != '$sarr[id]') AND";
		}
		$exclude = substr($exclude,0,-4);
	}
#####################[ EXACT ]#############################


###############[ DO A BEST MATCH SEARCH ]##################
if($do_loose){

	#complile the search ..
	$search = " WHERE ";
	foreach($qarr as $each){
		$search .= "(lower(content) LIKE '%$each%') OR ";
	}
	$search = substr($search,0,-3);

	$get_search = "SELECT id,heading FROM supp_db_questions $search $exclude";
	$run_search = db_exec($get_search) or errDie("Unable to get search results");
	if(pg_numrows($run_search) < 1){
		$results = "<tr><td>No results were found.</td></tr>";
	}else {
		$results = "";
		while ($sarr = pg_fetch_array($run_search)){
			$results .= "<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='support_search.php?id=$sarr[id]'>$sarr[heading]</a></td></tr>";
		}
	}

}
########################[ LOOSE ]##########################


	$display = "
			<h2>Search Results</h2>
			<table ".TMPL_tblDflts." width='50%'>
				$results
			</table>
			<br>
			<table ".TMPL_tblDflts." width=15%>
				<tr><th>Quick Links</th></tr>
				<td bgcolor='".TMPL_tblDataColor1."'><a href='support_search.php'>New Search</a></td>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>
		";
	return $display;

}

function show_question ($id = "")
{

	$id = $id + 0;

	if($id == "0"){
		return "Invalid use of module";
	}

	db_connect ();

	#get this data
	$get_info = "SELECT * FROM supp_db_questions WHERE id = '$id' LIMIT 1";
	$run_info = db_exec($get_info) or errDie("Unable to get support question information");
	if(pg_numrows($run_info) < 1){
		$info = "<tr><td bgcolor='".TMPL_tblDataColor1."'>Invalid ID Supplied</td></tr>";
	}else {
		$arr = pg_fetch_array($run_info);
		$info = "
				<tr>
					<th>Result Heading</th>
				</tr>
				<tr>
					<td bgcolor='".TMPL_tblDataColor1."'>$arr[heading]</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th>Result Content</th>
				</tr>
				<tr>
					<td bgcolor='".TMPL_tblDataColor2."'>".nl2br($arr['content'])."</td>
				</tr>
			";
	}

	$display = "
			<h2>Search Results</h2>
			<table ".TMPL_tblDflts." width='50%'>
				$info
				<tr><td><br></td></tr>
				<tr>
					<td><input type='button' onClick='javascript:history.back();' value='Return To Results'></td>
				</tr>
			</table>
			<br>
			<table ".TMPL_tblDflts." width=15%>
				<tr><th>Quick Links</th></tr>
				<td bgcolor='".TMPL_tblDataColor1."'><a href='support_search.php'>New Search</a></td>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>
		";
	return $display;


}


?>
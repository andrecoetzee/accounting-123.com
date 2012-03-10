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

// NODE FORMAT:
// {opening}
// type | node name | node id | node last | open icon | close icon | children
// {closing}
// ends the node (</div> or whatever)
// the children value determines whether any children are present (true/false value)

// TREE ICONS
$tree_icon = array (
	"closed" => "node_closed.gif",
	"closedlast" => "node_closedlast.gif",
	"open" => "node_open.gif",
	"openlast" => "node_openlast.gif",
	"last" => "node_last.gif",
	"none" => "node_none.gif",
	"line" => "node_line.gif"
);

// this class generates a tree from the tree id and return's it's HTML
class clsFolderTree {
	var $nodes; // array of nodes in the above format
	var $mnodes; // array of the MAIN nodes' folder_id
	var $account_id; // if this is an account generation, use this if as the id
	var $account_name; // if this is an account generation, use this name as the name

	// what type of tree are we creating now... hmmmmm
	var $type; // "account", "public", "privileged"

	// these variables are for making those little dotted lines and
	// [-] and [+] signs next to each node.
	var $tree_num;
	var $tree_count;

	// stores the javascript for retrieval later on
	var $java;

	// constructor
	function folder_tree() {
	}

	// this clears all variables and set's the necesary ones to the specified values
	function reset_tree($type, $account_id, $account_name, $tree_num, $tree_count) {
		$this->account = 0;
		$this->public = 0;
		$this->privileged = 0;

		// now sets the type... neat
		if ( $type == "account" ) $type = "A";
		if ( $type == "public" ) $type = "P";
		if ( $type == "privileged" ) $type = "V";
		$this->type = $type;

		// set the account name if any
		$this->account_id = $account_id;
		$this->account_name = $account_name;
		$this->tree_num = $tree_num;
		$this->tree_count = $tree_count;

		$this->nodes = "";
		$this->mnodes = "";

		$this->java = "";
	}

	// generates the tree
	function generate_tree() {
		// check to see what type of generation we are doing, create the head node,
		// get the main nodes, and generate the children tree for every main node
		if ( $this->type == "A" ) {
			$gen_title = "$this->account_name";
			$gen_id = $this->account_id;
			$icon_open = "icon_account.gif";
			$icon_closed = "icon_account.gif";
			$sql = "SELECT folder_id,parent_id FROM mail_folders WHERE account_id=$this->account_id";
		}

		if ( $this->type == "P" ) {
			$gen_title = "Public Folders";
			$gen_id = 0;
			$icon_open = "icon_publicfolderopen.gif";
			$icon_closed = "icon_publicfolderclosed.gif";
			$sql = "SELECT folder_id,parent_id FROM mail_folders WHERE \"public\"=1";
		}

		if ( $this->type == "V" ) {
			$gen_title = "Privileged Folders";
			$gen_id = 0;
			$icon_open = "icon_publicfolderopen.gif";
			$icon_closed = "icon_publicfolderclosed.gif";
			$sql = "SELECT mail_folders.folder_id,parent_id FROM mail_folders,mail_priv_folders
					WHERE mail_folders.folder_id = mail_priv_folders.folder_id
						AND priv_owner = '".USER_NAME."' ";
		}

		// generate the main nodes
		$this->generate_main_nodes($sql);

		// start making babies for the main nodes
		if ( is_array($this->mnodes) )
			$parent_count = count($this->mnodes);
		else
			$parent_count = 0;

		$parent_num = 1;

		// create the big main main big node, iow the title node, like Account name :>>>
		$tree_last = ($this->tree_num == $this->tree_count);
		$this->nodes[] = "$this->type|$gen_id|$gen_title|$tree_last|$icon_open|$icon_closed|$parent_count";

		if ( $parent_count > 0 && is_array($this->mnodes) ) {
			foreach ( $this->mnodes as $narr => $node_id ) {
				$this->generate_children($node_id, $sql, 1, $parent_num++, $parent_count);
			}
		}

		// end the BIG BIG node... and viola.... klaar soos 'n blaar
		$this->nodes[] = "_end";
	}

	// generates the main nodes from a result, iow, all folders that satisfy conditions, and dont
	// have parents in the same list
	function generate_main_nodes($sql) {
		$rslt = db_exec($sql);

		if ( pg_num_rows($rslt) > 0 ) {
			// create a list of the folder_id's, and one of the parent_id's
			while ( $row = pg_fetch_array($rslt) ) {
				$fid[] = $row["folder_id"];
				$pid[] = $row["parent_id"];
			}

			// see which items do NOT have a folder id that is in not in the same list's parent id's
			// mark those which do have (they have parents in the list, and is not one themselves)
			foreach ( $fid as $farr => $farrval ) {
				foreach ( $pid as $parr => $parrval ) {
					if ( $fid[$farr] == $pid[$parr] ) {
						$pid[$parr] = -1;
					}
				}
			}

			// all items which had a parent in the same list, had it's parent_id field marked with
			// a -1. If a field doesn't have this mark, it meant it WAS a main node, and it
			// get's added to the list
			foreach ( $fid as $farr => $farrval ) {
				if ( $pid[$farr] != (-1) ) { // wasn't in the list (if it was it would have been marked with -1)
					$this->mnodes[] = $farrval;
				}
			}
		}
	}

	// generates all children under a specific node
	// it also adds the parent specified by $parent_id
	function generate_children($folder_id, $sql, $generation, $child_num, $child_count) {
		// get the current node's information
		$rslt = db_exec("SELECT name,icon_open,icon_closed
			FROM mail_folders WHERE folder_id=$folder_id");
		$row = pg_fetch_array($rslt);

		$folder_name = $row["name"];
		$icon_open = $row["icon_open"];
		$icon_closed = $row["icon_closed"];
		$folder_last = ( $child_num == $child_count );

		// get it's children, if any, and count them
		$rslt = db_exec("$sql AND parent_id=$folder_id");
		$next_child_count = pg_num_rows($rslt);
		$next_child_num = 1;

		// create the current node
		$this->nodes[] = "F|$folder_id|$folder_name|$folder_last|$icon_open|$icon_closed|$next_child_count";

		// generate the children
		if ( $next_child_count > 0 ) {
			while ( $row = pg_fetch_array($rslt) ) {
				$this->generate_children($row["folder_id"], $sql, ($generation + 1),
					$next_child_num++, $next_child_count );
			}
		}

		$this->nodes[] = "_end";
	}

	// returns the results as HTML, and generate the java variable
	function fetch_html() {
		$html = "";
		$js_ti = "";

		if ( ! is_array($this->nodes) ) {
			return $html;
		}

		// go through each node, and add to the export html (this creates a whole
		// heirarchy of tables and layers inside each other, which ends up at a nice tree layout)
		foreach ( $this->nodes as $arr => $arrval ) {
			if ( $arrval != "_end" ) {
				list ( $type, $id, $name, $last, $iopen, $iclose, $children ) = explode ( "|", $arrval );

				// tree image (little dotted lines, [-] [+] etc...)
				$ti = "node_";
				if ( $children ) $ti .= "open";
				if ( $last ) $ti .= "last";
				if ( ! $last && ! $children ) $ti .= "none";
				$ti .= ".gif";

				// create the link for open/close node
				if ( $children ) {
					// the following java simple generates to images, whose sources will
					// be used to change the sources of the pages node open and close buttons
					$this->java[] = "ocbtndata_open['".$this->type."_ocbtn_$id'] = new Image();";
					$this->java[] = "ocbtndata_open['".$this->type."_ocbtn_$id'].src = '$ti';";

					$ti_close = str_replace("open", "closed", $ti);
					$this->java[] = "ocbtndata_close['".$this->type."_ocbtn_$id'] = new Image();";
					$this->java[] = "ocbtndata_close['".$this->type."_ocbtn_$id'].src = '$ti_close';";

					// create the link and image
					$ti = "<a href='javascript: nodeShowHide( \"$this->type\", $id );' >
						<img id='".$this->type."_ocbtn_$id' border=0 src='$ti'></a>";
				} else {
					$ti = "<img src='$ti'>";
				}

				// tree line
				if ( ! $last )
					$line = "background='node_line.gif'";
				else
					$line = "";

				// children layer
				if ( $children )
					$children = "<div id='".$this->type."_children_$id'>";
				else
					$children = "";

				// end children layer
				if ( $last )
					$children_end = "</div>";
				else
					$children_end = "";

				// create the link for current folder/account
				switch ( $type ) {
					case "A": // account
						$name = "<a class='mailtree' href='javascript:treeAjaxLink(\"accounts.php\", \"key=edit&aid=$id\");'>$name</a>";
						break;
					case "F": // folder
						$name = "<a class='mailtree' href='javascript:treeAjaxLink(\"messages.php\", \"fid=$id&print=1\");'>$name</a>";
						break;
				}

				$html .= "<table width='100%' cellpadding=0 cellspacing=0>
					<tr>
						<td width=10 valign=top $line>$ti</td>
						<td valign=top>
							<table cellpadding=0 cellspacing=0><tr>
							<td width=10 valign=middle>
								<img id='".$this->type."_ocicon_$id' src='$iclose'></td>
							<td valign=middle>$name</td>
							</tr></table>
							$children";
			} else { // nodes[] == _end
				$html .= "
						$children_end
						</td>
					</tr>
				</table>";
			}
		}

		return $html;
	}

	// function returns a <script></script> source for use in the main document :>
	function fetch_java() {
		$java = "";

		if ( is_array( $this->java ) ) {
			foreach( $this->java as $arr => $jsline ) {
				$java .= "$jsline\n";
			}
		}

		return $java;
	}
};

?>

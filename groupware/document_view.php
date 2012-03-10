<?php

require ("../settings.php");
require ("gw-common.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("document_save.php", "Add Document")
);

require ("gw-tmpl.php");




function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";
	$fields["disp_inactive"] = "";
	$fields["offset"] = 0;

	extract ($fields, EXTR_SKIP);

	$counter1 = 0;
	$counter2 = 0;
	$counter3 = 0;

	if (!empty($disp_inactive)) {
		$sql_status = "(status='active' OR status='inactive')";
	} else {
		$sql_status = "status='active'";
	}

//GET NORMAL CRM DOCUMENTS ...
	if (!empty($search)) {
		$sql = "SELECT * FROM cubit.documents WHERE $sql_status AND (docid ILIKE '%$search%'
				OR title  ILIKE '%$search%' OR location ILIKE '%$search%')";
	} else {
		$sql = "SELECT * FROM cubit.documents WHERE $sql_status";
	}

	$osRslt = db_exec($sql) or errDie("Unable to retrieve documents.");

	$offset_prev = ($offset - 20);
	$offset_next = ($offset + 20);


	if ($offset_prev < 0) {
		$prev = "";
	} else {
		$prev = "<a href='?search=$search&offset=$offset_prev&disp_inactive=$disp_inactive'>&laquo Previous</a>";
	}

	$numcount1 = pg_numrows($osRslt);

	if (!empty($search)) {
		$sql = "
			SELECT * 
			FROM cubit.documents 
			WHERE $sql_status AND (docid ILIKE '%$search%' OR title  ILIKE '%$search%' OR location ILIKE '%$search%')
			LIMIT 20 OFFSET $offset";
	} else {
		$sql = "SELECT * FROM cubit.documents WHERE $sql_status LIMIT 20 OFFSET $offset";
	}
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");

	$doc_out = "";
	while ($doc_data = pg_fetch_array($doc_rslt)) {
		if (!in_team($doc_data["team_id"], USER_ID)) {
			continue;
		}

		if (!$doc_data["wordproc"]) {
			$doc_edit = "document_save.php?id=$doc_data[docid]&mode=edit";
		} else {
			$doc_edit = "word_proc.php?id=$doc_data[docid]";
		}

		$doc_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$doc_data[title]</td>
				<td>$doc_data[location]</td>
				<td><a href='document_det.php?id=$doc_data[docid]'>Details</a></td>
				<td><a href='$doc_edit'>Edit</a></td>
				<td><a href='document_rem.php?id=$doc_data[docid]'>Remove</a></td>
				<td><a href='document_transmit.php?id=$doc_data[docid]'>Transmit</a></td>
				<td><a href='document_movement.php?id=$doc_data[docid]'>Document Movement</a></td>
			</tr>";
		$counter1++;
	}

	if (empty($doc_out)) {
		$doc_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='10'><li>No results found</li></td>
			</tr>";
	}

//GET CUSTOMER DOCUMENTS ...
	$sql = "SELECT * FROM crm.customer_docs LIMIT 20 OFFSET $offset";
	$cd_rslt = db_exec($sql) or errDie("Unable to retrieve customer docs.");

	$numcount2 = pg_numrows($cd_rslt);

	$cdoc_out = "";
	while ($cd_data = pg_fetch_array($cd_rslt)) {
		// Retrieve customer
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cd_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		if (!in_team($cust_data["team_id"], USER_ID)) {
			continue;
		}

		$cdoc_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$cd_data[filename] ($cd_data[real_filename])</td>
				<td>$cust_data[surname]</td>
				<td><a href='../cust_doc_get.php?id=$cd_data[id]&tmp=1&table=customer_docs'>View</a></td>
				<td><a href='doc-cust-rem.php?docid=$cd_data[id]'>Remove</a></td>
				<td colspan='5'>&nbsp;</td>
			</tr>";
		$counter2++;
	}

	if (empty($cdoc_out)) {
		$cdoc_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7'><li>No results found</li></td>
			</tr>";
	}

	db_conn ('crm');

//GET SUPPLIER DOCUMENTS ...
	$sql = "SELECT * FROM crm.supplier_docs LIMIT 20 OFFSET $offset";
	$sd_rslt = db_exec($sql) or errDie("Unable to retrieve customer docs.");

	$numcount3 = pg_numrows($sd_rslt);

	$sdoc_out = "";
	while ($sd_data = pg_fetch_array($sd_rslt)) {
		// Retrieve customer
		$sql = "SELECT * FROM cubit.suppliers WHERE supid='$sd_data[supid]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve supplier.");
		$supp_data = pg_fetch_array($cust_rslt);

		if (!in_team($supp_data["team_id"], USER_ID)) {
			continue;
		}

		if (strlen($sd_data['filename']) > 0){
			$showdoc = "$sd_data[filename]";
		}elseif (strlen($sd_data['real_filename']) > 0){
			$showdoc = "$sd_data[real_filename]";
		}else {
			$showdoc = "File".$sd_data["id"];
		}

		$sdoc_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$showdoc ($sd_data[real_filename])</td>
				<td>$supp_data[supname]</td>
				<td><a href='../supp_doc_get.php?id=$sd_data[id]'>View</a></td>
				<td><a href='doc-supp-rem.php?docid=$sd_data[id]'>Remove</a></td>
				<td colspan='5'>&nbsp;</td>
			</tr>";
		$counter3++;
	}

	if (empty($sdoc_out)) {
		$sdoc_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7'><li>No results found</li></td>
			</tr>";
	}

	if ($counter1 < 20 AND $counter2 < 20 AND $counter3 < 20){
		$showbuttons = "";
	}else {
		
	}

	if (($offset_next > $numcount1) AND ($offset_next > $numcount2) AND ($offset_next > $numcount3)) {
		$next = "";
	} else {
		$next = "<a href='?search=$search&offset=$offset_next&disp_inactive=$disp_inactive'>Next &raquo</a>";
	}

	$OUTPUT = "
		<center>
		<h3>View Documents</h3>
		<form method='post' action='".SELF."' name='form'>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th colspan='2'>Search</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' name='search' value='$search' /></td>
				<td><input type='submit' value='Search' /></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>
					<input type='checkbox' name='disp_inactive' value='checked'
					$disp_inactive onchange='javascript:document.form.submit()'>
					Display Inactive
				</td>
			</tr>
		</table>
		</form>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Title</th>
				<th>Location</th>
				<th colspan='5'>Options</th>
			</tr>
			$doc_out
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>$prev</td>
				<td colspan='4' align='right'>$next</td>
			</tr>
			<tr>
				<th colspan='7'>Customer Documents</th>
			</tr>
			<tr>
				<th>Title</th>
				<th>Customer</th>
				<th colspan='5'>Options</th>
			</tr>
			$cdoc_out
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>$prev</td>
				<td colspan='4' align='right'>$next</td>
			</tr>
			<tr>
				<th colspan='7'>Supplier Documents</th>
			</tr>
			<tr>
				<th>Title</th>
				<th>Supplier</th>
				<th colspan='5'>Options</th>
			</tr>
			$sdoc_out
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>$prev</td>
				<td colspan='4' align='right'>$next</td>
			</tr>
			$showbuttons
		</table>
		</center>";
	return $OUTPUT;

}


?>
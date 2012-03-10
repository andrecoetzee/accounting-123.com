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
$JSCRIPT = "
<script>
	var objdata = new Array();
	var ocbtndata_open = new Array();
	var ocbtndata_close = new Array();
	var ocicondata_open = new Array();
	var ocicondata_close = new Array()

	// shows and hides the tree
	function nodeShowHide(type,obj) {
		name_children = type + '_children_' + obj;
		name_ocbtn = type + '_ocbtn_' + obj;
		name_ocicon = type + '_ocicon_' + obj;

		childlayer = getObjectById(name_children); // layer which gets closed/opened
		ocbtnlayer = getObjectById(name_ocbtn); // open close button for nodes
		ociconlayer = getObjectById(name_ocicon); // open close icon next to folder name


		if ( childlayer.innerHTML == '' ) {
			childlayer.innerHTML = objdata[name_children]; // restore the data previously stored in array for this layer
			ocbtnlayer.src = ocbtndata_open[name_ocbtn].src // change the image of the node open/close btn
		} else {
			objdata[name_children] = childlayer.innerHTML; // store the data of layer in array
			childlayer.innerHTML = ''; // clear the array
			ocbtnlayer.src = ocbtndata_close[name_ocbtn].src; // change the image of the node open/close btn
		}
	}
</script>
";

?>

<?

/**
 * Generally used functions/constants related to html popups
 *
 * @package Cubit
 * @subpackage XPopup
 */
if (!defined("XPOPUP_LIB")) {
	define("XPOPUP_LIB", true);

/**
 * some of these functions are closely interlinked with the ajax functions,
 * so take caution when changing some things.
 *
 * such pieces are show with "///" comments
 */

/**
 * determine how to get to dateselect.php
 */
$dateselect = relpath("dateselect.php");

/**
 * XPopup javascript and a function to display data selection popup
 *
 */
global $JS_XPOPUP;
addglobals("JS_XPOPUP");

$JS_XPOPUP = "
	<script type=\"application/x-javascript\">
		document.onmousemove = getMouseXY;

		function findPosXY(obj) {
			obj.x = findPosX;
			obj.y = findPosY;
			return true;
		}

		function findPosX(eElement) {
			if (!eElement && this) {
				eElement = this;
			}

			var DL_bIE = document.all ? true : false;

			var nLeftPos = eElement.offsetLeft;
			var eParElement = eElement.offsetParent;

			while (eParElement != null) {
				if(DL_bIE) {
					if( (eParElement.tagName != 'TABLE') && (eParElement.tagName != 'BODY') ) {
					nLeftPos += eParElement.clientLeft;
					}
				} else {
					if(eParElement.tagName == 'TABLE') {
					var nParBorder = parseInt(eParElement.border);
					if(isNaN(nParBorder)) {
						var nParFrame = eParElement.getAttribute('frame');
						if(nParFrame != null) {
							nLeftPos += 1;
						}
					}
					else if(nParBorder > 0) {
						nLeftPos += nParBorder;
					}
					}
				}
				nLeftPos += eParElement.offsetLeft;
				eParElement = eParElement.offsetParent;
			}
			return nLeftPos;
		}

		function findPosY(eElement) {
			if (!eElement && this) {
				eElement = this;
			}

			var DL_bIE = document.all ? true : false;

			var nTopPos = eElement.offsetTop;
			var eParElement = eElement.offsetParent;

			while (eParElement != null) {
				if(DL_bIE) {
					if( (eParElement.tagName != 'TABLE') && (eParElement.tagName != 'BODY') ) {
					nTopPos += eParElement.clientTop;
					}
				} else {
					if(eParElement.tagName == 'TABLE') {
					var nParBorder = parseInt(eParElement.border);
					if(isNaN(nParBorder)) {
						var nParFrame = eParElement.getAttribute('frame');
						if(nParFrame != null) {
							nTopPos += 1;
						}
					} else if(nParBorder > 0) {
						nTopPos += nParBorder;
					}
					}
				}

				nTopPos += eParElement.offsetTop;
				eParElement = eParElement.offsetParent;
			}
			return nTopPos;
		}

		// Temporary variables to hold mouse x-y pos.s
		var mouseX = 0
		var mouseY = 0

		// Main function to retrieve mouse x-y pos.s
		function getMouseXY(e) {
			mouseX = e.pageX;
			mouseY = e.pageY;

			// catch possible negative values in NS4
			if (mouseX < 0) {
				mouseX = 0;
			}
			if (mouseY < 0) {
				mouseY = 0;
			}

			moveXLayerAct();

			return true;
		}

		/**
		 * register/unregister mouse movement
		 */
		var movingXLayer = false;
		var initposXMouse = 0;
		var initposYMouse = 0;
		var initposXLayer = 0;
		var initposYLayer = 0;
		var moveXNoAction = true;

		function moveXLayer(status) {
			if (movingXLayer = status) {
				initposXMouse = mouseX;
				initposYMouse = mouseY;

				layer = getObject(XPopupLayer());

				findPosXY(layer);
				initposXLayer = layer.x();
				initposYLayer = layer.y();

				moveXNoAction = true;
			}
		}

		/**
		 * actual layer moving with mouse
		 */
		function moveXLayerAct() {
			if (!movingXLayer) {
				return;
			}

			layer = getObject(XPopupLayer());
			propTop = initposYLayer - (initposYMouse - mouseY);
			propLeft = initposXLayer - (initposXMouse - mouseX);

			moveXNoAction = !moveXNoAction;
			if (moveXNoAction) {
				return;
			}

			if (propTop < 0) {
				propTop = 0;
			}

			if (propLeft < 0) {
				propLeft = 0;
			}

			if ((toomuch = (propTop + xp.offsetHeight) - window.innerHeight) > 0) {
				propTop -= toomuch;
			}

			if ((toomuch = (propLeft + xp.offsetWidth) - window.innerWidth) > 0) {
				propLeft -= toomuch;
			}

			// now set the position
			xp.style.top = propTop;
			xp.style.left = propLeft;
		}

		/**
		 * returns the layer XPopup uses's name
		 */
		function XPopupLayer() {
			return 'x_popup';
		}

		// obj = object we are moving over, content is html to fill it with
		XPopupHideTimer = false;
		XPopupActive = false;
		XPopupObject = false;
		XPopupContent = false;
		XPopupShowTimer = false;
		XPopupDuration = false;
		XPopupShowClose = false;
		function XPopupShow(content, object) {
			XPopupNoHide();

			if (XPopupShowTimer == false && XPopupContent != content) {
				XPopupContent = content;
				if (object) {
					XPopupObject = object;
				}
				XPopupShowTimer = setTimeout('XPopupShowAct()', 50);
			} else if (XPopupContent != content) {
				clearTimeout(XPopupShowTimer);
				XPopupShowTimer = false;
			}
		}

		function XPopupShowAct() {
			if (XPopupActive != false) return;

			xp = document.getElementById('x_popup');

			/// XPopupContent is set to null in the dateSelPopup() function
			/// called through ajax to make date selection popups.
			/// if changing this expression to match something other than
			/// null, remember to change the value dateSelPopup() passes
			/// aswell
			if (XPopupContent != null) {
				xp.innerHTML =
					'<table bgcolor=\"#fdeb89\" style=\"border: 1px dashed black\">'
					+'<tr><td align=\"right\">[<a href=\"javascript: XPopupHideAct()\">Close</a>]</td></tr>'
					+'<tr><td>'
						+ XPopupContent +
					'</td></tr>'
					+'</table>';
			}

			if (XPopupObject) {
				// get the object we clicked on
				o = XPopupObject;
				findPosXY(o);

				// calculate a position where popup will be 100% visible
				propTop = o.y() + o.offsetHeight;
				propLeft = o.x();
			} else {
				propTop = mouseY - (xp.offsetHeight / 2);
				propLeft = mouseX;
			}

			if ((toomuch = (propTop + xp.offsetHeight) - (window.innerHeight + window.pageYOffset)) > 0) {
				propTop -= toomuch;
			}

			if ((toomuch = (propLeft + xp.offsetWidth) - (window.innerWidth + window.pageXOffset)) > 0) {
				propLeft -= toomuch + 15;
			}

			if (propTop < 0) {
				propTop = 0;
			}

			if (propLeft < 0) {
				propLeft = 0;
			}

			// now set the position
			xp.style.top = propTop;
			xp.style.left = propLeft;

			// max width/height
			if (xp.offsetWidth > 300) {
				xp.style.width = 300;
			}

			xp.style.visibility = 'visible';
			XPopupShowTimer = false;
		}

		function XPopupHide() {
			if (XPopupHideTimer == false) {
				XPopupHideTimer = setTimeout('XPopupHideAct()', 500);
			}
		}

		function XPopupNoHide() {
			if (XPopupHideTimer != false) {
				clearTimeout(XPopupHideTimer);
				XPopupHideTimer = false;
			}
		}

		function XPopupHideAct() {
			xp = document.getElementById('x_popup');
			xp.style.visibility = 'hidden';
			xp.innerHTML = '';

			XPopupHideTimer = false;
			XPopupActive = false;
			XPopupContent = false;
			XPopupObject = false;

			if (XPopupShowTimer != false) {
				clearTimeout(XPopupShowTimer);
				XPopupShowTimer = false;
			}
		}

		/**
		 * initiates the date selection popup
		 */
		dateSelPopup_updateKeys = null;
		function dateSelPopup(idpfx, GWPP, arraykeys) {
			if (arraykeys) {
				dateSelPopup_updateKeys = arraykeys.split(',');
				v1 = dateSelPopup_updateKeys[0];

				/* get date field values */
				f_day = getObject(idpfx + '[' + v1 + ']_day');
				f_month = getObject(idpfx + '[' + v1 + ']_month');
				f_year = getObject(idpfx + '[' + v1 + ']_year');
			} else {
				dateSelPopup_updateKeys = null;

				/* get date field values */
				f_day = getObject(idpfx + '_day');
				f_month = getObject(idpfx + '_month');
				f_year = getObject(idpfx + '_year');
			}

			/* set default popup date values */
			val_day = (f_day && f_day.value == '') ? '".date("d")."' : f_day.value;
			val_month = (f_month && f_month.value == '') ? '".date("m")."' : f_month.value;
			val_year = (f_year && f_year.value == '') ? '".date("Y")."' : f_year.value;

			/* build get */
			get = 'date_selection=t'
				+ '&GWPP=' + GWPP
				+ '&idprefix=' + idpfx
				+ '&day=' + val_day
				+ '&month=' + val_month
				+ '&year=' + val_year

			/* do the request */
			ajaxRequest('$dateselect', XPopupLayer(), AJAX_SET | AJAX_EXE, get,
				dateSelPopupAct);
		}

		/**
		 * updates the date selection popup to specified month. popup
		 * uses this to move to previous/next month/year.
		 */
		function dateSelMove(idpfx, day, month, year, sday, smonth, syear, GWPP) {
			get = 'date_selection=t'
				+ '&GWPP=' + GWPP
				+ '&idprefix=' + idpfx
				+ '&day=' + day
				+ '&month=' + month
				+ '&year=' + year
				+ '&sday=' + sday
				+ '&smonth=' + smonth
				+ '&syear=' + syear;

			ajaxRequest('$dateselect', XPopupLayer(), AJAX_SET, get);
		}

		/**
		 * updates the date selection popup to month/year selected by
		 * the dropdowns
		 */
		function dateSelMoveBySelect(idpfx, day, sday, smonth, syear, GWPP) {
			document.getElementById('datesel_loading').style.height = '200px';
			document.getElementById('datesel_loading').style.visibility = 'visible';
			document.getElementById('datesel_calender').style.visibility = 'hidden';

			mon = getObject('datesel_move_month').value;
			year = getObject('datesel_move_year').value;

			dateSelMove(idpfx, day, mon, year, sday, smonth, syear, GWPP);
		}

		/**
		 * function to popup the date selection
		 */
		/// this is specified as exec function in ajax call with object to
		/// position with as 6th parameter.
		/// null is specified as popup text so XPopupShowAct() doesn't
		/// update the contents.
		function dateSelPopupAct(layer) {
			XPopupShow(null);
		}

		/**
		 * updates the forms with newly selected dates
		 *
		 * @param string idpfx form field prefix
		 * @param int day
		 * @param int month
		 * @param int year
		 */
		function dateSelUpdate(idpfx, day, month, year, textfield) {
			XPopupHideAct();

			if (dateSelPopup_updateKeys) {
				for (i = 0; i < dateSelPopup_updateKeys.length; ++i) {
					v = dateSelPopup_updateKeys[i];

					f_day = getObject(idpfx + '[' + v + ']_day');
					f_month = getObject(idpfx + '[' + v + ']_month');
					f_year = getObject(idpfx + '[' + v + ']_year');

					f_day.value = day;
					f_month.value = month;
					f_year.value = year;
				}
			} else {
				f_day = getObject(idpfx + '_day');
				f_month = getObject(idpfx + '_month');
				f_year = getObject(idpfx + '_year');

				f_day.value = day;
				f_month.value = month;
				f_year.value = year;
			}
		}
	</script>";

} /* LIB END */
?>

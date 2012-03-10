////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// VARIABLES / OBJECTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

var cubitmenuElementID = 1;

var cubitmenuTimeOut = null;			// how long the menu would stay
var cubitmenuCurrentItem = null;		// the current menu item being selected;

var cubitmenuNoAction = new Object ();	// indicate that the item cannot be hovered.
var cubitmenuSplit = new Object ();		// indicate that the item is a menu split

var cubitmenuItemList = new Array ();		// a simple list of items

var cubitmenuMainOrient = 'h';
var cubitmenuSubOrient = 'v';
var cubitmenuCloseDelay = 100;

// the actualleeee menu objecteee
var cubitmenuObject = {
  	mainFolderLeft: '&nbsp;',
  	mainFolderRight: '&nbsp;',
	mainItemLeft: '&nbsp;',
	mainItemRight: '&nbsp;',

	folderLeft: '',
	folderRight: '<img alt="" src="menus/arrow.gif">',
	itemLeft: '',
	itemRight: '',

	mainSpacing: 0,
	subSpacing: 0
};

// for horizontal menu split
var cubitmenuHSplit = [cubitmenuNoAction, '<td class="topMenuSplit"></td><td colspan="2"><div class="topMenuSplit"></div></td>'];
var cubitmenuMainHSplit = [cubitmenuNoAction, '<td class="topMenuSplit"></td><td colspan="2"><div class="topMenuSplit"></div></td>'];
var cubitmenuMainVSplit = [cubitmenuNoAction, '|'];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// FUNCTIONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function getObjectById(id) {
	return getObj(id);
}

// change the contents of the debug layer
function debugmsg(msg) {
	getObjectById('cubit_menu_debug').innerHTML = msg;
}

// produce a new unique id for the new element
function cubitmenuNewID (prefix) {
	return prefix + cubitmenuElementID++;
}

// return the property string for the menu item
function cubitmenuActionItem (item, prefix, isMain, idSub, orientation, nodeProperties) {
 	cubitmenuItemList[cubitmenuItemList.length] = item;
	var index = cubitmenuItemList.length - 1;
	idSub = ( ! idSub) ? 'null' : ('\'' + idSub + '\'');
	orientation = '\'' + orientation + '\'';
	prefix = '\'' + prefix + '\'';
	//return ' onmouseover="cubitmenuItemMouseOver (this,' + prefix + ',' + isMain + ',' + idSub + ',' + orientation + ',' + index + ')" onmouseout="cubitmenuItemMouseOut (this)" onmousedown="cubitmenuItemMouseDown (this,' + index + ')" onmouseup="cubitmenuItemMouseUp (this,' + index + ')"';
	return ' onmouseover="cubitmenuItemMouseOver (this,' + prefix + ',' + isMain + ',' + idSub + ',' + orientation + ',' + index + ')" onmouseout="cubitmenuItemMouseOut(this)" onmousedown="cubitmenuItemMouseDown (this,' + index + '); cubitmenuItemMouseUp(this, '+index+');" ';
}

function cubitmenuSplitItem (prefix, isMain, vertical) {
	var classStr = 'cubitmenu'
	if (isMain)
	{
		classStr += 'Main';
		if (vertical)
			classStr += 'HSplit';
		else
			classStr += 'VSplit';
	}
	else
		classStr += 'HSplit';
	var item = eval (classStr);

	return item[1];
}

// draw the sub menu recursively
function cubitmenuDrawSubMenu (subMenu, prefix, id, nodeProperties) {
	var str = '<div class="' + prefix + 'SubMenu" id="' + id + '"><table summary="sub menu" cellspacing="' + nodeProperties.subSpacing + '" class="' + prefix + 'SubMenuTable">';
	var strSub = '';

	var item;
	var idSub;
	var hasChild;

	var i;

	var classStr;

	for (i = 5; i < subMenu.length; ++i)
	{
		item = subMenu[i];

		if (!item)
			continue;

		hasChild = (item.length > 5);
		idSub = hasChild ? cubitmenuNewID (prefix) : null;

		str += '<tr class="' + prefix + 'MenuItem" ' + cubitmenuActionItem (item, prefix, 0, idSub, cubitmenuSubOrient, nodeProperties) + '>';

		if (item == cubitmenuSplit)
		{
			str += cubitmenuSplitItem (prefix, 0, true);
			str += '</tr>';
			continue;
		}

		if (item[0] == cubitmenuNoAction)
		{
			str += item[1];
			str += '</tr>';
			continue;
		}

		classStr = prefix + 'Menu';
		classStr += hasChild ? 'Folder' : 'Item';

		str += '\n<td nowrap class="' + classStr + 'Left">';

		if (item[0] != null && item[0] != cubitmenuNoAction)
			str += item[0];
		else
			str += hasChild ? nodeProperties.folderLeft : nodeProperties.itemLeft;

		str += '<td nowrap="t" class="' + classStr + 'Text">' + item[1];

		str += '<td nowrap class="' + classStr + 'Right">';

		if ( hasChild ) {
			str += nodeProperties.folderRight;
			strSub += cubitmenuDrawSubMenu (item, prefix, idSub, nodeProperties);
		} else
			str += nodeProperties.itemRight;
		str += '</td></tr>\n';
	}

	str += '</table></div>' + strSub;
	return str;
}

// The function that builds the menu inside the specified element id.
function cubitmenuDraw (menu, objname, orientation, nodeProperties, prefix) {
	var strSub = '';
	var vertical;
	var i;
	var item;
	var idSub;
	var hasChild;
	var classStr;
	var str = '<table summary="main menu" class="' + prefix + 'Menu" cellspacing="2">';
	var obj = getObjectById (objname);

	// draw the main menu items
	if ( orientation == 'hv' ) {
		str += '<tr>';
		cubitmenuMainOrient = 'h';
		cubitmenuSubOrient = 'v';
		vertical = false;
	} else {
		cubitmenuMainOrient = 'v';
		cubitmenuSubOrient = 'v';
		vertical = true;
	}

	for (i = 0; i < menu.length; i++) 	{
		item = menu[i];

		if ( ! item )
			continue;

		str += vertical ? '\n<tr' : '\n<td nowrap ';

		str += ' class="' + prefix + 'MainItem"';

		hasChild = (item.length > 5);
		idSub = hasChild ? cubitmenuNewID (prefix) : null;

		str += cubitmenuActionItem (item, prefix, 1, idSub, cubitmenuMainOrient, nodeProperties) + '>';
		if ( item == cubitmenuSplit ) {
			str += cubitmenuSplitItem (prefix, 1, vertical);
			str += vertical? '</tr>\n' : '</td>\n';
			continue;
		}

		if ( item[0] == cubitmenuNoAction ) {
			str += item[1];
			str += vertical? '</tr>\n' : '</td>\n';
			continue;
		}

		classStr = prefix + 'Main' + (hasChild ? 'Folder' : 'Item');

		str += vertical ? '\n<td nowrap ' : '\n<span';
		str += ' class="' + classStr + 'Text">';
		str += item[1];

		str += vertical ? '</td>\n' : '</span>';

		str += vertical ? '</tr>\n' : '</td>';

		if (hasChild) {
			strSub += cubitmenuDrawSubMenu (item, prefix, idSub, nodeProperties);
		}
	}

	if ( ! vertical ) str += '</tr>\n';

	str += '</table>' + strSub;

	//obj.innerHTML = '<xmp>' + str + '</xmp>';
	obj.innerHTML = str;
}

// action should be taken for mouse moving in to the menu item
function cubitmenuItemMouseOver (obj, prefix, isMain, idSub, orientation, index) {
	clearTimeout (cubitmenuTimeOut);
	cubitmenuTimeOut = null;

	if (!obj.cubitmenuPrefix) {
		obj.cubitmenuPrefix = prefix;
		obj.cubitmenuIsMain = isMain;
	}

	var thisMenu = cubitmenuGetThisMenu (obj, prefix);

	// insert obj into cubitmenuItems if cubitmenuItems doesn't have obj
	if (!thisMenu.cubitmenuItems)
		thisMenu.cubitmenuItems = new Array ();
	var i;

	for (i = 0; i < thisMenu.cubitmenuItems.length; i++) {
		if (thisMenu.cubitmenuItems[i] == obj)
			break;
	}

	if (i == thisMenu.cubitmenuItems.length) {
		//thisMenu.cubitmenuItems.push (obj);
		thisMenu.cubitmenuItems[i] = obj;
	}

	// hide the previous submenu that is not this branch
	if (cubitmenuCurrentItem) {
		// occationally, we get this case when user
		// move the mouse slowly to the border
		if (cubitmenuCurrentItem == thisMenu)
			return;

		var thatPrefix = cubitmenuCurrentItem.cubitmenuPrefix;
		var thatMenu = cubitmenuGetThisMenu (cubitmenuCurrentItem, thatPrefix);
		if ( thatMenu != thisMenu.cubitmenuParentMenu ) {
			if (cubitmenuCurrentItem.cubitmenuIsMain)
				cubitmenuCurrentItem.className = thatPrefix + 'MainItem';
			else
				cubitmenuCurrentItem.className = thatPrefix + 'MenuItem';
			if (thatMenu.id != idSub) {
				cubitmenuHideMenu (thatMenu, thisMenu, thatPrefix);
			}
		}
	}

	// okay, set the current menu to this obj
	cubitmenuCurrentItem = obj;

	// just in case, reset all items in this menu to MenuItem
	cubitmenuResetMenu (thisMenu, prefix);

	var item = cubitmenuItemList[index];
	var isDefaultItem = cubitmenuIsDefaultItem (item);

	if (isDefaultItem) {
		if (isMain)
			obj.className = prefix + 'MainItemHover';
		else
			obj.className = prefix + 'MenuItemHover';
	}

	if (idSub) {
		var subMenu = getObjectById (idSub);
		cubitmenuShowSubMenu (obj, prefix, subMenu, orientation);
	}

	var descript = '';
	if (item.length > 4)
		descript = (item[4] != null) ? item[4] : (item[2] ? item[2] : descript);
	else if (item.length > 2)
		descript = (item[2] ? item[2] : descript);

	window.defaultStatus = descript;
}

// action should be taken for mouse moving out of the menu item
function cubitmenuItemMouseOut () {
	if ( cubitmenuTimeOut == null ) cubitmenuTimeOut = window.setTimeout ('cubitmenuHideMenuTime ()', 1000);
	window.defaultStatus = '';
}

function cubitmenuItemMouseOutImmed() {
	cubitmenuHideMenuTime();
	window.defaultStatus = '';
}

// action should be taken for mouse button down at a menu item
function cubitmenuItemMouseDown (obj, index) {
	if (cubitmenuIsDefaultItem (cubitmenuItemList[index])) {
		if (obj.cubitmenuIsMain)
			obj.className = obj.cubitmenuPrefix + 'MainItemActive';
		else
			obj.className = obj.cubitmenuPrefix + 'MenuItemActive';
	}
}

// action should be taken for mouse button up at a menu item
function cubitmenuItemMouseUp (obj, index) {
	var item = cubitmenuItemList[index];

	var link = null, target = '';

	if ( item.length > 2 )
		link = item[2];
	if ( item.length > 3 )
		target = item[3] ? item[3] : target;

	// open the link in the correct location
	if (link != null) {
		switch ( target ) {
			case "help":
				link = 'help/help_window.php?f=' + link;
			case "_blank":
				window.open(link, target);
				break;
			case "mainframe":
				frames.mainframe.location = link;
				break;
			case "_crmPopup":
				crmPopup(link);
				break;
			case "theframe":
				top.theframe.location = link;
				break;
			case "ajax":
				ajaxLink("iframe.php", link);
				break;
			default:
				document.location = link;
		}
	}

	var prefix = obj.cubitmenuPrefix;
	var thisMenu = cubitmenuGetThisMenu (obj, prefix);

	var hasChild = (item.length > 5);
	if ( ! hasChild ) {
		if (cubitmenuIsDefaultItem (item)) {
			if (obj.cubitmenuIsMain)
				obj.className = prefix + 'MainItem';
			else
				obj.className = prefix + 'MenuItem';
		}
		cubitmenuHideMenu (thisMenu, null, prefix);
	}
	else {
		if ( cubitmenuIsDefaultItem (item) ) {
			if (obj.cubitmenuIsMain)
				obj.className = prefix + 'MainItemHover';
			else
				obj.className = prefix + 'MenuItemHover';
		}
	}
}

// move submenu to the appropriate location
function cubitmenuMoveSubMenu (obj, subMenu, orientation) {
	var mode = String (orientation);
	var p = subMenu.offsetParent;
	if (mode.charAt (0) == 'h') {
			subMenu.style.top = (cubitmenuGetYAt(obj, p) + obj.offsetHeight) + 'px';
			subMenu.style.left = (cubitmenuGetXAt(obj, p)) + 'px';
	}
	else {
			proposedXPos = (cubitmenuGetXAt(obj, p) + obj.offsetWidth);
			proposedYPos = cubitmenuGetYAt(obj, p);
			docWidth = window.innerWidth;
			docHeight = window.innerHeight;

			if ( proposedXPos + subMenu.offsetWidth < docWidth ) {
				subMenu.style.left = proposedXPos + 'px';
			} else if ( cubitmenuGetXAt(obj, p) - subMenu.offsetWidth < 0 ) {
				subMenu.style.left = 0;
			} else {
				subMenu.style.left = (cubitmenuGetXAt(obj, p) - subMenu.offsetWidth) + 'px';
			}

			if ( proposedYPos + subMenu.offsetHeight < docHeight ) {
				subMenu.style.top = proposedYPos + 'px';
			} else if ( docHeight - subMenu.offsetHeight < 0 ) {
				subMenu.style.top = 0;
			} else {
				subMenu.style.top = (docHeight - subMenu.offsetHeight) + 'px';
			}
	}
}

// show the subMenu with specified orientationation and move it to correct location
function cubitmenuShowSubMenu (obj, prefix, subMenu, orientation) {
	if (!subMenu.cubitmenuParentMenu) 	{
		// establish the tree w/ back edge
		var thisMenu = cubitmenuGetThisMenu (obj, prefix);
		subMenu.cubitmenuParentMenu = thisMenu;
		if (!thisMenu.cubitmenuSubMenu)
			thisMenu.cubitmenuSubMenu = new Array ();
		//thisMenu.cubitmenuSubMenu.push (subMenu);
		thisMenu.cubitmenuSubMenu[thisMenu.cubitmenuSubMenu.length] = subMenu;
	}

	// position the sub menu
	cubitmenuMoveSubMenu (obj, subMenu, orientation);
	subMenu.style.visibility = 'visible';

	if ( document.all ) { // it is IE 4... iesh
		subMenu.cubitmenuOverlap = new Array ();
		cubitmenuHideControl ("IFRAME", subMenu);
		cubitmenuHideControl ("SELECT", subMenu);
		cubitmenuHideControl ("OBJECT", subMenu);
	}
}

// reset all the menu items to class MenuItem in thisMenu
function cubitmenuResetMenu (thisMenu, prefix) {
	if (thisMenu.cubitmenuItems) {
		var i;
		var str;
		var items = thisMenu.cubitmenuItems;
		for (i = 0; i < items.length; ++i) {
			if (items[i].cubitmenuIsMain)
				str = prefix + 'MainItem';
			else
				str = prefix + 'MenuItem';
			if (items[i].className != str)
				items[i].className = str;
		}
	}
}

// called by the timer to hide the menu
function cubitmenuHideMenuTime () {
	if (cubitmenuCurrentItem) {
		var prefix = cubitmenuCurrentItem.cubitmenuPrefix;
		cubitmenuHideMenu (cubitmenuGetThisMenu (cubitmenuCurrentItem, prefix), null, prefix);
	}
}

// hide thisMenu, children of thisMenu, as well as the ancestor
// of thisMenu until currentMenu is encountered.  currentMenu
// will not be hidden
function cubitmenuHideMenu (thisMenu, currentMenu, prefix) {
	var str = prefix + 'SubMenu';

	// hide the down stream menus
	if (thisMenu.cubitmenuSubMenu) {
		var i;
		for (i = 0; i < thisMenu.cubitmenuSubMenu.length; ++i) {
			cubitmenuHideSubMenu (thisMenu.cubitmenuSubMenu[i], prefix);
		}
	}

	// hide the upstream menus
	while (thisMenu && thisMenu != currentMenu) {
		cubitmenuResetMenu (thisMenu, prefix);
		if (thisMenu.className == str) {
			thisMenu.style.visibility = 'hidden';
			cubitmenuShowControl (thisMenu);
		}
		else
			break;
		thisMenu = cubitmenuGetThisMenu (thisMenu.cubitmenuParentMenu, prefix);
	}
}

// hide thisMenu as well as its sub menus if thisMenu is not
// already hidden
function cubitmenuHideSubMenu (thisMenu, prefix) {
	if (thisMenu.style.visibility == 'hidden')
		return;
	if (thisMenu.cubitmenuSubMenu) {
		var i;
		for (i = 0; i < thisMenu.cubitmenuSubMenu.length; ++i) {
			cubitmenuHideSubMenu (thisMenu.cubitmenuSubMenu[i], prefix);
		}
	}
	cubitmenuResetMenu (thisMenu, prefix);
	thisMenu.style.visibility = 'hidden';
	cubitmenuShowControl (thisMenu);
}

// hide a control such as an IFRAME, helps in IE 4 where they are treated as window objects
function cubitmenuHideControl (tagName, subMenu) {

	// disable this function
	return;

	var x = cubitmenuGetX (subMenu);
	var y = cubitmenuGetY (subMenu);
	var w = subMenu.offsetWidth;
	var h = subMenu.offsetHeight;

	var i;
	for (i = 0; i < document.all.tags(tagName).length; ++i) {
		var obj = document.all.tags(tagName)[i];
		if (!obj || !obj.offsetParent)
			continue;

		// check if the object and the subMenu overlap
		var ox = cubitmenuGetX (obj);
		var oy = cubitmenuGetY (obj);
		var ow = obj.offsetWidth;
		var oh = obj.offsetHeight;

		if (ox > (x + w) || (ox + ow) < x)
			continue;
		if (oy > (y + h) || (oy + oh) < y)
			continue;

		subMenu.cubitmenuOverlap[subMenu.cubitmenuOverlap.length] = obj;
		obj.style.visibility = "hidden";
	}
}

// show the control hidden by the subMenu
function cubitmenuShowControl (subMenu) {

	// disable this function
	return;

	if (subMenu.cubitmenuOverlap) {
		var i;
		for (i = 0; i < subMenu.cubitmenuOverlap.length; ++i)
			subMenu.cubitmenuOverlap[i].style.visibility = "visible";
	}

	subMenu.cubitmenuOverlap = null;
}

// returns the main menu or the submenu table where this obj (menu item) is in
function cubitmenuGetThisMenu (obj, prefix) {
	var str1 = prefix + 'SubMenu';
	var str2 = prefix + 'Menu';
	while (obj) {
		if (obj.className == str1 || obj.className == str2) return obj;
		obj = obj.parentNode;
	}

	return null;
}

// return true if this item is handled using default handlers
function cubitmenuIsDefaultItem (item) {
	if ( ( item == cubitmenuSplit )
		 || ( item[0] == cubitmenuNoAction ) )
		return false;

	return true;
}

// functions that obtain the coordinates of an HTML element
function cubitmenuGetX (obj) {
	var x = 0;

	do {
		x += obj.offsetLeft;
		obj = obj.offsetParent;
	} while (obj);

	return x;
}

function cubitmenuGetXAt (obj, elm) {
	var x = 0;

	while (obj && obj != elm) {
		x += obj.offsetLeft;
		obj = obj.offsetParent;
	}

	return x;
}

function cubitmenuGetY (obj) {
	var y = 0;
	do {
		y += obj.offsetTop;
		obj = obj.offsetParent;
	} while (obj);

	return y;
}

function cubitmenuGetYAt (obj, elm) {
	var y = 0;

	while (obj && obj != elm) {
		y += obj.offsetTop;
		obj = obj.offsetParent;
	}

	return y;
}

// debug function, ignore :)
function cubitmenuGetProperties (obj) {
	if (obj == undefined)
		return 'undefined';

	if (obj == null)
		return 'null';

	var msg = obj + ':\n';
	var i;
	for (i in obj)
		msg += i + ' = ' + obj[i] + '; ';
	return msg;
}

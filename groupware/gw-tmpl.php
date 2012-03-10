<?
require_lib ("ajax");

if (!isset($js_onthespot)) {
	$js_onthespot = "";
}

print "
<html>

<head>
	<title>Cubit Mail</title>
	<link rel='stylesheet' href='stylesheet.css' type='text/css'>
	<link rel='stylesheet' href='".relpath("toptheme.css")."' type='text/css'>
	<script language='javascript'>
		// returns the object from it's id
		function getObjectById (id) {
			if (document.all)
				return document.all[id];

			return document.getElementById (id);
		}

		function getObject(id) {
			return getObjectById(id);
		}

		function ajaxLink() {
			var argv = ajaxLink.arguments;

			if (!argv[1]) {
				argv[1] = false;
			}

			page = argv[0];
			get = argv[1];

			if (argv[2]) {
				ajaxRequest(page, 'content', AJAX_SET | AJAX_EXE, get, argv[2]);
			} else {
				ajaxRequest(page, 'content', AJAX_SET, get);
			}


		}

		function treeAjaxLink(page, get) {
			ajaxRequest(page, parent.document.getElementById('content'), AJAX_OBJ, get);
		}

		function diaryAjaxLink(page, get) {
			ajaxRequest(page, 'diary_small_month', AJAX_SET, get);
		}

		function popupOpen(url,name) {
			argv = popupOpen.arguments;
			if (argv[2]) {
				opt = argv[2];
			} else {
				opt = 'scrollbars=yes, statusbar=no';
			}
			if (newwin = window.open(url,name,opt))
				newwin.focus();
		}

		function popupSized(url,name,width,height) {
			argv = popupSized.arguments;
			if (argv[4]) {
				opt = argv[4];
			} else {
				opt = 'scrollbars=yes, statusbar=no';
			}
			opt += ', width=' + width + ', height=' + height;

			popupOpen(url,name,opt);
		}
	</script>
	$JS_AJAX
	$JS_XPOPUP
</head>

<body>
<center>
<div id='doc_layer'>
$OUTPUT
<div id='x_popup' onMouseMove='XPopupNoHide();' style='visibility: hidden; position: absolute;'></div>
$js_onthespot
</div>
</center>
</body>
</html>";

exit();

?>

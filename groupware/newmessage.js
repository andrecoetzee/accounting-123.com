	function update() {
		document.editForm.bodydata.value = editArea.document.body.innerHTML;
		document.editForm.submit();
	}

	function NewMessageInit() {
		editArea.document.designMode = 'on';
		editArea.document.body.innerHTML = getObj('storehtml').innerHTML;
	}

	function controlSelOn(ctrl) {
		ctrl.style.borderColor = '#000000';
		ctrl.style.backgroundColor = '#B5BED6';
		ctrl.style.cursor = 'hand';
	}

	function controlSelOff(ctrl) {
		ctrl.style.borderColor = '#D6D3CE';
		ctrl.style.backgroundColor = '#D6D3CE';
	}

	function controlSelDown(ctrl) {
		ctrl.style.backgroundColor = '#8492B5';
	}

	function controlSelUp(ctrl) {
	ctrl.style.backgroundColor = '#B5BED6';
	}

	function doBold() {
		editArea.document.execCommand('bold', false, null);
	}

	function doItalic() {
		editArea.document.execCommand('italic', false, null);
	}

	function doUnderline() {
		editArea.document.execCommand('underline', false, null);
	}

	function doLeft() {
		editArea.document.execCommand('justifyleft', false, null);
	}

	function doCenter() {
		editArea.document.execCommand('justifycenter', false, null);
	}

	function doRight() {
		editArea.document.execCommand('justifyright', false, null);
	}

	function doOrdList() {
		editArea.document.execCommand('insertorderedlist', false, null);
	}

	function doBulList() {
		editArea.document.execCommand('insertunorderedlist', false, null);
	}

	function doRule() {
		editArea.document.execCommand('inserthorizontalrule', false, null);
	}

	function doSize(fSize) {
		if(fSize != '')
			editArea.document.execCommand('fontsize', false, fSize);
	}

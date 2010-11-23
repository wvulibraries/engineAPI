function emod_msww_addItemToID(id, item) {
	var theSelect = document.getElementById(id);
	
	if (item.value == "--null--") {
		return;
	}
	
	for (i = theSelect.length - 1; i >= 0; i--) {
		if (theSelect.options[i].value == item.value) {
			alert("This item has already been selected.");
			return;
		}
	}
	
	theSelect.options[theSelect.length] = new Option(item.text, item.value);
}

function emod_msww_removeItemFromID(id, item) {
	var selIndex = item.selectedIndex;
	if (selIndex != -1) {
		for (i = item.length - 1; i >= 0; i--) {
			if (item.options[i].selected) {
				item.options[i] = null;
			}
		}
		if (item.length > 0) {
			item.selectedIndex = selIndex == 0 ? 0 : selIndex - 1;
		}
	}
}

function emod_msww_selectAllOnSubmit(id) {
	var item = document.getElementById(id);
	
	for (i = item.length - 1; i >= 0; i--) {
		item.options[i].selected = true;
	}
}

function emod_msww_entrySubmit() {
	
	var e = document.getElementsByTagName("select");
	for (var i=0; i<e.length; i++) {
		if (e[i].id.indexOf("ms_") == 0) {
			emod_msww_selectAllOnSubmit(e[i].id);
		}
	}

	
	return(true);
}
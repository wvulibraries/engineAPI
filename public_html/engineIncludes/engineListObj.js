function listObjDeleteConfirm(f) {

var n = 0;

var deleteCheck = false;
for (var I=0;I<f.elements.length;I++ ) {
	if (f.elements[I].name.substr(0,6) == "delete" && f.elements[I].checked) {
		deleteCheck = true;
		n++;
	}
}

if (deleteCheck == true) {
	return confirm ("You have selected to delete "+n+" Items from this list. Continue?")
}

return true;
	
}

function engineDeleteConfirm(label) {
	
	return confirm("Are you sure you want to DELETE "+label+". This cannot be undone.");
	
}
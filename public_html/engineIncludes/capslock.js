function capsLockCheck(e){
	kc = e.keyCode?e.keyCode:e.which;
	sk = e.shiftKey?e.shiftKey:((kc == 16)?true:false);
	if(((kc >= 65 && kc <= 90) && !sk)||((kc >= 97 && kc <= 122) && sk)) {
		document.getElementById('capsLock').style.display = 'block';
	}
	else {
		document.getElementById('capsLock').style.display = 'none';
	}
}

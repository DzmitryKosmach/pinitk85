function seoHideDecode(){
	var shift = 15;
	var chars = 'abcdefghijklmnopqrstuvwxyz0123456789:/-_.?';
	var charsCnt = chars.length;

	var links = byTag('A');
	for(var i = 0, l = links.length; i < l; i++){
		if(typeof(links[i].dataset['shcode']) != 'undefined' && links[i].dataset['shcode'] != ''){
			links[i].href = decode(links[i].dataset['shcode']);
			links[i].dataset['shcode'] = '';
		}
	}

	function decode(code){
		code = code.toLowerCase();
		var url = '', ch = '', n = 0;
		for(var i = 0, l = code.length; i < l; i++){
			ch = code.charAt(i);
			n = chars.indexOf(ch);
			if(n !== -1){
				n -= shift;
				if(n < 0) n += charsCnt;
				ch = chars.charAt(n);
			}
			url += ch;
		}
		return url;
	}
}
onLoad(function(){
	seoHideDecode();
});
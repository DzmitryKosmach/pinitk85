function goToTop(){

	var link = $$$('goto-top');

	function fixPos(){
		var winW = winSize().w;
		if(winW >= 1320){
			link.style.right = Math.round((winW-1240)/2 - (60+20)) + 'px';
		}else{
			link.style.right = '20px';
		}
	}
	fixPos();
	addHandler(
		window,
		'resize',
		function(){fixPos()}
	);

	function checkScroll(){
		var scrollPos = D.body.scrollTop ? D.body.scrollTop : D.documentElement.scrollTop;
		if(scrollPos >= 500){
			link.style.display = 'block';
		}else{
			link.style.display = 'none';
		}
	}
	checkScroll();
	addHandler(
		window,
		'scroll',
		function(){checkScroll()}
	);

}
onLoad(function(){
	goToTop();
});
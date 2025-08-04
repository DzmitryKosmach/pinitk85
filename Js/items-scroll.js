var oItemsScroll, oItemsTabs;

oItemsScroll = new function(){
	var scrollStep = 20;
	var scrollStepsOneTime = 20;
	var scrollMax = 0;
	var menuStartPos = 0;
	var pointsIds = [];
	var points = [];
	var inited = false;
	var obj = this;
	var posDecrease = 70;

	function init(){
		scrollMax = D.body.scrollHeight ? D.body.scrollHeight : D.documentElement.scrollHeight;
		scrollMax -= winSize().h;

		var itemsBlock = $$$('groups-tabs');
		if(!itemsBlock || !$$$('groups-menu')) return;

		menuStartPos = elPos($$$('series-items-title')).y - 80;

		if($$$('groups-menu').offsetHeight > 50) {
			posDecrease = 100;
		}else{
			posDecrease = 70;
		}
		//console.log(posDecrease);

		if(!inited){
			var aTags = byTag('A', itemsBlock);
			pointsIds = [];
			for(var i = 0, l = aTags.length; i < l; i++){
				if(aTags[i].className.indexOf('items-scroll-point') !== -1){
					pointsIds.push(aTags[i].id);
					points[aTags[i].id] = elPos(aTags[i]).y;
				}
			}

			var urlNow = D.location.href;
			for(i = 0, l = pointsIds.length; i < l; i++){
				removeElement($$$(pointsIds[i]));
			}
			if(D.location.href.indexOf('#items-list') !== -1){
				setTimeout(
					function(){
						obj.navTo('#' + pointsIds[0]);
					},
					200
				);
			}

			setInterval(
				function(){
					for(var i = 0; i < scrollStepsOneTime; i++){
						scroll();
					}
				},
				10
			);
			addHandler(window, 'scroll', function(e){onScroll()});
			onScroll();
		}
		inited = true;
	}
	addHandler(window, 'load', function(){
		init();
	});
	addHandler(window, 'resize', function(){
		init();
	});

	this.navTo = function(anchorUrl){
		var pid = anchorUrl.split('#')[1];
		if(typeof(points[pid]) == 'undefined') return true;

		scrollTo(points[pid] - posDecrease);
		return false;
	};


	var scrollTarget = -1;

	function scrollTo(s){
		if(s > scrollMax){
			s = scrollMax;
		}
		if(s < 0){
			s = 0;
		}
		scrollTarget = s;
	}


	function scroll(){
		if(scrollTarget == -1) return;

		var scrollNow = D.body.scrollTop ? D.body.scrollTop : D.documentElement.scrollTop;

		var dir = scrollTarget > scrollNow ? 1 : -1;
		var scrollNew = scrollNow + scrollStep * dir;
		if(Math.abs(scrollNew - scrollTarget) < scrollStep){
			scrollNew = scrollTarget;
			scrollTarget = -1;
			onScroll();
			if($$$('groups-menu').className.indexOf(' floating') !== -1 && $$$('groups-menu').offsetHeight > 40){
				scrollNew -= 50;
			}
		}

		D.body.scrollTop = scrollNew;
		D.documentElement.scrollTop = scrollNew;

		onScroll();
	}


	//var changeUrlTimeout = false;

	function onScroll() {
		var scrollPos = D.body.scrollTop ? D.body.scrollTop : D.documentElement.scrollTop;
		// new code
		var topMenuHeight = document.querySelector('#tpanel .tpanel_inner').offsetHeight;
		// Включаем/выключаем "плавание" для меню групп товаров
		if (scrollPos >= menuStartPos) {
			if ($$$('groups-menu').className.indexOf(' floating') === -1) {
				$$$('groups-menu').className += ' floating';
				// new code
				$$$('groups-menu').style.top = topMenuHeight + 'px';
			}
		} else {
			$$$('groups-menu').className = $$$('groups-menu').className.replace(' floating', '');
			// new code
			$$$('groups-menu').style.top = '0px';
		}

		// Определяем, в области какой группы товаров мы сейчас находимся
		//var urlNew = '#';
		var pActive = pointsIds[0];
		for(var i = 0, l = pointsIds.length; i < l; i++){
			if(scrollPos >= (points[pointsIds[i]] - 300)){
				pActive = pointsIds[i];
				//urlNew = '#' + pActive;
			}
		}
		if(scrollPos >= scrollMax - 200){
			pActive = pointsIds[pointsIds.length-1];
		}

		// Посвечиваем пункт меню
		for(i = 0, l = pointsIds.length; i < l; i++){
			$$$('menu-' + pointsIds[i]).className = '';
		}
		$$$('menu-' + pActive).className = 'active';
	}
};
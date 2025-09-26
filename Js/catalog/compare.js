/**
 * Сравнение серий
 */
var oCompare = new function(){
	/**
	 * {string}
	 */
	this.url = '';


	/**
	 * @param	{int}	seriesId
	 */
	this.add = function(seriesId){
		$$$('series-compare-' + seriesId  + '-0').style.display = 'none';
		$$$('series-compare-' + seriesId  + '-1').style.display = '';

		AJAX.lookup(
			this.url + '?add=' + seriesId + '&ajax=1',
			function(cnt){
				$$$('series-compare-cnt').innerHTML = cnt;
				var spans = find('span.series-compare-cnt');
				for(var i = 0, l = spans.length; i < l; i++){
					if(typeof(spans[i]) === 'undefined') continue;
					spans[i].innerHTML = cnt;
				}

				// Обновляем бейджи сравнения в header
				var desktopBadge = $$$('compare-badge-count-desktop');
				var mobileBadge = $$$('compare-badge-count-mobile');

				if(desktopBadge) {
					desktopBadge.innerHTML = cnt;
					if(cnt > 0) {
						desktopBadge.classList.remove('hidden');
					} else {
						desktopBadge.classList.add('hidden');
					}
				}

				if(mobileBadge) {
					mobileBadge.innerHTML = cnt;
					if(cnt > 0) {
						mobileBadge.classList.remove('hidden');
					} else {
						mobileBadge.classList.add('hidden');
					}
				}
			}
		);
	};


	/**
	 * @param	{int}	seriesId
	 */
	this.remove = function(seriesId){
		$$$('series-compare-' + seriesId + '-0').style.display = '';
		$$$('series-compare-' + seriesId + '-1').style.display = 'none';

		AJAX.lookup(
			this.url + '?remove=' + seriesId + '&ajax=1',
			function(cnt){
				var spans = find('span.series-compare-cnt');
				for(var i = 0, l = spans.length; i < l; i++){
					if(typeof(spans[i]) === 'undefined') continue;
					spans[i].innerHTML = cnt;
				}

				// Обновляем бейджи сравнения в header
				var desktopBadge = $$$('compare-badge-count-desktop');
				var mobileBadge = $$$('compare-badge-count-mobile');

				if(desktopBadge) {
					desktopBadge.innerHTML = cnt;
					if(cnt > 0) {
						desktopBadge.classList.remove('hidden');
					} else {
						desktopBadge.classList.add('hidden');
					}
				}

				if(mobileBadge) {
					mobileBadge.innerHTML = cnt;
					if(cnt > 0) {
						mobileBadge.classList.remove('hidden');
					} else {
						mobileBadge.classList.add('hidden');
					}
				}
			}
		);
	};
};

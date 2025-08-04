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
	 * @param	{int}	rnd
	 */
	this.add = function(seriesId, rnd){
		$$$('series-compare-' + seriesId + '-' + rnd + '-0').style.display = 'none';
		$$$('series-compare-' + seriesId + '-' + rnd + '-1').style.display = '';

		AJAX.lookup(
			this.url + '?add=' + seriesId + '&ajax=1',
			function(cnt){
				$$$('series-compare-cnt').innerHTML = cnt;
				var spans = find('span.series-compare-cnt');
				for(var i = 0, l = spans.length; i < l; i++){
					if(typeof(spans[i]) === 'undefined') continue;
					spans[i].innerHTML = cnt;
				}
			}
		);
	};


	/**
	 * @param	{int}	seriesId
	 * @param	{int}	rnd
	 */
	this.remove = function(seriesId, rnd){
		$$$('series-compare-' + seriesId + '-' + rnd + '-0').style.display = '';
		$$$('series-compare-' + seriesId + '-' + rnd + '-1').style.display = 'none';

		AJAX.lookup(
			this.url + '?remove=' + seriesId + '&ajax=1',
			function(cnt){
				var spans = find('span.series-compare-cnt');
				for(var i = 0, l = spans.length; i < l; i++){
					if(typeof(spans[i]) === 'undefined') continue;
					spans[i].innerHTML = cnt;
				}
			}
		);
	};
};
/**
 *  ласс дл€ работы с переводчиком яндекса
 *
 * @author	Seka
 */

function Translate(){
	this.key = 'trnsl.1.1.20130502T111858Z.69a6b123e6c823d2.26b7a566e66f30f07715f0c0e28b82d35d517a74';


	/**
	 * –езультат перевода вставлетс€ в html-элемент
	 * @param	{string}	text
	 * @param	{object}	htmlElement
	 * @param	{string}	direct
	 */
	this.intoHtml = function(text, htmlElement, direct){
		if(!AJAX.browserSupport) return false;

		if(typeof(htmlElement) != 'object') return false;

		var url = this.url(text, direct); if(!url) return false;

		AJAX.lookup(
			url,
			function(res){
				if(htmlElement.tagName == 'INPUT' || htmlElement.tagName == 'TEXTAREA'){
					htmlElement.value = oTranslate.decode(res);
				}else{
					htmlElement.innerHTML = oTranslate.decode(res);
				}
			}
		);
	};


	/**
	 * –езультат перевода передаЄтс€ в callback-функцию
	 * @param	{string}	text
	 * @param	{function}	callback
	 * @param	{string}	direct
	 */
	this.intoCallback = function(text, callback, direct){
		if(!AJAX.browserSupport) return false;

		if(typeof(callback) != 'function') return false;

		var url = this.url(text, direct); if(!url) return false;

		AJAX.lookup(
			url,
			function(res){
				callback(oTranslate.decode(res));
			}
		);
	};



	/**
	 * √енераци€ URL'а дл€ запроса перевода
	 * @param	{string}	text
	 * @param	{string}	direct
	 */
	this.url = function(text, direct){
		if(typeof(direct) != 'string') direct = 'ru-en';
		if(typeof(text) != 'string') return false;

		return 'https://translate.yandex.net/api/v1.5/tr.json/translate?key=' + encodeURIComponent(this.key) + '&lang=' + encodeURIComponent(direct) + '&text=' + encodeURIComponent(text);
	};




	this.decode = function(respond){
		respond = AJAX.jsonDecode(respond);
		if(!respond || respond.code != 200) return false;
		return respond.text[0];
	}
}
var oTranslate = new Translate();
var D = document;

/**
 * Получает элемент по ID
 * @param	{string}	id
 */
function $$$(id){
	return D.getElementById(id) ? D.getElementById(id) : false;
}

// Эквивалент getElementsByTagName
function byTag(tag, el){
	if(typeof(el) == 'undefined') el = D;
	return el.getElementsByTagName(tag);
}


/**
 * @param	{string}	selector
 * @returns {Node}
 */
Node.prototype.findOne = function(selector){
	return this.querySelector(selector);
};


/**
 * @param	{string}	selector
 * @returns {NodeList}
 */
Node.prototype.find = function(selector){
	return this.querySelectorAll(selector);
};


/**
 * @param	{string}	selector
 * @returns {Node}
 */
function findOne(selector){
	return D.findOne(selector);
}



/**
 * @param	{string}	selector
 * @returns {NodeList}
 */
function find(selector){
	return D.find(selector);
}


/**
 * @param	{string}	c
 * @returns	{boolean}
 */
HTMLElement.prototype.hasClass = function(c){
	var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
	return re.test(this.className);
};


/**
 * @param	{string}	c
 */
HTMLElement.prototype.addClass = function(c){
	var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
	if(re.test(this.className)) return;
	this.className = (this.className + " " + c).replace(/\s+/g, " ").replace(/(^ | $)/g, "");
};


/**
 * @param	{string}	c
 */
HTMLElement.prototype.removeClass = function(c){
	var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
	this.className = this.className.replace(re, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "");
};


// Уничтожение элемента
function removeElement(el){
	return el.parentNode.removeChild(el);
}

// Добавляет обработчик события для объекта
function addHandler(object, event, handler, useCapture){
	if (object.addEventListener)
		object.addEventListener(event, handler, useCapture ? useCapture : false);
	else if (object.attachEvent)
		object.attachEvent('on' + event, handler);
	else object['on' + event] = handler;
}

//
(function(){
	var windowLoaded = false;
	addHandler(window, 'load', function(){
		windowLoaded = true;
	});
	window.onLoad = function(callback){
		if(!windowLoaded){
			addHandler(window, 'load', function(){callback()});
		}else{
			callback();
		}
	};
})();


// Предотвращает реакцию на событие по-умолчанию
function eventCancelDefault(e){
	if(!e) e = window.event;
	if(e.stopPropagation) e.stopPropagation(); else e.cancelBubble = true;
	if(e.preventDefault) e.preventDefault(); else e.returnValue = false;
}

// Клонирование объекта
function clone(obj){
	if(obj == null || typeof(obj) != 'object'){
		return obj;
	}
	var temp = new obj.constructor();
	for(var key in obj){
		temp[key] = clone(obj[key]);
	}
	return temp;
}

// Добавление текущей страницы в избранное
function bookmark(a){
	var url = window.document.location;
	var title = window.document.title;

	if(window.opera){	// Opera
		a.href = url;
		a.rel = 'sidebar';
		a.title = title;
		return true;

	}else if(document.all){	// IE
		var version = Math.round(navigator.appVersion.charAt(navigator.appVersion.indexOf('MSIE') + 5));
		if(version >= 4 && window.external) window.external.addFavorite(url, title);

	}else if(navigator.appName && window.sidebar){	// Другие браузеры
		window.sidebar.addPanel(title, url, '');

	}else alert('Нажмите CTRL-D, чтобы добавить страницу в закладки.');

	return false;
}

// ------------------------------- end DOM ---------------------------------- //
// -------------------------------------------------------------------------- //












// -------------------------------------------------------------------------- //
// ------------------------ КЛАСС ДЛЯ РАБОТЫ AJAX --------------------------- //
// -------------------------------------------------------------------------- //
// encodeURIComponent() - использовать для кодирования строк в GET-параметры
var AJAX = new function(){
	this.browserSupport = false;
	this.num = 0;
	this.objArr = [];
	this.lastUrl = '';
	this.sid = '';

	// Создание http-объекта
	this.getHTTP = function(){
		var xmlhttp;
		/*@cc_on
		@if(@_jscript_version >= 5) try{
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		}catch(e){
			try {
				xmlhttp = new
				ActiveXObject("Microsoft.XMLHTTP");
			}catch(E){
				xmlhttp = false;
			}
		}@else
			xmlhttp = false;
		@end @*/
		if(!xmlhttp && typeof XMLHttpRequest != 'undefined'){
			try{
				xmlhttp = new XMLHttpRequest();
			}catch(e){
				xmlhttp = false;
			}
		}
		return xmlhttp;
	};

	// Отправка запроса
	this.lookup = function(url, resultFunctionOrId, postData, contentType){
		if(!this.browserSupport) return;

		// Метод запроса
		if(typeof(postData) != 'string') postData = null;
		var method = postData != null ? 'POST' : 'GET';

		// Получаем объект XMLHTTPRequest
		var objHTTP = this.getHTTP();

		// Запрос
		if(objHTTP){
			url += url.indexOf('?') == -1 ? '?' : '&';
			url += 'hash=' + Math.random() + '&no-domain-redirect=1';
			this.lastUrl = url;

			objHTTP.open(method, url, true);
			if(typeof(resultFunctionOrId) == 'function'){
				objHTTP.onreadystatechange = function(){
					if(objHTTP.readyState == 4){
						resultFunctionOrId(objHTTP.responseText);
					}
				}
			}else if(typeof(resultFunctionOrId) == 'string'){
				objHTTP.onreadystatechange = function(){
					if(objHTTP.readyState == 4){
						$$$(resultFunctionOrId).innerHTML = objHTTP.responseText;
					}
				}
			}

			if(typeof(contentType) == 'string'){
				objHTTP.setRequestHeader('Content-Type', contentType);
			}else if(method == 'POST'){
				objHTTP.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			}

			objHTTP.send(postData);

		}else{
			alert('Error creating XMLHTTP object!');
		}
	};

	// Проверка поддержки AJAX
	this.test = function(){
		var t = this.getHTTP();
		return t ? true : false;
	};
	this.browserSupport = this.test();


	// Декодирование JSON-данных
	this.jsonDecode = function(respond){
		try{
			return eval('(' + respond + ')');
		}catch(e){
			//alert("Invalid JSON respond.\n-----------------------\n" + e + "\n-----------------------\nURL: " + this.lastUrl + "\n-----------------------\nRespond:\n" + respond);
			return false;
		}
	};


	/**
	 * АЯКСовая отправка формы
	 * @todo	Тут ещё много надо доделать: валидация pattern/required, input'ы вне формы, отправка файлов (?) и т.д.
	 * @param	{object}	event
	 * @param	{object}	form
	 * @param	{function}	callback
	 * @returns	{boolean}
	 */
	this.submitForm = function(event, form, callback){
		eventCancelDefault(event);

		var url = form.action;

		var data = {};
		var i, l;

		var inputs = byTag('INPUT', form);
		for(i = 0, l = inputs.length; i < l; i++){
			if(inputs[i].disabled || !inputs[i].name) continue;
			if(inputs[i].type == 'checkbox' || inputs[i].type == 'radio'){
				if(inputs[i].checked){
					data[inputs[i].name] = inputs[i].value;
				}
			}else{
				data[inputs[i].name] = inputs[i].value;
			}
		}

		var textareas = byTag('TEXTAREA', form);
		for(i = 0, l = textareas.length; i < l; i++){
			if(textareas[i].disabled || !textareas[i].name) continue;
			data[textareas[i].name] = textareas[i].value;
		}

		var selects = byTag('SELECT', form);
		for(i = 0, l = selects.length; i < l; i++){
			if(selects[i].disabled || !selects[i].name) continue;
			data[selects[i].name] = selects[i].value;
		}

		var query = [];
		for(name in data){
			query.push(encodeURIComponent(name) + '=' + encodeURIComponent(data[name]));
		}
		query = query.join('&');

		var postData = null;
		if(form.method == 'post'){
			// POST
			postData = query;
		}else{
			// GET
			url += url.indexOf('?') == -1 ? '?' : '&';
			url += query;
		}

		this.lookup(
			url,
			callback,
			postData
		);

		return false;
	}
};
// ------------------------------- end AJAX --------------------------------- //
// -------------------------------------------------------------------------- //








// -------------------------------------------------------------------------- //
// --------------------------------- INCLUDE -------------------------------- //
// -------------------------------------------------------------------------- //
function include(filename, callback){
	if(typeof(this.loaded) == 'undefined') this.loaded = [];
	if(typeof(this.head) == 'undefined') this.head = D.getElementsByTagName('HEAD')[0];
	if(typeof(this.userAgent) == 'undefined') this.userAgent = navigator.userAgent.toLowerCase();

	if(this.loaded[filename]){
		if(typeof(callback) == 'function'){
			callback();
		}
	}
	this.loaded[filename] = true;

	var js = D.createElement('script');
	js.setAttribute('type', 'text/javascript');
	js.setAttribute('defer', 'defer');

	if(typeof(callback) == 'function'){
		if(/msie/.test(this.userAgent) && !/opera/.test(this.userAgent)){
			js.onreadystatechange = function(){
				if(js.readyState == 'complete'){
					callback();
				}
			}
		}else{
			js.onload = function(){
				callback();
			}
		}
	}

	js.setAttribute('src', filename);
	this.head.appendChild(js);
}

// ------------------------------- end INCLUDE ------------------------------ //
// -------------------------------------------------------------------------- //






// -------------------------------------------------------------------------- //
// ------------------------ РАБОТА С ЭЛЕМЕНТАМИ ФОРМЫ ----------------------- //
// -------------------------------------------------------------------------- //

// обновляется капча на странице с формой
function recapcha(){
	$$$('capcha_pic').src = '/NEO/capcha.php?' + Math.random();
}



// отсчитывает макс. кол-во символов в элементе формы
var charsCounter = new function(){
	this.add = function(textarea, resBlock, maxLen){
		addHandler(textarea, 'keydown', function(){
			charsCounter.count(textarea, resBlock, maxLen);
		});
		addHandler(textarea, 'keyup', function(){
			charsCounter.count(textarea, resBlock, maxLen);
		});
		addHandler(textarea, 'blur', function(){
			charsCounter.count(textarea, resBlock, maxLen);
		});
		this.count(textarea, resBlock, maxLen);
	};

	this.count = function(textarea, resBlock, maxLen){
		if(textarea.value.length > maxLen) textarea.value = textarea.value.substring(0, maxLen);
		resBlock.innerHTML = maxLen - textarea.value.length;
	};
};


/**
 *
 * @param	{object}	inputR
 * @param	{object}	inputT
 */
function rangeInput(inputR, inputT, onChange){
	if(inputR.type != 'range' || inputT.type != 'text'){
		return;
	}
	inputR.parentNode.style.display = '';

	var min = typeof(inputR.min) != 'undefined' ? inputR.min : 0;
	var max = typeof(inputR.max) != 'undefined' ? inputR.max : 100;

	function valRange2Text(){
		inputT.value = inputR.value;
	}

	function valText2Range(){
		var val = parseInt(inputT.value);
		if(isNaN(val)) val = 0;
		if(val < min) val = min;
		if(val > max) val = max;
		inputR.value = val;
		valRange2Text();
	}

	if(typeof(onChange) != 'function'){
		onChange = function(){};
	}

	addHandler(inputR, 'change', function(){
		valRange2Text();
		onChange();
	});
	addHandler(inputR, 'click', function(){
		valRange2Text();
		onChange();
	});
	addHandler(inputR, 'keyup', function(){
		valRange2Text();
		onChange();
	});
	addHandler(inputR, 'mousemove', function(){
		valRange2Text();
		onChange();
	});
	addHandler(inputT, 'change', function(){
		valText2Range();
		onChange();
	});
	addHandler(inputT, 'click', function(){
		valText2Range();
		onChange();
	});
	addHandler(inputT, 'keyup', function(){
		valText2Range();
		onChange();
	});

	valText2Range();
}


// Очищает элемент SELECT и заполняет его новыми пунктами из полученной строки
// Формат строки: 'value1::label1//value2::label2//value3::label3...'
// selected - значение элемента, который нужно сделать выбранным
function setSelectOptions(obj, str, selected){
	if(!selected) selected = false;
	var selVal = false;	// Нужно ли установить значение
	while (obj.options.length) obj.options[0] = null;	// очистка списка

	if(trim(str) === '') return;

	// Разбираем и прокручиваем строку
	var lines = str.split('//');
	for(var i in lines){
		// Создаём элемент option
		var p = lines[i].split('::');
		var newOpt = new Option(p[1], p[0]);
		obj.options.add(newOpt);

		if(selected && p[0] == selected) selVal = true;	// Если значение на данной итерации совпало со значением selected
	}
	if(selVal) obj.value = selected;	// Устанавливаем значение selected
}

// определяет, какой из группы радио-элементов выбран
// radioGroupObj - это nodeList
function getRadioGroupValue(radioGroupObj){
	for(var i = 0; i < radioGroupObj.length; i++)
		if(radioGroupObj[i].type == 'radio' && radioGroupObj[i].checked)
			return radioGroupObj[i].value;

	return false;
}

// В элементе $$$(cntElementId) будет отображаться к-во пунктов, выбранных в мультиселекте $$$(selectId)
var oMultiselectCount = new function(selectId, cntElementId){
	this.count = function(selectId, cntElementId){
		var cnt = 0;
		var options = $$$(selectId).options;
		for(var i = 0, l = options.length; i < l; i++){
			if(options[i].selected){
				cnt++;
			}
		}
		$$$(cntElementId).innerHTML = cnt;
	};

	this.add = function(selectId, cntElementId){
		addHandler(
			$$$(selectId),
			'change',
			function(){
				oMultiselectCount.count(selectId, cntElementId);
			}
		);
		this.count(selectId, cntElementId);
	};
};




// ------------------------------- end ФОРМЫ -------------------------------- //
// -------------------------------------------------------------------------- //













// -------------------------------------------------------------------------- //
// --------------------- ФУНКЦИИ: аналоги PHP ------------------------------- //
// -------------------------------------------------------------------------- //

// аналог in_array()
function in_array(needle, arr){
	var res = false;
	for(var i in arr) if(arr[i] == needle) res = true;
	return res;
}

// аналог trim()
function trim(str, charlist){
	if(typeof(str) != 'string') return '';
	charlist = !charlist ? ' \\s\xA0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$$$\^\:])/g, '\$$$1');
	var re = new RegExp('^[' + charlist + ']+|[' + charlist + ']+$$$', 'g');
	return str.replace(re, '');
}

// аналог number_format()
function number_format(number, decimals, dec_point, thousands_sep){
	var i, j, kw, kd, km;
	if(isNaN(decimals = Math.abs(decimals))) decimals = 2;
	if(dec_point == undefined) dec_point = ",";
	if(thousands_sep == undefined) thousands_sep = ".";

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";
	if((j = i.length) > 3) j = j % 3; else j = 0;

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

	return km + kw + kd;
}

/**
 * Округление с нужной точностью
 * @param	{int}	val
 * @param	{int}	precision
 * @returns {number}
 */
function round(val, precision) {
	precision = precision || 0;
	return Math.round(val * Math.pow(10, precision)) / Math.pow(10, precision);
}

// укорачивает длинную строку, добавляет ... в конец
function short_str(str, max_len){
	if(str.length > max_len) str = str.substr(0, max_len) + '...';
	return str;
}


// Транслит
function translit(text){
	var rus = new Array(
		'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ',  'ъ', 'ы', 'ь', 'э', 'ю', 'я',
		'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ',  'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я');
	var eng = new Array(
		'a', 'b', 'v', 'g', 'd', 'e', 'yo','zh','z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'ts','ch','sh','sch','"', 'y', '\'','e', 'yu','ya',
		'A', 'B', 'V', 'G', 'D', 'E', 'Yo','Zh','Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'Ts','Ch','Sh','Sch','"', 'Y', '\'','E', 'Yu','Ya');

	if(text) for(i = 0; i < eng.length; i++) while(text.indexOf(rus[i]) != -1) text = text.replace(rus[i], eng[i]);
	return text;
}

// Перевод текста в корректную часть URL'а
function translitURL(text){
	text = translit(text); text = text.toLowerCase();
	var chars = 'abcdefghijklmnopqrstuvwxyz0123456789-'; var len = text.length;
	for(var i = 0; i < len; i++){
		var ch = text.charAt(i);
		if(chars.indexOf(ch) == -1) text = text.replace(ch, '-');
	}
	while(text.indexOf('--') != -1) text = text.replace('--', '-');
	return text;
}



// Случайное число
function rand(m, n){
	m = parseInt(m);
	n = parseInt(n);
	return Math.floor(Math.random() * (n - m + 1) ) + m;
}



function time(){
	return Math.floor((new Date()).getTime() / 1000);
}


// Для отладки
function print_r(o, sep, tabs1){
	if(typeof(sep) == 'undefined') sep = "\n";
	if(typeof(tabs1) == 'undefined') tabs1 = '';
	var tab = '    ';
	var tabs2 = tabs1 + tab;

	var res = '';
	for(var i in o)
		if(typeof(o[i]) == 'object')
			res += tabs2 + '[' + i + ']' + ' => ' + print_r(o[i], sep, tabs2) + sep;
		else
			res += tabs2 + '[' + i + ']' + ' => ' + o[i] + sep;

	return '{' + sep + res + tabs1 + '}';
}



// ------------------------------- end ФУНКЦИИ ---------------------------------- //
// ------------------------------------------------------------------------------ //













// -------------------------------------------------------------------------- //
// --------------------- Удобные фичи и навороты ---------------------------- //
// -------------------------------------------------------------------------- //

// Экранирование в произвольной строке всех спец. символов, действующих для рег.выражений
RegExp.escape = function(text) {
	return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
};



// Определение названия и версии браузера
// Пример: if ($is.IE) {....} или if ($is.IE>6) {....} или if ($is.Opera<9) {....}
/*(function(){
	var ua = navigator.userAgent, av = navigator.appVersion, v, i;
	alert(ua);

	$is = {};
	$is.Opera = !!(window.opera && opera.buildNumber);
	$is.WebKit = /WebKit/.test(ua);
	$is.OldWebKit = $is.WebKit && !window.getSelection().getRangeAt;
	$is.IE = !$is.WebKit && !$is.Opera && (/MSIE/gi).test(ua) && (/Explorer/gi).test(navigator.appName);
	$is.IE6 = $is.IE && /MSIE [56]/.test(ua);
	$is.IE5 = $is.IE && /MSIE [5]/.test(ua);
	$is.Gecko = !$is.WebKit && /Gecko/.test(ua);
	$is.Mac = ua.indexOf('Mac') != -1;

	for(i in $is){
		if(!$is[i]) $is[i] = NaN;
	}

	if(!$is.IE5) v = (ua.toLowerCase().match(new RegExp(".+(?:rv|it|ra|ie)[\\/: ]([\\d.]+)"))||[])[1];
	switch(true){
		case($is.WebKit):
			v = parseInt(v, 10);
			$is.WebKit = v = (v > 599) ? 4 : (v > 499) ? 3 : (v > 399) ? 2 : 1;
			break;
		case($is.Opera):
			$is.Opera = v  = v || 9;
			break;
		case($is.Gecko):
			$is.Gecko = v = v.substr(0, 3) || 1.8;
			break;
		case($is.IE):
			$is.IE = v = window.XMLHttpRequest ? 7 : (/MSIE [5]/.test(av)) ? ((/MSIE 5.5/.test(av)) ? 5.5 : 5) : 6;
	}
})();*/







/**
 * Определение названия и версии браузера
 * @return	{object}	{b: '...', v: ...}	(название и версия браузера)
 */
function browser() {
	var
		UA = window.navigator.userAgent,		// содержит переданный браузером юзерагент
		//--------------------------------------------------------------------------------
		OperaB = /Opera[ \/]+\w+\.\w+/i,		//
		OperaV = /Version[ \/]+\w+\.\w+/i,		//
		FirefoxB = /Firefox\/\w+\.\w+/i,		// шаблоны для распарсивания юзерагента
		ChromeB = /Chrome\/\w+\.\w+/i,			//
		SafariB = /Version\/\w+\.\w+/i,			//
		IEB = /MSIE *\d+\.\w+/i,				//
		SafariV = /Safari\/\w+\.\w+/i,			//
		//--------------------------------------------------------------------------------
		browser = '',
		browserSplit = /[ \/\.]/i,			// шаблон для разбивки данных о браузере из строки
		OperaV = UA.match(OperaV),
		Firefox = UA.match(FirefoxB),
		Chrome = UA.match(ChromeB),
		Safari = UA.match(SafariB),
		SafariV = UA.match(SafariV),
		IE = UA.match(IEB),
		Opera = UA.match(OperaB);


	//----- Opera ----
	if(Opera && OperaV){
		browser = OperaV[0].replace('Version', 'Opera');

	}else if(Opera){
		browser = Opera[0];

	//----- IE -----
	}else if(IE){
		browser = IE[0];

	//----- Firefox ----
	}else if(Firefox){
		browser = Firefox[0];

	//----- Chrome ----
	}else if(Chrome){
		browser = Chrome[0];

	//----- Safari ----
	}else if(Safari && SafariV){
		browser = Safari[0].replace('Version', 'Safari');
	}


	browser = browser.split(browserSplit);
	return {
		b: browser[0],
		v: parseFloat(browser[1] + '.' + browser[2])
	};
}
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
/* visual.js  */
/**
 * Определяем, каким CSS-свойством задаётся transition
 */
function transitionProp(){
	var body = D.body || D.documentElement;
	var style = body.style;
	if(style.transition !== undefined) return 'transition';
	if(style.WebkitTransition !== undefined) return 'WebkitTransition';
	if(style.MozTransition !== undefined) return 'MozTransition';
	if(style.MsTransition !== undefined) return 'MsTransition';
	if(style.OTransition !== undefined) return 'OTransition';
	return false;
}


/**
 * Проверка, поддерживает ли браурез CSS-свойство transition
 */
var TRANSITION_SUPPORT = false;
onLoad(function(){
	TRANSITION_SUPPORT = transitionProp() !== false;
});



function getPropName(prop){
	var body = D.body || D.documentElement;
	var style = body.style;

	prop = prop.toLowerCase();
	if(style[prop] !== undefined) return prop;

	prop = prop.substr(0, 1).toUpperCase() + prop.substr(1);
	if(style['Webkit' + prop] !== undefined) return 'Webkit' + prop;
	if(style['Moz' + prop] !== undefined) return 'Moz' + prop;
	if(style['Ms' + prop] !== undefined) return 'Ms' + prop;
	if(style['O' + prop] !== undefined) return 'O' + prop;
	return false;
}





// -------------------------------------------------------------------------- //
// -------------------------- РАЗМЕРЫ И КООРДИНАТЫ -------------------------- //
// -------------------------------------------------------------------------- //

/**
 * Определение координат мыши в документе
 * @param	{object}	e
 */
function mouseCoords(e) {
    e = e || window.event;
    if(e.pageX == null && e.clientX != null){
        var html = D.documentElement;
        var body = D.body;
        e.pageX = e.clientX + (html && html.scrollLeft || body && body.scrollLeft || 0) - (html.clientLeft || 0);
        e.pageY = e.clientY + (html && html.scrollTop || body && body.scrollTop || 0) - (html.clientTop || 0);
    }
    return {x:e.pageX, y:e.pageY};
}


/**
 * Размеры окна документа
 */
function winSize() {
    var w = window.innerWidth ? window.innerWidth : (document.documentElement.clientWidth ? document.documentElement.clientWidth : document.body.offsetWidth);
    var h = window.innerHeight ? window.innerHeight : (document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.offsetHeight);
    return {w:w, h:h};
}


/**
 * Вычисление размеров объекта
 * @param	{object}	el
 */
function elSize(el) {
    return {w:el.offsetWidth, h:el.offsetHeight};
}


/**
 * Вычисление координат объекта относительно всего документа
 * @param	{object}	el
 */
function elPos(el) {
    // Вычисление координат объекта суммированием offset'ов
    this.getOffsetSum = function (el) {
        var top = 0, left = 0;
        while (el) {
            top = top + parseFloat(el.offsetTop);
            left = left + parseFloat(el.offsetLeft);
            el = el.offsetParent;
        }
        return {x:Math.round(left), y:Math.round(top)};
    };

    // Вычисление координат объекта "правильным методом"
    this.getOffsetRect = function (el){
        // Получить ограничивающий прямоугольник для элемента
        var box = el.getBoundingClientRect();

        // Задать две переменных для удобства
        var body = D.body;
        var docElem = D.documentElement;

        // Вычислить прокрутку документа
        var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
        var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;

        // Документ(html или body) бывает сдвинут относительно окна (IE). Получаем этот сдвиг.
        var clientTop = docElem.clientTop || body.clientTop || 0;
        var clientLeft = docElem.clientLeft || body.clientLeft || 0;

        // Прибавляем к координатам относительно окна прокрутку и вычитаем сдвиг html/body, чтобы получить координаты относительно документа
        var top = box.top + scrollTop - clientTop;
        var left = box.left + scrollLeft - clientLeft;

        return {x:Math.round(left), y:Math.round(top)};
    };


    // Вычисляем результат подходящим способом
    if(el.getBoundingClientRect){
        // "правильный" вариант
        return this.getOffsetRect(el);
    }else{
        // пусть работает хоть как-то
        return this.getOffsetSum(el);
    }
}


/**
 * Устанавливаем style.width и style.height
 * @param	{object}	element
 * @param	{int}	w
 * @param	{int}	h
 */
function setSize(element, w, h){
	element.style.width = w + 'px';
	element.style.height = h + 'px';
}

/**
 * Устанавливаем style.left и style.top
 * @param	{object}	element
 * @param	{int}	x
 * @param	{int}	y
 */
function setPos(element, x, y){
	element.style.left = x + 'px';
	element.style.top = y + 'px';
}


/**
 * Drag & Drop
 * При перетаскивании сам элемент не двигается - это следует реализовать через калбеки onDragStart / onDrag
 * @param	{object}	el
 * @param	{function}	onDragStart		args: (e, startX, startY)
 * @param	{function}	onDrag			args: (dx, dy, currentX, currentY)
 * @param	{function}	onDragStop		args: ()
 */
function makeDraggable(el, onDragStart, onDrag, onDragStop){
	var drag, startX, startY;

	addHandler(
		el,
		'mousedown',
		function(e){
			e = e || window.event;
			eventCancelDefault(e);

			var mousePos = mouseCoords(e);
			startX = mousePos.x;
			startY = mousePos.y;
			drag = true;
			if(typeof(onDragStart) == 'function'){
				onDragStart(e, startX, startY);
			}
		}
	);
	addHandler(
		D,
		'mouseup',
		function(){
			if(!drag) return;
			drag = false;
			if(typeof(onDragStop) == 'function'){
				onDragStop();
			}
		}
	);
	addHandler(
		D,
		'mousemove',
		function(e){
			if(!drag) return;
			e = e || window.event;
			eventCancelDefault(e);

			var mousePos = mouseCoords(e);
			var dx = mousePos.x - startX;
			var dy = mousePos.y - startY;
			if(typeof(onDrag) == 'function'){
				onDrag(dx, dy, mousePos.x, mousePos.y);
			}
		}
	);
}
/*  catalog.js */
/**
 * @param	{number}	price
 * @return	{string}
 */
function priceFormat(price){
	price = number_format(Math.abs(price), 2, '.', ' ');
	price = price.replace('.00', '');
	return price
}


onLoad(function(){
	// В списке серий при наведении на серию должны подгружать картинки материалов
	var series = find('.showcase .js-element');
	if(!series.length) return;

	for(var i = 0, l = series.length; i < l; i++){
		if(typeof(series[i]) === 'undefined') continue;
		addHandler(
			series[i],
			'mouseover',
			function(){
				var materials = this.findOne('.materials');
				if(!materials) return;

				var preview = materials.dataset['preview'];
				if(!preview) return;
				materials.dataset['preview'] = '';

				var images = AJAX.jsonDecode(preview);
				var imgEl;
				var imagesLoaded = [];
				for(var k = 0, m = images.length; k < m; k++){
					if(typeof(images[k]) === 'undefined') continue;
					imgEl = D.createElement('IMG');
					addHandler(
						imgEl,
						'load',
						function(){
							imagesLoaded.push(this);
							if(imagesLoaded.length == images.length){
								materials.innerHTML = '';
								for(var n = 0, s = imagesLoaded.length; n < s; n++){
									if(typeof(imagesLoaded[n]) === 'undefined') continue;
									materials.appendChild(imagesLoaded[n]);
								}
							}
						}
					);
					imgEl.width = '20';
					imgEl.height = '20';
					imgEl.alt = images[k]['name'];
					imgEl.title = images[k]['name'];
					imgEl.src = images[k]['img'];
				}
			}
		);
	}
});


/**
 * Рассчёт и отображение сумарной цены комплекта серии
 */
function seriesSetPrice(){
	var setBlock = $$$('series-set'); if(!setBlock) return;
	var prices = byTag('SPAN', setBlock);
	var iId, price, priceOld, priceIn, amount;
	var total = 0, totalOld = 0, totalIn = 0;
	for(var i = 0, l = prices.length; i < l; i++){
		if(prices[i].id.indexOf('item2-') === 0 && prices[i].id.indexOf('-price') !== -1 && prices[i].id.indexOf('-price-') === -1){
			iId = prices[i].id.replace('item2-', '').replace('-price', '');

			price = parseFloat(prices[i].innerHTML.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', ''));
			if(isNaN(price)) price = 0;

			if($$$('item2-' + iId + '-price-old')){
				priceOld = parseFloat($$$('item2-' + iId + '-price-old').innerHTML.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', ''));
				if(isNaN(priceOld)) priceOld = 0;
			}else{
				priceOld = 0;
			}

			if($$$('item2-' + iId + '-price-in')){
				priceIn = parseFloat($$$('item2-' + iId + '-price-in').innerHTML.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', ''));
				if(isNaN(priceIn)) priceIn = 0;
			}else{
				priceIn = 0;
			}

			amount = parseInt($$$('item2-' + iId + '-amount').value);
			if(isNaN(amount)) amount = 0;

			total += price * amount;

			if(!priceOld){
				priceOld = price;
			}
			totalOld += priceOld * amount;
			totalIn += priceIn * amount;
		}
	}
	if(total){
		$$$('series-set-price').innerHTML = priceFormat(total);
		$$$('series-set-price').className = '';
	}else{
		$$$('series-set-price').innerHTML = 'Цена по запросу';
		$$$('series-set-price').className = 'price-by-request';
	}
	if(totalOld != total){
		$$$('series-set-price-old').style.display = '';
		$$$('series-set-price-old').innerHTML = priceFormat(totalOld);
	}else{
		$$$('series-set-price-old').style.display = 'none';
		$$$('series-set-price-old').innerHTML = '0';
	}

	if($$$('series-set-price-in')){
		$$$('series-set-price-in').innerHTML = priceFormat(totalIn);
	}

	oCart.setInfo();
}


/* Зум картинок материалов серии */
onLoad(function(){
	$(document).on('click', '.series-materials a.level1', function(){
		$.fancybox.open(
			this.nextElementSibling.innerHTML,
			{
				type			: 'inline',
				autoSize		: true,
				padding			: 20,
				tpl				: {
					closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><i class="icon text-red-600 hover:text-red-800 !text-4xl leading-none no-underline hover:no-underline">×</i></a>'
				}
			}
		);
		return false;
	});
});


/**
 * @param 	{int}		itemId
 * @param	{boolean}	hoverOn
 */
function seriesSetHoverTable(itemId, hoverOn){
	var point = $$$('series-set-point-' + itemId);
	if(!point) return;
	if(hoverOn){
		point.className = 'hover';
	}else{
		point.className = '';
	}
}


/**
 * @param 	{int}		itemId
 * @param	{boolean}	hoverOn
 */
function seriesSetHoverPoint(itemId, hoverOn){
	var row = $$$('series-set-row-' + itemId);
	if(!row) return;
	if(hoverOn){
		row.className = 'hover';
	}else{
		row.className = '';
	}
}





/**
 * Показать/Скрыть полное описание серии
 */
function showHideDescription(){
	var text = $$$('series-text');
	var lnk = $$$('series-text-lnk');
	if(text.className.indexOf('text-opened') !== -1){
		// Hide
		text.className = text.className.replace('text-opened', 'text-closed');
		lnk.className = lnk.className.replace('opened', 'closed');

	}else{
		// Show
		text.className = text.className.replace('text-closed', 'text-opened');
		lnk.className = lnk.className.replace('closed', 'opened');
	}
}
onLoad(function(){
	var text = $$$('series-text');
	var textIn = $$$('series-text-in');
	if(!text || !textIn) return;
	if(text.offsetHeight >= textIn.offsetHeight){
		//text.className = text.className.replace('text-opened', 'text-closed');
		text.className = text.className.replace('text-closed', 'text-opened');
		$$$('series-text-lnk').style.display = 'none';
	}
});

/**
 * Показываем попап с товаром при клике на него в перечне всех элементов серии
 * @param url
 * @param itemId
 */
function showItemPopup1(url, itemId){
	url += url.indexOf('?') == -1 ? '?' : '&';
	url += 'ajax=1';
	//console.log(url);

	oPopup.loadUrl(
		'', url, false,
		function(){
			oMaterials.refresh();
			oMaterials.onChangePageSize = function(subOpened){
				if(subOpened){
					findOne('.fancybox-inner').style.height = (winSize().h - 130) + 'px';
				}else{
					findOne('.fancybox-inner').style.height = 'auto';
				}
				$.fancybox.reposition();
			};
			oMaterials.onSave = function(price, material){
				// Отображаем цену с выбранным материалом
				if(price.current > 0){
					$$$('item1-' + itemId + '-price').className = '';
					$$$('item1-' + itemId + '-price').innerHTML = priceFormat(price.current);
					if(price.old){
						$$$('item1-' + itemId + '-price-old').style.display = '';
						$$$('item1-' + itemId + '-price-old').innerHTML = priceFormat(price.old);
					}else{
						$$$('item1-' + itemId + '-price-old').style.display = 'none';
						$$$('item1-' + itemId + '-price-old').innerHTML = '0';
					}
					if($$$('item1-' + itemId + '-price-in')){
						$$$('item1-' + itemId + '-price-in').style.display = '';
						$$$('item1-' + itemId + '-price-in').innerHTML = priceFormat(price.in);
					}
				}else{
					$$$('item1-' + itemId + '-price').innerHTML = 'по запросу';
					$$$('item1-' + itemId + '-price-old').innerHTML = '0';

					$$$('item1-' + itemId + '-price').className = 'price-by-request';
					$$$('item1-' + itemId + '-price-old').style.display = 'none';
					if($$$('item1-' + itemId + '-price-in')){
						$$$('item1-' + itemId + '-price-in').style.display = 'none';
					}
				}

				// Записываем ID материала в соответствующее поле
				$$$('item1-' + itemId + '-material').value = material.id;

				// Выделяем выбранный материал в полоске с их превьюшками
				var matsPreviewsBlock = $$$('item1-' + itemId + '-materials-preview');
				var matsPreviews = byTag('DIV', matsPreviewsBlock);
				var previewNum = 1;

				for(var i = 0, l = matsPreviews.length; i < l; i++){
					if(matsPreviews[i].id == 'item1-' + itemId + '-m-' + material.topId){
						matsPreviews[i].className = 'active';
						previewNum = i + 1;
					}else{
						matsPreviews[i].className = '';
					}
				}
				matsPreviewsBlock.className = 'materials pos-' + Math.ceil(previewNum/7);
			};
			oMaterials.open(
				itemId,
				$$$('item1-' + itemId + '-material').value
			);

			//oPopup.fixSize();
			//oPopup.fixPos();
		}
	);
}


/**
 * Показываем попап с товаром при клике на него в списке товаров, входящих в комплект серии
 * @param url
 * @param itemId
 */
function showItemPopup2(url, itemId){

	url += url.indexOf('?') == -1 ? '?' : '&';
	url += 'ajax=1';
	oPopup.loadUrl(
		'', url, false,
		function(){
			oMaterials.refresh();
			oMaterials.onChangePageSize = function(subOpened){
				if(subOpened){
					findOne('.fancybox-inner').style.height = (winSize().h - 130) + 'px';
				}else{
					findOne('.fancybox-inner').style.height = 'auto';
				}
				$.fancybox.reposition();
			};
			oMaterials.onSave = function(price, material){
				// Отображаем цену с выбранным материалом
				if(price.current){
					$$$('item2-' + itemId + '-price').className = '';
					$$$('item2-' + itemId + '-price').innerHTML = priceFormat(price.current);
					if(price.old){
						$$$('item2-' + itemId + '-price-old').style.display = '';
						$$$('item2-' + itemId + '-price-old').innerHTML = priceFormat(price.old);
					}else{
						$$$('item2-' + itemId + '-price-old').style.display = 'none';
						$$$('item2-' + itemId + '-price-old').innerHTML = '0';
					}
					if($$$('item2-' + itemId + '-price-in')){
						$$$('item2-' + itemId + '-price-old').style.display = '';
						$$$('item2-' + itemId + '-price-in').innerHTML = priceFormat(price.in);
					}
				}else{
					$$$('item2-' + itemId + '-price').innerHTML = 'по запросу';
					$$$('item2-' + itemId + '-price-old').innerHTML = '0';

					$$$('item2-' + itemId + '-price').className = 'price-by-request';
					$$$('item2-' + itemId + '-price-old').style.display = 'none';
					if($$$('item2-' + itemId + '-price-in')){
						$$$('item2-' + itemId + '-price-old').style.display = 'none';
					}
				}

				// Записываем ID материала в соответствующее поле
				$$$('item2-' + itemId + '-material').value = material.id;

				// Отображаем картинку и название выбранного материала
				var matImageBlock = $$$('item2-' + itemId + '-matimage');
				if(matImageBlock){
					if(material.image){
						matImageBlock.title = material.name;
						matImageBlock.innerHTML = '<img src="/Uploads/Material/' + material.image.id + '_21x21_0.' + material.image.ext + '" width="21" height="21" alt="">';
					}else{
						matImageBlock.title = '';
						matImageBlock.innerHTML = material.name;
					}
				}

				seriesSetPrice();
			};
			oMaterials.open(
				itemId,
				$$$('item2-' + itemId + '-material').value
			);

			//oPopup.fixSize();
			//oPopup.fixPos();
		}
	);
}

function handleOnclick(e){
	e.stopImmediatePropagation();
    return false;
}
/* materials.js  */
/**
 * Выбор материала при покупке товара
 */
var oMaterials = new function(){
	/**
	 * {object}
	 */
	this.tree = {};

	/**
	 * Ключи - ID товаров, значения - массивы объектов (связанных с товаром материалов)
	 * {object}
	 */
	this.items2mats = {};

	/**
	 * Функция, кот. будет вызываться при изменении размеров занимаемого блоком материалов пространства
	 * @type {function}
	 */
	this.onChangePageSize = function(subOpened){};

	/**
	 * Функция, кот. будет вызываться при выборе материала
	 * @type {function}
	 */
	this.onSave = function(){};

	/**
	 * Макс к-во блоков материалов в горизонтальном ряду в попапе
	 */
	var matsInRowLevel1 = 3;
	var matsInRowLevel2 = 3;
	var matsInRowLevel3 = 3;
	/*if(mobile){
		matsInRowLevel1 = 4;
		matsInRowLevel2 = 4;
		matsInRowLevel3 = 4;
	}*/

	/**
	 * {object}
	 */
	var mat2parent = {};

	/**
	 * {int}
	 */
	var openedItemId = 0;

	/**
	 * {int}
	 */
	var selectedMatId = 0;

	/**
	 *
	 */
	var itemActiveMats = [];

	/**
	 *
	 */
	var elPopup = false, elPopupContent = false;


	this.setRowSize = function(level, rowSize){
		if(level == 1){
			matsInRowLevel1 = rowSize;
		}else if(level == 2){
			matsInRowLevel2 = rowSize;
		}else if(level == 3){
			matsInRowLevel3 = rowSize;
		}
	};


	this.refresh = function(){
		openedItemId = 0;
		selectedMatId = 0;
		itemActiveMats = [];
		elPopup = false;
		elPopupContent = false;
	};


	function init(){

		var nojsBlock = byTag('DIV');
		var i, l, j, k;

		$('.material-nojs').hide();

		elPopup = $$$('materials-popup'); if(!elPopup) return;
		elPopupContent = $$$('materials-popup-content');

		oMaterials.onChangePageSize(false);
	}

	onLoad(function(){
		init();

		/**
		 * Для всех материалов в дереве находим их родительский материал и собираем хеш (id материала => id родителя)
		 */
		function p(parentMatId, materials){
			for(var matId in materials){
				mat2parent[matId] = parentMatId;
				if(materials[matId].has_sub){
					p(matId, materials[matId].sub);
				}
			}
		}
		p(0, oMaterials.tree);
	});


	/**
	 * Открыть попап для выбора материала
	 * @param	{int}	itemId
	 * @param	{int}	matId
	 */
	this.open = function(itemId, matId){

		if(typeof(this.items2mats[itemId]) === 'undefined') {
            return;
        }

		openedItemId = itemId;

		if(!elPopup){
			init();
			if(!elPopup){
				return;
			}
		}

		elPopup.style.display = 'block';

		var i, l;

		// Вычисляем активный для данного товара материал и цепочку его родителей
		if(typeof(matId) == 'undefined' || !matId || matId == '0'){
			selectedMatId = $$$('item-' + openedItemId + '-material').value;
			showSelectMaterial();

		}else{
			selectedMatId = matId;
			$$$('item-' + openedItemId + '-material').value = matId;
		}

		itemActiveMats = [selectedMatId];
		var m = selectedMatId;
		while(mat2parent[m] != 0){
			m = mat2parent[m];
			itemActiveMats.push(m);
		}

		// Цена
		displayMatPrice(selectedMatId);

		// Заполняем попап материалами верхнего уровня
		var mIds = [];

		for(i = 0, l = this.items2mats[openedItemId].length; i < l; i++){
			mIds.push(this.items2mats[openedItemId][i].material_id);
		}
		elPopupContent.innerHTML = materialsHtml(mIds, 1);

        // количество элементов (материалов) у позиции
        const el = document.getElementById("materials-popup-content");
        el.dataset.counts = mIds.length;

		this.onChangePageSize(false);
	};

    this.sortSubByOrder2 = function(obj) {
        // Создаем глубокую копию объекта
        const newObj = JSON.parse(JSON.stringify(obj));

        const subEntries = Object.entries(newObj.sub)
            .sort((a, b) => parseInt(a[1].order, 10) - parseInt(b[1].order, 10));

        newObj.sub = Object.fromEntries(subEntries);
        return newObj;
    }

    this.sortSubByOrder = function(obj) {
        // Получаем массив элементов sub
        const subEntries = Object.entries(obj.sub);

        // Сортируем по полю order (преобразуем в число для правильной сортировки)
        subEntries.sort((a, b) => {
            const orderA = parseInt(a[1].order, 10);
            const orderB = parseInt(b[1].order, 10);
            return orderA - orderB;
        });

        // Создаем новый отсортированный объект sub
        const sortedSub = {};
        subEntries.forEach(([key, value]) => {
            sortedSub['A'+key] = value;
        });

        // Возвращаем новый объект с отсортированным sub
        return {
            ...obj,
            sub: sortedSub
        };
    }

	/**
	 * Открываем список подматериалов 2-го уровня
	 * @param	{int}	mId
	 */
	this.openLevel2 = function(mId){
		// Отображаем плашку для 2-го уровня материалов
		var elLevel2 = $$$('materials-level2'); if(!elLevel2) return;
		elLevel2.style.display = '';

		// Перемещаем плашку так, чтобы она шла сразу под текущим рядом материалов (рядом, в кот. нах-ся кликнутый материал)
		var elMaterial = $$$('material-' + mId);
		var elRow = elMaterial.parentNode;
		while(elRow.className.indexOf('materials-row') === -1) {
			elRow = elRow.parentNode;
		}

		elRow.parentNode.insertBefore(elLevel2, elRow.nextSibling);

		// Подсвечиваем кликнутый материал
		highlightMaterial(mId);

		// Отображаем в плашке название кликнутого материала
		var m = matById(mId).material;
		$$$('materials-level2-title').innerHTML = m.name;

		// Перемещаем хвостик плашки
		$$$('materials-level2-tail').style.left = (elPos(elMaterial).x - elPos(elRow).x) + 'px';

        // -----
        const sortedData = this.sortSubByOrder(this.tree[mId])
        //console.log(sortedData);
        // -----

		// Отображаем материалы 2-го уровня
		var mIds = [];
		for(var i in sortedData.sub){
			mIds.push(sortedData.sub[i].id);
		}

        $$$('materials-level2-content').innerHTML = materialsHtml(mIds, 2, false);

		this.onChangePageSize(true);

		scrollToOpenedBLock(elLevel2);
	};

	/**
	 *
	 */
	this.closeLevel2 = function(){
		var elLevel2 = $$$('materials-level2'); if(!elLevel2) return;
		elLevel2.style.display = 'none';

		this.onChangePageSize(false);
	};


	/**
	 * Открываем список подматериалов 3-го уровня
	 * @param	{int}	mId
	 */
	this.openLevel3 = function(mId){
		// Отображаем плашку для 3-го уровня материалов
		var elLevel3 = $$$('materials-level3'); if(!elLevel3) return;
		elLevel3.style.display = '';

		// Перемещаем плашку так, чтобы она шла сразу под текущим рядом материалов (рядом, в кот. нах-ся кликнутый материал)
		var elMaterial = $$$('material-' + mId);
		var elRow = elMaterial.parentNode;
		while(elRow.className.indexOf('materials-row') === -1){
			elRow = elRow.parentNode;
		}
		elRow.parentNode.insertBefore(elLevel3, elRow.nextSibling);

		// Подсвечиваем кликнутый материал
		highlightMaterial(mId);

		// Отображаем в плашке название кликнутого материала
		var m = matById(mId).material;
		$$$('materials-level3-title').innerHTML = m.name;

		// Перемещаем хвостик плашки
		$$$('materials-level3-tail').style.left = (elPos(elMaterial).x - elPos(elRow).x) + 'px';

		// Отображаем материалы 2-го уровня
		var p = mat2parent[mId];
		var mIds = [];
		for(var i in this.tree[p].sub[mId].sub){
			mIds.push(this.tree[p].sub[mId].sub[i].id);
		}
		$$$('materials-level3-content').innerHTML = materialsHtml(mIds, 3);

		this.onChangePageSize(true);

		scrollToOpenedBLock(elLevel3);
	};


	this.closeLevel3 = function(){
		var elLevel3 = $$$('materials-level3'); if(!elLevel3) return;
		elLevel3.style.display = 'none';

		this.onChangePageSize(true);
	};


	/**
	 * @param	{object}	levelBlock
	 */
	function scrollToOpenedBLock(levelBlock){
		return;
		var topSpace = 300;
		var bSize = elSize(levelBlock).h;
		var bPos = elPos(levelBlock).y;
		var scrollNow = D.body.scrollTop ? D.body.scrollTop : D.documentElement.scrollTop;
		var wSize = winSize().h;

		var scrollTo = 0;

		if(bPos > (scrollNow+topSpace) && (bPos+bSize) < (scrollNow+wSize)){
			// Блок уже полностью виден в окне
			return;
		}else if(bSize < (wSize-topSpace)){
			// Блок небольшой и может полностью уместиться в окно
			scrollTo = bPos - (wSize - bSize) + 50;
		}else{
			// Блок большой, показываем его верхнюю часть
			scrollTo = bPos - topSpace;
		}

		if(scrollTo){
			D.body.scrollTop = scrollTo;
			D.documentElement.scrollTop = scrollTo;
		}
	}


	/**
	 * Сохранить выбор и закрыть попап
	 */
	this.save = function(){
		if(!openedItemId) return;

		var matInfo = showSelectMaterial();

		// Цепочка материалов от выбранного до верхнего уровня
		itemActiveMats = [selectedMatId];
		var m = selectedMatId;
		while(mat2parent[m] != 0){
			m = mat2parent[m];
			itemActiveMats.push(m);
		}

		//if(!mobile){
			// Скролим станицу, чтобы был виден выбранный материал
			/*var p = elPos(matInfo.resultImage).y - 350;
			D.body.scrollTop = p;
			D.documentElement.scrollTop = p;*/
		//}

		// Вызываем калбеки
		var topM = matById(selectedMatId).top;
		//this.onChangePageSize();
		this.onSave(
			{	// price
				current:matPrice4Item(topM.id),
				old:	matPriceOld4Item(topM.id),
				in:		matPriceIn4Item(topM.id)
			},
			{	// material
				id:		selectedMatId,
				name:	matInfo.name,
				image:	matInfo.image,
				topId:	topM.id
			}
		);
	};


	/**
	 *
	 */
	function showSelectMaterial(){
		if(!openedItemId) return {
			resultImage: false,
			name: '',
			image: false
		};
		var mat = matById(selectedMatId).material;

		var resultImage = $$$('item-' + openedItemId + '-image');
		var resultName = $$$('item-' + openedItemId + '-name');
		var resultMaterialVal = $$$('item-' + openedItemId + '-material');

		// Вычисляем картинку выбранного материала
		var image;
		if(mat.image.ext){
			image = mat.image;
			resultImage.innerHTML = '<img src="/Uploads/Material/' + image.id + '_76x61_0.' + image.ext + '" width="76" height="61" alt="">';
		}else{
			image = false;
			resultImage.innerHTML = '';
		}

		// Название
		var name = [mat.name];
		var tmpId = mat.id;
		while(mat2parent[tmpId] != 0){
			tmpId = mat2parent[tmpId];

			name.push(matById(tmpId).material.name);
		}
		name.reverse();
		name = name.join(' / ');
		resultName.innerHTML = name;

		// Цена
		displayMatPrice(selectedMatId);

		// Скрытый <input>
		resultMaterialVal.value = selectedMatId;

		oCart.setInfo();

		return {
			resultImage: resultImage,
			name: name,
			image: image
		};
	}


  /**
   * @param {number} matId
   */
  function displayMatPrice(matId) {
    const topM = matById(matId).top;
    const price = matPrice4Item(topM.id);
    const priceEl = $$$("item-" + openedItemId + "-price");
    const priceOldEl = $$$("item-" + openedItemId + "-price-old");
    const priceInEl = $$$("item-" + openedItemId + "-price-in");

    if (price > 0) {
      // Убираем класс "price-by-request", если есть
      priceEl.classList.remove("price-by-request");
      // Устанавливаем текст цены + символ рубля
      priceEl.innerHTML = priceFormat(price) + " ₽";

      // Показываем и обновляем старую цену, если элемент существует
      if (priceOldEl) {
        priceOldEl.style.display = "";
        priceOldEl.innerHTML = priceFormat(matPriceOld4Item(topM.id)) + " ₽";
      }

      // Показываем и обновляем цену "в наличии", если элемент существует
      if (priceInEl) {
        priceInEl.style.display = "";
        priceInEl.innerHTML = priceFormat(matPriceIn4Item(topM.id)) + " ₽";
      }
    } else {
      // Добавляем класс "price-by-request", не трогая другие классы
      priceEl.classList.add("price-by-request");
      priceEl.innerHTML = "Цена по запросу"; // Без ₽

      // Скрываем старую цену и цену "в наличии", если элементы существуют
      if (priceOldEl) {
        priceOldEl.style.display = "none";
      }
      if (priceInEl) {
        priceInEl.style.display = "none";
      }
    }
  }


	/**
	 * Выбор материала
	 * @param	{int}	mId
	 */
	this.selectMaterial = function(mId){
		highlightMaterial(mId);

		this.closeLevel3();
		this.closeLevel2();

		// Запоминаем, какой материал был выбран для товара
		selectedMatId = mId;

		// Сохраняем выбор и закрываем попап
		this.save();
	};


	this.openrows = function(){
        if ($('.item-materials').hasClass('kopen')) {
            $('.item-materials').removeClass('kopen');
            $('.dis').hide();
            $('.klink-dashed').text('Показать все');
        } else {
            $('.dis').show();
            $('.item-materials').addClass('kopen');
            $('.klink-dashed').text('Свернуть');
        }
	};

	/**
	 * Подсвечиваем (визуально выделяем) блок с заданным материалом
	 * @param	{int}	mId
	 */
	function highlightMaterial(mId){
		var m = $$$('material-' + mId);

		// Снимаем визуальное выделение со всех материалов на том же уровне
		var levelBlock = m.parentNode;
		while(levelBlock.className.indexOf('materials-level') === -1){
			levelBlock = levelBlock.parentNode;
		}
		var mats = byTag('A', levelBlock);
		for(var i = 0, l = mats.length; i < l; i++){
			if(mats[i].className == 'material' || mats[i].className.indexOf('material ') !== -1){
				mats[i].className = mats[i].className.replace(' active', '');
			}
		}

		// Выделяем выбранный материал
		m.className += ' active';
	}


	/**
	 * Получаем цену на товар с заданным материалом
	 * @param	{int}	mId
	 * @return	{int}
	 */
	function matPrice4Item(mId){
		for(var i = 0, l = oMaterials.items2mats[openedItemId].length; i < l; i++){
			if(oMaterials.items2mats[openedItemId][i].material_id == mId){
				return oMaterials.items2mats[openedItemId][i].price;
			}
		}
		return 0;
	}


	/**
	 * Получаем старую цену (без скидки) на товар с заданным материалом
	 * @param	{int}	mId
	 * @return	{int}
	 */
	function matPriceOld4Item(mId){
		for(var i = 0, l = oMaterials.items2mats[openedItemId].length; i < l; i++){
			if(oMaterials.items2mats[openedItemId][i].material_id == mId){
				return oMaterials.items2mats[openedItemId][i]['price-old'];
			}
		}
		return 0;
	}


	/**
	 * Получаем входную цену (для дилеров) на товар с заданным материалом
	 * @param	{int}	mId
	 * @return	{int}
	 */
	function matPriceIn4Item(mId){
		for(var i = 0, l = oMaterials.items2mats[openedItemId].length; i < l; i++){
			if(oMaterials.items2mats[openedItemId][i].material_id == mId){
				return oMaterials.items2mats[openedItemId][i]['price-in'];
			}
		}
		return 0;
	}


	/**
	 * Получаем объект материала из дерева по ID
	 * @param	{int}	mId
	 * @return	{object}		{material: {object}, top: {object}, level: {int}}
	 */
	function matById(mId){
		var mIdTmp = mId;
		var ids = [mIdTmp];

		if(typeof(mat2parent[mIdTmp]) != 'undefined'){
		while(mat2parent[mIdTmp] != 0){
			mIdTmp = mat2parent[mIdTmp];
			ids.push(mIdTmp);
		}
		ids.reverse();

		var idsIter = 0;
		var mat = oMaterials.tree[ids[idsIter]];
		while(mat.id != mId){
			idsIter++;
			mat = mat.sub[ids[idsIter]];
		}
		return {
			material: mat,
			top: oMaterials.tree[ids[0]],
			level: ids.length
		};
		}
	}


	/**
	 * Получаем HTML-код для отображения множества материалов в попапе
	 * Материалы группируются в строки <div class="materials-row"></div> по 5 шт. в каждой
	 * @param	{object}	mIds	Массив ID материалов
	 * @param	{int}		level	Уровень вложенности этих материалов
	 * @param	{boolean}	hidden	Скрывать или нет
	 */
	function materialsHtml(mIds, level, hidden = true){
		var matsInRow = 7;
		if(level == 1){
			matsInRow = matsInRowLevel1;
		}else if(level == 2){
			matsInRow = matsInRowLevel2;
		}else if(level == 3){
			matsInRow = matsInRowLevel3;
		}

		if(mIds.length < matsInRow*2){
            const el = document.getElementById("materials-popup-content");
            if ( Number(el.dataset.counts) <=6 ) {
                $('.klink-dashed').hide();
            }
		}

		var html = '', rowHtml = '', rclass = '';

		for(var i = 0, l = mIds.length; i < l; i++)
        {
			if(i > matsInRow*2 && mIds.length > matsInRow*2) {
                // скрывает позицию
                //rclass = hidden ? 'dis' : '';
                // обновление: если уровень больше или равен второму уровню вложенности, то ничего не скрываем
                rclass = level < 2 ? 'dis' : '';
            }

			if( i != 0 && i % matsInRow == 0 ) {
				html += '<div class="materials-row '+rclass+'">' + rowHtml + '<div class="cl"></div></div>';
				rowHtml = '';
			}
			rowHtml += oneMaterialHtml(mIds[i]);
		}

        // последний элемент должен быть скрыт, если кол-во элементов больше кол-ва строк
		if( mIds.length > matsInRow*2) {
            rclass = hidden ? 'dis' : '';
        }

		html += '<div class="materials-row '+rclass+'">' + rowHtml + '<div class="cl"></div></div>';

		if(level === 1){
			html +=
				'<div class="materials-level2" id="materials-level2" style="display: none">' +
					'<div class="level-tail" id="materials-level2-tail"></div>' +
					'<a class="close" href="javascript:void(0)" onclick="oMaterials.closeLevel2(); return false;"></a>' +
					'<strong class="level-title" id="materials-level2-title"></strong>' +
					'<div class="level-help">Нажмите на изображение материала, чтобы выбрать его</div>' +
					'<div id="materials-level2-content">' +
					'</div>' +
				'</div>';
		}else{
			html +=
				'<div class="materials-level3" id="materials-level3" style="display: none">' +
					'<div class="level-tail" id="materials-level3-tail"></div>' +
					'<a class="close" href="javascript:void(0)" onclick="oMaterials.closeLevel3(); return false;"></a>' +
					'<strong class="level-title" id="materials-level3-title"></strong>' +
					'<div id="materials-level3-content">' +
					'</div>' +
				'</div>';
		}

		return html;
	}

	/**
	 * Получаем HTML-код для отображения одного материала в попапе
	 * @param	{int}	mId
	 * @return	{string}
	 */
  function oneMaterialHtml(mId) {
    var tmp = matById(mId);
    var m = tmp.material;
    var topM = tmp.top;
    var level = tmp.level;

    var onclick = "";
    var className = "material";
    if (m.has_sub == 1) {
      className += " has-sub";
      if (level == 1) {
        onclick = "oMaterials.openLevel2(" + mId + ")";
      } else {
        onclick = "oMaterials.openLevel3(" + mId + ")";
      }
    } else {
      onclick = "oMaterials.selectMaterial(" + mId + ")";
    }

    var active = false;
    for (var i = 0, l = itemActiveMats.length; i < l; i++) {
      if (itemActiveMats[i] == mId) {
        active = true;
        break;
      }
    }
    if (active) {
      className += " active";
    }

    var image = "";
    var imageBig = "";
    if (m.image.ext) {
      image =
        '<img src="/Uploads/Material/' +
        m.image.id +
        "_76x61_0." +
        m.image.ext +
        '" width="76" height="61" alt="">';
      imageBig =
        '<img src="/Uploads/Material/' +
        m.image.id +
        "_164x132_0." +
        m.image.ext +
        '" width="164" height="132" alt="">';
    }

    // 🔥 Исправлено: " р." → " ₽"
    var price = matPrice4Item(topM.id);
    price = price > 0 ? priceFormat(price) + " ₽" : "по запросу";

    var html =
      '<a id="material-' +
      m.id +
      '" class="' +
      className +
      '" href="javascript:void(0)" onclick="' +
      onclick +
      '; return false;">' +
      '<div class="material-in">' +
      '<div class="image">' +
      image +
      (imageBig ? '<div class="image-big">' + imageBig + "</div>" : "") +
      "</div>" +
      '<div class="info material-info">' +
      '<div class="name">' +
      m.name +
      "</div>" +
      '<div class="price">' +
      price +
      "</div>" +
      "</div>" +
      "</div>" +
      "</a>";
    return html;
  }
};
/*  catalog/compare.js */
/**
 * Сравнение серий
 */
/**
 * Сравнение серий
 */
/**
 * Избранное
 */
var oFavorite = new function() {
  /**
   * {string}
   */
  this.url = '/pinitk85/catalog-favorite';

  /**
   * @param  {int}  seriesId
   */
  this.add = function (seriesId) {
    // Находим все кнопки "Добавить" для этого товара
    var addButtons = document.querySelectorAll('.series-favorite-add[data-id="' + seriesId + '"]');
    var removeButtons = document.querySelectorAll('.series-favorite-remove[data-id="' + seriesId + '"]');

    // Скрываем "Добавить", показываем "Убрать" через классы вместо прямого изменения стилей
    addButtons.forEach(function (btn) {
      btn.classList.add('hidden');
    });
    removeButtons.forEach(function (btn) {
      btn.classList.remove('hidden');
    });

    // Отправляем AJAX
    AJAX.lookup(
      this.url + '?add=' + seriesId + '&ajax=1',
      function (cnt) {
        // Обновление счётчиков
        $$$('series-favorite-cnt').innerHTML = cnt;
        var spans = find('span.series-favorite-cnt');
        for (var i = 0, l = spans.length; i < l; i++) {
          if (typeof spans[i] !== 'undefined') spans[i].innerHTML = cnt;
        }

        // Обновление бейджей в header
        var desktopBadge = $$$('favorite-badge-count-desktop');
        var mobileBadge = $$$('favorite-badge-count-mobile');
        if (desktopBadge) {
          desktopBadge.innerHTML = cnt;
        }
        if (mobileBadge) {
          mobileBadge.innerHTML = cnt;
        }
      }
    );
  };

  /**
   * @param  {int}  seriesId
   */
  this.remove = function (seriesId) {
    // Находим все кнопки "Убрать" для этого товара
    var addButtons = document.querySelectorAll('.series-favorite-add[data-id="' + seriesId + '"]');
    var removeButtons = document.querySelectorAll('.series-favorite-remove[data-id="' + seriesId + '"]');

    // Показываем "Добавить", скрываем "Убрать" через классы вместо прямого изменения стилей
    addButtons.forEach(function (btn) {
      btn.classList.remove('hidden');
    });
    removeButtons.forEach(function (btn) {
      btn.classList.add('hidden');
    });

    // Отправляем AJAX
    AJAX.lookup(
      this.url + '?remove=' + seriesId + '&ajax=1',
      function (cnt) {
        // Обновление счётчиков
        $$$('series-favorite-cnt').innerHTML = cnt;
        var spans = find('span.series-favorite-cnt');
        for (var i = 0, l = spans.length; i < l; i++) {
          if (typeof spans[i] !== 'undefined') spans[i].innerHTML = cnt;
        }

        // Обновление бейджей в header
        var desktopBadge = $$$('favorite-badge-count-desktop');
        var mobileBadge = $$$('favorite-badge-count-mobile');
        if (desktopBadge) {
          desktopBadge.innerHTML = cnt;
        }
        if (mobileBadge) {
          mobileBadge.innerHTML = cnt;
        }
      }
    );
  };
};

/**
 * Сравнение серий
 */
var oCompare = new function() {
  /**
   * {string}
   */
  this.url = '';


  /**
   * @param  {int}  seriesId
   */
  this.add = function (seriesId) {
    // Находим все кнопки "Добавить" для этого товара
    var addButtons = document.querySelectorAll('.series-compare-add[data-id="' + seriesId + '"]');
    var removeButtons = document.querySelectorAll('.series-compare-remove[data-id="' + seriesId + '"]');

    // Скрываем "Добавить", показываем "Убрать" через классы вместо прямого изменения стилей
    addButtons.forEach(function (btn) {
      btn.classList.add('hidden');
    });
    removeButtons.forEach(function (btn) {
      btn.classList.remove('hidden');
    });

    // Отправляем AJAX
    AJAX.lookup(
      this.url + '?add=' + seriesId + '&ajax=1',
      function (cnt) {
        // Обновление счётчиков (оставьте как есть)
        $$$('series-compare-cnt').innerHTML = cnt;
        var spans = find('span.series-compare-cnt');
        for (var i = 0, l = spans.length; i < l; i++) {
          if (typeof spans[i] !== 'undefined') spans[i].innerHTML = cnt;
        }

        // Обновление бейджей в header (оставьте как есть)
        var desktopBadge = $$$('compare-badge-count-desktop');
        var mobileBadge = $$$('compare-badge-count-mobile');
        if (desktopBadge) {
          desktopBadge.innerHTML = cnt;
          cnt > 0 ? desktopBadge.classList.remove('hidden') : desktopBadge.classList.add('hidden');
        }
        if (mobileBadge) {
          mobileBadge.innerHTML = cnt;
          cnt > 0 ? mobileBadge.classList.remove('hidden') : mobileBadge.classList.add('hidden');
        }
      }
    );
  };

  /**
   * @param  {int}  seriesId
   */
  this.remove = function (seriesId) {
    var addButtons = document.querySelectorAll('.series-compare-add[data-id="' + seriesId + '"]');
    var removeButtons = document.querySelectorAll('.series-compare-remove[data-id="' + seriesId + '"]');

    // Скрываем "Убрать", показываем "Добавить" через классы вместо прямого изменения стилей
    removeButtons.forEach(function (btn) {
      btn.classList.add('hidden');
    });
    addButtons.forEach(function (btn) {
      btn.classList.remove('hidden');
    });

    // Отправляем AJAX
    AJAX.lookup(
      this.url + '?remove=' + seriesId + '&ajax=1',
      function (cnt) {
        // Аналогичное обновление счётчиков (как в add)
        $$$('series-compare-cnt').innerHTML = cnt;
        var spans = find('span.series-compare-cnt');
        for (var i = 0, l = spans.length; i < l; i++) {
          if (typeof spans[i] !== 'undefined') spans[i].innerHTML = cnt;
        }

        var desktopBadge = $$$('compare-badge-count-desktop');
        var mobileBadge = $$$('compare-badge-count-mobile');
        if (desktopBadge) {
          desktopBadge.innerHTML = cnt;
          cnt > 0 ? desktopBadge.classList.remove('hidden') : desktopBadge.classList.add('hidden');
        }
        if (mobileBadge) {
          mobileBadge.innerHTML = cnt;
          cnt > 0 ? mobileBadge.classList.remove('hidden') : mobileBadge.classList.add('hidden');
        }
      }
    );
  };
}
/*  search.js */
/**
 * Имитация AJAX
 * @param	{string}	url
 * @param	{function}	callback
 */
function serachAjaxDemo(url, callback){
	url = url.replace(oSearch.url, '');
	if(trim(url) == '') callback('[]');

	var lines = [
		url,
		'test ' + url,
		'test ' + url + ' test',
		'test test ' + url + ' test',
		'test test test ' + url + ' test',
		'test test test test ' + url + ' test',
		'test test test test ' + url + ' test',
		'test test test ' + url + ' test',
		'test test ' + url + ' test test',
		'test test ' + url + ' test test',
		'test test ' + url + ' test test'
	];
	callback('["' + lines.join('","') + '"]');
}


/**
 * Класс для управленим поиском и подсказками
 */
function Search(){
	// Конфиг
	this.url = '/catalog/search/?getpopular=';
	this.inp = 'search-input';

	// Переменные для процесса
	this.prevText = '';
	this.layer = false;
	this.layerVisible = false;
	this.transitionTime = 100;
	this.resHeight = 30;

	var o = this;


	/**
	 * Инициализация
	 */
	this.init = function(){
		if(this.layer !== false) return;

		// Получаем объект поля для ввода запросов
		this.inp = $$$(this.inp);

		// Создаём блок для вывода подсказок
		var LId = this.inp.id + '-search-res';
		if($$$(LId) !== false){
			alert('Element with ID \'' + LId + '\' is already exist. This ID reserved for search results layer.');
			return;
		}
		this.layer = D.createElement('DIV');
		this.layer.id = LId;
		this.layer.className = 'title-search-result'
		this.inp.parentNode.insertBefore(this.layer, this.inp);
		/*if($$$('head')){
			$$$('head').appendChild(this.layer);
		}else if($$$('head-floating-mobile')){
			$$$('head-floating-mobile').appendChild(this.layer);
		}else{
			return;
		}*/
		addHandler(window, 'resize', function(){fixSizeAndPos()});
		addHandler(window, 'scroll', function(){fixSizeAndPos()});
		fixSizeAndPos();


		// Раздаём события и параметры
		this.inp.autocomplete = 'off';
		this.inp['onkeydown'] = function(e){
			return oSearch.keycheck(e);
		};
		addHandler(
			this.inp,
			'blur',
			function(){
				oSearch.hide();
			}
		);

		// Запускаем цикличную проверку изменений в тексте поля (на все случаи: клавой, мышкой, "right-click -> вставить")
		this.prevText = this.inp.value;
		setInterval(
			function(){
				oSearch.detectChanges();
			},
			200
		);
	};


	function fixSizeAndPos(){
		/*var headPos = elPos($$$('head'));
		var inpPos = elPos(o.inp);
		o.layer.style.left = (inpPos.x - headPos.x) + 'px';
		o.layer.style.top = (inpPos.y - headPos.y + 30) + 'px';*/

		var inpSize = elSize(o.inp);
		o.layer.style.width = (inpSize.w + 90) + 'px';
	}


	/**
	 * Цикличная проверка изменений в тексте поля
	 */
	this.detectChanges = function(){
		var text = this.inp.value;
		if(this.prevText == text) return;
		this.prevText = text;
		this.request(text);
	};


	/**
	 * Обработка нажатия стрелок и ентера
	 * @param	{event}	e
	 */
	this.keycheck = function(e){
		if(!e) e = window.event;
		// e.keyCode: 38 - UP, 40 - DOWN, 13 - ENTER

		/*if(e.keyCode == 38){
			// UP
			this.variantCheck(this.variantGetPrev());
		}
		if(e.keyCode == 40){
			// DOWN
			this.variantCheck(this.variantGetNext());
		}
		if(e.keyCode == 13){
			// ENTER
			var checkedVar = this.variantGetChecked();
			this.select(checkedVar);

			// Если варианты не показаны, или не один из них не выбран, форма отправляется по первому нажатию Enter
			return checkedVar ? false : true;
		}*/

		return true;
	};


	/**
	 * AJAX-запрос к серверу за списком подсказок
	 * @param	{string}	text
	 */
	this.request = function(text){
		if(typeof text == 'undefined' || text.length < 3){
			this.hide();
			return;
		}

		// AJAX-запрос
		//serachAjaxDemo(
		AJAX.lookup(
			this.url + encodeURIComponent(text),
			function(res){
				//console.log(res); return;

				/*res = AJAX.jsonDecode(res);
				if(res === false){	// Некорректный ответ
					oSearch.hide();
					return;
				}*/

				if(res != '0'){
					// Варианты для подсказки нет
					oSearch.show2(res);
				}else{
					// Вариантов для подсказки нет
					oSearch.hide();
				}
			}
		);
	};


	/**
	 * Скрываем блок с подсказками
	 */
	this.hide = function(){
		if(!this.layer) return;
		setTimeout(
			function(){
				oSearch.layerVisible = false;
				oSearch.layer.style.height = '0';

				if(TRANSITION_SUPPORT){
					setTimeout(
						function(){
							oSearch.layer.style.display = 'none';
						},
						oSearch.transitionTime
					);
				}else{
					oSearch.layer.style.display = 'none';
				}
			},
			50
		);
	};


	/**
	 * Отображаем блок с подсказками
	 * @param	{object}	res		Массив строк-подсказок
	 */
	this.show = function(res, text){
		if(!this.layer) return;
		text = trim(text);

		var html = '';
		var resCount = 0;
		for(var i in res){
			if(trim(res[i]) !== ''){
				var variant = trim(res[i]);
				variant = variant.replace(new RegExp(RegExp.escape(text), 'ig'), '<b>$&</b>');
				html += '<div onmouseover="oSearch.variantCheck(this)" onclick="oSearch.select(this)">' + variant + '</div>';
				resCount++;
			}
		}

		if(!html){
			this.hide();
			return;
		}

		this.layer.innerHTML = html;
		this.layer.style.display = 'block';

		this.layerVisible = true;

		if(TRANSITION_SUPPORT){
			setTimeout(
				function(){
					oSearch.layer.style.height = (oSearch.resHeight * resCount + 10) + 'px';
				},
				10
			);
		}else{
			this.layer.style.height = (this.resHeight * resCount) + 'px';
		}

		//this.variantCheck(this.variantGetFirst());
	};


	/**
	 * Отображаем блок с быстрыми результатами
	 * @param	{string}	html
	 */
	this.show2 = function(html){
		if(!this.layer) return;

		this.layer.innerHTML = html;
		this.layer.style.display = 'block';

		this.layerVisible = true;

		var resCount = parseInt($$$('search-res-count').innerHTML);
		if(TRANSITION_SUPPORT){
			setTimeout(
				function(){
					oSearch.layer.style.height = (oSearch.resHeight * resCount + 119) + 'px';
				},
				10
			);
		}else{
			this.layer.style.height = (this.resHeight * resCount + 119) + 'px';
		}

		//this.variantCheck(this.variantGetFirst());
	};


	/**
	 * Получаем html-элемент с активной (подсвеченной) подсказкой
	 * @return	{object}
	 */
	this.variantGetChecked = function(){
		if(!this.layerVisible) return false;

		var variants = byTag('DIV', this.layer);
		for(var i = 0, l = variants.length; i < l; i++){
			if(variants[i].className == 'checked'){
				return variants[i];
			}
		}
		return false;
	};


	/**
	 * Получаем html-элемент с первой в списке подсказкой
	 * @return	{object}
	 */
	this.variantGetFirst = function(){
		return this.layer.firstChild;
	};


	/**
	 * Получаем html-элемент с последней в списке подсказкой
	 * @return	{object}
	 */
	this.variantGetLast = function(){
		return this.layer.lastChild;
	};


	/**
	 * Получаем html-элемент со следующей по списку подсказкой
	 * @return	{object}
	 */
	this.variantGetNext = function(){
		var cur = this.variantGetChecked();
		var next = cur.nextSibling;
		if(next == null) next = this.variantGetFirst();
		return next;
	};


	/**
	 * Получаем html-элемент с предыдущей по списку подсказкой
	 * @return	{object}
	 */
	this.variantGetPrev = function(){
		var cur = this.variantGetChecked();
		var prev = cur.previousSibling;
		if(prev == null) prev = this.variantGetLast();
		return prev;
	};


	/**
	 * Делаем элемент с подсказкой активным (подсвеченным)
	 * @param varElement
	 */
	this.variantCheck = function(varElement){
		if(typeof(varElement) == 'undefined' || varElement == null) return;

		var variants = byTag('DIV', this.layer);
		for(var i = 0, l = variants.length; i < l; i++){
			if(variants[i] == varElement){
				variants[i].className = 'checked';
			}else{
				variants[i].className = '';
			}
		}
	};


	/**
	 * Клик/Enter по посказке - вписываем её в поле поиска и скрываем подсказки
	 * @param varElement
	 */
	this.select = function(varElement){
		if(varElement){
			var variant = varElement.innerHTML;

			while(variant.indexOf('<b>') != -1) variant = variant.replace('<b>', '');
			while(variant.indexOf('</b>') != -1) variant = variant.replace('</b>', '');
			while(variant.indexOf('<B>') != -1) variant = variant.replace('<B>', '');
			while(variant.indexOf('</B>') != -1) variant = variant.replace('</B>', '');

			this.prevText = variant;
			this.inp.value = variant;
		}
		this.hide();
	}
}

/**
 * Стартуем
 */
var oSearch = new Search();
onLoad(function(){
	oSearch.init();
});


/*var a;
try{
	a = eval('([])');
}catch(e){
	alert('1');
	a = 0;
}
alert(a);*/
/*  cart.js */
/**
 * Добавление в корзину,  доп. услуги в корзине и рассчёт их стоимости
 */
var oCart = new function(){

	/**
	 *
	 */
	var elCartItem, elCartTarget;

	/**
	 *
	 */
	var cartItemHideTimeout = 0;

	/**
	 *
	 */
	this.url = '';

	/**
	 *
	 */
	this.urlBuyOneClick = '';

	/**
	 *
	 */
	this.totalPrice = 0;


	this.amountPlus = function(lnk){
		var inp = byTag('INPUT', lnk.parentNode)[0];
		if(typeof(inp) === 'undefined') return;
		var amount = parseInt(inp.value);
		if(!isNaN(amount) && amount >= 0){
			amount++;
		}else{
			amount = 1;
		}
		inp.value = amount;
	};


	this.amountMinus = function(lnk, zero){
		if(typeof(zero) == 'undefined') zero = false;

		var inp = byTag('INPUT', lnk.parentNode)[0];
		if(typeof(inp) === 'undefined') return;
		var amount = parseInt(inp.value);
		if(!isNaN(amount) && amount >= 0){
			amount--;
			if(amount < 0) amount = 0;
		}else{
			amount = 1;
		}
		if(amount == 0 && !zero){
			amount = 1;
		}
		inp.value = amount;
	};


	/**
	 * @returns {string}
	 */
	function collectSet(){
		var seriesSet = [];
		var iId, mId, amount;
		if($$$('series-set')){
			var prices = byTag('SPAN', $$$('series-set'));
			for(var i = 0, l = prices.length; i < l; i++){
				if(prices[i].id.indexOf('item2-') === 0 && prices[i].id.indexOf('-price') !== -1 && prices[i].id.indexOf('-price-') === -1){
					iId = parseInt(prices[i].id.replace('item2-', '').replace('-price', ''));
					mId = $$$('item2-' + iId + '-material') ? parseInt($$$('item2-' + iId + '-material').value) : 0;
					amount = parseInt($$$('item2-' + iId + '-amount').value);

					if(isNaN(iId)) iId = 0;
					if(isNaN(mId)) mId = 0;
					if(isNaN(iId)) amount = 0;

                    if (isNaN(amount)) {
                        amount = 0;
                    }

					seriesSet.push(iId + '-' + mId + '-' + amount);
				}
			}
		} else if($$$('single-item-id')) {
			iId = parseInt($$$('single-item-id').value);
			mId = $$$('item-' + iId + '-material') ? parseInt($$$('item-' + iId + '-material').value) : 0;
			amount = parseInt($$$('item-' + iId + '-amount').value);

			if(isNaN(iId)) iId = 0;
			if(isNaN(mId)) mId = 0;
			if(isNaN(iId)) amount = 0;

            if (isNaN(amount)) {
                amount = 0;
            }

			seriesSet.push(iId + '-' + mId + '-' + amount);
		}

		return seriesSet.join(';');
	}


	this.setInfo = function(){
		var seriesSet = collectSet();
		//console.log(this.url + '?set-info=' + encodeURIComponent(seriesSet));
		AJAX.lookup(
			this.url + '?set-info=' + encodeURIComponent(seriesSet),
			function(respond){
				respond = AJAX.jsonDecode(respond);
				if(!respond) return;

				if($$$('info-delivery')){
					$$$('info-delivery').innerHTML = priceFormat(respond[0]);
				}
				if($$$('info-assembly')){
					if(Math.abs(respond[1]) != 0 || respond[3] == ''){
						$$$('info-assembly').innerHTML = priceFormat(respond[1]);
						$$$('info-assembly').className = '';
					}else{
						$$$('info-assembly').innerHTML = respond[3];
						$$$('info-assembly').className = 'zero';
					}
				}

				if($$$('text-delivery')){
					var spans = byTag('SPAN', $$$('text-delivery'));
					for(var i in spans){
						if(typeof(spans[i].className) == 'undefined') continue;
						if(spans[i].className.indexOf('info-delivery') != -1){
							spans[i].innerHTML = '<b>' + priceFormat(respond[0]) + '</b> руб.';
						}
						if(spans[i].className.indexOf('info-assembly') != -1){
							if(Math.abs(respond[1]) != 0 || respond[3] == ''){
								spans[i].innerHTML = '<b>' + priceFormat(respond[1]) + '</b> руб.';
							}else{
								spans[i].innerHTML = '<b>' + respond[3] + '</b>';
							}
						}
						if(spans[i].className.indexOf('info-unloading') != -1){
							spans[i].innerHTML = '<b>' + priceFormat(respond[2]) + '</b> руб.';
						}
					}
				}

			}
		);
	};


	this.buyOneClick = function(itemId){
		var seriesSet = collectSet();
		oPopup.loadUrl(
			'Купить в один клик',
			this.urlBuyOneClick + '?set=' + encodeURIComponent(seriesSet) + '&ajax=1',
			false, false, true
		);
	};





	onLoad(function(){
		elCartItem = $$$('cart-item');
		elCartTarget = $$$('cart-cnt');
	});

	this.add = function(elForm, addSet){

		addSet = !(typeof(addSet) === 'undefined' || !addSet);

		// Формируем URL для отправки запроса
		var url = elForm.action + '?';
		var inputs = byTag('INPUT', elForm);
		for(var i = 0, l = inputs.length; i < l; i++){
			url += inputs[i].name + '=' + encodeURIComponent(inputs[i].value) + '&';
		}
		var selects = byTag('SELECT', elForm);
		for(i = 0, l = selects.length; i < l; i++){
			url += selects[i].name + '=' + encodeURIComponent(selects[i].value) + '&';
		}
		url += 'ajax=1';
		//console.log(url);
		AJAX.lookup(
			url,
			function(respond){
				// console.log(respond);
				respond = AJAX.jsonDecode(respond);

				if(!respond) return;

				if(addSet){
					var btn1, btn2, btn3;
					btn1 = $('div.add2basketform button.add2basket');
					btn2 = $('div.add2basketform a.buy1click')[0];
					btn3 = $('div.add2basketform a.go2basket')[0];
					if(btn1){
						btn1.removeClass('add2basket');
						btn1.addClass('inbasket');
						btn1.title = 'В корзине';
						btn1.innerHTML = '<i class="icon pngicons"></i>В корзине';
					}
					if(btn2){
						btn2.style.display = 'none';
					}
					if(btn3){
						btn3.style.display = '';
					}
				}

				displayTotal(respond);
			}
		);
	};

	jQuery(document).on('click', '.seriespage-left div.add2basketform.main-cart-form button[type="submit"]', function(){
		var form = jQuery('form.mods')[0];
		jQuery(form).submit();
	});
	/**
	 *
	 */
  function displayTotal(cartTotal) {
    $$$("basketinfo").innerHTML =
      cartTotal[0] +
      " " +
      itemsAmountWord(cartTotal[0]) +
      " на " +
      priceFormat(cartTotal[1]) +
      " руб.";

    $$$("basketinfo-amount").innerHTML = cartTotal[0];
    $$$("basketinfo-price").innerHTML = priceFormat(cartTotal[1]) + " руб.";

    // Update cart badge counts
    var mobileBadge = $$$("cart-badge-count-mobile");
    var desktopBadge = $$$("cart-badge-count-desktop");

    if (mobileBadge) {
      mobileBadge.innerHTML = cartTotal[0];
      if (cartTotal[0] > 0) {
        mobileBadge.classList.remove("hidden");
      } else {
        mobileBadge.classList.add("hidden");
      }
    }

    if (desktopBadge) {
      desktopBadge.innerHTML = cartTotal[0];
      if (cartTotal[0] > 0) {
        desktopBadge.classList.remove("hidden");
      } else {
        desktopBadge.classList.add("hidden");
      }
    }

    if (typeof cartTotal[3] === "object") {
      var iId, btn1, btn2, btn3;
      for (var i = 0, l = cartTotal[3].length; i < l; i++) {
        if (typeof cartTotal[3][i] === "undefined") continue;
        iId = cartTotal[3][i];

        // Items list
        btn1 = findOne("#catalog-item-" + iId + " .add2basketform .submit");
        if (btn1) {
          btn1.addClass("in-cart");
          btn1.title = "В корзине";
        }

        // Item page
        btn1 = findOne("#catalog-item-full-" + iId + " .add2basket");
        btn2 = findOne("#catalog-item-full-" + iId + " .buy1click");
        btn3 = findOne("#catalog-item-full-" + iId + " .go2basket");
        if (btn1) {
          btn1.removeClass("add2basket");
          btn1.addClass("inbasket");
          btn1.title = "В корзине";
          btn1.innerHTML = '<i class="icon pngicons"></i>В корзине';
        }
        if (btn2) {
          btn2.style.display = "none";
        }
        if (btn3) {
          btn3.style.display = "";
        }
      }
    }
  }


	/** Получаем слово "товаров" в правильном падеже, в зависимоисти от к-ва товаров
	 * @param	{int}	amount
	 * @returns	{string}
	 */
	function itemsAmountWord(amount){
		amount = parseInt(amount); if(isNaN(amount)) amount = 0;

		if(amount >= 10){
			amount += '';
			amount = parseInt(amount.substr(amount.length - 2));
		}
		if(10 <= amount && amount <= 20){
			return 'товаров';
		}

		amount += '';
		amount = parseInt(amount.substr(amount.length - 1));
		if(amount == 0 || amount == 5 || amount == 6  || amount == 7  || amount == 8  || amount == 9){
			return 'товаров';
		}
		if(amount == 1){
			return 'товар';
		}
		if(amount == 2 || amount == 3 || amount == 4){
			return 'товарa';
		}
		return 'товаров';
	}


	/**
	 *
	 */
	var prevOptions = {};

	var calcOptionsTimeout = 0;

	/**
	 * При изменении опций заказа в корзине вызывается этот метод и стоимость опций пересчитывается
	 */
	this.calcOptions = function(){
		if(calcOptionsTimeout){
			clearTimeout(calcOptionsTimeout);
		}
		calcOptionsTimeout = setTimeout(
			function(){
				var form = $$$('cart-options');
				if(!form) return;

				// При выборе доставки "До трансп.компании", остальные опции выключаются
				if($$$('delivery-3').checked){
					$$$('unloading-0').checked = true;
					$$$('unloading-1').disabled = true;
					$$$('unloading-2').disabled = true;

					$$$('assembly-0').checked = true;
					$$$('assembly-1').disabled = true;

					$$$('garbage-0').checked = true;
					$$$('garbage-1').disabled = true;
				}else{
					$$$('unloading-1').disabled = false;
					$$$('unloading-2').disabled = false;
					$$$('assembly-1').disabled = false;
					$$$('garbage-1').disabled = false;
				}

				// Показать/скрыть ползунок выбора расстояния за МКАД
				if($$$('delivery-2').checked){
					$$$('delivery-distance-block').style.display = '';
				}else{
					$$$('delivery-distance-block').style.display = 'none';
				}

				// Показать/скрыть ползунок выбора этажа для разгрузки
				if($$$('unloading-1').checked || $$$('unloading-2').checked){
					$$$('unloading-floor-block').style.display = '';
				}else{
					$$$('unloading-floor-block').style.display = 'none';
				}

				var i, l, name;
				var options = {};
				var inputs = byTag('INPUT', form);
				for(i = 0, l = inputs.length; i < l; i++){
					if(!inputs[i].name){
						continue;
					}
					if((inputs[i].type == 'radio' || inputs[i].type == 'checkbox') && !inputs[i].checked){
						continue;
					}
					options[inputs[i].name] = inputs[i].value;
				}

				var changed = false;
				for(i in options){
					if(typeof(prevOptions[i]) == 'undefined' || prevOptions[i] != options[i]){
						changed = true;
						break;
					}
				}
				if(!changed) return;
				prevOptions = options;

				var url = oCart.url + '?ajax=1&options-calc=1';
				for(i in options){
					url += '&' + encodeURIComponent(i) + '=' + options[i];
				}
				AJAX.lookup(
					url,
					function(respond){
						respond = AJAX.jsonDecode(respond);
						if(!respond) return;

						if($$$('delivery-info')){
							$$$('delivery-info').innerHTML = respond.delivery.info;
						}
						if($$$('delivery-price')){
							$$$('delivery-price').innerHTML = priceFormat(respond.delivery.price);
						}

						if($$$('unloading-info')){
							$$$('unloading-info').innerHTML = respond.unloading.info;
						}
						if($$$('unloading-price')){
							$$$('unloading-price').innerHTML = priceFormat(respond.unloading.price);
						}

						if($$$('assembly-info')){
							$$$('assembly-info').innerHTML = respond.assembly.info;
						}
						if($$$('assembly-price')){
							$$$('assembly-price').innerHTML = priceFormat(respond.assembly.price);
						}

						if($$$('garbage-info')){
							$$$('garbage-info').innerHTML = respond.garbage.info;
						}
						if($$$('garbage-price')){
							$$$('garbage-price').innerHTML = priceFormat(respond.garbage.price);
						}

						var optionsPrice =
							respond.delivery.price +
							respond.unloading.price +
							respond.assembly.price +
							respond.garbage.price;

						if($$$('options-price1')){
							$$$('options-price1').innerHTML = priceFormat(optionsPrice);
						}
						if($$$('options-price2')){
							$$$('options-price2').innerHTML = priceFormat(optionsPrice);
						}
						if($$$('total-price')){
							$$$('total-price').innerHTML = priceFormat(oCart.totalPrice + optionsPrice);
						}
					}
				);
			},
			100
		);
	};
	onLoad(function(){
		oCart.calcOptions();
		if($$$('options-calc-nojs')){
			$$$('options-calc-nojs').style.display = 'none';
		}
	});
};
/* gototop.js  */
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


// фиксированные при прокрутке шапка
document.addEventListener('DOMContentLoaded', function () {
	var tpanel_inner = document.querySelector('.tpanel_inner'),
		tpanel_inner_right = document.querySelector('.tpanel_inner_right'),
		// ширина меню в шапке
		widthTpanel = $('.tpanel_inner .centering').outerWidth(true),
		widthTpanelRight = $('.tpanel_inner_right').outerWidth(true),
		widthTpanelLeft = widthTpanel - widthTpanelRight - 20,
		tPanelMenu = document.querySelector('.tpanel_menu');
	tpanel_inner_right.style.display = "none";
	if (document.querySelector('#tpanel')) {
		var elHeight = $('#header').outerHeight(true);
		var h = $("#tpanel").offset().top + elHeight - $(window).scrollTop();
		if (h < 0) {
			tpanel_inner.classList.add('scrolled');
			tpanel_inner_right.style.display = "inline-block";
			// ширина меню в шапке
			tPanelMenu.style.width = widthTpanelLeft + 'px';
			tPanelMenu.style.marginBottom = '10px';
		} else {
			tpanel_inner.classList.remove('scrolled');
			tpanel_inner_right.style.display = "none";
			// ширина меню в шапке
			tPanelMenu.style.width = '100%';
			tPanelMenu.style.marginBottom = '0px';
		};
		$(window).scroll(function () {
			h = $("#tpanel").offset().top + elHeight - $(window).scrollTop();
			if (h < 0) {
				tpanel_inner.classList.add('scrolled');
				tpanel_inner_right.style.display = "inline-block";
				// ширина меню в шапке
				tPanelMenu.style.width = widthTpanelLeft + 'px';
				tPanelMenu.style.marginBottom = '10px';
			} else {
				tpanel_inner.classList.remove('scrolled');
				tpanel_inner_right.style.display = "none";
				// ширина меню в шапке
				tPanelMenu.style.width = '100%';
				tPanelMenu.style.marginBottom = '0px';
			}
		});
	}
})



// фиксированные при прокрутке тэги
document.addEventListener('DOMContentLoaded', function () {
	if (document.querySelector('.tags_container')) {
		var elHeight = $('.tag_pages').outerHeight(true);
		var tPanelHeight = $('.tpanel .tpanel_inner').outerHeight(true);
		var h = $(".tags_container").offset().top - tPanelHeight - $(window).scrollTop();
		if (h < 0) {
			$('#tags').addClass('scrolled');
			document.querySelector('.tags_container').style.marginBottom = elHeight + "px";
			document.querySelector('#tags').style.top = tPanelHeight + "px";
		} else {
			$('#tags').removeClass('scrolled');
			document.querySelector('.tags_container').style.marginBottom = "0px";
			document.querySelector('#tags').style.top = "unset";
		};

		$(window).scroll(function () {
			tPanelHeight = $('.tpanel .tpanel_inner').outerHeight(true);
			h = $(".tags_container").offset().top - tPanelHeight - $(window).scrollTop();
			if (h < 0) {
				$('#tags').addClass('scrolled');
				document.querySelector('.tags_container').style.marginBottom = elHeight + "px";
				document.querySelector('#tags').style.top = tPanelHeight + "px";
			} else {
				$('#tags').removeClass('scrolled');
				document.querySelector('.tags_container').style.marginBottom = "0px";
				document.querySelector('#tags').style.top = "unset";
			};
		});
	}
})


// Показать все/Скрыть - блок тэгов
document.addEventListener('DOMContentLoaded', function () {
	if ($(window).width() < 876) {
		if (document.querySelector('#tags')) {
			var tagPages = $('.tag_pages'),
				tagPagesHeight = tagPages.height(),
				tagPagesLink = $('.tag_page_link'),
				tagPagesLinkHeight = tagPagesLink.outerHeight() + parseInt(tagPagesLink.css('margin-top'), 10) + parseInt(tagPagesLink.css('margin-bottom'), 10),
				tagPagesLinkBorderHeight = 2;
			btnMore = document.querySelector('.btn_tag_more'),
				btnLess = document.querySelector('.btn_tag_less'),
				tagsBlock = $('#tags');
			if (tagPagesHeight > tagPagesLinkHeight) {
				tagsBlock.addClass('expandable');
				tagPages.css('height', (tagPagesLinkHeight - tagPagesLinkBorderHeight) + 'px');
			};

			btnMore.addEventListener('click', function () {
				tagsBlock.addClass('open');
				tagPages.css('height', 'auto');
			});
			btnLess.addEventListener('click', function () {
				tagsBlock.removeClass('open');
				tagPages.css('height', (tagPagesLinkHeight - tagPagesLinkBorderHeight) + 'px');
			});
		};

		// шапка, мобильный кнопка поиска, меню каталога
		var aroundText = document.querySelector('.new_block .searchinhead .aroundtext');
		document.querySelector('.new_block .searchinhead .icon1').addEventListener('click', function () {
			aroundText.classList.add('show_search'); // показать поиск
		});
		document.querySelector('.new_block .catalogmenu li.parent').addEventListener('click', function () {
			aroundText.classList.remove('show_search'); // скрыть поиск
		})
		$(window).scroll(function () {
			if (!document.querySelector('.new_block .searchinhead .aroundtext input').value && !$(".new_block .searchinhead .aroundtext input").is(":focus")) {
				aroundText.classList.remove('show_search'); // скрыть поиск при прокрутке
			};
		})
		var catalog_button = document.querySelector('.new_block .catalogmenu .icon.menu'),
			clearfix = document.querySelector('.new_block .catalogmenu.clearfix'),
			liParent = document.querySelector('.new_block .catalogmenu li.parent');
		catalog_button.addEventListener('click', function () {
			if (catalog_button.classList.contains('hover')) {
				clearfix.classList.remove('hover');
				liParent.classList.remove('hover');
				catalog_button.classList.remove('hover');
			} else {
				clearfix.classList.add('hover');
				liParent.classList.add('hover');
				catalog_button.classList.add('hover');
			};
		});
	};
});

// мигание "Цены на товары"
document.addEventListener('DOMContentLoaded', function () {

	var blinkElement = document.querySelector('.title2.green');

	if (blinkElement) {
		setInterval(function () {
			blinkElement.classList.add('active');
			setTimeout(function () {
				blinkElement.classList.remove('active')
			}, 150);
		}, 1200);
	};

});

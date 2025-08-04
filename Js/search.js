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
		if(text.length < 3){
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

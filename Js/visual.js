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

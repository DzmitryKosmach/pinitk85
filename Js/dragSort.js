function DragSort(table){

    this.dragON = false;

    this.rowsO = [];
    this.y1 = 0;
    this.y2 = 0;
    this.shadow = null;

    this.dragRowO = null;
    this.dragRowY1 = null;
    this.dragRowY2 = null;
    this.dragRowOPrev = null;
    this.dragRowONext = null;

    var body = document.body;

    // Инициация
    this.init = function(){
        // Находим строки таблицы
        var cells = byTag('TD', table);
        for(var i = 0, l = cells.length; i < l; i++)
            if(cells[i].className == 'dragSort-id'){
                var rowE = cells[i].parentNode;
                while(rowE.tagName != 'TR' && rowE.tagName != 'BODY') rowE = rowE.parentNode;
                if(rowE.tagName != 'TR') return;
                this.rowsO.push(new DragSortRowO(rowE, this.rowsO.length, this));
            }
        // Определяем края таблицы
        if(this.rowsO.length){
            var firstE = this.rowsO[0].element;
            var lastE = this.rowsO[this.rowsO.length - 1].element;
            this.y1 = elPos(firstE).y;
            this.y2 = elPos(lastE).y + elSize(lastE).h;
        }

        // Создаём объект с тенькой
        if(!$$$('dragSort-shadow')){
            this.shadow = document.createElement('DIV');
            this.shadow.id = 'dragSort-shadow';
            body.appendChild(this.shadow);
        } else this.shadow = $$$('dragSort-shadow');
        this.shadow.style.display = 'none';
        this.shadow.style.position = 'absolute';
    };


    // Запуск перетягивания
    this.dragStart = function(rowE){
        disableSelection();
        body.style.cursor = 'move';

        // Находим активную строку (объект)
        this.dragRowO = false;
        for(var i in this.rowsO) if (this.rowsO[i].element == rowE){
            this.dragRowO = this.rowsO[i];
            break;
        }
        if(this.dragRowO === false){
            this.dragStop();
            return;
        }

        // Находим предыдущую и следующую строку
        this.dragRowOPrev = this.dragRowO.num ? this.getRowO(this.dragRowO.num - 1) : false;
        this.dragRowONext = this.dragRowO.num < this.rowsO.length - 1 ? this.getRowO(this.dragRowO.num + 1) : false;

        // Вычисляем координаты верхнего и нижнего края текущей строки
        if (this.dragRowOPrev) this.dragRowY1 = elPos(rowE).y;
        if (this.dragRowONext) this.dragRowY2 = elPos(rowE).y + +elSize(rowE).h;

        // Подсветка/Тень
        this.highlightOn(rowE);

        this.dragON = true;
    };

    // Остановка перетягивания
    this.dragStop = function(){
        if(!this.dragON) return;

        enableSelection();
        body.style.cursor = 'default';
        this.highlightOff();
        this.dragON = false;

        AJAX.lookup(
            dragTableUrl +
                (dragTableUrl.indexOf('?') !== -1 ? '&' : '?') +
                'order=' + encodeURIComponent(this.getIDS()) +
                '&direct=' + dragTableDirect +
                '&act=dragsort'
        );
    };


    // Возвращает массив ID строк в реальном порядке
    this.getIDS = function(){
        var ids = [];
        var cells = byTag('TD', table);
        for(var i = 0, l = cells.length; i < l; i++) if(cells[i].className == 'dragSort-id') ids.push(Math.abs(cells[i].innerHTML));
        return ids;
    };

    // Обменивает строки
    this.exchangeRows = function(rowO1, rowO2){
        rowO1.element.parentNode.insertBefore(rowO2.element, rowO1.element);		// Перемещаем элементы
        var tmp = rowO1.num;
        rowO1.num = rowO2.num;
        rowO2.num = tmp;	// Обмениваем их номера
    };


    body.onmousemove = function(e){
        if(!dragTable.dragON) return;
        var mouseY = mouseCoords(e).y;
        if(mouseY < dragTable.y1) mouseY = dragTable.y1;
        if(mouseY > dragTable.y2) mouseY = dragTable.y2;

        // Обмен с предыдущей ячейкой
        while(dragTable.dragRowOPrev && mouseY < dragTable.dragRowY1){
            dragTable.exchangeRows(dragTable.dragRowOPrev, dragTable.dragRowO);
            dragTable.dragStart(dragTable.dragRowO.element);
        }

        // Обмен со следующей ячейкой
        while(dragTable.dragRowONext && mouseY > dragTable.dragRowY2){
            dragTable.exchangeRows(dragTable.dragRowO, dragTable.dragRowONext);
            dragTable.dragStart(dragTable.dragRowO.element);

        }

    };
    body.onmouseup = function(){
        dragTable.dragStop();
    };


    var disableSelectionOrigVal;
    // Выключаем выдыление текста на странице
    function disableSelection(){
        var o = document.body;
        if(typeof(o.onselectstart) != 'undefined'){
            if (typeof(disableSelectionOrigVal) == 'undefined') disableSelectionOrigVal = o.onselectstart;
            o.onselectstart = function(){
                return false
            };
        }else if(typeof(o.style.MozUserSelect) != 'undefined'){
            if (typeof(disableSelectionOrigVal) == 'undefined') disableSelectionOrigVal = o.style.MozUserSelect;
            o.style.MozUserSelect = 'none';
        }else{
            if(typeof(disableSelectionOrigVal) == 'undefined') disableSelectionOrigVal = o.onmousedown;
            o.onmousedown = function(){
                return false
            };
        }
    }

    // Включаем выдыление текста на странице
    function enableSelection(){
        if(typeof(disableSelectionOrigVal) == 'undefined') return;
        var o = document.body;
        if(typeof(o.onselectstart) != 'undefined'){
            o.onselectstart = disableSelectionOrigVal;
        }else if (typeof(o.style.MozUserSelect) != 'undefined'){
            o.style.MozUserSelect = disableSelectionOrigVal;
        }else{
            o.onmousedown = disableSelectionOrigVal;
        }
    }

    // Возвращает строку по номеру
    this.getRowO = function(n){
        for (var i in this.rowsO) if (this.rowsO[i].num == n) return this.rowsO[i];
        return false;
    };

    // Подсветка / Тень
    this.highlightOff = function(){
        this.shadow.style.display = 'none';
        for (var i in this.rowsO) this.rowsO[i].element.className = '';
    };

    this.highlightOn = function(rowE){
        this.highlightOff();
        rowE.className = 'dragSort-on';

        var c = elPos(rowE);
        var s = elSize(rowE);
        this.shadow.style.left = c.x + 'px';
        this.shadow.style.top = c.y + 'px';
        this.shadow.style.width = s.w + 'px';
        this.shadow.style.height = s.h + 'px';
        this.shadow.style.display = 'block';
    };
}

function DragSortRowO(rowEelement, num, table){
    this.element = rowEelement;
    this.num = num;

    // Область, за которую нужно хвататься
    var cells = byTag('TD', rowEelement);
    for(var i = 0, l = cells.length; i < l; i++){
	    if(cells[i].className == 'dragSort-pull') cells[i].onmousedown = function(){
		    table.dragStart(rowEelement)
	    }
    }
}

var dragTable;
var dragTableUrl = '';
var dragTableDirect = 'ASC';
onLoad(function(){
    if($$$('dragTable') && dragTableUrl){
        dragTable = new DragSort($$$('dragTable'));
        dragTable.init();
    }
});
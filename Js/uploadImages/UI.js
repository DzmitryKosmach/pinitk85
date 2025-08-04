// -------------------------------------------------------------------------- //
// ------------- РАСШИРЕННЫЕ ФУНКЦИИ ДЛЯ ПОЛЕЙ ЗАГРУЗКИ КАРТИНОК ------------ //
// -------------------------------------------------------------------------- //

// Подключение стилей. Их также можно отдельно переорпделить.
document.write('<link rel="stylesheet" href="/Js/uploadImages/style.css" type="text/css">');

// Основной класс
function UploadImagesClass() {
    if (!AJAX.browserSupport) return;

    // Ресайз превьюшки
    this.previewResize = true;
    this.previewResizeTo = {w:80, h:80};

    // URL для сабмита
    this.connector = '/upload-images/';

    // Шаблон расширенного поля
    this.pattern = '<div class="uploadImages" id="uploadImages-%num%" ondragover="return">';
    this.pattern += '	<div class="uploadImages-preview-area">';
    this.pattern += '		<div id="uploadImages-preview-no-%num%" class="uploadImages-preview-no"></div>';
    this.pattern += '		<div class="uploadImages-preview" id="uploadImages-preview-%num%">';
    this.pattern += '			<a id="uploadImages-preview-link-%num%" class="uploadImages-preview-link" target="_blank">';
    this.pattern += '				<img id="uploadImages-preview-image-%num%" width="50" height="50" alt="Предварительный просмотр">';
    this.pattern += '			</a>';
    this.pattern += '			<a href="javascript:void(0)" onclick="UploadImages.imgDel(%num%); return false;" class="uploadImages-preview-del" id="uploadImages-preview-del-%num%" title="Удалить картинку"><img src="/Js/uploadImages/img/del-preview.png" width="10" height="10" alt=""></a>';
    this.pattern += '		</div>';
    this.pattern += '	</div>';
    this.pattern += '	<div class="uploadImages-right">';
    this.pattern += '		<div id="uploadImages-field-%num%" class="uploadImages-field">';
    this.pattern += '			<input id="file-input-will-replace-this">';		// Этот элемент обозначает место, куда вставится исходное поле <input type="file">
    this.pattern += '		</div>';
    this.pattern += '		<div class="uploadImages-tip">';
    this.pattern += '			?';
    this.pattern += '			<div class="uploadImages-tip-baloon" id="uploadImages-tip-baloon-%num%">';
    this.pattern += '				<div id="uploadImages-tip-%num%" class="uploadImages-tip-baloon-text"></div>';
    this.pattern += '				<div class="uploadImages-tip-baloon-tail1"><div class="uploadImages-tip-baloon-tail2"></div></div>';
    this.pattern += '			</div>';
    this.pattern += '		</div>';
    this.pattern += '		<div class="cl"></div>';
    this.pattern += '		<div class="uploadImages-status">';
    this.pattern += '			<div id="uploadImages-status-default-%num%" class="uploadImages-status-default">';
    this.pattern += '				Выберите файл или<br>перетащите его сюда мышкой.';
    this.pattern += '			</div>';
    this.pattern += '			<div id="uploadImages-status-loading-%num%" class="uploadImages-status-loading" title="Загрузка файла на сервер"></div>';
    this.pattern += '			<div id="uploadImages-status-deleted-%num%" class="uploadImages-status-deleted">Изображение удалено.</div>';
    this.pattern += '			<div id="uploadImages-status-error-%num%" class="uploadImages-status-error"></div>';
    this.pattern += '			<div id="uploadImages-status-success-%num%" class="uploadImages-status-success">Файл успешно загружен.</div>';
    this.pattern += '		</div>';
    this.pattern += '	</div>';
    this.pattern += '</div><div class="cl"></div>';
    this.pattern += '<input type="hidden" size="100" id="uploadImages-result-%num%">';
    this.pattern += '<iframe id="uploadImages-iframe-%num%" name="uploadImages-iframe-%num%" class="uploadImages-iframe"></iframe>';

    this.UInum = 0;
    this.fileFields = [];

    // Создаём новое расширенное поле из обычного поля
    this.create = function (fileField, defaultFile, commentText) {
        // Проверяем передеанные параметры, присваиваем значение по-умолчанию
        if (typeof(fileField) != 'object') return;
        if (typeof(defaultFile) != 'string') defaultFile = '';
        if (typeof(commentText) != 'string') commentText = '';

        this.UInum++;
        var UInum = this.UInum;
        this.fileFields[UInum] = fileField;	// Сохраняем поле в массиве полей


        // ГЕНЕРАЦИЯ КОДА РАСШИРЕННОГО ПОЛЯ
        // Создаём блок, вставляем в него HTML из шаблона
        var html = this.pattern;
        while (html.indexOf('%num%') != -1) html = html.replace('%num%', UInum);
        var UI = D.createElement('DIV');
        UI.innerHTML = html;
        fileField.parentNode.insertBefore(UI, fileField);

        // Вставляем в нужное место исходное поле <input type="file">
        var placeForInput = $$$('file-input-will-replace-this');
        placeForInput.parentNode.insertBefore(fileField, placeForInput);
        removeElement(placeForInput);

        // ПОЛЕ С ИМЕНЕМ ИТОГОВОГО ФАЙЛА
        $$$('uploadImages-result-' + UInum).name = fileField.name + '-preloaded';

        // ПРЕВЬЮШКА
        this.showPreview(UInum, defaultFile);

        // ПОДСКАЗКА
        $$$('uploadImages-tip-' + UInum).innerHTML = commentText;
        $$$('uploadImages-tip-baloon-' + UInum).style.height = ($$$('uploadImages-tip-' + UInum).offsetHeight + 20) + 'px';

        // СТАТУС
        this.setStatus(UInum, 'default');

        // ПОЛЕ ВЫБОРА ФАЙЛА
        addHandler(fileField, 'change', function (e) {
            if (!e) e = window.event;
            UploadImages.uploadByForm(UInum);
        });

        // Drag&Drop
        var mainBlock = $$$('uploadImages-' + UInum);
        if (typeof(window.FileReader) != 'undefined' && typeof(mainBlock.ondragover) != 'undefined') {

            mainBlock.ondragover = function () {
                if (mainBlock.className.indexOf(' uploadImages-drag') == -1) mainBlock.className += ' uploadImages-drag';
                return false;
            };

            mainBlock.ondragleave = function () {
                mainBlock.className = mainBlock.className.replace(' uploadImages-drag', '');
                return false;
            };

            mainBlock.ondrop = function (e) {
                mainBlock.className = mainBlock.className.replace(' uploadImages-drag', '');
                UploadImages.uploadByDrop(UInum, e);
            };
        }
    };


    // Выводит превью загруженного изображения
    this.previewHasOnLoadHandler1 = [];
    this.previewHasOnLoadHandler2 = [];
    this.showPreview = function (UInum, link) {
        $$$('uploadImages-preview-' + UInum).className = 'uploadImages-preview';
        $$$('uploadImages-preview-no-' + UInum).className = 'uploadImages-preview-no';

        if (link) {
            // Ссылка
            $$$('uploadImages-preview-link-' + UInum).href = link;

            // Картинка
            var preview = $$$('uploadImages-preview-image-' + UInum);

            // Вешаем событие на onLoad
            if (typeof(UploadImages.previewHasOnLoadHandler1[UInum]) == 'undefined') {
                UploadImages.previewHasOnLoadHandler1[UInum] = true;
                addHandler(preview, 'load', function () {
                    $$$('uploadImages-preview-' + UInum).className = 'uploadImages-preview visible';
                });
            }

            if (this.previewResize) {
                var dotPos = link.lastIndexOf('.');
                link = link.substr(0, dotPos) + '_' + this.previewResizeTo.w + 'x' + this.previewResizeTo.h + '_0' + link.substr(dotPos);
            }
            preview.src = link;

        } else {
            // Превьюшки нет
            $$$('uploadImages-preview-no-' + UInum).className = 'uploadImages-preview-no visible';
        }
    };

    // Удаление картинки
    this.imgDel = function (UInum) {
        this.showPreview(UInum, false);
        $$$('uploadImages-result-' + UInum).value = 'delete';

        this.setStatus(UInum, 'deleted');
        setTimeout(function () {
            UploadImages.setStatus(UInum, 'default');
        }, 3000);
    };

    // Устанавливает стутус текущего процесса
    this.setStatus = function (UInum, type, text) {
        if (typeof(text) != 'string') text = '';

        // Включаем невидимость для вех статусов
        var statuses = ['default', 'deleted', 'loading', 'error', 'success'];
        for (var i in statuses) $$$('uploadImages-status-' + statuses[i] + '-' + UInum).className = 'uploadImages-status-' + statuses[i];

        // Включаем видимость для 1-го выбранного статуса, вставляем текст, если надо
        $$$('uploadImages-status-' + type + '-' + UInum).className = 'uploadImages-status-' + type + ' visible';
        if (text) $$$('uploadImages-status-' + type + '-' + UInum).innerHTML = text;
    };

    // Выводит статус "Ошибка" на 3 секунды
    this.showErr = function (UInum, text) {
        this.setStatus(UInum, 'error', text);
        setTimeout(function () {
            UploadImages.setStatus(UInum, 'default');
        }, 3000);
    };


    // Отправка файла через форму
    this.uploadByForm = function (UInum) {
        // Создаём форму
        var form = D.createElement('FORM');
        form.className = 'uploadImages-tmpForm';
        form.method = 'post';
        form.action = this.connector + '?type=iframe&UInum=' + UInum;
        form.target = 'uploadImages-iframe-' + UInum;
        form.enctype = 'multipart/form-data';
        D.body.appendChild(form);

        var fldOld = this.fileFields[UInum];

        // Создаём новое файловое поле
        var fldNew = D.createElement('INPUT');
        fldNew.type = 'file';
        fldNew.id = fldOld.id;
        fldNew.className = fldOld.className;
        fldNew.name = fldOld.name;

        // Меняем поля местами, старое поле отправляем во временную форму
        fldOld.parentNode.insertBefore(fldNew, fldOld);
        this.fileFields[UInum] = fldNew;
        form.appendChild(fldOld);

        // Корректируем свойства
        fldOld.id = '';
        fldOld.name = 'image';
        addHandler(fldNew, 'change', function (e) {
            if (!e) e = window.event;
            UploadImages.uploadByForm(UInum);
        });

        // Сабмит формы
        this.setStatus(UInum, 'loading');
        form.submit();

        // Удаляем форму
        removeElement(form);
    };


    // Отправка файла через AJAX (после дропа)
    this.uploadByDrop = function (UInum, e) {
        if (!e) e = window.event;
        eventCancelDefault(e);

        if (e.dataTransfer.files.length) {
            // Перетащили файл
            this.setStatus(UInum, 'loading');
            var file = e.dataTransfer.files[0];

            var imgXHR = new XMLHttpRequest();
            imgXHR.onreadystatechange = function () {
                if (imgXHR.readyState != 4) return;
                UploadImages.uploadResult(imgXHR.responseText);
            };
            imgXHR.open('POST', this.connector + '?type=drop&UInum=' + UInum);
            imgXHR.setRequestHeader('X-FILE-NAME', file.name);
            var fd = new FormData;
            fd.append('image', file);
            imgXHR.send(fd);

            return;
        }

        var srcRegex = /src=\"([^\s]+)\"/ig; 								//"		- я здесь закоментил ковычку, т.к. редактор кода из-за этого регэкспа по-дурацки подсвечивает код
        var data = e.dataTransfer.getData('text/html');
        var img = srcRegex.exec(data);
        if (img != null) {
            // Перетащили картинку
            img = img[1];
            this.setStatus(UInum, 'loading');
            AJAX.lookup(
                this.connector + '?type=drop&UInum=' + UInum + '&src=' + encodeURIComponent(img),
                UploadImages.uploadResult
            );
        }
    };


    // Обработка результата загрузки файла
    this.uploadResult = function (result) {
        // Разбираем результат
        result = result.split('|');
        var success = Math.abs(result[0]);
        var UInum = result[1];
        var data1 = result[2];
        var data2 = result[3];

        if (!success) {
            UploadImages.showErr(UInum, data1);
            return;
        }	// Ошибка при загрузке

        // Успешная загрузка

        if (typeof(UploadImages.previewHasOnLoadHandler2[UInum]) == 'undefined') {
            UploadImages.previewHasOnLoadHandler2[UInum] = true;
            addHandler($$$('uploadImages-preview-image-' + UInum), 'load', function () {
                UploadImages.setStatus(UInum, 'success');
                setTimeout(function () {
                    UploadImages.setStatus(UInum, 'default');
                }, 3000);
            });
        }
        $$$('uploadImages-result-' + UInum).value = data1;
        UploadImages.showPreview(UInum, data2);
    };
}
var UploadImages = new UploadImagesClass;
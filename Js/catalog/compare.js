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

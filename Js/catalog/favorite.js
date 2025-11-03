var oFavorite = new (function () {
  /**
   * {string}
   */
  this.url = "";

  /**
   * @param  {int}  seriesId
   */
  this.add = function (seriesId) {
    // Находим все кнопки "Добавить" для этого товара
    var addButtons = document.querySelectorAll(
      '.series-favorite-add[data-id="' + seriesId + '"]'
    );
    var removeButtons = document.querySelectorAll(
      '.series-favorite-remove[data-id="' + seriesId + '"]'
    );

    // Скрываем "Добавить", показываем "Убрать" через классы вместо прямого изменения стилей
    addButtons.forEach(function (btn) {
      btn.classList.add("hidden");
    });
    removeButtons.forEach(function (btn) {
      btn.classList.remove("hidden");
    });

    // Отправляем AJAX
    AJAX.lookup(this.url + "?add=" + seriesId + "&ajax=1", function (cnt) {
      // Обновление счётчиков
      $$$("series-favorite-cnt").innerHTML = cnt;
      var spans = find("span.series-favorite-cnt");
      for (var i = 0, l = spans.length; i < l; i++) {
        if (typeof spans[i] !== "undefined") spans[i].innerHTML = cnt;
      }

      // Обновление бейджей в header
      var desktopBadge = $$$("favorite-badge-count-desktop");
      var mobileBadge = $$$("favorite-badge-count-mobile");
      if (desktopBadge) {
        desktopBadge.innerHTML = cnt;
      }
      if (mobileBadge) {
        mobileBadge.innerHTML = cnt;
      }
    });
  };

  /**
   * @param  {int}  seriesId
   */
  this.remove = function (seriesId) {
    // Находим все кнопки "Убрать" для этого товара
    var addButtons = document.querySelectorAll(
      '.series-favorite-add[data-id="' + seriesId + '"]'
    );
    var removeButtons = document.querySelectorAll(
      '.series-favorite-remove[data-id="' + seriesId + '"]'
    );

    // Показываем "Добавить", скрываем "Убрать" через классы вместо прямого изменения стилей
    addButtons.forEach(function (btn) {
      btn.classList.remove("hidden");
    });
    removeButtons.forEach(function (btn) {
      btn.classList.add("hidden");
    });

    // Отправляем AJAX
    AJAX.lookup(this.url + "?remove=" + seriesId + "&ajax=1", function (cnt) {
      // Обновление счётчиков
      $$$("series-favorite-cnt").innerHTML = cnt;
      var spans = find("span.series-favorite-cnt");
      for (var i = 0, l = spans.length; i < l; i++) {
        if (typeof spans[i] !== "undefined") spans[i].innerHTML = cnt;
      }

      // Обновление бейджей в header
      var desktopBadge = $$$("favorite-badge-count-desktop");
      var mobileBadge = $$$("favorite-badge-count-mobile");
      if (desktopBadge) {
        desktopBadge.innerHTML = cnt;
      }
      if (mobileBadge) {
        mobileBadge.innerHTML = cnt;
      }
    });
  };
})();

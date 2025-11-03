/**
 * Избранное
 */
var oFavorite = new function () {
  this.url = "";

  this.add = function (seriesId) {
    var addButtons = document.querySelectorAll('.series-favorite-add[data-id="' + seriesId + '"]');
    var removeButtons = document.querySelectorAll('.series-favorite-remove[data-id="' + seriesId + '"]');

    addButtons.forEach(btn => btn.classList.add("hidden"));
    removeButtons.forEach(btn => btn.classList.remove("hidden"));

    AJAX.lookup(this.url + "?add=" + seriesId + "&ajax=1", function (cnt) {
      $$$("series-favorite-cnt").innerHTML = cnt;
      var spans = find("span.series-favorite-cnt");
      for (var i = 0, l = spans.length; i < l; i++) {
        if (typeof spans[i] !== "undefined") spans[i].innerHTML = cnt;
      }

      // Обновление бейджей в header с учётом видимости
      var desktopBadge = $$$("favorite-badge-count-desktop");
      var mobileBadge = $$$("favorite-badge-count-mobile");
      if (desktopBadge) {
        desktopBadge.innerHTML = cnt;
        cnt > 0 ? desktopBadge.classList.remove('hidden') : desktopBadge.classList.add('hidden');
      }
      if (mobileBadge) {
        mobileBadge.innerHTML = cnt;
        cnt > 0 ? mobileBadge.classList.remove('hidden') : mobileBadge.classList.add('hidden');
      }
    });
  };

  this.remove = function (seriesId) {
    var addButtons = document.querySelectorAll('.series-favorite-add[data-id="' + seriesId + '"]');
    var removeButtons = document.querySelectorAll('.series-favorite-remove[data-id="' + seriesId + '"]');

    removeButtons.forEach(btn => btn.classList.add("hidden"));
    addButtons.forEach(btn => btn.classList.remove("hidden"));

    AJAX.lookup(this.url + "?remove=" + seriesId + "&ajax=1", function (cnt) {
      $$$("series-favorite-cnt").innerHTML = cnt;
      var spans = find("span.series-favorite-cnt");
      for (var i = 0, l = spans.length; i < l; i++) {
        if (typeof spans[i] !== "undefined") spans[i].innerHTML = cnt;
      }

      var desktopBadge = $$$("favorite-badge-count-desktop");
      var mobileBadge = $$$("favorite-badge-count-mobile");
      if (desktopBadge) {
        desktopBadge.innerHTML = cnt;
        cnt > 0 ? desktopBadge.classList.remove('hidden') : desktopBadge.classList.add('hidden');
      }
      if (mobileBadge) {
        mobileBadge.innerHTML = cnt;
        cnt > 0 ? mobileBadge.classList.remove('hidden') : mobileBadge.classList.add('hidden');
      }
    });
  };
};

/**
 * Добавление в корзину,  доп. услуги в корзине и рассчёт их стоимости
 */
var oCart = new (function () {
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
  this.url = "";

  /**
   *
   */
  this.urlBuyOneClick = "";

  /**
   *
   */
  this.totalPrice = 0;

  this.amountPlus = function (lnk) {
    var inp = byTag("INPUT", lnk.parentNode)[0];
    if (typeof inp === "undefined") return;
    var amount = parseInt(inp.value);
    if (!isNaN(amount) && amount >= 0) {
      amount++;
    } else {
      amount = 1;
    }
    inp.value = amount;
  };

  this.amountMinus = function (lnk, zero) {
    if (typeof zero == "undefined") zero = false;

    var inp = byTag("INPUT", lnk.parentNode)[0];
    if (typeof inp === "undefined") return;
    var amount = parseInt(inp.value);
    if (!isNaN(amount) && amount >= 0) {
      amount--;
      if (amount < 0) amount = 0;
    } else {
      amount = 1;
    }
    if (amount == 0 && !zero) {
      amount = 1;
    }
    inp.value = amount;
  };

  /**
   * @returns {string}
   */
  function collectSet() {
    var seriesSet = [];
    var iId, mId, amount;
    if ($$$("series-set")) {
      var prices = byTag("SPAN", $$$("series-set"));
      for (var i = 0, l = prices.length; i < l; i++) {
        if (
          prices[i].id.indexOf("item2-") === 0 &&
          prices[i].id.indexOf("-price") !== -1 &&
          prices[i].id.indexOf("-price-") === -1
        ) {
          iId = parseInt(
            prices[i].id.replace("item2-", "").replace("-price", "")
          );
          mId = $$$("item2-" + iId + "-material")
            ? parseInt($$$("item2-" + iId + "-material").value)
            : 0;
          amount = parseInt($$$("item2-" + iId + "-amount").value);

          if (isNaN(iId)) iId = 0;
          if (isNaN(mId)) mId = 0;
          if (isNaN(iId)) amount = 0;

          if (isNaN(amount)) {
            amount = 0;
          }

          seriesSet.push(iId + "-" + mId + "-" + amount);
        }
      }
    } else if ($$$("single-item-id")) {
      iId = parseInt($$$("single-item-id").value);
      mId = $$$("item-" + iId + "-material")
        ? parseInt($$$("item-" + iId + "-material").value)
        : 0;
      amount = parseInt($$$("item-" + iId + "-amount").value);

      if (isNaN(iId)) iId = 0;
      if (isNaN(mId)) mId = 0;
      if (isNaN(iId)) amount = 0;

      if (isNaN(amount)) {
        amount = 0;
      }

      seriesSet.push(iId + "-" + mId + "-" + amount);
    }
    return seriesSet.join(";");
  }

  this.setInfo = function () {
    var seriesSet = collectSet();
    //console.log(this.url + '?set-info=' + encodeURIComponent(seriesSet));
    AJAX.lookup(
      this.url + "?set-info=" + encodeURIComponent(seriesSet),
      function (respond) {
        respond = AJAX.jsonDecode(respond);
        if (!respond) return;

        if ($$$("info-delivery")) {
          $$$("info-delivery").innerHTML = priceFormat(respond[0]);
        }
        if ($$$("info-assembly")) {
          if (Math.abs(respond[1]) != 0 || respond[3] == "") {
            $$$("info-assembly").innerHTML = priceFormat(respond[1]);
            $$$("info-assembly").className = "";
          } else {
            $$$("info-assembly").innerHTML = respond[3];
            $$$("info-assembly").className = "zero";
          }
        }

        if ($$$("text-delivery")) {
          var spans = byTag("SPAN", $$$("text-delivery"));
          for (var i in spans) {
            if (typeof spans[i].className == "undefined") continue;
            if (spans[i].className.indexOf("info-delivery") != -1) {
              spans[i].innerHTML =
                "<b>" + priceFormat(respond[0]) + "</b> руб.";
            }
            if (spans[i].className.indexOf("info-assembly") != -1) {
              if (Math.abs(respond[1]) != 0 || respond[3] == "") {
                spans[i].innerHTML =
                  "<b>" + priceFormat(respond[1]) + "</b> руб.";
              } else {
                spans[i].innerHTML = "<b>" + respond[3] + "</b>";
              }
            }
            if (spans[i].className.indexOf("info-unloading") != -1) {
              spans[i].innerHTML =
                "<b>" + priceFormat(respond[2]) + "</b> руб.";
            }
          }
        }
      }
    );
  };

  this.buyOneClick = function (itemId) {
    var seriesSet = collectSet();
    oPopup.loadUrl(
      "Купить в один клик",
      this.urlBuyOneClick + "?set=" + encodeURIComponent(seriesSet) + "&ajax=1",
      false,
      false,
      true
    );
  };

  onLoad(function () {
    elCartItem = $$$("cart-item");
    elCartTarget = $$$("cart-cnt");
  });

  this.add = function (elForm, addSet) {
    addSet = !(typeof addSet === "undefined" || !addSet);

    // Формируем URL для отправки запроса
    var url = elForm.action + "?";
    var inputs = byTag("INPUT", elForm);
    for (var i = 0, l = inputs.length; i < l; i++) {
      url += inputs[i].name + "=" + encodeURIComponent(inputs[i].value) + "&";
    }
    var selects = byTag("SELECT", elForm);
    for (i = 0, l = selects.length; i < l; i++) {
      url += selects[i].name + "=" + encodeURIComponent(selects[i].value) + "&";
    }
    url += "ajax=1";
    console.log('DEBUG: Cart URL:', url);
    //console.log(url);
    AJAX.lookup(url, function (respond) {
      console.log('DEBUG: Cart response:', respond);
      //console.log(respond);
      respond = AJAX.jsonDecode(respond);
      if (!respond) return;

      if (addSet) {
        var btn1, btn2, btn3;
        btn1 = elForm.findOne(".add2basket");
        btn2 = elForm.findOne(".buy1click");
        btn3 = elForm.findOne(".go2basket");
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

      displayTotal(respond);
    });
  };

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
  function itemsAmountWord(amount) {
    amount = parseInt(amount);
    if (isNaN(amount)) amount = 0;

    if (amount >= 10) {
      amount += "";
      amount = parseInt(amount.substr(amount.length - 2));
    }
    if (10 <= amount && amount <= 20) {
      return "товаров";
    }

    amount += "";
    amount = parseInt(amount.substr(amount.length - 1));
    if (
      amount == 0 ||
      amount == 5 ||
      amount == 6 ||
      amount == 7 ||
      amount == 8 ||
      amount == 9
    ) {
      return "товаров";
    }
    if (amount == 1) {
      return "товар";
    }
    if (amount == 2 || amount == 3 || amount == 4) {
      return "товарa";
    }
    return "товаров";
  }

  /**
   *
   */
  var prevOptions = {};

  var calcOptionsTimeout = 0;

  /**
   * При изменении опций заказа в корзине вызывается этот метод и стоимость опций пересчитывается
   */
  this.calcOptions = function () {
    if (calcOptionsTimeout) {
      clearTimeout(calcOptionsTimeout);
    }
    calcOptionsTimeout = setTimeout(function () {
      var form = $$$("cart-options");
      if (!form) return;

      // При выборе доставки "До трансп.компании", остальные опции выключаются
      if ($$$("delivery-3").checked) {
        $$$("unloading-0").checked = true;
        $$$("unloading-1").disabled = true;
        $$$("unloading-2").disabled = true;

        $$$("assembly-0").checked = true;
        $$$("assembly-1").disabled = true;

        $$$("garbage-0").checked = true;
        $$$("garbage-1").disabled = true;
      } else {
        $$$("unloading-1").disabled = false;
        $$$("unloading-2").disabled = false;
        $$$("assembly-1").disabled = false;
        $$$("garbage-1").disabled = false;
      }

      // Показать/скрыть ползунок выбора расстояния за МКАД
      if ($$$("delivery-2").checked) {
        $$$("delivery-distance-block").style.display = "";
      } else {
        $$$("delivery-distance-block").style.display = "none";
      }

      // Показать/скрыть ползунок выбора этажа для разгрузки
      if ($$$("unloading-1").checked || $$$("unloading-2").checked) {
        $$$("unloading-floor-block").style.display = "";
      } else {
        $$$("unloading-floor-block").style.display = "none";
      }

      var i, l, name;
      var options = {};
      var inputs = byTag("INPUT", form);
      for (i = 0, l = inputs.length; i < l; i++) {
        if (!inputs[i].name) {
          continue;
        }
        if (
          (inputs[i].type == "radio" || inputs[i].type == "checkbox") &&
          !inputs[i].checked
        ) {
          continue;
        }
        options[inputs[i].name] = inputs[i].value;
      }

      var changed = false;
      for (i in options) {
        if (
          typeof prevOptions[i] == "undefined" ||
          prevOptions[i] != options[i]
        ) {
          changed = true;
          break;
        }
      }
      if (!changed) return;
      prevOptions = options;

      var url = oCart.url + "?ajax=1&options-calc=1";
      for (i in options) {
        url += "&" + encodeURIComponent(i) + "=" + options[i];
      }
      AJAX.lookup(url, function (respond) {
        respond = AJAX.jsonDecode(respond);
        if (!respond) return;

        if ($$$("delivery-info")) {
          $$$("delivery-info").innerHTML = respond.delivery.info;
        }
        if ($$$("delivery-price")) {
          $$$("delivery-price").innerHTML = priceFormat(respond.delivery.price);
        }

        if ($$$("unloading-info")) {
          $$$("unloading-info").innerHTML = respond.unloading.info;
        }
        if ($$$("unloading-price")) {
          $$$("unloading-price").innerHTML = priceFormat(
            respond.unloading.price
          );
        }

        if ($$$("assembly-info")) {
          $$$("assembly-info").innerHTML = respond.assembly.info;
        }
        if ($$$("assembly-price")) {
          $$$("assembly-price").innerHTML = priceFormat(respond.assembly.price);
        }

        if ($$$("garbage-info")) {
          $$$("garbage-info").innerHTML = respond.garbage.info;
        }
        if ($$$("garbage-price")) {
          $$$("garbage-price").innerHTML = priceFormat(respond.garbage.price);
        }

        var optionsPrice =
          respond.delivery.price +
          respond.unloading.price +
          respond.assembly.price +
          respond.garbage.price;

        if ($$$("options-price1")) {
          $$$("options-price1").innerHTML = priceFormat(optionsPrice);
        }
        if ($$$("options-price2")) {
          $$$("options-price2").innerHTML = priceFormat(optionsPrice);
        }
        if ($$$("total-price")) {
          $$$("total-price").innerHTML = priceFormat(
            oCart.totalPrice + optionsPrice
          );
        }
      });
    }, 100);
  };
  onLoad(function () {
    oCart.calcOptions();
    if ($$$("options-calc-nojs")) {
      $$$("options-calc-nojs").style.display = "none";
    }
  });
})();

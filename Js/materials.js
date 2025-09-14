/**
 * Выбор материала при покупке товара
 */
var oMaterials = new (function () {
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
  this.onChangePageSize = function (subOpened) {};

  /**
   * Функция, кот. будет вызываться при выборе материала
   * @type {function}
   */
  this.onSave = function () {};

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
  var elPopup = false,
    elPopupContent = false;

  this.setRowSize = function (level, rowSize) {
    if (level == 1) {
      matsInRowLevel1 = rowSize;
    } else if (level == 2) {
      matsInRowLevel2 = rowSize;
    } else if (level == 3) {
      matsInRowLevel3 = rowSize;
    }
  };

  this.refresh = function () {
    openedItemId = 0;
    selectedMatId = 0;
    itemActiveMats = [];
    elPopup = false;
    elPopupContent = false;
  };

  function init() {
    var nojsBlock = byTag("DIV");
    var i, l, j, k;

    $(".material-nojs").hide();

    elPopup = $$$("materials-popup");
    if (!elPopup) return;
    elPopupContent = $$$("materials-popup-content");

    oMaterials.onChangePageSize(false);
  }

  onLoad(function () {
    init();

    /**
     * Для всех материалов в дереве находим их родительский материал и собираем хеш (id материала => id родителя)
     */
    function p(parentMatId, materials) {
      for (var matId in materials) {
        mat2parent[matId] = parentMatId;
        if (materials[matId].has_sub) {
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
  this.open = function (itemId, matId) {
    if (typeof this.items2mats[itemId] == "undefined") return;
    openedItemId = itemId;
    if (!elPopup) {
      init();
      if (!elPopup) {
        return;
      }
    }

    elPopup.style.display = "block";

    var i, l;

    // Вычиляем активный для данного товара материал и цепочку его родителей
    if (typeof matId == "undefined" || !matId || matId == "0") {
      selectedMatId = $$$("item-" + openedItemId + "-material").value;
      showSelectMaterial();
    } else {
      selectedMatId = matId;
      $$$("item-" + openedItemId + "-material").value = matId;
    }

    itemActiveMats = [selectedMatId];
    var m = selectedMatId;
    while (mat2parent[m] != 0) {
      m = mat2parent[m];
      itemActiveMats.push(m);
    }

    // Цена
    displayMatPrice(selectedMatId);

    // Заполняем попап материалами верхнего уровня
    var mIds = [];
    for (i = 0, l = this.items2mats[openedItemId].length; i < l; i++) {
      mIds.push(this.items2mats[openedItemId][i].material_id);
    }
    elPopupContent.innerHTML = materialsHtml(mIds, 1);

    this.onChangePageSize(false);
  };

  /**
   * Открываем список подматериалов 2-го уровня
   * @param	{int}	mId
   */
  this.openLevel2 = function (mId) {
    // Отображаем плашку для 2-го уровня материалов
    var elLevel2 = $$$("materials-level2");
    if (!elLevel2) return;
    elLevel2.style.display = "";

    // Перемещаем плашку так, чтобы она шла сразу под текущим рядом материалов (рядом, в кот. нах-ся кликнутый материал)
    var elMaterial = $$$("material-" + mId);
    var elRow = elMaterial.parentNode;
    while (elRow.className.indexOf("materials-row") === -1) {
      elRow = elRow.parentNode;
    }
    elRow.parentNode.insertBefore(elLevel2, elRow.nextSibling);

    // Подсвечиваем кликнутый материал
    highlightMaterial(mId);

    // Отображаем в плашке название кликнутого материала
    var m = matById(mId).material;
    $$$("materials-level2-title").innerHTML = m.name;

    // Перемещаем хвостик плашки
    $$$("materials-level2-tail").style.left =
      elPos(elMaterial).x - elPos(elRow).x + "px";

    // Отображаем материалы 2-го уровня
    var mIds = [];
    for (var i in this.tree[mId].sub) {
      mIds.push(this.tree[mId].sub[i].id);
    }
    $$$("materials-level2-content").innerHTML = materialsHtml(mIds, 2);

    this.onChangePageSize(true);

    scrollToOpenedBLock(elLevel2);
  };

  /**
   *
   */
  this.closeLevel2 = function () {
    var elLevel2 = $$$("materials-level2");
    if (!elLevel2) return;
    elLevel2.style.display = "none";

    this.onChangePageSize(false);
  };

  /**
   * Открываем список подматериалов 3-го уровня
   * @param	{int}	mId
   */
  this.openLevel3 = function (mId) {
    // Отображаем плашку для 3-го уровня материалов
    var elLevel3 = $$$("materials-level3");
    if (!elLevel3) return;
    elLevel3.style.display = "";

    // Перемещаем плашку так, чтобы она шла сразу под текущим рядом материалов (рядом, в кот. нах-ся кликнутый материал)
    var elMaterial = $$$("material-" + mId);
    var elRow = elMaterial.parentNode;
    while (elRow.className.indexOf("materials-row") === -1) {
      elRow = elRow.parentNode;
    }
    elRow.parentNode.insertBefore(elLevel3, elRow.nextSibling);

    // Подсвечиваем кликнутый материал
    highlightMaterial(mId);

    // Отображаем в плашке название кликнутого материала
    var m = matById(mId).material;
    $$$("materials-level3-title").innerHTML = m.name;

    // Перемещаем хвостик плашки
    $$$("materials-level3-tail").style.left =
      elPos(elMaterial).x - elPos(elRow).x + "px";

    // Отображаем материалы 2-го уровня
    var p = mat2parent[mId];
    var mIds = [];
    for (var i in this.tree[p].sub[mId].sub) {
      mIds.push(this.tree[p].sub[mId].sub[i].id);
    }
    $$$("materials-level3-content").innerHTML = materialsHtml(mIds, 3);

    this.onChangePageSize(true);

    scrollToOpenedBLock(elLevel3);
  };

  this.closeLevel3 = function () {
    var elLevel3 = $$$("materials-level3");
    if (!elLevel3) return;
    elLevel3.style.display = "none";

    this.onChangePageSize(true);
  };

  /**
   * @param	{object}	levelBlock
   */
  function scrollToOpenedBLock(levelBlock) {
    return;
    var topSpace = 300;
    var bSize = elSize(levelBlock).h;
    var bPos = elPos(levelBlock).y;
    var scrollNow = D.body.scrollTop
      ? D.body.scrollTop
      : D.documentElement.scrollTop;
    var wSize = winSize().h;

    var scrollTo = 0;

    if (bPos > scrollNow + topSpace && bPos + bSize < scrollNow + wSize) {
      // Блок уже полностью виден в окне
      return;
    } else if (bSize < wSize - topSpace) {
      // Блок небольшой и может полностью уместиться в окно
      scrollTo = bPos - (wSize - bSize) + 50;
    } else {
      // Блок большой, показываем его верхнюю часть
      scrollTo = bPos - topSpace;
    }

    if (scrollTo) {
      D.body.scrollTop = scrollTo;
      D.documentElement.scrollTop = scrollTo;
    }
  }

  /**
   * Сохранить выбор и закрыть попап
   */
  this.save = function () {
    if (!openedItemId) return;

    var matInfo = showSelectMaterial();

    // Цепочка материалов от выбранного до верхнего уровня
    itemActiveMats = [selectedMatId];
    var m = selectedMatId;
    while (mat2parent[m] != 0) {
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
      {
        // price
        current: matPrice4Item(topM.id),
        old: matPriceOld4Item(topM.id),
        in: matPriceIn4Item(topM.id),
      },
      {
        // material
        id: selectedMatId,
        name: matInfo.name,
        image: matInfo.image,
        topId: topM.id,
      }
    );
  };

  /**
   *
   */
  function showSelectMaterial() {
    if (!openedItemId)
      return {
        resultImage: false,
        name: "",
        image: false,
      };
    var mat = matById(selectedMatId).material;

    var resultImage = $$$("item-" + openedItemId + "-image");
    var resultName = $$$("item-" + openedItemId + "-name");
    var resultMaterialVal = $$$("item-" + openedItemId + "-material");

    // Вычисляем картинку выбранного материала
    var image;
    if (mat.image.ext) {
      image = mat.image;
      resultImage.innerHTML =
        '<img src="/Uploads/Material/' +
        image.id +
        "_76x61_0." +
        image.ext +
        '" width="76" height="61" alt="">';
    } else {
      image = false;
      resultImage.innerHTML = "";
    }

    // Название
    var name = [mat.name];
    var tmpId = mat.id;
    while (mat2parent[tmpId] != 0) {
      tmpId = mat2parent[tmpId];

      name.push(matById(tmpId).material.name);
    }
    name.reverse();
    name = name.join(" / ");
    resultName.innerHTML = name;

    // Цена
    displayMatPrice(selectedMatId);

    // Скрытый <input>
    resultMaterialVal.value = selectedMatId;

    oCart.setInfo();

    return {
      resultImage: resultImage,
      name: name,
      image: image,
    };
  }

  /**
   * @param	{int}	matId
   */
  function displayMatPrice(matId) {
    var topM = matById(matId).top;
    var price = matPrice4Item(topM.id);
    if (price > 0) {
      // $$$('item-' + openedItemId + '-price').className = '';
      $$$("item-" + openedItemId + "-price").innerHTML = priceFormat(price);
      if ($$$("item-" + openedItemId + "-price-old")) {
        $$$("item-" + openedItemId + "-price-old").style.display = "";
        $$$("item-" + openedItemId + "-price-old").innerHTML = priceFormat(
          matPriceOld4Item(topM.id)
        );
      }
      if ($$$("item-" + openedItemId + "-price-in")) {
        $$$("item-" + openedItemId + "-price-in").style.display = "";
        $$$("item-" + openedItemId + "-price-in").innerHTML = priceFormat(
          matPriceIn4Item(topM.id)
        );
      }
    } else {
      $$$("item-" + openedItemId + "-price").className = "price-by-request";
      $$$("item-" + openedItemId + "-price").innerHTML = "Цена по запросу";
      if ($$$("item-" + openedItemId + "-price-old")) {
        $$$("item-" + openedItemId + "-price-old").style.display = "none";
      }
      if ($$$("item-" + openedItemId + "-price-in")) {
        $$$("item-" + openedItemId + "-price-in").style.display = "none";
      }
    }
  }

  /**
   * Выбор материала
   * @param	{int}	mId
   */
  this.selectMaterial = function (mId) {
    highlightMaterial(mId);

    this.closeLevel3();
    this.closeLevel2();

    // Запоминаем, какой материал был выбран для товара
    selectedMatId = mId;

    // Сохраняем выбор и закрываем попап
    this.save();
  };

  this.openrows = function () {
    if ($(".item-materials").hasClass("kopen")) {
      $(".item-materials").removeClass("kopen");
      $(".dis").hide();
      $(".klink-dashed").text("Показать все");
    } else {
      $(".dis").show();
      $(".item-materials").addClass("kopen");
      $(".klink-dashed").text("Свернуть");
    }
  };

  /**
   * Посвечиваем (визуально выделяем) блок с заданным материалом
   * @param	{int}	mId
   */
  function highlightMaterial(mId) {
    var m = $$$("material-" + mId);

    // Снимаем визуальное выделение со всех материалов на том же уровне
    var levelBlock = m.parentNode;
    while (levelBlock.className.indexOf("materials-level") === -1) {
      levelBlock = levelBlock.parentNode;
    }
    var mats = byTag("A", levelBlock);
    for (var i = 0, l = mats.length; i < l; i++) {
      if (
        mats[i].className == "material" ||
        mats[i].className.indexOf("material ") !== -1
      ) {
        mats[i].className = mats[i].className.replace(" active", "");
      }
    }

    // Выделяем выбранный материал
    m.className += " active";
  }

  /**
   * Получаем цену на товар с заданным материалом
   * @param	{int}	mId
   * @return	{int}
   */
  function matPrice4Item(mId) {
    for (
      var i = 0, l = oMaterials.items2mats[openedItemId].length;
      i < l;
      i++
    ) {
      if (oMaterials.items2mats[openedItemId][i].material_id == mId) {
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
  function matPriceOld4Item(mId) {
    for (
      var i = 0, l = oMaterials.items2mats[openedItemId].length;
      i < l;
      i++
    ) {
      if (oMaterials.items2mats[openedItemId][i].material_id == mId) {
        return oMaterials.items2mats[openedItemId][i]["price-old"];
      }
    }
    return 0;
  }

  /**
   * Получаем входную цену (для дилеров) на товар с заданным материалом
   * @param	{int}	mId
   * @return	{int}
   */
  function matPriceIn4Item(mId) {
    for (
      var i = 0, l = oMaterials.items2mats[openedItemId].length;
      i < l;
      i++
    ) {
      if (oMaterials.items2mats[openedItemId][i].material_id == mId) {
        return oMaterials.items2mats[openedItemId][i]["price-in"];
      }
    }
    return 0;
  }

  /**
   * Получаем объект материала из дерева по ID
   * @param	{int}	mId
   * @return	{object}		{material: {object}, top: {object}, level: {int}}
   */
  function matById(mId) {
    var mIdTmp = mId;
    var ids = [mIdTmp];

    if (typeof mat2parent[mIdTmp] != "undefined") {
      while (mat2parent[mIdTmp] != 0) {
        mIdTmp = mat2parent[mIdTmp];
        ids.push(mIdTmp);
      }
      ids.reverse();

      var idsIter = 0;
      var mat = oMaterials.tree[ids[idsIter]];
      while (mat.id != mId) {
        idsIter++;
        mat = mat.sub[ids[idsIter]];
      }
      return {
        material: mat,
        top: oMaterials.tree[ids[0]],
        level: ids.length,
      };
    }
  }

  /**
   * Получаем HTML-код для отображения множества материалов в попапе
   * Материалы группируются в строки <div class="materials-row"></div> по 5 шт. в каждой
   * @param	{object}	mIds	Массив ID материалов
   * @param	{int}		level	Уровень вложенности этих материалов
   */
  function materialsHtml(mIds, level) {
    var matsInRow = 7;
    if (level == 1) {
      matsInRow = matsInRowLevel1;
    } else if (level == 2) {
      matsInRow = matsInRowLevel2;
    } else if (level == 3) {
      matsInRow = matsInRowLevel3;
    }

    if (mIds.length < matsInRow * 2) {
      $(".klink-dashed").hide();
    }

    var html = "",
      rowHtml = "",
      rclass = "";
    for (var i = 0, l = mIds.length; i < l; i++) {
      if (i > matsInRow * 2 && mIds.length > matsInRow * 2) rclass = "dis";
      if (i != 0 && i % matsInRow == 0) {
        html +=
          '<div class="materials-row ' +
          rclass +
          '">' +
          rowHtml +
          '<div class="cl"></div></div>';
        rowHtml = "";
      }
      rowHtml += oneMaterialHtml(mIds[i]);
    }

    if (mIds.length > matsInRow * 2) rclass = "dis";
    html +=
      '<div class="materials-row ' +
      rclass +
      '">' +
      rowHtml +
      '<div class="cl"></div></div>';

    if (level == 1) {
      html +=
        '<div class="materials-level2" id="materials-level2" style="display: none">' +
        '<div class="level-tail" id="materials-level2-tail"></div>' +
        '<a class="close" href="javascript:void(0)" onclick="oMaterials.closeLevel2(); return false;"></a>' +
        '<strong class="level-title" id="materials-level2-title"></strong>' +
        '<div class="level-help">Нажмите на изображение материала, чтобы выбрать его</div>' +
        '<div id="materials-level2-content">' +
        "</div>" +
        "</div>";
    } else {
      html +=
        '<div class="materials-level3" id="materials-level3" style="display: none">' +
        '<div class="level-tail" id="materials-level3-tail"></div>' +
        '<a class="close" href="javascript:void(0)" onclick="oMaterials.closeLevel3(); return false;"></a>' +
        '<strong class="level-title" id="materials-level3-title"></strong>' +
        '<div id="materials-level3-content">' +
        "</div>" +
        "</div>";
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

    var price = matPrice4Item(topM.id);
    price = price > 0 ? priceFormat(price) + " р." : "по запросу";
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
})();

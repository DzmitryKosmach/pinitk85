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
   * Скролим вверх
   */
  this.scrollToSectionPhoto = function () {
    // Небольшая задержка для обновления DOM после выбора материала
    setTimeout(function() {
      const block = document.getElementById("material-selected");
      if (block) {
        const top = block.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: "smooth" });
      }
    }, 100);
  };

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

    this.scrollToSectionPhoto();

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

    if (level === 1) {
      matsInRow = matsInRowLevel1;
    } else if (level === 2) {
      matsInRow = matsInRowLevel2;
    } else if (level === 3) {
      matsInRow = matsInRowLevel3;
    }

    // Управляем кнопкой "Показать все" только при построении уровня 1
    if (level === 1) {
      if (mIds.length > matsInRow) {
        $(".klink-dashed").show();
      } else {
        // $(".klink-dashed").hide();
      }
    }

    var html = "";
    var rowHtml = "";
    var rowIndex = 0; // счётчик строк

    for (var i = 0; i < mIds.length; i++) {
      // начало новой строки
      if (i > 0 && i % matsInRow === 0) {
        // для level 1: только первая строка видна, остальные — dis
        var rowClass = level === 1 && rowIndex > 0 ? "dis" : "";
        html +=
          '<div class="materials-row ' +
          rowClass +
          ' relative z-[100] w-[330px] h-[125px] mb-5">' +
          rowHtml +
          '<div class="cl"></div></div>';
        rowHtml = "";
        rowIndex++;
      }

      // добавляем материал
      rowHtml += oneMaterialHtml(mIds[i]);
    }

    // добавляем последнюю строку
    if (rowHtml) {
      var lastRowClass = level === 1 && rowIndex > 0 ? "dis" : "";
      html +=
        '<div class="materials-row ' +
        lastRowClass +
        ' relative z-[100] w-[330px] h-[125px] mb-5">' +
        rowHtml +
        '<div class="cl"></div></div>';
    }

    // добавляем уровень
    if (level === 1) {
      html +=
        '<div class="materials-level2 hidden relative -top-5 z-50 p-5 bg-white border border-[#7a8a93] rounded" id="materials-level2">' +
        '<div class="level-tail" id="materials-level2-tail"></div>' +
        '<a class="close" href="javascript:void(0)" onclick="oMaterials.closeLevel2(); return false;">×</a>' +
        '<strong class="level-title block py-2 pl-[2px] text-[#737769] font-normal text-[16px]" id="materials-level2-title"></strong>' +
        '<div class="level-help block pb-4 pl-[2px] text-gray-500 text-[12px]">Нажмите на изображение материала, чтобы выбрать его</div>' +
        '<div id="materials-level2-content" class="relative z-[1000] overflow-y-scroll overflow-x-hidden h-[300px] w-[300px] p-5"></div>' +
        "</div>";
    } else {
      html +=
        '<div class="materials-level3 hidden relative -top-5 z-50 p-5 bg-white border border-[#7a8a93] rounded" id="materials-level3">' +
        '<div class="level-tail" id="materials-level3-tail"></div>' +
        '<a class="close" href="javascript:void(0)" onclick="oMaterials.closeLevel3(); return false;"><span class="flex justify-end text-red-600 hover:text-red-800 !text-5xl leading-none no-underline hover:no-underline">×</span></a>' +
        '<strong class="level-title block py-2 pl-[2px] text-[#737769] font-normal text-[16px]" id="materials-level3-title"></strong>' +
        '<div id="materials-level3-content" class="relative z-[1000] overflow-y-scroll overflow-x-hidden h-[300px]"></div>' +
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

    // 🔥 Исправлено: " р." → " ₽"
    var price = matPrice4Item(topM.id);
    price = price > 0 ? priceFormat(price) + " ₽" : "по запросу";

    var anchorClasses = "material block relative float-left box-border w-[110px] h-[135px] m-0 no-underline rounded hover:bg-gray-50";
    var materialInClasses = "material-in box-border w-[95px] h-full";
    var imageClasses = "image block relative w-[76px] h-[61px] mb-[2px] border-2 border-white shadow";
    var imageBigWrapClasses = "image-big absolute z-[800] w-[164px] h-[132px] left-[-32px] top-[-65px] bg-white border-2 border-white shadow";
    var infoClasses = "info material-info h-[55px] overflow-hidden pl-[2px]";
    var nameClasses = "name table-cell align-middle h-[26px] leading-[13px] text-[11px] text-[#0075c2] underline";
    var priceClasses = "price block whitespace-nowrap text-[11px]";
    if (active) {
      nameClasses += " font-semibold";
      imageClasses = imageClasses.replace("border-white", "border-[#ffa800]");
    }

    var hasSubIcon = "";
    if (m.has_sub == 1) {
      hasSubIcon = "";
    }

    var html =
      '<a id="material-' +
      m.id +
      '" class="' +
      className +
      ' ' +
      anchorClasses +
      '" href="javascript:void(0)" onclick="' +
      onclick +
      '; return false;">' +
      hasSubIcon +
      '<div class="' +
      materialInClasses +
      '">' +
      '<div class="' +
      imageClasses +
      '">' +
      image +
      (imageBig
        ? '<div class="' +
        imageBigWrapClasses +
        '" style="display:none;">' +
        imageBig +
        "</div>"
        : "") +
      "</div>" +
      '<div class="' +
      infoClasses +
      '">' +
      '<div class="' +
      nameClasses +
      '">' +
      m.name +
      "</div>" +
      '<div class="' +
      priceClasses +
      '">' +
      price +
      "</div>" +
      "</div>" +
      "</div>" +
      "</a>";
    return html;
  }
})();

/**
 * @param	{number}	price
 * @return	{string}
 */
function priceFormat(price){
	price = number_format(Math.abs(price), 2, '.', ' ');
	price = price.replace('.00', '');
	return price
}


onLoad(function(){
	// В списке серий при наведении на серию должны подгружать картинки материалов
	var series = find('.showcase .js-element');
	if(!series.length) return;

	for(var i = 0, l = series.length; i < l; i++){
		if(typeof(series[i]) === 'undefined') continue;
		addHandler(
			series[i],
			'mouseover',
			function(){
				var materials = this.findOne('.materials');
				if(!materials) return;

				var preview = materials.dataset['preview'];
				if(!preview) return;
				materials.dataset['preview'] = '';

				var images = AJAX.jsonDecode(preview);
				var imgEl;
				var imagesLoaded = [];
				for(var k = 0, m = images.length; k < m; k++){
					if(typeof(images[k]) === 'undefined') continue;
					imgEl = D.createElement('IMG');
					addHandler(
						imgEl,
						'load',
						function(){
							imagesLoaded.push(this);
							if(imagesLoaded.length == images.length){
								materials.innerHTML = '';
								for(var n = 0, s = imagesLoaded.length; n < s; n++){
									if(typeof(imagesLoaded[n]) === 'undefined') continue;
									materials.appendChild(imagesLoaded[n]);
								}
							}
						}
					);
					imgEl.width = '20';
					imgEl.height = '20';
					imgEl.alt = images[k]['name'];
					imgEl.title = images[k]['name'];
					imgEl.src = images[k]['img'];
				}
			}
		);
	}
});


/**
 * Рассчёт и отображение сумарной цены комплекта серии
 */
function seriesSetPrice(){
	var setBlock = $$$('series-set'); if(!setBlock) return;
	var prices = byTag('SPAN', setBlock);
	var iId, price, priceOld, priceIn, amount;
	var total = 0, totalOld = 0, totalIn = 0;
	for(var i = 0, l = prices.length; i < l; i++){
		if(prices[i].id.indexOf('item2-') === 0 && prices[i].id.indexOf('-price') !== -1 && prices[i].id.indexOf('-price-') === -1){
			iId = prices[i].id.replace('item2-', '').replace('-price', '');

			price = parseFloat(prices[i].innerHTML.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', ''));
			if(isNaN(price)) price = 0;

			if($$$('item2-' + iId + '-price-old')){
				priceOld = parseFloat($$$('item2-' + iId + '-price-old').innerHTML.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', ''));
				if(isNaN(priceOld)) priceOld = 0;
			}else{
				priceOld = 0;
			}

			if($$$('item2-' + iId + '-price-in')){
				priceIn = parseFloat($$$('item2-' + iId + '-price-in').innerHTML.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', ''));
				if(isNaN(priceIn)) priceIn = 0;
			}else{
				priceIn = 0;
			}

			amount = parseInt($$$('item2-' + iId + '-amount').value);
			if(isNaN(amount)) amount = 0;

			total += price * amount;

			if(!priceOld){
				priceOld = price;
			}
			totalOld += priceOld * amount;
			totalIn += priceIn * amount;
		}
	}
	if(total){
		$$$('series-set-price').innerHTML = priceFormat(total);
		$$$('series-set-price').className = '';
	}else{
		$$$('series-set-price').innerHTML = 'Цена по запросу';
		$$$('series-set-price').className = 'price-by-request';
	}
	if(totalOld != total){
		$$$('series-set-price-old').style.display = '';
		$$$('series-set-price-old').innerHTML = priceFormat(totalOld);
	}else{
		$$$('series-set-price-old').style.display = 'none';
		$$$('series-set-price-old').innerHTML = '0';
	}

	if($$$('series-set-price-in')){
		$$$('series-set-price-in').innerHTML = priceFormat(totalIn);
	}

	oCart.setInfo();
}


/* Зум картинок материалов серии */
onLoad(function(){
	$(document).on('click', '.series-materials a.level1', function(){
		$.fancybox.open(
			this.nextElementSibling.innerHTML,
			{
				type			: 'inline',
				autoSize		: true,
				padding			: 20,
				tpl				: {
					closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><span class="text-red-600 hover:text-red-800 !text-5xl leading-none no-underline hover:no-underline">×</span></a>'
				}
			}
		);
		return false;
	});
});


/**
 * @param 	{int}		itemId
 * @param	{boolean}	hoverOn
 */
function seriesSetHoverTable(itemId, hoverOn){
	var point = $$$('series-set-point-' + itemId);
	if(!point) return;
	if(hoverOn){
		point.className = 'hover';
	}else{
		point.className = '';
	}
}


/**
 * @param 	{int}		itemId
 * @param	{boolean}	hoverOn
 */
function seriesSetHoverPoint(itemId, hoverOn){
	var row = $$$('series-set-row-' + itemId);
	if(!row) return;
	if(hoverOn){
		row.className = 'hover';
	}else{
		row.className = '';
	}
}





/**
 * Показать/Скрыть полное описание серии
 */
function showHideDescription(){
	var text = $$$('series-text');
	var lnk = $$$('series-text-lnk');
	if(text.className.indexOf('text-opened') !== -1){
		// Hide
		text.className = text.className.replace('text-opened', 'text-closed');
		lnk.className = lnk.className.replace('opened', 'closed');

	}else{
		// Show
		text.className = text.className.replace('text-closed', 'text-opened');
		lnk.className = lnk.className.replace('closed', 'opened');
	}
}
onLoad(function(){
	var text = $$$('series-text');
	var textIn = $$$('series-text-in');
	if(!text || !textIn) return;
	if(text.offsetHeight >= textIn.offsetHeight){
		//text.className = text.className.replace('text-opened', 'text-closed');
		text.className = text.className.replace('text-closed', 'text-opened');
		$$$('series-text-lnk').style.display = 'none';
	}
});

/**
 * Показываем попап с товаром при клике на него в перечне всех элементов серии
 * @param url
 * @param itemId
 */
function showItemPopup1(url, itemId){
	url += url.indexOf('?') == -1 ? '?' : '&';
	url += 'ajax=1';
	//console.log(url);

	oPopup.loadUrl(
		'', url, false,
		function(){
			oMaterials.refresh();
			oMaterials.onChangePageSize = function(subOpened){
				if(subOpened){
					findOne('.fancybox-inner').style.height = (winSize().h - 130) + 'px';
				}else{
					findOne('.fancybox-inner').style.height = 'auto';
				}
				$.fancybox.reposition();
			};
			oMaterials.onSave = function(price, material){
				// Отображаем цену с выбранным материалом (если есть элементы на странице)
				var priceEl     = $$$('item1-' + itemId + '-price');
				var priceOldEl  = $$$('item1-' + itemId + '-price-old');
				var priceInEl   = $$$('item1-' + itemId + '-price-in');
				var materialInp = $$$('item1-' + itemId + '-material');
				var matsPreviewsBlock = $$$('item1-' + itemId + '-materials-preview');

				if (priceEl && priceOldEl) {
					if(price.current > 0){
						priceEl.className = '';
						priceEl.innerHTML = priceFormat(price.current);
						if(price.old){
							priceOldEl.style.display = '';
							priceOldEl.innerHTML = priceFormat(price.old);
						}else{
							priceOldEl.style.display = 'none';
							priceOldEl.innerHTML = '0';
						}
						if(priceInEl){
							priceInEl.style.display = '';
							priceInEl.innerHTML = priceFormat(price.in);
						}
					}else{
						priceEl.innerHTML = 'по запросу';
						priceOldEl.innerHTML = '0';

						priceEl.className = 'price-by-request';
						priceOldEl.style.display = 'none';
						if(priceInEl){
							priceInEl.style.display = 'none';
						}
					}
				}

				// Записываем ID материала в соответствующее поле, если оно есть
				if (materialInp) {
					materialInp.value = material.id;
				}

				// Выделяем выбранный материал в полоске с их превьюшками (если она есть)
				if (matsPreviewsBlock) {
					var matsPreviews = byTag('DIV', matsPreviewsBlock);
					var previewNum = 1;

					for(var i = 0, l = matsPreviews.length; i < l; i++){
						if(matsPreviews[i].id == 'item1-' + itemId + '-m-' + material.topId){
							matsPreviews[i].className = 'active';
							previewNum = i + 1;
						}else{
							matsPreviews[i].className = '';
						}
					}
					matsPreviewsBlock.className = 'materials pos-' + Math.ceil(previewNum/7);
				}
			};

			var matInput = $$$('item1-' + itemId + '-material');
			var matId = matInput ? matInput.value : 0;
			oMaterials.open(itemId, matId);

			//oPopup.fixSize();
			//oPopup.fixPos();
		}
	);
}


/**
 * Показываем попап с товаром при клике на него в списке товаров, входящих в комплект серии
 * @param url
 * @param itemId
 */
function showItemPopup2(url, itemId){

	url += url.indexOf('?') == -1 ? '?' : '&';
	url += 'ajax=1';
	oPopup.loadUrl(
		'', url, false,
		function(){
			oMaterials.refresh();
			oMaterials.onChangePageSize = function(subOpened){
				if(subOpened){
					findOne('.fancybox-inner').style.height = (winSize().h - 130) + 'px';
				}else{
					findOne('.fancybox-inner').style.height = 'auto';
				}
				$.fancybox.reposition();
			};
			oMaterials.onSave = function(price, material){
				// Отображаем цену с выбранным материалом
				if(price.current){
					$$$('item2-' + itemId + '-price').className = '';
					$$$('item2-' + itemId + '-price').innerHTML = priceFormat(price.current);
					if(price.old){
						$$$('item2-' + itemId + '-price-old').style.display = '';
						$$$('item2-' + itemId + '-price-old').innerHTML = priceFormat(price.old);
					}else{
						$$$('item2-' + itemId + '-price-old').style.display = 'none';
						$$$('item2-' + itemId + '-price-old').innerHTML = '0';
					}
					if($$$('item2-' + itemId + '-price-in')){
						$$$('item2-' + itemId + '-price-old').style.display = '';
						$$$('item2-' + itemId + '-price-in').innerHTML = priceFormat(price.in);
					}
				}else{
					$$$('item2-' + itemId + '-price').innerHTML = 'по запросу';
					$$$('item2-' + itemId + '-price-old').innerHTML = '0';

					$$$('item2-' + itemId + '-price').className = 'price-by-request';
					$$$('item2-' + itemId + '-price-old').style.display = 'none';
					if($$$('item2-' + itemId + '-price-in')){
						$$$('item2-' + itemId + '-price-old').style.display = 'none';
					}
				}

				// Записываем ID материала в соответствующее поле
				$$$('item2-' + itemId + '-material').value = material.id;

				// Отображаем картинку и название выбранного материала
				var matImageBlock = $$$('item2-' + itemId + '-matimage');
				if(matImageBlock){
					if(material.image){
						matImageBlock.title = material.name;
						matImageBlock.innerHTML = '<img src="/Uploads/Material/' + material.image.id + '_21x21_0.' + material.image.ext + '" width="21" height="21" alt="">';
					}else{
						matImageBlock.title = '';
						matImageBlock.innerHTML = material.name;
					}
				}

				seriesSetPrice();
			};
			oMaterials.open(
				itemId,
				$$$('item2-' + itemId + '-material').value
			);

			//oPopup.fixSize();
			//oPopup.fixPos();
		}
	);
}

function handleOnclick(e){
	e.stopImmediatePropagation();
    return false;
}
